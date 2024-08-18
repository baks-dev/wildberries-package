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

namespace BaksDev\Wildberries\Package\Messenger\Package;

use BaksDev\Centrifugo\Server\Publish\CentrifugoPublishInterface;
use BaksDev\Orders\Order\Type\Id\OrderUid;
use BaksDev\Wildberries\Api\Token\Orders\WildberriesAddOrderToSupply;
use BaksDev\Wildberries\Api\Token\Supplies\SupplyInfo\WildberriesSupplyInfo;
use BaksDev\Wildberries\Package\Entity\Package\Orders\WbPackageOrder;
use BaksDev\Wildberries\Package\Repository\Package\CountOrdersSupply\CountOrdersSupplyInterface;
use BaksDev\Wildberries\Package\Repository\Package\OrderByPackage\OrderByPackageInterface;
use BaksDev\Wildberries\Package\Repository\Supply\OpenWbSupply\OpenWbSupplyInterface;
use BaksDev\Wildberries\Package\Type\Package\Status\WbPackageStatus\WbPackageStatusAdd;
use BaksDev\Wildberries\Package\Type\Package\Status\WbPackageStatus\WbPackageStatusError;
use BaksDev\Wildberries\Package\UseCase\Package\OrderStatus\UpdatePackageOrderStatusDTO;
use BaksDev\Wildberries\Package\UseCase\Package\OrderStatus\UpdatePackageOrderStatusHandler;
use DomainException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class AddWildberriesSupplyOrdersHandler
{
    private CountOrdersSupplyInterface $countOrdersSupply;

    private OpenWbSupplyInterface $openWbSupply;
    private WildberriesSupplyInfo $wildberriesSupplyInfo;
    private OrderByPackageInterface $orderByPackage;
    private WildberriesAddOrderToSupply $addOrderToSupply;
    private UpdatePackageOrderStatusHandler $orderStatusHandler;
    private LoggerInterface $logger;
    private CentrifugoPublishInterface $CentrifugoPublish;

    public function __construct(
        CountOrdersSupplyInterface $countOrdersSupply,
        OpenWbSupplyInterface $openWbSupply,
        WildberriesSupplyInfo $wildberriesSupplyInfo,
        OrderByPackageInterface $orderByPackage,
        WildberriesAddOrderToSupply $addOrderToSupply,
        UpdatePackageOrderStatusHandler $orderStatusHandler,
        LoggerInterface $wildberriesPackageLogger,
        CentrifugoPublishInterface $CentrifugoPublish
    )
    {
        $this->countOrdersSupply = $countOrdersSupply;
        $this->openWbSupply = $openWbSupply;
        $this->wildberriesSupplyInfo = $wildberriesSupplyInfo;
        $this->orderByPackage = $orderByPackage;
        $this->addOrderToSupply = $addOrderToSupply;
        $this->orderStatusHandler = $orderStatusHandler;
        $this->logger = $wildberriesPackageLogger;
        $this->CentrifugoPublish = $CentrifugoPublish;
    }

    /**
     * Метод добавляет заказы в указанную поставку Wildberries (Api) и обновляет статусы в системной упаковке
     */
    public function __invoke(WbPackageMessage $message): void
    {

        /* Получаем системную поставку, которой соответствует упаковка */
        $WbSupplyUid = $this->countOrdersSupply->findSupplyByPackage($message->getEvent());

        if(!$WbSupplyUid)
        {
            return;
        }

        /* Получаем профиль пользователя и идентификатор поставки в качестве аттрибута */
        $UserProfileUid = $this->openWbSupply->getWbSupply($WbSupplyUid);

        if(!$UserProfileUid)
        {
            return;
        }

        /* Получаем открытую поставку Wildberries API */
        $wildberriesSupplyInfo = $this->wildberriesSupplyInfo
            ->profile($UserProfileUid)
            ->withSupply($UserProfileUid->getAttr())
            ->getInfo();

        if($wildberriesSupplyInfo->isDone())
        {
            return;
        }

        /* Получаем упаковку, все её заказы со статусом NEW и идентификаторами заказов Wildberries */
        $orders = $this->orderByPackage->fetchAllOrderByPackageAssociative($message->getEvent());

        $addOrderToSupply = $this->addOrderToSupply
            ->profile($UserProfileUid)
            ->withSupply($UserProfileUid->getAttr());

        /**
         * Добавляем по очереди заказы в открытую поставку
         * @var OrderUid $order
         */
        foreach($orders as $order)
        {
            $UpdateOrderStatusDTO = new UpdatePackageOrderStatusDTO($order);

            $this->logger->info(
                'Добавляем заказ в открытую поставку Wildberries',
                [
                    'supply' => $UserProfileUid->getAttr(),
                    'order' => $order->getAttr(),
                    self::class.':'.__LINE__
                ]);

            try
            {
                /* Добавляем заказ в открытую поставку Wildberries */
                $addOrderToSupply
                    ->withOrder($order->getAttr())
                    ->add();

                $UpdateOrderStatusDTO->setStatus(WbPackageStatusAdd::class);

            }
            catch(DomainException $exception)
            {
                $this->logger->critical(
                    $exception->getMessage(),
                    [
                        'code' => $exception->getCode(),
                        self::class.':'.__LINE__
                    ]);

                $UpdateOrderStatusDTO->setStatus(WbPackageStatusError::class);
            }

            $WbPackageOrder = $this->orderStatusHandler->handle($UpdateOrderStatusDTO);

            if(!$WbPackageOrder instanceof WbPackageOrder)
            {
                $this->logger->critical(
                    'Ошибка при добавлении заказа',
                    [
                        'code' => $WbPackageOrder,
                        self::class.':'.__LINE__
                    ]);

                throw new DomainException('Ошибка при добавлении заказа');
            }
        }


        /** Отправляем сокет сокет с идентификатором упаковки */
        $this->CentrifugoPublish
            ->addData(['identifier' => (string) $message->getId()]) // ID упаковки
            ->send('publish');
    }
}