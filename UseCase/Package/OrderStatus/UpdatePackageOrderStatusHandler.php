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

namespace BaksDev\Wildberries\Package\UseCase\Package\OrderStatus;

use BaksDev\Core\Entity\AbstractHandler;
use BaksDev\Core\Messenger\MessageDispatchInterface;
use BaksDev\Core\Validator\ValidatorCollectionInterface;
use BaksDev\Files\Resources\Upload\File\FileUploadInterface;
use BaksDev\Files\Resources\Upload\Image\ImageUploadInterface;
use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;
use BaksDev\Wildberries\Orders\Repository\WbOrdersById\WbOrdersByIdInterface;
use BaksDev\Wildberries\Orders\Type\OrderStatus\Status\WbOrderStatusConfirm;
use BaksDev\Wildberries\Orders\UseCase\Command\Status\StatusWbOrderDTO;
use BaksDev\Wildberries\Orders\UseCase\Command\Status\StatusWbOrderHandler;
use BaksDev\Wildberries\Package\Entity\Package\Orders\WbPackageOrder;
use BaksDev\Wildberries\Package\Entity\Supply\Const\WbSupplyConst;
use BaksDev\Wildberries\Package\Entity\Supply\Event\WbSupplyEvent;
use BaksDev\Wildberries\Package\Entity\Supply\WbSupply;
use BaksDev\Wildberries\Package\Messenger\Supply\WbSupplyMessage;
use BaksDev\Wildberries\Package\Type\Package\Status\WbPackageStatus\WbPackageStatusNew;
use Doctrine\ORM\EntityManagerInterface;
use DomainException;

final class UpdatePackageOrderStatusHandler extends AbstractHandler
{

    private WbOrdersByIdInterface $wbOrdersById;
    private StatusWbOrderHandler $statusWbOrderHandler;


    public function __construct(
        EntityManagerInterface $entityManager,
        MessageDispatchInterface $messageDispatch,
        ValidatorCollectionInterface $validatorCollection,
        ImageUploadInterface $imageUpload,
        FileUploadInterface $fileUpload,

        WbOrdersByIdInterface $wbOrdersById,
        StatusWbOrderHandler $statusWbOrderHandler
    )
    {
        parent::__construct($entityManager, $messageDispatch, $validatorCollection, $imageUpload, $fileUpload);

        $this->wbOrdersById = $wbOrdersById;
        $this->statusWbOrderHandler = $statusWbOrderHandler;
    }


    public function handle(UpdatePackageOrderStatusDTO $command): string|WbPackageOrder
    {
        /** Валидация WbSupplyOpenDTO  */
        $this->validatorCollection->add($command);

        $this->entityManager->clear();

        /** Только упаковки со статусом NEW можно изменить статус на ADD или ERROR */
        $WbPackageOrder = $this->entityManager
            ->getRepository(WbPackageOrder::class)
            ->findOneBy(['id' => $command->getId(), 'status' => WbPackageStatusNew::STATUS]);

        if(false === $this->validatorCollection->add($WbPackageOrder, context: [__FILE__.':'.__LINE__]))
        {
            return $this->validatorCollection->getErrorUniqid();
        }

        $WbPackageOrder->setEntity($command);

        /** Валидация всех объектов */
        if($this->validatorCollection->isInvalid())
        {
            return $this->validatorCollection->getErrorUniqid();
        }

        $this->entityManager->flush();


        /**
         * Изменяем статус заказа Wildberries на Confirm (Добавлен к поставке, на сборке)
         */

        $WbOrdersEvent = $this->wbOrdersById->getWbOrderByOrderUidOrNullResult($command->getId());

        if($WbOrdersEvent)
        {
            /** @var StatusWbOrderDTO $StatusWbOrderDTO */
            $StatusWbOrderDTO = $WbOrdersEvent->getDto(StatusWbOrderDTO::class);
            $StatusWbOrderDTO->setStatus(WbOrderStatusConfirm::class);
            $this->statusWbOrderHandler->handle($StatusWbOrderDTO);
        }

        return $WbPackageOrder;
    }
}