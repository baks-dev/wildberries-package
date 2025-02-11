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
use BaksDev\Core\Deduplicator\DeduplicatorInterface;
use BaksDev\Core\Messenger\MessageDelay;
use BaksDev\Core\Messenger\MessageDispatchInterface;
use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;
use BaksDev\Wildberries\Package\Api\GetWildberriesSupplyStickerRequest;
use BaksDev\Wildberries\Package\Api\PostWildberriesSupplyClosedRequest;
use BaksDev\Wildberries\Package\Entity\Supply\Event\WbSupplyEvent;
use BaksDev\Wildberries\Package\Repository\Supply\OpenWbSupply\OpenWbSupplyInterface;
use BaksDev\Wildberries\Package\Repository\Supply\WbSupplyCurrentEvent\WbSupplyCurrentEventInterface;
use BaksDev\Wildberries\Package\Type\Supply\Status\WbSupplyStatus\WbSupplyStatusClose;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(priority: 99)] // высокий приоритет - выполняется первым
final readonly class CloseWbSupplyHandler
{
    public function __construct(
        #[Target('wildberriesPackageLogger')] private LoggerInterface $logger,
        private OpenWbSupplyInterface $openWbSupply,
        private PostWildberriesSupplyClosedRequest $wildberriesSupplyClosed,
        private WbSupplyCurrentEventInterface $wbSupplyCurrentEvent,
        private DeduplicatorInterface $deduplicator,
        private MessageDispatchInterface $MessageDispatch,
        private GetWildberriesSupplyStickerRequest $GetWildberriesSupplyStickerRequest,
    ) {}

    /**
     * Метод закрывает поставку Wildberries если системная поставка со статусом Close (Закрыта)
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
        $WbSupplyEvent = $this->wbSupplyCurrentEvent
            ->forSupply($message->getId())
            ->find();

        if(false === ($WbSupplyEvent instanceof WbSupplyEvent))
        {
            return;
        }

        // только если статус поставки закрото
        if(false === $WbSupplyEvent->getStatus()->equals(WbSupplyStatusClose::class))
        {
            return;
        }

        /** Получаем профиль пользователя и идентификатор поставки в качестве аттрибута */
        $UserProfileUid = $this->openWbSupply
            ->forSupply($message->getId())
            ->find();

        if(false === ($UserProfileUid instanceof UserProfileUid) || empty($UserProfileUid->getAttr()))
        {
            return;
        }

        /* Закрываем поставку Wildberries Api */
        $isClose = $this->wildberriesSupplyClosed
            ->profile($UserProfileUid)
            ->withSupply($UserProfileUid->getAttr())
            ->close();

        if(false === $isClose)
        {
            $this->logger->critical('wildberries-package: Пробуем закрыть поставку Wildberries через 3 сек');

            $this->MessageDispatch->dispatch(
                message: $message,
                stamps: [new MessageDelay('3 seconds')],
                transport: (string) $UserProfileUid
            );

            return;
        }

        $Deduplicator->save();

        $this->logger->info(
            sprintf('%s: Закрыли поставку Wildberries', $UserProfileUid->getAttr()),
            [self::class.':'.__LINE__,]
        );

        /**
         * Прогреваем кеш стикера поставки
         */

        $this
            ->GetWildberriesSupplyStickerRequest
            ->profile($UserProfileUid)
            ->withSupply($UserProfileUid->getAttr())
            ->getSupplySticker();
    }
}