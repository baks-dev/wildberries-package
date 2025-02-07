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

namespace BaksDev\Wildberries\Package\Repository\Package\OrdersIdentifierByPackage;

use BaksDev\Core\Doctrine\DBALQueryBuilder;
use BaksDev\Delivery\Type\Id\DeliveryUid;
use BaksDev\Orders\Order\Entity\Invariable\OrderInvariable;
use BaksDev\Orders\Order\Entity\User\Delivery\OrderDelivery;
use BaksDev\Orders\Order\Entity\User\OrderUser;
use BaksDev\Orders\Order\Type\Id\OrderUid;
use BaksDev\Wildberries\Orders\Entity\WbOrders;
use BaksDev\Wildberries\Orders\Type\DeliveryType\TypeDeliveryFbsWildberries;
use BaksDev\Wildberries\Package\Entity\Package\Event\WbPackageEvent;
use BaksDev\Wildberries\Package\Entity\Package\Orders\WbPackageOrder;
use BaksDev\Wildberries\Package\Type\Package\Event\WbPackageEventUid;
use BaksDev\Wildberries\Package\Type\Package\Status\WbPackageStatus;
use BaksDev\Wildberries\Package\Type\Package\Status\WbPackageStatus\WbPackageStatusAdd;
use BaksDev\Wildberries\Package\Type\Package\Status\WbPackageStatus\WbPackageStatusNew;
use Generator;
use InvalidArgumentException;


final class OrdersIdentifierByPackageRepository implements OrdersIdentifierByPackageInterface
{
    private WbPackageEventUid|false $event = false;

    private string $status = WbPackageStatusNew::class;

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

    public function onlyNew(): self
    {
        $this->status = WbPackageStatusNew::class;

        return $this;
    }

    public function onlyAddSupply(): self
    {
        $this->status = WbPackageStatusAdd::class;

        return $this;
    }


    /**
     * Метод возвращает идентификаторы системных заказов и идентификаторы заказа Wildberries FBS качестве атрибута
     */
    public function findAll(): Generator|false
    {
        if(false === ($this->event instanceof WbPackageEventUid))
        {
            throw new InvalidArgumentException('Invalid Argument WbPackageEvent');
        }

        $dbal = $this->DBALQueryBuilder->createQueryBuilder(self::class);

        $dbal
            ->select('orders.id AS value')
            ->from(WbPackageOrder::class, 'orders');

        $dbal
            ->where('orders.event = :event')
            ->setParameter(
                key: 'event',
                value: $this->event,
                type: WbPackageEventUid::TYPE
            );

        $dbal
            ->andWhere('orders.status = :status')
            ->setParameter(
                key: 'status',
                value: $this->status,
                type: WbPackageStatus::TYPE
            );


        $dbal
            ->addSelect('invariable.number AS attr')
            ->leftJoin(
                'orders',
                OrderInvariable::class,
                'invariable',
                'invariable.id = orders.id',
            );

        $dbal->leftJoin(
            'invariable',
            OrderUser::class,
            'usr',
            'user.event = invariable.event',
        );

        $dbal->join(
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

        return $dbal->fetchAllHydrate(OrderUid::class);

    }
}