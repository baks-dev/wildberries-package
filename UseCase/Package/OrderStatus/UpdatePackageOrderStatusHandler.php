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

namespace BaksDev\Wildberries\Package\UseCase\Package\OrderStatus;

use BaksDev\Core\Entity\AbstractHandler;
use BaksDev\Wildberries\Package\Entity\Package\Orders\WbPackageOrder;
use BaksDev\Wildberries\Package\Type\Package\Status\WbPackageStatus\WbPackageStatusAdd;

final class UpdatePackageOrderStatusHandler extends AbstractHandler
{
    public function handle(UpdatePackageOrderStatusDTO $command): bool|WbPackageOrder
    {

        $this->setCommand($command);

        /**
         * Только заказ в упаковке со статусом «NEW» либо «ERROR» можно изменить на «ADD» или «ERROR»
         *
         * @var WbPackageOrder $WbPackageOrder
         */

        $this->clear();

        $WbPackageOrder = $this
            ->getRepository(WbPackageOrder::class)
            ->find($command->getId());

        /**
         * Заказ %s не найден в упаковке
         */
        if(false === ($WbPackageOrder instanceof WbPackageOrder))
        {
            return false;
        }

        /** Заказ уже добавлен в упаковку */
        if($WbPackageOrder->isPackageStatusEquals(WbPackageStatusAdd::class))
        {
            return $WbPackageOrder;
        }

        $WbPackageOrder->setEntity($command);
        $this->flush();

        return $WbPackageOrder;

    }
}