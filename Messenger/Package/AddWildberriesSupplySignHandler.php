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

use BaksDev\Core\Deduplicator\DeduplicatorInterface;
use BaksDev\Core\Doctrine\DBALQueryBuilder;
use BaksDev\Materials\Sign\BaksDevMaterialsSignBundle;
use BaksDev\Materials\Sign\Repository\MaterialSignByOrder\MaterialSignByOrderRepository;
use BaksDev\Orders\Order\Type\Id\OrderUid;
use BaksDev\Products\Sign\BaksDevProductsSignBundle;
use BaksDev\Products\Sign\Repository\ProductSignByOrder\ProductSignByOrderRepository;
use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;
use BaksDev\Wildberries\Orders\Api\PostWildberriesSgtinRequest;
use BaksDev\Wildberries\Package\Api\SupplyInfo\FindWildberriesSupplyInfoRequest;
use BaksDev\Wildberries\Package\Repository\Package\OrdersIdentifierByPackage\OrdersIdentifierByPackageInterface;
use BaksDev\Wildberries\Package\Repository\Package\SupplyByPackage\SupplyByPackageInterface;
use BaksDev\Wildberries\Package\Repository\Supply\OpenWbSupply\OpenWbSupplyInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Отправляет Честные знаки на указанные в упаковке заказы Wildberries (Api)
 */
#[AsMessageHandler(priority: -5)]
final readonly class AddWildberriesSupplySignHandler
{
    public function __construct(
        #[Target('wildberriesPackageLogger')] private LoggerInterface $logger,
        private OpenWbSupplyInterface $OpenWbSupply,
        private OrdersIdentifierByPackageInterface $OrdersIdentifierByPackage,
        private FindWildberriesSupplyInfoRequest $WildberriesSupplyInfoRequest,
        private SupplyByPackageInterface $SupplyByPackage,
        private PostWildberriesSgtinRequest $PostWildberriesSgtinRequest,
        private DeduplicatorInterface $deduplicator,
        private DBALQueryBuilder $DBALQueryBuilder,
    )
    {
        $this->deduplicator->namespace('wildberries-package');
    }


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
            ->onlyAddSupply() // только если поставка открыта
            ->findAll();

        if(false === $orders || false === $orders->valid())
        {
            return;
        }

        /**
         * Отправляем по очереди честные знаки
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

            $this->PostWildberriesSgtinRequest
                ->profile($UserProfileUid)
                ->forOrder($OrderUid->getAttr());

            /** Получаем честные знаки по заказу в сырье */

            if(class_exists(BaksDevMaterialsSignBundle::class))
            {
                $MaterialSignByOrder = new MaterialSignByOrderRepository($this->DBALQueryBuilder);

                $materialSign = $MaterialSignByOrder->forOrder($OrderUid)->findAll();

                if($materialSign)
                {
                    foreach($materialSign as $sign)
                    {
                        $this->PostWildberriesSgtinRequest->sgtin($sign['code_string']);

                        $this->logger->info(
                            sprintf('%s: отправляем честные знак %s', $OrderUid->getAttr(), $sign['code_string']),
                            [$message, self::class.':'.__LINE__]
                        );
                    }
                }
            }

            /** Получаем честные знаки по заказу в продукции */

            if(class_exists(BaksDevProductsSignBundle::class))
            {
                $ProductSignByOrder = new ProductSignByOrderRepository($this->DBALQueryBuilder);

                $productSign = $ProductSignByOrder->forOrder($OrderUid)->findAll();

                if($productSign)
                {
                    foreach($productSign as $sign)
                    {
                        $this->PostWildberriesSgtinRequest->sgtin($sign['code_string']);

                        $this->logger->info(
                            sprintf('%s: отправляем честные знак %s', $OrderUid->getAttr(), $sign['code_string']),
                            [$OrderUid->getAttr(), self::class.':'.__LINE__]
                        );
                    }
                }
            }

            $isUpdate = $this->PostWildberriesSgtinRequest->update();

            if(false === $isUpdate)
            {
                $this->logger->critical(
                    sprintf('wildberries-package: Ошибка при отправке честных знаков заказа %s', $OrderUid->getAttr()),
                    [$message, self::class.':'.__LINE__]
                );
            }
        }

        $DeduplicatorExecuted->save();
    }
}