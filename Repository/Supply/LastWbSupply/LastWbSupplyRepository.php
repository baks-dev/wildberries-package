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
use BaksDev\Core\Type\UidType\UidType;
use BaksDev\Users\Profile\UserProfile\Repository\UserProfileTokenStorage\UserProfileTokenStorageInterface;
use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;
use BaksDev\Wildberries\Entity\Event\Name\WbTokenName;
use BaksDev\Wildberries\Entity\WbToken;
use BaksDev\Wildberries\Package\Entity\Supply\Event\WbSupplyEvent;
use BaksDev\Wildberries\Package\Entity\Supply\Invariable\WbSupplyInvariable;
use BaksDev\Wildberries\Package\Entity\Supply\Modify\WbSupplyModify;
use BaksDev\Wildberries\Package\Entity\Supply\Token\WbSupplyToken;
use BaksDev\Wildberries\Package\Entity\Supply\WbSupply;
use BaksDev\Wildberries\Package\Entity\Supply\Wildberries\WbSupplyWildberries;
use BaksDev\Wildberries\Package\Type\Supply\Id\WbSupplyUid;
use BaksDev\Wildberries\Type\id\WbTokenUid;
use Symfony\Component\Uid\Uuid;

final class LastWbSupplyRepository implements LastWbSupplyInterface
{
    private WbSupplyUid|false $supply = false;

    private Uuid|false $token = false;

    public function __construct(
        private readonly DBALQueryBuilder $DBALQueryBuilder,
        private readonly UserProfileTokenStorageInterface $UserProfileTokenStorage
    ) {}

    /** Идентификатор поставки */
    public function forSupply(WbSupply|WbSupplyUid|null $supply): self
    {
        if(null === $supply)
        {
            return $this;
        }

        if($supply instanceof WbSupply)
        {
            $supply = $supply->getId();
        }

        $this->supply = $supply;

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

    /**
     * Получаем ПОСЛЕДНЮЮ поставку профиля пользователя с любым статусом
     */
    public function find(): LustWbSupplyResult|false
    {
        $dbal = $this->DBALQueryBuilder->createQueryBuilder(self::class);

        $dbal
            ->addSelect('invariable.total')
            ->from(WbSupplyInvariable::class, 'invariable')
            ->where('invariable.profile = :profile')
            ->setParameter(
                key: 'profile',
                value: $this->UserProfileTokenStorage->getProfile(),
                type: UserProfileUid::TYPE,
            );

        $dbal
            ->addSelect('supply.id')
            ->addSelect('supply.event')
            ->join(
                'invariable',
                WbSupply::class,
                'supply',
                'supply.id = invariable.main',
            );

        $dbal
            ->addSelect('event.status')
            ->join(
                'supply',
                WbSupplyEvent::class,
                'event',
                'event.id = supply.event',
            );

        $dbal
            ->addSelect('modify.mod_date AS supply_date')
            ->leftJoin(
                'supply',
                WbSupplyModify::class,
                'modify',
                'modify.event = supply.event',
            );


        $dbal
            ->addSelect('wb.identifier')
            ->leftJoin(
                'supply',
                WbSupplyWildberries::class,
                'wb',
                'wb.main = supply.id',
            );

        $dbal
            ->addSelect('token.value as token')
            ->leftJoin(
                'supply',
                WbSupplyToken::class,
                'token',
                'token.main = supply.id',
            );

        $dbal
            ->leftJoin(
                'token',
                WbToken::class,
                'wb_token',
                'wb_token.id = token.value',
            );

        $dbal
            ->addSelect('wb_token_name.value AS token_name')
            ->leftJoin(
                'wb_token',
                WbTokenName::class,
                'wb_token_name',
                'wb_token_name.event = wb_token.event',
            );

        if(true === ($this->token instanceof Uuid))
        {
            $dbal
                ->andWhere('token.value = :token')
                ->setParameter(
                    key: 'token',
                    value: $this->token,
                    type: UidType::TYPE,
                );
        }

        if(true === ($this->supply instanceof WbSupplyUid))
        {
            $dbal
                ->andWhere('supply.id = :supply')
                ->setParameter(
                    key: 'supply',
                    value: $this->supply,
                    type: WbSupplyUid::TYPE,
                );
        }

        $dbal->orderBy('modify.mod_date', 'DESC');
        $dbal->setMaxResults(1);

        $this->token = false;
        $this->supply = false;

        return $dbal
            ->enableCache('wildberries-package', 3600)
            ->fetchHydrate(LustWbSupplyResult::class);
    }
}