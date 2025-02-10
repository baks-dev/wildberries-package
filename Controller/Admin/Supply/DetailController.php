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

namespace BaksDev\Wildberries\Package\Controller\Admin\Supply;


use BaksDev\Core\Controller\AbstractController;
use BaksDev\Core\Form\Search\SearchDTO;
use BaksDev\Core\Form\Search\SearchForm;
use BaksDev\Core\Listeners\Event\Security\RoleSecurity;
use BaksDev\Products\Product\Forms\ProductFilter\Admin\ProductFilterDTO;
use BaksDev\Products\Product\Forms\ProductFilter\Admin\ProductFilterForm;
use BaksDev\Wildberries\Orders\Forms\WbOrdersProductFilter\WbOrdersProductFilterDTO;
use BaksDev\Wildberries\Orders\Forms\WbOrdersProductFilter\WbOrdersProductFilterForm;
use BaksDev\Wildberries\Package\Entity\Supply\WbSupply;
use BaksDev\Wildberries\Package\Repository\Supply\AllWbSupplyOrders\AllWbSupplyOrdersInterface;
use BaksDev\Wildberries\Package\Repository\Supply\WbSupply\WbSupplyInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
#[RoleSecurity('ROLE_WB_SUPPLY')]
final class DetailController extends AbstractController
{
    /**
     * Заказы в поставке
     */
    #[Route('/admin/wb/supply/detail/{id}/{page<\d+>}', name: 'admin.supply.detail', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        #[MapEntity] WbSupply $wbSupply,
        WbSupplyInterface $wbSupplyRepository,
        AllWbSupplyOrdersInterface $allWbSupplyOrders,

        //AllWbSupplyInterface $allWbSupplyOrders,
        int $page = 0,
    ): Response
    {

        // Получаем указанную поставку
        $supply = $wbSupplyRepository->getWbSupplyById($wbSupply);

        // Поиск
        $search = new SearchDTO();

        $searchForm = $this
            ->createForm(
                type: SearchForm::class,
                data: $search,
                options: ['action' => $this->generateUrl('wildberries-package:admin.supply.detail', ['id' => $wbSupply->getId()])]
            )
            ->handleRequest($request);


        /**
         * Фильтр товаров
         */

        /**
         * Фильтр продукции
         */
        $filter = new ProductFilterDTO();

        $filterForm = $this
            ->createForm(
                type: ProductFilterForm::class,
                data: $filter,
                options: ['action' => $this->generateUrl('manufacture-part:admin.index')]
            )
            ->handleRequest($request);

        // Получаем список
        $orders = $allWbSupplyOrders
            ->search($search)
            //->filter($filter)
            ->fetchAllWbSupplyOrdersAssociative($wbSupply);

        return $this->render(
            [
                'query' => $orders,
                'supply' => $supply,
                'search' => $searchForm->createView(),
                'filter' => $filterForm->createView(),
            ]
        );
    }
}
