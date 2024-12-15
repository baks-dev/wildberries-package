<?php
/*
 *  Copyright 2024.  Baks.dev <admin@baks.dev>
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

namespace BaksDev\Wildberries\Package\Repository\Package\OrderByPackage;

use BaksDev\Core\Doctrine\DBALQueryBuilder;
use BaksDev\Orders\Order\Entity\Order;
use BaksDev\Orders\Order\Entity\Products\OrderProduct;
use BaksDev\Orders\Order\Type\Id\OrderUid;
use BaksDev\Wildberries\Orders\Entity\Event\WbOrdersEvent;
use BaksDev\Wildberries\Orders\Entity\Sticker\WbOrdersSticker;
use BaksDev\Wildberries\Orders\Entity\WbOrders;
use BaksDev\Wildberries\Package\Entity\Package\Event\WbPackageEvent;
use BaksDev\Wildberries\Package\Entity\Package\Orders\WbPackageOrder;
use BaksDev\Wildberries\Package\Type\Package\Event\WbPackageEventUid;
use BaksDev\Wildberries\Package\Type\Package\Status\WbPackageStatus;
use BaksDev\Wildberries\Package\Type\Package\Status\WbPackageStatus\WbPackageStatusNew;
use Generator;


final class OrderByPackageRepository implements OrderByPackageInterface
{

    public function __construct(private readonly DBALQueryBuilder $DBALQueryBuilder) {}


    /**
     * Метод получает заказы в упаковке со стикерами
     */
    public function getOrdersPackage(WbPackageEventUid $event): ?array
    {
        $qb = $this->DBALQueryBuilder
            ->createQueryBuilder(self::class)
            ->bindLocal();

        $qb->from(WbPackageOrder::class, 'package_order');

        $qb
            ->where('package_order.event = :event')
            ->setParameter('event', $event, WbPackageEventUid::TYPE);

        $qb
            ->addSelect('package_event.total')
            ->leftJoin(
                'package_order',
                WbPackageEvent::class,
                'package_event',
                'package_event.id = package_order.event'
            );


        $qb
            ->addSelect('wb_sticker.sticker')
            ->leftJoin(
                'package_order',
                WbOrdersSticker::class,
                'wb_sticker',
                'wb_sticker.main = package_order.id'
            );


        $qb
            ->leftJoin(
                'package_order',
                Order::class,
                'ord',
                'ord.id = package_order.id'
            );

        $qb
            ->addSelect('ord_product.product AS product_event')
            ->addSelect('ord_product.offer AS product_offer')
            ->addSelect('ord_product.variation AS product_variation')
            ->leftJoin(
                'ord',
                OrderProduct::class,
                'ord_product',
                'ord_product.event = ord.event'
            );


        $qb
            ->leftJoin(
                'package_order',
                WbOrders::class,
                'wb_orders',
                'wb_orders.id = package_order.id'
            );

        $qb
            ->addSelect('wb_orders_event.barcode')
            ->leftJoin(
                'wb_orders',
                WbOrdersEvent::class,
                'wb_orders_event',
                'wb_orders_event.id = wb_orders.event'
            );


        return $qb
            ->enableCache('wildberries-package')
            ->fetchAllAssociative();
    }


    /**
     * Метод возвращает идентификаторы системных заказов и идентификаторы заказов Wildberries качестве атрибута
     */
    public function fetchAllOrderByPackageAssociative(WbPackageEventUid $event): Generator
    {
        $qb = $this->DBALQueryBuilder->createQueryBuilder(self::class);

        $qb
            ->select('orders.id AS value')
            ->from(WbPackageOrder::class, 'orders');

        $qb
            ->where('orders.event = :event')
            ->setParameter('event', $event, WbPackageEventUid::TYPE);

        $qb
            ->andWhere('orders.status = :status')
            ->setParameter('status', new WbPackageStatus(WbPackageStatusNew::class), WbPackageStatus::TYPE);


        $qb
            ->addSelect('wb_orders.ord AS attr')
            ->join(
                'orders',
                WbOrders::class,
                'wb_orders',
                'wb_orders.id = orders.id'
            );


        return $qb->fetchAllHydrate(OrderUid::class);

    }
}