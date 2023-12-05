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

namespace BaksDev\Wildberries\Products\Entity\Settings\Event;


use BaksDev\Core\Entity\EntityEvent;
use BaksDev\Products\Category\Type\Id\ProductCategoryUid;
use BaksDev\Wildberries\Products\Entity\Settings\Modify\Modify;
use BaksDev\Wildberries\Products\Entity\Settings\Modify\WbProductSettingsModify;
use BaksDev\Wildberries\Products\Entity\Settings\Offer\WbProductSettingsOffer;
use BaksDev\Wildberries\Products\Entity\Settings\Property\WbProductSettingsProperty;
use BaksDev\Wildberries\Products\Entity\Settings\Variation\WbProductSettingsVariation;
use BaksDev\Wildberries\Products\Entity\Settings\WbProductSettings;
use BaksDev\Wildberries\Products\Type\Settings\Event\WbProductSettingsEventUid;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;
use Symfony\Component\Validator\Constraints as Assert;

/* Event */

#[ORM\Entity]
#[ORM\Table(name: 'wb_card_settings_event')]
#[ORM\Index(columns: ['settings'])]
#[ORM\Index(columns: ['name'])]
class WbProductSettingsEvent extends EntityEvent
{
    const TABLE = 'wb_card_settings_event';

    /**
     * Идентификатор события
     */
    #[Assert\NotBlank]
    #[Assert\Uuid]
    #[ORM\Id]
    #[ORM\Column(type: WbProductSettingsEventUid::TYPE)]
    private WbProductSettingsEventUid $id;

    /**
     * Идентификатор настройки
     */
    #[Assert\NotBlank]
    #[Assert\Uuid]
    #[ORM\Column(type: ProductCategoryUid::TYPE)]
    private ProductCategoryUid $settings;

    /**
     * Категория Wildberries
     */
    #[Assert\NotBlank]
    #[ORM\Column(type: Types::STRING)]
    private string $name;

    /**
     * Модификатор
     */
    #[Assert\Valid]
    #[ORM\OneToOne(mappedBy: 'event', targetEntity: WbProductSettingsModify::class, cascade: ['all'])]
    private WbProductSettingsModify $modify;

    /**
     * Свойства карточки
     */
    #[Assert\Valid]
    #[ORM\OneToMany(mappedBy: 'event', targetEntity: WbProductSettingsProperty::class, cascade: ['all'])]
    private Collection $property;

    public function __construct()
    {
        $this->id = new WbProductSettingsEventUid();
        $this->modify = new WbProductSettingsModify($this);
    }

    public function __clone(): void
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

        if($dto instanceof WbProductSettingsEventInterface)
        {
            return parent::getDto($dto);
        }

        throw new InvalidArgumentException(sprintf('Class %s interface error', $dto::class));
    }


    public function setEntity($dto): mixed
    {
        if($dto instanceof WbProductSettingsEventInterface || $dto instanceof self)
        {
            return parent::setEntity($dto);
        }

        throw new InvalidArgumentException(sprintf('Class %s interface error', $dto::class));
    }

    public function setMain(WbProductSettings $settings): self
    {
        return $this;
    }



    public function getId(): WbProductSettingsEventUid
    {
        return $this->id;
    }

    public function getSettings(): ProductCategoryUid
    {
        return $this->settings;
    }


    public function getName(): string
    {
        return $this->name;
    }

}