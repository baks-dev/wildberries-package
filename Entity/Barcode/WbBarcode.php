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

namespace BaksDev\Wildberries\Products\Entity\Barcode;

use BaksDev\Core\Entity\EntityState;
use BaksDev\Products\Category\Type\Id\ProductCategoryUid;
use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;
use BaksDev\Wildberries\Products\Entity\Barcode\Event\WbBarcodeEvent;
use BaksDev\Wildberries\Products\Type\Barcode\Event\WbBarcodeEventUid;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(name: 'wb_barcode')]
class WbBarcode extends EntityState
{
    const TABLE = 'wb_barcode';

    /** ID */
    #[Assert\NotBlank]
    #[Assert\Uuid]
    #[ORM\Id]
    #[ORM\Column(type: ProductCategoryUid::TYPE)]
    private ProductCategoryUid $id;


    /** ID профиля */
    #[Assert\NotBlank]
    #[Assert\Uuid]
    #[ORM\Id]
    #[ORM\Column(type: UserProfileUid::TYPE)]
    private UserProfileUid $profile;


    /** ID События */
    #[Assert\NotBlank]
    #[Assert\Uuid]
    #[ORM\Column(type: WbBarcodeEventUid::TYPE, unique: true)]
    private WbBarcodeEventUid $event;


    public function __construct(UserProfileUid $profile)
    {
        $this->profile = $profile;
    }


    public function __toString(): string
    {
        return (string) $this->id;
    }

    public function getId(): ProductCategoryUid
    {
        return $this->id;
    }

    /**
     * Profile
     */
    public function getProfile(): UserProfileUid
    {
        return $this->profile;
    }


    public function getEvent(): WbBarcodeEventUid
    {
        return $this->event;
    }

    public function setEvent(WbBarcodeEvent $event): void
    {
        $this->event = $event->getId();
        $this->id = $event->getCategory();
    }

}