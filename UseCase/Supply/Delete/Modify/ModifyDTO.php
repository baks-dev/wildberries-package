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

namespace BaksDev\Wildberries\Package\UseCase\Supply\Delete\Modify;


use BaksDev\Core\Type\Modify\Modify\ModifyActionDelete;
use BaksDev\Core\Type\Modify\ModifyAction;
use BaksDev\Core\Type\Modify\Modify\ModifyActionNew;
use BaksDev\Core\Type\Modify\Modify\ModifyActionUpdate;
use BaksDev\Wildberries\Package\Entity\Supply\Modify\WbSupplyModify;
use BaksDev\Wildberries\Package\Entity\Supply\Modify\WbSupplyModifyInterface;
use BaksDev\Wildberries\Products\Entity\Barcode\Modify\WbBarcodeModifyInterface;
use Symfony\Component\Validator\Constraints as Assert;

/** @see WbSupplyModify */
final class ModifyDTO implements WbSupplyModifyInterface
{
    /**
     * Модификатор
     */
    #[Assert\NotBlank]
    private readonly ModifyAction $action;

    public function __construct()
    {
        $this->action = new ModifyAction(ModifyActionDelete::class);
    }

    public function getAction(): ModifyAction
    {
        return $this->action;
    }
}

