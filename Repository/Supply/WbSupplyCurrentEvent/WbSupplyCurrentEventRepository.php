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

namespace BaksDev\Wildberries\Package\Repository\Supply\WbSupplyCurrentEvent;

use BaksDev\Core\Doctrine\ORMQueryBuilder;
use BaksDev\Wildberries\Package\Entity\Supply\Event\WbSupplyEvent;
use BaksDev\Wildberries\Package\Entity\Supply\WbSupply;
use BaksDev\Wildberries\Package\Type\Supply\Id\WbSupplyUid;

final class WbSupplyCurrentEventRepository implements WbSupplyCurrentEventInterface
{
    private ORMQueryBuilder $ORMQueryBuilder;

    public function __construct(ORMQueryBuilder $ORMQueryBuilder)
    {
        $this->ORMQueryBuilder = $ORMQueryBuilder;
    }

    /**
     * Возвращает активное событие поставки
     */
    public function findWbSupplyEvent(WbSupply|WbSupplyUid $supply): ?WbSupplyEvent
    {
        $supply = $supply instanceof WbSupply ? $supply->getId() : $supply;

        $qb = $this->ORMQueryBuilder->createQueryBuilder(self::class);

        $qb->select('event');

        $qb
            ->from(WbSupply::class, 'supply')
            ->where('supply.id = :supply')
            ->setParameter('supply', $supply, WbSupplyUid::TYPE);

        $qb->join(
            WbSupplyEvent::class,
            'event',
            'WITH',
            'event.id = supply.event'
        );

        return $qb->getQuery()->getOneOrNullResult();
    }
}