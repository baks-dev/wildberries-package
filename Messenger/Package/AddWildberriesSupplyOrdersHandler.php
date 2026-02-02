<?php
/*
 *  Copyright 2026.  Baks.dev <admin@baks.dev>
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

namespace BaksDev\Wildberries\Package\Messenger\Package;

use BaksDev\Centrifugo\Server\Publish\CentrifugoPublishInterface;
use BaksDev\Core\Deduplicator\DeduplicatorInterface;
use BaksDev\Core\Messenger\MessageDispatchInterface;
use BaksDev\Orders\Order\Type\Id\OrderUid;
use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;
use BaksDev\Wildberries\Package\Api\SupplyInfo\FindWildberriesSupplyInfoRequest;
use BaksDev\Wildberries\Package\Api\SupplyInfo\WildberriesSupplyInfoDTO;
use BaksDev\Wildberries\Package\Messenger\Orders\Confirm\ConfirmOrderWildberriesMessage;
use BaksDev\Wildberries\Package\Repository\Package\OrdersIdentifierByPackage\OrdersIdentifierByPackageInterface;
use BaksDev\Wildberries\Package\Repository\Package\SupplyByPackage\SupplyByPackageInterface;
use BaksDev\Wildberries\Package\Repository\Supply\OpenWbSupply\OpenWbSupplyInterface;
use BaksDev\Wildberries\Package\Type\Supply\Id\WbSupplyUid;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Добавляет заказы в указанную поставку Wildberries (Api) и обновляет статусы в системной упаковке
 */
#[AsMessageHandler(priority: 0)]
final readonly class AddWildberriesSupplyOrdersHandler
{
    public function __construct(
        private OpenWbSupplyInterface $OpenWbSupply,
        private OrdersIdentifierByPackageInterface $OrdersIdentifierByPackage,
        private FindWildberriesSupplyInfoRequest $WildberriesSupplyInfoRequest,
        private SupplyByPackageInterface $SupplyByPackage,
        private CentrifugoPublishInterface $CentrifugoPublish,
        private DeduplicatorInterface $deduplicator,
        private MessageDispatchInterface $MessageDispatch
    ) {}

    public function __invoke(WbPackageMessage $message): void
    {
        $DeduplicatorExecuted = $this->deduplicator
            ->namespace('wildberries-package')
            ->deduplication([
                $message->getId(),
                self::class,
            ]);

        if($DeduplicatorExecuted->isExecuted())
        {
            return;
        }

        /* Получаем системную поставку, которой соответствует упаковка */
        $WbSupplyUid = $this->SupplyByPackage
            ->forPackageEvent($message->getEvent())
            ->find();

        if(false === ($WbSupplyUid instanceof WbSupplyUid))
        {
            return;
        }

        /* Получаем профиль пользователя и идентификатор поставки в качестве аттрибута */
        $UserProfileUid = $this->OpenWbSupply
            ->forSupply($WbSupplyUid)
            ->find();

        if(false === ($UserProfileUid instanceof UserProfileUid) || empty($UserProfileUid->getAttr()))
        {
            return;
        }

        /* Получаем открытую поставку Wildberries API */
        $wildberriesSupplyInfo = $this->WildberriesSupplyInfoRequest
            ->profile($UserProfileUid)
            ->withSupply($UserProfileUid->getAttr())
            ->getInfo();

        if(false === ($wildberriesSupplyInfo instanceof WildberriesSupplyInfoDTO))
        {
            return;
        }

        if($wildberriesSupplyInfo->isDone())
        {
            return;
        }

        /* Получаем упаковку, все её заказы со статусом NEW и идентификаторами заказов Wildberries */
        $orders = $this->OrdersIdentifierByPackage
            ->forPackageEvent($message->getEvent())
            ->onlyNewStatus()
            ->findAll();

        if(false === $orders || false === $orders->valid())
        {
            return;
        }

        /**
         * Добавляем в очереди заказы в открытую поставку селлере
         *
         * @var OrderUid $OrderUid
         */

        foreach($orders as $OrderUid)
        {
            /** Отправляем сокет с идентификатором заказа */
            $this->CentrifugoPublish
                ->addData(['identifier' => (string) $OrderUid])
                ->send('publish');

            $ConfirmOrderWildberriesMessage = new ConfirmOrderWildberriesMessage(
                $UserProfileUid,
                $OrderUid, // идентификатор системного заказа
                $UserProfileUid->getAttr(), // идентификатор поставки
                $OrderUid->getAttr(), // идентификатор заказа Wildberries
            );

            $this->MessageDispatch->dispatch(
                $ConfirmOrderWildberriesMessage,
                transport: (string) $UserProfileUid,
            );

            /** Добавляем задержку времени между отправкой сообщений */
            usleep(100000);
        }

        $DeduplicatorExecuted->save();
    }
}