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

namespace BaksDev\Wildberries\Package\Messenger\Supply;

use App\Kernel;
use BaksDev\Core\Doctrine\ORMQueryBuilder;
use BaksDev\Wildberries\Api\Token\Supplies\WildberriesSupplyClosed;
use BaksDev\Wildberries\Api\Token\Supplies\WildberriesSupplyDelete;
use BaksDev\Wildberries\Package\Entity\Supply\WbSupply;
use BaksDev\Wildberries\Package\Repository\Supply\OpenWbSupply\OpenWbSupplyInterface;
use BaksDev\Wildberries\Package\Repository\Supply\WbSupplyCurrentEvent\WbSupplyCurrentEventInterface;
use BaksDev\Wildberries\Package\Type\Supply\Status\WbSupplyStatus\WbSupplyStatusClose;
use BaksDev\Wildberries\Package\UseCase\Supply\Close\WbSupplyCloseDTO;
use BaksDev\Wildberries\Package\UseCase\Supply\Close\WbSupplyCloseHandler;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(priority: 99)] // высокий приоритет - выполняется первым
final class DeleteWbSupplyHandler
{
    private LoggerInterface $messageDispatchLogger;
    private WbSupplyCurrentEventInterface $wbSupplyCurrentEvent;
    private OpenWbSupplyInterface $openWbSupply;
    private WildberriesSupplyDelete $wildberriesSupplyDelete;
    private ORMQueryBuilder $ORMQueryBuilder;

    public function __construct(
        OpenWbSupplyInterface $openWbSupply,
        WildberriesSupplyDelete $wildberriesSupplyDelete,
        WbSupplyCurrentEventInterface $wbSupplyCurrentEvent,
        LoggerInterface $messageDispatchLogger,
        ORMQueryBuilder $ORMQueryBuilder
    )
    {
        $this->messageDispatchLogger = $messageDispatchLogger;
        $this->wbSupplyCurrentEvent = $wbSupplyCurrentEvent;
        $this->openWbSupply = $openWbSupply;
        $this->wildberriesSupplyDelete = $wildberriesSupplyDelete;
        $this->ORMQueryBuilder = $ORMQueryBuilder;
    }

    /**
     * Метод удаляет поставку Wildberries если системная поставка со статусом Close (Закрыта),
     * но в ней отсутствуют заказы!
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
            $Event->getTotal() !== 0 ||
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
        $this->wildberriesSupplyDelete
            ->profile($UserProfileUid)
            ->withSupply($UserProfileUid->getAttr())
            ->delete();


        /** Удаляем системную поставку */
        $EntityManager = $this->ORMQueryBuilder->getEntityManager();
        $WbSupply = $EntityManager->getRepository(WbSupply::class)->find($message->getId());
        $WbSupply ? $EntityManager->remove($WbSupply) : false;
        $EntityManager->flush();




        $this->messageDispatchLogger->info('Удалили поставку Wildberries с нулевыми заказами',
            [
                'supply' => $UserProfileUid->getAttr(),
                __FILE__.':'.__LINE__,
            ]);
    }


}