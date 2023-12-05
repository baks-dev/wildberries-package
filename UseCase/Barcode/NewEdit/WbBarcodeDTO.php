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

namespace BaksDev\Wildberries\Products\UseCase\Barcode\NewEdit;


use BaksDev\Products\Category\Type\Id\ProductCategoryUid;
use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;
use BaksDev\Wildberries\Products\Entity\Barcode\Event\WbBarcodeEventInterface;
use BaksDev\Wildberries\Products\Type\Barcode\Event\WbBarcodeEventUid;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Validator\Constraints as Assert;

/** @see WbBarcodeEvent */
final class WbBarcodeDTO implements WbBarcodeEventInterface
{
    /**
     * Идентификатор события
     */
    #[Assert\Uuid]
    private ?WbBarcodeEventUid $id = null;


    /**
     * Идентификатор категории
     */
    #[Assert\Uuid]
    #[Assert\NotBlank]
    private ?ProductCategoryUid $category = null;

    /**
     * Флаг для запрета редактирования категории
     */
    private bool $hidden = false;


    /**
     * Добавить Торговое предложение в стикер
     */
    private bool $offer = false;

    /**
     * Добавить Множественный вариант в стикер
     */
    private bool $variation = false;

    /**
     * Количество стикеров
     */
    #[Assert\NotBlank]
    #[Assert\Range(min: 1, max: 5)]
    private ?int $counter = 1;
    
    /**
     * Свойства товара
     */
    #[Assert\Valid]
    private ArrayCollection $property;
    
    /**
     * Пользовательские свойства
     */
    #[Assert\Valid]
    private ArrayCollection $custom;




    public function __construct() {
        $this->property = new ArrayCollection();
        $this->custom = new ArrayCollection();
    }
    
    public function getEvent() : ?WbBarcodeEventUid
    {
        return $this->id;
    }
    
    public function setId(WbBarcodeEventUid $id) : void
    {
        $this->id = $id;
    }
    

    public function getCategory() : ?ProductCategoryUid
    {
        return $this->category;
    }
    

    public function setCategory(string|ProductCategoryUid $category) : void
    {

        $this->category = $category instanceof ProductCategoryUid ? $category : new ProductCategoryUid($category);
    }

    public function hiddenCategory() : void
    {
        $this->hidden = true;
    }

    public function isHiddenCategory(): bool
    {
        return $this->hidden;
    }






    public function getProperty() : ArrayCollection
    {
        return $this->property;
    }

    public function addProperty(Property\WbBarcodePropertyDTO $property) : void
    {
        $this->property->add($property);
    }
    
    public function removeProperty(Property\WbBarcodePropertyDTO $property) : void
    {
        $this->property->removeElement($property);
    }
    
    public function getPropertyClass() : Property\WbBarcodePropertyDTO
    {
        return new Property\WbBarcodePropertyDTO();
    }
    
    /** OFFER */
    
    public function getOffer() : bool
    {
        return $this->offer;
    }
    
    public function setOffer(bool $offer) : void
    {
        $this->offer = $offer;
    }

    /**
     * Variation
     */
    public function getVariation(): bool
    {
        return $this->variation;
    }

    public function setVariation(bool $variation): self
    {
        $this->variation = $variation;
        return $this;
    }


    
    /** COUNTER */
    
    public function getCounter() : ?int
    {
        return $this->counter;
    }

    public function setCounter(?int $counter = 1) : void
    {
        $this->counter = $counter;
    }
    
    
    /** CUSTOM */
    
    public function getCustom() : ArrayCollection
    {
        return $this->custom;
    }

    public function addCustom(Custom\WbBarcodeCustomDTO $custom) : void
    {
        $this->custom->add($custom);
    }

    public function removeCustom(Custom\WbBarcodeCustomDTO $custom) : void
    {
        $this->custom->removeElement($custom);
    }

}

