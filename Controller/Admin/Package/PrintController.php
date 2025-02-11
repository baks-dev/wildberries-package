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

namespace BaksDev\Wildberries\Package\Controller\Admin\Package;


use BaksDev\Barcode\Writer\BarcodeFormat;
use BaksDev\Barcode\Writer\BarcodeType;
use BaksDev\Barcode\Writer\BarcodeWrite;
use BaksDev\Centrifugo\Server\Publish\CentrifugoPublishInterface;
use BaksDev\Core\Controller\AbstractController;
use BaksDev\Core\Listeners\Event\Security\RoleSecurity;
use BaksDev\Core\Messenger\MessageDispatchInterface;
use BaksDev\Products\Product\Repository\ProductDetail\ProductDetailByUidInterface;
use BaksDev\Products\Product\Type\Event\ProductEventUid;
use BaksDev\Products\Product\Type\Id\ProductUid;
use BaksDev\Wildberries\Orders\Api\WildberriesOrdersSticker\GetWildberriesOrdersStickerRequest;
use BaksDev\Wildberries\Package\Entity\Package\WbPackage;
use BaksDev\Wildberries\Package\Repository\Package\OrderByPackage\OrderByPackageInterface;
use BaksDev\Wildberries\Package\UseCase\Package\Print\PrintWbPackageMessage;
use BaksDev\Wildberries\Products\Repository\Barcode\WbBarcodeProperty\WbBarcodePropertyByProductEventInterface;
use BaksDev\Wildberries\Products\Repository\Barcode\WbBarcodeSettings\WbBarcodeSettingsInterface;
use RuntimeException;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
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
        BarcodeWrite $BarcodeWrite,
        GetWildberriesOrdersStickerRequest $WildberriesOrdersStickerRequest
    ): Response
    {

        /* Скрываем у все пользователей заказ для печати */
        $CentrifugoPublish
            ->addData(['identifier' => (string) $wbPackage->getId()]) // ID продукта
            ->addData(['profile' => $this->getCurrentProfileUid()])
            ->send('remove');

        /* Получаем все заказы в упаковке  */
        $orders = $orderByPackage
            ->forPackageEvent($wbPackage->getEvent())
            ->findAll();

        if(empty($orders))
        {
            throw new RouteNotFoundException('Orders Not Found');
        }

        $stickers = null;

        /**
         * Получаем стикеры заказа Wildberries
         */

        foreach($orders as $order)
        {
            $stickers[$order['orders']] = $WildberriesOrdersStickerRequest
                ->profile($this->getProfileUid())
                ->forOrderWb($order['number'])
                ->getOrderSticker();
        }

        /** Получаем честные знаки на заказ из материалов */


        /**
         * Получаем продукцию для штрихкода
         */
        $order = current($orders);

        $Product = $productDetail
            ->event($order['product_event'])
            ->offer($order['product_offer'])
            ->variation($order['product_variation'])
            ->modification($order['product_modification'])
            ->find();

        if(!$Product)
        {
            throw new RouteNotFoundException('Product Not Found');
        }

        /**
         * Генерируем штрихкод продукции (один на все заказы)
         */

        $barcode = $BarcodeWrite
            ->text($Product['product_barcode'])
            ->type(BarcodeType::Code128)
            ->format(BarcodeFormat::SVG)
            ->generate(implode(DIRECTORY_SEPARATOR, ['barcode', 'test']));

        if($barcode === false)
        {
            /**
             * Проверить права на исполнение
             * chmod +x /home/bundles.baks.dev/vendor/baks-dev/barcode/Writer/Generate
             * chmod +x /home/bundles.baks.dev/vendor/baks-dev/barcode/Reader/Decode
             * */
            throw new RuntimeException('Barcode write error');
        }

        $Code = $BarcodeWrite->render();
        $BarcodeWrite->remove();

        /**
         * Получаем настройки бокового стикера
         */

        $ProductUid = new ProductUid($Product['main']);
        $BarcodeSettings = $barcodeSettings->findWbBarcodeSettings($ProductUid);

        /** Применяем настройки по умолчанию */
        if(false === $BarcodeSettings)
        {
            $BarcodeSettings = null;
            $BarcodeSettings['offer'] = false;
            $BarcodeSettings['variation'] = false;
            $BarcodeSettings['modification'] = false;

        }

        $property = $BarcodeSettings ? $wbBarcodeProperty->getPropertyCollection(new ProductEventUid($order['product_event'])) : [];

        /** Отправляем сообщение в шину и отмечаем принт упаковки */
        $messageDispatch->dispatch(
            message: new PrintWbPackageMessage($wbPackage->getId()),
            transport: 'wildberries-package',
        );

        return $this->render(
            [
                'item' => $orders,
                'barcode' => $Code,
                'counter' => $BarcodeSettings['counter'] ?? 1,
                'settings' => $BarcodeSettings,
                'card' => $Product,
                'property' => $property,
                'stickers' => $stickers
            ]
        );
    }
}
