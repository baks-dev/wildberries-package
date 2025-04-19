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

namespace BaksDev\Wildberries\Package\UseCase\Package\Pack\Orders;

use BaksDev\Orders\Order\Entity\Order;
use BaksDev\Orders\Order\Type\Id\OrderUid;
use BaksDev\Wildberries\Package\Entity\Package\Orders\WbPackageOrderInterface;
use BaksDev\Wildberries\Package\Type\Package\Status\WbPackageStatus;
use Doctrine\DBAL\Types\Types;
use Symfony\Component\Validator\Constraints as Assert;

/** @see WbPackageOrder */
final class WbPackageOrderDTO implements WbPackageOrderInterface
{
    /**
     * ID заказа
     */
    #[Assert\NotBlank]
    #[Assert\Uuid]
    private OrderUid $id;

    /**
     * Статус заказа
     */
    #[Assert\NotBlank]
    private readonly WbPackageStatus $status;

    /** Порядок сортировки */
    #[Assert\NotBlank]
    private ?int $sort;


    public function __construct()
    {
        $this->status = new WbPackageStatus(WbPackageStatus\WbPackageStatusNew::class);
    }


    /**
     * Ord
     */
    public function getId(): OrderUid
    {
        return $this->id;
    }

    public function setId(Order|OrderUid $id): self
    {
        $this->id = $id instanceof Order ? $id->getId() : $id;
        return $this;
    }

    /**
     * Status
     */
    public function getStatus(): WbPackageStatus
    {
        return $this->status;
    }

    public function getSort(): ?int
    {
        return $this->sort;
    }

    public function setSort(?int $sort): self
    {
        $this->sort = $sort;
        return $this;
    }
}