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
use BaksDev\Wildberries\Orders\Api\PostWildberriesAddOrderToSupplyRequest;
use BaksDev\Wildberries\Package\Api\SupplyInfo\FindWildberriesSupplyInfoRequest;
use BaksDev\Wildberries\Package\Api\SupplyInfo\WildberriesSupplyInfoDTO;
use BaksDev\Wildberries\Package\Entity\Package\Orders\WbPackageOrder;
use BaksDev\Wildberries\Package\Repository\Package\OrdersIdentifierByPackage\OrdersIdentifierByPackageInterface;
use BaksDev\Wildberries\Package\Repository\Package\SupplyByPackage\SupplyByPackageInterface;
use BaksDev\Wildberries\Package\Repository\Supply\OpenWbSupply\OpenWbSupplyInterface;
use BaksDev\Wildberries\Package\Type\Package\Status\WbPackageStatus\WbPackageStatusAdd;
use BaksDev\Wildberries\Package\Type\Package\Status\WbPackageStatus\WbPackageStatusError;
use BaksDev\Wildberries\Package\Type\Supply\Id\WbSupplyUid;
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
        private PostWildberriesAddOrderToSupplyRequest $AddOrderToSupplyRequest,
        private FindWildberriesSupplyInfoRequest $WildberriesSupplyInfoRequest,
        private UpdatePackageOrderStatusHandler $orderStatusHandler,
        private SupplyByPackageInterface $SupplyByPackage,
        private CentrifugoPublishInterface $CentrifugoPublish,
        private DeduplicatorInterface $deduplicator
    )
    {
        $this->deduplicator->namespace('wildberries-package');
    }

    /**
     * Метод добавляет заказы в указанную поставку Wildberries (Api) и обновляет статусы в системной упаковке
     */
    public function __invoke(WbPackageMessage $message): void
    {
        $DeduplicatorExecuted = $this->deduplicator
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

        // Присваиваем первоначальные настройки Request
        $AddOrderToSupplyRequest = $this->AddOrderToSupplyRequest
            ->profile($UserProfileUid)
            ->withSupply($UserProfileUid->getAttr());

        /**
         * Добавляем по очереди заказы в открытую поставку
         * @var OrderUid $OrderUid
         */

        foreach($orders as $OrderUid)
        {
            $Deduplicator = $this->deduplicator
                ->deduplication([$OrderUid, self::class]);

            if($Deduplicator->isExecuted())
            {
                return;
            }

            /** Отправляем сокет с идентификатором заказа */
            $this->CentrifugoPublish
                ->addData(['identifier' => (string) $OrderUid])
                ->send('publish');

            $UpdateOrderStatusDTO = new UpdatePackageOrderStatusDTO($OrderUid);

            $this->logger->info(
                sprintf(
                    'Добавляем заказ %s в открытую поставку Wildberries %s',
                    $OrderUid->getAttr(), $UserProfileUid->getAttr()
                ), [self::class.':'.__LINE__]);

            $isAdd = $AddOrderToSupplyRequest
                ->withOrder($OrderUid->getAttr())
                ->add();

            /**
             * Применяем статус добавления заказа к поставке
             * Приступаем к отправке Честных знаков @see AddWildberriesSupplySignHandler
             */

            $WbPackageStatus = $isAdd ? WbPackageStatusAdd::class : WbPackageStatusError::class;
            $UpdateOrderStatusDTO->setStatus($WbPackageStatus);

            $WbPackageOrder = $this->orderStatusHandler->handle($UpdateOrderStatusDTO);

            if(false === ($WbPackageOrder instanceof WbPackageOrder))
            {
                $this->logger->critical(
                    sprintf('wildberries-package: Ошибка %s при добавления заказа в поставку Wildberries', $WbPackageOrder),
                    [$message, self::class.':'.__LINE__]
                );
            }

            $Deduplicator->save();
        }

        $DeduplicatorExecuted->save();
    }
}