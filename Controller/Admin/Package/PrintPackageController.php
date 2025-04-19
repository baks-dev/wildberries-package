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
use BaksDev\Wildberries\Orders\Api\WildberriesOrdersSticker\GetWildberriesOrdersStickerRequest;
use BaksDev\Wildberries\Package\Entity\Package\WbPackage;
use BaksDev\Wildberries\Package\Messenger\Orders\Confirm\ConfirmOrderWildberriesMessage;
use BaksDev\Wildberries\Package\Repository\Package\OrdersByPackage\OrdersByPackageInterface;
use BaksDev\Wildberries\Package\Repository\Package\OrdersByPackage\OrdersByPackageResult;
use BaksDev\Wildberries\Package\Type\Package\Status\WbPackageStatus\WbPackageStatusAdd;
use BaksDev\Wildberries\Package\UseCase\Package\Print\PrintWbPackageMessage;
use BaksDev\Wildberries\Products\Repository\Barcode\WbBarcodeProperty\WbBarcodePropertyByProductEventInterface;
use BaksDev\Wildberries\Products\Repository\Barcode\WbBarcodeSettings\WbBarcodeSettingsInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
#[RoleSecurity('ROLE_WB_PACKAGE_PRINT')]
final class PrintPackageController extends AbstractController
{
    private ?array $packages = null;

    private ?array $orders = null;

    private ?array $stickers = null;

    private ?array $matrix = null;

    private ?array $products = null;

    private ?array $barcodes = null;

    private ?array $settings = null;


    /**
     * Печать штрихкодов и QR заказов в упаковке
     */
    #[Route('/admin/wb/packages/print/pack/{id}', name: 'admin.package.print.pack', methods: ['GET', 'POST'])]
    public function printer(
        #[Target('wildberriesPackageLogger')] LoggerInterface $logger,
        #[MapEntity] WbPackage $wbPackage,
        CentrifugoPublishInterface $CentrifugoPublish,
        OrdersByPackageInterface $orderByPackage,
        WbBarcodeSettingsInterface $barcodeSettings,
        WbBarcodePropertyByProductEventInterface $wbBarcodeProperty,
        ProductDetailByUidInterface $productDetail,
        MessageDispatchInterface $messageDispatch,
        BarcodeWrite $BarcodeWrite,
        GetWildberriesOrdersStickerRequest $WildberriesOrdersStickerRequest
    ): Response
    {

        /**
         * Получаем все заказы в упаковке
         *
         */
        $orders = $orderByPackage
            ->forPackageEvent($wbPackage->getEvent())
            ->toArray();

        if(false === $orders)
        {
            $logger->critical(
                'wildberries-package: Заказов на упаковку не найдено',
                [$wbPackage->getEvent(), self::class.':'.__LINE__]
            );

            return new Response('Заказов на упаковку не найдено', Response::HTTP_NOT_FOUND);
        }


        $WbPackageUid = (string) $wbPackage->getId();
        $this->packages[] = $WbPackageUid;



        /**
         * Получаем стикеры заказа Wildberries
         */

        $isPrint = true;

        $Product = false;

        /** @var OrdersByPackageResult $order */
        foreach($orders as $order)
        {

            if($isPrint === true && false === $order->getOrderStatus()->equals(WbPackageStatusAdd::class))
            {
                $isPrint = false;
            }

            $OrderUid = (string) $order->getOrderId();
            $this->orders[$WbPackageUid][$order->getOrderNumber()] = $OrderUid;


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


            if(!empty($WildberriesOrdersSticker) || $order->getOrderStatus()->equals(WbPackageStatusAdd::class))
            {
                $this->stickers[$OrderUid] = $WildberriesOrdersSticker;
            }
            else
            {
                $this->stickers[$OrderUid] = null;
            }

            if($isPrint === true && !isset($this->stickers[$OrderUid]))
            {
                $isPrint = false;
            }

            /**
             * Генерируем честный знак
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
                     * */
                    throw new RuntimeException('Datamatrix write error');
                }

                $render = $BarcodeWrite->render();
                $BarcodeWrite->remove();
                $render = strip_tags($render, ['path']);
                $render = trim($render);

                $this->matrix[$OrderUid] = $render;
            }

            /* Получаем продукцию для штрихкода (в упаковке всегда один и тот же продукт) */

            if($Product === false)
            {
                $Product = $productDetail
                    ->event($order->getProductEvent())
                    ->offer($order->getProductOffer())
                    ->variation($order->getProductVariation())
                    ->modification($order->getProductModification())
                    ->find();
            }
        }


        if(!$Product)
        {
            $logger->critical(
                'wildberries-package: Продукция в упаковке не найдена',
                [$order, self::class.':'.__LINE__]
            );

            return new Response('Продукция в упаковке не найдена', Response::HTTP_NOT_FOUND);
        }

        $this->products[$WbPackageUid] = $Product;

        if(empty($Product['product_barcode']))
        {
            $logger->critical(
                'wildberries-package: В продукции не указан артикул либо штрихкод',
                [$Product, self::class.':'.__LINE__]
            );

            return new Response('В продукции не указан артикул либо штрихкод', Response::HTTP_NOT_FOUND);
        }

        /**
         * Генерируем штрихкод продукции (один на все заказы)
         */

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

        $packageKey = (string) $wbPackage->getId();

        $render = $BarcodeWrite->render();
        $BarcodeWrite->remove();
        $render = strip_tags($render, ['path']);
        $render = trim($render);

        $this->barcodes[$packageKey] = $render;

        /**
         * Получаем настройки бокового стикера
         */

        $this->settings[$WbPackageUid] = $Product['main'] ? $barcodeSettings
            ->forProduct($Product['main'])
            ->find() : false;


        /** Скрываем у все пользователей упаковку для печати */
        $CentrifugoPublish
            ->addData(['identifier' => $packageKey]) // ID упаковки
            ->send('remove');


        $render = $this->render(
            [
                //                'packages' => [$packageKey],
                //                'orders' => [$packageKey => $orders],
                //                'barcodes' => $this->barcodes,
                //                'settings' => $BarcodeSettings,
                //                'products' => $this->products,
                //                'stickers' => $this->stickers,
                //                'matrix' => $this->matrix,


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


        if($isPrint)
        {
            /** Отправляем сообщение в шину и отмечаем принт упаковки */
            $messageDispatch->dispatch(
                message: new PrintWbPackageMessage($wbPackage->getId()),
                transport: 'wildberries-package',
            );
        }

        return $render;
    }
}
