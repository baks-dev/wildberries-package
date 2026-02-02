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

namespace BaksDev\Wildberries\Package\Messenger\Orders\Confirm;


use BaksDev\Core\Messenger\MessageDelay;
use BaksDev\Core\Messenger\MessageDispatchInterface;
use BaksDev\Wildberries\Orders\Api\FindAllWildberriesOrdersStatusFbsRequest;
use BaksDev\Wildberries\Orders\Api\PostWildberriesAddOrderToSupplyRequest;
use BaksDev\Wildberries\Package\Api\SupplyInfo\FindWildberriesSupplyInfoRequest;
use BaksDev\Wildberries\Package\Api\SupplyInfo\WildberriesSupplyInfoDTO;
use BaksDev\Wildberries\Package\Entity\Package\Orders\WbPackageOrder;
use BaksDev\Wildberries\Package\Messenger\Orders\OrderSticker\WildberriesOrdersStickerMessage;
use BaksDev\Wildberries\Package\Messenger\Orders\Sign\OrderWildberriesSignMessage;
use BaksDev\Wildberries\Package\Repository\Package\DeleteOrderPackage\DeleteOrderPackageInterface;
use BaksDev\Wildberries\Package\Repository\Package\ExistOrdersByPackage\ExistOrdersByPackageInterface;
use BaksDev\Wildberries\Package\Repository\Supply\ExistOpenSupplyProfile\ExistOpenSupplyProfileInterface;
use BaksDev\Wildberries\Package\Type\Package\Status\WbPackageStatus\WbPackageStatusAdd;
use BaksDev\Wildberries\Package\Type\Package\Status\WbPackageStatus\WbPackageStatusError;
use BaksDev\Wildberries\Package\UseCase\Package\OrderStatus\UpdatePackageOrderStatusDTO;
use BaksDev\Wildberries\Package\UseCase\Package\OrderStatus\UpdatePackageOrderStatusHandler;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Добавляет заказ Wildberries в открытую поставку в селлере и прогревает кеш стикера
 */
