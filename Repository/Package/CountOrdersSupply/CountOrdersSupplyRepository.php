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

namespace BaksDev\Wildberries\Package\Repository\Package\CountOrdersSupply;

use BaksDev\Core\Doctrine\DBALQueryBuilder;
use BaksDev\Wildberries\Package\Entity\Package\Orders\WbPackageOrder;
use BaksDev\Wildberries\Package\Entity\Package\Supply\WbPackageSupply;
use BaksDev\Wildberries\Package\Type\Package\Event\WbPackageEventUid;
use BaksDev\Wildberries\Package\Type\Supply\Id\WbSupplyUid;

final class CountOrdersSupplyRepository implements CountOrdersSupplyInterface
{

    public function __construct(private readonly DBALQueryBuilder $DBALQueryBuilder) {}

    /**
     * Возвращает идентификатор поставки упаковки
     */
    public function findSupplyByPackage(WbPackageEventUid $event): ?WbSupplyUid
    {
        $qb = $this->DBALQueryBuilder->createQueryBuilder(self::class);

        $qb
            ->select('package_supply.supply')
            ->from(WbPackageSupply::class, 'package_supply')
            ->where('package_supply.event = :event AND package_supply.print = false')
            ->setParameter('event', $event, WbPackageEventUid::TYPE);

        $supply = $qb->fetchOne();

        return $supply ? new WbSupplyUid($supply) : null;
    }


    /**
     * Метод возвращает количество всех заказов в поставке
     */
    public function countOrdersSupply(WbSupplyUid $supply): int
    {
        $qb = $this->DBALQueryBuilder->createQueryBuilder(self::class);

        $qb
            ->select('COUNT(*)')
            ->from(WbPackageSupply::class, 'package_supply')
            ->where('package_supply.supply = :supply')
            ->setParameter('supply', $supply, WbPackageEventUid::TYPE);

        $qb->leftJoin(
            'package_supply',
            WbPackageOrder::class,
            'package_order',
            'package_order.event = package_supply.event'
        );

        return $qb->fetchOne() ?: 0;
    }
}