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

namespace BaksDev\Wildberries\Package\Repository\Package\PrintOrdersPackageSupply;

use BaksDev\Core\Doctrine\DBALQueryBuilder;
use BaksDev\Orders\Order\Entity\Order;
use BaksDev\Orders\Order\Entity\Products\OrderProduct;
use BaksDev\Products\Category\Entity\Offers\CategoryProductOffers;
use BaksDev\Products\Category\Entity\Offers\Trans\CategoryProductOffersTrans;
use BaksDev\Products\Category\Entity\Offers\Variation\CategoryProductVariation;
use BaksDev\Products\Category\Entity\Offers\Variation\Modification\CategoryProductModification;
use BaksDev\Products\Category\Entity\Offers\Variation\Modification\Trans\CategoryProductModificationTrans;
use BaksDev\Products\Category\Entity\Offers\Variation\Trans\CategoryProductVariationTrans;
use BaksDev\Products\Product\Entity\Category\ProductCategory;
use BaksDev\Products\Product\Entity\Event\ProductEvent;
use BaksDev\Products\Product\Entity\Info\ProductInfo;
use BaksDev\Products\Product\Entity\Offers\Barcode\ProductOfferBarcode;
use BaksDev\Products\Product\Entity\Offers\Image\ProductOfferImage;
use BaksDev\Products\Product\Entity\Offers\ProductOffer;
use BaksDev\Products\Product\Entity\Offers\Variation\Barcode\ProductVariationBarcode;
use BaksDev\Products\Product\Entity\Offers\Variation\Image\ProductVariationImage;
use BaksDev\Products\Product\Entity\Offers\Variation\Modification\Barcode\ProductModificationBarcode;
use BaksDev\Products\Product\Entity\Offers\Variation\Modification\Image\ProductModificationImage;
use BaksDev\Products\Product\Entity\Offers\Variation\Modification\ProductModification;
use BaksDev\Products\Product\Entity\Offers\Variation\ProductVariation;
use BaksDev\Products\Product\Entity\Photo\ProductPhoto;
use BaksDev\Products\Product\Entity\Trans\ProductTrans;
use BaksDev\Wildberries\Package\Entity\Package\Event\WbPackageEvent;
use BaksDev\Wildberries\Package\Entity\Package\Orders\WbPackageOrder;
use BaksDev\Wildberries\Package\Entity\Package\Supply\WbPackageSupply;
use BaksDev\Wildberries\Package\Type\Supply\Id\WbSupplyUid;
use BaksDev\Wildberries\Products\Entity\Barcode\Event\WbBarcodeEvent;
use BaksDev\Wildberries\Products\Entity\Barcode\WbBarcode;

final class PrintOrdersPackageSupplyRepository implements PrintOrdersPackageSupplyInterface
{
    private DBALQueryBuilder $DBALQueryBuilder;

    public function __construct(
        DBALQueryBuilder $DBALQueryBuilder,
    )
    {
        $this->DBALQueryBuilder = $DBALQueryBuilder;
    }

