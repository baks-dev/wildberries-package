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

namespace BaksDev\Wildberries\Package\Repository\Package\AllOrdersPackage;

use BaksDev\Core\Type\Field\InputField;
use BaksDev\Products\Product\Type\Event\ProductEventUid;
use BaksDev\Products\Product\Type\Invariable\ProductInvariableUid;
use BaksDev\Products\Product\Type\Offers\Id\ProductOfferUid;
use BaksDev\Products\Product\Type\Offers\Variation\Id\ProductVariationUid;
use BaksDev\Products\Product\Type\Offers\Variation\Modification\Id\ProductModificationUid;
use DateTimeImmutable;
use Symfony\Component\Validator\Constraints as Assert;

final class AllOrderPackageResult
{

    public function __construct(

        private string $invariable, //" => "0199e478-9679-71c3-b867-564aee8e914a"
        private string $product, //" => "019a7e5a-3fcf-7467-add9-b21b988c52b4"

        private string $product_name, //" => "Triangle AdvanteX TC101"
        private string $card_article, //" => "TC101"
        private string $product_article, //" => "TC101-15-185-55-82V"
        private string $order_data, // " => "2025-11-17 00:00:00"


        private ?string $offer, //" => "019a7e5a-3fd1-7c0f-8933-2f0ea1ae6075"
        private ?string $product_offer_value, //" => "15"
        private ?string $product_offer_postfix, //" => null
        private ?string $product_offer_reference, //" => "tire_radius_field"

        private ?string $variation, //" => "019a7e5a-3fd1-7c0f-8933-2f0ea21d2c07"
        private ?string $product_variation_value, //" => "185"
        private ?string $product_variation_postfix, //" => null
        private ?string $product_variation_reference, //" => "tire_width_field"

        private ?string $modification, //" => "019a7e5a-3fd1-7c0f-8933-2f0ea2f70501"
        private ?string $product_modification_value, //" => "55"
        private ?string $product_modification_postfix, //" => "82V"
        private ?string $product_modification_reference, //" => "tire_profile_field"

        private ?string $product_image, //" => "/upload/product_photo/6c0003c59af4454b3e3697ddec435f3f"
        private ?string $product_image_ext, //" => "webp"
        private ?bool $product_image_cdn, //" => true

        private ?int $order_total, //" => 6
        private ?int $stock_total, //" => 11720
        private ?int $stock_available, //" => 11720

        private ?string $exist_manufacture, //" => "176.332.455.327"
    ) {}

    public function getProductInvariable(): ProductInvariableUid
    {
        return new ProductInvariableUid($this->invariable);
    }

    public function getProductEvent(): ProductEventUid
    {
        return new ProductEventUid($this->product);
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

    public function getOrderData(): DateTimeImmutable
    {
        return new DateTimeImmutable($this->order_data);
    }

    /** Торговое предложение */

    public function getOfferId(): ProductOfferUid|false
    {
        return $this->offer ? new ProductOfferUid($this->offer) : false;
    }

    public function getProductOfferValue(): ?string
    {
        return $this->product_offer_value;
    }

    public function getProductOfferPostfix(): ?string
    {
        return $this->product_offer_postfix;
    }

    public function getProductOfferReference(): InputField
    {
        return new InputField($this->product_offer_reference);
    }

    /**
     * Множественный вариант
     */


    public function getVariationId(): ProductVariationUid|false
    {
        return $this->variation ? new ProductVariationUid($this->variation) : false;
    }

    public function getProductVariationValue(): ?string
    {
        return $this->product_variation_value;
    }

    public function getProductVariationPostfix(): ?string
    {
        return $this->product_variation_postfix;
    }

    public function getProductVariationReference(): InputField
    {
        return new InputField($this->product_variation_reference);
    }


    /**
     * Модификация множественного варианта
     */

    public function getModificationId(): ProductModificationUid|false
    {
        return $this->modification ? new ProductModificationUid($this->modification) : false;
    }

    public function getProductModificationValue(): ?string
    {
        return $this->product_modification_value;
    }

    public function getProductModificationPostfix(): ?string
    {
        return $this->product_modification_postfix;
    }

    public function getProductModificationReference(): InputField
    {
        return new InputField($this->product_modification_reference);
    }

    /**
     * Изображение
     */

    public function getProductImage(): ?string
    {
        return $this->product_image;
    }

    public function getProductImageExt(): ?string
    {
        return $this->product_image_ext;
    }

    public function getProductImageCdn(): bool
    {
        return true === $this->product_image_cdn;
    }

    /**
     * Остатки
     */

    public function getOrderTotal(): int
    {
        return $this->order_total ? max($this->order_total, 0) : 0;
    }

    public function getStockTotal(): int
    {
        return $this->stock_total ? max($this->stock_total, 0) : 0;
    }

    public function getStockAvailable(): int
    {
        return $this->stock_available ? max($this->stock_available, 0) : 0;
    }

    public function getManufactureExist(): ?string
    {
        return $this->exist_manufacture;
    }
}