<?php
/*
 *  Copyright 2023.  Baks.dev <admin@baks.dev>
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

namespace BaksDev\Wildberries\Package\Type\Package\Status\Tests;

use BaksDev\Orders\Order\Type\Id\OrderUid;
use BaksDev\Wildberries\Orders\Type\OrderStatus\Status\WbOrderStatusConfirm;
use BaksDev\Wildberries\Package\Entity\Package\Event\WbPackageEvent;
use BaksDev\Wildberries\Package\Entity\Package\Orders\WbPackageOrder;
use BaksDev\Wildberries\Package\Entity\Package\WbPackage;
use BaksDev\Wildberries\Package\Entity\Supply\Const\WbSupplyConst;
use BaksDev\Wildberries\Package\Type\Package\Id\WbPackageUid;
use BaksDev\Wildberries\Package\Type\Package\Status\WbPackageStatus;
use BaksDev\Wildberries\Package\Type\Package\Status\WbPackageStatus\Collection\WbPackageStatusCollection;
use BaksDev\Wildberries\Package\Type\Package\Status\WbPackageStatus\Collection\WbPackageStatusInterface;
use BaksDev\Wildberries\Package\Type\Package\Status\WbPackageStatus\WbPackageStatusAdd;
use BaksDev\Wildberries\Package\Type\Package\Status\WbPackageStatus\WbPackageStatusNew;
use BaksDev\Wildberries\Package\Type\Package\Status\WbPackageStatusType;
use BaksDev\Wildberries\Package\Type\Supply\Id\WbSupplyUid;
use BaksDev\Wildberries\Package\Type\Supply\Status\WbSupplyStatus;
use BaksDev\Wildberries\Package\Type\Supply\Status\WbSupplyStatus\Collection\WbSupplyStatusCollection;
use BaksDev\Wildberries\Package\Type\Supply\Status\WbSupplyStatusType;
use BaksDev\Wildberries\Package\UseCase\Package\OrderStatus\UpdatePackageOrderStatusDTO;
use BaksDev\Wildberries\Package\UseCase\Package\OrderStatus\UpdatePackageOrderStatusHandler;
use BaksDev\Wildberries\Package\UseCase\Package\Pack\Orders\WbPackageOrderDTO;
use BaksDev\Wildberries\Package\UseCase\Package\Pack\Supply\WbPackageSupplyDTO;
use BaksDev\Wildberries\Package\UseCase\Package\Pack\WbPackageDTO;
use BaksDev\Wildberries\Package\UseCase\Package\Pack\WbPackageHandler;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\Attribute\When;
use function PHPUnit\Framework\assertTrue;

/**
 * @group wildberries-package
 * @group wildberries-package-supply
 */
#[When(env: 'test')]
final class WbPackageStatusTest extends KernelTestCase
{
    public function testUseCase(): void
    {
        /** @var WbPackageStatusCollection $WbPackageStatusCollection */
        $WbPackageStatusCollection = self::getContainer()->get(WbPackageStatusCollection::class);

        /** @var WbPackageStatusInterface $case */
        foreach($WbPackageStatusCollection->cases() as $case)
        {
            $WbPackageStatus = new WbPackageStatus($case->getValue());

            self::assertTrue($WbPackageStatus->equals($case::class)); // немспейс интерфейса
            self::assertTrue($WbPackageStatus->equals($case)); // объект интерфейса
            self::assertTrue($WbPackageStatus->equals($case->getValue())); // срока
            self::assertTrue($WbPackageStatus->equals($WbPackageStatus)); // объект класса

            $WbPackageStatusType = new WbPackageStatusType();
            $platform = $this->getMockForAbstractClass(AbstractPlatform::class);

            $convertToDatabase = $WbPackageStatusType->convertToDatabaseValue($WbPackageStatus, $platform);
            self::assertEquals($WbPackageStatus->getWbPackageStatusValue(), $convertToDatabase);

            /** @var WbPackageStatus $convertToPHP */
            $convertToPHP = $WbPackageStatusType->convertToPHPValue($convertToDatabase, $platform);
            self::assertInstanceOf(WbPackageStatus::class, $convertToPHP);
            self::assertInstanceOf($case::class, $convertToPHP->getWbPackageStatus());
            self::assertEquals($case, $convertToPHP->getWbPackageStatus());
            
        }

    }
}