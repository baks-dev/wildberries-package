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
 *
 */

declare(strict_types=1);

namespace BaksDev\Wildberries\Package\Messenger\Orders\OrderPackage;

use BaksDev\Core\Deduplicator\DeduplicatorInterface;
use BaksDev\Core\Messenger\MessageDelay;
use BaksDev\Core\Messenger\MessageDispatchInterface;
use BaksDev\Orders\Order\Entity\Event\OrderEvent;
use BaksDev\Orders\Order\Messenger\OrderMessage;
use BaksDev\Orders\Order\Repository\CurrentOrderEvent\CurrentOrderEventInterface;
use BaksDev\Orders\Order\Type\Status\OrderStatus\Collection\OrderStatusPackage;
use BaksDev\Wildberries\Manufacture\BaksDevWildberriesManufactureBundle;
use BaksDev\Wildberries\Manufacture\Messenger\AddOrdersPackageByPartCompleted\AddOrdersPackageByPartCompletedMessage;
use BaksDev\Wildberries\Orders\Type\DeliveryType\TypeDeliveryFbsWildberries;
use BaksDev\Wildberries\Package\Entity\Supply\WbSupply;
use BaksDev\Wildberries\Package\Repository\Supply\NewOrOpenWbSupply\NewOrOpenWbSupplyRepository;
use BaksDev\Wildberries\Package\Type\Supply\Id\WbSupplyUid;
use BaksDev\Wildberries\Package\Type\Supply\Status\WbSupplyStatus;
use BaksDev\Wildberries\Package\Type\Supply\Status\WbSupplyStatus\WbSupplyStatusNew;
use BaksDev\Wildberries\Package\UseCase\Supply\New\WbSupplyNewDTO;
use BaksDev\Wildberries\Package\UseCase\Supply\New\WbSupplyNewHandler;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;

/**
 * Создает новую поставку при поступлении заказа от Wildberries
 */