#[AsMessageHandler(priority: 0)]
final readonly class ConfirmOrderWildberriesDispatcher
{
    public function __construct(
        #[Target('wildberriesPackageLogger')] private LoggerInterface $logger,
        private PostWildberriesAddOrderToSupplyRequest $PostWildberriesAddOrderToSupplyRequest,
        private UpdatePackageOrderStatusHandler $UpdatePackageOrderStatusHandler,
        private FindWildberriesSupplyInfoRequest $WildberriesSupplyInfoRequest,
        private MessageDispatchInterface $MessageDispatch,
        private ExistOrdersByPackageInterface $ExistOrdersByPackage,
        private FindAllWildberriesOrdersStatusFbsRequest $FindAllWildberriesOrdersStatusFbsRequest,
        private DeleteOrderPackageInterface $DeleteOrderPackage,
        private ExistOpenSupplyProfileInterface $ExistOpenSupplyProfile,
    ) {}

    public function __invoke(ConfirmOrderWildberriesMessage $message): void
    {
        /* Получаем открытую поставку Wildberries */
        $wildberriesSupplyInfo = $this->WildberriesSupplyInfoRequest
            ->profile($message->getProfile())
            ->withSupply($message->getSupply())
            ->getInfo();

        if(false === ($wildberriesSupplyInfo instanceof WildberriesSupplyInfoDTO))
        {
            /** Проверяем, имеется ли соответствующая открытая поставка */

            $isSupplyExistNewOrOpen = $this->ExistOpenSupplyProfile
                ->forProfile($message->getProfile())
                ->forIdentifier($message->getSupply())
                ->isExistNewOrOpenSupply();

            if($isSupplyExistNewOrOpen)
            {
                $this->logger->critical(
                    sprintf('wildberries-package: Ошибка при получении информации о поставке %s', $message->getSupply()),
                    [$message, self::class.':'.__LINE__],
                );

                $this->MessageDispatch->dispatch(
                    message: $message,
                    stamps: [new MessageDelay('3 seconds')],
                    transport: 'wildberries-package-low',
                );
            }

            return;
        }

        /** Поставка закрыта */
        if($wildberriesSupplyInfo->isDone())
        {
            return;
        }


        /** Проверяем что заказ имеется в упаковке и не был удален пользователем */

        $isOrderExist = $this->ExistOrdersByPackage
            ->forOrder($message->getIdentifier())
            ->exist();

        if(false === $isOrderExist)
        {
            return;
        }

        /**
         * Добавляем заказ в открытую поставку в селлере
         */

        $UpdateOrderStatusDTO = new UpdatePackageOrderStatusDTO($message->getIdentifier());

        $isAdd = $this->PostWildberriesAddOrderToSupplyRequest
            ->profile($message->getProfile())
            ->withSupply($message->getSupply())
            ->withOrder($message->getOrder())
            ->add();

        if(false === $isAdd)
        {
            /**
             * Проверяем статус добавленного заказа
             */

            $isCancel = $this->FindAllWildberriesOrdersStatusFbsRequest
                ->profile($message->getProfile())
                ->addOrder($message->getOrder())
                ->findOrderCancel();

            if(false === $isCancel)
            {
                $this->logger->critical(
                    sprintf(
                        'wildberries-package: Ошибка при добавлении заказа %s в поставку %s',
                        $message->getOrder(), $message->getSupply(),
                    ).
                    'Пробуем повторить попытку через 5 сек',
                    [$message, self::class.':'.__LINE__],
                );

                /** Пробуем повторить попытку через 3 сек */
                $this->MessageDispatch->dispatch(
                    message: $message,
                    stamps: [new MessageDelay('5 seconds')],
                    transport: 'wildberries-package-low',
                );

                /** Помечаем статус заказа как с ошибкой */
                $UpdateOrderStatusDTO->setStatus(WbPackageStatusError::class);
                $this->UpdatePackageOrderStatusHandler->handle($UpdateOrderStatusDTO);

                return;
            }

            /** Если заказ отменен - запускаем отмену */

            $this->DeleteOrderPackage
                ->forOrder($message->getIdentifier())
                ->delete();

            $this->logger->warning(
                sprintf('wildberries-package: Удалили отмененный заказ %s из упаковки поставки %s', $message->getOrder(), $message->getSupply()),
                [$message, self::class.':'.__LINE__],
            );

            return;
        }

        /**
         * Прогреваем кеш со стикерами
         */

        $WildberriesOrdersStickerMessage = new WildberriesOrdersStickerMessage(
            $message->getProfile(),
            $message->getOrder(),
        );

        $this->MessageDispatch->dispatch(
            message: $WildberriesOrdersStickerMessage,
            transport: (string) $message->getProfile(),
        );

        /**
         * Отправляем Честные знаки на указанные в упаковке заказы Wildberries
         * отправляем через 30 сек на случай обработки заказов и поиску кизов
         * расчетно на обработку 30 заказов в секунду
         */

        $OrderWildberriesSignMessage = new OrderWildberriesSignMessage(
            $message->getProfile(),
            $message->getIdentifier(), // идентификатор системного заказа
            $message->getOrder(), // идентификатор заказа Wildberries
        );


        $this->MessageDispatch->dispatch(
            message: $OrderWildberriesSignMessage,
            stamps: [new MessageDelay('30 seconds')],
            transport: (string) $message->getProfile(),
        );

        /**
         * Обновляем статус заказа Wildberries в упаковке
         */

        $UpdateOrderStatusDTO->setStatus(WbPackageStatusAdd::class);
        $WbPackageOrder = $this->UpdatePackageOrderStatusHandler->handle($UpdateOrderStatusDTO);

        if(false === ($WbPackageOrder instanceof WbPackageOrder))
        {
            $this->logger->critical(
                sprintf('wildberries-package: Ошибка %s при обновлении заказа в упаковке', $WbPackageOrder),
                [self::class.':'.__LINE__, var_export($message, true)],
            );
        }
    }
}
