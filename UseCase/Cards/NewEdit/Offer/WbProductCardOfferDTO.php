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

namespace BaksDev\Wildberries\Products\UseCase\Cards\NewEdit\Offer;

use BaksDev\Products\Product\Type\Offers\ConstId\ProductOfferConst;
use BaksDev\Wildberries\Products\Entity\Cards\WbProductCardOfferInterface;
use Symfony\Component\Validator\Constraints as Assert;

/** @see WbProductCardOffer */
final class WbProductCardOfferDTO implements WbProductCardOfferInterface
{

    /**
     * Номенклатура (Артикул WB)
     */
    #[Assert\NotBlank]
    private int $nomenclature;

    /**
     * ID постоянного торгового предложения в продукте
     */
    #[Assert\NotBlank]
    #[Assert\Uuid]
    private ProductOfferConst $offer;


    /**
     * Номенклатура
     */
    public function getNomenclature(): int
    {
        return $this->nomenclature;
    }


    public function setNomenclature(int $nomenclature): void
    {
        $this->nomenclature = $nomenclature;
    }


    /**
     * ID постоянного торгового предложения в продукте
     */
    public function getOffer(): ProductOfferConst
    {
        return $this->offer;
    }


    public function setOffer(ProductOfferConst $offer): void
    {
        $this->offer = $offer;
    }

}