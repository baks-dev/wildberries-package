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

namespace BaksDev\Wildberries\Package\UseCase\Package\OrderStatus;

use BaksDev\Orders\Order\Type\Id\OrderUid;
use BaksDev\Wildberries\Package\Entity\Package\Orders\WbPackageOrderInterface;
use BaksDev\Wildberries\Package\Type\Package\Status\WbPackageStatus;
use BaksDev\Wildberries\Package\Type\Package\Status\WbPackageStatus\Collection\WbPackageStatusInterface;
use Symfony\Component\Validator\Constraints as Assert;

/** @see WbPackageOrder */
final class UpdatePackageOrderStatusDTO implements WbPackageOrderInterface
{
    /**
     * ID заказа
     */
    #[Assert\NotBlank]
    #[Assert\Uuid]
    private readonly OrderUid $id;


    /**
     * Статус отправки заказа
     */
    #[Assert\NotBlank]
    private WbPackageStatus $status;


    public function __construct(OrderUid $id)
    {
        $this->id = $id;
    }

    /**
     * Id
     */
    public function getId(): OrderUid
    {
        return $this->id;
    }

    /**
     * Status
     */
    public function setStatus(WbPackageStatus|WbPackageStatusInterface|string $status): void
    {
        if(is_string($status) && class_exists($status))
        {
            $status = new $status();
        }

        $this->status = $status instanceof WbPackageStatusInterface ? new WbPackageStatus($status) : $status;
    }

    public function getStatus(): WbPackageStatus
    {
        return $this->status;
    }

}