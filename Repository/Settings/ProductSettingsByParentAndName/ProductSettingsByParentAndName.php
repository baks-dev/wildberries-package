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

namespace BaksDev\Wildberries\Products\Repository\Settings\ProductSettingsByParentAndName;


use BaksDev\Wildberries\Products\Entity\Settings\Event\WbProductSettingsEvent;
use BaksDev\Wildberries\Products\Entity\Settings\WbProductSettings;
use Doctrine\ORM\EntityManagerInterface;

final class ProductSettingsByParentAndName implements ProductSettingsByParentAndNameInterface
{
    private EntityManagerInterface $entityManager;
    
    public function __construct(EntityManagerInterface $entityManager)
    {
    
        $this->entityManager = $entityManager;
    }
    
    public function get(string $name): mixed
    {
        /* EXIST SETTINGS */
        $subQueryBuilder = $this->entityManager->createQueryBuilder();
        $subQueryBuilder
          ->select('1')
          ->from(WbProductSettings::class, 'settings')
          ->where('settings.id = event.settings AND settings.event = event.id')
        ;
    

        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('event');
        
        $qb->from( WbProductSettingsEvent::class, 'event');

        $qb->where('event.name = :name');

        $qb->andWhere($qb->expr()->exists($subQueryBuilder->getDQL()));
        
        $qb->setParameter('name', $name);

        return $qb->getQuery()->getOneOrNullResult();
    }
    
}