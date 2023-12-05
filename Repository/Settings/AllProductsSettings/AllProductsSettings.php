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

namespace BaksDev\Wildberries\Products\Repository\Settings\AllProductsSettings;

use BaksDev\Core\Doctrine\DBALQueryBuilder;
use BaksDev\Core\Form\Search\SearchDTO;
use BaksDev\Core\Services\Paginator\PaginatorInterface;
use BaksDev\Core\Services\Switcher\SwitcherInterface;
use BaksDev\Core\Type\Locale\Locale;
use BaksDev\Products\Category\Entity\Cover\ProductCategoryCover;
use BaksDev\Products\Category\Entity\Event\ProductCategoryEvent;
use BaksDev\Products\Category\Entity\ProductCategory;
use BaksDev\Products\Category\Entity\Trans\ProductCategoryTrans;
use BaksDev\Wildberries\Products\Entity\Settings\Event\WbProductSettingsEvent;
use BaksDev\Wildberries\Products\Entity\Settings\WbProductSettings;
use Symfony\Contracts\Translation\TranslatorInterface;

final class AllProductsSettings implements AllProductsSettingsInterface
{

    private PaginatorInterface $paginator;

    private SwitcherInterface $switcher;

    private TranslatorInterface $translator;
    private DBALQueryBuilder $DBALQueryBuilder;


    public function __construct(
        DBALQueryBuilder $DBALQueryBuilder,
        PaginatorInterface $paginator,
        SwitcherInterface $switcher,
        TranslatorInterface $translator,
    )
    {

        $this->paginator = $paginator;
        $this->switcher = $switcher;
        $this->translator = $translator;
        $this->DBALQueryBuilder = $DBALQueryBuilder;
    }


    /** Метод возвращает пагинатор WbProductsSettings */
    public function fetchAllProductsSettingsAssociative(SearchDTO $search): PaginatorInterface
    {
        $local = new Locale($this->translator->getLocale());

        $qb = $this->DBALQueryBuilder->createQueryBuilder(self::class);

        $qb->select('settings.id');
        $qb->addSelect('settings.event');

        $qb->addSelect('event.name');
        //$qb->addSelect('event.parent');

        $qb->from(WbProductSettings::TABLE, 'settings');

        $qb->join(
            'settings',
            WbProductSettingsEvent::TABLE,
            'event',
            'event.id = settings.event AND event.settings = settings.id',
        );

        /** Категория */
        $qb->addSelect('category.id as category_id');
        $qb->addSelect('category.event as category_event'); /* ID события */
        $qb->join('settings', ProductCategory::TABLE, 'category', 'category.id = settings.id');

        /** События категории */
        $qb->addSelect('category_event.sort');

        $qb->join
        (
            'category',
            ProductCategoryEvent::TABLE,
            'category_event',
            'category_event.id = category.event',
        );

        /** Обложка */
        $qb->addSelect('category_cover.ext');
        $qb->addSelect('category_cover.cdn');
        $qb->leftJoin(
            'category_event',
            ProductCategoryCover::TABLE,
            'category_cover',
            'category_cover.event = category_event.id',
        );


        $qb->addSelect("
			CASE
			   WHEN category_cover.name IS NOT NULL THEN
					CONCAT ( '/upload/".ProductCategoryCover::TABLE."' , '/', category_cover.name)
			   ELSE NULL
			END AS cover
		"
        );


        /** Перевод категории */
        $qb->addSelect('category_trans.name as category_name');
        $qb->addSelect('category_trans.description as category_description');

        $qb->leftJoin(
            'category_event',
            ProductCategoryTrans::TABLE,
            'category_trans',
            'category_trans.event = category_event.id AND category_trans.local = :local',
        );

        $qb->setParameter('local', $local, Locale::TYPE);

        return $this->paginator->fetchAllAssociative($qb);

    }
}
