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

namespace BaksDev\Wildberries\Products\Controller\Admin\Barcode;

use BaksDev\Core\Controller\AbstractController;
use BaksDev\Core\Listeners\Event\Security\RoleSecurity;
use BaksDev\Products\Product\Repository\ProductByVariation\ProductByVariationInterface;
use BaksDev\Products\Product\Repository\ProductDetail\ProductDetailByUidInterface;
use BaksDev\Wildberries\Products\Entity\Cards\WbProductCardVariation;
use BaksDev\Wildberries\Products\Repository\Barcode\WbBarcodeProperty\WbBarcodePropertyByProductEventInterface;
use BaksDev\Wildberries\Products\Repository\Barcode\WbBarcodeSettings\WbBarcodeSettingsInterface;
use Picqer\Barcode\BarcodeGeneratorSVG;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Annotation\Route;

#[AsController]
#[RoleSecurity('ROLE_WB_BARCODE_PRINT')]
final class PrintController extends AbstractController
{
    /**
     * Штрихкод карточки Wildberries
     */
    #[Route('/admin/wb/barcode/print/{id}', name: 'admin.barcode.print', methods: ['GET'])]
    public function index(
        Request $request,
        #[MapEntity] WbProductCardVariation $WbProductCardVariation,
        WbBarcodeSettingsInterface $barcodeSettings,
        ProductByVariationInterface $productByVariation,
        ProductDetailByUidInterface $productDetailByUid,
        WbBarcodePropertyByProductEventInterface $wbBarcodeProperty

    ): Response
    {

        /** Получаем настройки стикера */

       $WbProductCard = $WbProductCardVariation->getCard();
       $BarcodeSettings = $barcodeSettings->findWbBarcodeSettings($WbProductCard->getProduct());


        //dd($BarcodeSettings);

        /* Генерируем боковые стикеры */
        $gen = new BarcodeGeneratorSVG();
        $barcode = $gen->getBarcode($WbProductCardVariation->getBarcode(), $gen::TYPE_CODE_128, 2, 60);



        /** Получаем информацию о продукте */

        $Product = null;
        $property = [];

        if($BarcodeSettings)
        {
           $ProductConst = $productByVariation->getProductByVariationConstOrNull($WbProductCardVariation->getVariation());

           if($ProductConst)
           {
              $Product = $productDetailByUid->fetchProductDetailByEventAssociative(
                  $ProductConst['event_id'],
                  $ProductConst['offer_id'],
                  $ProductConst['variation_id']
               );

              /** Получаем дополнительные свойства */
              $property = $wbBarcodeProperty->getPropertyCollection($ProductConst['event_id']);

           }
        }


        return $this->render(
            [
                'item'  => $WbProductCardVariation,
                'barcode'  => base64_encode($barcode),
                'counter'  => $BarcodeSettings['counter'] ?? 1,
                'settings' => $BarcodeSettings,
                'card'  => $Product,
                'property'  => $property,
            ],
        );
    }

}