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

namespace BaksDev\Wildberries\Products\UseCase\Cards\NewEdit\Tests;

use BaksDev\Products\Category\Type\Id\ProductCategoryUid;
use BaksDev\Products\Category\Type\Section\Field\Id\ProductCategorySectionFieldUid;
use BaksDev\Products\Product\Type\Id\ProductUid;
use BaksDev\Products\Product\Type\Offers\ConstId\ProductOfferConst;
use BaksDev\Products\Product\Type\Offers\Variation\ConstId\ProductVariationConst;
use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;
use BaksDev\Wildberries\Products\Entity\Barcode\WbBarcode;
use BaksDev\Wildberries\Products\Entity\Cards\WbProductCard;
use BaksDev\Wildberries\Products\Type\Cards\Id\WbCardUid;
use BaksDev\Wildberries\Products\UseCase\Barcode\NewEdit\Custom\WbBarcodeCustomDTO;
use BaksDev\Wildberries\Products\UseCase\Barcode\NewEdit\Property\WbBarcodePropertyDTO;
use BaksDev\Wildberries\Products\UseCase\Barcode\NewEdit\WbBarcodeDTO;
use BaksDev\Wildberries\Products\UseCase\Barcode\NewEdit\WbBarcodeHandler;
use BaksDev\Wildberries\Products\UseCase\Cards\NewEdit\Offer\WbProductCardOfferDTO;
use BaksDev\Wildberries\Products\UseCase\Cards\NewEdit\Variation\WbProductCardVariationDTO;
use BaksDev\Wildberries\Products\UseCase\Cards\NewEdit\WbProductCardDTO;
use BaksDev\Wildberries\Products\UseCase\Cards\NewEdit\WbProductCardHandler;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\Attribute\When;

/**
 * @group wildberries-products
 * @group wildberries-products-card
 */
#[When(env: 'test')]
final class WbProductCardNewTest extends KernelTestCase
{
    public function testUseCase(): void
    {

        /**
         * WbProductCardDTO
         */

        $WbProductCardDTO = new WbProductCardDTO();

        $ProductUid = new ProductUid();
        $WbProductCardDTO->setProduct($ProductUid);
        self::assertSame($ProductUid, $WbProductCardDTO->getProduct());

        $WbProductCardDTO->setImtId(12345);
        self::assertEquals(12345, $WbProductCardDTO->getImtId());


        /**
         * WbProductCardOfferDTO
         */

        $WbProductCardOfferDTO = new  WbProductCardOfferDTO();

        $ProductOfferConst = new  ProductOfferConst();
        $WbProductCardOfferDTO->setOffer($ProductOfferConst);
        self::assertSame($ProductOfferConst, $WbProductCardOfferDTO->getOffer());

        $WbProductCardOfferDTO->setNomenclature(67890);
        self::assertEquals(67890, $WbProductCardOfferDTO->getNomenclature());

        $WbProductCardDTO->addOffer($WbProductCardOfferDTO);
        self::assertTrue($WbProductCardDTO->getOffer()->contains($WbProductCardOfferDTO));

        /**
         * WbProductCardVariationDTO
         */

        $WbProductCardVariationDTO = new  WbProductCardVariationDTO();

        $ProductVariationConst = new  ProductVariationConst();
        $WbProductCardVariationDTO->setVariation($ProductVariationConst);
        self::assertSame($ProductVariationConst, $WbProductCardVariationDTO->getVariation());

        $WbProductCardVariationDTO->setBarcode('IyWsDVXJqe');
        self::assertEquals('IyWsDVXJqe', $WbProductCardVariationDTO->getBarcode());

        $WbProductCardDTO->addVariation($WbProductCardVariationDTO);
        self::assertTrue($WbProductCardDTO->getVariation()->contains($WbProductCardVariationDTO));

        self::bootKernel();

        /** @var WbProductCardHandler $WbProductCardHandler */
        $WbProductCardHandler = self::getContainer()->get(WbProductCardHandler::class);
        $handle = $WbProductCardHandler->handle($WbProductCardDTO);

        self::assertTrue(($handle instanceof WbProductCard), $handle.': Ошибка WbProductCard');

    }


    public function testComplete(): void
    {
        /** @var EntityManagerInterface $em */
        $em = self::getContainer()->get(EntityManagerInterface::class);

        $WbProductCard = $em->getRepository(WbProductCard::class)
            ->find(WbCardUid::TEST);

        self::assertNotNull($WbProductCard);
    }
}