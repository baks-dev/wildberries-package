<?php
/*
 *  Copyright 2024.  Baks.dev <admin@baks.dev>
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

namespace BaksDev\Wildberries\Package\Controller\Admin\Package;


use BaksDev\Centrifugo\Server\Publish\CentrifugoPublishInterface;
use BaksDev\Core\Controller\AbstractController;
use BaksDev\Core\Listeners\Event\Security\RoleSecurity;
use BaksDev\Core\Messenger\MessageDispatchInterface;
use BaksDev\Products\Product\Repository\ProductDetail\ProductDetailByUidInterface;
use BaksDev\Products\Product\Type\Event\ProductEventUid;
use BaksDev\Products\Product\Type\Id\ProductUid;
use BaksDev\Products\Product\Type\Offers\Id\ProductOfferUid;
use BaksDev\Products\Product\Type\Offers\Variation\Id\ProductVariationUid;
use BaksDev\Wildberries\Package\Entity\Supply\WbSupply;
use BaksDev\Wildberries\Package\Repository\Package\PrintOrdersPackageSupply\PrintOrdersPackageSupplyInterface;
use BaksDev\Wildberries\Package\Type\Package\Id\WbPackageUid;
use BaksDev\Wildberries\Package\UseCase\Package\Print\PrintWbPackageDTO;
use BaksDev\Wildberries\Products\Repository\Barcode\WbBarcodeProperty\WbBarcodePropertyByProductEventInterface;
use BaksDev\Wildberries\Products\Repository\Barcode\WbBarcodeSettings\WbBarcodeSettingsInterface;
use chillerlan\QRCode\QRCode;
use Picqer\Barcode\BarcodeGeneratorSVG;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Exception\RouteNotFoundException;

#[AsController]
#[RoleSecurity('ROLE_WB_PACKAGE_PRINT')]
final class PrintsController extends AbstractController
{
    /**
     * Печать всех упаковок, которые не напечатаны
     */
    #[Route('/admin/wb/packages/prints/{id}', name: 'admin.package.prints', methods: ['GET', 'POST'])]
    public function printer(
        #[MapEntity] WbSupply $wbSupply,
        PrintOrdersPackageSupplyInterface $printOrdersPackageSupply,
        CentrifugoPublishInterface $CentrifugoPublish,
        WbBarcodePropertyByProductEventInterface $wbBarcodeProperty,
        MessageDispatchInterface $messageDispatch,
    ): Response
    {
        $orders = $printOrdersPackageSupply->fetchAllPrintOrdersPackageSupplyAssociative($wbSupply->getId());

        if(!$orders)
        {
            return $this->redirectToRoute('wildberries-package:admin.supply.detail', ['id' => $wbSupply->getId()]);
        }

        /** Получаем все заказы и их упаковки, которые не напечатаны */
        $prints = $printOrdersPackageSupply->getPrinterOrdersPackageSupply($wbSupply->getId());

        foreach($prints as $key => $order)
        {
            /* Скрываем у все пользователей заказ для печати */
            $CentrifugoPublish
                ->addData(['identifier' => $order['package_id']]) // ID продукта
                ->send('remove');

            /* Генерируем боковые стикеры */
            $BarcodeGenerator = new BarcodeGeneratorSVG();
            $prints[$key]['barcode_sticker'] = base64_encode(
                $BarcodeGenerator->getBarcode(
                    $order['barcode'],
                    $BarcodeGenerator::TYPE_CODE_128,
                    2,
                    60
                ));

            $prints[$key]['barcode_property'] = null;

            if($order['barcode_counter'])
            {
                $prints[$key]['barcode_property'] = $wbBarcodeProperty->getPropertyCollection($order['product_event']);
            }

            /* Отправляем сообщение в шину и отмечаем принт */
            $messageDispatch->dispatch(
                message: new PrintWbPackageDTO(new WbPackageUid($order['package_id'])),
                transport: (string) $this->getProfileUid(),
            );
        }

        return $this->render([
            'item' => $prints,
            'orders' => $orders
        ]);
    }
}
