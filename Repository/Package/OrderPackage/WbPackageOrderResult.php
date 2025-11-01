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

namespace BaksDev\Wildberries\Package\Repository\Package\OrderPackage;


use BaksDev\Materials\Sign\Entity\Event\MaterialSignEvent;
use BaksDev\Materials\Sign\Type\Event\MaterialSignEventUid;
use BaksDev\Materials\Sign\Type\Status\MaterialSignStatus;
use BaksDev\Orders\Order\Type\Id\OrderUid;
use BaksDev\Products\Product\Type\Event\ProductEventUid;
use BaksDev\Products\Product\Type\Offers\Id\ProductOfferUid;
use BaksDev\Products\Product\Type\Offers\Variation\Id\ProductVariationUid;
use BaksDev\Products\Product\Type\Offers\Variation\Modification\Id\ProductModificationUid;
use BaksDev\Wildberries\Package\Type\Package\Id\WbPackageUid;

final class WbPackageOrderResult
{
    /**
     * Идентификатор упаковки
     * @see WbPackageUid
     */
    private string $package;

    /**
     * Идентификатор заказа
     * @see OrderUid
     */
    private string $order;

    private string $number;

    /** @see ProductEventUid */
    private string $product;

    /** @see ProductOfferUid */
    private ?string $offer;

    /** @var ProductVariationUid */
    private ?string $variation;

    /** @var ProductModificationUid */
    private ?string $modification;


    /**
     * Событие честного знака
     * @see MaterialSignEventUid
     */
    private ?string $sign;

    /**
     * Статус честного знака
     * @see MaterialSignStatus
     */
    private ?string $status;

    /** Код честного знака */
    private ?string $code;

    /** Изображение честного знака */
    private ?string $code_image;
    private ?string $code_ext;
    private bool $code_cdn;


    public function __construct(...$data)
    {

        $this->order = $data['order'];
        $this->package = $data['package'];
        $this->number = $data['number'];

        $this->product = $data['product_event'];
        $this->offer = $data['product_offer'];
        $this->variation = $data['product_variation'];
        $this->modification = $data['product_modification'];

        $this->status = $data['status'] ?? null;
        $this->sign = $data['code_event'] ?? null;
        $this->code = $data['code_string'] ?? null;

        $this->code_image = $data['code_image'] ?? null;
        $this->code_ext = $data['code_ext'] ?? null;
        $this->code_cdn = isset($data['code_cdn']) ?  $data['code_cdn'] === true : false;

    }

    /**
     * Package
     */
    public function getPackage(): WbPackageUid
    {
        return new WbPackageUid($this->package);
    }

    /**
     * Order
     */
    public function getOrder(): OrderUid
    {
        return new OrderUid($this->order);
    }

    /**
     * Number
     */
    public function getNumber(): string
    {
        return $this->number;
    }

    /**
     * Product
     */
    public function getProduct(): ProductEventUid
    {
        return new ProductEventUid($this->product);
    }

    /**
     * Offer
     */
    public function getOffer(): ProductOfferUid|false
    {
        return $this->offer ? new ProductOfferUid($this->offer) : false;
    }

    /**
     * Variation
     */
    public function getVariation(): ProductVariationUid|false
    {
        return $this->variation ? new ProductVariationUid($this->variation) : false;
    }

    /**
     * Modification
     */
    public function getModification(): ProductModificationUid|false
    {
        return $this->modification ? new ProductModificationUid($this->modification) : false;
    }

    /**
     * Sign
     */
    public function getSign(): MaterialSignEventUid|false
    {
        return $this->sign ? new MaterialSignEventUid($this->sign) : false;
    }

    /**
     * Status
     */
    public function getStatus(): MaterialSignStatus|false
    {
        return $this->status ? new MaterialSignStatus($this->status) : false;
    }

    public function isExistCode(): bool
    {
        return empty($this->code) === false;
    }

    /**
     * Code
     */
    public function getCode(): string|false
    {
        if($this->isExistCode())
        {
            $subChar = "";
            preg_match_all('/\((\d{2})\)((?:(?!\(\d{2}\)).)*)/', $this->code, $matches, PREG_SET_ORDER);
            return $matches[0][1].$matches[0][2].$matches[1][1].$matches[1][2].$subChar.$matches[2][1].$matches[2][2].$subChar.$matches[3][1].$matches[3][2];
        }

        return false;
    }

    /**
     * CodeImage
     */
    public function getCodeImage(): ?string
    {
        return $this->code_image;
    }

    /**
     * CodeExt
     */
    public function getCodeExt(): ?string
    {
        return $this->code_ext;
    }

    /**
     * CodeCdn
     */
    public function getCodeCdn(): bool
    {
        return $this->code_cdn;
    }
}