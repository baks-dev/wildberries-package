<?php
/*
 *  Copyright 2023.  Baks.dev <admin@baks.dev>
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
use BaksDev\Orders\Order\Entity\Event\OrderEvent;
use BaksDev\Orders\Order\Entity\Order;
use BaksDev\Orders\Order\Entity\Products\OrderProduct;
use BaksDev\Orders\Order\Type\Id\OrderUid;
use BaksDev\Products\Category\Entity\Offers\ProductCategoryOffers;
use BaksDev\Products\Category\Entity\Offers\Variation\ProductCategoryVariation;
use BaksDev\Products\Product\Entity\Event\ProductEvent;
use BaksDev\Products\Product\Entity\Info\ProductInfo;
use BaksDev\Products\Product\Entity\Offers\Image\ProductOfferImage;
use BaksDev\Products\Product\Entity\Offers\ProductOffer;
use BaksDev\Products\Product\Entity\Offers\Variation\Image\ProductVariationImage;
use BaksDev\Products\Product\Entity\Offers\Variation\ProductVariation;
use BaksDev\Products\Product\Entity\Photo\ProductPhoto;
use BaksDev\Products\Product\Entity\Trans\ProductTrans;
use BaksDev\Wildberries\Orders\Entity\Sticker\WbOrdersSticker;
use BaksDev\Wildberries\Orders\Entity\WbOrders;
use BaksDev\Wildberries\Package\Entity\Package\Event\WbPackageEvent;
use BaksDev\Wildberries\Package\Entity\Package\Orders\WbPackageOrder;
use BaksDev\Wildberries\Package\Type\Package\Event\WbPackageEventUid;
use BaksDev\Wildberries\Package\Type\Package\Id\WbPackageUid;
use BaksDev\Wildberries\Package\Type\Package\Status\WbPackageStatus;
use BaksDev\Wildberries\Package\Type\Package\Status\WbPackageStatus\WbPackageStatusNew;
use BaksDev\Wildberries\Products\Entity\Cards\WbProductCardVariation;
use Generator;


final class OrderByPackage implements OrderByPackageInterface
{
    private DBALQueryBuilder $DBALQueryBuilder;

    public function __construct(
        DBALQueryBuilder $DBALQueryBuilder,
    )
    {
        $this->DBALQueryBuilder = $DBALQueryBuilder;
    }


    /**
     * Метод получает заказы в упаковке со стикерами
     */
    public function getOrdersPackage(WbPackageEventUid $event): ?array
    {
        $qb = $this->DBALQueryBuilder
            ->createQueryBuilder(self::class)
            ->bindLocal();

        $qb->from(WbPackageOrder::TABLE, 'package_order');

        $qb
            ->where('package_order.event = :event')
            ->setParameter('event', $event, WbPackageEventUid::TYPE);

        $qb
            ->addSelect('package_event.total')
            ->leftJoin(
                'package_order',
                WbPackageEvent::TABLE,
                'package_event',
                'package_event.id = package_order.event'
            );


        $qb
            ->addSelect('wb_sticker.sticker')
            ->leftJoin(
                'package_order',
                WbOrdersSticker::TABLE,
                'wb_sticker',
                'wb_sticker.main = package_order.id'
            );


        $qb
            ->leftJoin(
                'package_order',
                Order::TABLE,
                'ord',
                'ord.id = package_order.id'
            );


        $qb
            ->addSelect('ord_product.product AS product_event')
            ->addSelect('ord_product.offer AS product_offer')
            ->addSelect('ord_product.variation AS product_variation')
            ->leftJoin(
                'ord',
                OrderProduct::TABLE,
                'ord_product',
                'ord_product.event = ord.event'
            );

        $qb
            ->leftJoin('ord_product',
                ProductVariation::TABLE,
                'product_variation',
                'product_variation.id = ord_product.variation'
            );

        $qb
            ->addSelect('card_variation.barcode')
            ->leftJoin(
                'product_variation',
                WbProductCardVariation::TABLE,
                'card_variation',
                'card_variation.variation = product_variation.const'
            );

        return $qb
            ->enableCache('WildberriesPackage')
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
            ->from(WbPackageOrder::TABLE, 'orders');

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
                WbOrders::TABLE,
                'wb_orders',
                'wb_orders.id = orders.id'
            );


        return $qb->fetchAllHydrate(OrderUid::class);

    }
}