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

namespace BaksDev\Wildberries\Products\Controller\Admin\Barcode;


use BaksDev\Core\Controller\AbstractController;
use BaksDev\Core\Listeners\Event\Security\RoleSecurity;
use BaksDev\Wildberries\Products\Entity\Barcode\Event\WbBarcodeEvent;
use BaksDev\Wildberries\Products\Entity\Barcode\WbBarcode;
use BaksDev\Wildberries\Products\UseCase\Barcode\Delete\WbBarcodeDeleteDTO;
use BaksDev\Wildberries\Products\UseCase\Barcode\Delete\WbBarcodeDeleteForm;
use BaksDev\Wildberries\Products\UseCase\Barcode\Delete\WbBarcodeDeleteHandler;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Annotation\Route;

#[AsController]
#[RoleSecurity('ROLE_WB_BARCODE_DELETE')]
final class DeleteController extends AbstractController
{
    #[Route('/admin/wb/barcode/delete/{id}', name: 'admin.barcode.delete', methods: ['GET', 'POST'])]
    public function delete(
        Request $request,
        #[MapEntity] WbBarcodeEvent $WbBarcodeEvent,
        WbBarcodeDeleteHandler $WbBarcodeDeleteHandler,
    ): Response
    {

        $WbBarcodeDeleteDTO = new WbBarcodeDeleteDTO();
        $WbBarcodeEvent->getDto($WbBarcodeDeleteDTO);

        $form = $this->createForm(WbBarcodeDeleteForm::class, $WbBarcodeDeleteDTO, [
            'action' => $this->generateUrl('wildberries-products:admin.barcode.delete', ['id' => $WbBarcodeDeleteDTO->getEvent()]),
        ]);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid() && $form->has('wb_barcode_delete'))
        {
            $handle = $WbBarcodeDeleteHandler->handle($WbBarcodeDeleteDTO);

            $this->addFlash
            (
                'admin.page.delete',
                $handle instanceof WbBarcode ? 'admin.success.delete' : 'admin.danger.delete',
                'admin.wb.products.barcode',
                $handle
            );

            return $this->redirectToRoute('wildberries-products:admin.barcode.index');

        }

        return $this->render(['form' => $form->createView()]);
    }
}
