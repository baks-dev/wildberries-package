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

namespace BaksDev\Wildberries\Package\Messenger\Orders;

use BaksDev\Core\Deduplicator\DeduplicatorInterface;
use BaksDev\Orders\Order\Type\Id\OrderUid;
use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;
use BaksDev\Wildberries\Orders\Api\WildberriesOrdersSticker\GetWildberriesOrdersStickerRequest;
use BaksDev\Wildberries\Package\Messenger\Package\WbPackageMessage;
use BaksDev\Wildberries\Package\Repository\Package\OrdersIdentifierByPackage\OrdersIdentifierByPackageInterface;
use BaksDev\Wildberries\Package\Repository\Package\SupplyByPackage\SupplyByPackageInterface;
use BaksDev\Wildberries\Package\Repository\Supply\OpenWbSupply\OpenWbSupplyInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class UpdateOrdersStickerHandler
{
    public function __construct(
        #[Target('wildberriesOrdersLogger')] private LoggerInterface $logger,
        private OrdersIdentifierByPackageInterface $OrdersIdentifierByPackage,
        private SupplyByPackageInterface $SupplyByPackage,
        private OpenWbSupplyInterface $OpenWbSupply,
        private GetWildberriesOrdersStickerRequest $WildberriesOrdersStickerRequest,
        private DeduplicatorInterface $Deduplicator
    ) {}

    /**
     * При обновлении статуса заказа Confirm (Добавлен к поставке, на сборке) получаем и обновляет стикер
     */
    public function __invoke(WbPackageMessage $message): void
    {
        $DeduplicatorExecuted = $this->Deduplicator
            ->namespace('wildberries-package')
            ->deduplication([
                $message->getId(),
                self::class
            ]);

        if($DeduplicatorExecuted->isExecuted())
        {
            return;
        }

        /* Получаем системную поставку, которой соответствует упаковка */
        $WbSupplyUid = $this->SupplyByPackage
            ->forPackageEvent($message->getEvent())
            ->find();

        if(false === $WbSupplyUid)
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

        /* Получаем упаковку, все её заказы со статусом ADD и идентификаторами заказов Wildberries в качестве аттрибута */
        $orders = $this->OrdersIdentifierByPackage
            ->forPackageEvent($message->getEvent())
            ->onlyAddSupply()
            ->findAll();

        if(false === $orders || false === $orders->valid())
        {
            return;
        }

        /** @var OrderUid $OrderUid */
        foreach($orders as $OrderUid)
        {
            /** Прогреваем кеш со стикерами */
            $this->WildberriesOrdersStickerRequest
                ->profile($UserProfileUid)
                ->forOrderWb($OrderUid->getAttr()) // идентификатор заказа Wildberries
                ->getOrderSticker();
        }

        $DeduplicatorExecuted->save();
    }
}