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

namespace BaksDev\Wildberries\Package\Messenger\Orders\OrdersComplete;


use BaksDev\Centrifugo\Server\Publish\CentrifugoPublishInterface;
use BaksDev\Core\Deduplicator\DeduplicatorInterface;
use BaksDev\DeliveryTransport\UseCase\Admin\Package\Completed\ProductStock\CompletedProductStockDTO;
use BaksDev\DeliveryTransport\UseCase\Admin\Package\Completed\ProductStock\CompletedProductStockHandler;
use BaksDev\Products\Stocks\Entity\Stock\ProductStock;
use BaksDev\Products\Stocks\Repository\ProductStocksByOrder\ProductStocksByOrderInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[Autoconfigure(shared: false)]
#[AsMessageHandler(priority: 0)]
final readonly class OrdersCompleteByCloseWbSupplyDispatcher
{
    public function __construct(
        #[Target('wildberriesPackageLogger')] private LoggerInterface $logger,
        private ProductStocksByOrderInterface $ProductStocksByOrderRepository,
        private CompletedProductStockHandler $CompletedProductStockHandler,
        private CentrifugoPublishInterface $publish,
        private DeduplicatorInterface $deduplicator,
    ) {}

    public function __invoke(OrdersCompleteByCloseWbSupplyMessage $message): void
    {
        $Deduplicator = $this->deduplicator
            ->namespace('wildberries-package')
            ->deduplication([$message->getId(), self::class]);

        if($Deduplicator->isExecuted())
        {
            return;
        }

        $stocks = $this->ProductStocksByOrderRepository
            ->onOrder($message->getId())
            ->findAll();

        if(empty($stocks))
        {
            return;
        }

        foreach($stocks as $ProductStockEvent)
        {
            /** Скрываем идентификатор у всех пользователей */

            $this->publish
                ->addData([
                    'identifier' => (string) $ProductStockEvent->getMain(), // складскую заявку
                    'profile' => false, // Скрывает у всех
                    'context' => self::class.':'.__LINE__,
                ])
                ->send('remove');

            $this->publish
                ->addData([
                    'identifier' => (string) $message->getId(), // заказ
                    'profile' => false, // Скрывает у всех
                    'context' => self::class.':'.__LINE__,
                ])
                ->send('remove');


            /**
             * Обновляем складскую заявку
             */

            $CompletedProductStockDTO = new CompletedProductStockDTO();
            $ProductStockEvent->getDto($CompletedProductStockDTO);

            $ProductStock = $this->CompletedProductStockHandler->handle($CompletedProductStockDTO);

            if(false === ($ProductStock instanceof ProductStock))
            {
                $this->logger->critical(
                    sprintf('products-stocks: Ошибка %s при обновлении складской заявки %s на статус Completed «Выдан по месту назначения»',
                        $ProductStock,
                        $ProductStockEvent->getNumber(),
                    ),
                    [self::class.':'.__LINE__, var_export($message, true)],
                );

                return;
            }

            $this->logger->info(
                sprintf('%s: «Выдан по месту назначения» после закрытия поставки Wildberries',
                    $ProductStockEvent->getNumber(),
                ),
                [self::class.':'.__LINE__],
            );
        }


        $Deduplicator->save();
    }
}
