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

namespace BaksDev\Wildberries\Package\Api\SupplyAll\Tests;

use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;
use BaksDev\Wildberries\Package\Api\SupplyAll\FindAllWildberriesSupplyRequest;
use BaksDev\Wildberries\Package\Api\SupplyAll\WildberriesSupplyDTO;
use BaksDev\Wildberries\Type\Authorization\WbAuthorizationToken;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\Attribute\When;

#[Group('wildberries-package')]
#[When(env: 'test')]
final class WildberriesSupplyAllTest extends KernelTestCase
{

    private static $tocken;

    public static function setUpBeforeClass(): void
    {
        self::$tocken = $_SERVER['TEST_WB_TOCKEN'];
    }


    public function testUseCase(): void
    {
        /** @var FindAllWildberriesSupplyRequest $WildberriesSupplyAll */
        $WildberriesSupplyAll = self::getContainer()->get(FindAllWildberriesSupplyRequest::class);

        $WildberriesSupplyAll->TokenHttpClient(new WbAuthorizationToken(new UserProfileUid(), self::$tocken));

        /** @var WildberriesSupplyDTO $WildberriesSupply */
        $WildberriesSupply = ($WildberriesSupplyAll->all())->current();


        self::assertNotNull($WildberriesSupply->getIdentifier());
        self::assertIsString($WildberriesSupply->getIdentifier());

        self::assertNotNull($WildberriesSupply->getName());
        self::assertIsString($WildberriesSupply->getName());

        self::assertNotNull($WildberriesSupply->isDone());
        self::assertIsBool($WildberriesSupply->isDone());

        self::assertNotNull($WildberriesSupply->getCreated());

        self::assertNotNull($WildberriesSupply->getCargo());
        self::assertIsInt($WildberriesSupply->getCargo());
        self::assertContains($WildberriesSupply->getCargo(), [0, 1, 2, 3]);

    }
}