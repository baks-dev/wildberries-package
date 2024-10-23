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

namespace BaksDev\Wildberries\Package\Repository\Supply\ExistOpenSupplyProfile;

use BaksDev\Core\Doctrine\DBALQueryBuilder;
use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;
use BaksDev\Wildberries\Package\Entity\Supply\Const\WbSupplyConst;
use BaksDev\Wildberries\Package\Entity\Supply\Event\WbSupplyEvent;
use BaksDev\Wildberries\Package\Entity\Supply\WbSupply;
use BaksDev\Wildberries\Package\Type\Supply\Status\WbSupplyStatus;
use Doctrine\DBAL\Connection;

final class ExistOpenSupplyProfileRepository implements ExistOpenSupplyProfileInterface
{

    private DBALQueryBuilder $DBALQueryBuilder;

    public function __construct(DBALQueryBuilder $DBALQueryBuilder)
    {
        $this->DBALQueryBuilder = $DBALQueryBuilder;
    }

    public function isExistOpenSupply(UserProfileUid $profile): bool
    {

        $qb = $this->DBALQueryBuilder->createQueryBuilder(self::class);

        $qb
            ->from(WbSupplyConst::TABLE, 'supply_const')
            ->where('supply_const.profile = :profile')
            ->setParameter('profile', $profile, UserProfileUid::TYPE);


        $qb->join(
            'supply_const',
            WbSupply::TABLE, 'supply',
            'supply.id = supply_const.main AND supply.event = supply_const.event'
        );


        $qb
            ->join(
                'supply_const',
                WbSupplyEvent::TABLE, 'supply_event',
                'supply_event.id = supply_const.event AND (supply_event.status = :new OR supply_event.status = :open)'
            )
            ->setParameter('new', WbSupplyStatus\WbSupplyStatusNew::STATUS)
            ->setParameter('open', WbSupplyStatus\WbSupplyStatusOpen::STATUS);

        return $qb->fetchExist();

    }

}