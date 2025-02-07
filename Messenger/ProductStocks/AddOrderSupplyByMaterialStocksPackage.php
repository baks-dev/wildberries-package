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

namespace BaksDev\Wildberries\Package\Messenger\ProductStocks;


use BaksDev\Core\Deduplicator\DeduplicatorInterface;
use BaksDev\Materials\Catalog\Repository\CurrentMaterialIdentifier\CurrentIdentifierMaterialByValueInterface;
use BaksDev\Materials\Sign\Entity\MaterialSign;
use BaksDev\Materials\Sign\Repository\MaterialSignNew\MaterialSignNewInterface;
use BaksDev\Materials\Sign\Type\Id\MaterialSignUid;
use BaksDev\Materials\Sign\UseCase\Admin\Status\MaterialSignProcessDTO;
use BaksDev\Materials\Sign\UseCase\Admin\Status\MaterialSignStatusHandler;
use BaksDev\Products\Product\Repository\CurrentProductIdentifier\CurrentProductIdentifierByConstInterface;
use BaksDev\Products\Product\Repository\ProductMaterials\ProductMaterialsInterface;
use BaksDev\Products\Product\Type\Material\MaterialUid;
use BaksDev\Products\Sign\Type\Status\ProductSignStatus\ProductSignStatusProcess;
use BaksDev\Products\Stocks\Entity\Stock\Event\ProductStockEvent;
use BaksDev\Products\Stocks\Entity\Stock\Products\ProductStockProduct;
use BaksDev\Products\Stocks\Messenger\ProductStockMessage;
use BaksDev\Products\Stocks\Repository\CurrentProductStocks\CurrentProductStocksInterface;
use BaksDev\Products\Stocks\Repository\ProductStocksById\ProductStocksByIdInterface;
use BaksDev\Products\Stocks\Type\Status\ProductStockStatus\ProductStockStatusIncoming;
use BaksDev\Products\Stocks\Type\Status\ProductStockStatus\ProductStockStatusPackage;
use BaksDev\Users\Profile\UserProfile\Repository\UserByUserProfile\UserByUserProfileInterface;
use BaksDev\Wildberries\Package\Repository\Supply\WbSupplyCurrentEvent\WbSupplyCurrentEventInterface;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(priority: -5)]
final readonly class AddOrderSupplyByMaterialStocksPackage
{
    public function __construct(
        #[Target('materialsSignLogger')] private LoggerInterface $logger,
        private ProductStocksByIdInterface $ProductStocks,
        private CurrentProductStocksInterface $currentProductStocks,
        private UserByUserProfileInterface $userByUserProfile,
        private DeduplicatorInterface $deduplicator,
        private CurrentProductIdentifierByConstInterface $CurrentProductIdentifierByConst,
        private ProductMaterialsInterface $ProductMaterials,
        private CurrentIdentifierMaterialByValueInterface $CurrentIdentifierMaterialByValue,
        private MaterialSignNewInterface $MaterialSignNew,
        private MaterialSignStatusHandler $MaterialSignStatusHandler,


        private WbSupplyCurrentEventInterface $wbSupplyCurrentEvent,
    ) {}

    /**
     * Добавляем заказы со статусом Упаковке в открытую поставку
     */
    public function __invoke(ProductStockMessage $message): void
    {
        $Deduplicator = $this->deduplicator
            ->namespace('materials-sign')
            ->deduplication([
                $message,
                self::class
            ]);

        if($Deduplicator->isExecuted())
        {
            return;
        }

        $ProductStockEvent = $this->currentProductStocks->getCurrentEvent($message->getId());

        if(!$ProductStockEvent)
        {
            $this->logger->critical('products-sign: Не найдено событие ProductStock', [$message, self::class.':'.__LINE__]);
            return;
        }

        if(false === $ProductStockEvent->equalsProductStockStatus(ProductStockStatusPackage::class))
        {
            $this->logger->notice(
                'Не добавляем заказ в поставку: Складская заявка не является Package «Упаковка»',
                [$message, self::class.':'.__LINE__]
            );

            return;
        }

        /** Получаем заказ */


        //        /**  Получаем активное событие системной поставки */
        //        $Event = $this->wbSupplyCurrentEvent->findWbSupplyEvent($message->getId());
        //
        //        if(!$Event || !$Event->getStatus()->equals(WbSupplyStatusNew::class))
        //        {
        //            return;
        //        }


        $Deduplicator->save();
    }
}
