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
use BaksDev\Core\Listeners\Event\Security\RoleSecurity;
use BaksDev\Wildberries\Package\Entity\Supply\WbSupply;
use BaksDev\Wildberries\Package\Forms\Supply\TokenForWbSupply\TokenForWbSupplyDTO;
use BaksDev\Wildberries\Package\Forms\Supply\TokenForWbSupply\TokenForWbSupplyForm;
use BaksDev\Wildberries\Package\Repository\Supply\ExistOpenSupplyProfile\ExistOpenSupplyProfileInterface;
use BaksDev\Wildberries\Package\UseCase\Supply\New\WbSupplyNewDTO;
use BaksDev\Wildberries\Package\UseCase\Supply\New\WbSupplyNewHandler;
use BaksDev\Wildberries\Repository\AllWbTokensByProfile\AllWbTokensByProfileInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
#[RoleSecurity('ROLE_WB_SUPPLY_NEW')]
final class NewController extends AbstractController
{
    /**
     * Открыть поставку
     */
    #[Route(path: '/admin/wb/supply/new', name: 'admin.supply.new', methods: ['GET', 'POST'])]
    public function controller(
        Request $request,
        ExistOpenSupplyProfileInterface $existOpenSupplyProfileRepository,
        AllWbTokensByProfileInterface $allWbTokensByProfileRepository,
        WbSupplyNewHandler $WbSupplyNewHandler,
    ): Response
    {

        $WbTokens = $allWbTokensByProfileRepository
            ->forProfile($this->getProfileUid())
            ->findAll();

        if(false === $WbTokens || false === $WbTokens->valid())
        {
            $this->addFlash(
                'danger',
                'Не найдено ни одного токена Wildberries',
                'wildberries-package.supply',
            );

            return $this->redirectToReferer();
        }

        /**
         * Выбор только тех токенов, на которые нет новой или открытой поставки Wildberries
         */
        $WbTokensForOpen = [];

        foreach($WbTokens as $WbTokenUid)
        {
            $exist = $existOpenSupplyProfileRepository
                ->forProfile($this->getProfileUid())
                ->forToken($WbTokenUid)
                ->isExistNewOrOpenSupply();

            if(false === $exist)
            {
                $WbTokensForOpen[] = $WbTokenUid;
            }
        }

        $TokenForWbSupplyDTO = new TokenForWbSupplyDTO(
            profile: $this->getProfileUid(),
            tokens: $WbTokensForOpen
        );

        $form = $this->createForm(
            type: TokenForWbSupplyForm::class,
            data: $TokenForWbSupplyDTO,
            options: ['action' => $this->generateUrl('wildberries-package:admin.supply.new'),
            ])
            ->handleRequest($request);

        if($form->isSubmitted() && $form->isValid() && $form->has('new_wb_supply'))
        {
            $this->refreshTokenForm($form);

            $WbSupplyNewDTO = new WbSupplyNewDTO(
                profile: $this->getProfileUid(),
                token: $TokenForWbSupplyDTO->getToken()
            );

            $handle = $WbSupplyNewHandler->handle($WbSupplyNewDTO);

            $this->addFlash
            (
                'page.new',
                $handle instanceof WbSupply ? 'success.new' : 'danger.new',
                'wildberries-package.supply',
                $handle,
            );

            return $this->redirectToRoute('wildberries-package:admin.package.index', ['token' => $TokenForWbSupplyDTO->getToken()]);
        }

        return $this->render(['form' => $form->createView()]);
    }
}