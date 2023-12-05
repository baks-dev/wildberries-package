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

namespace BaksDev\Wildberries\Products\Repository\Cards\AllWbProductCard;


use BaksDev\Core\Doctrine\DBALQueryBuilder;
use BaksDev\Core\Form\Search\SearchDTO;
use BaksDev\Core\Services\Paginator\PaginatorInterface;
use BaksDev\Products\Category\Entity\Offers\ProductCategoryOffers;
use BaksDev\Products\Category\Entity\Offers\Variation\ProductCategoryVariation;
use BaksDev\Products\Category\Entity\ProductCategory;
use BaksDev\Products\Category\Entity\Trans\ProductCategoryTrans;
use BaksDev\Products\Category\Type\Id\ProductCategoryUid;
use BaksDev\Products\Product\Entity\Info\ProductInfo;
use BaksDev\Products\Product\Entity\Offers\Image\ProductOfferImage;
use BaksDev\Products\Product\Entity\Offers\ProductOffer;
use BaksDev\Products\Product\Entity\Offers\Variation\Image\ProductVariationImage;
use BaksDev\Products\Product\Entity\Offers\Variation\ProductVariation;
use BaksDev\Products\Product\Entity\Photo\ProductPhoto;
use BaksDev\Products\Product\Entity\Product;
use BaksDev\Products\Product\Entity\Trans\ProductTrans;
use BaksDev\Products\Product\Forms\ProductFilter\ProductFilterInterface;
use BaksDev\Users\Profile\UserProfile\Entity\Personal\UserProfilePersonal;
use BaksDev\Users\Profile\UserProfile\Entity\UserProfile;
use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;
use BaksDev\Wildberries\Products\Entity\Cards\WbProductCard;
use BaksDev\Wildberries\Products\Entity\Cards\WbProductCardOffer;
use BaksDev\Wildberries\Products\Entity\Cards\WbProductCardVariation;

