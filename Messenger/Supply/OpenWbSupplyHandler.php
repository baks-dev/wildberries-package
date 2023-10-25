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
use BaksDev\Centrifugo\Server\Publish\CentrifugoPublishInterface;
use BaksDev\Wildberries\Api\Token\Supplies\SupplyOpen\WildberriesSupplyOpen;
use BaksDev\Wildberries\Package\Entity\Supply\WbSupply;
use BaksDev\Wildberries\Package\Repository\Supply\OpenWbSupply\OpenWbSupplyInterface;
use BaksDev\Wildberries\Package\Repository\Supply\WbSupplyCurrentEvent\WbSupplyCurrentEventInterface;
use BaksDev\Wildberries\Package\Type\Supply\Status\WbSupplyStatus\WbSupplyStatusNew;
use BaksDev\Wildberries\Package\UseCase\Supply\Open\WbSupplyOpenDTO;
use BaksDev\Wildberries\Package\UseCase\Supply\Open\WbSupplyOpenHandler;
use DomainException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class OpenWbSupplyHandler
{
    private LoggerInterface $messageDispatchLogger;
    private WbSupplyOpenHandler $wbSupplyOpenHandler;
    private WbSupplyCurrentEventInterface $wbSupplyCurrentEvent;
    private WildberriesSupplyOpen $wildberriesSupplyOpen;
    private OpenWbSupplyInterface $openWbSupply;
    private CentrifugoPublishInterface $CentrifugoPublish;

    public function __construct(
        OpenWbSupplyInterface $openWbSupply,
        WildberriesSupplyOpen $wildberriesSupplyOpen,
        WbSupplyCurrentEventInterface $wbSupplyCurrentEvent,
        LoggerInterface $messageDispatchLogger,
        WbSupplyOpenHandler $wbSupplyOpenHandler,
        CentrifugoPublishInterface $CentrifugoPublish
    )
    {
        $this->messageDispatchLogger = $messageDispatchLogger;
        $this->wbSupplyOpenHandler = $wbSupplyOpenHandler;
        $this->wbSupplyCurrentEvent = $wbSupplyCurrentEvent;
        $this->wildberriesSupplyOpen = $wildberriesSupplyOpen;
        $this->openWbSupply = $openWbSupply;
        $this->CentrifugoPublish = $CentrifugoPublish;
    }

    /**
     * Метод открывает поставку Wildberries если статус системной поставки New (новая)
     * и присваивает идентификатор системной
     */
    public function __invoke(WbSupplyMessage $message): void
    {
        if(Kernel::isTestEnvironment())
        {
            return;
        }

        /**  Получаем активное событие системной поставки */
        $Event = $this->wbSupplyCurrentEvent->findWbSupplyEvent($message->getId());

        if(!$Event || !$Event->getStatus()->equals(WbSupplyStatusNew::class))
        {
            return;
        }


        /* Получаем профиль пользователя и идентификатор поставки в качестве аттрибута */
        $UserProfileUid = $this->openWbSupply->getWbSupply($message->getId());

        if(!$UserProfileUid || $UserProfileUid->getAttr())
        {
            return;
        }

        /* Открываем поставку Wildberries и получаем идентификатор */
        $WildberriesSupplyOpenDTO = $this->wildberriesSupplyOpen
            ->profile($UserProfileUid)
            ->open();

        /* Открываем поставку Wildberries и присваиваем идентификатор поставки  */
        $WbSupplyOpenDTO = new WbSupplyOpenDTO();
        $Event->getDto($WbSupplyOpenDTO);
        $WbSupplyWildberriesDTO = $WbSupplyOpenDTO->getWildberries();
        $WbSupplyWildberriesDTO->setIdentifier($WildberriesSupplyOpenDTO->getIdentifier());

        $handle = $this->wbSupplyOpenHandler->handle($WbSupplyOpenDTO);

        if(!$handle instanceof WbSupply)
        {
            throw new DomainException(sprintf('%s: Ошибка при открытии поставки', $handle));
        }

        $this->messageDispatchLogger->info('Открыли новую поставку',
            [
                'identifier' => $WildberriesSupplyOpenDTO->getIdentifier(),
                __FILE__.':'.__LINE__,
            ]);

        /** Отправляем сокет с идентификатором поставки */
        $this->CentrifugoPublish
            ->addData(['number' => $WildberriesSupplyOpenDTO->getIdentifier()]) // ID упаковки
            ->send((string) $message->getId());

    }

}