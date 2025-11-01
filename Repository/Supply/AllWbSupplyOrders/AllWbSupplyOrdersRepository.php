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

namespace BaksDev\Wildberries\Package\Repository\Supply\AllWbSupplyOrders;


use BaksDev\Core\Doctrine\DBALQueryBuilder;
use BaksDev\Core\Form\Search\SearchDTO;
use BaksDev\Core\Services\Paginator\PaginatorInterface;
use BaksDev\Orders\Order\Entity\Event\OrderEvent;
use BaksDev\Orders\Order\Entity\Invariable\OrderInvariable;
use BaksDev\Orders\Order\Entity\Order;
use BaksDev\Orders\Order\Entity\Products\OrderProduct;
use BaksDev\Products\Category\Entity\Offers\CategoryProductOffers;
use BaksDev\Products\Category\Entity\Offers\Variation\CategoryProductVariation;
use BaksDev\Products\Category\Entity\Offers\Variation\Modification\CategoryProductModification;
use BaksDev\Products\Category\Type\Id\CategoryProductUid;
use BaksDev\Products\Product\Entity\Category\ProductCategory;
use BaksDev\Products\Product\Entity\Event\ProductEvent;
use BaksDev\Products\Product\Entity\Info\ProductInfo;
use BaksDev\Products\Product\Entity\Offers\Image\ProductOfferImage;
use BaksDev\Products\Product\Entity\Offers\ProductOffer;
use BaksDev\Products\Product\Entity\Offers\Variation\Image\ProductVariationImage;
use BaksDev\Products\Product\Entity\Offers\Variation\Modification\Image\ProductModificationImage;
use BaksDev\Products\Product\Entity\Offers\Variation\Modification\ProductModification;
use BaksDev\Products\Product\Entity\Offers\Variation\ProductVariation;
use BaksDev\Products\Product\Entity\Photo\ProductPhoto;
use BaksDev\Products\Product\Entity\Trans\ProductTrans;
use BaksDev\Products\Product\Forms\ProductFilter\Admin\ProductFilterDTO;
use BaksDev\Wildberries\Package\Entity\Package\Orders\WbPackageOrder;
use BaksDev\Wildberries\Package\Entity\Package\Supply\WbPackageSupply;
use BaksDev\Wildberries\Package\Entity\Supply\WbSupply;
use BaksDev\Wildberries\Package\Forms\Supply\SupplyOrdersFilter\SupplyOrdersFilterDTO;
use BaksDev\Wildberries\Package\Type\Supply\Id\WbSupplyUid;


final class AllWbSupplyOrdersRepository implements AllWbSupplyOrdersInterface
{
    private ?SearchDTO $search = null;

    private ?ProductFilterDTO $filter = null;

    private ?SupplyOrdersFilterDTO $supplyOrdersFilter = null;

    public function __construct(
        private readonly DBALQueryBuilder $DBALQueryBuilder,
        private readonly PaginatorInterface $paginator,
    ) {}


    public function search(SearchDTO $search): self
    {
        $this->search = $search;
        return $this;
    }

    public function filter(ProductFilterDTO $filter): self
    {
        $this->filter = $filter;
        return $this;
    }

    public function supplyOrderFilter(SupplyOrdersFilterDTO $supplyOrdersFilter): self
    {
        $this->supplyOrdersFilter = $supplyOrdersFilter;
        return $this;
    }

