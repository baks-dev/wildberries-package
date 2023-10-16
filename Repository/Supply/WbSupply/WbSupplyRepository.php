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

namespace BaksDev\Wildberries\Package\Repository\Supply\WbSupply;

use BaksDev\Core\Doctrine\DBALQueryBuilder;
use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;
use BaksDev\Wildberries\Package\Entity\Supply\Const\WbSupplyConst;
use BaksDev\Wildberries\Package\Entity\Supply\Event\WbSupplyEvent;
use BaksDev\Wildberries\Package\Entity\Supply\Modify\WbSupplyModify;
use BaksDev\Wildberries\Package\Entity\Supply\WbSupply;
use BaksDev\Wildberries\Package\Entity\Supply\Wildberries\WbSupplyWildberries;
use BaksDev\Wildberries\Package\Type\Supply\Id\WbSupplyUid;

final class WbSupplyRepository implements WbSupplyRepositoryInterface
{
    private DBALQueryBuilder $DBALQueryBuilder;

    public function __construct(DBALQueryBuilder $DBALQueryBuilder,)
    {
        $this->DBALQueryBuilder = $DBALQueryBuilder;
    }

    /**
     * Получаем поставку по указанному идентификатору
     */
    public function getWbSupplyById(WbSupply|WbSupplyUid $supply): ?array
    {

        $supply = $supply instanceof WbSupply ? $supply->getId() : $supply;

        $qb = $this->DBALQueryBuilder->createQueryBuilder(self::class);

        $qb
            ->addSelect('supply_const.total')
            ->from(WbSupply::TABLE, 'supply')
            ->where('supply.id = :supply')
            ->setParameter('supply', $supply, WbSupplyUid::TYPE);


        $qb
            ->addSelect('supply.id')
            ->addSelect('supply.event')
            ->leftJoin(
                'supply',
                WbSupplyConst::TABLE,
                'supply_const',
                'supply_const.main = supply.id'
            );


        $qb
            ->addSelect('event.status')
            ->leftJoin(
                'supply',
                WbSupplyEvent::TABLE,
                'event',
                'event.id = supply.event'
            );

        $qb
            ->addSelect('modify.mod_date AS supply_date')
            ->leftJoin(
                'supply',
                WbSupplyModify::TABLE,
                'modify',
                'modify.event = supply.event'
            );


        $qb
            ->addSelect('wb.identifier')
            ->addSelect('wb.sticker')
            ->leftJoin(
                'supply',
                WbSupplyWildberries::TABLE,
                'wb',
                'wb.main = supply.id'
            );

        $qb->setMaxResults(1);

        return $qb
            ->enableCache('WildberriesPackage', 3600)
            ->fetchAssociative();
    }
}