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

namespace BaksDev\Wildberries\Package\Entity\Package\Event;

use BaksDev\Core\Entity\EntityEvent;
use BaksDev\Wildberries\Package\Entity\Package\Modify\WbPackageModify;
use BaksDev\Wildberries\Package\Entity\Package\Orders\WbPackageOrder;
use BaksDev\Wildberries\Package\Entity\Package\Supply\WbPackageSupply;
use BaksDev\Wildberries\Package\Entity\Package\WbPackage;
use BaksDev\Wildberries\Package\Type\Package\Event\WbPackageEventUid;
use BaksDev\Wildberries\Package\Type\Package\Id\WbPackageUid;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;
use Symfony\Component\Validator\Constraints as Assert;


/* WbPackageEvent */

#[ORM\Entity]
#[ORM\Table(name: 'wb_package_event')]
class WbPackageEvent extends EntityEvent
{
    public const TABLE = 'wb_package_event';

    /**
     * Идентификатор События
     */
    #[Assert\NotBlank]
    #[Assert\Uuid]
    #[ORM\Id]
    #[ORM\Column(type: WbPackageEventUid::TYPE)]
    private WbPackageEventUid $id;

    /**
     * Идентификатор WbPackage
     */
    #[Assert\NotBlank]
    #[Assert\Uuid]
    #[ORM\Column(type: WbPackageUid::TYPE, nullable: false)]
    private ?WbPackageUid $main = null;

    /**
     * Модификатор
     */
    #[ORM\OneToOne(mappedBy: 'event', targetEntity: WbPackageModify::class, cascade: ['all'])]
    private WbPackageModify $modify;

    /**
     * Заказы, добавленные в поставку
     */
    #[ORM\OneToMany(mappedBy: 'event', targetEntity: WbPackageOrder::class, cascade: ['all'])]
    private Collection $ord;

    /**
     * Общее количество заказов в паковке
     */
    #[ORM\Column(type: Types::INTEGER, options: ['default' => 0])]
    private int $total = 0;

    /**
     * Поставка
     */
    #[Assert\Valid]
    #[ORM\OneToOne(mappedBy: 'event', targetEntity: WbPackageSupply::class, cascade: ['all'])]
    private WbPackageSupply $supply;


    public function __construct()
    {
        $this->id = new WbPackageEventUid();
        $this->modify = new WbPackageModify($this);
    }

    public function __clone()
    {
        $this->id = new WbPackageEventUid();
    }

    public function __toString(): string
    {
        return (string) $this->id;
    }

    public function getDto($dto): mixed
    {
        $dto = is_string($dto) && class_exists($dto) ? new $dto() : $dto;

        if($dto instanceof WbPackageEventInterface)
        {
            return parent::getDto($dto);
        }

        throw new InvalidArgumentException(sprintf('Class %s interface error', $dto::class));
    }

    public function setEntity($dto): mixed
    {
        if($dto instanceof WbPackageEventInterface || $dto instanceof self)
        {
            return parent::setEntity($dto);
        }

        throw new InvalidArgumentException(sprintf('Class %s interface error', $dto::class));
    }

    public function getId(): WbPackageEventUid
    {
        return $this->id;
    }

    public function getMain(): ?WbPackageUid
    {
        return $this->main;
    }

    public function setMain(WbPackageUid|WbPackage $main): void
    {
        $this->main = $main instanceof WbPackage ? $main->getId() : $main;
    }

}