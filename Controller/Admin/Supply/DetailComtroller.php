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

namespace BaksDev\Wildberries\Package\Controller\Admin\Supply;


use BaksDev\Core\Controller\AbstractController;
use BaksDev\Core\Form\Search\SearchDTO;
use BaksDev\Core\Form\Search\SearchForm;
use BaksDev\Core\Listeners\Event\Security\RoleSecurity;
use BaksDev\Wildberries\Orders\Forms\WbOrdersProductFilter\WbOrdersProductFilterDTO;
use BaksDev\Wildberries\Orders\Forms\WbOrdersProductFilter\WbOrdersProductFilterForm;
use BaksDev\Wildberries\Package\Entity\Supply\WbSupply;
use BaksDev\Wildberries\Package\Repository\Supply\AllWbSupplyOrders\AllWbSupplyOrdersInterface;
use BaksDev\Wildberries\Package\Repository\Supply\OpenWbSupply\OpenWbSupplyInterface;
use BaksDev\Wildberries\Package\Repository\Supply\WbSupply\WbSupplyRepositoryInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Annotation\Route;

#[AsController]
#[RoleSecurity('ROLE_WB_SUPPLY')]
final class DetailComtroller extends AbstractController
{
    /**
     * Заказы в поставке
     */
    #[Route('/admin/wb/supply/detail/{id}/{page<\d+>}', name: 'admin.supply.detail', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        #[MapEntity] WbSupply $wbSupply,
        WbSupplyRepositoryInterface $wbSupplyRepository,
        AllWbSupplyOrdersInterface $allWbSupplyOrders,
        //AllWbSupplyInterface $allWbSupplyOrders,
        int $page = 0,
    ): Response
    {

        // Получаем указанную поставку
        $supply = $wbSupplyRepository->getWbSupplyById($wbSupply);

        // Поиск
        $search = new SearchDTO();
        $searchForm = $this->createForm(SearchForm::class, $search,
            ['action' => $this->generateUrl('wildberries-package:admin.supply.detail')]
        );
        $searchForm->handleRequest($request);


        /**
         * Фильтр товаров
         */

        $filter = new WbOrdersProductFilterDTO($request);
        $filterForm = $this->createForm(WbOrdersProductFilterForm::class, $filter, [
            'action' => $this->generateUrl('wildberries-package:admin.supply.detail', ['id' => $wbSupply->getId()]),
        ]);
        $filterForm->handleRequest($request);
        !$filterForm->isSubmitted()?:$this->redirectToReferer();

        // Получаем список
        $orders = $allWbSupplyOrders
            ->search($search)
            ->filter($filter)
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
