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

namespace BaksDev\Wildberries\Products\UseCase\Settings\NewEdit\Property;

use BaksDev\Products\Category\Type\Section\Field\Id\ProductCategorySectionFieldUid;
use BaksDev\Wildberries\Products\Entity\Settings\Property\WbProductSettingsPropertyInterface;
use ReflectionProperty;
use Symfony\Component\Validator\Constraints as Assert;

/** @see WbProductSettingsProperty */
final class WbProductSettingsPropertyDTO implements WbProductSettingsPropertyInterface
{

    /**
     * Наименование характеристики
     */
    #[Assert\NotBlank]
    private ?string $type = null;

    /**
     * Связь на свойство продукта в категории
     */
    #[Assert\Uuid]
    private ?ProductCategorySectionFieldUid $field = null;


    /* Вспомогательные свойства */

    /**
     * Характеристика обязательна к заполнению
     */
    private readonly bool $required;

    /**
     * Единица измерения (см, гр и т.д.)
     */
    private readonly string $unit;

    /**
     * Характеристика популярна у пользователей
     */
    private readonly bool $popular;


    public function getType(): string
    {
        return $this->type;
    }


    public function setType(string $type): void
    {
        $this->type = $type;
    }


    public function getField(): ?ProductCategorySectionFieldUid
    {
        return $this->field;
    }


    public function setField(?ProductCategorySectionFieldUid $field): void
    {
        $this->field = $field;
    }


    /**
     * Характеристика обязательна к заполнению
     */
    public function isRequired(): bool
    {

        if(!(new ReflectionProperty(self::class, 'required'))->isInitialized($this))
        {
            $this->required = true;
        }

        return $this->required;
    }


    public function setRequired(bool $required): void
    {
        $this->required = $required;
    }


    /**
     * Единица измерения (см, гр и т.д.)
     */
    public function getUnit(): ?string
    {

        if(!(new ReflectionProperty(self::class, 'unit'))->isInitialized($this))
        {
            $this->unit = '';
        }

        return $this->unit;
    }


    public function setUnit(?string $unit): void
    {
        $this->unit = $unit;
    }


    /**
     * Popular
     */
    public function isPopular(): bool
    {

        if(!(new ReflectionProperty(self::class, 'popular'))->isInitialized($this))
        {
            $this->popular = false;
        }

        return $this->popular;
    }


    public function setPopular(bool $popular): void
    {
        $this->popular = $popular;
    }

}