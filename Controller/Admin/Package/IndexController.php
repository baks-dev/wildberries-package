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


use BaksDev\Centrifugo\Services\Token\TokenUserGenerator;
use BaksDev\Core\Controller\AbstractController;
use BaksDev\Core\Form\Search\SearchDTO;
use BaksDev\Core\Form\Search\SearchForm;
use BaksDev\Core\Listeners\Event\Security\RoleSecurity;
use BaksDev\Manufacture\Part\Type\Complete\ManufacturePartComplete;
use BaksDev\Products\Category\Type\Id\ProductCategoryUid;
use BaksDev\Wildberries\Manufacture\Repository\AllWbOrdersGroup\AllWbOrdersManufactureInterface;
use BaksDev\Wildberries\Manufacture\Type\ManufacturePartComplete\ManufacturePartCompleteWildberriesFbs;
use BaksDev\Wildberries\Orders\Forms\WbOrdersProductFilter\WbOrdersProductFilterDTO;
use BaksDev\Wildberries\Orders\Forms\WbOrdersProductFilter\WbOrdersProductFilterForm;
use BaksDev\Wildberries\Package\Repository\Package\PrintOrdersPackageSupply\PrintOrdersPackageSupplyInterface;
use BaksDev\Wildberries\Package\Repository\Supply\OpenWbSupply\OpenWbSupplyInterface;
use BaksDev\Wildberries\Package\Type\Supply\Id\WbSupplyUid;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Annotation\Route;

#[AsController]
#[RoleSecurity('ROLE_WB_PACKAGE')]
final class IndexController extends AbstractController
{
    /**
     * Упаковка заказов
     */
    #[Route('/admin/wb/packages/{page<\d+>}', name: 'admin.package.index', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        OpenWbSupplyInterface $openWbSupply,
        PrintOrdersPackageSupplyInterface $printOrdersPackageSupply,
        TokenUserGenerator $tokenUserGenerator,
        AllWbOrdersManufactureInterface $allWbOrdersGroup,
        int $page = 0,
    ): Response
    {

        // Поиск
        $search = new SearchDTO($request);
        $searchForm = $this->createForm(SearchForm::class, $search);
        $searchForm->handleRequest($request);

        // Получаем открытую поставку
        $opens = $openWbSupply->getLastWbSupply($this->getProfileUid());

        /* Получаем заказа, которые не были напечатаны  */
        $print = isset($opens['id']) ? $printOrdersPackageSupply->fetchAllPrintOrdersPackageSupplyAssociative(new WbSupplyUid($opens['id'])) : null;


        /**
         * Фильтр заказов
         */

        $filter = new WbOrdersProductFilterDTO($request);

        $filterForm = $this->createForm(WbOrdersProductFilterForm::class, $filter, [
            'action' => $this->generateUrl('WildberriesManufacture:admin.index'),
        ]);
        $filterForm->handleRequest($request);
        !$filterForm->isSubmitted() ?: $this->redirectToReferer();


        /**
         * Получаем список заказов
         */

        $WbOrders = $allWbOrdersGroup
            ->fetchAllWbOrdersGroupAssociative(
                $search,
                $this->getProfileUid(),
                $filter,
                class_exists(ManufacturePartCompleteWildberriesFbs::class) ? new ManufacturePartComplete(ManufacturePartCompleteWildberriesFbs::class) : null
            );



        // Фильтр
        // $filter = new ProductsStocksFilterDTO($request, $ROLE_ADMIN ? null : $this->getProfileUid());
        // $filterForm = $this->createForm(ProductsStocksFilterForm::class, $filter);
        // $filterForm->handleRequest($request);

        // Получаем список
        //$WbPackage = $allWbPackage->fetchAllWbPackageAssociative($search);

        return $this->render(
            [
                'opens' => $opens,
                'print' => $print,
                'query' => $WbOrders,
                'search' => $searchForm->createView(),
                //'profile' => $profileForm->createView(),
                'filter' => $filterForm->createView(),
                'token' => $tokenUserGenerator->generate($this->getUsr()),
            ]
        );
    }
}
