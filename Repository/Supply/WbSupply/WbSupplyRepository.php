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

namespace BaksDev\Wildberries\Package\Repository\Supply\WbSupply;

use BaksDev\Core\Doctrine\DBALQueryBuilder;
use BaksDev\Wildberries\Package\Entity\Supply\Event\WbSupplyEvent;
use BaksDev\Wildberries\Package\Entity\Supply\Invariable\WbSupplyInvariable;
use BaksDev\Wildberries\Package\Entity\Supply\Modify\WbSupplyModify;
use BaksDev\Wildberries\Package\Entity\Supply\WbSupply;
use BaksDev\Wildberries\Package\Entity\Supply\Wildberries\WbSupplyWildberries;
use BaksDev\Wildberries\Package\Type\Supply\Id\WbSupplyUid;
use InvalidArgumentException;

final  class WbSupplyRepository implements WbSupplyInterface
{
    private WbSupplyUid|false $supply = false;

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


    /**
     * Получаем поставку по указанному идентификатору
     */
    public function find(): WbSupplyResult|false
    {
        if(false === ($this->supply instanceof WbSupplyUid))
        {
            throw new InvalidArgumentException('Invalid Argument WbSupply');
        }

        $dbal = $this->DBALQueryBuilder->createQueryBuilder(self::class);

        $dbal
            ->addSelect('supply_const.total')
            ->from(WbSupply::class, 'supply')
            ->where('supply.id = :supply')
            ->setParameter(
                key: 'supply',
                value: $this->supply,
                type: WbSupplyUid::TYPE
            );


        $dbal
            ->addSelect('supply.id')
            ->addSelect('supply.event')
            ->leftJoin(
                'supply',
                WbSupplyInvariable::class,
                'supply_const',
                'supply_const.main = supply.id'
            );


        $dbal
            ->addSelect('event.status')
            ->leftJoin(
                'supply',
                WbSupplyEvent::class,
                'event',
                'event.id = supply.event'
            );

        $dbal
            ->addSelect('modify.mod_date AS date')
            ->leftJoin(
                'supply',
                WbSupplyModify::class,
                'modify',
                'modify.event = supply.event'
            );


        $dbal
            ->addSelect('wb.identifier')
            ->leftJoin(
                'supply',
                WbSupplyWildberries::class,
                'wb',
                'wb.main = supply.id'
            );

        $dbal->setMaxResults(1);

        return $dbal
            ->enableCache('wildberries-package', 3600)
            ->fetchHydrate(WbSupplyResult::class);
    }
}