    /**
     * @note нигде не используется
     *
     * Метод получает все заказы добавленной в поставку необходимые для печати, со стикерами
     */
    public function getPrinterOrdersPackageSupply(WbSupplyUid $supply): ?array
    {
        $dbal = $this->DBALQueryBuilder
            ->createQueryBuilder(self::class)
            ->bindLocal();

        $dbal
            ->from(WbPackageSupply::class, 'package_supply')
            ->where('package_supply.supply = :supply')
            ->andWhere('package_supply.print = false')
            ->setParameter('supply', $supply, WbSupplyUid::TYPE);

        $dbal
            ->addSelect('package_event.main AS package_id')
            ->addSelect('package_event.total AS product_total')
            ->leftJoin(
                'package_supply',
                WbPackageEvent::class,
                'package_event',
                'package_event.id = package_supply.event'
            );

        $dbal->leftJoin(
            'package_supply',
            WbPackageOrder::class,
            'package_order',
            'package_order.event = package_supply.event'
        );


        //        $dbal
        //            ->addSelect('ord_sticker.sticker')
        //            ->leftJoin(
        //                'package_order',
        //                WbOrdersSticker::class,
        //                'ord_sticker',
        //                'ord_sticker.main = package_order.id'
        //            );

        $dbal
            ->leftJoin(
                'package_order',
                Order::class,
                'ord',
                'ord.id = package_order.id'
            );


        //        $dbal
        //            ->leftJoin(
        //                'ord',
        //                WbOrders::class,
        //                'wb_orders',
        //                'wb_orders.id = ord.id'
        //            );
        //
        //        $dbal
        //            ->addSelect('wb_orders_event.barcode')
        //            ->leftJoin(
        //                'wb_orders',
        //                WbOrdersEvent::class,
        //                'wb_orders_event',
        //                'wb_orders_event.id = wb_orders.event'
        //            );


        $dbal
            ->addSelect('ord_product.product AS product_event')
            //            ->addSelect('ord_product.offer AS product_offer')
            //            ->addSelect('ord_product.variation AS product_variation')
            ->leftJoin(
                'ord',
                OrderProduct::class,
                'ord_product',
                'ord_product.event = ord.event'
            );


        /* Получаем настройки бокового стикера */
        $dbal->leftJoin(
            'ord_product',
            ProductEvent::class,
            'product_event',
            'product_event.id = ord_product.product'
        );

        $dbal->leftJoin(
            'ord_product',
            ProductCategory::class,
            'product_category',
            'product_category.event = product_event.id AND product_category.root = true'
        );


        $dbal->leftJoin(
            'product_event',
            ProductInfo::class,
            'product_info',
            'product_info.product = product_event.main'
        );


        //$dbal->addSelect('barcode.event');
        $dbal->join(
            'product_category',
            WbBarcode::class,
            'barcode',
            'barcode.id = product_category.category AND barcode.profile = product_info.profile'
        );

        // @TODO
        $dbal->addSelect('barcode_event.offer AS barcode_offer');
        $dbal->addSelect('barcode_event.variation AS barcode_variation');
        $dbal->addSelect('barcode_event.counter AS barcode_counter');

        $dbal->join(
            'barcode',
            WbBarcodeEvent::class,
            'barcode_event',
            'barcode_event.id = barcode.event'
        );


        $dbal->addSelect('product_trans.name AS product_name');

        $dbal->leftJoin(
            'product_event',
            ProductTrans::class,
            'product_trans',
            'product_trans.event = product_event.id AND product_trans.local = :local'
        );


        $dbal
            ->addSelect('product_offer.value as product_offer_value')
            ->leftJoin(
                'ord_product',
                ProductOffer::class,
                'product_offer',
                'product_offer.id = ord_product.offer '
            );

        /* Получаем тип торгового предложения */
        $dbal
            ->addSelect('category_offer.reference AS product_offer_reference')
            ->leftJoin(
                'product_offer',
                CategoryProductOffers::class,
                'category_offer',
                'category_offer.id = product_offer.category_offer'
            );

        /* Получаем название торгового предложения */
        $dbal
            ->addSelect('category_offer_trans.name as product_offer_name')
            ->addSelect('category_offer_trans.postfix as product_offer_name_postfix')
            ->leftJoin(
                'category_offer',
                CategoryProductOffersTrans::class,
                'category_offer_trans',
                'category_offer_trans.offer = category_offer.id AND category_offer_trans.local = :local'
            );


        $dbal
            ->addSelect('product_variation.value as product_variation_value')
            ->leftJoin(
                'ord_product',
                ProductVariation::class,
                'product_variation',
                'product_variation.id = ord_product.variation '
            );


        /* Получаем тип множественного варианта */
        $dbal
            ->addSelect('category_variation.reference as product_variation_reference')
            ->leftJoin(
                'product_variation',
                CategoryProductVariation::class,
                'category_variation',
                'category_variation.id = product_variation.category_variation'
            );

        /* Получаем название множественного варианта */
        $dbal
            ->addSelect('category_variation_trans.name as product_variation_name')
            ->addSelect('category_variation_trans.postfix as product_variation_name_postfix')
            ->leftJoin(
                'category_variation',
                CategoryProductVariationTrans::class,
                'category_variation_trans',
                'category_variation_trans.variation = category_variation.id AND category_variation_trans.local = :local'
            );


        //        $dbal->leftJoin(
        //            'ord_product',
        //            ProductModification::class,
        //            'product_modification',
        //            'product_modification.id = ord_product.modification'
        //        );


        //        $dbal
        //            ->addSelect('card_variation.barcode')
        //            ->leftJoin(
        //                'product_variation',
        //                WbProductCardVariation::class,
        //                'card_variation',
        //                'card_variation.variation = product_variation.const'
        //            );


        $dbal->addSelect('
            COALESCE(
                product_modification.article, 
                product_variation.article, 
                product_offer.article, 
                product_info.article
            ) AS product_article
		');


        $dbal->addSelect('
            COALESCE(
                product_modification.barcode_old, 
                product_variation.barcode_old, 
                product_offer.barcode_old, 
                product_info.barcode
            ) AS product_barcode
		');

        return $dbal
            //->enableCache('wildberries-package', 3600)
            ->fetchAllAssociative();
    }

    /**
     * Метод получает список продукции, добавленной в поставку (заказы группируются)
     */

    public function fetchAllPrintOrdersPackageSupplyAssociative(WbSupplyUid $supply): ?array
    {
        $dbal = $this->DBALQueryBuilder
            ->createQueryBuilder(self::class)
            ->bindLocal();

        $dbal
            ->select('package_supply.main AS id')
            ->from(WbPackageSupply::class, 'package_supply')
            ->where('package_supply.supply = :supply')
            ->andWhere('package_supply.print = false')
            ->setParameter('supply', $supply, WbSupplyUid::TYPE);

        $dbal
            ->addSelect('package_event.total AS product_total')
            ->leftJoin(
                'package_supply',
                WbPackageEvent::class,
                'package_event',
                'package_event.id = package_supply.event'
            );

        $dbal
            ->leftOneJoin(
                'package_supply',
                WbPackageOrder::class,
                'package_orders',
                'package_orders.event = package_supply.event'
            );

        $dbal->addOrderBy('package_orders.sort');

        /** Стикеры Wildberries */


        $dbal->leftJoin(
            'package_orders',
            Order::class,
            'ord',
            'ord.id = package_orders.id'
        );


        $dbal->leftJoin(
            'ord',
            OrderProduct::class,
            'ord_product',
            'ord_product.event = ord.event'
        );


        $dbal
            ->addSelect('product_event.id AS product_event')
            ->leftJoin(
                'ord_product',
                ProductEvent::class,
                'product_event',
                'product_event.id = ord_product.product'
            );

        $dbal
            //->addSelect('product_event.id AS product_event')
            ->leftJoin(
                'ord_product',
                ProductInfo::class,
                'product_info',
                'product_info.product = product_event.main'
            );

        $dbal
            ->addSelect('product_trans.name AS product_name')
            ->leftJoin(
                'product_event',
                ProductTrans::class,
                'product_trans',
                'product_trans.event = product_event.id AND product_trans.local = :local'
            );

        /* Торговое предложение */

        $dbal
            ->addSelect('product_offer.id as product_offer_uid')
            ->addSelect('product_offer.value as product_offer_value')
            ->addSelect('product_offer.postfix as product_offer_postfix')
            ->addSelect('product_offer.barcode_old as product_offer_barcode')
            ->addSelect('product_offer.name as product_offer_detail_name')
            ->leftJoin(
                'ord_product',
                ProductOffer::class,
                'product_offer',
                'product_offer.id = ord_product.offer OR product_offer.id IS NULL'
            );

        /** Offer Barcode */

        $dbal
            ->leftJoin(
                'product_offer',
                ProductOfferBarcode::class,
                'product_offer_barcode',
                'product_offer_barcode.offer = product_offer.id'
            );

        /* Получаем тип торгового предложения */
        $dbal
            ->addSelect('category_offer.reference AS product_offer_reference')
            ->leftJoin(
                'product_offer',
                CategoryProductOffers::class,
                'category_offer',
                'category_offer.id = product_offer.category_offer'
            );

        /* Получаем название торгового предложения */
        $dbal
            ->addSelect('category_offer_trans.name as product_offer_name')
            ->addSelect('category_offer_trans.postfix as product_offer_name_postfix')
            ->leftJoin(
                'category_offer',
                CategoryProductOffersTrans::class,
                'category_offer_trans',
                'category_offer_trans.offer = category_offer.id AND category_offer_trans.local = :local'
            );


        /* Множественные варианты торгового предложения */

        $dbal
            ->addSelect('product_variation.id as product_variation_uid')
            ->addSelect('product_variation.value as product_variation_value')
            ->addSelect('product_variation.postfix as product_variation_postfix')
            ->addSelect('product_variation.barcode_old as product_variation_barcode')
            ->leftJoin(
                'ord_product',
                ProductVariation::class,
                'product_variation',
                'product_variation.id = ord_product.variation OR product_variation.id IS NULL '
            );

        /** Variation Barcode */

        $dbal
            ->leftJoin(
                'product_variation',
                ProductVariationBarcode::class,
                'product_variation_barcode',
                'product_variation_barcode.variation = product_variation.id'
            );


        /* Получаем тип множественного варианта */
        $dbal->addSelect('category_variation.reference as product_variation_reference');
        $dbal->leftJoin(
            'product_variation',
            CategoryProductVariation::class,
            'category_variation',
            'category_variation.id = product_variation.category_variation'
        );

        /* Получаем название множественного варианта */
        $dbal
            ->addSelect('category_variation_trans.name as product_variation_name')
            ->addSelect('category_variation_trans.postfix as product_variation_name_postfix')
            ->leftJoin(
                'category_variation',
                CategoryProductVariationTrans::class,
                'category_variation_trans',
                'category_variation_trans.variation = category_variation.id AND category_variation_trans.local = :local'
            );


        /* Модификация множественного варианта торгового предложения */

        $dbal
            ->addSelect('product_modification.id as product_modification_uid')
            ->addSelect('product_modification.value as product_modification_value')
            ->addSelect('product_modification.postfix as product_modification_postfix')
            ->addSelect('product_modification.barcode_old as product_modification_barcode')
            ->leftJoin(
                'ord_product',
                ProductModification::class,
                'product_modification',
                'product_modification.id = ord_product.modification OR product_modification.id IS NULL '
            );

        /** Modification Barcode */

        $dbal
            ->leftJoin(
                'product_modification',
                ProductModificationBarcode::class,
                'product_modification_barcode',
                'product_modification_barcode.modification = product_modification.id'
            );

        /* Получаем тип модификации множественного варианта */
        $dbal
            ->addSelect('category_modification.reference as product_modification_reference')
            ->leftJoin(
                'product_modification',
                CategoryProductModification::class,
                'category_modification',
                'category_modification.id = product_modification.category_modification'
            );

        /* Получаем название типа модификации */
        $dbal
            ->addSelect('category_modification_trans.name as product_modification_name')
            ->addSelect('category_modification_trans.postfix as product_modification_name_postfix')
            ->leftJoin(
                'category_modification',
                CategoryProductModificationTrans::class,
                'category_modification_trans',
                'category_modification_trans.modification = category_modification.id AND category_modification_trans.local = :local'
            );

        /** Штрихкоды продукта */

        $dbal->addSelect(
            "
            JSON_AGG
                    (DISTINCT
         			CASE
         			    WHEN product_modification_barcode.value IS NOT NULL
                        THEN product_modification_barcode.value
                        
                        WHEN product_variation_barcode.value IS NOT NULL
                        THEN product_variation_barcode.value
                        
                        WHEN product_offer_barcode.value IS NOT NULL
                        THEN product_offer_barcode.value
                        
                        WHEN product_info.barcode IS NOT NULL
                        THEN product_info.barcode
                        
                        ELSE NULL
                    END
                    )
                    AS barcodes"
        );

        $dbal->addSelect('
            COALESCE(
                product_modification.barcode_old, 
                product_variation.barcode_old, 
                product_offer.barcode_old,
                product_info.barcode
            ) AS barcode
		');

        /* Фото продукта */

        $dbal->leftJoin(
            'product_event',
            ProductPhoto::class,
            'product_photo',
            'product_photo.event = product_event.id AND product_photo.root = true'
        );

        $dbal->leftJoin(
            'product_offer',
            ProductModificationImage::class,
            'product_modification_image',
            'product_modification_image.modification = product_modification.id AND product_modification_image.root = true'
        );

        $dbal->leftJoin(
            'product_offer',
            ProductVariationImage::class,
            'product_variation_image',
            'product_variation_image.variation = product_variation.id AND product_variation_image.root = true'
        );


        $dbal->leftJoin(
            'product_offer',
            ProductOfferImage::class,
            'product_offer_images',
            'product_offer_images.offer = product_offer.id AND product_offer_images.root = true'
        );


        $dbal->addSelect(
            "
			CASE
				WHEN product_modification_image.name IS NOT NULL THEN
					CONCAT ( '/upload/".$dbal->table(ProductModificationImage::class)."' , '/', product_modification_image.name)
			   WHEN product_variation_image.name IS NOT NULL THEN
					CONCAT ( '/upload/".$dbal->table(ProductVariationImage::class)."' , '/', product_variation_image.name)
			   WHEN product_offer_images.name IS NOT NULL THEN
					CONCAT ( '/upload/".$dbal->table(ProductOfferImage::class)."' , '/', product_offer_images.name)
			   WHEN product_photo.name IS NOT NULL THEN
					CONCAT ( '/upload/".$dbal->table(ProductPhoto::class)."' , '/', product_photo.name)
			   ELSE NULL
			END AS product_image
		"
        );

        /* Флаг загрузки файла CDN */
        $dbal->addSelect('
			CASE
			    WHEN product_modification_image.name IS NOT NULL THEN
					product_modification_image.ext
			   WHEN product_variation_image.name IS NOT NULL THEN
					product_variation_image.ext
			   WHEN product_offer_images.name IS NOT NULL THEN
					product_offer_images.ext
			   WHEN product_photo.name IS NOT NULL THEN
					product_photo.ext
			   ELSE NULL
			END AS product_image_ext
		');

        /* Флаг загрузки файла CDN */
        $dbal->addSelect('
			CASE
			   WHEN product_modification_image.name IS NOT NULL THEN
					product_modification_image.cdn			   
			    WHEN product_variation_image.name IS NOT NULL THEN
					product_variation_image.cdn
			   WHEN product_offer_images.name IS NOT NULL THEN
					product_offer_images.cdn
			   WHEN product_photo.name IS NOT NULL THEN
					product_photo.cdn
			   ELSE NULL
			END AS product_image_cdn
		');

        /* Артикул продукта */
        $dbal->addSelect('
			COALESCE(
                product_modification.article,
                product_variation.article,
                product_offer.article,
                product_info.article
            ) AS product_article
		');

        $dbal->addGroupBy("package_orders.sort");
        $dbal->allGroupByExclude();

        return $dbal
            //->enableCache('wildberries-package', 3600)
            ->fetchAllAssociative();
    }


}