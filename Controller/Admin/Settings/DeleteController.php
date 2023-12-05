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

namespace BaksDev\Wildberries\Products\Controller\Admin\Settings;

//use BaksDev\Core\Controller\AbstractController;
use BaksDev\Core\Controller\AbstractController;
use BaksDev\Core\Listeners\Event\Security\RoleSecurity;
use BaksDev\Wildberries\Products\Entity\Settings\Event\WbProductSettingsEvent;
use BaksDev\Wildberries\Products\Entity\Settings\WbProductSettings;
use BaksDev\Wildberries\Products\UseCase\Settings\Delete\DeleteWbProductSettingsDTO;
use BaksDev\Wildberries\Products\UseCase\Settings\Delete\DeleteWbProductSettingsForm;
use BaksDev\Wildberries\Products\UseCase\Settings\Delete\DeleteWbProductSettingsHandler;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Annotation\Route;

#[AsController]
#[RoleSecurity('ROLE_WB_PRODUCTS_SETTING_DELETE')]
final class DeleteController extends AbstractController
{

    #[Route('/admin/wb/product/setting/delete/{id}', name: 'admin.settings.delete', methods: ['POST', 'GET'])]
    public function delete(
        Request $request,
        DeleteWbProductSettingsHandler $ProductSettingsHandler,
        #[MapEntity] WbProductSettingsEvent $Event,
    ): Response
    {
        $DeleteWbProductSettingsDTO = new DeleteWbProductSettingsDTO();
        $Event->getDto($DeleteWbProductSettingsDTO);

        $form = $this->createForm(DeleteWbProductSettingsForm::class, $DeleteWbProductSettingsDTO, [
            'action' => $this->generateUrl('wildberries-products:admin.settings.delete', ['id' => $DeleteWbProductSettingsDTO->getEvent()]),
        ]);

        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid() && $form->has('delete_product_settings'))
        {
            $WbProductSettings = $ProductSettingsHandler->handle($DeleteWbProductSettingsDTO);

            if($WbProductSettings instanceof WbProductSettings)
            {
                $this->addFlash('admin.page.delete', 'admin.success.delete', 'admin.wb.products.settings');

                return $this->redirectToRoute('wildberries-products:admin.settings.index');
            }

            $this->addFlash(
                'admin.page.delete',
                'admin.danger.delete',
                'admin.wb.products.settings',
                $WbProductSettings,
            );

            return $this->redirectToRoute('wildberries-products:admin.settings.index', status: 400);

        }

        return $this->render([
            'form' => $form->createView(),
            'name' => $Event->getName(),
        ],);
    }

}