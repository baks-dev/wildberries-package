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

namespace BaksDev\Wildberries\Package\Api\SupplySticker\Tests;

use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;
use BaksDev\Wildberries\Package\Api\SupplySticker\WildberriesSupplySticker;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\Attribute\When;

/**
 * @group wildberries
 * @group wildberries-api
 */
#[When(env: 'test')]
final class WildberriesSupplyStickerTest extends KernelTestCase
{
    public function testUseCase(): void
    {
        /** @var WildberriesSupplySticker $WildberriesSupplySticker */
        //$WildberriesSupplySticker = self::getContainer()->get(WildberriesSupplySticker::class);


        /** TODO: получить номер заказа и получить по нему стикер */

        self::assertTrue(true);

        //        $WildberriesSupplySticker = $WildberriesSupplySticker
        //            ->profile(new UserProfileUid())
        //            ->withSupply('WB-GI-7654321')
        //            ->request()
        //        ;
        //
        //        self::assertEquals('WB-GI-7654321', $WildberriesSupplySticker->getIdentifier());
        //        self::assertNotNull($WildberriesSupplySticker->getSticker());

    }
}