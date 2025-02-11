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

declare(strict_types=1);

namespace BaksDev\Wildberries\Package\Entity\Package\Supply;

use BaksDev\Core\Entity\EntityReadonly;
use BaksDev\Wildberries\Package\Entity\Package\Event\WbPackageEvent;
use BaksDev\Wildberries\Package\Type\Package\Id\WbPackageUid;
use BaksDev\Wildberries\Package\Type\Supply\Id\WbSupplyUid;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(name: 'wb_package_supply')]
#[ORM\Index(columns: ['supply', 'print'])]
class WbPackageSupply extends EntityReadonly
{
    /**
     * Идентификатор WbSupply
     */
    #[Assert\NotBlank]
    #[Assert\Uuid]
    #[ORM\Id]
    #[ORM\Column(type: WbPackageUid::TYPE)]
    private WbPackageUid $main;

    /**
     * Идентификатор события
     */
    #[Assert\NotBlank]
    #[Assert\Uuid]
    #[ORM\OneToOne(targetEntity: WbPackageEvent::class, inversedBy: 'supply')]
    #[ORM\JoinColumn(name: 'event', referencedColumnName: 'id')]
    private WbPackageEvent $event;

    /**
     * Идентификатор Поставки
     */
    #[Assert\NotBlank]
    #[Assert\Uuid]
    #[ORM\Column(type: WbSupplyUid::TYPE)]
    private WbSupplyUid $supply;

    /**
     * Статус печати стикеров упаковки
     */
    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    private bool $print = false;


    public function __construct(WbPackageEvent $event)
    {
        $this->event = $event;
        $this->main = $event->getMain();
    }


    public function getSupply(): WbSupplyUid
    {
        return $this->supply;
    }


    public function __toString(): string
    {
        return (string) $this->main;
    }

    public function getDto($dto): mixed
    {
        $dto = is_string($dto) && class_exists($dto) ? new $dto() : $dto;

        if($dto instanceof WbPackageSupplyInterface)
        {
            return parent::getDto($dto);
        }

        throw new InvalidArgumentException(sprintf('Class %s interface error', $dto::class));
    }

    public function setEntity($dto): mixed
    {
        if($dto instanceof WbPackageSupplyInterface || $dto instanceof self)
        {
            return parent::setEntity($dto);
        }

        throw new InvalidArgumentException(sprintf('Class %s interface error', $dto::class));
    }

}