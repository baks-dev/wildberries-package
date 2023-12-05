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
use BaksDev\Core\Listeners\Event\Security\RoleSecurity;
use BaksDev\Wildberries\Products\Entity\Barcode\WbBarcode;
use BaksDev\Wildberries\Products\UseCase\Barcode\NewEdit\WbBarcodeDTO;
use BaksDev\Wildberries\Products\UseCase\Barcode\NewEdit\WbBarcodeForm;
use BaksDev\Wildberries\Products\UseCase\Barcode\NewEdit\WbBarcodeHandler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Annotation\Route;

#[AsController]
#[RoleSecurity('ROLE_WB_BARCODE_NEW')]
final class NewController extends AbstractController
{
    #[Route('/admin/wb/barcode/new', name: 'admin.barcode.newedit.new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        WbBarcodeHandler $wbBarcodeHandler,
    ): Response
    {
        $WbBarcodeDTO = new WbBarcodeDTO();

        /* Форма добавления */
        $form = $this->createForm(WbBarcodeForm::class, $WbBarcodeDTO, [
            'action' => $this->generateUrl('wildberries-products:admin.barcode.newedit.new'),
        ]);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid() && $form->has('wb_barcode'))
        {
            $handle = $wbBarcodeHandler->handle($WbBarcodeDTO, $this->getProfileUid());

            $this->addFlash
            (
                'admin.page.new',
                $handle instanceof WbBarcode ? 'admin.success.new' : 'admin.danger.new',
                'admin.wb.products.barcode',
                $handle
            );

            return $this->redirectToRoute('wildberries-products:admin.barcode.index');

        }

        return $this->render(['form' => $form->createView()]);

    }

}