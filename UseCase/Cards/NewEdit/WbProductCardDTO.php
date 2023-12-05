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

namespace BaksDev\Wildberries\Products\UseCase\Cards\NewEdit;

use BaksDev\Products\Product\Entity\Product;
use BaksDev\Products\Product\Type\Id\ProductUid;
use BaksDev\Wildberries\Products\Entity\Cards\WbProductCardInterface;
use Doctrine\Common\Collections\ArrayCollection;

/** @see WbProductCard */
final class WbProductCardDTO implements WbProductCardInterface
{

    /**
     * Идентификатор карточки товара
     */
    private int $imtId;

    /**
     * Идентификатор продукта
     */
    private ProductUid $product;

    /**
     * Торговые предложения
     */
    private ArrayCollection $offer;

    /**
     * Множественные варианты торгового предложения
     */
    private ArrayCollection $variation;


    public function __construct()
    {
        $this->offer = new ArrayCollection();
        $this->variation = new ArrayCollection();
    }


    /**
     * ImtId
     */
    public function getImtId(): int
    {
        return $this->imtId;
    }


    public function setImtId(int $imtId): void
    {
        $this->imtId = $imtId;
    }


    /**
     * Product
     */
    public function getProduct(): ProductUid
    {
        return $this->product;
    }


    public function setProduct(ProductUid|Product $product): void
    {
        $this->product = $product instanceof Product ? $product->getId() : $product;
    }


    /**
     * Offer
     */
    public function getOffer(): ArrayCollection
    {
        return $this->offer;
    }


    public function setOffer(ArrayCollection $offer): void
    {
        $this->offer = $offer;
    }


    public function addOffer(Offer\WbProductCardOfferDTO $offer): void
    {

        $filter = $this->offer->filter(function(Offer\WbProductCardOfferDTO $element) use ($offer)
            {
                return $offer->getNomenclature() === $element->getNomenclature();
            });

        if($filter->isEmpty())
        {
            $this->offer->add($offer);
        }

    }


    public function removeOffer(Offer\WbProductCardOfferDTO $offer): void
    {
        $this->offer->removeElement($offer);
    }


    /**
     * Variation
     */
    public function getVariation(): ArrayCollection
    {
        return $this->variation;
    }


    public function setVariation(ArrayCollection $variation): void
    {
        $this->variation = $variation;
    }


    public function addVariation(Variation\WbProductCardVariationDTO $variation): void
    {
        $filter = $this->variation->filter(function(Variation\WbProductCardVariationDTO $element) use ($variation)
            {
                return $variation->getBarcode() === $element->getBarcode();
            });

        if($filter->isEmpty())
        {
            $this->variation->add($variation);
        }

    }


    public function removeVariation(Variation\WbProductCardVariationDTO $variation): void
    {
        $this->variation->removeElement($variation);
    }

}