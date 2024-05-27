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

namespace BaksDev\Wildberries\Package\Repository\Supply\ExistOpenSupplyProfile;

use BaksDev\Core\Doctrine\DBALQueryBuilder;
use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;
use BaksDev\Wildberries\Package\Entity\Supply\Const\WbSupplyConst;
use BaksDev\Wildberries\Package\Entity\Supply\Event\WbSupplyEvent;
use BaksDev\Wildberries\Package\Entity\Supply\WbSupply;
use BaksDev\Wildberries\Package\Type\Supply\Status\WbSupplyStatus;
use Doctrine\DBAL\Connection;

final class ExistOpenSupplyProfile implements ExistOpenSupplyProfileInterface
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
            ->setParameter('profile', $profile, UserProfileUid::TYPE)
        ;


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
            ->setParameter('open', WbSupplyStatus\WbSupplyStatusOpen::STATUS)
        ;

        return $qb->fetchExist();

    }

}