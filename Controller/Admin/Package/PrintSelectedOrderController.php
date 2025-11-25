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
use BaksDev\Products\Product\Repository\ProductDetail\ProductDetailByUidResult;
use BaksDev\Wildberries\Orders\Api\WildberriesOrdersSticker\GetWildberriesOrdersStickerRequest;
use BaksDev\Wildberries\Package\Forms\Package\Print\Collection\PrintOrderPackageDTO;
use BaksDev\Wildberries\Package\Forms\Package\Print\PrintMultipleOrdersPackageDTO;
use BaksDev\Wildberries\Package\Forms\Package\Print\PrintMultipleOrdersPackageForm;
use BaksDev\Wildberries\Package\Repository\Package\OrderPackage\WbPackageOrderInterface;
use BaksDev\Wildberries\Package\UseCase\Package\Print\PrintWbPackageMessage;
use BaksDev\Wildberries\Products\Repository\Barcode\WbBarcodeSettings\WbBarcodeSettingsInterface;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;


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

        $PrintOrdersPackageDTO = new PrintMultipleOrdersPackageDTO();

        $this
            ->createForm(
                type: PrintMultipleOrdersPackageForm::class,
                data: $PrintOrdersPackageDTO,
                options: ['action' => $this->generateUrl('wildberries-package:admin.package.print.selected.order')],
            )
            ->handleRequest($request);

        /**
         * @var PrintOrderPackageDTO $printOrderPackageDTO
         */
        foreach($PrintOrdersPackageDTO->getCollection() as $printOrderPackageDTO)
        {
            $WbPackageOrderResult = $WbPackageOrder
                ->forOrder($printOrderPackageDTO->getOrder())
                ->find();

            /**
             * Получаем информацию о продукте
             */

            $ProductDetailByUidResult = $productDetail
                ->event($WbPackageOrderResult->getProduct())
                ->offer($WbPackageOrderResult->getOffer())
                ->variation($WbPackageOrderResult->getVariation())
                ->modification($WbPackageOrderResult->getModification())
                ->findResult();

            if(false === ($ProductDetailByUidResult instanceof ProductDetailByUidResult))
            {
                $logger->critical(
                    'wildberries-package: Продукция в упаковке не найдена',
                    [$WbPackageOrderResult, self::class.':'.__LINE__]
                );

                return new Response('Продукция в упаковке не найдена', Response::HTTP_NOT_FOUND);
            }

            if(empty($ProductDetailByUidResult->getProductBarcode()))
            {
                $logger->critical(
                    'wildberries-package: В продукции не указан артикул либо штрихкод',
                    [$ProductDetailByUidResult, self::class.':'.__LINE__],
                );

                return new Response('В продукции не указан артикул либо штрихкод', Response::HTTP_NOT_FOUND);
            }


            $keyOrder = (string) $printOrderPackageDTO->getOrder();

            $print[$keyOrder]['card'] = $ProductDetailByUidResult;
            $print[$keyOrder]['number'] = $WbPackageOrderResult->getNumber();

            /**
             * Получаем стикер Wildberries заказа
             */

            $print[$keyOrder]['sticker'] = $WildberriesOrdersStickerRequest
                ->profile($this->getProfileUid())
                ->forOrderWb($WbPackageOrderResult->getNumber())
                ->getOrderSticker();


            /**
             * Получаем настройки бокового стикера
             */

            $BarcodeSettings = $ProductDetailByUidResult->getProductMain() ? $barcodeSettings
                ->forProduct($ProductDetailByUidResult->getProductMain())
                ->find() : false;

            $print[$keyOrder]['settings'] = $BarcodeSettings;


            /**
             * Генерируем штрихкод товара
             */

            /* Генерируем штрихкод в формате SVG */
            $barcode = $BarcodeWrite
                ->text($ProductDetailByUidResult->getProductBarcode())
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

            $print[$keyOrder]['barcode'] = trim($render);


            /**
             * Генерируем стикер Честного знака
             */

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

                $print[$keyOrder]['matrix'] = $render;
            }


            /* Отправляем сообщение в шину и отмечаем print WbPackageSupply */
            $messageDispatch->dispatch(
                message: new PrintWbPackageMessage($WbPackageOrderResult->getPackage()),
                transport: 'wildberries-package',
            );

        }

        if(empty($print))
        {
            throw new InvalidArgumentException('Page Not Found', 404);
        }

        return $this->render(['orders' => $print],
            dir: 'admin.package',
            file: '/print/orders.html.twig',
        );

    }
}
