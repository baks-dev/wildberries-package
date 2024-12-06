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

namespace BaksDev\Wildberries\Package\Repository\Package\PrintOrdersPackageSupply;

use BaksDev\Core\Doctrine\DBALQueryBuilder;
use BaksDev\Manufacture\Part\Entity\Products\ManufacturePartProduct;
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
use BaksDev\Products\Product\Entity\Offers\Image\ProductOfferImage;
use BaksDev\Products\Product\Entity\Offers\ProductOffer;
use BaksDev\Products\Product\Entity\Offers\Variation\Image\ProductVariationImage;
use BaksDev\Products\Product\Entity\Offers\Variation\Modification\Image\ProductModificationImage;
use BaksDev\Products\Product\Entity\Offers\Variation\Modification\ProductModification;
use BaksDev\Products\Product\Entity\Offers\Variation\ProductVariation;
use BaksDev\Products\Product\Entity\Photo\ProductPhoto;
use BaksDev\Products\Product\Entity\Trans\ProductTrans;
use BaksDev\Wildberries\Orders\Entity\Event\WbOrdersEvent;
use BaksDev\Wildberries\Orders\Entity\Sticker\WbOrdersSticker;
use BaksDev\Wildberries\Orders\Entity\WbOrders;
use BaksDev\Wildberries\Package\Entity\Package\Event\WbPackageEvent;
use BaksDev\Wildberries\Package\Entity\Package\Orders\WbPackageOrder;
use BaksDev\Wildberries\Package\Entity\Package\Supply\WbPackageSupply;
use BaksDev\Wildberries\Package\Type\Supply\Id\WbSupplyUid;
use BaksDev\Wildberries\Products\Entity\Barcode\Event\WbBarcodeEvent;
use BaksDev\Wildberries\Products\Entity\Barcode\WbBarcode;
use BaksDev\Wildberries\Products\Entity\Cards\WbProductCardVariation;

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
     * Метод получает все заказы добавленной в поставку необходимые для печати, со стикерами
     */
    public function getPrinterOrdersPackageSupply(WbSupplyUid $supply): ?array
    {
        $qb = $this->DBALQueryBuilder
            ->createQueryBuilder(self::class)
            ->bindLocal();

        $qb
            ->from(WbPackageSupply::TABLE, 'package_supply')
            ->where('package_supply.supply = :supply')
            ->andWhere('package_supply.print = false')
            ->setParameter('supply', $supply, WbSupplyUid::TYPE);

        $qb
            ->addSelect('package_event.main AS package_id')
            ->addSelect('package_event.total AS product_total')
            ->leftJoin(
                'package_supply',
                WbPackageEvent::TABLE,
                'package_event',
                'package_event.id = package_supply.event'
            );

        $qb->leftJoin(
            'package_supply',
            WbPackageOrder::TABLE,
            'package_order',
            'package_order.event = package_supply.event'
        );


        $qb
            ->addSelect('ord_sticker.sticker')
            ->leftJoin(
                'package_order',
                WbOrdersSticker::TABLE,
                'ord_sticker',
                'ord_sticker.main = package_order.id'
            );

        $qb
            ->leftJoin(
                'package_order',
                Order::TABLE,
                'ord',
                'ord.id = package_order.id'
            );


        $qb
            ->leftJoin(
                'ord',
                WbOrders::TABLE,
                'wb_orders',
                'wb_orders.id = ord.id'
            );

        $qb
            ->addSelect('wb_orders_event.barcode')
            ->leftJoin(
                'wb_orders',
                WbOrdersEvent::TABLE,
                'wb_orders_event',
                'wb_orders_event.id = wb_orders.event'
            );


        $qb
            ->addSelect('ord_product.product AS product_event')
            //            ->addSelect('ord_product.offer AS product_offer')
            //            ->addSelect('ord_product.variation AS product_variation')
            ->leftJoin(
                'ord',
                OrderProduct::TABLE,
                'ord_product',
                'ord_product.event = ord.event'
            );


        /* Получаем настройки бокового стикера */
        $qb->leftJoin(
            'ord_product',
            ProductEvent::TABLE,
            'product_event',
            'product_event.id = ord_product.product'
        );

        $qb->leftJoin(
            'ord_product',
            ProductCategory::TABLE,
            'product_category',
            'product_category.event = product_event.id AND product_category.root = true'
        );


        $qb->leftJoin(
            'product_event',
            ProductInfo::TABLE,
            'product_info',
            'product_info.product = product_event.main'
        );


        //$qb->addSelect('barcode.event');
        $qb->join(
            'product_category',
            WbBarcode::TABLE,
            'barcode',
            'barcode.id = product_category.category AND barcode.profile = product_info.profile'
        );

        $qb->addSelect('barcode_event.offer AS barcode_offer');
        $qb->addSelect('barcode_event.variation AS barcode_variation');
        $qb->addSelect('barcode_event.counter AS barcode_counter');

        $qb->join(
            'barcode',
            WbBarcodeEvent::TABLE,
            'barcode_event',
            'barcode_event.id = barcode.event'
        );


        $qb->addSelect('product_trans.name AS product_name');

        $qb->leftJoin(
            'product_event',
            ProductTrans::TABLE,
            'product_trans',
            'product_trans.event = product_event.id AND product_trans.local = :local'
        );


        $qb->addSelect('product_offer.value as product_offer_value');
        $qb->leftJoin(
            'ord_product',
            ProductOffer::TABLE,
            'product_offer',
            'product_offer.id = ord_product.offer '
        );

        /* Получаем тип торгового предложения */
        $qb->addSelect('category_offer.reference AS product_offer_reference');
        $qb->leftJoin(
            'product_offer',
            CategoryProductOffers::TABLE,
            'category_offer',
            'category_offer.id = product_offer.category_offer'
        );

        /* Получаем название торгового предложения */
        $qb->addSelect('category_offer_trans.name as product_offer_name');
        $qb->addSelect('category_offer_trans.postfix as product_offer_name_postfix');
        $qb->leftJoin(
            'category_offer',
            CategoryProductOffersTrans::TABLE,
            'category_offer_trans',
            'category_offer_trans.offer = category_offer.id AND category_offer_trans.local = :local'
        );


        $qb->addSelect('product_variation.value as product_variation_value');
        $qb->leftJoin(
            'ord_product',
            ProductVariation::TABLE,
            'product_variation',
            'product_variation.id = ord_product.variation '
        );


        /* Получаем тип множественного варианта */
        $qb->addSelect('category_variation.reference as product_variation_reference');
        $qb->leftJoin(
            'product_variation',
            CategoryProductVariation::TABLE,
            'category_variation',
            'category_variation.id = product_variation.category_variation'
        );

        /* Получаем название множественного варианта */
        $qb->addSelect('category_variation_trans.name as product_variation_name');

        $qb->addSelect('category_variation_trans.postfix as product_variation_name_postfix');
        $qb->leftJoin(
            'category_variation',
            CategoryProductVariationTrans::TABLE,
            'category_variation_trans',
            'category_variation_trans.variation = category_variation.id AND category_variation_trans.local = :local'
        );


        //        $qb->leftJoin(
        //            'ord_product',
        //            ProductModification::TABLE,
        //            'product_modification',
        //            'product_modification.id = ord_product.modification'
        //        );


        //        $qb
        //            ->addSelect('card_variation.barcode')
        //            ->leftJoin(
        //                'product_variation',
        //                WbProductCardVariation::TABLE,
        //                'card_variation',
        //                'card_variation.variation = product_variation.const'
        //            );


        $qb->addSelect('
            COALESCE(
                product_modification.article, 
                product_variation.article, 
                product_offer.article, 
                product_info.article
            ) AS product_article
		');


        return $qb
            //->enableCache('wildberries-package', 3600)
            ->fetchAllAssociative();
    }

    /**
     * Метод получает список продукции, добавленной в поставку (заказы группируются)
     */

    public function fetchAllPrintOrdersPackageSupplyAssociative(WbSupplyUid $supply): ?array
    {
        $qb = $this->DBALQueryBuilder
            ->createQueryBuilder(self::class)
            ->bindLocal();

        $qb->select('package_supply.main AS id');

        $qb
            ->from(WbPackageSupply::TABLE, 'package_supply')
            ->where('package_supply.supply = :supply')
            ->andWhere('package_supply.print = false')
            ->setParameter('supply', $supply, WbSupplyUid::TYPE);

        $qb
            ->addSelect('package_event.total AS product_total')
            ->leftJoin(
                'package_supply',
                WbPackageEvent::TABLE,
                'package_event',
                'package_event.id = package_supply.event'
            );

        $qb->leftOneJoin(
            'package_supply',
            WbPackageOrder::TABLE,
            'package_orders',
            'package_orders.event = package_supply.event'
        );

        /** Стикеры Wildberries */


        $qb
            ->addSelect('
                CASE 
                WHEN ord_sticker.sticker IS NULL 
                THEN FALSE
                ELSE TRUE
                END AS sticker
            ')
            ->leftJoin(
                'package_orders',
                WbOrdersSticker::TABLE,
                'ord_sticker',
                'ord_sticker.main = package_orders.id'
            );


        $qb->leftJoin(
            'package_orders',
            Order::TABLE,
            'ord',
            'ord.id = package_orders.id'
        );


        $qb->leftJoin(
            'ord',
            OrderProduct::TABLE,
            'ord_product',
            'ord_product.event = ord.event'
        );


        $qb->addSelect('product_event.id AS product_event');
        $qb->leftJoin(
            'ord_product',
            ProductEvent::TABLE,
            'product_event',
            'product_event.id = ord_product.product'
        );

        $qb->addSelect('product_trans.name AS product_name');
        $qb->leftJoin(
            'product_event',
            ProductTrans::TABLE,
            'product_trans',
            'product_trans.event = product_event.id AND product_trans.local = :local'
        );

        /* Торговое предложение */

        $qb->addSelect('product_offer.id as product_offer_uid');
        $qb->addSelect('product_offer.value as product_offer_value');
        $qb->addSelect('product_offer.postfix as product_offer_postfix');


        $qb->leftJoin(
            'ord_product',
            ProductOffer::TABLE,
            'product_offer',
            'product_offer.id = ord_product.offer OR product_offer.id IS NULL'
        );

        /* Получаем тип торгового предложения */
        $qb->addSelect('category_offer.reference AS product_offer_reference');
        $qb->leftJoin(
            'product_offer',
            CategoryProductOffers::TABLE,
            'category_offer',
            'category_offer.id = product_offer.category_offer'
        );

        /* Получаем название торгового предложения */
        $qb->addSelect('category_offer_trans.name as product_offer_name');
        $qb->addSelect('category_offer_trans.postfix as product_offer_name_postfix');
        $qb->leftJoin(
            'category_offer',
            CategoryProductOffersTrans::TABLE,
            'category_offer_trans',
            'category_offer_trans.offer = category_offer.id AND category_offer_trans.local = :local'
        );


        /* Множественные варианты торгового предложения */

        $qb->addSelect('product_variation.id as product_variation_uid');
        $qb->addSelect('product_variation.value as product_variation_value');
        $qb->addSelect('product_variation.postfix as product_variation_postfix');

        $qb->leftJoin(
            'ord_product',
            ProductVariation::TABLE,
            'product_variation',
            'product_variation.id = ord_product.variation OR product_variation.id IS NULL '
        );


        /* Получаем тип множественного варианта */
        $qb->addSelect('category_variation.reference as product_variation_reference');
        $qb->leftJoin(
            'product_variation',
            CategoryProductVariation::TABLE,
            'category_variation',
            'category_variation.id = product_variation.category_variation'
        );

        /* Получаем название множественного варианта */
        $qb->addSelect('category_variation_trans.name as product_variation_name');

        $qb->addSelect('category_variation_trans.postfix as product_variation_name_postfix');
        $qb->leftJoin(
            'category_variation',
            CategoryProductVariationTrans::TABLE,
            'category_variation_trans',
            'category_variation_trans.variation = category_variation.id AND category_variation_trans.local = :local'
        );


        /* Модификация множественного варианта торгового предложения */

        $qb->addSelect('product_modification.id as product_modification_uid');
        $qb->addSelect('product_modification.value as product_modification_value');
        $qb->addSelect('product_modification.postfix as product_modification_postfix');

        $qb->leftJoin(
            'ord_product',
            ProductModification::TABLE,
            'product_modification',
            'product_modification.id = ord_product.modification OR product_modification.id IS NULL '
        );


        /* Получаем тип модификации множественного варианта */
        $qb->addSelect('category_modification.reference as product_modification_reference');
        $qb->leftJoin(
            'product_modification',
            CategoryProductModification::TABLE,
            'category_modification',
            'category_modification.id = product_modification.category_modification'
        );

        /* Получаем название типа модификации */
        $qb->addSelect('category_modification_trans.name as product_modification_name');
        $qb->addSelect('category_modification_trans.postfix as product_modification_name_postfix');
        $qb->leftJoin(
            'category_modification',
            CategoryProductModificationTrans::TABLE,
            'category_modification_trans',
            'category_modification_trans.modification = category_modification.id AND category_modification_trans.local = :local'
        );


        /* Фото продукта */

        $qb->leftJoin(
            'product_event',
            ProductPhoto::TABLE,
            'product_photo',
            'product_photo.event = product_event.id AND product_photo.root = true'
        );

        $qb->leftJoin(
            'product_offer',
            ProductModificationImage::TABLE,
            'product_modification_image',
            'product_modification_image.modification = product_modification.id AND product_modification_image.root = true'
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

        $qb->addSelect(
            "
			CASE
				WHEN product_modification_image.name IS NOT NULL THEN
					CONCAT ( '/upload/".ProductModificationImage::TABLE."' , '/', product_modification_image.name)
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

        /* Флаг загрузки файла CDN */
        $qb->addSelect('
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
        $qb->addSelect('
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


        return $qb
            //->enableCache('wildberries-package', 3600)
            ->fetchAllAssociative();
    }


}