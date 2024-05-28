<?php
/*
 *  Copyright 2022.  Baks.dev <admin@baks.dev>
 *
 *  Licensed under the Apache License, Version 2.0 (the "License");
 *  you may not use this file except in compliance with the License.
 *  You may obtain a copy of the License at
 *
 *  http://www.apache.org/licenses/LICENSE-2.0
 *
 *  Unless required by applicable law or agreed to in writing, software
 *  distributed under the License is distributed on an "AS IS" BASIS,
 *  WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 *  See the License for the specific language governing permissions and
 *   limitations under the License.
 *
 */

namespace BaksDev\Wildberries\Package\Entity\Supply\Wildberries;

use BaksDev\Core\Entity\EntityReadonly;
use BaksDev\Wildberries\Package\Entity\Supply\Event\WbSupplyEvent;
use BaksDev\Wildberries\Package\Type\Supply\Id\WbSupplyUid;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;
use Symfony\Component\Validator\Constraints as Assert;

/* WbSupply */

#[ORM\Entity]
#[ORM\Table(name: 'wb_supply_wildberries')]
class WbSupplyWildberries extends EntityReadonly
{
    public const TABLE = 'wb_supply_wildberries';

    /**
     * Идентификатор WbSupply
     */
    #[Assert\NotBlank]
    #[Assert\Uuid]
    #[ORM\Id]
    #[ORM\Column(type: WbSupplyUid::TYPE)]
    private WbSupplyUid $main;

    /**
     * ID события
     */
    #[Assert\NotBlank]
    #[Assert\Uuid]
    #[ORM\OneToOne(inversedBy: 'wildberries', targetEntity: WbSupplyEvent::class)]
    #[ORM\JoinColumn(name: 'event', referencedColumnName: 'id')]
    private WbSupplyEvent $event;


    /**
     * Идентификатор поставки @example WB-GI-1234567
     */
    #[Assert\NotBlank]
    #[ORM\Column(type: Types::STRING, length: 15)]
    private string $identifier;

    /**
     * Стикер SVG (Штрихкод) поставки
     */
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $sticker = null;

    public function __construct(WbSupplyEvent $event)
    {
        $this->event = $event;
        $this->main = $event->getMain();
        //$this->id = new WbSupplyEventUid();
    }

    public function __toString(): string
    {
        return (string) $this->main;
    }

    public function getDto($dto): mixed
    {
        $dto = is_string($dto) && class_exists($dto) ? new $dto() : $dto;

        if($dto instanceof WbSupplyWildberriesInterface)
        {
            return parent::getDto($dto);
        }

        throw new InvalidArgumentException(sprintf('Class %s interface error', $dto::class));
    }


    public function setEntity($dto): mixed
    {
        if($dto instanceof WbSupplyWildberriesInterface || $dto instanceof self)
        {
            return parent::setEntity($dto);
        }

        throw new InvalidArgumentException(sprintf('Class %s interface error', $dto::class));
    }

    /**
     * Identifier
     */
    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    /**
     * Sticker
     */
    public function getSticker(): string
    {
        return $this->sticker;
    }

}