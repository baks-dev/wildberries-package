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

namespace BaksDev\Wildberries\Products\UseCase\Cards\Delete\Tests;

use BaksDev\Core\Doctrine\ORMQueryBuilder;
use BaksDev\Products\Category\Type\Id\ProductCategoryUid;
use BaksDev\Products\Category\Type\Section\Field\Id\ProductCategorySectionFieldUid;
use BaksDev\Products\Product\Entity\Product;
use BaksDev\Products\Product\Type\Id\ProductUid;
use BaksDev\Products\Product\Type\Offers\ConstId\ProductOfferConst;
use BaksDev\Products\Product\Type\Offers\Variation\ConstId\ProductVariationConst;
use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;
use BaksDev\Wildberries\Products\Entity\Barcode\Event\WbBarcodeEvent;
use BaksDev\Wildberries\Products\Entity\Barcode\WbBarcode;
use BaksDev\Wildberries\Products\Entity\Cards\WbProductCard;
use BaksDev\Wildberries\Products\Type\Cards\Id\WbCardUid;
use BaksDev\Wildberries\Products\UseCase\Barcode\Delete\WbBarcodeDeleteDTO;
use BaksDev\Wildberries\Products\UseCase\Barcode\Delete\WbBarcodeDeleteHandler;
use BaksDev\Wildberries\Products\UseCase\Barcode\NewEdit\Custom\WbBarcodeCustomDTO;
use BaksDev\Wildberries\Products\UseCase\Barcode\NewEdit\Property\WbBarcodePropertyDTO;
use BaksDev\Wildberries\Products\UseCase\Barcode\NewEdit\WbBarcodeDTO;
use BaksDev\Wildberries\Products\UseCase\Cards\Delete\WbProductCardDeleteDTO;
use BaksDev\Wildberries\Products\UseCase\Cards\Delete\WbProductCardDeleteHandler;
use BaksDev\Wildberries\Products\UseCase\Cards\NewEdit\Offer\WbProductCardOfferDTO;
use BaksDev\Wildberries\Products\UseCase\Cards\NewEdit\Variation\WbProductCardVariationDTO;
use BaksDev\Wildberries\Products\UseCase\Cards\NewEdit\WbProductCardDTO;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\Attribute\When;

/**
 * @group wildberries-products
 * @group wildberries-products-card
 */
#[When(env: 'test')]
final class WbProductCardDeleteTest extends KernelTestCase
{
    /**
     * @depends BaksDev\Wildberries\Products\Controller\Admin\Cards\Tests\UpdateControllerTest::testComplete
     */
    public function testUseCase(): void
    {
        self::bootKernel();
        $container = self::getContainer();


        /** @var EntityManager $em */
        $em = $container->get(EntityManagerInterface::class);

        $WbProductCard = $em->getRepository(WbProductCard::class)
            ->find(WbCardUid::TEST);


        /**
         * WbProductCardDTO
         */


        $WbProductCardDTO = new WbProductCardDTO();
        $WbProductCard->getDto($WbProductCardDTO);

        self::assertEquals(ProductUid::TEST, (string) $WbProductCardDTO->getProduct());
        self::assertEquals(12345, $WbProductCardDTO->getImtId());


        /**
         * WbProductCardOfferDTO
         */

        /** @var WbProductCardOfferDTO $WbProductCardOfferDTO */
        $WbProductCardOfferDTO = $WbProductCardDTO->getOffer()->current();
        self::assertEquals(ProductOfferConst::TEST, (string) $WbProductCardOfferDTO->getOffer());
        self::assertEquals(67890, $WbProductCardOfferDTO->getNomenclature());



        /**
         * WbProductCardVariationDTO
         */


        /** @var WbProductCardVariationDTO $WbProductCardVariationDTO */
        $WbProductCardVariationDTO = $WbProductCardDTO->getVariation()->current();
        self::assertEquals(ProductVariationConst::TEST, (string) $WbProductCardVariationDTO->getVariation());
        self::assertEquals('IyWsDVXJqe', $WbProductCardVariationDTO->getBarcode());



        /** DELETE */


        $WbProductCardDeleteDTO = new WbProductCardDeleteDTO();
        $WbProductCard->getDto($WbProductCardDeleteDTO);

        /** @var WbProductCardDeleteHandler $WbProductCardDeleteHandler */
        $WbProductCardDeleteHandler = $container->get(WbProductCardDeleteHandler::class);
        $WbProductCardDeleteHandler->handle($WbProductCardDeleteDTO);

        $em->remove($WbProductCard);
        $em->flush();

    }

    /**
     * @depends testUseCase
     */
    public function testComplete(): void
    {
        /** @var EntityManagerInterface $em */
        $container = self::getContainer();
        $em = $container->get(EntityManagerInterface::class);

        $WbProductCard = $em->getRepository(WbProductCard::class)
            ->find(WbCardUid::TEST);

        if($WbProductCard)
        {
            $em->remove($WbProductCard);
        }

        self::assertNull($WbProductCard);


    }
}
