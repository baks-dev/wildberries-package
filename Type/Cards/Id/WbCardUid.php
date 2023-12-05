<?php
/*
*  Copyright Baks.dev <admin@baks.dev>
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

namespace BaksDev\Wildberries\Products\Type\Cards\Id;

use App\Kernel;
use BaksDev\Core\Type\UidType\Uid;
use Symfony\Component\Uid\AbstractUid;

final class WbCardUid extends Uid
{

    public const TEST = '018ad839-da78-7527-8627-82a7dde7631c';

    public const TYPE = 'wb_card';

    private mixed $attr;

    private mixed $option;

    private mixed $property;

    private mixed $characteristic;


    public function __construct(
        AbstractUid|string|null $value = null,
        mixed $attr = null,
        mixed $option = null,
        mixed $property = null,
        mixed $characteristic = null,
    )
    {
        parent::__construct($value);

        $this->attr = $attr;
        $this->option = $option;
        $this->property = $property;
        $this->characteristic = $characteristic;
    }


    public function getAttr(): mixed
    {
        return $this->attr;
    }


    public function getOption(): mixed
    {
        return $this->option;
    }


    public function getProperty(): mixed
    {
        return $this->property;
    }

    public function getCharacteristic(): mixed
    {
        return $this->characteristic;
    }
}