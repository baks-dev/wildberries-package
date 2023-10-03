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

namespace BaksDev\Wildberries\Products\UseCase\Barcode\NewEdit\Tests;

use BaksDev\Products\Category\Type\Id\ProductCategoryUid;
use BaksDev\Products\Category\Type\Section\Field\Id\ProductCategorySectionFieldUid;
use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;
use BaksDev\Wildberries\Package\Entity\Supply\Event\WbSupplyEvent;
use BaksDev\Wildberries\Package\Entity\Supply\WbSupply;
use BaksDev\Wildberries\Package\Type\Supply\Id\WbSupplyUid;
use BaksDev\Wildberries\Package\UseCase\Supply\Open\Const\WbSupplyConstDTO;
use BaksDev\Wildberries\Package\UseCase\Supply\Open\WbSupplyOpenDTO;
use BaksDev\Wildberries\Package\UseCase\Supply\Open\WbSupplyOpenHandler;
use BaksDev\Wildberries\Products\Entity\Barcode\WbBarcode;
use BaksDev\Wildberries\Products\UseCase\Barcode\NewEdit\Custom\WbBarcodeCustomDTO;
use BaksDev\Wildberries\Products\UseCase\Barcode\NewEdit\Property\WbBarcodePropertyDTO;
use BaksDev\Wildberries\Products\UseCase\Barcode\NewEdit\WbBarcodeHandler;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\Attribute\When;

/**
 * @group wildberries-package
 * @group wildberries-package-supply
 */
#[When(env: 'test')]
final class OpenHandleTest extends KernelTestCase
{
    /**
     * This method is called before the first test of this test class is run.
     */
    public static function setUpBeforeClass(): void
    {
        /** @var EntityManagerInterface $em */
        $em = self::getContainer()->get(EntityManagerInterface::class);

        $WbSupply = $em->getRepository(WbSupply::class)
            ->findOneBy(['id' => WbSupplyUid::TEST]);

        if($WbSupply)
        {
            $em->remove($WbSupply);

            /* WbBarcodeEvent */

            $WbSupplyEventCollection = $em->getRepository(WbSupplyEvent::class)
                ->findBy(['main' => WbSupplyUid::TEST]);

            foreach($WbSupplyEventCollection as $remove)
            {
                $em->remove($remove);
            }

            $em->flush();
        }
    }


    public function testUseCase(): void
    {
        $WbSupplyOpenDTO = new WbSupplyOpenDTO();

        $UserProfileUid = new UserProfileUid();
        $WbSupplyOpenDTO->setProfile(new UserProfileUid());
        self::assertSame($UserProfileUid, $WbSupplyOpenDTO->getProfile());


        /** @var WbSupplyConstDTO $WbSupplyConstDTO */


        $WbSupplyConstDTO = $WbSupplyOpenDTO->getConst();

        $WbSupplyConstDTO->setProfile($UserProfileUid);
        self::assertSame($UserProfileUid, $WbSupplyOpenDTO->getProfile());

        /** @var WbSupplyOpenHandler $WbSupplyOpenHandler */
        $WbSupplyOpenHandler = self::getContainer()->get(WbSupplyOpenHandler::class);
        $handle = $WbSupplyOpenHandler->handle($WbSupplyOpenDTO);

        self::assertTrue(($handle instanceof WbSupply), $handle.': Ошибка WbSupply');

    }

    public function testComplete(): void
    {
        /** @var EntityManagerInterface $em */
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $WbSupply = $em->getRepository(WbSupply::class)
            ->find(WbSupplyUid::TEST);
        self::assertNotNull($WbSupply);
    }
}