    /** Метод возвращает пагинатор WbSupply */
    public function fetchAllWbSupplyOrdersAssociative(WbSupply|WbSupplyUid $supply): PaginatorInterface
    {
        $supply = $supply instanceof WbSupply ? $supply->getId() : $supply;

        $dbal = $this->DBALQueryBuilder
            ->createQueryBuilder(self::class)
            ->bindLocal();

        //$dbal->select('*');
        $dbal
            ->from(WbSupply::class, 'supply')
            ->where('supply.id = :supply')
            ->setParameter('supply', $supply, WbSupplyUid::TYPE);

        $dbal
            ->addSelect('supply_package.print')
            ->leftJoin(
                'supply',
                WbPackageSupply::class,
                'supply_package',
                'supply_package.supply = supply.id'
            );

        $dbal
            ->addSelect('supply_order.id as order_id')
            ->addSelect('supply_order.status')
            ->join(
                'supply',
                WbPackageOrder::class,
                'supply_order',
                'supply_order.event = supply_package.event'
            );


        /**
         * Системный заказ
         */

        $dbal->leftJoin(
            'supply_order',
            Order::class,
            'orders',
            'orders.id = supply_order.id'
        );

        $dbal
            ->addSelect('invariable.number AS order_number')
            ->leftJoin(
                'orders',
                OrderInvariable::class,
                'invariable',
                'invariable.main = orders.id'
            );

        $dbal
            ->addSelect('event.created AS order_data')
            ->join(
                'orders',
                OrderEvent::class,
                'event',
                'event.id = orders.event'
            );


        $dbal->addSelect('order_product.product AS wb_product_event');
        $dbal->addSelect('order_product.offer AS wb_product_offer');
        $dbal->addSelect('order_product.variation AS wb_product_variation');
        $dbal->addSelect('order_product.modification AS wb_product_modification');

        $dbal->leftJoin('orders',
            OrderProduct::class,
            'order_product',
            'order_product.event = orders.event'
        );

        //        $dbal->addSelect('order_price.price AS order_price');
        //        $dbal->addSelect('order_price.currency AS order_currency');
        //        $dbal->leftJoin('order_product',
        //            OrderPrice::class,
        //            'order_price',
        //            'order_price.product = order_product.id'
        //        );

        /**
         * Продукт
         */

        $dbal->leftJoin('order_product',
            ProductEvent::class,
            'product_event',
            'product_event.id = order_product.product'
        );

        $dbal
            ->addSelect('product_info.article AS card_article')
            ->leftJoin('order_product',
            ProductInfo::class,
            'product_info',
            'product_info.product = product_event.main'
        );

        if($this->filter?->getCategory())
        {
            $dbal->join('order_product',
                ProductCategory::class,
                'product_category',
                '
                product_category.event = product_info.event AND 
                product_category.category = :category AND 
                product_category.root = true'
            )
                ->setParameter(
                    key: 'category',
                    value: $this->filter->getCategory(),
                    type: CategoryProductUid::TYPE
                );

        }



        $dbal->addSelect('product_trans.name AS product_name');
        $dbal->leftJoin('order_product',
            ProductTrans::class,
            'product_trans',
            'product_trans.event = order_product.product AND product_trans.local = :local'
        );

        /*
         * Торговое предложение
         */

        $dbal->addSelect('product_offer.value as product_offer_value');
        $dbal->addSelect('product_offer.postfix as product_offer_postfix');

        $dbal->leftJoin('order_product',
            ProductOffer::class,
            'product_offer',
            'product_offer.id = order_product.offer'
        );


        $dbal->addSelect('category_offer.reference as product_offer_reference');
        $dbal->leftJoin(
            'product_offer',
            CategoryProductOffers::class,
            'category_offer',
            'category_offer.id = product_offer.category_offer'
        );

        if(!$this->search?->getQuery() && $this->filter?->getOffer())
        {

            $dbal->andWhere('product_offer.value = :offer');
            $dbal->setParameter('offer', $this->filter->getOffer());
        }


        /*
        * Множественный вариант
        */

        $dbal->addSelect('product_variation.value as product_variation_value');
        $dbal->addSelect('product_variation.postfix as product_variation_postfix');

        $dbal->leftJoin('order_product',
            ProductVariation::class,
            'product_variation',
            'product_variation.id = order_product.variation'
        );

        if(!$this->search?->getQuery() && $this->filter?->getVariation())
        {
            $dbal->andWhere('product_variation.value = :variation');
            $dbal->setParameter('variation', $this->filter->getVariation());
        }


        /* Тип множественного враианта торгового предложения */
        $dbal->addSelect('category_variation.reference as product_variation_reference');
        $dbal->leftJoin(
            'product_variation',
            CategoryProductVariation::class,
            'category_variation',
            'category_variation.id = product_variation.category_variation'
        );

        /**
         * Модификации множественного варианта
         */

        $dbal
            ->addSelect('product_modification.value AS product_modification_value')
            ->addSelect('product_modification.postfix AS product_modification_postfix')
            ->leftJoin(
                'product_variation',
                ProductModification::class,
                'product_modification',
                'product_modification.id = order_product.modification AND product_modification.variation = product_variation.id'
            );

        $dbal
            ->addSelect('category_modification.reference AS product_modification_reference')
            ->leftJoin(
                'product_modification',
                CategoryProductModification::class,
                'category_modification',
                'category_modification.id = product_modification.category_modification'
            );

        /** Артикул продукта */

        $dbal->addSelect('
            COALESCE(
                product_modification.article, 
                product_variation.article, 
                product_offer.article, 
                product_info.article
            ) AS product_article
		');


        /** Фото продукта */

        $dbal->leftJoin(
            'order_product',
            ProductPhoto::class,
            'product_photo',
            'product_photo.event = order_product.product AND product_photo.root = true'
        );

        $dbal->leftJoin(
            'order_product',
            ProductOfferImage::class,
            'product_offer_images',
            'product_offer_images.offer = order_product.offer AND product_offer_images.root = true'
        );

        $dbal->leftJoin(
            'order_product',
            ProductVariationImage::class,
            'product_variation_image',
            'product_variation_image.variation = order_product.variation AND product_variation_image.root = true'
        );

        $dbal->leftJoin(
            'order_product',
            ProductModificationImage::class,
            'product_modification_image',
            'product_modification_image.modification = order_product.variation AND product_modification_image.root = true'
        );


        $dbal->addSelect("
			CASE
			    WHEN product_modification_image.name IS NOT NULL 
			   THEN CONCAT ( '/upload/".$dbal->table(ProductModificationImage::class)."' , '/', product_modification_image.name)
			
			   WHEN product_variation_image.name IS NOT NULL 
			   THEN CONCAT ( '/upload/".$dbal->table(ProductVariationImage::class)."' , '/', product_variation_image.name)
			   
			   WHEN product_offer_images.name IS NOT NULL 
			   THEN CONCAT ( '/upload/".$dbal->table(ProductOfferImage::class)."' , '/', product_offer_images.name)
			   
			   WHEN product_photo.name IS NOT NULL 
			   THEN CONCAT ( '/upload/".$dbal->table(ProductPhoto::class)."' , '/', product_photo.name)
			   
			   ELSE NULL
			END AS product_image
		"
        );

        /** Флаг загрузки файла CDN */
        $dbal->addSelect("
			CASE
			    WHEN product_modification_image.name IS NOT NULL 
			   THEN product_modification_image.ext
			   
			   WHEN product_variation_image.name IS NOT NULL 
			   THEN product_variation_image.ext
			   
			   WHEN product_offer_images.name IS NOT NULL 
			   THEN product_offer_images.ext
			   
			   WHEN product_photo.name IS NOT NULL 
			   THEN product_photo.ext
			   
			   ELSE NULL
			END AS product_image_ext
		");

        /** Флаг загрузки файла CDN */
        $dbal->addSelect("
			CASE
			   WHEN product_modification_image.name IS NOT NULL 
			   THEN product_modification_image.cdn
			   
			   WHEN product_variation_image.name IS NOT NULL 
			   THEN product_variation_image.cdn
			  
			   WHEN product_offer_images.name IS NOT NULL 
			   THEN product_offer_images.cdn
			   
			   WHEN product_photo.name IS NOT NULL 
			   THEN product_photo.cdn
			   
			   ELSE NULL
			END AS product_image_cdn
		");


        /* Карточка Wildberries */


        //        $dbal->leftJoin('wb_order',
        //            WbProductCardVariation::class,
        //            'wb_card_variation',
        //            'wb_card_variation.barcode = wb_order_event.barcode'
        //        );

        //        $dbal->addSelect('wb_card_offer.nomenclature AS wb_order_nomenclature');
        //
        //        $dbal->leftJoin('wb_card_variation',
        //            WbProductCardOffer::class,
        //            'wb_card_offer',
        //            'wb_card_offer.card = wb_card_variation.card AND wb_card_offer.offer =  product_offer.const'
        //        );


        /* Поиск */
        if($this->search?->getQuery())
        {
            $dbal
                ->createSearchQueryBuilder($this->search)
                //->addSearchEqualUid('supply_order.id')
                ->addSearchLike('product_modification.article')
                ->addSearchLike('product_variation.article')
                ->addSearchLike('product_offer.article')
                ->addSearchLike('product_info.article')
                ->addSearchLike('invariable.number')
                //->addSearchLike('wb_order_event.barcode')
                //->addSearchLike('wb_order_sticker.part')
                //->addSearchEqual('wb_order.ord')
            ;

        }

        /* Фильтр по флагу Printed */
        if(true === $this->supplyOrdersFilter->getPrint())
        {
            $dbal->andWhere('supply_package.print IS TRUE');
        }

        if(false === $this->supplyOrdersFilter->getPrint())
        {
            $dbal->andWhere('supply_package.print IS NOT TRUE');
        }

        $dbal->orderBy('supply_package.print', 'ASC');
        $dbal->addOrderBy('supply_order.event', 'DESC');

        return $this->paginator->fetchAllAssociative($dbal);
    }
}