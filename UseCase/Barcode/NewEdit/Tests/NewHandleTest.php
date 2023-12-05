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
use BaksDev\Wildberries\Products\Entity\Barcode\Event\WbBarcodeEvent;
use BaksDev\Wildberries\Products\Entity\Barcode\WbBarcode;
use BaksDev\Wildberries\Products\UseCase\Barcode\NewEdit\Custom\WbBarcodeCustomDTO;
use BaksDev\Wildberries\Products\UseCase\Barcode\NewEdit\Property\WbBarcodePropertyDTO;
use BaksDev\Wildberries\Products\UseCase\Barcode\NewEdit\WbBarcodeDTO;
use BaksDev\Wildberries\Products\UseCase\Barcode\NewEdit\WbBarcodeHandler;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\Attribute\When;

/**
 * @group wildberries-products
 * @group wildberries-products-barcode
 */
#[When(env: 'test')]
final class NewHandleTest extends KernelTestCase
{
    /**
     * This method is called before the first test of this test class is run.
     */
    public static function setUpBeforeClass(): void
    {
        /** @var EntityManagerInterface $em */
        $em = self::getContainer()->get(EntityManagerInterface::class);

        $WbBarcode = $em->getRepository(WbBarcode::class)
            ->findOneBy(['id' => ProductCategoryUid::TEST, 'profile' => UserProfileUid::TEST]);

        if($WbBarcode)
        {
            $em->remove($WbBarcode);

            /* WbBarcodeEvent */

            $WbBarcodeEventCollection = $em->getRepository(WbBarcodeEvent::class)
                ->findBy(['category' => ProductCategoryUid::TEST]);

            foreach($WbBarcodeEventCollection as $remove)
            {
                $em->remove($remove);
            }

            $em->flush();
        }
    }


    public function testUseCase(): void
    {
        $WbBarcodeDTO = new WbBarcodeDTO();

        // setCategory
        $ProductCategoryUid = new ProductCategoryUid();
        $WbBarcodeDTO->setCategory($ProductCategoryUid);
        self::assertSame($ProductCategoryUid, $WbBarcodeDTO->getCategory());

        // setOffer
        $WbBarcodeDTO->setOffer(true);
        self::assertTrue($WbBarcodeDTO->getOffer());

        // setVariation
        $WbBarcodeDTO->setVariation(true);
        self::assertTrue($WbBarcodeDTO->getVariation());

        // setCounter
        $WbBarcodeDTO->setCounter(3);
        self::assertEquals(3, $WbBarcodeDTO->getCounter());


        $WbBarcodePropertyDTO = new WbBarcodePropertyDTO();

        // setOffer
        $ProductCategorySectionFieldUid = new  ProductCategorySectionFieldUid();
        $WbBarcodePropertyDTO->setOffer($ProductCategorySectionFieldUid);
        self::assertSame($ProductCategorySectionFieldUid, $WbBarcodePropertyDTO->getOffer());

        // setSort
        $WbBarcodePropertyDTO->setSort(100);
        self::assertEquals(100, $WbBarcodePropertyDTO->getSort());

        // setName
        $WbBarcodePropertyDTO->setName('Property');
        self::assertEquals('Property', $WbBarcodePropertyDTO->getName());

        // addProperty
        $WbBarcodeDTO->addProperty($WbBarcodePropertyDTO);
        self::assertTrue($WbBarcodeDTO->getProperty()->contains($WbBarcodePropertyDTO));


        $WbBarcodeCustomDTO = new WbBarcodeCustomDTO();

        // setSort
        $WbBarcodeCustomDTO->setSort(100);
        self::assertEquals(100, $WbBarcodeCustomDTO->getSort());

        // setName
        $WbBarcodeCustomDTO->setName('Custom');
        self::assertEquals('Custom', $WbBarcodeCustomDTO->getName());

        // setValue
        $WbBarcodeCustomDTO->setValue('Value');
        self::assertEquals('Value', $WbBarcodeCustomDTO->getValue());

        // addCustom
        $WbBarcodeDTO->addCustom($WbBarcodeCustomDTO);
        self::assertTrue($WbBarcodeDTO->getCustom()->contains($WbBarcodeCustomDTO));


        self::bootKernel();

        /** @var WbBarcodeHandler $WbBarcodeHandler */
        $UserProfileUid = new UserProfileUid();
        $WbBarcodeHandler = self::getContainer()->get(WbBarcodeHandler::class);
        $handle = $WbBarcodeHandler->handle($WbBarcodeDTO, $UserProfileUid);

        self::assertTrue(($handle instanceof WbBarcode), $handle.': Ошибка WbBarcode');

    }

    public function testComplete(): void
    {

        self::bootKernel();
        $container = self::getContainer();

        /** @var EntityManagerInterface $em */
        $em = $container->get(EntityManagerInterface::class);
        $WbBarcode = $em->getRepository(WbBarcode::class)
            ->findOneBy(['id' => ProductCategoryUid::TEST, 'profile' => UserProfileUid::TEST]);
        self::assertNotNull($WbBarcode);


        self::assertTrue(true);
    }
}