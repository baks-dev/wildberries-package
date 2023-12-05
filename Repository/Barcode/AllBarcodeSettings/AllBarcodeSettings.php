<?php
/*
 *  Copyright 2022.  Baks.dev <admin@baks.dev>
 *
 *  Licensed under the Apache License, Version 2.0 (the "License");
 *  you may not use this file except in compliance with the License.
 *  You may obtain a copy of the License at
 *
 *  http://www.apache.org/licenses/LICENSE-2.0
 *
 *  Unless required by applicable law or agreed to in writing, software
 *  distributed under the License is distributed on an "AS IS" BASIS,
 *  WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 *  See the License for the specific language governing permissions and
 *   limitations under the License.
 *
 */

namespace BaksDev\Wildberries\Products\Repository\Barcode\AllBarcodeSettings;

use BaksDev\Core\Doctrine\DBALQueryBuilder;
use BaksDev\Core\Form\Search\SearchDTO;
use BaksDev\Core\Services\Paginator\PaginatorInterface;
use BaksDev\Products\Category\Entity\Cover\ProductCategoryCover;
use BaksDev\Products\Category\Entity\Event\ProductCategoryEvent;
use BaksDev\Products\Category\Entity\ProductCategory;
use BaksDev\Products\Category\Entity\Trans\ProductCategoryTrans;
use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;
use BaksDev\Wildberries\Products\Entity\Barcode\Event\WbBarcodeEvent;
use BaksDev\Wildberries\Products\Entity\Barcode\WbBarcode;

final class AllBarcodeSettings implements AllBarcodeSettingsInterface
{

    private PaginatorInterface $paginator;
    private DBALQueryBuilder $DBALQueryBuilder;

    public function __construct(
        DBALQueryBuilder $DBALQueryBuilder,
        PaginatorInterface $paginator,
    )
    {
        $this->paginator = $paginator;
        $this->DBALQueryBuilder = $DBALQueryBuilder;
    }

    public function fetchAllBarcodeSettings(SearchDTO $search, ?UserProfileUid $profile): PaginatorInterface
    {
        $qb = $this->DBALQueryBuilder
            ->createQueryBuilder(self::class)
            ->bindLocal();

        $qb->select('barcode.event');

        $qb->from(WbBarcode::TABLE, 'barcode');

        if($profile)
        {
            $qb
                ->where('barcode.profile = :profile')
                ->setParameter('profile', $profile, UserProfileUid::TYPE);
        }

//        $qb->join(
//            'barcode',
//            WbBarcodeEvent::TABLE,
//            'event',
//            'event.id = barcode.event'
//        );


        /** Категория */
        $qb->addSelect('category.id as category');
        $qb->addSelect('category.event as category_event'); /* ID события */
        $qb->join('barcode', ProductCategory::TABLE, 'category', 'category.id = barcode.id');


        /** События категории */
        $qb->addSelect('category_event.sort');

        $qb->join
        (
            'category',
            ProductCategoryEvent::TABLE,
            'category_event',
            'category_event.id = category.event'
        );


        /** Обложка */
        $qb->addSelect('category_cover.name AS cover');
        $qb->addSelect('category_cover.ext');
        $qb->addSelect('category_cover.cdn');
        $qb->leftJoin(
            'category_event',
            ProductCategoryCover::TABLE,
            'category_cover',
            'category_cover.event = category_event.id');


        /** Перевод категории */
        $qb->addSelect('category_trans.name as category_name');
        $qb->addSelect('category_trans.description as category_description');

        $qb->leftJoin(
            'category_event',
            ProductCategoryTrans::TABLE,
            'category_trans',
            'category_trans.event = category_event.id AND category_trans.local = :local');


        /* Поиск */
        if($search->getQuery())
        {
            $this->DBALQueryBuilder
                //  ->createSearchQueryBuilder($search)
                //
                //  ->addSearchEqualUid('product.id')
                //
                ->addSearchLike('category_trans.name');

        }

        return $this->paginator->fetchAllAssociative($qb);
    }

}