<?php
/*
 *  Copyright 2025.  Baks.dev <admin@baks.dev>
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

namespace BaksDev\Wildberries\Package\Repository\Package\OrdersByPackage;

use BaksDev\Materials\Sign\Entity\Event\MaterialSignEvent;
use BaksDev\Materials\Sign\Type\Event\MaterialSignEventUid;
use BaksDev\Materials\Sign\Type\Status\MaterialSignStatus;
use BaksDev\Orders\Order\Type\Id\OrderUid;
use BaksDev\Products\Product\Type\Event\ProductEventUid;
use BaksDev\Products\Product\Type\Offers\Id\ProductOfferUid;
use BaksDev\Products\Product\Type\Offers\Variation\Id\ProductVariationUid;
use BaksDev\Products\Product\Type\Offers\Variation\Modification\Id\ProductModificationUid;
use BaksDev\Wildberries\Package\Type\Package\Status\WbPackageStatus;
use Symfony\Component\Validator\Constraints as Assert;

/** @see OrdersByPackageResult */
final readonly class OrdersByPackageResult
{
    public function __construct(
        private string $order, // @see WbPackageOrder
        private string $order_status,
        private int $total,
        private string $supply,
        private string $number,
        private string $product_event,

        private ?string $product_offer,
        private ?string $product_variation,
        private ?string $product_modification,

        private ?string $code_event = null,
        private ?string $code_status = null,
        private ?string $code_image = null,
        private ?string $code_ext = null,
        private ?bool $code_cdn = null,
        private ?string $code_string = null
    ) {}

    public function getOrderId(): OrderUid
    {
        return new OrderUid($this->order);
    }

    public function getOrderStatus(): WbPackageStatus
    {
        return new WbPackageStatus($this->order_status);
    }

    public function getTotal(): int
    {
        return $this->total;
    }

    /**
     * Идентификатор поставки
     * @example WB-GI-1234567
     */
    public function getSupply(): string
    {
        return $this->supply;
    }

    /**
     * Идентификатор заказа Wildberries
     * @example 3242965305
     */
    public function getOrderNumber(): string
    {
        return str_replace('W-', '', $this->number);
    }

    /** Идентификатор события продукта */
    public function getProductEvent(): ProductEventUid
    {
        return new ProductEventUid($this->product_event);
    }

    /** Идентификатор торгового предложения */
    public function getProductOffer(): ProductOfferUid|false
    {
        return $this->product_offer ? new ProductOfferUid($this->product_offer) : false;
    }

    /** Идентификатор множественного варианта торгового предложения */
    public function getProductVariation(): ProductVariationUid|false
    {
        return $this->product_variation ? new ProductVariationUid($this->product_variation) : false;
    }

    /** Идентификатор модификации множественного варианта торгового предложения */
    public function getProductModification(): ProductModificationUid|false
    {
        return $this->product_modification ? new ProductModificationUid($this->product_modification) : false;
    }

    public function getCodeEvent(): MaterialSignEventUid|false
    {
        return $this->code_event ? new MaterialSignEventUid($this->code_event) : false;
    }

    public function getCodeStatus(): MaterialSignStatus|false
    {
        return $this->code_status ? new MaterialSignStatus($this->code_status) : false;
    }

    public function getCodeImage(): ?string
    {
        return $this->code_image;
    }

    public function getCodeExt(): ?string
    {
        return $this->code_ext;
    }

    public function getCodeCdn(): bool
    {
        return $this->code_cdn === true;
    }

    public function isExistCode(): bool
    {
        return empty($this->code_string) === false;
    }

    public function getCodeString(): string|false
    {
        if($this->isExistCode())
        {
            $subChar = "";
            preg_match_all('/\((\d{2})\)((?:(?!\(\d{2}\)).)*)/', $this->code_string, $matches, PREG_SET_ORDER);
            return $matches[0][1].$matches[0][2].$matches[1][1].$matches[1][2].$subChar.$matches[2][1].$matches[2][2].$subChar.$matches[3][1].$matches[3][2];
        }

        return false;
    }

}