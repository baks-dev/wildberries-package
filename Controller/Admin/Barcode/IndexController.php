<?php
/*
 *  Copyright 2022.  Baks.dev <admin@baks.dev>
 *
 *  Licensed under the Apache License, Version 2.0 (the "License");
 *  you may not use this file except in compliance with the License.
 *  You may obtain a copy of the License at
 *
 *  http://www.apache.org/licenses/LICENSE-2.0
 *
 *  Unless required by applicable law or agreed to in writing, software
 *  distributed under the License is distributed on an "AS IS" BASIS,
 *  WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 *  See the License for the specific language governing permissions and
 *   limitations under the License.
 *
 */

namespace BaksDev\Wildberries\Products\Controller\Admin\Barcode;


use BaksDev\Core\Controller\AbstractController;
use BaksDev\Core\Form\Search\SearchDTO;
use BaksDev\Core\Form\Search\SearchForm;
use BaksDev\Core\Listeners\Event\Security\RoleSecurity;
use BaksDev\Wildberries\Products\Repository\Barcode\AllBarcodeSettings\AllBarcodeSettingsInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Annotation\Route;


#[AsController]
#[RoleSecurity('ROLE_WB_BARCODE')]
final class IndexController extends AbstractController
{
    #[Route('/admin/wb/barcodes/{page<\d+>}', name: 'admin.barcode.index', methods: ['GET', 'POST'])]
    public function index(
      Request $request,
      AllBarcodeSettingsInterface $allSticker,
      int $page = 0
    ) : Response
    {
        /* Поиск */
        $search = new SearchDTO($request);
        $searchForm = $this->createForm(SearchForm::class, $search);
        $searchForm->handleRequest($request);


        /* Получаем список */
        $query = $allSticker->fetchAllBarcodeSettings($search, $this->getAdminFilterProfile());


        return $this->render(
          [
            'query' => $query,
            'search' => $searchForm->createView(),
          ]);
    }
    
}