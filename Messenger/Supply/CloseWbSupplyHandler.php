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

namespace BaksDev\Wildberries\Package\Messenger\Supply;

use App\Kernel;
use BaksDev\Wildberries\Package\Api\WildberriesSupplyClosed;
use BaksDev\Wildberries\Package\Repository\Supply\OpenWbSupply\OpenWbSupplyInterface;
use BaksDev\Wildberries\Package\Repository\Supply\WbSupplyCurrentEvent\WbSupplyCurrentEventInterface;
use BaksDev\Wildberries\Package\Type\Supply\Status\WbSupplyStatus\WbSupplyStatusClose;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(priority: 99)] // высокий приоритет - выполняется первым
final class CloseWbSupplyHandler
{
    private LoggerInterface $logger;
    private WbSupplyCurrentEventInterface $wbSupplyCurrentEvent;
    private WildberriesSupplyClosed $wildberriesSupplyClosed;
    private OpenWbSupplyInterface $openWbSupply;

    public function __construct(
        OpenWbSupplyInterface $openWbSupply,
        WildberriesSupplyClosed $wildberriesSupplyClosed,
        WbSupplyCurrentEventInterface $wbSupplyCurrentEvent,
        LoggerInterface $wildberriesPackageLogger
    )
    {
        $this->logger = $wildberriesPackageLogger;
        $this->wbSupplyCurrentEvent = $wbSupplyCurrentEvent;
        $this->wildberriesSupplyClosed = $wildberriesSupplyClosed;
        $this->openWbSupply = $openWbSupply;
    }

    /**
     * Метод закрывает поставку Wildberries если системная поставка со статусом Close (Закрыта)
     */
    public function __invoke(WbSupplyMessage $message): void
    {
        if(Kernel::isTestEnvironment())
        {
            return;
        }

        /**
         * Получаем активное событие системной поставки
         */
        $Event = $this->wbSupplyCurrentEvent->findWbSupplyEvent($message->getId());

        if(
            !$Event ||
            $Event->getTotal() === 0 ||
            !$Event->getStatus()->equals(WbSupplyStatusClose::STATUS)
        )
        {
            return;
        }

        /* Получаем профиль пользователя и идентификатор поставки в качестве аттрибута */
        $UserProfileUid = $this->openWbSupply->getWbSupply($message->getId());

        if(!$UserProfileUid || !$UserProfileUid->getAttr())
        {
            return;
        }

        /* Закрываем поставку Wildberries Api */
        $this->wildberriesSupplyClosed
            ->profile($UserProfileUid)
            ->withSupply($UserProfileUid->getAttr())
            ->close();

        $this->logger->info('Закрыли поставку Wildberries',
            [
                'supply' => $UserProfileUid->getAttr(),
                self::class.':'.__LINE__,
            ]);
    }


}