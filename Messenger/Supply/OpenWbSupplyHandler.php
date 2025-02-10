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
use BaksDev\Core\Deduplicator\DeduplicatorInterface;
use BaksDev\Core\Messenger\MessageDelay;
use BaksDev\Core\Messenger\MessageDispatchInterface;
use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;
use BaksDev\Wildberries\Package\Api\SupplyOpen\PostWildberriesSupplyOpenRequest;
use BaksDev\Wildberries\Package\Entity\Supply\WbSupply;
use BaksDev\Wildberries\Package\Repository\Supply\OpenWbSupply\OpenWbSupplyInterface;
use BaksDev\Wildberries\Package\Repository\Supply\WbSupplyCurrentEvent\WbSupplyCurrentEventInterface;
use BaksDev\Wildberries\Package\Type\Supply\Status\WbSupplyStatus\WbSupplyStatusNew;
use BaksDev\Wildberries\Package\UseCase\Supply\Open\WbSupplyOpenDTO;
use BaksDev\Wildberries\Package\UseCase\Supply\Open\WbSupplyOpenHandler;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class OpenWbSupplyHandler
{
    public function __construct(
        #[Target('wildberriesPackageLogger')] private LoggerInterface $logger,
        private OpenWbSupplyInterface $OpenWbSupply,
        private PostWildberriesSupplyOpenRequest $WildberriesSupplyOpen,
        private WbSupplyCurrentEventInterface $WbSupplyCurrentEvent,
        private WbSupplyOpenHandler $wbSupplyOpenHandler,
        private CentrifugoPublishInterface $CentrifugoPublish,
        private MessageDispatchInterface $MessageDispatch,
        private DeduplicatorInterface $deduplicator
    ) {}

    /**
     * Метод открывает поставку Wildberries если статус системной поставки New (Новая)
     * и присваивает идентификатор системной
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

        /** Получаем активное событие системной поставки */
        $Event = $this->WbSupplyCurrentEvent
            ->forSupply($message->getId())
            ->find();

        if(false === $Event || false === $Event->getStatus()->equals(WbSupplyStatusNew::class))
        {
            return;
        }

        /** Получаем профиль пользователя и идентификатор системной поставки в качестве аттрибута */
        $UserProfileUid = $this->OpenWbSupply
            ->forSupply($message->getId())
            ->find();

        if(false === ($UserProfileUid instanceof UserProfileUid))
        {
            return;
        }

        /**
         * Не открываем поставку если уже имеется идентификатор Wildberries
         */
        if(false === empty($UserProfileUid->getAttr()))
        {
            return;
        }

        /**
         * Открываем поставку Wildberries и получаем идентификатор (API)
         */
        $WildberriesSupplyOpenDTO = $this->WildberriesSupplyOpen
            ->profile($UserProfileUid)
            ->open();


        if(false === $WildberriesSupplyOpenDTO)
        {
            $this->logger->critical('wildberries-package: Пробуем открыть поставку через 3 секунды');

            $this->MessageDispatch->dispatch(
                message: $message,
                stamps: [new MessageDelay('3 seconds')],
                transport: (string) $UserProfileUid,
            );

            return;
        }

        /**
         * Присваиваем системной поставке идентификатор поставки Wildberries
         */
        $WbSupplyOpenDTO = new WbSupplyOpenDTO();
        $Event->getDto($WbSupplyOpenDTO);

        $WbSupplyWildberriesDTO = $WbSupplyOpenDTO->getWildberries();
        $WbSupplyWildberriesDTO->setIdentifier($WildberriesSupplyOpenDTO->getIdentifier());

        $handle = $this->wbSupplyOpenHandler->handle($WbSupplyOpenDTO);

        if(false === ($handle instanceof WbSupply))
        {
            $this->logger->critical(
                sprintf('wildberries-package: %s: Ошибка при обновлении поставки идентификатором', $handle),
                [self::class.':'.__LINE__]
            );

            return;
        }

        $Deduplicator->save();

        $this->logger->info(
            sprintf('%s: Обновили идентификатор Wildberries поставке', $WildberriesSupplyOpenDTO->getIdentifier()),
            [self::class.':'.__LINE__,]);

        /** Отправляем сокет с идентификатором поставки */
        $this->CentrifugoPublish
            ->addData(['number' => $WildberriesSupplyOpenDTO->getIdentifier()]) // ID упаковки
            ->send((string) $message->getId(), 4);

    }

}