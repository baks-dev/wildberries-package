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

namespace BaksDev\Wildberries\Package\Entity\Supply;

use BaksDev\Wildberries\Package\Entity\Supply\Event\WbSupplyEvent;
use BaksDev\Wildberries\Package\Type\Supply\Event\WbSupplyEventUid;
use BaksDev\Wildberries\Package\Type\Supply\Id\WbSupplyUid;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\DBAL\Types\Types;
use BaksDev\Core\Entity\EntityEvent;
use Exception;
use InvalidArgumentException;

/* Supply */

#[ORM\Entity]
#[ORM\Table(name: 'wb_supply')]
// #[ORM\Index(columns: ['column'])]
class WbSupply
{
    public const TABLE = 'wb_supply';
    
    /** ID */
    #[ORM\Id]
    #[ORM\Column(type: WbSupplyUid::TYPE)]
    private WbSupplyUid $id;
    
    /** ID События */
    #[ORM\Column(type: WbSupplyEventUid::TYPE, unique: true)]
    private WbSupplyEventUid $event;

    
    public function __construct()
    {
        $this->id = new WbSupplyUid();
    }

    public function getId() : WbSupplyUid
    {
        return $this->id;
    }

    public function getEvent() : WbSupplyEventUid
    {
        return $this->event;
    }

    public function setEvent(WbSupplyEventUid|WbSupplyEvent $event) : void
    {
        $this->event = $event instanceof WbSupplyEvent ? $event->getId() : $event;
    }
}