<?php
/*
 *  Copyright 2023.  Baks.dev <admin@baks.dev>
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

declare(strict_types=1);

namespace BaksDev\Wildberries\Package\Entity\Supply\Event;

use BaksDev\Core\Type\Locale\Locale;
use BaksDev\Core\Type\Modify\ModifyAction;
use BaksDev\Core\Type\Modify\ModifyActionEnum;
use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;
use BaksDev\Wildberries\Package\Entity\Supply\Const\WbSupplyConst;
use BaksDev\Wildberries\Package\Entity\Supply\Modify\WbSupplyModify;
use BaksDev\Wildberries\Package\Entity\Supply\WbSupply;
use BaksDev\Wildberries\Package\Type\Supply\Event\WbSupplyEventUid;
use BaksDev\Wildberries\Package\Type\Supply\Id\WbSupplyUid;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\DBAL\Types\Types;
use BaksDev\Core\Entity\EntityEvent;
use BaksDev\Core\Entity\EntityState;
use InvalidArgumentException;
use Symfony\Component\Validator\Constraints as Assert;


/* WbSupplyEvent */

#[ORM\Entity]
#[ORM\Table(name: 'wb_supply_event')]
#[ORM\Index(columns: ['profile', 'status'])]
class WbSupplyEvent extends EntityEvent
{
    public const TABLE = 'wb_supply_event';

    /**
     * Идентификатор События
     */
    #[Assert\NotBlank]
    #[Assert\Uuid]
    #[ORM\Id]
    #[ORM\Column(type: WbSupplyEventUid::TYPE)]
    private WbSupplyEventUid $id;

    /**
     * Идентификатор WbSupply
     */
    #[Assert\NotBlank]
    #[Assert\Uuid]
    #[ORM\Column(type: WbSupplyUid::TYPE, nullable: false)]
    private ?WbSupplyUid $main = null;

    /**
     * Модификатор
     */
    #[ORM\OneToOne(mappedBy: 'event', targetEntity: WbSupplyModify::class, cascade: ['all'])]
    private WbSupplyModify $modify;


    /**
     * Профиль пользователя (владелец)
     */
    #[ORM\Column(type: UserProfileUid::TYPE)]
    private UserProfileUid $profile;


    /**
     * Статус поставки
     */
    #[ORM\Column(type: WbSupplyStatus::TYPE)]
    private WbSupplyStatus $status;


    /**
     * Константа
     */
    #[ORM\OneToOne(mappedBy: 'event', targetEntity: WbSupplyConst::class, cascade: ['all'])]
    private WbSupplyConst $const;


    public function __construct()
    {
        $this->id = new WbSupplyEventUid();
        $this->modify = new WbSupplyModify($this);
    }

    /**
     * Идентификатор События
     */

    public function __clone()
    {
        $this->id = new WbSupplyEventUid();
    }

    public function __toString(): string
    {
        return (string) $this->id;
    }

    public function getId(): WbSupplyEventUid
    {
        return $this->id;
    }

    /**
     * Идентификатор WbSupply
     */
    public function setMain(WbSupplyUid|WbSupply $main): void
    {
        $this->main = $main instanceof WbSupply ? $main->getId() : $main;
    }


    public function getMain(): ?WbSupplyUid
    {
        return $this->main;
    }

    public function getDto($dto): mixed
    {
        if($dto instanceof WbSupplyEventInterface)
        {
            return parent::getDto($dto);
        }

        throw new InvalidArgumentException(sprintf('Class %s interface error', $dto::class));
    }

    public function setEntity($dto): mixed
    {
        if($dto instanceof WbSupplyEventInterface)
        {
            return parent::setEntity($dto);
        }

        throw new InvalidArgumentException(sprintf('Class %s interface error', $dto::class));
    }


    //	public function isModifyActionEquals(ModifyActionEnum $action) : bool
    //	{
    //		return $this->modify->equals($action);
    //	}

    //	public function getUploadClass() : WbSupplyImage
    //	{
    //		return $this->image ?: $this->image = new WbSupplyImage($this);
    //	}

    //	public function getNameByLocale(Locale $locale) : ?string
    //	{
    //		$name = null;
    //		
    //		/** @var WbSupplyTrans $trans */
    //		foreach($this->translate as $trans)
    //		{
    //			if($name = $trans->name($locale))
    //			{
    //				break;
    //			}
    //		}
    //		
    //		return $name;
    //	}
}