#[Autoconfigure(shared: false)]
#[AsMessageHandler(priority: 2)]
final readonly class CreateNewWbSupplyWhenOrderPackageDispatcher
{
    public function __construct(
        #[Target('wildberriesPackageLogger')] private LoggerInterface $logger,
        private DeduplicatorInterface $deduplicator,
        private MessageDispatchInterface $messageDispatch,
        private CurrentOrderEventInterface $currentOrderEventRepository,
        private NewOrOpenWbSupplyRepository $newOrOpenWbSupplyRepository,
        private WbSupplyNewHandler $wbSupplyNewHandler,
    ) {}

    public function __invoke(OrderMessage $message): void
    {
        if(false === class_exists(BaksDevWildberriesManufactureBundle::class))
        {
            return;
        }

        /** Дедубликатор по идентификатору заказа */
        $Deduplicator = $this->deduplicator
            ->namespace('wildberries-package')
            ->deduplication([
                $message,
                self::class,
            ]);

        if(true === $Deduplicator->isExecuted())
        {
            return;
        }

        /** Активное событие заказа */
        $OrderEvent = $this->currentOrderEventRepository
            ->forOrder($message->getId())
            ->find();

        if(false === ($OrderEvent instanceof OrderEvent))
        {
            $this->logger->critical(
                message: 'ozon-orders: Не найдено событие OrderEvent',
                context: [self::class.':'.__LINE__, var_export($message, true)],
            );

            return;
        }

        /**
         * Если тип доставки заказа НЕ Wildberries Fbs «Доставка службой Wildberries» - Завершаем обработчик
         */
        if(false === $OrderEvent->isDeliveryTypeEquals(TypeDeliveryFbsWildberries::TYPE))
        {
            $Deduplicator->save();
            return;
        }

        /**
         * Если у заказа нет токена маркетплейса - Завершаем обработчик
         */
        if(false === ($OrderEvent->getOrderTokenIdentifier() instanceof Uuid))
        {
            return;
        }

        /** Если статус заказа НЕ Package «Упаковка заказов» - Завершаем обработчик */
        if(false === $OrderEvent->isStatusEquals(OrderStatusPackage::class))
        {
            $Deduplicator->save();
            return;
        }

        /** Проверяем существование НОВОЙ поставки Wildberries по профилю и токену */
        $WbSupplyUid = $this->newOrOpenWbSupplyRepository
            ->forProfile($OrderEvent->getOrderProfile())
            ->forToken($OrderEvent->getOrderTokenIdentifier())
            ->find();

        /** Если поставка создана - пробуем добавить продукты из заказа в поставку */
        if(true === $WbSupplyUid instanceof WbSupplyUid)
        {
            $WbSupplyStatus = new WbSupplyStatus($WbSupplyUid->getProperty());

            /**
             * Если статус поставки New - еще не получен идентификатор поставки от WB API - ждем получения идентификатора
             */

            if(true === $WbSupplyStatus->equals(WbSupplyStatusNew::STATUS))
            {
                $this->logger->warning(
                    message: 'не получен идентификатор поставки от WB API',
                    context: [
                        self::class.':'.__LINE__,
                        'wbSupply' => (string) $WbSupplyUid,
                        'order_number' => $OrderEvent->getPostingNumber(),
                    ],
                );

                $this->messageDispatch->dispatch(
                    message: $message,
                    stamps: [new MessageDelay('5 seconds')],
                    transport: 'wildberries-package',
                );

                return;
            }

            /**
             * Если статус поставки Open Добавлен в поставку Wildberries - пробуем добавить продукт в открытую поставку
             */
            $AddOrdersPackageByPartCompletedMessage = new AddOrdersPackageByPartCompletedMessage(
                profile: $OrderEvent->getOrderProfile(),
                supply: $WbSupplyUid,
            );

            $AddOrdersPackageByPartCompletedMessage->addOrder($OrderEvent->getMain());

            $this->logger->info(
                message: sprintf(
                    'Добавляем продукты из заказа %s в открытую системную поставку',
                    $OrderEvent->getPostingNumber()
                ),
                context: [
                    self::class.':'.__LINE__,
                    'wbSupply' => (string) $WbSupplyUid,
                    'order_number' => $OrderEvent->getPostingNumber(),
                ],
            );

            $this->messageDispatch->dispatch(
                message: $AddOrdersPackageByPartCompletedMessage,
                transport: 'wildberries-package',
            );

            return;

        }

        /**
         * Открываем поставку со статусом NEW на указанный профиль и токен
         */

        $WbSupplyNewDTO = new WbSupplyNewDTO(
            profile: $OrderEvent->getOrderProfile(),
            token: $OrderEvent->getOrderTokenIdentifier()
        );

        $WbSupply = $this->wbSupplyNewHandler->handle($WbSupplyNewDTO);

        if(false === ($WbSupply instanceof WbSupply))
        {
            $this->logger->critical(
                message: 'wildberries-package: Ошибка при открытии новой поставки Wildberries',
                context: [$WbSupply, self::class.':'.__LINE__],
            );

            return;
        }

        $this->logger->info(
            message: 'создали поставку Wildberries в статусе New',
            context: [
                self::class.':'.__LINE__,
                'profile' => $OrderEvent->getOrderProfile(),
                'token' => $OrderEvent->getOrderTokenIdentifier(),
            ],
        );

        /**
         * Добавляем заказ в открытую системную поставку
         */

        $AddOrdersPackageByPartCompletedMessage = new AddOrdersPackageByPartCompletedMessage(
            profile: $OrderEvent->getOrderProfile(),
            supply: $WbSupply->getId(),
        );

        $AddOrdersPackageByPartCompletedMessage->addOrder($OrderEvent->getMain());

        $this->logger->info(
            message: sprintf(
                'Добавляем продукты из заказа %s в открытую системную поставку',
                $OrderEvent->getPostingNumber()
            ),
            context: [
                self::class.':'.__LINE__,
                'wbSupply' => (string) $WbSupply->getId(),
                'order_number' => $OrderEvent->getPostingNumber(),
            ],
        );

        $this->messageDispatch->dispatch(
            message: $AddOrdersPackageByPartCompletedMessage,
            transport: 'wildberries-package',
        );

        $Deduplicator->save();
    }
}
