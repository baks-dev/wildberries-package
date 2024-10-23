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

namespace BaksDev\Wildberries\Package\UseCase\Supply\Close\Tests;

use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;
use BaksDev\Wildberries\Package\Entity\Supply\WbSupply;
use BaksDev\Wildberries\Package\Repository\Supply\WbSupplyCurrentEvent\WbSupplyCurrentEventInterface;
use BaksDev\Wildberries\Package\Type\Supply\Id\WbSupplyUid;
use BaksDev\Wildberries\Package\Type\Supply\Status\WbSupplyStatus\Collection\WbSupplyStatusCollection;
use BaksDev\Wildberries\Package\UseCase\Supply\Close\WbSupplyCloseDTO;
use BaksDev\Wildberries\Package\UseCase\Supply\Close\WbSupplyCloseHandler;
use BaksDev\Wildberries\Package\UseCase\Supply\Open\Tests\OpenHandleTest;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\Attribute\When;

/**
 * @group wildberries-package
 * @group wildberries-package-supply
 *
 * @depends BaksDev\Wildberries\Package\UseCase\Supply\Open\Tests\OpenHandleTest::class
 *
 * @see     OpenHandleTest
 */
#[When(env: 'test')]
final class CloseHandleTest extends KernelTestCase
{
    public function testUseCase(): void
    {
        /**
         * Инициируем статус для итератора тегов
         * @var WbSupplyStatusCollection $WbSupplyStatus
         */
        $WbSupplyStatus = self::getContainer()->get(WbSupplyStatusCollection::class);
        $WbSupplyStatus->cases();


        /** @var WbSupplyCurrentEventInterface $WbSupplyCurrent */
        $WbSupplyCurrent = self::getContainer()->get(WbSupplyCurrentEventInterface::class);
        $WbSupplyEvent = $WbSupplyCurrent->findWbSupplyEvent(new WbSupplyUid());
        self::assertNotNull($WbSupplyEvent);

        $WbSupplyCloseDTO = new WbSupplyCloseDTO();
        $WbSupplyEvent->getDto($WbSupplyCloseDTO);


        /** @var WbSupplyCloseHandler $WbSupplyCloseHandler */
        $WbSupplyCloseHandler = self::getContainer()->get(WbSupplyCloseHandler::class);
        $handle = $WbSupplyCloseHandler->handle($WbSupplyCloseDTO);

        self::assertTrue(($handle instanceof WbSupply), $handle.': Ошибка WbSupply');

    }


}