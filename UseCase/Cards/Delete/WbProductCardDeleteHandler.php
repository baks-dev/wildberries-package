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

namespace BaksDev\Wildberries\Products\UseCase\Cards\Delete;

use BaksDev\Core\Validator\ValidatorCollectionInterface;
use BaksDev\Products\Product\Entity\Event\ProductEvent;
use BaksDev\Products\Product\Entity\Product;
use BaksDev\Products\Product\Type\Id\ProductUid;
use BaksDev\Products\Product\UseCase\Admin\Delete\ProductDeleteDTO;
use BaksDev\Products\Product\UseCase\Admin\Delete\ProductDeleteHandler;
use BaksDev\Wildberries\Products\Entity\Cards\WbProductCard;
use Doctrine\ORM\EntityManagerInterface;

final class WbProductCardDeleteHandler
{
    private EntityManagerInterface $entityManager;
    private ProductDeleteHandler $productDeleteHandler;
    private ValidatorCollectionInterface $validatorCollection;

    public function __construct(
        EntityManagerInterface $entityManager,
        ProductDeleteHandler $productDeleteHandler,
        ValidatorCollectionInterface $validatorCollection
    )
    {
        $this->entityManager = $entityManager;
        $this->productDeleteHandler = $productDeleteHandler;
        $this->validatorCollection = $validatorCollection;
    }


    public function handle(WbProductCardDeleteDTO $command,): string|Product
    {
        /** Валидация DTO */
        $this->validatorCollection->add($command);

        /**
         * Получаем карточку Wildberries
         */

        $WbProductCard = $this->entityManager
            ->getRepository(WbProductCard::class)
            ->find($command->getId());

        if(false === $this->validatorCollection->add($WbProductCard, context: [__FILE__.':'.__LINE__]))
        {
            return $this->validatorCollection->getErrorUniqid();
        }


        /**
         * Получаем активное событие продукции
         */

        $qb = $this->entityManager->createQueryBuilder()
            ->from(Product::class, 'product')
            ->where('product.id = :product')
            ->setParameter('product', $WbProductCard->getProduct(), ProductUid::TYPE)
            ->select('event')
            ->leftJoin(
                ProductEvent::class,
                'event',
                'WITH',
                'event.id = product.event'
            );


        /** @var ProductEvent $ProductEvent */
        $ProductEvent = $qb->getQuery()->getOneOrNullResult();

        /** Нет продукции для удаления */
        if(false === $this->validatorCollection->add($ProductEvent, context: [__FILE__.':'.__LINE__]))
        {
            return $this->validatorCollection->getErrorUniqid();
        }


        /**
         * Удаляем карточку Product (модификатор del удалит карточку Wildberries)
         * @see DeleteCardByProductDelete
         */
        $ProductDeleteDTO = new ProductDeleteDTO();
        $ProductEvent->getDto($ProductDeleteDTO);

        return $this->productDeleteHandler->handle($ProductDeleteDTO);

    }
}