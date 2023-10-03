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

namespace BaksDev\Wildberries\Package\Entity\Package\Orders;


use BaksDev\Orders\Order\Type\Id\OrderUid;
use BaksDev\Wildberries\Package\Entity\Package\Event\WbPackageEvent;
use BaksDev\Wildberries\Package\Type\Package\Status\WbPackageStatus;
use Doctrine\ORM\Mapping as ORM;
use BaksDev\Core\Entity\EntityEvent;
use InvalidArgumentException;
use Symfony\Component\Validator\Constraints as Assert;

/* WbPackageOrder */

#[ORM\Entity]
#[ORM\Table(name: 'wb_package_order')]
#[ORM\Index(columns: ['status'])]
class WbPackageOrder extends EntityEvent
{
    public const TABLE = 'wb_package_order';

    /**
     * Связь на событие
     */
    #[Assert\NotBlank]
    #[Assert\Uuid]
    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: WbPackageEvent::class, inversedBy: "ord")]
    #[ORM\JoinColumn(name: 'event', referencedColumnName: "id", nullable: false)]
    private WbPackageEvent $event;

    /**
     * ID заказа
     */
    #[Assert\NotBlank]
    #[Assert\Uuid]
    #[ORM\Id]
    #[ORM\Column(type: OrderUid::TYPE)]
    private OrderUid $ord;

    /**
     * Статус отправки заказа
     */
    #[Assert\NotBlank]
    #[ORM\Column(type: WbPackageStatus::TYPE)]
    private WbPackageStatus $status;


    public function __construct(WbPackageEvent $event)
    {
        $this->event = $event;
    }

    public function getDto($dto): mixed
    {
        if($dto instanceof WbPackageOrderInterfaface)
        {
            return parent::getDto($dto);
        }

        throw new InvalidArgumentException(sprintf('Class %s interface error', $dto::class));
    }


    public function setEntity($dto): mixed
    {
        if($dto instanceof WbPackageOrderInterfaface)
        {
            return parent::setEntity($dto);
        }

        throw new InvalidArgumentException(sprintf('Class %s interface error', $dto::class));
    }

}