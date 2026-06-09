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

namespace BaksDev\Wildberries\Package\Repository\Supply\NewOrOpenWbSupply;

use BaksDev\Core\Doctrine\DBALQueryBuilder;
use BaksDev\Core\Type\UidType\UidType;
use BaksDev\Users\Profile\UserProfile\Entity\UserProfile;
use BaksDev\Users\Profile\UserProfile\Repository\UserProfileTokenStorage\UserProfileTokenStorageInterface;
use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;
use BaksDev\Wildberries\Package\Entity\Supply\Event\WbSupplyEvent;
use BaksDev\Wildberries\Package\Entity\Supply\Invariable\WbSupplyInvariable;
use BaksDev\Wildberries\Package\Entity\Supply\Token\WbSupplyToken;
use BaksDev\Wildberries\Package\Entity\Supply\WbSupply;
use BaksDev\Wildberries\Package\Type\Supply\Id\WbSupplyUid;
use BaksDev\Wildberries\Package\Type\Supply\Status\WbSupplyStatus;
use BaksDev\Wildberries\Package\Type\Supply\Status\WbSupplyStatus\WbSupplyStatusNew;
use BaksDev\Wildberries\Package\Type\Supply\Status\WbSupplyStatus\WbSupplyStatusOpen;
use BaksDev\Wildberries\Type\id\WbTokenUid;
use Symfony\Component\Uid\Uuid;

/**
 * Возвращает внутренний идентификатор поставки Wildberries с доп полями в атрибутах
 */
final class NewOrOpenWbSupplyRepository //implements LastWbSupplyInterface
{
    private UserProfileUid|false $profile = false;

    private Uuid|false $token = false;

    public function __construct(
        private readonly DBALQueryBuilder $DBALQueryBuilder,
        private readonly UserProfileTokenStorageInterface $UserProfileTokenStorage
    ) {}

    /** Профиль пользователя */
    public function forProfile(UserProfile|UserProfileUid $profile): self
    {
        if($profile instanceof UserProfile)
        {
            $profile = $profile->getId();
        }

        $this->profile = $profile;

        return $this;
    }

    /** Идентификатор токена маркетплейса */
    public function forToken(Uuid|WbTokenUid|null $token): self
    {
        if(null === $token)
        {
            return $this;
        }

        if(true === ($token instanceof WbTokenUid))
        {
            $token = new Uuid((string) $token);
        }

        $this->token = $token;
        return $this;
    }

    public function find(): WbSupplyUid|false
    {
        $dbal = $this->DBALQueryBuilder->createQueryBuilder(self::class);

        $profile = ($this->profile instanceof UserProfileUid) ? $this->profile : $this->UserProfileTokenStorage->getProfile();

        $dbal
            ->from(WbSupplyInvariable::class, 'wb_supply_invariable')
            ->where('wb_supply_invariable.profile = :profile')
            ->setParameter(
                key: 'profile',
                value: $profile,
                type: UserProfileUid::TYPE,
            );

        $dbal
            ->select('wb_supply.id AS value')
            ->join(
                'wb_supply_invariable',
                WbSupply::class,
                'wb_supply',
                'wb_supply.id = wb_supply_invariable.main',
            );

        $dbal
            ->addSelect('wb_supply_event.status AS property')
            ->join(
                'wb_supply_invariable',
                WbSupplyEvent::class,
                'wb_supply_event',
                '
                    wb_supply_event.id = wb_supply_invariable.event AND 
                    (wb_supply_event.status = :new OR wb_supply_event.status = :open)',
            )
            ->setParameter('new', WbSupplyStatusNew::class, WbSupplyStatus::TYPE)
            ->setParameter('open', WbSupplyStatusOpen::class, WbSupplyStatus::TYPE);

        if($this->token instanceof Uuid)
        {
            $dbal
                ->leftJoin(
                    'wb_supply_invariable',
                    WbSupplyToken::class,
                    'wb_supply_token',
                    'wb_supply_token.main = wb_supply_invariable.main',
                )
                ->andWhere('wb_supply_token.value = :token')
                ->setParameter(
                    key: 'token',
                    value: $this->token,
                    type: UidType::TYPE,
                );
        }

        $dbal->setMaxResults(1);

        return $dbal
            ->enableCache('wildberries-package')
            ->fetchHydrate(WbSupplyUid::class);
    }
}