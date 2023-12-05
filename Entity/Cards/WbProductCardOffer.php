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
use BaksDev\Products\Product\Type\Offers\ConstId\ProductOfferConst;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;
use Symfony\Component\Validator\Constraints as Assert;

/* Торговые предложения карточки товаров Wildberries */

#[ORM\Entity]
#[ORM\Table(name: 'wb_product_card_offer')]
class WbProductCardOffer extends EntityState
{

    public const TABLE = 'wb_product_card_offer';

    /**
     * Номенклатура Wildberries
     */
    #[Assert\NotBlank]
    #[ORM\Id]
    #[ORM\Column(type: Types::INTEGER)]
    private readonly int $nomenclature;

    /**
     * Идентификатор карточки
     */
    #[ORM\ManyToOne(targetEntity: WbProductCard::class, inversedBy: "offer")]
    #[ORM\JoinColumn(name: 'card', referencedColumnName: "id")]
    private WbProductCard $card;

    /**
     * Идентификатор постоянного торгового предложения в продукте
     */
    #[Assert\NotBlank]
    #[Assert\Uuid]
    #[ORM\Column(type: ProductOfferConst::TYPE)]
    private ProductOfferConst $offer;


    public function __construct(WbProductCard $card,)
    {
        $this->card = $card;
    }


    public function __toString(): string
    {
        return (string) $this->nomenclature;
    }



    public function getDto($dto): mixed
    {
        $dto = is_string($dto) && class_exists($dto) ? new $dto() : $dto;

        if($dto instanceof WbProductCardOfferInterface)
        {
            return parent::getDto($dto);
        }

        throw new InvalidArgumentException(sprintf('Class %s interface error', $dto::class));
    }


    public function setEntity($dto): mixed
    {
        if($dto instanceof WbProductCardOfferInterface || $dto instanceof self)
        {
            return parent::setEntity($dto);
        }

        throw new InvalidArgumentException(sprintf('Class %s interface error', $dto::class));
    }

    public function getCard(): WbProductCard
    {
        return $this->card;
    }

}