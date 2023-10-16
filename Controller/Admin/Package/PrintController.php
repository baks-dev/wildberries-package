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
use BaksDev\Wildberries\Package\Entity\Package\WbPackage;
use BaksDev\Wildberries\Package\Entity\Supply\Wildberries\WbSupplyWildberries;
use BaksDev\Wildberries\Package\Repository\Package\OrderByPackage\OrderByPackageInterface;
use BaksDev\Wildberries\Package\Type\Package\Event\WbPackageEventUid;
use BaksDev\Wildberries\Package\UseCase\Package\Print\PrintWbPackageDTO;
use BaksDev\Wildberries\Products\Repository\Barcode\WbBarcodeProperty\WbBarcodePropertyByProductEventInterface;
use BaksDev\Wildberries\Products\Repository\Barcode\WbBarcodeSettings\WbBarcodeSettingsInterface;
use chillerlan\QRCode\QRCode;
use Picqer\Barcode\BarcodeGeneratorSVG;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Exception\RouteNotFoundException;

#[AsController]
#[RoleSecurity('ROLE_WB_PACKAGE_PRINT')]
final class PrintController extends AbstractController
{
    /**
     * Печать штрихкодов и QR заказов в упаковке
     */
    #[Route('/admin/wb/packages/print/{id}', name: 'admin.package.print', methods: ['GET', 'POST'])]
    public function printer(
        #[MapEntity] WbPackage $wbPackage,
        CentrifugoPublishInterface $CentrifugoPublish,
        OrderByPackageInterface $orderByPackage,
        WbBarcodeSettingsInterface $barcodeSettings,
        WbBarcodePropertyByProductEventInterface $wbBarcodeProperty,
        ProductDetailByUidInterface $productDetail,
        MessageDispatchInterface $messageDispatch,
    ): Response
    {

        /* Скрываем у все пользователей заказ для печати */
        $CentrifugoPublish
            ->addData(['identifier' => (string) $wbPackage->getId()]) // ID продукта
            ->send('remove');

        /* Получаем все заказы в упаковке  */
        $orders = $orderByPackage->getOrdersPackage($wbPackage->getEvent());

        if(!$orders)
        {
            throw new RouteNotFoundException('Orders Not Found');
        }

        /* Получаем продукцию для иллюстрации */
        $order = current($orders);

        if(!$order)
        {
            throw new RouteNotFoundException('Order Not Found');
        }

        $Product = $productDetail->fetchProductDetailByEventAssociative(
            new ProductEventUid($order['product_event']),
            new ProductOfferUid($order['product_offer']),
            new ProductVariationUid($order['product_variation']),
        );

        if(!$Product)
        {
            throw new RouteNotFoundException('Product Not Found');
        }

        /* Генерируем боковые стикеры */
        $BarcodeGenerator = new BarcodeGeneratorSVG();
        $barcode = $BarcodeGenerator->getBarcode(
            $order['barcode'],
            $BarcodeGenerator::TYPE_CODE_128,
            2,
            60
        );



        /* Получаем настройки бокового стикера */
        $ProductUid = new ProductUid($Product['main']);
        $BarcodeSettings = $barcodeSettings->findWbBarcodeSettings($ProductUid) ?: null;
        $property = $BarcodeSettings ? $wbBarcodeProperty->getPropertyCollection(new ProductEventUid($order['product_event'])) : [];

        /* Отправляем сообщение в шину и отмечаем принт */
        $messageDispatch->dispatch(
            message: new PrintWbPackageDTO($wbPackage->getId()),
             transport: (string) $this->getProfileUid(),
        );

        return $this->render(
            [
                'item' => $orders,
                'barcode' => base64_encode($barcode),
                'counter' => $BarcodeSettings['counter'] ?? 1,
                'settings' => $BarcodeSettings,
                'card' => $Product,
                'property' => $property,
            ]
        );
    }
}
