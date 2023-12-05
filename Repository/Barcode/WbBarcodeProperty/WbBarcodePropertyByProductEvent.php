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

namespace BaksDev\Wildberries\Products\Repository\Barcode\WbBarcodeProperty;

use BaksDev\Products\Product\Entity\Category\ProductCategory;
use BaksDev\Products\Product\Entity\Event\ProductEvent;
use BaksDev\Products\Product\Entity\Info\ProductInfo;
use BaksDev\Products\Product\Entity\Property\ProductProperty;
use BaksDev\Products\Product\Type\Event\ProductEventUid;
use BaksDev\Wildberries\Products\Entity\Barcode\Custom\WbBarcodeCustom;
use BaksDev\Wildberries\Products\Entity\Barcode\Event\WbBarcodeEvent;
use BaksDev\Wildberries\Products\Entity\Barcode\Property\WbBarcodeProperty;
use BaksDev\Wildberries\Products\Entity\Barcode\WbBarcode;
use Doctrine\DBAL\Connection;

final class WbBarcodePropertyByProductEvent implements WbBarcodePropertyByProductEventInterface
{

    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }
    
    
    public function getPropertyCollection(ProductEvent|ProductEventUid|string $event) : array
    {
        if(is_string($event))
        {
            $event = new ProductEventUid($event);
        }

        if($event instanceof ProductEvent)
        {
            $event = $event->getId();
        }


        $property = $this->getProperty();
        $custom = $this->getCustom();
        
        $qb = $this->connection->prepare($property.' UNION '.$custom.' ORDER BY sort ');
        $qb->bindValue('event', $event, ProductEventUid::TYPE);


        return $qb->executeQuery()->fetchAllAssociative();
    }
    
    
    
    private function getProperty(): string
    {
        $qb = $this->from();
        
        $qb->addSelect('sticker_settings_property.sort');
        $qb->addSelect('sticker_settings_property.name');

        $qb->join(
          'sticker_settings_event',
          WbBarcodeProperty::TABLE,
          'sticker_settings_property',
          'sticker_settings_property.event = sticker_settings_event.id'
        );

        $qb->addSelect('product_property.value');

        $qb->join(
          'sticker_settings_property',
          ProductProperty::TABLE,
          'product_property',
          'product_property.event = product_event.id AND product_property.field = sticker_settings_property.offer');
        
        
        return $qb->getSQL();
    }
    
    
    private function getCustom(): string
    {
        $qb = $this->from();
        
        $qb->addSelect('sticker_settings_custom.sort');
        $qb->addSelect('sticker_settings_custom.name');
        $qb->addSelect('sticker_settings_custom.value');
        $qb->join(
          'sticker_settings_event',
          WbBarcodeCustom::TABLE,
          'sticker_settings_custom',
          'sticker_settings_custom.event = sticker_settings_event.id'
        );
        
        return $qb->getSQL();
    }
    

    private function from()
    {
        $qb = $this->connection->createQueryBuilder();
    
        $qb->from(ProductEvent::TABLE, 'product_event');

        /* Категория продукта */
        //$qb->select('product_event_category.category');


        $qb->leftJoin(
          'product_event',
          ProductInfo::TABLE,
          'product_info',
          'product_info.product = product_event.main'
        );


        $qb->leftJoin(
          'product_event',
          ProductCategory::TABLE,
          'product_event_category',
          'product_event_category.event = product_event.id AND product_event_category.root = true'
        );

       
        $qb->join(
          'product_event',
          WbBarcode::TABLE,
          'sticker_settings',
          'sticker_settings.id = product_event_category.category AND sticker_settings.profile = product_info.profile'
        );
    
        $qb->addSelect('sticker_settings_event.offer');
        $qb->addSelect('sticker_settings_event.counter');
        $qb->join(
          'sticker_settings',
          WbBarcodeEvent::TABLE,
          'sticker_settings_event',
          'sticker_settings_event.id = sticker_settings.event'
        );
    
        $qb->where('product_event.id = :event');
        
        return $qb;
    }
    
}