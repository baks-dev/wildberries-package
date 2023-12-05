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

namespace BaksDev\Wildberries\Products\Entity\Settings\Property;


use BaksDev\Core\Entity\EntityEvent;
use BaksDev\Products\Category\Type\Section\Field\Id\ProductCategorySectionFieldUid;
use BaksDev\Wildberries\Products\Entity\Settings\Event\WbProductSettingsEvent;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;
use Symfony\Component\Validator\Constraints as Assert;

/* Property */

#[ORM\Entity]
#[ORM\Table(name: 'wb_card_settings_property')]
class WbProductSettingsProperty extends EntityEvent
{
    const TABLE = 'wb_card_settings_property';
    
    /**
     * Идентификатор события
     */
    #[Assert\NotBlank]
    #[Assert\Uuid]
    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: WbProductSettingsEvent::class, inversedBy: 'property')]
    #[ORM\JoinColumn(name: 'event', referencedColumnName: 'id')]
    private WbProductSettingsEvent $event;
    
    /**
     * Наименование характеристики
     */
    #[Assert\NotBlank]
    #[ORM\Id]
    #[ORM\Column(type: Types::STRING)]
    private string $type;
    
    /**
     * Связь на свойство продукта в категории
     */
    #[Assert\NotBlank]
    #[Assert\Uuid]
    #[ORM\Column(type: ProductCategorySectionFieldUid::TYPE)]
    private ProductCategorySectionFieldUid $field;
    

    public function __construct(WbProductSettingsEvent $event) {
        $this->event = $event;
    }

    public function __toString(): string
    {
        return $this->event.' '.$this->type;
    }

    public function getDto($dto): mixed
    {
        $dto = is_string($dto) && class_exists($dto) ? new $dto() : $dto;
        
        if($dto instanceof WbProductSettingsPropertyInterface)
        {
            return parent::getDto($dto);
        }
        
        throw new InvalidArgumentException(sprintf('Class %s interface error', $dto::class));
    }
    

    public function setEntity($dto): mixed
    {
        if($dto instanceof WbProductSettingsPropertyInterface || $dto instanceof self)
        {
            if(empty($dto->getField())) { return false; }
            
            return parent::setEntity($dto);
        }
        
        throw new InvalidArgumentException(sprintf('Class %s interface error', $dto::class));
    }
}