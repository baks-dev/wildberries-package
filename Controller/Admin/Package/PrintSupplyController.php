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
use BaksDev\Wildberries\Package\Messenger\Orders\Confirm\ConfirmOrderWildberriesMessage;
use BaksDev\Wildberries\Package\Repository\Package\OrdersByPackage\OrdersByPackageInterface;
use BaksDev\Wildberries\Package\Repository\Package\PackageBySupply\PackageBySupplyInterface;
use BaksDev\Wildberries\Package\Type\Package\Id\WbPackageUid;
use BaksDev\Wildberries\Package\Type\Supply\Id\WbSupplyUid;
use BaksDev\Wildberries\Package\UseCase\Package\Print\PrintWbPackageMessage;
use BaksDev\Wildberries\Products\Repository\Barcode\WbBarcodeSettings\WbBarcodeSettingsInterface;
use RuntimeException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Exception\RouteNotFoundException;

#[AsController]
#[RoleSecurity('ROLE_WB_PACKAGE_PRINT')]
final class PrintSupplyController extends AbstractController
{
    private ?array $packages = null;

    private ?array $orders = null;

    private ?array $stickers = null;

    private ?array $matrix = null;

    private ?array $products = null;

    private ?array $barcodes = null;

    private ?array $settings = null;

    /**
     * Печать всех упаковок, которые не напечатаны
     */
    #[Route('/admin/wb/packages/print/supply/{id}', name: 'admin.package.print.supply', methods: ['GET', 'POST'])]
    public function printers(
        #[ParamConverter(WbSupplyUid::class)] WbSupplyUid $WbSupplyUid,
        Request $request,
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
            ->toArray();

        if(false === $packages)
        {
            return new Response('Package not found', Response::HTTP_NOT_FOUND);
        }

        $printers = null;

        foreach($packages as $PackageBySupplyResult)
        {
            $WbPackageUid = (string) $PackageBySupplyResult->getId();
            $this->packages[] = $WbPackageUid;

            /** Получаем все заказы в упаковке */
            $orders = $OrderByPackage
                ->forPackageEvent($PackageBySupplyResult->getEvent())
                ->findAll();

            // сбрасываем на каждую упаковку продукт
            $Product = false;

            foreach($orders as $order)
            {
                $OrderUid = (string) $order->getOrderId();
                $this->orders[$WbPackageUid][$order->getOrderNumber()] = $OrderUid;


                /**
                 * Получаем стикеры заказов Wildberries
                 */

                if(false === isset($this->stickers[$OrderUid]))
                {
                    $WildberriesOrdersSticker = $WildberriesOrdersStickerRequest
                        ->profile($this->getProfileUid())
                        ->forOrderWb($order->getOrderNumber())
                        ->getOrderSticker();

                    /** Если стикер не найден - пробуем повторно отправить заказ в поставку */
                    if(false === $WildberriesOrdersSticker)
                    {
                        $ConfirmOrderWildberriesMessage = new ConfirmOrderWildberriesMessage(
                            $this->getProfileUid(),
                            $order->getOrderId(),
                            $order->getSupply(),
                            $order->getOrderNumber()
                        );

                        $messageDispatch->dispatch($ConfirmOrderWildberriesMessage);

                        /** Повторно прогреваем стикер */
                        $WildberriesOrdersSticker = $WildberriesOrdersStickerRequest
                            ->profile($this->getProfileUid())
                            ->forOrderWb($order->getOrderNumber())
                            ->getOrderSticker();
                    }

                    $this->stickers[$OrderUid] = $WildberriesOrdersSticker;
                }

                /**
                 * Получаем стикеры честных знаков на заказ
                 */

                if(false === isset($this->matrix[$OrderUid]) && $order->isExistCode())
                {
                    $datamatrix = $BarcodeWrite
                        ->text($order->getCodeString())
                        ->type(BarcodeType::DataMatrix)
                        ->format(BarcodeFormat::SVG)
                        ->generate();

                    if($datamatrix === false)
                    {
                        /**
                         * Проверить права на исполнение
                         * chmod +x /home/bundles.baks.dev/vendor/baks-dev/barcode/Writer/Generate
                         * chmod +x /home/bundles.baks.dev/vendor/baks-dev/barcode/Reader/Decode
                         */
                        throw new RuntimeException('Datamatrix write error');
                    }

                    $render = $BarcodeWrite->render();
                    $render = strip_tags($render, ['path']);
                    $render = trim($render);
                    $BarcodeWrite->remove();

                    $this->matrix[$OrderUid] = $render;
                }

                if(false === $Product)
                {
                    // на каждую упаковку всегда один продукт
                    $Product = $productDetail
                        ->event($order->getProductEvent())
                        ->offer($order->getProductOffer())
                        ->variation($order->getProductVariation())
                        ->modification($order->getProductModification())
                        ->find();

                    if(!$Product)
                    {
                        throw new RouteNotFoundException('Product Not Found');
                    }

                    $this->products[$WbPackageUid] = $Product;
                }

                /**
                 * Генерируем штрихкод продукции (один на все заказы в упаковке)
                 */

                if(false === isset($this->barcodes[$WbPackageUid]))
                {
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

                    $render = $BarcodeWrite->render();
                    $render = strip_tags($render, ['path']);
                    $render = trim($render);
                    $BarcodeWrite->remove();

                    $this->barcodes[$WbPackageUid] = $render;

                }

                /**
                 * Получаем настройки бокового стикера
                 */

                if(false === isset($this->settings[$WbPackageUid]))
                {
                    $this->settings[$WbPackageUid] = $Product['main'] ? $barcodeSettings
                        ->forProduct($Product['main'])
                        ->find() : false;
                }
            }

            /** Скрываем у все пользователей упаковку для печати */
            $CentrifugoPublish
                ->addData(['identifier' => $WbPackageUid]) // ID упаковки
                ->send('remove');

            $printers[] = $WbPackageUid;
        }


        $render = $this->render(
            [
                'packages' => $this->packages,
                'orders' => $this->orders,

                'stickers' => $this->stickers, // стикеры Wildberries
                'matrix' => $this->matrix,
                'barcodes' => $this->barcodes,
                'settings' => $this->settings,
                'products' => $this->products,
            ],
            routingName: 'admin.package',
            file: '/print/print.html.twig'
        );


        /** Отправляем сообщение в шину и отмечаем принт упаковок */
        if($printers)
        {
            foreach($printers as $printer)
            {
                $messageDispatch->dispatch(
                    message: new PrintWbPackageMessage(new WbPackageUid($printer)),
                    transport: 'wildberries-package',
                );
            }
        }

        return $render;
    }
}
