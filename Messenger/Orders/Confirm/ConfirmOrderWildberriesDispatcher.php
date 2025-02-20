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

namespace BaksDev\Wildberries\Package\Messenger\Orders\Confirm;


use BaksDev\Core\Messenger\MessageDelay;
use BaksDev\Core\Messenger\MessageDispatchInterface;
use BaksDev\Wildberries\Orders\Api\PostWildberriesAddOrderToSupplyRequest;
use BaksDev\Wildberries\Orders\Api\WildberriesOrdersSticker\GetWildberriesOrdersStickerRequest;
use BaksDev\Wildberries\Package\Api\SupplyInfo\FindWildberriesSupplyInfoRequest;
use BaksDev\Wildberries\Package\Api\SupplyInfo\WildberriesSupplyInfoDTO;
use BaksDev\Wildberries\Package\Entity\Package\Orders\WbPackageOrder;
use BaksDev\Wildberries\Package\Type\Package\Status\WbPackageStatus\WbPackageStatusAdd;
use BaksDev\Wildberries\Package\Type\Package\Status\WbPackageStatus\WbPackageStatusError;
use BaksDev\Wildberries\Package\UseCase\Package\OrderStatus\UpdatePackageOrderStatusDTO;
use BaksDev\Wildberries\Package\UseCase\Package\OrderStatus\UpdatePackageOrderStatusHandler;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Добавляет заказ Wildberries в открытую поставки и прогревает кеш стикера
 */
#[AsMessageHandler(priority: 0)]
final readonly class ConfirmOrderWildberriesDispatcher
{
    public function __construct(
        #[Target('wildberriesPackageLogger')] private LoggerInterface $logger,
        private PostWildberriesAddOrderToSupplyRequest $PostWildberriesAddOrderToSupplyRequest,
        private GetWildberriesOrdersStickerRequest $WildberriesOrdersStickerRequest,
        private UpdatePackageOrderStatusHandler $UpdatePackageOrderStatusHandler,
        private FindWildberriesSupplyInfoRequest $WildberriesSupplyInfoRequest,
        private MessageDispatchInterface $MessageDispatch,
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
            $this->logger->critical(
                sprintf('wildberries-package: Ошибка при получении информации о поставке %s', $message->getSupply()),
                [$message, self::class.':'.__LINE__]
            );

            $this->MessageDispatch->dispatch(
                message: $message,
                stamps: [new MessageDelay('3 seconds')],
                transport: 'wildberries-package-low'
            );

            return;
        }

        /** Поставка закрыта */
        if($wildberriesSupplyInfo->isDone())
        {
            return;
        }

        /**
         * Добавляем заказ в открытую поставку
         */

        $UpdateOrderStatusDTO = new UpdatePackageOrderStatusDTO($message->getIdentifier());

        $isAdd = $this->PostWildberriesAddOrderToSupplyRequest
            ->profile($message->getProfile())
            ->withSupply($message->getSupply())
            ->withOrder($message->getOrder())
            ->add();

        if(false === $isAdd)
        {
            $this->logger->critical(
                sprintf('wildberries-package: Ошибка при добавлении заказа %s в поставку %s', $message->getOrder(), $message->getSupply()).
                'Пробуем повторить попытку через 3 сек',
                [$message, self::class.':'.__LINE__]
            );

            /** Пробуем повторить попытку через 3 сек */
            $this->MessageDispatch->dispatch(
                message: $message,
                stamps: [new MessageDelay('3 seconds')],
                transport: 'wildberries-package-low'
            );

            /** Помечаем статус заказа как с ошибкой */
            $UpdateOrderStatusDTO->setStatus(WbPackageStatusError::class);
            $this->UpdatePackageOrderStatusHandler->handle($UpdateOrderStatusDTO);

            return;
        }

        /**
         * Прогреваем кеш со стикерами
         */

        $this->WildberriesOrdersStickerRequest
            ->profile($message->getProfile())
            ->forOrderWb($message->getOrder()) // идентификатор заказа Wildberries
            ->getOrderSticker();

        /**
         * Обновляем статус заказа в упаковке
         */

        $UpdateOrderStatusDTO->setStatus(WbPackageStatusAdd::class);
        $WbPackageOrder = $this->UpdatePackageOrderStatusHandler->handle($UpdateOrderStatusDTO);

        if(false === ($WbPackageOrder instanceof WbPackageOrder))
        {
            $this->logger->critical(
                sprintf('wildberries-package: Ошибка %s при обновлении заказа в паковке', $WbPackageOrder),
                [$message, self::class.':'.__LINE__]
            );
        }
    }
}
