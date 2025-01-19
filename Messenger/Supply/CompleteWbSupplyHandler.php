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

use App\Kernel;
use BaksDev\Centrifugo\Server\Publish\CentrifugoPublishInterface;
use BaksDev\Wildberries\Package\Api\SupplySticker\WildberriesSupplySticker;
use BaksDev\Wildberries\Package\Entity\Supply\WbSupply;
use BaksDev\Wildberries\Package\Repository\Supply\OpenWbSupply\OpenWbSupplyInterface;
use BaksDev\Wildberries\Package\Repository\Supply\WbSupplyCurrentEvent\WbSupplyCurrentEventInterface;
use BaksDev\Wildberries\Package\Type\Supply\Status\WbSupplyStatus\WbSupplyStatusClose;
use BaksDev\Wildberries\Package\UseCase\Supply\Sticker\WbSupplyStickerDTO;
use BaksDev\Wildberries\Package\UseCase\Supply\Sticker\WbSupplyStickerHandler;
use DomainException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(priority: 1)]
final class CompleteWbSupplyHandler
{
    private LoggerInterface $logger;
    private WbSupplyStickerHandler $WbSupplyStickerHandler;
    private WbSupplyCurrentEventInterface $wbSupplyCurrentEvent;
    private WildberriesSupplySticker $wildberriesSupplySticker;
    private OpenWbSupplyInterface $openWbSupply;
    private CentrifugoPublishInterface $CentrifugoPublish;


    public function __construct(
        OpenWbSupplyInterface $openWbSupply,
        WbSupplyCurrentEventInterface $wbSupplyCurrentEvent,
        LoggerInterface $wildberriesPackageLogger,
        WbSupplyStickerHandler $WbSupplyStickerHandler,
        WildberriesSupplySticker $wildberriesSupplySticker,
        CentrifugoPublishInterface $CentrifugoPublish
    )
    {
        $this->logger = $wildberriesPackageLogger;
        $this->WbSupplyStickerHandler = $WbSupplyStickerHandler;
        $this->wbSupplyCurrentEvent = $wbSupplyCurrentEvent;
        $this->wildberriesSupplySticker = $wildberriesSupplySticker;
        $this->openWbSupply = $openWbSupply;
        $this->CentrifugoPublish = $CentrifugoPublish;
    }

    /**
     * Метод получает стикер закрытой поставки Wildberries для печати и присваивает статус Complete
     */
    public function __invoke(WbSupplyMessage $message): void
    {
        if(Kernel::isTestEnvironment())
        {
            return;
        }

        /**
         * Получаем активное событие системной поставки
         */
        $Event = $this->wbSupplyCurrentEvent->findWbSupplyEvent($message->getId());

        if(
            !$Event ||
            $Event->getTotal() === 0 ||
            !$Event->getStatus()->equals(WbSupplyStatusClose::STATUS)
        )
        {
            return;
        }

        /* Получаем профиль пользователя и идентификатор поставки в качестве аттрибута */
        $UserProfileUid = $this->openWbSupply->getWbSupply($message->getId());

        if(!$UserProfileUid || !$UserProfileUid->getAttr())
        {
            return;
        }

        $WildberriesSupplyStickerDTO = $this->wildberriesSupplySticker
            ->profile($UserProfileUid)
            ->withSupply($UserProfileUid->getAttr())
            ->request();

        if($WildberriesSupplyStickerDTO->getIdentifier() !== $UserProfileUid->getAttr())
        {
            $this->logger->critical('Не соответствует идентификатор поставки',
                [
                    'expected' => $UserProfileUid->getAttr(),
                    'received' => $WildberriesSupplyStickerDTO->getIdentifier(),
                    self::class.':'.__LINE__,
                ]);

            throw new DomainException('Не соответствует идентификатор поставки');
        }

        /** Получаем в системе поставку Wildberries */

        $WbSupplyStickerDTO = $Event->getDto(WbSupplyStickerDTO::class);
        $WbSupplyWildberriesDTO = $WbSupplyStickerDTO->getWildberries();
        $WbSupplyWildberriesDTO->setSticker($WildberriesSupplyStickerDTO->getSticker());

        $handle = $this->WbSupplyStickerHandler->handle($WbSupplyStickerDTO);

        if(!$handle instanceof WbSupply)
        {
            throw new DomainException(sprintf('%s: Ошибка при получении стикера поставки', $handle));
        }

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