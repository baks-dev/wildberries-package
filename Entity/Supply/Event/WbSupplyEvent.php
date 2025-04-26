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

namespace BaksDev\Wildberries\Package\Entity\Supply\Event;

use BaksDev\Core\Entity\EntityEvent;
use BaksDev\Wildberries\Package\Entity\Supply\Invariable\WbSupplyInvariable;
use BaksDev\Wildberries\Package\Entity\Supply\Modify\WbSupplyModify;
use BaksDev\Wildberries\Package\Entity\Supply\WbSupply;
use BaksDev\Wildberries\Package\Entity\Supply\Wildberries\WbSupplyWildberries;
use BaksDev\Wildberries\Package\Type\Supply\Event\WbSupplyEventUid;
use BaksDev\Wildberries\Package\Type\Supply\Id\WbSupplyUid;
use BaksDev\Wildberries\Package\Type\Supply\Status\WbSupplyStatus;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;
use Symfony\Component\Validator\Constraints as Assert;


/* WbSupplyEvent */

#[ORM\Entity]
#[ORM\Table(name: 'wb_supply_event')]
#[ORM\Index(columns: ['status'])]
class WbSupplyEvent extends EntityEvent
{
    /**
     * Идентификатор События
     */
    #[Assert\NotBlank]
    #[Assert\Uuid]
    #[ORM\Id]
    #[ORM\Column(type: WbSupplyEventUid::TYPE)]
    private WbSupplyEventUid $id;

    /**
     * Идентификатор WbSupply
     */
    #[Assert\NotBlank]
    #[Assert\Uuid]
    #[ORM\Column(type: WbSupplyUid::TYPE, nullable: false)]
    private ?WbSupplyUid $main = null;

    /**
     * Модификатор
     */
    #[ORM\OneToOne(targetEntity: WbSupplyModify::class, mappedBy: 'event', cascade: ['all'], fetch: 'EAGER')]
    private WbSupplyModify $modify;

    /**
     * Статус поставки
     */
    #[Assert\NotBlank]
    #[ORM\Column(type: WbSupplyStatus::TYPE)]
    private WbSupplyStatus $status;

    /**
     * Константы сущности
     */
    #[Assert\Valid]
    #[ORM\OneToOne(targetEntity: WbSupplyInvariable::class, mappedBy: 'event', cascade: ['all'], fetch: 'EAGER')]
    private ?WbSupplyInvariable $invariable = null;


    /**
     * Поставка Wildberries
     */
    #[Assert\Valid]
    #[ORM\OneToOne(targetEntity: WbSupplyWildberries::class, mappedBy: 'event', cascade: ['all'], fetch: 'EAGER')]
    private ?WbSupplyWildberries $wildberries = null;


    public function __construct()
    {
        $this->id = new WbSupplyEventUid();
        $this->modify = new WbSupplyModify($this);
    }

    public function __clone()
    {
        $this->id = clone $this->id;
    }

    public function __toString(): string
    {
        return (string) $this->id;
    }

    public function getDto($dto): mixed
    {
        $dto = is_string($dto) && class_exists($dto) ? new $dto() : $dto;

        if($dto instanceof WbSupplyEventInterface)
        {
            return parent::getDto($dto);
        }

        throw new InvalidArgumentException(sprintf('Class %s interface error', $dto::class));
    }

    public function setEntity($dto): mixed
    {
        if($dto instanceof WbSupplyEventInterface || $dto instanceof self)
        {
            return parent::setEntity($dto);
        }

        throw new InvalidArgumentException(sprintf('Class %s interface error', $dto::class));
    }

    public function getId(): WbSupplyEventUid
    {
        return $this->id;
    }

    /**
     * Идентификатор WbSupply
     */
    public function setMain(WbSupplyUid|WbSupply $main): void
    {
        $this->main = $main instanceof WbSupply ? $main->getId() : $main;
    }


    public function getMain(): ?WbSupplyUid
    {
        return $this->main;
    }

    /**
     * Status
     */
    public function getStatus(): WbSupplyStatus
    {
        return $this->status;
    }

    /**
     * Wildberries
     */
    public function getIdentifier(): ?string
    {
        return $this->wildberries?->getIdentifier();
    }

    /**
     * Total
     */
    public function getTotal(): int
    {
        return $this->invariable->getTotal();
    }


}