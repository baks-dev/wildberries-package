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

namespace BaksDev\Wildberries\Package\UseCase\Supply\New\Tests;

use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;
use BaksDev\Wildberries\Package\Entity\Supply\Event\WbSupplyEvent;
use BaksDev\Wildberries\Package\Entity\Supply\WbSupply;
use BaksDev\Wildberries\Package\Type\Supply\Id\WbSupplyUid;
use BaksDev\Wildberries\Package\Type\Supply\Status\WbSupplyStatus\Collection\WbSupplyStatusCollection;
use BaksDev\Wildberries\Package\Type\Supply\Status\WbSupplyStatus\WbSupplyStatusNew;
use BaksDev\Wildberries\Package\UseCase\Supply\New\Invariable\WbSupplyInvariableDTO;
use BaksDev\Wildberries\Package\UseCase\Supply\New\WbSupplyNewDTO;
use BaksDev\Wildberries\Package\UseCase\Supply\New\WbSupplyNewHandler;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\Attribute\When;

#[Group('wildberries-package')]
#[When(env: 'test')]
final class NewHandleTest extends KernelTestCase
{
    public static function setUpBeforeClass(): void
    {
        /**
         * Инициируем статус для итератора тегов
         * @var WbSupplyStatusCollection $WbSupplyStatus
         */
        $WbSupplyStatus = self::getContainer()->get(WbSupplyStatusCollection::class);
        $WbSupplyStatus->cases();

        /** @var EntityManagerInterface $em */
        $em = self::getContainer()->get(EntityManagerInterface::class);

        $WbSupply = $em->getRepository(WbSupply::class)
            ->findOneBy(['id' => WbSupplyUid::TEST]);

        if($WbSupply)
        {
            $em->remove($WbSupply);
        }

        /* WbBarcodeEvent */

        $WbSupplyEventCollection = $em->getRepository(WbSupplyEvent::class)
            ->findBy(['main' => WbSupplyUid::TEST]);

        foreach($WbSupplyEventCollection as $remove)
        {
            $em->remove($remove);
        }

        $em->flush();
        $em->clear();
    }


    public function testUseCase(): void
    {
        $status = new WbSupplyStatusNew();

        $UserProfileUid = new UserProfileUid();
        $WbSupplyNewDTO = new WbSupplyNewDTO($UserProfileUid);

        /** @var WbSupplyInvariableDTO $WbSupplyInvariableDTO */
        $WbSupplyInvariableDTO = $WbSupplyNewDTO->getInvariable();
        self::assertSame($UserProfileUid, $WbSupplyInvariableDTO->getProfile());


        /** @var WbSupplyNewHandler $WbSupplyOpenHandler */
        $WbSupplyOpenHandler = self::getContainer()->get(WbSupplyNewHandler::class);
        $handle = $WbSupplyOpenHandler->handle($WbSupplyNewDTO, new UserProfileUid());

        self::assertTrue(($handle instanceof WbSupply), $handle.': Ошибка WbSupply');

    }


    public function testComplete(): void
    {
        /** @var EntityManagerInterface $em */
        $em = self::getContainer()->get(EntityManagerInterface::class);
        $WbSupply = $em->getRepository(WbSupply::class)
            ->find(WbSupplyUid::TEST);
        self::assertNotNull($WbSupply);

        $em->clear();

    }
}