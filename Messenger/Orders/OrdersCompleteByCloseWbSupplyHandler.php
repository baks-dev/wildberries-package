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

namespace BaksDev\Wildberries\Package\Messenger\Orders;

use BaksDev\Core\Deduplicator\DeduplicatorInterface;
use BaksDev\DeliveryTransport\UseCase\Admin\Package\Completed\CompletedProductStockDTO;
use BaksDev\DeliveryTransport\UseCase\Admin\Package\Completed\CompletedProductStockHandler;
use BaksDev\DeliveryTransport\UseCase\Admin\Package\Delivery\DeliveryProductStockDTO;
use BaksDev\Orders\Order\Type\Id\OrderUid;
use BaksDev\Products\Stocks\Entity\Stock\Event\ProductStockEvent;
use BaksDev\Products\Stocks\Entity\Stock\ProductStock;
use BaksDev\Products\Stocks\Repository\ProductStocksByOrder\ProductStocksByOrderInterface;
use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;
use BaksDev\Wildberries\Package\Entity\Supply\Event\WbSupplyEvent;
use BaksDev\Wildberries\Package\Messenger\Supply\WbSupplyMessage;
use BaksDev\Wildberries\Package\Repository\Package\OrdersIdentifierByWbSupply\OrdersIdentifierByWbSupplyInterface;
use BaksDev\Wildberries\Package\Repository\Supply\OpenWbSupply\OpenWbSupplyInterface;
use BaksDev\Wildberries\Package\Repository\Supply\WbSupplyCurrentEvent\WbSupplyCurrentEventInterface;
use BaksDev\Wildberries\Package\Type\Supply\Status\WbSupplyStatus\WbSupplyStatusClose;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(priority: 0)]
final readonly class OrdersCompleteByCloseWbSupplyHandler
{
    public function __construct(
        #[Target('wildberriesPackageLogger')] private LoggerInterface $logger,
        private WbSupplyCurrentEventInterface $wbSupplyCurrentEvent,
        private OrdersIdentifierByWbSupplyInterface $OrdersIdentifierByWbSupply,
        private OpenWbSupplyInterface $OpenWbSupply,
        private ProductStocksByOrderInterface $ProductStocksByOrder,
        private CompletedProductStockHandler $CompletedProductStockHandler,
        private DeduplicatorInterface $deduplicator
    ) {}

    /**
     * Метод закрывает выполненные заказы при закрытии поставки
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

        if(false === $WbSupplyEvent->getStatus()->equals(WbSupplyStatusClose::class))
        {
            return;
        }

        /* Получаем профиль пользователя и идентификатор поставки в качестве аттрибута */
        $UserProfileUid = $this->OpenWbSupply
            ->forSupply($message->getId())
            ->find();

        if(false === ($UserProfileUid instanceof UserProfileUid) || empty($UserProfileUid->getAttr()))
        {
            return;
        }

        /** Получаем все добавленные заказы в поставке */

        $orders = $this->OrdersIdentifierByWbSupply
            ->forSupply($WbSupplyEvent->getMain())
            ->onlyAdOrders()
            ->findAll();

        if(false === $orders || false === $orders->valid())
        {
            return;
        }

        /** @var OrderUid $OrderUid */
        foreach($orders as $OrderUid)
        {
            $invoices = $this->ProductStocksByOrder->findByOrder($OrderUid);

            /** @var ProductStockEvent $ProductStockEvent */
            foreach($invoices as $ProductStockEvent)
            {
                /**
                 * @var DeliveryProductStockDTO $DeliveryProductStockDTO
                 */
                $CompletedProductStockDTO = new CompletedProductStockDTO($ProductStockEvent->getId());
                $ProductStockEvent->getDto($CompletedProductStockDTO);

                $ProductStock = $this->CompletedProductStockHandler->handle($CompletedProductStockDTO);

                if(false === ($ProductStock instanceof ProductStock))
                {
                    $this->logger->critical(
                        sprintf('wildberries-package: Ошибка %s при изменении складской заявки при закрытии поставки', $ProductStock),
                        [$message, self::class.':'.__LINE__]
                    );
                }
            }
        }

        $Deduplicator->save();
    }
}