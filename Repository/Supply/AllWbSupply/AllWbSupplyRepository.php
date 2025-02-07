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

namespace BaksDev\Wildberries\Package\Repository\Supply\AllWbSupply;

use BaksDev\Core\Doctrine\DBALQueryBuilder;
use BaksDev\Core\Form\Search\SearchDTO;
use BaksDev\Core\Services\Paginator\PaginatorInterface;
use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;
use BaksDev\Wildberries\Package\Entity\Supply\Event\WbSupplyEvent;
use BaksDev\Wildberries\Package\Entity\Supply\Invariable\WbSupplyInvariable;
use BaksDev\Wildberries\Package\Entity\Supply\Modify\WbSupplyModify;
use BaksDev\Wildberries\Package\Entity\Supply\WbSupply;
use BaksDev\Wildberries\Package\Entity\Supply\Wildberries\WbSupplyWildberries;
use BaksDev\Wildberries\Package\Forms\Supply\SupplyFilter\SupplyFilterDTO;
use DateTimeImmutable;

final class AllWbSupplyRepository implements AllWbSupplyInterface
{
    private ?SupplyFilterDTO $filter = null;

    private SearchDTO $search;

    public function __construct(
        private readonly DBALQueryBuilder $DBALQueryBuilder,
        private readonly PaginatorInterface $paginator,
    ) {}

    public function setFilter(SupplyFilterDTO $filter): self
    {
        $this->filter = $filter;
        return $this;
    }

    public function setSearch(SearchDTO $search): self
    {
        $this->search = $search;
        return $this;
    }


    /**
     * Метод возвращает пагинатор WbSupply
     */

    public function fetchAllWbSupplyAssociative(UserProfileUid $profile): PaginatorInterface
    {
        $qb = $this->DBALQueryBuilder->createQueryBuilder(self::class);


        $qb
            ->addSelect('supply_const.total')
            ->from(WbSupplyInvariable::class, 'supply_const')
            ->where('supply_const.profile = :profile')
            ->setParameter('profile', $profile, UserProfileUid::TYPE);

        $qb
            ->addSelect('supply.id')
            ->addSelect('supply.event')
            ->join(
                'supply_const',
                WbSupply::class,
                'supply',
                'supply.id = supply_const.main'
            );


        $qb
            ->addSelect('event.status')
            ->leftJoin(
                'supply',
                WbSupplyEvent::class,
                'event',
                'event.id = supply.event'
            );

        $qb
            ->addSelect('modify.mod_date AS supply_date')
            ->leftJoin(
                'supply',
                WbSupplyModify::class,
                'modify',
                'modify.event = supply.event'
            );


        /**
         * Фильтр по дате
         */

        if(!$this->search?->getQuery() && $this->filter?->getDate())
        {
            $date = $this->filter?->getDate() ?: new DateTimeImmutable();

            // Начало дня
            $startOfDay = $date->setTime(0, 0, 0);
            // Конец дня
            $endOfDay = $date->setTime(23, 59, 59);

            //($date ? ' AND part_modify.mod_date = :date' : '')
            $qb->andWhere('modify.mod_date BETWEEN :start AND :end');

            $qb->setParameter('start', $startOfDay->format("Y-m-d H:i:s"));
            $qb->setParameter('end', $endOfDay->format("Y-m-d H:i:s"));
        }


        $qb
            ->addSelect('wb.identifier')
            ->addSelect('wb.sticker')
            ->leftJoin(
                'supply',
                WbSupplyWildberries::class,
                'wb',
                'wb.main = supply.id'
            );


        $qb->orderBy('modify.mod_date', 'DESC');

        if($this->search?->getQuery())
        {

            $qb
                ->createSearchQueryBuilder($this->search)
                ->addSearchEqualUid('supply.id')
                ->addSearchEqualUid('supply.event')
                ->addSearchLike('wb.identifier');
        }

        return $this->paginator->fetchAllAssociative($qb);
    }
}
