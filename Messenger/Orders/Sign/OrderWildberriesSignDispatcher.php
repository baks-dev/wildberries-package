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

namespace BaksDev\Wildberries\Package\Messenger\Orders\Sign;


use BaksDev\Core\Deduplicator\DeduplicatorInterface;
use BaksDev\Core\Doctrine\DBALQueryBuilder;
use BaksDev\Core\Messenger\MessageDelay;
use BaksDev\Core\Messenger\MessageDispatchInterface;
use BaksDev\Materials\Sign\BaksDevMaterialsSignBundle;
use BaksDev\Materials\Sign\Repository\MaterialSignByOrder\MaterialSignByOrderRepository;
use BaksDev\Products\Sign\BaksDevProductsSignBundle;
use BaksDev\Products\Sign\Repository\ProductSignByOrder\ProductSignByOrderRepository;
use BaksDev\Wildberries\Orders\Api\PostWildberriesSgtinRequest;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Отправляет Честные знаки на указанные в упаковке заказы Wildberries (Api)
 */
#[AsMessageHandler(priority: 0)]
final readonly class OrderWildberriesSignDispatcher
{
    public function __construct(
        #[Target('wildberriesPackageLogger')] private LoggerInterface $logger,
        private DBALQueryBuilder $DBALQueryBuilder,
        private PostWildberriesSgtinRequest $PostWildberriesSgtinRequest,
        private MessageDispatchInterface $MessageDispatch,
        private DeduplicatorInterface $Deduplicator
    ) {}


    public function __invoke(OrderWildberriesSignMessage $message): void
    {
        /** Получаем честные знаки по заказу в сырье */

        $this->PostWildberriesSgtinRequest
            ->profile($message->getProfile())
            ->forOrder($message->getOrder());

        /** Получаем честные знаки по заказу в сырье */

        if(class_exists(BaksDevMaterialsSignBundle::class))
        {
            $MaterialSignByOrder = new MaterialSignByOrderRepository($this->DBALQueryBuilder);

            $materialSign = $MaterialSignByOrder->forOrder($message->getIdentifier())->findAll();

            if($materialSign)
            {
                foreach($materialSign as $sign)
                {
                    $this->PostWildberriesSgtinRequest->sgtin($sign['code_string']);

                    $this->logger->info(
                        sprintf('%s: отправляем сырьевой честный знак %s', $message->getOrder(), $sign['code_string']),
                        [$message, self::class.':'.__LINE__]
                    );
                }
            }
        }


        /** Получаем честные знаки по заказу в продукции */

        if(class_exists(BaksDevProductsSignBundle::class))
        {
            $ProductSignByOrder = new ProductSignByOrderRepository($this->DBALQueryBuilder);

            $productSign = $ProductSignByOrder->forOrder($message->getIdentifier())->findAll();

            if($productSign)
            {
                foreach($productSign as $sign)
                {
                    $this->PostWildberriesSgtinRequest->sgtin($sign['code_string']);

                    $this->logger->info(
                        sprintf('%s: отправляем продуктовый честный знак %s', $message->getOrder(), $sign['code_string']),
                        [$message, self::class.':'.__LINE__]
                    );
                }
            }
        }

        $isUpdate = $this->PostWildberriesSgtinRequest->update();

        if(false === $isUpdate)
        {
            $Deduplicator = $this->Deduplicator
                ->namespace('wildberries-package')
                ->expiresAfter('5 minutes')
                ->deduplication([$message, self::class]);

            if($Deduplicator->isExecuted())
            {
                /** Если повтор через минуту не сработал - следовательно честного знака не нашлось */
                $this->logger->critical(
                    sprintf('wildberries-package: Ошибка при отправке честных знаков заказа %s', $message->getOrder()),
                    [$message, self::class.':'.__LINE__],
                );

                return;
            }

            $Deduplicator->save();

            /**
             * Пробуем повторно отправить сообщение через минуту
             */

            $this->logger->warning(
                sprintf('wildberries-package: Пробуем повторно через минуту отправить честный знак по заказу %s', $message->getOrder()),
                [$message, self::class.':'.__LINE__],
            );

            $this->MessageDispatch->dispatch(
                message: $message,
                stamps: [new MessageDelay('1 minute')],
                transport: (string) $message->getProfile(),
            );
        }
    }
}
