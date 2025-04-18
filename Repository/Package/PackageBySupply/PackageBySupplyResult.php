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

namespace BaksDev\Wildberries\Package\Repository\Package\PackageBySupply;

use BaksDev\Wildberries\Package\Type\Package\Event\WbPackageEventUid;
use BaksDev\Wildberries\Package\Type\Package\Id\WbPackageUid;
use BaksDev\Wildberries\Package\Type\Supply\Id\WbSupplyUid;

/** @see PackageBySupplyResult */
final readonly class PackageBySupplyResult
{
    public function __construct(
        private string $main,
        private string $event,
        private string $supply,
        private bool $print
    ) {}

    /**
     * Идентификатор WbSupply
     */
    public function getId(): WbPackageUid
    {
        return new WbPackageUid($this->main);
    }

    /**
     * Идентификатор события
     */
    public function getEvent(): WbPackageEventUid
    {
        return new WbPackageEventUid($this->event);
    }

    /**
     * Идентификатор Поставки
     */
    public function getSupply(): WbSupplyUid
    {
        return new WbSupplyUid($this->supply);
    }

    /**
     * Статус печати стикеров упаковки
     */
    public function isPrint(): bool
    {
        return $this->print === true;
    }
}