final class AllWbProductCard implements AllWbProductCardInterface
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

    /** Метод возвращает пагинатор WbProductCard */
    public function fetchAllWbProductCardAssociative(
        SearchDTO $search,
        ProductFilterInterface $filter,
        ?UserProfileUid $profile
    ): PaginatorInterface
    {

        $qb = $this->DBALQueryBuilder
            ->createQueryBuilder(self::class)
            ->bindLocal();

        $qb->select('product.id AS product_id');
        $qb->addSelect('product.event AS product_event');

        $qb->from(Product::TABLE, 'product');

        $qb
            ->addSelect('card.id AS card_id')
            ->join(
                'product',
                WbProductCard::TABLE,
                'card',
                'card.product = product.id'
            );


        $qb->addSelect('product_trans.name AS product_name');
        //$qb->addSelect('product_trans.preview AS product_preview');
        $qb->leftJoin(
            'product',
            ProductTrans::TABLE,
            'product_trans',
            'product_trans.event = product.event AND product_trans.local = :local'
        );

        if($profile)
        {
            $qb->andWhere('product_info.profile = :profile');
            $qb->setParameter('profile', $profile, UserProfileUid::TYPE);
        }

        /* ProductInfo */

        $qb->addSelect('product_info.url');

        $qb->leftJoin(
            'product',
            ProductInfo::TABLE,
            'product_info',
            'product_info.product = product.id'
        );


        /** Ответственное лицо (Профиль пользователя) */

        $qb->leftJoin(
            'product_info',
            UserProfile::TABLE,
            'users_profile',
            'users_profile.id = product_info.profile'
        );

        $qb->addSelect('users_profile_personal.username AS users_profile_username');
        $qb->leftJoin(
            'users_profile',
            UserProfilePersonal::TABLE,
            'users_profile_personal',
            'users_profile_personal.event = users_profile.event'
        );


        /** Торговое предложение */

        $qb->addSelect('product_offer.id as product_offer_id');
        $qb->addSelect('product_offer.value as product_offer_value');
        $qb->addSelect('product_offer.postfix as product_offer_postfix');

        $qb->leftJoin(
            'product',
            ProductOffer::TABLE,
            'product_offer',
            'product_offer.event = product.event'
        );


        $qb->addSelect('card_offer.nomenclature');
        $qb->join(
            'card',
            WbProductCardOffer::TABLE,
            'card_offer',
            'card_offer.card = card.id AND card_offer.offer = product_offer.const'
        );


        if($filter->getOffer())
        {
            $qb->andWhere('product_offer.value = :offer');
            $qb->setParameter('offer', $filter->getOffer());
        }


        /* Тип торгового предложения */
        $qb->addSelect('category_offer.reference as product_offer_reference');
        $qb->leftJoin(
            'product_offer',
            ProductCategoryOffers::TABLE,
            'category_offer',
            'category_offer.id = product_offer.category_offer'
        );


        /** Множественные варианты торгового предложения */

        $qb->addSelect('product_variation.id as product_variation_id');
        $qb->addSelect('product_variation.value as product_variation_value');
        $qb->addSelect('product_variation.postfix as product_variation_postfix');

        $qb->leftJoin(
            'product_offer',
            ProductVariation::TABLE,
            'product_variation',
            'product_variation.offer = product_offer.id'
        );


        if($filter->getVariation())
        {
            $qb->andWhere('product_variation.value = :variation');
            $qb->setParameter('variation', $filter->getVariation());
        }


        /* Тип множественного варианта торгового предложения */
        $qb->addSelect('category_offer_variation.reference as product_variation_reference');
        $qb->leftJoin(
            'product_variation',
            ProductCategoryVariation::TABLE,
            'category_offer_variation',
            'category_offer_variation.id = product_variation.category_variation'
        );


        $qb->addSelect('card_variation.barcode');
        $qb->leftJoin(
            'product_variation',
            WbProductCardVariation::TABLE,
            'card_variation',
            'card_variation.card = card.id AND card_variation.variation = product_variation.const'
        );


        /** Артикул продукта */

        $qb->addSelect("
					CASE
					   WHEN product_variation.article IS NOT NULL THEN product_variation.article
					   WHEN product_offer.article IS NOT NULL THEN product_offer.article
					   WHEN product_info.article IS NOT NULL THEN product_info.article
					   ELSE NULL
					END AS product_article
				"
        );


        /** Фото продукта */

        $qb->leftJoin(
            'product',
            ProductPhoto::TABLE,
            'product_photo',
            'product_photo.event = product.event AND product_photo.root = true'
        );

        $qb->leftJoin(
            'product_offer',
            ProductVariationImage::TABLE,
            'product_variation_image',
            'product_variation_image.variation = product_variation.id AND product_variation_image.root = true'
        );

        $qb->leftJoin(
            'product_offer',
            ProductOfferImage::TABLE,
            'product_offer_images',
            'product_offer_images.offer = product_offer.id AND product_offer_images.root = true'
        );

        $qb->addSelect("
			CASE
			   WHEN product_variation_image.name IS NOT NULL THEN
					CONCAT ( '/upload/".ProductVariationImage::TABLE."' , '/', product_variation_image.name)
			   WHEN product_offer_images.name IS NOT NULL THEN
					CONCAT ( '/upload/".ProductOfferImage::TABLE."' , '/', product_offer_images.name)
			   WHEN product_photo.name IS NOT NULL THEN
					CONCAT ( '/upload/".ProductPhoto::TABLE."' , '/', product_photo.name)
			   ELSE NULL
			END AS product_image
		"
        );

        /** Флаг загрузки файла CDN */
        $qb->addSelect("
			CASE
			   WHEN product_variation_image.name IS NOT NULL THEN
					product_variation_image.ext
			   WHEN product_offer_images.name IS NOT NULL THEN
					product_offer_images.ext
			   WHEN product_photo.name IS NOT NULL THEN
					product_photo.ext
			   ELSE NULL
			END AS product_image_ext
		");

        /** Флаг загрузки файла CDN */
        $qb->addSelect("
			CASE
			   WHEN product_variation_image.name IS NOT NULL THEN
					product_variation_image.cdn
			   WHEN product_offer_images.name IS NOT NULL THEN
					product_offer_images.cdn
			   WHEN product_photo.name IS NOT NULL THEN
					product_photo.cdn
			   ELSE NULL
			END AS product_image_cdn
		");


        /* Категория */
        $qb->join(
            'product',
            \BaksDev\Products\Product\Entity\Category\ProductCategory::TABLE,
            'product_event_category',
            'product_event_category.event = product.event AND product_event_category.root = true'
        );

        if($filter->getCategory())
        {
            $qb->andWhere('product_event_category.category = :category');
            $qb->setParameter('category', $filter->getCategory(), ProductCategoryUid::TYPE);
        }

        $qb->join(
            'product_event_category',
            ProductCategory::TABLE,
            'category',
            'category.id = product_event_category.category'
        );

        $qb->addSelect('category_trans.name AS category_name');

        $qb->leftJoin(
            'category',
            ProductCategoryTrans::TABLE,
            'category_trans',
            'category_trans.event = category.event AND category_trans.local = :local'
        );


        if($search->getQuery())
        {

            $qb
                ->createSearchQueryBuilder($search)
                ->addSearchEqualUid('product.id')
                ->addSearchEqualUid('product.event')
                ->addSearchEqualUid('product_variation.id')
                ->addSearchLike('product_trans.name')
                //->addSearchLike('product_trans.preview')
                ->addSearchLike('product_info.article')
                ->addSearchLike('product_offer.article')
                ->addSearchLike('product_variation.article');

        }

        $qb->orderBy('product.event', 'DESC');

        return $this->paginator->fetchAllAssociative($qb);
    }

}
