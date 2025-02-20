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

namespace BaksDev\Wildberries\Package\Messenger\Orders\Confirm;


use BaksDev\Orders\Order\Type\Id\OrderUid;
use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;

final class ConfirmOrderWildberriesMessage
{
    private string $profile;

    private string $identifier;

    private string $supply;

    private string $order;

    public function __construct(
        UserProfileUid|string $profile,
        OrderUid|string $identifier,
        string $supply,
        string $order
    )
    {
        $this->identifier = (string) $identifier;
        $this->profile = (string) $profile;
        $this->supply = $supply;
        $this->order = $order;
    }

    /**
     * Идентификатор системного заказа
     */
    public function getIdentifier(): OrderUid
    {
        return new OrderUid($this->identifier);
    }


    /**
     * Profile
     */
    public function getProfile(): UserProfileUid
    {
        return new UserProfileUid($this->profile);
    }

    /**
     * Supply
     */
    public function getSupply(): string
    {
        return $this->supply;
    }

    /**
     * Order
     */
    public function getOrder(): string
    {
        return $this->order;
    }
}
