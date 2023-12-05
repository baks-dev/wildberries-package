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

namespace BaksDev\Wildberries\Products\UseCase\Settings\NewEdit\Tests;

use BaksDev\Products\Category\Type\Id\ProductCategoryUid;
use BaksDev\Products\Category\Type\Section\Field\Id\ProductCategorySectionFieldUid;
use BaksDev\Wildberries\Products\Entity\Settings\Event\WbProductSettingsEvent;
use BaksDev\Wildberries\Products\Entity\Settings\WbProductSettings;
use BaksDev\Wildberries\Products\Type\Settings\Event\WbProductSettingsEventUid;
use BaksDev\Wildberries\Products\UseCase\Settings\NewEdit\Property\WbProductSettingsPropertyDTO;
use BaksDev\Wildberries\Products\UseCase\Settings\NewEdit\WbProductsSettingsDTO;
use BaksDev\Wildberries\Products\UseCase\Settings\NewEdit\WbProductsSettingsHandler;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\Attribute\When;
use BaksDev\Wildberries\Products\Controller\Admin\Settings\Tests\EditControllerTest;

/**
 * @group wildberries-products
 * @group wildberries-products-settings
 *
 * @depends BaksDev\Wildberries\Products\Controller\Admin\Settings\Tests\EditControllerTest::class
 *
 * @see EditControllerTest
 */
#[When(env: 'test')]
final class WbProductSettingsEditTest extends KernelTestCase
{

    public function testUseCase(): void
    {
        //self::bootKernel();
        $container = self::getContainer();

        /** @var EntityManagerInterface $em */
        $em = $container->get(EntityManagerInterface::class);

        $WbProductSettingsEvent = $em->getRepository(WbProductSettingsEvent::class)->find(WbProductSettingsEventUid::TEST);
        self::assertNotNull($WbProductSettingsEvent);


        $WbProductsSettingsDTO = new WbProductsSettingsDTO();
        $WbProductSettingsEvent->getDto($WbProductsSettingsDTO);


        self::assertEquals(ProductCategoryUid::TEST, (string) $WbProductsSettingsDTO->getSettings());
        //$WbProductsSettingsDTO->setSettings(new ProductCategoryUid());

        self::assertEquals('ffxZnGTCbd', $WbProductsSettingsDTO->getName());
        $WbProductsSettingsDTO->setName('GCRIVEHZUY');


        /** @var WbProductSettingsPropertyDTO $WbProductSettingsPropertyDTO */

        $WbProductSettingsPropertyDTO = $WbProductsSettingsDTO->getProperty()->current();

        self::assertEquals('MsUPpqkQHD', $WbProductSettingsPropertyDTO->getType());
        $WbProductSettingsPropertyDTO->setType('fTXTGyZxIr');

        self::assertEquals(ProductCategorySectionFieldUid::TEST, (string) $WbProductSettingsPropertyDTO->getField());
        $WbProductSettingsPropertyDTO->setField(new ProductCategorySectionFieldUid());



        /** Вспомогательные свойства */

        $WbProductSettingsPropertyDTO->setUnit('rRSzqbqKxA');
        self::assertEquals('rRSzqbqKxA', $WbProductSettingsPropertyDTO->getUnit());

        $WbProductSettingsPropertyDTO->setPopular(false);
        self::assertFalse($WbProductSettingsPropertyDTO->isPopular());

        $WbProductSettingsPropertyDTO->setRequired(false);
        self::assertFalse($WbProductSettingsPropertyDTO->isRequired());


        /** UPDATE */

        self::bootKernel();

        /** @var WbProductsSettingsHandler $WbProductsSettingsHandler */
        $WbProductsSettingsHandler = self::getContainer()->get(WbProductsSettingsHandler::class);
        $handle = $WbProductsSettingsHandler->handle($WbProductsSettingsDTO);
        self::assertTrue(($handle instanceof WbProductSettings), $handle.': Ошибка WbProductSettings');

    }

    public function testComplete(): void
    {
        //self::bootKernel();
        $container = self::getContainer();

        /** @var EntityManagerInterface $em */
        $em = $container->get(EntityManagerInterface::class);
        $WbProductSettings = $em->getRepository(WbProductSettings::class)->find(ProductCategoryUid::TEST);
        self::assertNotNull($WbProductSettings);
    }
}