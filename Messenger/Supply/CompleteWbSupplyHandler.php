<?php
/*
 *  Copyright 2025.  Baks.dev <admin@baks.dev>
 *  
 *  Permission is hereby granted, free of charge, to any person obtaining a copy
 *  of this software and associated documentation files (the "Software"), to deal
 *  in the Software without restriction, including without limitation the rights
 *  to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 *  copies of the Software, and to permit persons to whom the Software is furnished
 *  to do so, subject to the following conditions:
 *  
 *  The above copyright notice and this permission notice shall be included in all
 *  copies or substantial portions of the Software.
 *  
 *  THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 *  IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 *  FITNESS FOR A PARTICULAR PURPOSE AND NON INFRINGEMENT. IN NO EVENT SHALL THE
 *  AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 *  LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 *  OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 *  THE SOFTWARE.
 */

declare(strict_types=1);

namespace BaksDev\Wildberries\Package\Messenger\Supply;

use BaksDev\Centrifugo\Server\Publish\CentrifugoPublishInterface;
use BaksDev\Core\Deduplicator\DeduplicatorInterface;
use BaksDev\Core\Messenger\MessageDelay;
use BaksDev\Core\Messenger\MessageDispatchInterface;
use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;
use BaksDev\Wildberries\Package\Api\SupplySticker\WildberriesSupplySticker;
use BaksDev\Wildberries\Package\Entity\Supply\WbSupply;
use BaksDev\Wildberries\Package\Repository\Supply\OpenWbSupply\OpenWbSupplyInterface;
use BaksDev\Wildberries\Package\Repository\Supply\WbSupplyCurrentEvent\WbSupplyCurrentEventInterface;
use BaksDev\Wildberries\Package\Type\Supply\Status\WbSupplyStatus\WbSupplyStatusClose;
use BaksDev\Wildberries\Package\UseCase\Supply\Sticker\WbSupplyStickerDTO;
use BaksDev\Wildberries\Package\UseCase\Supply\Sticker\WbSupplyStickerHandler;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(priority: 1)]
final readonly class CompleteWbSupplyHandler
{
    public function __construct(
        #[Target('wildberriesPackageLogger')] private LoggerInterface $logger,
        private OpenWbSupplyInterface $openWbSupply,
        private WbSupplyCurrentEventInterface $wbSupplyCurrentEvent,
        private WbSupplyStickerHandler $WbSupplyStickerHandler,
        private WildberriesSupplySticker $wildberriesSupplySticker,
        private CentrifugoPublishInterface $CentrifugoPublish,
        private MessageDispatchInterface $MessageDispatch,
        private DeduplicatorInterface $deduplicator
    ) {}

    /**
     * Метод получает стикер закрытой поставки Wildberries для печати и присваивает статус Complete
     */
    public function __invoke(WbSupplyMessage $message): void
    {
        $Deduplicator = $this->deduplicator
            ->namespace('wildberries-package')
            ->deduplication([$message->getId(), self::class]);

        if($Deduplicator->isExecuted())
        {
            return;
        }

        /**
         * Получаем активное событие системной поставки
         */
        $Event = $this->wbSupplyCurrentEvent
            ->forSupply($message->getId())
            ->find();

        if(false === $Event || false === $Event->getStatus()->equals(WbSupplyStatusClose::STATUS))
        {
            return;
        }

        /**
         * Не получаем стикер поставки если в ней нет заказов
         */
        if(0 === $Event->getTotal())
        {
            return;
        }


        /* Получаем профиль пользователя и идентификатор поставки в качестве аттрибута */
        $UserProfileUid = $this->openWbSupply
            ->forSupply($message->getId())
            ->find();

        if(false === ($UserProfileUid instanceof UserProfileUid) || empty($UserProfileUid->getAttr()))
        {
            return;
        }

        /** Получаем QR поставки в svg */
        $WildberriesSupplyStickerDTO = $this->wildberriesSupplySticker
            ->profile($UserProfileUid)
            ->withSupply($UserProfileUid->getAttr())
            ->sticker();

        if(false === $WildberriesSupplyStickerDTO)
        {
            $this->MessageDispatch->dispatch(
                message: $message,
                stamps: [new MessageDelay('3 seconds')],
                transport: (string) $UserProfileUid
            );

            $this->logger->critical('Пробуем получить стикер поставки через 3 сек');

            return;
        }

        if($WildberriesSupplyStickerDTO->getIdentifier() !== $UserProfileUid->getAttr())
        {
            $this->logger->critical('Не соответствует идентификатор системной поставки и Wildberries',
                [
                    'expected' => $UserProfileUid->getAttr(),
                    'received' => $WildberriesSupplyStickerDTO->getIdentifier(),
                    self::class.':'.__LINE__,
                ]);

            return;
        }

        /**
         * Обновляем системную поставку Wildberries
         */

        $WbSupplyStickerDTO = $Event->getDto(WbSupplyStickerDTO::class);
        $WbSupplyWildberriesDTO = $WbSupplyStickerDTO->getWildberries();
        $WbSupplyWildberriesDTO->setSticker($WildberriesSupplyStickerDTO->getSticker());

        $handle = $this->WbSupplyStickerHandler->handle($WbSupplyStickerDTO);

        if(!$handle instanceof WbSupply)
        {
            $this->logger->critical(sprintf('%s: Ошибка при сохранении стикера поставки', $handle));
            return;
        }

        $Deduplicator->save();

        /** Отправляем сокет комплектации */
        $this->CentrifugoPublish
            ->addData(['identifier' => 'complete']) // ID упаковки
            ->send((string) $message->getId(), 4);

        $this->logger->info('Присвоили стикер и укомплектовали поставку',
            [
                'identifier' => $WildberriesSupplyStickerDTO->getIdentifier(),
                self::class.':'.__LINE__,
            ]);

    }
}