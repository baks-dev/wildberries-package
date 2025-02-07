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

namespace BaksDev\Wildberries\Package\Repository\Package\SupplyByPackage;

use BaksDev\Core\Doctrine\DBALQueryBuilder;
use BaksDev\Wildberries\Package\Entity\Package\Event\WbPackageEvent;
use BaksDev\Wildberries\Package\Entity\Package\Supply\WbPackageSupply;
use BaksDev\Wildberries\Package\Type\Package\Event\WbPackageEventUid;
use BaksDev\Wildberries\Package\Type\Supply\Id\WbSupplyUid;
use InvalidArgumentException;


final class SupplyByPackageRepository implements SupplyByPackageInterface
{
    private WbPackageEventUid|false $event = false;

    public function __construct(private readonly DBALQueryBuilder $DBALQueryBuilder) {}

    public function forPackageEvent(WbPackageEvent|WbPackageEventUid|string $event): self
    {
        if(is_string($event))
        {
            $event = new WbPackageEventUid($event);
        }

        if($event instanceof WbPackageEvent)
        {
            $event = $event->getId();
        }

        $this->event = $event;

        return $this;
    }

    /**
     * Возвращает идентификатор поставки упаковки, при условии, что она еще не в печати
     */
    public function find(): WbSupplyUid|false
    {
        if(false === ($this->event instanceof WbPackageEventUid))
        {
            throw new InvalidArgumentException('Invalid Argument WbPackageEvent');
        }

        $dbal = $this->DBALQueryBuilder->createQueryBuilder(self::class);

        $dbal
            ->select('package_supply.supply')
            ->from(WbPackageSupply::class, 'package_supply')
            ->where('package_supply.event = :event AND package_supply.print IS NOT TRUE')
            ->setParameter(
                key: 'event',
                value: $this->event,
                type: WbPackageEventUid::TYPE
            );

        $supply = $dbal->fetchOne();

        return $supply ? new WbSupplyUid($supply) : false;
    }
}