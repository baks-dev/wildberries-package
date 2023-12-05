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

namespace BaksDev\Wildberries\Products\Entity\Barcode\Event;

use BaksDev\Core\Entity\EntityEvent;
use BaksDev\Products\Category\Type\Id\ProductCategoryUid;
use BaksDev\Wildberries\Products\Entity\Barcode\Custom\WbBarcodeCustom;
use BaksDev\Wildberries\Products\Entity\Barcode\Modify\WbBarcodeModify;
use BaksDev\Wildberries\Products\Entity\Barcode\Property\WbBarcodeProperty;
use BaksDev\Wildberries\Products\Entity\Barcode\WbBarcode;
use BaksDev\Wildberries\Products\Type\Barcode\Event\WbBarcodeEventUid;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(name: 'wb_barcode_event')]
class WbBarcodeEvent extends EntityEvent
{
    const TABLE = 'wb_barcode_event';

    /** ID События */
    #[Assert\NotBlank]
    #[Assert\Uuid]
    #[ORM\Id]
    #[ORM\Column(type: WbBarcodeEventUid::TYPE)]
    private WbBarcodeEventUid $id;

    /**
     * ID категории
     */
    #[Assert\NotBlank]
    #[Assert\Uuid]
    #[ORM\Column(type: ProductCategoryUid::TYPE)]
    private ProductCategoryUid $category;

    /**
     * Добавить Торговое предложение в стикер
     */
    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $offer = false;

    /**
     * Добавить Множественный вариант в стикер
     */
    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $variation = false;

    /**
     * Количество стикеров
     */
    #[Assert\NotBlank]
    #[Assert\Range(min: 1, max: 5)]
    #[ORM\Column(type: Types::SMALLINT)]
    private int $counter = 1;

    /** Коллекция свойств продукта */
    #[Assert\Valid]
    #[ORM\OneToMany(mappedBy: 'event', targetEntity: WbBarcodeProperty::class, cascade: ['all'])]
    private Collection $property;

    /** Коллекция пользовательских свойств */
    #[Assert\Valid]
    #[ORM\OneToMany(mappedBy: 'event', targetEntity: WbBarcodeCustom::class, cascade: ['all'])]
    private Collection $custom;

    /** Модификатор */
    #[Assert\Valid]
    #[ORM\OneToOne(mappedBy: 'event', targetEntity: WbBarcodeModify::class, cascade: ['all'])]
    private WbBarcodeModify $modify;

    public function __construct()
    {
        $this->id = new WbBarcodeEventUid();
        $this->modify = new WbBarcodeModify($this);
    }

    public function __clone()
    {
        $this->id = clone $this->id;
    }

    public function __toString(): string
    {
        return (string) $this->id;
    }

    public function getDto($dto): mixed
    {
        $dto = is_string($dto) && class_exists($dto) ? new $dto() : $dto;

        if($dto instanceof WbBarcodeEventInterface)
        {
            return parent::getDto($dto);
        }

        throw new InvalidArgumentException(sprintf('Class %s interface error', $dto::class));
    }

    public function setMain(WbBarcode $main): self
    {
        return $this;
    }


    public function setEntity($dto): mixed
    {
        if($dto instanceof WbBarcodeEventInterface || $dto instanceof self)
        {
            return parent::setEntity($dto);
        }

        throw new InvalidArgumentException(sprintf('Class %s interface error', $dto::class));
    }

    public function getId(): WbBarcodeEventUid
    {
        return $this->id;
    }

    public function getCategory(): ProductCategoryUid
    {
        return $this->category;
    }

}