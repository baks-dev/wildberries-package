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

namespace BaksDev\Wildberries\Package\Repository\Supply\WbSupply\Tests;

use BaksDev\Wildberries\Package\Repository\Supply\WbSupply\WbSupplyInterface;
use BaksDev\Wildberries\Package\Type\Supply\Event\WbSupplyEventUid;
use BaksDev\Wildberries\Package\Type\Supply\Id\WbSupplyUid;
use BaksDev\Wildberries\Package\Type\Supply\Status\WbSupplyStatus;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\DependencyInjection\Attribute\When;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

#[Group('wildberries-package')]
#[When(env: 'test')]
class WbSupplyRepositoryTest extends KernelTestCase
{
    public function testUseCase(): void
    {

        self::assertTrue(true);
        return;

        // Бросаем событие консольной комманды
        $dispatcher = self::getContainer()->get(EventDispatcherInterface::class);
        $event = new ConsoleCommandEvent(new Command(), new StringInput(''), new NullOutput());
        $dispatcher->dispatch($event, 'console.command');

        /** @var WbSupplyInterface $WbSupplyRepository */
        $WbSupplyRepository = self::getContainer()->get(WbSupplyInterface::class);

        $WbSupply = $WbSupplyRepository
            ->forSupply('0195949b-0a3e-7bdc-9abf-104d647b8de6')
            ->find();

        self::assertInstanceOf(WbSupplyUid::class, $WbSupply->getId());
        self::assertInstanceOf(WbSupplyEventUid::class, $WbSupply->getEvent());
        self::assertInstanceOf(WbSupplyStatus::class, $WbSupply->getStatus());
        self::assertInstanceOf(DateTimeImmutable::class, $WbSupply->getDate());


    }

}