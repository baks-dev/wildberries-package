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

namespace BaksDev\Wildberries\Package\UseCase\Package\DeleteOrder;


use BaksDev\Core\Entity\AbstractHandler;
use BaksDev\Core\Messenger\MessageDispatchInterface;
use BaksDev\Core\Validator\ValidatorCollectionInterface;
use BaksDev\Files\Resources\Upload\File\FileUploadInterface;
use BaksDev\Files\Resources\Upload\Image\ImageUploadInterface;
use BaksDev\Wildberries\Package\Entity\Package\Event\WbPackageEvent;
use BaksDev\Wildberries\Package\Entity\Package\Orders\WbPackageOrder;
use BaksDev\Wildberries\Package\Entity\Package\WbPackage;
use BaksDev\Wildberries\Package\Repository\Package\OrdersByPackage\OrdersByPackageInterface;
use BaksDev\Wildberries\Package\UseCase\Package\DeletePackage\WbPackageDeleteDTO;
use Doctrine\ORM\EntityManagerInterface;

final class WbPackageOrderDeleteHandler extends AbstractHandler
{

    public function __construct(
        EntityManagerInterface $entityManager,
        MessageDispatchInterface $messageDispatch,
        ValidatorCollectionInterface $validatorCollection,
        ImageUploadInterface $imageUpload,
        FileUploadInterface $fileUpload,
        private readonly OrdersByPackageInterface $OrdersByPackage,
    )
    {
        parent::__construct($entityManager, $messageDispatch, $validatorCollection, $imageUpload, $fileUpload);
    }

    /** @see */
    public function handle(WbPackageOrderDeleteDTO $command): false|string|WbPackageOrder
    {

        $this->setCommand($command);

        $WbPackageEvent = $command->getEvent();

        $WbPackageOrder = $this->getRepository(WbPackageOrder::class)->find([
            'id' => $command->getId(),
            'event' => $WbPackageEvent,
        ]);


        /* Удалить WbPackageOrder */

        if(false === ($WbPackageOrder instanceof WbPackageOrder))
        {
            return false;
        }

        $this->remove($WbPackageOrder);


        /* Найти все WbPackageOrder'ы связанные с event */
        $ordersByPackage = $this->OrdersByPackage->forPackageEvent($command->getEvent())->findAll();

        /* Если нет WbPackageOrder'ы то удалить корень - WbPackage */
        if(false === $ordersByPackage || false === $ordersByPackage->valid())
        {

            $command = new WbPackageDeleteDTO();
            $command->setId($WbPackageEvent);
            $this->setCommand($command);

            $this->preEventRemove(WbPackage::class, WbPackageEvent::class);

            if($this->validatorCollection->isInvalid())
            {
                return $this->validatorCollection->getErrorUniqid();
            }

        }

        $this->flush();

        $this->messageDispatch->addClearCacheOther('wildberries-package');

        return $WbPackageOrder;
    }
}