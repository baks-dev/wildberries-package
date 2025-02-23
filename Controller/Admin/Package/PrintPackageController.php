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
use BaksDev\Wildberries\Package\Repository\Package\OrdersByPackage\OrdersByPackageInterface;
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
    private ?array $stickers = null;

    private ?array $barcodes = null;

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

        /* Получаем все заказы в упаковке вместе с честными занками при наличии */
        $orders = $orderByPackage
            ->forPackageEvent($wbPackage->getEvent())
            ->findAll();

        if(empty($orders))
        {
            $logger->critical(
                'wildberries-package: Заказов на упаковку не найдено',
                [$wbPackage->getEvent(), self::class.':'.__LINE__]
            );

            return new Response('Заказов на упаковку не найдено', Response::HTTP_NOT_FOUND);
        }

        /**
         * Получаем стикеры заказа Wildberries
         */

        $isPrint = true;

        foreach($orders as $order)
        {
            if($isPrint === true && $order['order_status'] !== 'add')
            {
                $isPrint = false;
            }

            $this->stickers[$order['order']] = $order['order_status'] === 'add' ? $WildberriesOrdersStickerRequest
                ->profile($this->getProfileUid())
                ->forOrderWb($order['number'])
                ->getOrderSticker() : null;
        }


        /**
         * Получаем честные знаки на заказ из материалов
         */


        /**
         * Получаем продукцию для штрихкода (в упаковке всегда один и тот же продукт)
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
            $logger->critical(
                'wildberries-package: Продукция в упаковке не найдена',
                [$order, self::class.':'.__LINE__]
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


        $this->barcodes[(string) $wbPackage->getId()] = $BarcodeWrite->render();
        $BarcodeWrite->remove();

        /**
         * Получаем настройки бокового стикера
         */

        $BarcodeSettings = $Product['main'] ? $barcodeSettings
            ->forProduct($Product['main'])
            ->find() : false;


        /** Скрываем у все пользователей упаковку для печати */
        $CentrifugoPublish
            ->addData(['identifier' => (string) $wbPackage->getId()]) // ID упаковки
            ->send('remove');


        $render = $this->render(
            [
                'packages' => [(string) $wbPackage->getId()],
                'orders' => [(string) $wbPackage->getId() => $orders],
                'barcodes' => $this->barcodes,
                'settings' => $BarcodeSettings,
                'card' => $Product,
                'stickers' => $this->stickers
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
