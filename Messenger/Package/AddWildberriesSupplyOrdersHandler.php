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

namespace BaksDev\Wildberries\Package\Messenger\Package;

use BaksDev\Centrifugo\Server\Publish\CentrifugoPublishInterface;
use BaksDev\Core\Deduplicator\DeduplicatorInterface;
use BaksDev\Orders\Order\Type\Id\OrderUid;
use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;
use BaksDev\Wildberries\Orders\Api\WildberriesAddOrderToSupplyRequest;
use BaksDev\Wildberries\Package\Api\SupplyInfo\WildberriesSupplyInfoRequest;
use BaksDev\Wildberries\Package\Entity\Package\Orders\WbPackageOrder;
use BaksDev\Wildberries\Package\Repository\Package\OrdersIdentifierByPackage\OrdersIdentifierByPackageInterface;
use BaksDev\Wildberries\Package\Repository\Package\SupplyByPackage\SupplyByPackageInterface;
use BaksDev\Wildberries\Package\Repository\Supply\OpenWbSupply\OpenWbSupplyInterface;
use BaksDev\Wildberries\Package\Type\Package\Status\WbPackageStatus\WbPackageStatusAdd;
use BaksDev\Wildberries\Package\Type\Package\Status\WbPackageStatus\WbPackageStatusError;
use BaksDev\Wildberries\Package\UseCase\Package\OrderStatus\UpdatePackageOrderStatusDTO;
use BaksDev\Wildberries\Package\UseCase\Package\OrderStatus\UpdatePackageOrderStatusHandler;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(priority: 0)]
final readonly class AddWildberriesSupplyOrdersHandler
{
    public function __construct(
        #[Target('wildberriesPackageLogger')] private LoggerInterface $logger,
        private OpenWbSupplyInterface $OpenWbSupply,
        private OrdersIdentifierByPackageInterface $OrdersIdentifierByPackage,
        private WildberriesAddOrderToSupplyRequest $AddOrderToSupplyRequest,
        private WildberriesSupplyInfoRequest $WildberriesSupplyInfoRequest,
        private UpdatePackageOrderStatusHandler $orderStatusHandler,
        private SupplyByPackageInterface $SupplyByPackage,
        private CentrifugoPublishInterface $CentrifugoPublish,
        private DeduplicatorInterface $Deduplicator
    ) {}

    /**
     * Метод добавляет заказы в указанную поставку Wildberries (Api) и обновляет статусы в системной упаковке
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

        /* Получаем открытую поставку Wildberries API */
        $wildberriesSupplyInfo = $this->WildberriesSupplyInfoRequest
            ->profile($UserProfileUid)
            ->withSupply($UserProfileUid->getAttr())
            ->getInfo();

        if($wildberriesSupplyInfo->isDone())
        {
            return;
        }

        /* Получаем упаковку, все её заказы со статусом NEW и идентификаторами заказов Wildberries */
        $orders = $this->OrdersIdentifierByPackage
            ->forPackageEvent($message->getEvent())
            ->onlyNew()
            ->findAll();

        if(false === $orders || false === $orders->valid())
        {
            return;
        }


        // Присваиваем первоначальные настройки
        $AddOrderToSupplyRequest = $this->AddOrderToSupplyRequest
            ->profile($UserProfileUid)
            ->withSupply($UserProfileUid->getAttr());

        /**
         * Добавляем по очереди заказы в открытую поставку
         * @var OrderUid $OrderUid
         */

        $this->Deduplicator
            ->namespace('wildberries-package')
            ->expiresAfter('1 hour');

        foreach($orders as $OrderUid)
        {
            $Deduplicator = $this->Deduplicator
                ->deduplication([$OrderUid, self::class]);

            if($Deduplicator->isExecuted())
            {
                return;
            }

            $UpdateOrderStatusDTO = new UpdatePackageOrderStatusDTO($OrderUid);

            $this->logger->info('Добавляем заказ в открытую поставку Wildberries',
                [
                    'supply' => $UserProfileUid->getAttr(),
                    'order' => $OrderUid->getAttr(),
                    self::class.':'.__LINE__
                ]);

            $isAdd = $AddOrderToSupplyRequest
                ->withOrder($OrderUid->getAttr())
                ->add();

            /**
             * Применяем статус добавления заказа к поставке
             */

            $WbPackageStatus = $isAdd ? WbPackageStatusAdd::class : WbPackageStatusError::class;
            $UpdateOrderStatusDTO->setStatus($WbPackageStatus);

            $WbPackageOrder = $this->orderStatusHandler->handle($UpdateOrderStatusDTO);

            if(false === ($WbPackageOrder instanceof WbPackageOrder))
            {
                $this->logger->critical(
                    'Ошибка при добавления заказа в поставку Wildberries',
                    [$WbPackageOrder, self::class.':'.__LINE__]
                );
            }

            $Deduplicator->save();
        }

        $DeduplicatorExecuted->save();

        /** Отправляем сокет с идентификатором упаковки */
        $this->CentrifugoPublish
            ->addData(['identifier' => (string) $message->getId()]) // ID упаковки
            ->send('publish');
    }
}