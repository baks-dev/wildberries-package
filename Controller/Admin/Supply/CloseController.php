<?php
/*
 *  Copyright 2024.  Baks.dev <admin@baks.dev>
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
use BaksDev\Core\Listeners\Event\Security\RoleSecurity;
use BaksDev\Wildberries\Package\Entity\Supply\WbSupply;
use BaksDev\Wildberries\Package\Repository\Supply\WbSupplyCurrentEvent\WbSupplyCurrentEventInterface;
use BaksDev\Wildberries\Package\UseCase\Supply\Close\WbSupplyCloseDTO;
use BaksDev\Wildberries\Package\UseCase\Supply\Close\WbSupplyCloseForm;
use BaksDev\Wildberries\Package\UseCase\Supply\Close\WbSupplyCloseHandler;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Exception\RouteNotFoundException;

#[AsController]
#[RoleSecurity('ROLE_WB_SUPPLY_CLOSE')]
final class CloseController extends AbstractController
{
    /**
     * Закрыть поставку
     */
    #[Route('/admin/wb/supply/close/{id}', name: 'admin.supply.close', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        #[MapEntity] WbSupply $WbSupply,
        WbSupplyCurrentEventInterface $supplyCurrentEvent,
        WbSupplyCloseHandler $WbSupplyCloseHandler,
    ): Response
    {

        $WbSupplyEvent = $supplyCurrentEvent->findWbSupplyEvent($WbSupply);

        if(!$WbSupplyEvent)
        {
            throw new RouteNotFoundException('Supply Event Not Found');
        }


        $WbSupplyCloseDTO = new WbSupplyCloseDTO();
        $WbSupplyEvent->getDto($WbSupplyCloseDTO);

        // Форма
        $form = $this->createForm(WbSupplyCloseForm::class, $WbSupplyCloseDTO, [
            'action' => $this->generateUrl('wildberries-package:admin.supply.close', ['id' => $WbSupply->getId()]),
        ]);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid() && $form->has('wb_supply_close'))
        {
            $this->refreshTokenForm($form);

            $handle = $WbSupplyCloseHandler->handle($WbSupplyCloseDTO);

            $this->addFlash
            (
                'admin.page.close',
                $handle instanceof WbSupply ? 'admin.success.close' : 'admin.danger.close',
                'admin.wb.supply',
                $handle
            );

            return $this->redirectToReferer();

        }

        return $this->render([
            'form' => $form->createView(),
            'identifier' => $WbSupplyEvent->getIdentifier()
        ]);
    }
}
