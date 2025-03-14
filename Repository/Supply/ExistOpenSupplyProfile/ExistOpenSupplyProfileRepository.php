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

namespace BaksDev\Wildberries\Package\Repository\Supply\ExistOpenSupplyProfile;

use BaksDev\Core\Doctrine\DBALQueryBuilder;
use BaksDev\Users\Profile\UserProfile\Entity\UserProfile;
use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;
use BaksDev\Wildberries\Package\Entity\Supply\Event\WbSupplyEvent;
use BaksDev\Wildberries\Package\Entity\Supply\Invariable\WbSupplyInvariable;
use BaksDev\Wildberries\Package\Entity\Supply\WbSupply;
use BaksDev\Wildberries\Package\Entity\Supply\Wildberries\WbSupplyWildberries;
use BaksDev\Wildberries\Package\Type\Supply\Status\WbSupplyStatus\WbSupplyStatusNew;
use BaksDev\Wildberries\Package\Type\Supply\Status\WbSupplyStatus\WbSupplyStatusOpen;
use Doctrine\DBAL\ArrayParameterType;
use InvalidArgumentException;


final class ExistOpenSupplyProfileRepository implements ExistOpenSupplyProfileInterface
{
    private UserProfileUid|false $profile = false;

    private string|false $identifier = false;

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

    public function forIdentifier(string $identifier): self
    {

        $this->identifier = $identifier;

        return $this;
    }

    private function builder(): DBALQueryBuilder
    {
        $dbal = $this->DBALQueryBuilder->createQueryBuilder(self::class);

        $dbal
            ->from(WbSupplyInvariable::class, 'invariable')
            ->where('invariable.profile = :profile')
            ->setParameter(
                key: 'profile',
                value: $this->profile,
                type: UserProfileUid::TYPE
            );


        $dbal->join(
            'invariable',
            WbSupply::class, 'supply',
            'supply.id = invariable.main AND supply.event = invariable.event'
        );


        $dbal
            ->join(
                'invariable',
                WbSupplyEvent::class, 'supply_event',
                'supply_event.id = invariable.event AND supply_event.status IN (:status)'
            );


        if($this->identifier)
        {
            $dbal
                ->join(
                    'invariable',
                    WbSupplyWildberries::class, 'supply_wildberries',
                    '
                    supply_wildberries.main = invariable.main AND 
                    supply_wildberries.event = invariable.event AND
                    supply_wildberries.identifier = :identifier
                    '
                )
                ->setParameter(
                    key: 'identifier',
                    value: $this->identifier,
                );
        }

        return $dbal;
    }

    /**
     * Метод проверяет, имеется ли поставка со статусом «New»
     */
    public function isExistNewSupply(): bool
    {
        if(false === $this->profile)
        {
            throw new InvalidArgumentException('Invalid Argument Profile');
        }

        $dbal = $this->builder();

        $dbal->setParameter(
            key: 'status',
            value: [WbSupplyStatusNew::STATUS],
            type: ArrayParameterType::STRING
        );

        return $dbal->fetchExist();
    }


    /**
     * Метод проверяет, имеется ли поставка со статусом «Open»
     */
    public function isExistOpenSupply(): bool
    {
        if(false === $this->profile)
        {
            throw new InvalidArgumentException('Invalid Argument Profile');
        }

        $dbal = $this->builder();

        $dbal->setParameter(
            key: 'status',
            value: [WbSupplyStatusOpen::STATUS],
            type: ArrayParameterType::STRING
        );

        return $dbal->fetchExist();
    }

    /**
     * Метод проверяет, имеется ли поставка со статусом «New» либо «Open»
     */
    public function isExistNewOrOpenSupply(): bool
    {
        if(false === $this->profile)
        {
            throw new InvalidArgumentException('Invalid Argument Profile');
        }

        $dbal = $this->builder();

        $dbal->setParameter(
            key: 'status',
            value: [WbSupplyStatusNew::STATUS, WbSupplyStatusOpen::STATUS],
            type: ArrayParameterType::STRING
        );

        return $dbal->fetchExist();
    }

}