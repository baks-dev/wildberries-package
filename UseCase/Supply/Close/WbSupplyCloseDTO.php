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

namespace BaksDev\Wildberries\Package\UseCase\Supply\Close;

use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;
use BaksDev\Wildberries\Package\Entity\Supply\Event\WbSupplyEventInterface;
use BaksDev\Wildberries\Package\Type\Supply\Event\WbSupplyEventUid;
use BaksDev\Wildberries\Package\Type\Supply\Status\WbSupplyStatus;
use BaksDev\Wildberries\Package\Type\Supply\Status\WbSupplyStatus\WbSupplyStatusClose;
use Symfony\Component\Validator\Constraints as Assert;

/** @see WbSupplyEvent */
final class WbSupplyCloseDTO implements WbSupplyEventInterface
{
    /**
     * Идентификатор события
     */
    #[Assert\Uuid]
    #[Assert\NotBlank]
    private readonly WbSupplyEventUid $id;

    /**
     * Статус поставки
     */
    #[Assert\NotBlank]
    private readonly WbSupplyStatus $status;

    /**
     * Константа
     */
    #[Assert\Valid]
    private Invariable\WbSupplyInvariableDTO $invariable;

    public function __construct()
    {
        $this->status = new WbSupplyStatus(WbSupplyStatusClose::class);
        $this->invariable = new Invariable\WbSupplyInvariableDTO();
    }

    /**
     * Идентификатор события
     */
    public function getEvent(): WbSupplyEventUid
    {
        return $this->id;
    }


    /**
     * Status
     */
    public function getStatus(): WbSupplyStatus
    {
        return $this->status;
    }

    /**
     * Profile
     */
    public function getProfile(): UserProfileUid
    {
        return $this->invariable->getProfile();
    }

}