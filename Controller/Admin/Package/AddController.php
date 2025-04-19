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

use BaksDev\Centrifugo\Server\Publish\CentrifugoPublishInterface;
use BaksDev\Core\Controller\AbstractController;
use BaksDev\Core\Deduplicator\DeduplicatorInterface;
use BaksDev\Core\Listeners\Event\Security\RoleSecurity;
use BaksDev\Core\Type\UidType\ParamConverter;
use BaksDev\Orders\Order\Repository\RelevantNewOrderByProduct\RelevantNewOrderByProductInterface;
use BaksDev\Products\Product\Repository\ProductDetail\ProductDetailByUidInterface;
use BaksDev\Products\Product\Type\Event\ProductEventUid;
use BaksDev\Products\Product\Type\Offers\Id\ProductOfferUid;
use BaksDev\Products\Product\Type\Offers\Variation\Id\ProductVariationUid;
use BaksDev\Products\Product\Type\Offers\Variation\Modification\Id\ProductModificationUid;
use BaksDev\Products\Stocks\Entity\Stock\Event\ProductStockEvent;
use BaksDev\Products\Stocks\Entity\Stock\ProductStock;
use BaksDev\Products\Stocks\Repository\ProductStocksByOrder\ProductStocksByOrderInterface;
use BaksDev\Products\Stocks\UseCase\Admin\Extradition\ExtraditionProductStockDTO;
use BaksDev\Products\Stocks\UseCase\Admin\Extradition\ExtraditionProductStockHandler;
use BaksDev\Wildberries\Orders\Type\DeliveryType\TypeDeliveryFbsWildberries;
use BaksDev\Wildberries\Package\Entity\Package\WbPackage;
use BaksDev\Wildberries\Package\Forms\Package\AddOrdersPackage\AddOrdersPackageDTO;
use BaksDev\Wildberries\Package\Forms\Package\AddOrdersPackage\AddOrdersPackageForm;
use BaksDev\Wildberries\Package\Repository\Package\ExistOrderPackage\ExistOrderPackageInterface;
use BaksDev\Wildberries\Package\Repository\Supply\OpenWbSupplyIdentifier\OpenWbSupplyIdentifierInterface;
use BaksDev\Wildberries\Package\UseCase\Package\Pack\Orders\WbPackageOrderDTO;
use BaksDev\Wildberries\Package\UseCase\Package\Pack\WbPackageDTO;
use BaksDev\Wildberries\Package\UseCase\Package\Pack\WbPackageHandler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

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
        ProductDetailByUidInterface $productDetail,
        ExistOrderPackageInterface $ExistOrderPackage,
        WbPackageHandler $WbPackageHandler,
        CentrifugoPublishInterface $CentrifugoPublish,
        OpenWbSupplyIdentifierInterface $OpenWbSupplyIdentifier,
        RelevantNewOrderByProductInterface $RelevantNewOrderByProduct,
        ProductStocksByOrderInterface $ProductStocksByOrder,
        ExtraditionProductStockHandler $ExtraditionProductStockHandler,
        DeduplicatorInterface $deduplicator,
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
        $form = $this->createForm(
            type: AddOrdersPackageForm::class,
            data: $PackageOrdersDTO,
            options: ['action' => $this->generateUrl('wildberries-package:admin.package.add'),]
        );

        $form->handleRequest($request);

        $details = $productDetail
            ->event($PackageOrdersDTO->getProduct())
            ->offer($PackageOrdersDTO->getOffer())
            ->variation($PackageOrdersDTO->getVariation())
            ->modification($PackageOrdersDTO->getModification())
            ->find();


        if($form->isSubmitted() && $form->isValid() && $form->has('package_orders'))
        {

            $this->refreshTokenForm($form);

            /* Скрываем у всех продукт */
            $CentrifugoPublish
                ->addData(['identifier' => $PackageOrdersDTO->getIdentifier()]) // ID продукта
                ->send('remove');

            $WbSupplyUid = $OpenWbSupplyIdentifier->find();

            if(!$WbSupplyUid)
            {
                return $this->errorController('Supply');
            }

            /** Создаем упаковку */
            $WbPackageDTO = new WbPackageDTO($this->getProfileUid())
                ->setPackageSupply($WbSupplyUid);

            $deduplicator
                ->namespace('wildberries-package')
                ->expiresAfter('1 day');


            /**
             * Перебираем все количество продукции в упаковке
             */

            $total = (int) $PackageOrdersDTO->getTotal();

            for($i = 1; $i <= $total; $i++)
            {
                /**
                 * Получаем заказ со статусом «УПАКОВКА» на данную продукцию
                 */
                $OrderEvent = $RelevantNewOrderByProduct
                    ->forProductEvent($PackageOrdersDTO->getProduct())
                    ->forOffer($PackageOrdersDTO->getOffer())
                    ->forVariation($PackageOrdersDTO->getVariation())
                    ->forModification($PackageOrdersDTO->getModification())
                    ->forDelivery(TypeDeliveryFbsWildberries::TYPE)
                    ->onlyPackageStatus()
                    ->find();

                if(false === $OrderEvent)
                {
                    break;
                }

                /**
                 * Если найденный заказ добавлен в поставку, но его статус еще не успел обновиться на складе (складская заявка и сам заказ)
                 * - пробуем через время (если застрял в очереди)
                 */

                $DeduplicatorPack = $deduplicator
                    ->deduplication([$OrderEvent->getMain(), self::class]);

                if($DeduplicatorPack->isExecuted())
                {
                    /** Если превышает 100 попыток */
                    if($total >= 100)
                    {
                        $this->addFlash(
                            'page.add',
                            '%s: Нет возможности добавить заказа в поставку',
                            'wildberries-package.package',
                            $OrderEvent->getOrderNumber()
                        );

                        break;
                    }

                    $total++;
                    usleep(100000);
                    continue;
                }

                /**
                 * Получаем складскую заявку и обновляем статус квитанции на «Готов к выдаче»
                 */

                $invoices = $ProductStocksByOrder->findByOrder($OrderEvent->getMain());

                if(empty($invoices))
                {
                    $this->addFlash(
                        'page.add',
                        '%s: Не найдено ни одной складской заявки на заказ',
                        'wildberries-package.package',
                        $OrderEvent->getOrderNumber()
                    );

                    return $this->redirectToReferer();
                }

                /**
                 * @var ProductStockEvent $ProductStockEvent
                 * Изменяем статус складской заявки «Готов к выдаче»
                 */
                foreach($invoices as $ProductStockEvent)
                {
                    $ExtraditionProductStockDTO = new ExtraditionProductStockDTO();
                    $ProductStockEvent->getDto($ExtraditionProductStockDTO);
                    $ProductStock = $ExtraditionProductStockHandler->handle($ExtraditionProductStockDTO);

                    if(false === ($ProductStock instanceof ProductStock))
                    {
                        $this->addFlash(
                            'page.add',
                            'danger.add',
                            'wildberries-package.package',
                            $ProductStock
                        );

                        return $this->redirectToReferer();
                    }
                }

                $DeduplicatorPack->save();

                /** Не добавляем, если заказ уже имеется в упаковке */
                if($ExistOrderPackage->forOrder($OrderEvent->getMain())->isExist())
                {
                    continue;
                }

                /** Добавляем в упаковку поставки заказ */
                $WbPackageOrderDTO = new WbPackageOrderDTO()
                    ->setId($OrderEvent->getMain())
                    ->setSort(time());

                $WbPackageDTO->addOrd($WbPackageOrderDTO);

            }

            if($WbPackageDTO->getOrd()->isEmpty())
            {
                return $this->redirectToReferer();
            }

            /** Сохраняем упаковку с имеющимися заказами */

            $WbPackage = $WbPackageHandler->handle($WbPackageDTO);


            $this->addFlash
            (
                type: 'page.new',
                message: $WbPackage instanceof WbPackage ? 'success.new' : 'danger.new',
                domain: 'wildberries-package.package',
                arguments: $WbPackage
            );

            return $this->redirectToReferer();
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
            'page.add',
            'danger.add',
            'wildberries-package.package',
            $code
        );

        return $this->redirectToRoute('wildberries-package:admin.package.index');
    }
}