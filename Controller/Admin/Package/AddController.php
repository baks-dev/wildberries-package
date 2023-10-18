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
use BaksDev\Core\Type\UidType\ParamConverter;
use BaksDev\Manufacture\Part\Entity\Event\ManufacturePartEvent;
use BaksDev\Manufacture\Part\Entity\Products\ManufacturePartProduct;
use BaksDev\Products\Product\Repository\ProductDetail\ProductDetailByUidInterface;
use BaksDev\Products\Product\Type\Event\ProductEventUid;
use BaksDev\Products\Product\Type\Offers\Id\ProductOfferUid;
use BaksDev\Products\Product\Type\Offers\Variation\Id\ProductVariationUid;
use BaksDev\Products\Product\Type\Offers\Variation\Modification\Id\ProductModificationUid;
use BaksDev\Wildberries\Orders\Repository\WbOrdersByProduct\WbOrdersByProductInterface;
use BaksDev\Wildberries\Package\Entity\Package\WbPackage;
use BaksDev\Wildberries\Package\Forms\Package\AddOrdersPackage\AddOrdersPackageDTO;
use BaksDev\Wildberries\Package\Forms\Package\AddOrdersPackage\AddOrdersPackageForm;
use BaksDev\Wildberries\Package\Repository\Package\ExistOrderPackage\ExistOrderPackageInterface;
use BaksDev\Wildberries\Package\Repository\Supply\OpenWbSupply\OpenWbSupplyInterface;
use BaksDev\Wildberries\Package\UseCase\Package\Pack\Orders\WbPackageOrderDTO;
use BaksDev\Wildberries\Package\UseCase\Package\Pack\WbPackageDTO;
use BaksDev\Wildberries\Package\UseCase\Package\Pack\WbPackageHandler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Annotation\Route;

#[AsController]
#[RoleSecurity('ROLE_WB_PACKAGE_ADD')]
final class AddController extends AbstractController
{
    /**
     * Добавить заказы в открытую поставку
     */
    #[Route('/admin/wb/supply/add/{total}', name: 'admin.package.add', methods: ['GET', 'POST'])]
    public function news(
        Request $request,
        WbOrdersByProductInterface $wbOrdersByProduct,
        OpenWbSupplyInterface $openWbSupply,
        WbPackageHandler $wbPackageHandler,
        ProductDetailByUidInterface $productDetail,
        ExistOrderPackageInterface $existOrderPackage,
        CentrifugoPublishInterface $CentrifugoPublish,
        #[ParamConverter(ProductEventUid::class)] $product = null,
        #[ParamConverter(ProductOfferUid::class)] $offer = null,
        #[ParamConverter(ProductVariationUid::class)] $variation = null,
        #[ParamConverter(ProductModificationUid::class)] $modification = null,
        ?int $total = null,
    ): Response
    {
        $PackageOrdersDTO = new AddOrdersPackageDTO($this->getProfileUid());

        if($request->isMethod('GET'))
        {
            $PackageOrdersDTO
                ->setProduct($product)
                ->setOffer($offer)
                ->setVariation($variation)
                ->setModification($modification)
                ->setTotal($total);
        }

        // Форма
        $form = $this->createForm(AddOrdersPackageForm::class, $PackageOrdersDTO, [
            'action' => $this->generateUrl('wildberries-package:admin.package.add'),
        ]);

        $form->handleRequest($request);

        $details = $productDetail->fetchProductDetailByEventAssociative(
            $PackageOrdersDTO->getProduct(),
            $PackageOrdersDTO->getOffer(),
            $PackageOrdersDTO->getVariation(),
            $PackageOrdersDTO->getModification()
        );


        if($form->isSubmitted() && $form->isValid() && $form->has('package_orders'))
        {
            /* Скрываем у всех продукт */
            $CentrifugoPublish
                ->addData(['identifier' => $PackageOrdersDTO->getIdentifier()]) // ID продукта
                ->send('remove');

            $WbSupplyUid = $openWbSupply->getOpenWbSupplyByProfile($this->getProfileUid());

            if(!$WbSupplyUid)
            {
                return $this->errorController('Supply');
            }

            $WbPackage = new WbPackageDTO($this->getProfileUid());
            $WbPackage->setPackageSupply($WbSupplyUid);

            $orders = $wbOrdersByProduct->findOldWbOrders(
                $PackageOrdersDTO->getTotal(),
                $PackageOrdersDTO->getProduct(),
                $PackageOrdersDTO->getOffer(),
                $PackageOrdersDTO->getVariation(),
                $PackageOrdersDTO->getModification()
            );

            foreach($orders as $Order)
            {
                if($existOrderPackage->isExistOrder($Order))
                {
                    return $this->errorController('Exist');
                }

                $WbPackageOrderDTO = new WbPackageOrderDTO();
                $WbPackageOrderDTO->setId($Order);
                $WbPackage->addOrd($WbPackageOrderDTO);
            }

            $handle = $wbPackageHandler->handle($WbPackage);

            /** Если был добавлен продукт в открытую партию отправляем сокет */
            if($handle instanceof WbPackage)
            {
                /* Добавляем в список заказ */
                $details['product_total'] = $PackageOrdersDTO->getTotal();
                $details['id'] = $handle->getId();

                $CentrifugoPublish
                    // HTML продукта
                    ->addData(['product' => $this->render(['card' => $details,],
                        file: 'centrifugo.html.twig')->getContent()]) // шаблон
                    ->addData(['total' => $PackageOrdersDTO->getTotal()]) // количество для суммы всех товаров
                    ->send((string) $WbSupplyUid);


                $CentrifugoPublish->send('publish');

                $return = $this->addFlash(
                    type: 'admin.page.add',
                    message: 'admin.success.add',
                    domain: 'admin.wb.package',
                    status: $request->isXmlHttpRequest() ? 200 : 302 // не делаем редирект в случае AJAX
                );

                return $request->isXmlHttpRequest() ? $return : $this->redirectToRoute('wildberries-package:admin.package.index');
            }

            $this->addFlash(
                'admin.page.add',
                'admin.danger.add',
                'admin.wb.package
                ', $handle);

            return $this->redirectToRoute('wildberries-package:admin.package.index');
        }

        return $this->render([
            'form' => $form->createView(),
            'card' => $details
        ]);
    }

    public function errorController($code): Response
    {
        $this->addFlash
        (
            'admin.page.add',
            'admin.danger.add',
            'admin.wb.package',
            $code
        );

        return $this->redirectToRoute('wildberries-package:admin.package.index');
    }
}