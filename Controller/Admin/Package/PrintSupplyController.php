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
use BaksDev\Core\Type\UidType\ParamConverter;
use BaksDev\Products\Product\Repository\ProductDetail\ProductDetailByUidInterface;
use BaksDev\Wildberries\Orders\Api\WildberriesOrdersSticker\GetWildberriesOrdersStickerRequest;
use BaksDev\Wildberries\Package\Repository\Package\OrdersByPackage\OrdersByPackageInterface;
use BaksDev\Wildberries\Package\Repository\Package\PackageBySupply\PackageBySupplyInterface;
use BaksDev\Wildberries\Package\Type\Package\Id\WbPackageUid;
use BaksDev\Wildberries\Package\Type\Supply\Id\WbSupplyUid;
use BaksDev\Wildberries\Package\UseCase\Package\Print\PrintWbPackageMessage;
use BaksDev\Wildberries\Products\Repository\Barcode\WbBarcodeSettings\WbBarcodeSettingsInterface;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Exception\RouteNotFoundException;

#[AsController]
#[RoleSecurity('ROLE_WB_PACKAGE_PRINT')]
final class PrintSupplyController extends AbstractController
{
    private ?array $stickers = null;

    private ?array $products = null;

    private ?array $barcodes = null;

    /**
     * Печать всех упаковок, которые не напечатаны
     */
    #[Route('/admin/wb/packages/print/supply/{id}', name: 'admin.package.print.supply', methods: ['GET', 'POST'])]
    public function printers(
        #[ParamConverter(WbSupplyUid::class)] WbSupplyUid $WbSupplyUid,
        PackageBySupplyInterface $PackageBySupply,
        OrdersByPackageInterface $OrderByPackage,
        ProductDetailByUidInterface $productDetail,
        WbBarcodeSettingsInterface $barcodeSettings,
        GetWildberriesOrdersStickerRequest $WildberriesOrdersStickerRequest,
        CentrifugoPublishInterface $CentrifugoPublish,
        MessageDispatchInterface $messageDispatch,
        BarcodeWrite $BarcodeWrite,
    ): Response
    {
        /** Получаем все упаковки на печати в поставке */

        $packages = $PackageBySupply
            ->forSupply($WbSupplyUid)
            ->onlyPrint()
            ->findAll();

        if(false === ($packages || $packages->valid()))
        {
            return new Response('Package not found', Response::HTTP_NOT_FOUND);
        }


        $Product = null;
        $property = [];

        $packages = iterator_to_array($packages);

        $printers = null;

        /** @var WbPackageUid $WbPackageUid */
        foreach($packages as $WbPackageUid)
        {
            /** Получаем все заказы в упаковке  */
            $orders[(string) $WbPackageUid] = $OrderByPackage
                ->forPackageEvent($WbPackageUid->getAttr())
                ->findAll();

            if(empty($orders))
            {
                continue;
            }

            /** Скрываем у все пользователей упаковку для печати */
            $CentrifugoPublish
                ->addData(['identifier' => (string) $WbPackageUid]) // ID упаковки
                ->send('remove');

            foreach($orders[(string) $WbPackageUid] as $order)
            {
                $this->stickers[$order['order']] = $WildberriesOrdersStickerRequest
                    ->profile($this->getProfileUid())
                    ->forOrderWb($order['number'])
                    ->getOrderSticker();
            }

            $order = current($orders[(string) $WbPackageUid]);

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
             * Генерируем штрихкод продукции (один на все заказы в упаковке)
             */

            if(isset($this->barcodes[(string) $WbPackageUid]))
            {
                continue;
            }


            $this->products[(string) $WbPackageUid] = $Product;

            $barcode = $BarcodeWrite
                ->text($Product['product_barcode'])
                ->type(BarcodeType::Code128)
                ->format(BarcodeFormat::SVG)
                ->generate();


            if($barcode === false)
            {
                /**
                 * Проверить права на исполнение
                 * chmod +x /home/bundles.baks.dev/vendor/baks-dev/barcode/Writer/Generate
                 * chmod +x /home/bundles.baks.dev/vendor/baks-dev/barcode/Reader/Decode
                 * */
                throw new RuntimeException('Barcode write error');
            }

            $this->barcodes[(string) $WbPackageUid] = $BarcodeWrite->render();
            $BarcodeWrite->remove();

            $printers[] = $WbPackageUid;

        }

        /**
         * Получаем настройки бокового стикера
         */

        $BarcodeSettings = $Product ? $barcodeSettings
            ->forProduct($Product['main'])
            ->find() : false;

        $render = $this->render(
            [
                'packages' => $packages,
                'orders' => $orders,
                'settings' => $BarcodeSettings,
                'card' => $this->products,
                'property' => $property,
                'barcodes' => $this->barcodes,
                'stickers' => $this->stickers
            ],
            routingName: 'admin.package',
            file: '/print/print.html.twig'
        );


        /** Отправляем сообщение в шину и отмечаем принт упаковок */

        foreach($printers as $printer)
        {
            $messageDispatch->dispatch(
                message: new PrintWbPackageMessage($printer),
                transport: 'wildberries-package',
            );
        }

        return $render;
    }
}
