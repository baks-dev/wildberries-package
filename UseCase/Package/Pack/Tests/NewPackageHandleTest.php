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

namespace BaksDev\Wildberries\Package\UseCase\Package\Pack\Tests;

use BaksDev\Orders\Order\Type\Id\OrderUid;
use BaksDev\Wildberries\Package\Entity\Package\Event\WbPackageEvent;
use BaksDev\Wildberries\Package\Entity\Package\WbPackage;
use BaksDev\Wildberries\Package\Type\Package\Id\WbPackageUid;
use BaksDev\Wildberries\Package\Type\Package\Status\WbPackageStatus\Collection\WbPackageStatusCollection;
use BaksDev\Wildberries\Package\Type\Package\Status\WbPackageStatus\WbPackageStatusNew;
use BaksDev\Wildberries\Package\Type\Supply\Id\WbSupplyUid;
use BaksDev\Wildberries\Package\UseCase\Package\Pack\Orders\WbPackageOrderDTO;
use BaksDev\Wildberries\Package\UseCase\Package\Pack\Supply\WbPackageSupplyDTO;
use BaksDev\Wildberries\Package\UseCase\Package\Pack\WbPackageDTO;
use BaksDev\Wildberries\Package\UseCase\Package\Pack\WbPackageHandler;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\Attribute\When;

/**
 * @group wildberries-package
 * @group wildberries-package-package
 */
#[When(env: 'test')]
final class NewPackageHandleTest extends KernelTestCase
{
    public static function setUpBeforeClass(): void
    {
        /** @var $WbPackageStatusCollection $WbPackageStatusCollection */
        $WbPackageStatusCollection = self::getContainer()->get(WbPackageStatusCollection::class);
        $WbPackageStatusCollection->cases();

        /** @var EntityManagerInterface $em */
        $em = self::getContainer()->get(EntityManagerInterface::class);

        $WbPackage = $em->getRepository(WbPackage::class)
            ->findOneBy(['id' => WbPackageUid::TEST]);

        if($WbPackage)
        {
            $em->remove($WbPackage);
        }

        /* WbBarcodeEvent */

        $WbPackageEvent = $em->getRepository(WbPackageEvent::class)
            ->findBy(['main' => WbPackageUid::TEST]);

        foreach($WbPackageEvent as $remove)
        {
            $em->remove($remove);
        }

        $em->flush();
    }


    public function testUseCase(): void
    {

        /** @var WbPackageDTO $WbPackageDTO */

        $WbSupplyUid = new WbSupplyUid();
        $WbPackageDTO = new WbPackageDTO();
        $WbPackageDTO->setPackageSupply($WbSupplyUid);


        /** @var WbPackageOrderDTO $WbPackageOrderDTO */

        $WbPackageOrderDTO = new WbPackageOrderDTO();
        $OrderUid = new OrderUid();
        $WbPackageOrderDTO->setId($OrderUid);
        self::assertSame($OrderUid, $WbPackageOrderDTO->getId());
        self::assertEquals(WbPackageStatusNew::STATUS, (string) $WbPackageOrderDTO->getStatus());

        $WbPackageDTO->addOrd($WbPackageOrderDTO);

        /** @var WbPackageSupplyDTO $WbPackageSupplyDTO */

        $WbPackageSupplyDTO = $WbPackageDTO->getSupply();
        self::assertSame($WbSupplyUid, $WbPackageSupplyDTO->getSupply());


        /** @var WbPackageHandler $WbPackageHandler */

        $WbPackageHandler = self::getContainer()->get(WbPackageHandler::class);
        $handle = $WbPackageHandler->handle($WbPackageDTO);

        self::assertTrue(($handle instanceof WbPackage), $handle.': Ошибка WbPackage');

    }

    public function testComplete(): void
    {
        /** @var EntityManagerInterface $em */
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $WbPackage = $em->getRepository(WbPackage::class)
            ->find(WbPackageUid::TEST);
        self::assertNotNull($WbPackage);
    }
}