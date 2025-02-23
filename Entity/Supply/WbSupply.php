<?php
/*
 *  Copyright 2025.  Baks.dev <admin@baks.dev>
 *  
 *  Permission is hereby granted, free of charge, to any person obtaining a copy
 *  of this software and associated documentation files (the "Software"), to deal
 *  in the Software without restriction, including without limitation the rights
 *  to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 *  copies of the Software, and to permit persons to whom the Software is furnished
 *  to do so, subject to the following conditions:
 *  
 *  The above copyright notice and this permission notice shall be included in all
 *  copies or substantial portions of the Software.
 *  
 *  THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 *  IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 *  FITNESS FOR A PARTICULAR PURPOSE AND NON INFRINGEMENT. IN NO EVENT SHALL THE
 *  AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 *  LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 *  OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 *  THE SOFTWARE.
 */

namespace BaksDev\Wildberries\Package\Entity\Supply;

use BaksDev\Core\Entity\EntityEvent;
use BaksDev\Wildberries\Package\Entity\Supply\Event\WbSupplyEvent;
use BaksDev\Wildberries\Package\Type\Supply\Event\WbSupplyEventUid;
use BaksDev\Wildberries\Package\Type\Supply\Id\WbSupplyUid;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/* Supply */

#[ORM\Entity]
#[ORM\Table(name: 'wb_supply')]
class WbSupply
{
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

    public function __toString(): string
    {
        return (string) $this->id;
    }

    public function getId(): WbSupplyUid
    {
        return $this->id;
    }

    public function getEvent(): WbSupplyEventUid
    {
        return $this->event;
    }

    public function setEvent(WbSupplyEventUid|WbSupplyEvent $event): void
    {
        $this->event = $event instanceof WbSupplyEvent ? $event->getId() : $event;
    }
}