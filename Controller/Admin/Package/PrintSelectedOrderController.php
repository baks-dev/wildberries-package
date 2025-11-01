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
use BaksDev\Core\Controller\AbstractController;
use BaksDev\Core\Listeners\Event\Security\RoleSecurity;
use BaksDev\Core\Messenger\MessageDispatchInterface;
use BaksDev\Products\Product\Repository\ProductDetail\ProductDetailByUidInterface;
use BaksDev\Wildberries\Orders\Api\WildberriesOrdersSticker\GetWildberriesOrdersStickerRequest;
use BaksDev\Wildberries\Package\Forms\Package\Print\PrintOrderPackageDTO;
use BaksDev\Wildberries\Package\Forms\Package\Print\PrintOrdersPackageDTO;
use BaksDev\Wildberries\Package\Forms\Package\Print\PrintOrdersPackageForm;
use BaksDev\Wildberries\Package\Repository\Package\OrderPackage\WbPackageOrderInterface;
use BaksDev\Wildberries\Products\Repository\Barcode\WbBarcodeSettings\WbBarcodeSettingsInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use BaksDev\Wildberries\Package\UseCase\Package\Print\PrintWbPackageMessage;


#[AsController]
#[RoleSecurity('ROLE_WB_PACKAGE_PRINT')]
final class PrintSelectedOrderController extends AbstractController
{
    /**
     * Печать штрихкодов и QR заказа
     */
    #[Route('/admin/wb/packages/print/order', name: 'admin.package.print.selected.order', methods: ['GET', 'POST'])]
    public function printer(
        #[Target('wildberriesPackageLogger')] LoggerInterface $logger,
        Request $request,
        WbPackageOrderInterface $WbPackageOrder,
        WbBarcodeSettingsInterface $barcodeSettings,
        ProductDetailByUidInterface $productDetail,
        BarcodeWrite $BarcodeWrite,
        GetWildberriesOrdersStickerRequest $WildberriesOrdersStickerRequest,
        MessageDispatchInterface $messageDispatch
    ): Response
    {
        /**
         * Получаем данные по выбранным идентификаторам заказов в упаковке коллекции
         */

        $PrintOrdersPackageDTO = new PrintOrdersPackageDTO();

        $this
            ->createForm(
                type: PrintOrdersPackageForm::class,
                data: $PrintOrdersPackageDTO,
                options: ['action' => $this->generateUrl('wildberries-package:admin.package.print.selected.order')],
            )
            ->handleRequest($request);


        $packages = [];
        $orders = [];
        $settings = [];
        $card = [];
        $stickers = [];
        $barcodes = [];
        $products = [];

        /**
         * @var PrintOrderPackageDTO $printOrderPackageDTO
         */
        foreach($PrintOrdersPackageDTO->getCollection() as $printOrderPackageDTO)
        {
            $OrderUid = $printOrderPackageDTO->getOrder();

            $WbPackageOrderResult = $WbPackageOrder
                ->forOrder($OrderUid)
                ->find();

            /**
             * Получаем информацию о продукте (в упаковке всегда один и тот же продукт)
             */

            $Product = $productDetail
                ->event($WbPackageOrderResult->getProduct())
                ->offer($WbPackageOrderResult->getOffer())
                ->variation($WbPackageOrderResult->getVariation())
                ->modification($WbPackageOrderResult->getModification())
                ->find();


            if(!$Product)
            {
                $logger->critical(
                    'wildberries-package: Продукция в упаковке не найдена',
                    [$WbPackageOrderResult, self::class.':'.__LINE__]
                );

                return new Response('Продукция в упаковке не найдена', Response::HTTP_NOT_FOUND);
            }

            if(empty($Product['product_barcode']))
            {
                $logger->critical(
                    'wildberries-package: В продукции не указан артикул либо штрихкод',
                    [$Product, self::class.':'.__LINE__]
                );

                return new Response('В продукции не указан артикул либо штрихкод', Response::HTTP_NOT_FOUND);
            }

            $WbPackageUid = (string) $WbPackageOrderResult->getPackage();
            $packages[] = $WbPackageUid;

            $card[$WbPackageUid] = $Product;

            $products[$WbPackageUid] = $Product;

            $keyOrder = (string) $OrderUid;

            $orders[$WbPackageUid] = [
                $WbPackageOrderResult->getNumber() => (string) $WbPackageOrderResult->getOrder()
            ];

            /**
             * Получаем стикер Wildberries заказа
             */

            $stickers[$keyOrder] = $WildberriesOrdersStickerRequest
                ->profile($this->getProfileUid())
                ->forOrderWb($WbPackageOrderResult->getNumber())
                ->getOrderSticker();

            /**
             * Генерируем штрихкод товара
             */

            // Получаем настройки бокового стикера
            $BarcodeSettings = $Product['main'] ? $barcodeSettings
                ->forProduct($Product['main'])
                ->find() : false;

            $settings[$WbPackageUid] = $BarcodeSettings;

            /* Генерируем штрихкод в формате SVG */
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
            $BarcodeWrite->remove();
            $render = strip_tags($render, ['path']);
            $render = trim($render);

            $barcodes[$WbPackageUid] = trim($render);


            /**
             * Генерируем стикер Честного знака
             */

            $matrix = null;

            if($WbPackageOrderResult->isExistCode())
            {
                $datamatrix = $BarcodeWrite
                    ->text($WbPackageOrderResult->getCode())
                    ->type(BarcodeType::DataMatrix)
                    ->format(BarcodeFormat::SVG)
                    ->generate();

                if($datamatrix === false)
                {
                    /**
                     * Проверить права на исполнение
                     * chmod +x /home/bundles.baks.dev/vendor/baks-dev/barcode/Writer/Generate
                     * chmod +x /home/bundles.baks.dev/vendor/baks-dev/barcode/Reader/Decode
                     * */
                    throw new RuntimeException('Datamatrix write error');
                }

                /* Генерируем «Честный знак» в формате SVG */
                $render = $BarcodeWrite->render();
                $BarcodeWrite->remove();
                $render = strip_tags($render, ['path']);
                $render = trim($render);

                $matrix[$keyOrder] = $render;
            }

            /* Отправляем сообщение в шину и отмечаем print WbPackageSupply */
            $messageDispatch->dispatch(
                message: new PrintWbPackageMessage($WbPackageOrderResult->getPackage()),
                transport: 'wildberries-package',
            );

        }


        return $this->render(
            [
                'packages' => $packages,

                'orders' => $orders,

                'settings' => $settings,

                'card' => $card,

                'stickers' => $stickers,

                'matrix' => $matrix,

                'barcodes' => $barcodes,

                'products' => $products

            ],
            dir: 'admin.package',
            file: '/print/print.html.twig'
        );

    }
}
