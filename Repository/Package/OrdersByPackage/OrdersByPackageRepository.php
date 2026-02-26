<?php
/*
 *  Copyright 2026.  Baks.dev <admin@baks.dev>
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

namespace BaksDev\Wildberries\Package\Repository\Package\OrdersByPackage;

use BaksDev\Core\Doctrine\DBALQueryBuilder;
use BaksDev\Materials\Sign\BaksDevMaterialsSignBundle;
use BaksDev\Materials\Sign\Entity\Code\MaterialSignCode;
use BaksDev\Materials\Sign\Entity\Event\MaterialSignEvent;
use BaksDev\Orders\Order\Entity\Event\Posting\OrderPosting;
use BaksDev\Orders\Order\Entity\Invariable\OrderInvariable;
use BaksDev\Orders\Order\Entity\Order;
use BaksDev\Orders\Order\Entity\Products\OrderProduct;
use BaksDev\Wildberries\Package\Entity\Package\Event\WbPackageEvent;
use BaksDev\Wildberries\Package\Entity\Package\Orders\WbPackageOrder;
use BaksDev\Wildberries\Package\Entity\Package\Supply\WbPackageSupply;
use BaksDev\Wildberries\Package\Entity\Supply\Wildberries\WbSupplyWildberries;
use BaksDev\Wildberries\Package\Type\Package\Event\WbPackageEventUid;
use Generator;
use InvalidArgumentException;


final class OrdersByPackageRepository implements OrdersByPackageInterface
{
    private WbPackageEventUid|false $event = false;

    public function __construct(private readonly DBALQueryBuilder $DBALQueryBuilder) {}

    public function forPackageEvent(WbPackageEvent|WbPackageEventUid|string $event): self
    {
        if(is_string($event))
        {
            $event = new WbPackageEventUid($event);
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
     *
     * @return Generator<int, OrdersByPackageResult>|false
     */
    public function findAll(): Generator|false
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
            ->addSelect('package_order.status AS order_status')
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


        $dbal
            ->leftJoin(
                'package_event',
                WbPackageSupply::class,
                'package_supply',
                'package_supply.main = package_event.main'
            );


        $dbal
            ->addSelect('supply.identifier AS supply')
            ->leftJoin(
                'package_supply',
                WbSupplyWildberries::class,
                'supply',
                'supply.main = package_supply.supply'
            );


        $dbal
            ->leftJoin(
                'package_order',
                Order::class,
                'ord',
                'ord.id = package_order.id'
            );

        //        $dbal
        //            ->addSelect('invariable.number')
        //            ->leftJoin(
        //                'package_order',
        //                OrderInvariable::class,
        //                'invariable',
        //                'invariable.main = ord.id'
        //            );


        $dbal
            ->addSelect('orders_posting.value AS number')
            ->leftJoin(
                'package_order',
                OrderPosting::class,
                'orders_posting',
                'orders_posting.main = ord.id',
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
                ->addSelect('sign_event.status AS code_status')
                ->addSelect("sign_event.id AS code_event")
                ->leftOneJoin(
                    'package_order',
                    MaterialSignEvent::class,
                    'sign_event',
                    'sign_event.ord = package_order.id',
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
                ->addSelect("code.code AS code_string")
                ->leftJoin(
                    'sign_event',
                    MaterialSignCode::class,
                    'code',
                    'code.main = sign_event.main'
                );
        }

        return $dbal
            ->enableCache('wildberries-package')
            ->fetchAllHydrate(OrdersByPackageResult::class);
    }

    /** @return array{int, OrdersByPackageResult}|false */
    public function toArray(): array|false
    {
        $Generator = $this->findAll();

        return (false === $Generator || false === $Generator->valid()) ? false : iterator_to_array($Generator);
    }

}