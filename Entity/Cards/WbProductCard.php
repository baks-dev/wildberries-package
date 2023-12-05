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

namespace BaksDev\Wildberries\Products\Entity\Cards;

use BaksDev\Core\Entity\EntityState;
use BaksDev\Products\Product\Type\Id\ProductUid;
use BaksDev\Wildberries\Products\Type\Cards\Id\WbCardUid;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;

/* Карточки товаров Wildberries и связь на продукт */


#[ORM\Entity]
#[ORM\Table(name: 'wb_product_card')]
class WbProductCard extends EntityState
{

    public const TABLE = 'wb_product_card';

    /** Идентификатор карточки */
    #[ORM\Id]
    #[ORM\Column(type: WbCardUid::TYPE)]
    private WbCardUid $id;

    /**
     * Идентификатор карточки товара Wildberries
     * (не уникально, в одной карточке может быть несколько продуктов)
     */
    #[ORM\Column(type: Types::INTEGER)]
    private int $imtId;

    /**
     * Идентификатор продукта
     */
    #[ORM\Column(type: ProductUid::TYPE, unique: true)]
    private ProductUid $product;

    /**
     * Торговые предложения
     */
    #[ORM\OneToMany(mappedBy: 'card', targetEntity: WbProductCardOffer::class, cascade: ['all'])]
    private Collection $offer;

    /**
     * Множественные варианты торгового предложения
     */
    #[ORM\OneToMany(mappedBy: 'card', targetEntity: WbProductCardVariation::class, cascade: ['all'])]
    private Collection $variation;


    public function __construct()
    {
        $this->id = new WbCardUid();
    }

    public function __toString(): string
    {
        return (string) $this->id;
    }

    public function getDto($dto): mixed
    {
        $dto = is_string($dto) && class_exists($dto) ? new $dto() : $dto;

        if($dto instanceof WbProductCardInterface)
        {
            return parent::getDto($dto);
        }

        throw new InvalidArgumentException(sprintf('Class %s interface error', $dto::class));
    }


    public function setEntity($dto): mixed
    {
        if($dto instanceof WbProductCardInterface || $dto instanceof self)
        {
            return parent::setEntity($dto);
        }

        throw new InvalidArgumentException(sprintf('Class %s interface error', $dto::class));
    }

    public function getId(): WbCardUid
    {
        return $this->id;
    }

    public function getProduct(): ProductUid
    {
        return $this->product;
    }

}