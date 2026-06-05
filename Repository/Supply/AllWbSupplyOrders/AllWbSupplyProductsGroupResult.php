<?php
/*
 *  Copyright 2026.  Baks.dev <admin@baks.dev>
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

namespace BaksDev\Wildberries\Package\Repository\Supply\AllWbSupplyOrders;

use BaksDev\Products\Product\Type\Event\ProductEventUid;
use BaksDev\Products\Product\Type\Offers\Id\ProductOfferUid;
use BaksDev\Products\Product\Type\Offers\Variation\Id\ProductVariationUid;
use BaksDev\Products\Product\Type\Offers\Variation\Modification\Id\ProductModificationUid;
use Symfony\Component\Validator\Constraints as Assert;

/** @see AllWbSupplyProductsGroupResult */
final class AllWbSupplyProductsGroupResult
{

    /** Идентификатор */
    #[Assert\Uuid]
    private $id = null;


    public function __construct(
        private readonly string $wb_product_event, // " => "019a7e5a-3fcf-7467-add9-b21b988c52b4"
        private readonly ?string $wb_product_offer, // " => "019a7e5a-3fd1-7c0f-8933-2f0ea1ae6075"
        private readonly ?string $wb_product_variation, // " => "019a7e5a-3fd1-7c0f-8933-2f0ea21d2c07"
        private readonly ?string $wb_product_modification, // " => "019a7e5a-3fd1-7c0f-8933-2f0ea2f70501"

        private readonly string $product_name, // " => "Triangle AdvanteX TC101"
        private readonly string $card_article, // " => "TC101"
        private readonly string $product_article, // " => "TC101-15-185-55-82V"
        private readonly int $order_total, // " => 2


        private readonly ?string $product_offer_value, // " => "15"
        private readonly ?string $product_offer_postfix, // " => null
        private readonly ?string $product_offer_reference, // " => "tire_radius_field"

        private readonly ?string $product_variation_value, // " => "185"
        private readonly ?string $product_variation_postfix, // " => null
        private readonly ?string $product_variation_reference, // " => "tire_width_field"

        private readonly ?string $product_modification_value, // " => "55"
        private readonly ?string $product_modification_postfix, // " => "82V"
        private readonly ?string $product_modification_reference, // " => "tire_profile_field"

        private readonly ?string $product_image, // " => "/upload/product_photo/6c0003c59af4454b3e3697ddec435f3f"
        private readonly ?string $product_image_ext, // " => "webp"
        private readonly ?bool $product_image_cdn, // " => true
    ) {}

    public function getWbProductEvent(): ProductEventUid
    {
        return new ProductEventUid($this->wb_product_event);
    }

    public function getWbProductOffer(): ProductOfferUid|false
    {
        return $this->wb_product_offer ? new ProductOfferUid($this->wb_product_offer) : false;
    }

    public function getWbProductVariation(): ProductVariationUid|false
    {
        return $this->wb_product_variation ? new ProductVariationUid($this->wb_product_variation) : false;
    }

    public function getWbProductModification(): ProductModificationUid|false
    {
        return $this->wb_product_modification ? new ProductModificationUid($this->wb_product_modification) : false;
    }

    public function getProductName(): string
    {
        return $this->product_name;
    }

    public function getCardArticle(): string
    {
        return $this->card_article;
    }

    public function getProductArticle(): string
    {
        return $this->product_article;
    }

    public function getOrderTotal(): int
    {
        return $this->order_total;
    }

    public function getProductOfferValue(): ?string
    {
        return $this->product_offer_value;
    }

    public function getProductOfferPostfix(): ?string
    {
        return $this->product_offer_postfix;
    }

    public function getProductOfferReference(): ?string
    {
        return $this->product_offer_reference;
    }

    public function getProductVariationValue(): ?string
    {
        return $this->product_variation_value;
    }

    public function getProductVariationPostfix(): ?string
    {
        return $this->product_variation_postfix;
    }

    public function getProductVariationReference(): ?string
    {
        return $this->product_variation_reference;
    }


    public function getProductModificationValue(): ?string
    {
        return $this->product_modification_value;
    }

    public function getProductModificationPostfix(): ?string
    {
        return $this->product_modification_postfix;
    }

    public function getProductModificationReference(): ?string
    {
        return $this->product_modification_reference;
    }


    public function getProductImage(): ?string
    {
        return $this->product_image;
    }

    public function getProductImageExt(): ?string
    {
        return $this->product_image_ext;
    }

    public function getProductImageCdn(): ?bool
    {
        return $this->product_image_cdn === true;
    }


}