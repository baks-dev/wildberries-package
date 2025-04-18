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

namespace BaksDev\Wildberries\Package\Repository\Package\ExistOrderPackage;

use BaksDev\Core\Doctrine\DBALQueryBuilder;
use BaksDev\Orders\Order\Entity\Order;
use BaksDev\Orders\Order\Type\Id\OrderUid;
use BaksDev\Wildberries\Package\Entity\Package\Orders\WbPackageOrder;
use BaksDev\Wildberries\Package\Entity\Package\WbPackage;
use BaksDev\Wildberries\Package\Type\Package\Status\WbPackageStatus;
use BaksDev\Wildberries\Package\Type\Package\Status\WbPackageStatus\WbPackageStatusError;
use InvalidArgumentException;

final class ExistOrderPackageRepository implements ExistOrderPackageInterface
{
    private OrderUid|false $order;

    public function __construct(private readonly DBALQueryBuilder $DBALQueryBuilder) {}

    public function forOrder(Order|OrderUid|string $order): self
    {
        if(is_string($order))
        {
            $order = new OrderUid($order);
        }

        if($order instanceof Order)
        {
            $order = $order->getId();
        }

        $this->order = $order;

        return $this;
    }

    /**
     * Метод проверяет, имеется ли заказ в упаковке (без статуса ERROR)
     */
    public function isExist(): bool
    {
        if(false === ($this->order instanceof OrderUid))
        {
            throw new InvalidArgumentException('Invalid Argument Order');
        }

        $dbal = $this->DBALQueryBuilder->createQueryBuilder(self::class);

        $dbal->from(WbPackageOrder::class, 'wb_order');

        $dbal
            ->where('wb_order.id = :order')
            ->setParameter(
                key: 'order',
                value: $this->order,
                type: OrderUid::TYPE
            );

        $dbal
            ->andWhere('wb_order.status != :status')
            ->setParameter(
                key: 'status',
                value: new WbPackageStatus(WbPackageStatusError::class),
                type: WbPackageStatus::TYPE
            );

        $dbal->join(
            'wb_order',
            WbPackage::class,
            'package',
            'package.event = wb_order.event'
        );

        return $dbal->fetchExist();
    }
}