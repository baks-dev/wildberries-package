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

namespace BaksDev\Wildberries\Products\Messenger\Cards;

use BaksDev\Core\Cache\AppCacheInterface;
use BaksDev\Core\Type\Modify\Modify\ModifyActionDelete;
use BaksDev\Core\Type\Modify\Modify\ModifyActionNew;
use BaksDev\Core\Type\Modify\Modify\ModifyActionUpdate;
use BaksDev\Products\Product\Entity\Modify\ProductModify;
use BaksDev\Products\Product\Messenger\ProductMessage;
use BaksDev\Wildberries\Products\Entity\Cards\WbProductCard;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class DeleteCardByProductDelete
{
    private AppCacheInterface $cache;
    private LoggerInterface $messageDispatchLogger;
    private EntityManagerInterface $entityManager;

    public function __construct(
        AppCacheInterface $cache,
        LoggerInterface $messageDispatchLogger,
        EntityManagerInterface $entityManager
    ) {
        $this->cache = $cache;
        $this->messageDispatchLogger = $messageDispatchLogger;
        $this->entityManager = $entityManager;
    }


    /**
     * Удаляем карточку Wildberries если удалена карточка Product
     */
    public function __invoke(ProductMessage $message)
    {

        $this->entityManager->clear();

       /** Проверяем, что событие карточки - Удалено */
        $ProductModify = $this->entityManager->getRepository(ProductModify::class)->find($message->getEvent());

        if(!$ProductModify->equals(ModifyActionDelete::class))
        {
            return;
        }

        /** Получаем карточку товара */
        $WbProductCard = $this->entityManager->getRepository(WbProductCard::class)->findOneBy(['product' => $message->getId()]);

        if(!$WbProductCard)
        {
            return;
        }

        $this->entityManager->remove($WbProductCard);
        $this->entityManager->flush();

        $this->messageDispatchLogger->info(sprintf('Удалили карточку Wildberries (product: %s)', $message->getId()), [__FILE__.':'.__LINE__]);

        /* Чистим кеш модуля */
        $this->cache->init('wildberries-products')->clear();
        $this->messageDispatchLogger->info('Очистили кеш WildberriesProducts', [__FILE__.':'.__LINE__]);

    }
}