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

namespace BaksDev\Wildberries\Package\UseCase\Package\OrderStatus\Tests;

use BaksDev\Orders\Order\Type\Id\OrderUid;
use BaksDev\Wildberries\Package\Entity\Package\Event\WbPackageEvent;
use BaksDev\Wildberries\Package\Entity\Package\Orders\WbPackageOrder;
use BaksDev\Wildberries\Package\Entity\Package\WbPackage;
use BaksDev\Wildberries\Package\Type\Package\Id\WbPackageUid;
use BaksDev\Wildberries\Package\Type\Package\Status\WbPackageStatus\WbPackageStatusAdd;
use BaksDev\Wildberries\Package\UseCase\Package\OrderStatus\UpdatePackageOrderStatusDTO;
use BaksDev\Wildberries\Package\UseCase\Package\OrderStatus\UpdatePackageOrderStatusHandler;
use BaksDev\Wildberries\Package\UseCase\Package\Pack\Tests\NewPackageHandleTest;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\Attribute\When;

/**
 * @group wildberries-package
 * @group wildberries-package-package
 *
 * @depends BaksDev\Wildberries\Package\UseCase\Package\Pack\Tests\NewPackageHandleTest::class
 *
 * @see     NewPackageHandleTest
 *
 */
#[When(env: 'test')]
final class UpdatePackageOrderStatusHandleTest extends KernelTestCase
{

    public function testUseCase(): void
    {

        $OrderUid = new OrderUid();
        $UpdatePackageOrderStatusDTO = new UpdatePackageOrderStatusDTO($OrderUid);
        self::assertSame($OrderUid, $UpdatePackageOrderStatusDTO->getId());


        $UpdatePackageOrderStatusDTO->setStatus(WbPackageStatusAdd::class);
        self::assertTrue($UpdatePackageOrderStatusDTO->getStatus()->equals(WbPackageStatusAdd::class));


        /** @var UpdatePackageOrderStatusHandler $UpdatePackageOrderStatusHandler */
        $UpdatePackageOrderStatusHandler = self::getContainer()->get(UpdatePackageOrderStatusHandler::class);
        $handle = $UpdatePackageOrderStatusHandler->handle($UpdatePackageOrderStatusDTO);

        self::assertTrue(($handle instanceof WbPackageOrder), $handle.': Ошибка WbPackageOrder');

    }

    public function testComplete(): void
    {
        /** @var EntityManagerInterface $em */
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $WbPackage = $em->getRepository(WbPackage::class)
            ->find(WbPackageUid::TEST);
        self::assertNotNull($WbPackage);
    }


    public static function tearDownAfterClass(): void
    {
        /** @var EntityManagerInterface $em */
        $em = self::getContainer()->get(EntityManagerInterface::class);

        $WbPackage = $em->getRepository(WbPackage::class)
            ->find(WbPackageUid::TEST);

        if($WbPackage)
        {
            $em->remove($WbPackage);
        }

        $WbPackageEventCollection = $em->getRepository(WbPackageEvent::class)
            ->findBy(['main' => WbPackageUid::TEST]);

        foreach($WbPackageEventCollection as $remove)
        {
            $em->remove($remove);
        }

        $em->flush();
    }


}