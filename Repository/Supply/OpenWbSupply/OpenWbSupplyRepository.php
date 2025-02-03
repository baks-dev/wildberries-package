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

namespace BaksDev\Wildberries\Package\Repository\Supply\OpenWbSupply;

use BaksDev\Core\Doctrine\DBALQueryBuilder;
use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;
use BaksDev\Wildberries\Package\Entity\Supply\Const\WbSupplyConst;
use BaksDev\Wildberries\Package\Entity\Supply\Event\WbSupplyEvent;
use BaksDev\Wildberries\Package\Entity\Supply\Modify\WbSupplyModify;
use BaksDev\Wildberries\Package\Entity\Supply\WbSupply;
use BaksDev\Wildberries\Package\Entity\Supply\Wildberries\WbSupplyWildberries;
use BaksDev\Wildberries\Package\Type\Supply\Id\WbSupplyUid;
use BaksDev\Wildberries\Package\Type\Supply\Status\WbSupplyStatus;

final class OpenWbSupplyRepository implements OpenWbSupplyInterface
{

    public function __construct(private readonly DBALQueryBuilder $DBALQueryBuilder) {}

    /**
     * Метод возвращает профиль пользователя и идентификатор поставки в качестве аттрибута
     */
    public function getWbSupply(WbSupplyUid $WbSupplyUid): ?UserProfileUid
    {
        $qb = $this->DBALQueryBuilder->createQueryBuilder(self::class);

        $qb
            ->addSelect('supply_const.profile')
            ->from(WbSupplyConst::class, 'supply_const')
            ->where('supply_const.main = :supply')
            ->setParameter('supply', $WbSupplyUid, WbSupplyUid::TYPE);

        $qb
            ->addSelect('supply_wildberries.identifier')
            ->leftJoin(
                'supply_const',
                WbSupplyWildberries::class,
                'supply_wildberries',
                'supply_wildberries.main = supply_const.main'
            );

        $supply = $qb->fetchAssociative();

        return $supply ? new UserProfileUid($supply['profile'], $supply['identifier']) : null;

    }


    /**
     * Получаем идентификатор ОТКРЫТОЙ поставки профиля пользователя
     */
    public function getOpenWbSupplyByProfile(UserProfileUid $profile): ?WbSupplyUid
    {
        $qb = $this->DBALQueryBuilder->createQueryBuilder(self::class);

        $qb
            ->from(WbSupplyConst::class, 'supply_const')
            ->where('supply_const.profile = :profile')
            ->setParameter('profile', $profile, UserProfileUid::TYPE);

        $qb
            ->addSelect('supply.id')
            ->join(
                'supply_const',
                WbSupply::class,
                'supply',
                'supply.id = supply_const.main'
            );

        $qb
            ->addSelect('event.status')
            ->join(
                'supply',
                WbSupplyEvent::class,
                'event',
                'event.id = supply.event AND (event.status = :new OR event.status = :open) '
            )
            ->setParameter('new', WbSupplyStatus\WbSupplyStatusNew::STATUS)
            ->setParameter('open', WbSupplyStatus\WbSupplyStatusOpen::STATUS);

        $qb->setMaxResults(1);

        $result = $qb->fetchOne();

        return $result ? new WbSupplyUid($result) : null;
    }

    /**
     * Получаем ПОСЛЕДНЮЮ поставку профиля пользователя с любым статусом
     */
    public function getLastWbSupply(UserProfileUid $profile): array|false
    {
        $qb = $this->DBALQueryBuilder->createQueryBuilder(self::class);

        $qb
            ->addSelect('supply_const.total')
            ->from(WbSupplyConst::class, 'supply_const')
            ->where('supply_const.profile = :profile')
            ->setParameter('profile', $profile, UserProfileUid::TYPE);

        $qb
            ->addSelect('supply.id')
            ->addSelect('supply.event')
            ->join(
                'supply_const',
                WbSupply::class,
                'supply',
                'supply.id = supply_const.main'
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
            ->enableCache((string) $profile, 3600)
            ->fetchAssociative();
    }
}