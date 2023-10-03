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

namespace BaksDev\Wildberries\Package\Entity\Package;

use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;
use BaksDev\Wildberries\Package\Entity\Package\Event\WbPackageEvent;
use BaksDev\Wildberries\Package\Type\Package\Event\PackageEventUid;
use BaksDev\Wildberries\Package\Type\Package\Event\WbPackageEventUid;
use BaksDev\Wildberries\Package\Type\Package\Id\PackageUid;
use BaksDev\Wildberries\Package\Type\Package\Id\WbPackageUid;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/* Упаковка заказов */

#[ORM\Entity]
#[ORM\Table(name: 'wb_package')]
class WbPackage
{
    public const TABLE = 'wb_package';

    /**
     * Идентификатор упаковки
     */
    #[Assert\NotBlank]
    #[Assert\Uuid]
    #[ORM\Id]
    #[ORM\Column(type: WbPackageUid::TYPE)]
    private WbPackageUid $id;

    /**
     * Идентификатор События
     */
    #[Assert\NotBlank]
    #[Assert\Uuid]
    #[ORM\Column(type: WbPackageEventUid::TYPE, unique: true)]
    private WbPackageEventUid $event;


    public function __construct(WbPackageUid $id = null)
    {
        $this->id = $id ?: new WbPackageUid();
    }

    public function __toString(): string
    {
        return (string) $this->id;
    }


    public function getId(): WbPackageUid
    {
        return $this->id;
    }

    public function getEvent(): WbPackageEventUid
    {
        return $this->event;
    }

    public function setEvent(WbPackageEventUid|WbPackageEvent $event): void
    {
        $this->event = $event instanceof WbPackageEvent ? $event->getId() : $event;
    }
}