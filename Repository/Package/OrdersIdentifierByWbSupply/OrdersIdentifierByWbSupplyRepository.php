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

namespace BaksDev\Wildberries\Package\Repository\Package\OrdersIdentifierByWbSupply;

use BaksDev\Core\Doctrine\DBALQueryBuilder;
use BaksDev\Delivery\Type\Id\DeliveryUid;
use BaksDev\Orders\Order\Entity\Event\Posting\OrderPosting;
use BaksDev\Orders\Order\Entity\Invariable\OrderInvariable;
use BaksDev\Orders\Order\Entity\Order;
use BaksDev\Orders\Order\Entity\User\Delivery\OrderDelivery;
use BaksDev\Orders\Order\Entity\User\OrderUser;
use BaksDev\Orders\Order\Type\Id\OrderUid;
use BaksDev\Wildberries\Orders\Type\DeliveryType\TypeDeliveryFbsWildberries;
use BaksDev\Wildberries\Package\Entity\Package\Orders\WbPackageOrder;
use BaksDev\Wildberries\Package\Entity\Package\Supply\WbPackageSupply;
use BaksDev\Wildberries\Package\Entity\Package\WbPackage;
use BaksDev\Wildberries\Package\Entity\Supply\WbSupply;
use BaksDev\Wildberries\Package\Type\Package\Status\WbPackageStatus;
use BaksDev\Wildberries\Package\Type\Package\Status\WbPackageStatus\WbPackageStatusAdd;
use BaksDev\Wildberries\Package\Type\Supply\Id\WbSupplyUid;
use Generator;
use InvalidArgumentException;


final class OrdersIdentifierByWbSupplyRepository implements OrdersIdentifierByWbSupplyInterface
{
    private WbSupplyUid|false $supply;

    private string|false $status = false;

    private bool $print = false;

    public function __construct(private readonly DBALQueryBuilder $DBALQueryBuilder) {}

    public function forSupply(WbSupply|WbSupplyUid|string $supply): self
    {
        if(is_string($supply))
        {
            $supply = new WbSupplyUid($supply);
        }

        if($supply instanceof WbSupply)
        {
            $supply = $supply->getId();
        }

        $this->supply = $supply;

        return $this;
    }

    public function onlyAdOrders(): self
    {
        $this->status = WbPackageStatusAdd::class;

        return $this;
    }

    public function onlyPrint(): OrdersIdentifierByWbSupplyInterface
    {
        $this->print = true;

        return $this;
    }

    /**
     * Метод возвращает все идентификаторы заказов в поставке и о статус в качестве атрибута
     */
    public function findAll(): Generator|false
    {
        if(false === ($this->supply instanceof WbSupplyUid))
        {
            throw new InvalidArgumentException('Invalid Argument WbSupply');
        }

        $dbal = $this->DBALQueryBuilder->createQueryBuilder(self::class);

        $dbal
            ->from(WbPackageSupply::class, 'supply')
            ->where('supply.supply = :supply')
            ->setParameter(
                key: 'supply',
                value: $this->supply,
                type: WbSupplyUid::TYPE
            );

        if($this->print)
        {
            $dbal->andWhere('supply.print IS NOT TRUE');
        }

        $dbal
            ->join(
                'supply',
                WbPackage::class,
                'package',
                'package.id = supply.main'
            );

        $dbal
            ->addSelect('package_order.id AS value')
            ->addSelect('package_order.status AS option')
            ->leftJoin(
                'package',
                WbPackageOrder::class,
                'package_order',
                'package_order.event = package.event '.($this->status ? ' AND package_order.status = :status' : '')
            );

        if($this->status !== false)
        {
            $dbal->setParameter(
                key: 'status',
                value: $this->status,
                type: WbPackageStatus::TYPE
            );
        }

        $dbal
            ->leftJoin(
                'package_order',
                Order::class,
                'orders',
                'orders.id = package_order.id',
            );

        /*$dbal
            ->addSelect('invariable.number AS attr')
            ->leftJoin(
                'package_order',
                OrderInvariable::class,
                'invariable',
                'invariable.main = package_order.id',
            );*/


        $dbal
            ->addSelect('orders_posting.value AS attr')
            ->leftJoin(
                'package_order',
                OrderPosting::class,
                'orders_posting',
                'orders_posting.main = package_order.id',
            );

        $dbal
            ->leftJoin(
                'orders',
                OrderUser::class,
                'usr',
                'usr.event = orders.event',
            );

        $dbal
            ->join(
                'usr',
                OrderDelivery::class,
                'delivery',
                'delivery.usr = usr.id AND delivery.delivery = :delivery',
            )
            ->setParameter(
                key: 'delivery',
                value: new DeliveryUid(TypeDeliveryFbsWildberries::class),
                type: DeliveryUid::TYPE,
            );

        $result = $dbal
            // ->enableCache('Namespace', 3600)
            ->fetchAllHydrate(OrderUid::class);

        return $result->valid() ? $result : false;
    }


}