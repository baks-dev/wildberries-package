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

namespace BaksDev\Wildberries\Package\UseCase\Supply\Delete\Tests;

use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;
use BaksDev\Wildberries\Package\Entity\Supply\Event\WbSupplyEvent;
use BaksDev\Wildberries\Package\Entity\Supply\WbSupply;
use BaksDev\Wildberries\Package\Repository\Supply\WbSupplyCurrentEvent\WbSupplyCurrentEventInterface;
use BaksDev\Wildberries\Package\Type\Supply\Id\WbSupplyUid;
use BaksDev\Wildberries\Package\Type\Supply\Status\WbSupplyStatus\Collection\WbSupplyStatusCollection;
use BaksDev\Wildberries\Package\Type\Supply\Status\WbSupplyStatus\WbSupplyStatusClose;
use BaksDev\Wildberries\Package\Type\Supply\Status\WbSupplyStatus\WbSupplyStatusComplete;
use BaksDev\Wildberries\Package\Type\Supply\Status\WbSupplyStatus\WbSupplyStatusNew;
use BaksDev\Wildberries\Package\Type\Supply\Status\WbSupplyStatus\WbSupplyStatusOpen;
use BaksDev\Wildberries\Package\UseCase\Supply\Delete\Tests\Const\WbSupplyConstDTO;
use BaksDev\Wildberries\Package\UseCase\Supply\Delete\Tests\Wildberries\WbSupplyWildberriesDTO;
use BaksDev\Wildberries\Package\UseCase\Supply\Delete\WbSupplyDeleteDTO;
use BaksDev\Wildberries\Package\UseCase\Supply\Delete\WbSupplyDeleteHandler;
use BaksDev\Wildberries\Package\UseCase\Supply\Sticker\Tests\StickerHandleTest;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\Attribute\When;

/**
 * @group wildberries-package
 * @group wildberries-package-supply
 *
 * @depends BaksDev\Wildberries\Package\UseCase\Supply\Sticker\Tests\StickerHandleTest::class
 *
 * @see     StickerHandleTest
 */
#[When(env: 'test')]
final class DeleteHandleTest extends KernelTestCase
{
    public function testUseCase(): void
    {
        /**
         * Инициируем статус для итератора тегов
         * @var WbSupplyStatusCollection $WbSupplyStatus
         */
        $WbSupplyStatus = self::getContainer()->get(WbSupplyStatusCollection::class);
        $WbSupplyStatus->cases();

        /** Проверяем что события были инициированы */

        /** @var EntityManagerInterface $em */
        $em = self::getContainer()->get(EntityManagerInterface::class);

        $WbSupplyEvent = $em->getRepository(WbSupplyEvent::class)
            ->findOneBy(['main' => WbSupplyUid::TEST, 'status' => WbSupplyStatusNew::STATUS]);
        self::assertNotNull($WbSupplyEvent);

        $WbSupplyEvent = $em->getRepository(WbSupplyEvent::class)
            ->findOneBy(['main' => WbSupplyUid::TEST, 'status' => WbSupplyStatusOpen::STATUS]);
        self::assertNotNull($WbSupplyEvent);

        $WbSupplyEvent = $em->getRepository(WbSupplyEvent::class)
            ->findOneBy(['main' => WbSupplyUid::TEST, 'status' => WbSupplyStatusClose::STATUS]);
        self::assertNotNull($WbSupplyEvent);

        $WbSupplyEvent = $em->getRepository(WbSupplyEvent::class)
            ->findOneBy(['main' => WbSupplyUid::TEST, 'status' => WbSupplyStatusComplete::STATUS]);
        self::assertNotNull($WbSupplyEvent);


        /** Проверяем активное событие */

        /** @var WbSupplyCurrentEventInterface $WbSupplyCurrent */
        $WbSupplyCurrent = self::getContainer()->get(WbSupplyCurrentEventInterface::class);
        $WbSupplyEvent = $WbSupplyCurrent->findWbSupplyEvent(new WbSupplyUid());
        self::assertNotNull($WbSupplyEvent);

        $TestDeleteDTO = new TestDeleteDTO();
        $WbSupplyEvent->getDto($TestDeleteDTO);
        self::assertEquals((string) $TestDeleteDTO->getStatus(), WbSupplyStatusComplete::STATUS);

        /** @var WbSupplyWildberriesDTO $WbSupplyWildberriesDTO */
        $WbSupplyWildberriesDTO = $TestDeleteDTO->getWildberries();
        self::assertEquals('LmnIjwQsdI', $WbSupplyWildberriesDTO->getIdentifier());
        self::assertEquals('DWxgCsZEeC', $WbSupplyWildberriesDTO->getSticker());


        /** @var WbSupplyConstDTO $WbSupplyConstDTO */
        $WbSupplyConstDTO = $TestDeleteDTO->getConst();
        self::assertEquals(UserProfileUid::TEST, (string) $WbSupplyConstDTO->getProfile());


        /** DELETE */

        $WbSupplyDeleteDTO = new WbSupplyDeleteDTO();
        $WbSupplyEvent->getDto($WbSupplyDeleteDTO);

        /** @var WbSupplyDeleteHandler $WbSupplyDeleteHandler */
        $WbSupplyDeleteHandler = self::getContainer()->get(WbSupplyDeleteHandler::class);
        $handle = $WbSupplyDeleteHandler->handle($WbSupplyDeleteDTO);

        self::assertTrue(($handle instanceof WbSupply), $handle.': Ошибка WbSupply');


        $WbSupplyEvent = $em->getRepository(WbSupply::class)
            ->find(WbSupplyUid::TEST);
        self::assertNull($WbSupplyEvent);

        $em->clear();
        //$em->close();

    }

    public static function tearDownAfterClass(): void
    {
        /** @var EntityManagerInterface $em */
        $em = self::getContainer()->get(EntityManagerInterface::class);

        $WbSupply = $em->getRepository(WbSupply::class)
            ->find(WbSupplyUid::TEST);

        if($WbSupply)
        {
            $em->remove($WbSupply);
        }

        $WbSupplyEventCollection = $em->getRepository(WbSupplyEvent::class)
            ->findBy(['main' => WbSupplyUid::TEST]);

        foreach($WbSupplyEventCollection as $remove)
        {
            $em->remove($remove);
        }

        $em->flush();

        $em->clear();
        //$em->close();
    }
}