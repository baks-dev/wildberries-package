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

namespace BaksDev\Wildberries\Products\UseCase\Barcode\NewEdit\Custom;

use BaksDev\Wildberries\Products\Entity\Barcode\Custom\WbBarcodeCustomInterface;
use Symfony\Component\Validator\Constraints as Assert;

/** @see WbBarcodeCustom */
final class WbBarcodeCustomDTO implements WbBarcodeCustomInterface
{
    
    /**
     * Сортировка
     */
    #[Assert\NotBlank]
    #[Assert\Range(min: 1, max: 999)]
    private int $sort = 100;
    
    /**
     * Название поля
     */
    #[Assert\NotBlank]
    private string $name;
    
    /**
     * Значение
     */
    #[Assert\NotBlank]
    private string $value;
    

    public function getSort() : int
    {
        return $this->sort;
    }
    

    public function setSort(int $sort) : void
    {
        $this->sort = $sort;
    }
    

    public function getName(): string
    {
        return $this->name;
    }
    

    public function setName(string $name) : void
    {
        $this->name = $name;
    }
    

    public function getValue(): string
    {
        return $this->value;
    }
    

    public function setValue(string $value) : void
    {
        $this->value = $value;
    }
}

