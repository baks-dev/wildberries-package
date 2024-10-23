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

declare(strict_types=1);

namespace BaksDev\Wildberries\Package\Type\Supply\Status\Tests;

use BaksDev\Orders\Order\Type\Id\OrderUid;
use BaksDev\Wildberries\Orders\Type\OrderStatus\Status\WbOrderStatusConfirm;
use BaksDev\Wildberries\Package\Type\Supply\Status\WbSupplyStatus;
use BaksDev\Wildberries\Package\Type\Supply\Status\WbSupplyStatus\Collection\WbSupplyStatusCollection;
use BaksDev\Wildberries\Package\Type\Supply\Status\WbSupplyStatusType;
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
final class WbSupplyStatusTest extends KernelTestCase
{
    public function testUseCase(): void
    {
        /** @var WbSupplyStatusCollection $WbSupplyStatusCollection */
        $WbSupplyStatusCollection = self::getContainer()->get(WbSupplyStatusCollection::class);

        /** @var WbSupplyStatus\Collection\WbSupplyStatusInterface $case */
        foreach($WbSupplyStatusCollection->cases() as $case)
        {
            $WbSupplyStatus = new WbSupplyStatus($case->getValue());

            self::assertTrue($WbSupplyStatus->equals($case)); // объект интерфейса
            self::assertTrue($WbSupplyStatus->equals($case::class)); // немспейс интерфейса
            self::assertTrue($WbSupplyStatus->equals($case->getValue())); // срока
            self::assertTrue($WbSupplyStatus->equals($WbSupplyStatus)); // объект класса


            $WbSupplyStatusType = new WbSupplyStatusType();
            $platform = $this->getMockForAbstractClass(AbstractPlatform::class);

            $convertToDatabase = $WbSupplyStatusType->convertToDatabaseValue($WbSupplyStatus, $platform);
            self::assertEquals($WbSupplyStatus->getWbSupplyStatusValue(), $convertToDatabase);

            $convertToPHP = $WbSupplyStatusType->convertToPHPValue($convertToDatabase, $platform);
            self::assertInstanceOf(WbSupplyStatus::class, $convertToPHP);
            self::assertEquals($case, $convertToPHP->getWbSupplyStatus());

        }

        self::assertTrue(true);

    }
}