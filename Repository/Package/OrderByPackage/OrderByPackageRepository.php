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

namespace BaksDev\Wildberries\Package\Repository\Package\OrderByPackage;

use BaksDev\Core\Doctrine\DBALQueryBuilder;
use BaksDev\Materials\Sign\BaksDevMaterialsSignBundle;
use BaksDev\Materials\Sign\Entity\Code\MaterialSignCode;
use BaksDev\Materials\Sign\Entity\Event\MaterialSignEvent;
use BaksDev\Orders\Order\Entity\Invariable\OrderInvariable;
use BaksDev\Orders\Order\Entity\Order;
use BaksDev\Orders\Order\Entity\Products\OrderProduct;
use BaksDev\Orders\Order\Type\Id\OrderUid;
use BaksDev\Wildberries\Orders\Entity\Event\WbOrdersEvent;
use BaksDev\Wildberries\Orders\Entity\Sticker\WbOrdersSticker;
use BaksDev\Wildberries\Orders\Entity\WbOrders;
use BaksDev\Wildberries\Package\Entity\Package\Event\WbPackageEvent;
use BaksDev\Wildberries\Package\Entity\Package\Orders\WbPackageOrder;
use BaksDev\Wildberries\Package\Type\Package\Event\WbPackageEventUid;
use InvalidArgumentException;


final class OrderByPackageRepository implements OrderByPackageInterface
{
    private WbPackageEventUid|false $event = false;

    public function __construct(private readonly DBALQueryBuilder $DBALQueryBuilder) {}

    public function forPackageEvent(WbPackageEvent|WbPackageEventUid|string $event): self
    {
        if(is_string($event))
        {
            $event = new WbPackageEventUid();
        }

        if($event instanceof WbPackageEvent)
        {
            $event = $event->getId();
        }

        $this->event = $event;

        return $this;
    }


    /**
     * Метод получает все заказы в упаковке со стикерами
     */
    public function findAll(): ?array
    {
        if(false === ($this->event instanceof WbPackageEventUid))
        {
            throw new InvalidArgumentException('Invalid Argument WbPackageEvent');
        }

        $dbal = $this->DBALQueryBuilder
            ->createQueryBuilder(self::class)
            ->bindLocal();

        $dbal
            ->addSelect('package_order.id AS order')
            ->from(WbPackageOrder::class, 'package_order');

        $dbal
            ->where('package_order.event = :event')
            ->setParameter(
                key: 'event',
                value: $this->event,
                type: WbPackageEventUid::TYPE
            );

        $dbal
            ->addSelect('package_event.total')
            ->leftJoin(
                'package_order',
                WbPackageEvent::class,
                'package_event',
                'package_event.id = package_order.event'
            );


        //        $dbal
        //            ->addSelect('wb_sticker.sticker')
        //            ->leftJoin(
        //                'package_order',
        //                WbOrdersSticker::class,
        //                'wb_sticker',
        //                'wb_sticker.main = package_order.id'
        //            );


        $dbal
            ->leftJoin(
                'package_order',
                Order::class,
                'ord',
                'ord.id = package_order.id'
            );

        $dbal
            ->addSelect('invariable.number')
            ->leftJoin(
                'package_order',
                OrderInvariable::class,
                'invariable',
                'invariable.main = ord.id'
            );


        $dbal
            ->addSelect('ord_product.product AS product_event')
            ->addSelect('ord_product.offer AS product_offer')
            ->addSelect('ord_product.variation AS product_variation')
            ->addSelect('ord_product.modification AS product_modification')
            ->leftJoin(
                'ord',
                OrderProduct::class,
                'ord_product',
                'ord_product.event = ord.event'
            );


        if(class_exists(BaksDevMaterialsSignBundle::class))
        {
            $dbal
                ->addSelect('sign_event.status')
                ->leftOneJoin(
                    'package_order',
                    MaterialSignEvent::class,
                    'sign_event',
                    'sign_event.ord = package_order.id ',

                );


            $dbal
                ->addSelect(
                    "
                CASE
                   WHEN code.name IS NOT NULL 
                   THEN CONCAT ( '/upload/".$dbal->table(MaterialSignCode::class)."' , '/', code.name)
                   ELSE NULL
                END AS code_image
            "
                )
                ->addSelect("code.ext AS code_ext")
                ->addSelect("code.cdn AS code_cdn")
                ->addSelect("code.event AS code_event")
                ->addSelect("code.code AS code_string")
                ->leftJoin(
                    'sign_event',
                    MaterialSignCode::class,
                    'code',
                    'code.main = sign_event.main'
                );


        }


        //
        //        $dbal
        //            ->leftJoin(
        //                'package_order',
        //                WbOrders::class,
        //                'wb_orders',
        //                'wb_orders.id = package_order.id'
        //            );
        //
        //        $dbal
        //            ->addSelect('wb_orders_event.barcode')
        //            ->leftJoin(
        //                'wb_orders',
        //                WbOrdersEvent::class,
        //                'wb_orders_event',
        //                'wb_orders_event.id = wb_orders.event'
        //            );


        return $dbal
            ->enableCache('wildberries-package')
            ->fetchAllAssociative();
    }

}