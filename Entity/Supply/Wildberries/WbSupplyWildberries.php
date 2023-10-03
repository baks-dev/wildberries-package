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

use BaksDev\Wildberries\Package\Entity\Supply\Event\WbSupplyEvent;
use App\Module\Wildberries\Orders\Supplys\Supply\Type\Uid\SupplyUid;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\DBAL\Types\Types;

use BaksDev\Core\Entity\EntityEvent;
use Exception;
use InvalidArgumentException;

/* WbSupply */

#[ORM\Entity]
#[ORM\Table(name: 'wb_supply_wildberries')]
// #[ORM\Index(columns: ['column'])]
class WbSupplyWildberries extends EntityEvent
{
    public const TABLE = 'wb_supply_wildberries';
    
    /** ID события */
    #[ORM\Id]
    #[ORM\OneToOne(inversedBy: 'wb', targetEntity: WbSupplyEvent::class)]
    #[ORM\JoinColumn(name: 'event_id', referencedColumnName: 'id')]
    private WbSupplyEvent $event;
    
    /** Идентификатор поставки @example WB-GI-1234567 */
    #[ORM\Column(type: Types::STRING, length: 15, nullable: true)]
    private ?string $identifier = null;
    
    /** Стикер SVG (Штрихкод) поставки */
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $sticker = null;
    
    
    /** column */
    //    #[ORM\Column(type: Types::TEXT)]
    //    private ?string $string;
    
    public function __construct(WbSupplyEvent $event)
    {
        $this->event = $event;
    }
    
    /**
     * @return string|null
     */
    public function getIdentifier() : ?string
    {
        return $this->identifier;
    }
    
    /**
     * @return string|null
     */
    public function getSticker() : ?string
    {
        return $this->sticker;
    }

    
    
    /**
     * Метод заполняет объект DTO свойствами сущности и возвращает
     * @throws Exception
     */
    public function getDto($dto) : mixed
    {
        if($dto instanceof WbSupplyWildberriesInterface)
        {
            return parent::getDto($dto);
        }
        
        throw new InvalidArgumentException(sprintf('Class %s interface error', $dto::class));
    }
    
    /**
     * Метод присваивает свойствам значения из объекта DTO
     * @throws Exception
     */
    public function setEntity($dto) : mixed
    {
        if($dto instanceof WbSupplyWildberriesInterface)
        {
            return parent::setEntity($dto);
        }
        
        throw new InvalidArgumentException(sprintf('Class %s interface error', $dto::class));
    }
    
}