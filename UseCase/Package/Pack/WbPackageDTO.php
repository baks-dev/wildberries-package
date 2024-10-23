<?php
/*
 *  Copyright 2024.  Baks.dev <admin@baks.dev>
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

namespace BaksDev\Wildberries\Package\UseCase\Package\Pack;

use BaksDev\Manufacture\Part\Entity\Products\ManufacturePartProductInterface;
use BaksDev\Products\Product\Type\Event\ProductEventUid;
use BaksDev\Products\Product\Type\Offers\Id\ProductOfferUid;
use BaksDev\Products\Product\Type\Offers\Variation\Id\ProductVariationUid;
use BaksDev\Products\Product\Type\Offers\Variation\Modification\Id\ProductModificationUid;
use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;
use BaksDev\Wildberries\Package\Entity\Package\Event\WbPackageEventInterface;
use BaksDev\Wildberries\Package\Entity\Supply\WbSupply;
use BaksDev\Wildberries\Package\Type\Package\Event\WbPackageEventUid;
use BaksDev\Wildberries\Package\Type\Supply\Id\WbSupplyUid;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\Types\Types;
use Symfony\Component\Validator\Constraints as Assert;

/** @see WbPackageEvent */
final class WbPackageDTO implements WbPackageEventInterface
{
    /**
     * Идентификатор События
     */
    #[Assert\Uuid]
    #[Assert\IsNull]
    private ?WbPackageEventUid $id = null;


    /**
     * Заказы, добавленные в поставку
     */
    #[Assert\Valid]
    private ArrayCollection $ord;


    /**
     * Общее количество заказов в паковке
     */
    #[Assert\NotBlank]
    #[Assert\Range(min: 1)]
    private int $total = 0;

    /**
     * Поставка
     */
    #[Assert\Valid]
    private Supply\WbPackageSupplyDTO $supply;

    #[Assert\NotBlank]
    #[Assert\Uuid]
    private readonly UserProfileUid $profile;


    public function __construct(UserProfileUid $profile)
    {
        $this->ord = new ArrayCollection();
        $this->supply = new Supply\WbPackageSupplyDTO();
        $this->profile = $profile;
    }

    public function getEvent(): ?WbPackageEventUid
    {
        return $this->id;
    }

    /**
     * Ord
     */
    public function getOrd(): ArrayCollection
    {
        return $this->ord;
    }

    public function setOrd(ArrayCollection $ord): self
    {
        $this->ord = $ord;
        return $this;
    }

    public function addOrd(Orders\WbPackageOrderDTO $ord): self
    {
        $filter = $this->ord->filter(function(Orders\WbPackageOrderDTO $element) use ($ord) {
            return $ord->getId()->equals($element->getId());
        });

        if($filter->isEmpty())
        {
            $this->ord->add($ord);
        }

        $this->total = $this->ord->count();

        return $this;
    }

    public function removeOrd($ord): self
    {
        $this->ord->removeElement($ord);
        return $this;
    }

    /**
     * Supply
     */
    public function getSupply(): Supply\WbPackageSupplyDTO
    {
        return $this->supply;
    }

    public function setPackageSupply(WbSupply|WbSupplyUid $supply): void
    {
        $supply = $supply instanceof WbSupply ? $supply->getId() : $supply;
        $this->supply->setSupply($supply);
    }

    /**
     * Total
     */
    public function getTotal(): int
    {
        return $this->total;
    }

    /**
     * Profile
     */
    public function getProfile(): UserProfileUid
    {
        return $this->profile;
    }

}