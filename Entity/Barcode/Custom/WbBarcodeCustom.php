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

namespace BaksDev\Wildberries\Products\Entity\Barcode\Custom;

use BaksDev\Core\Entity\EntityEvent;
use BaksDev\Wildberries\Products\Entity\Barcode\Event\WbBarcodeEvent;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(name: 'wb_barcode_custom')]
class WbBarcodeCustom extends EntityEvent
{
    const TABLE = 'wb_barcode_custom';
    
    /** Связь на событие */
    #[Assert\NotBlank]
    #[Assert\Uuid]
    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: WbBarcodeEvent::class,  inversedBy: "custom")]
    #[ORM\JoinColumn(name: 'event', referencedColumnName: "id", nullable: false)]
    private WbBarcodeEvent $event;
    
    /** Сортировка */
    #[Assert\NotBlank]
    #[Assert\Range(min: 1, max: 999)]
    #[ORM\Column(type: Types::SMALLINT)]
    private int $sort = 100;
    
    /** Название поля */
    #[Assert\NotBlank]
    #[Assert\Length(max: 32)]
    #[ORM\Column(type: Types::STRING)]
    private string $name;
    
    /** Значение */
    #[Assert\NotBlank]
    #[Assert\Length(max: 100)]
    #[ORM\Column(type: Types::STRING)]
    private string $value;
    

    public function __construct(WbBarcodeEvent $event)
    {
        $this->event = $event;
    }

    public function __toString(): string
    {
        return (string) $this->event;
    }


    public function getDto($dto): mixed
    {
        $dto = is_string($dto) && class_exists($dto) ? new $dto() : $dto;

        if($dto instanceof WbBarcodeCustomInterface)
        {
            return parent::getDto($dto);
        }
        
        throw new InvalidArgumentException(sprintf('Class %s interface error', $dto::class));
    }
    

    public function setEntity($dto): mixed
    {
        if($dto instanceof WbBarcodeCustomInterface || $dto instanceof self)
        {
            return parent::setEntity($dto);
        }
        
        throw new InvalidArgumentException(sprintf('Class %s interface error', $dto::class));
    }
    
}


