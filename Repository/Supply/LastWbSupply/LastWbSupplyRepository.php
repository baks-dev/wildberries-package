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

namespace BaksDev\Wildberries\Package\Repository\Supply\LastWbSupply;

use BaksDev\Core\Doctrine\DBALQueryBuilder;
use BaksDev\Users\Profile\UserProfile\Entity\UserProfile;
use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;
use BaksDev\Wildberries\Package\Entity\Supply\Event\WbSupplyEvent;
use BaksDev\Wildberries\Package\Entity\Supply\Invariable\WbSupplyInvariable;
use BaksDev\Wildberries\Package\Entity\Supply\Modify\WbSupplyModify;
use BaksDev\Wildberries\Package\Entity\Supply\WbSupply;
use BaksDev\Wildberries\Package\Entity\Supply\Wildberries\WbSupplyWildberries;
use InvalidArgumentException;


final class LastWbSupplyRepository implements LastWbSupplyInterface
{
    private UserProfileUid|false $profile;

    public function __construct(private readonly DBALQueryBuilder $DBALQueryBuilder) {}


    public function forProfile(UserProfile|UserProfileUid|string $profile): self
    {
        if(is_string($profile))
        {
            $profile = new UserProfileUid($profile);
        }

        if($profile instanceof UserProfile)
        {
            $profile = $profile->getId();
        }

        $this->profile = $profile;

        return $this;
    }

    /**
     * Получаем ПОСЛЕДНЮЮ поставку профиля пользователя с любым статусом
     */
    public function find(): array|false
    {
        if(false === $this->profile)
        {
            throw new InvalidArgumentException('Invalid Argument Profile');
        }

        $qb = $this->DBALQueryBuilder->createQueryBuilder(self::class);

        $qb
            ->addSelect('invariable.total')
            ->from(WbSupplyInvariable::class, 'invariable')
            ->where('invariable.profile = :profile')
            ->setParameter(
                'profile',
                $this->profile,
                UserProfileUid::TYPE
            );

        $qb
            ->addSelect('supply.id')
            ->addSelect('supply.event')
            ->join(
                'invariable',
                WbSupply::class,
                'supply',
                'supply.id = invariable.main'
            );


        $qb
            ->addSelect('event.status')
            ->leftJoin(
                'supply',
                WbSupplyEvent::class,
                'event',
                'event.id = supply.event'
            );

        $qb
            ->addSelect('modify.mod_date AS supply_date')
            ->leftJoin(
                'supply',
                WbSupplyModify::class,
                'modify',
                'modify.event = supply.event'
            );


        $qb
            ->addSelect('wb.identifier')
            ->addSelect('wb.sticker')
            ->leftJoin(
                'supply',
                WbSupplyWildberries::class,
                'wb',
                'wb.main = supply.id'
            );


        $qb->orderBy('modify.mod_date', 'DESC');
        $qb->setMaxResults(1);

        return $qb
            ->enableCache('wildberries-package', 3600)
            ->fetchAssociative();
    }
}