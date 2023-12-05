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

namespace BaksDev\Wildberries\Products\UseCase\Cards\NewEdit;

use BaksDev\Core\Messenger\MessageDispatchInterface;
use BaksDev\Core\Validator\ValidatorCollectionInterface;
use BaksDev\Wildberries\Products\Entity\Cards\WbProductCard;
use BaksDev\Wildberries\Products\Messenger\Cards\WbProductCardMessage;
use Doctrine\ORM\EntityManagerInterface;

final class WbProductCardHandler
{

    private EntityManagerInterface $entityManager;
    private MessageDispatchInterface $messageDispatch;
    private ValidatorCollectionInterface $validatorCollection;

    public function __construct(
        EntityManagerInterface $entityManager,
        MessageDispatchInterface $messageDispatch,
        ValidatorCollectionInterface $validatorCollection
    )
    {
        $this->entityManager = $entityManager;
        $this->messageDispatch = $messageDispatch;
        $this->validatorCollection = $validatorCollection;
    }

    public function handle(
        WbProductCardDTO $command,
    ): string|WbProductCard
    {
        /**
         *  Валидация DTO
         */
        $this->validatorCollection->add($command);

        $WbProductCard = $this->entityManager->getRepository(WbProductCard::class)->findOneBy(
            ['product' => $command->getProduct()],
        );

        if(empty($WbProductCard))
        {
            $WbProductCard = new WbProductCard();
            $this->entityManager->persist($WbProductCard);
        }

        $WbProductCard->setEntity($command);
        $this->validatorCollection->add($WbProductCard);


        /**
         * Валидация всех объектов
         */
        if($this->validatorCollection->isInvalid())
        {
            return $this->validatorCollection->getErrorUniqid();
        }

        $this->entityManager->flush();

        /* Отправляем сообщение в шину */
        $this->messageDispatch->dispatch(
            message: new WbProductCardMessage($WbProductCard->getId()),
            transport: 'wildberries-products',
        );

        return $WbProductCard;
    }
}