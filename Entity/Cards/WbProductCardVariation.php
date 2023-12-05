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
use BaksDev\Products\Product\Type\Offers\Variation\ConstId\ProductVariationConst;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;
use Symfony\Component\Validator\Constraints as Assert;

/* Торговые предложения карточки товаров Wildberries */


#[ORM\Entity]
#[ORM\Table(name: 'wb_product_card_variation')]
class WbProductCardVariation extends EntityState
{

    public const TABLE = 'wb_product_card_variation';

    /**
     * Cтроковый идентификатор множественного варианта Wildberries
     */
    #[Assert\NotBlank]
    #[ORM\Id]
    #[ORM\Column(type: Types::STRING)]
    private readonly string $barcode;

    /**
     * Идентификатор карточки
     */
    #[ORM\ManyToOne(targetEntity: WbProductCard::class, inversedBy: "variation")]
    #[ORM\JoinColumn(name: 'card', referencedColumnName: "id")]
    private WbProductCard $card;

    /**
     * Идентификатор постоянного множественного варианта торгового предложения
     */
    #[Assert\NotBlank]
    #[Assert\Uuid]
    #[ORM\Column(type: ProductVariationConst::TYPE, unique: true)]
    private ProductVariationConst $variation;


    public function __construct(WbProductCard $card,)
    {
        $this->card = $card;
    }

    public function __toString(): string
    {
        return $this->barcode;
    }

    public function getDto($dto): mixed
    {
        $dto = is_string($dto) && class_exists($dto) ? new $dto() : $dto;

        if($dto instanceof WbProductCardVariationInterface)
        {
            return parent::getDto($dto);
        }

        throw new InvalidArgumentException(sprintf('Class %s interface error', $dto::class));
    }


    public function setEntity($dto): mixed
    {
        if($dto instanceof WbProductCardVariationInterface || $dto instanceof self)
        {
            return parent::setEntity($dto);
        }

        throw new InvalidArgumentException(sprintf('Class %s interface error', $dto::class));
    }

    /**
     * Barcode
     */
    public function getBarcode(): string
    {
        return $this->barcode;
    }

    /**
     * Card
     */
    public function getCard(): WbProductCard
    {
        return $this->card;
    }

    /**
     * Variation
     */
    public function getVariation(): ProductVariationConst
    {
        return $this->variation;
    }


}