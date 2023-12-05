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

namespace BaksDev\Wildberries\Products\Controller\Admin\Cards;

use BaksDev\Core\Cache\AppCacheInterface;
use BaksDev\Core\Controller\AbstractController;
use BaksDev\Core\Listeners\Event\Security\RoleSecurity;
use BaksDev\Core\Messenger\MessageDispatchInterface;
use BaksDev\Wildberries\Products\Forms\Get\WbProductCardGetForm;
use BaksDev\Wildberries\Products\Messenger\WbCardNew\WbCardNewMessage;
use DateInterval;
use Psr\Cache\CacheItemInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Cache\CacheInterface;

#[AsController]
#[RoleSecurity('ROLE_WB_CARDS_GET')]
final class GetController extends AbstractController
{
    #[Route('/admin/wb/product/card/get', name: 'admin.card.get', methods: ['GET', 'POST'])]
    public function Update(
        Request $request,
        AppCacheInterface $cache,
        MessageDispatchInterface $messageDispatch
    ): Response
    {

        $form = $this->createForm(WbProductCardGetForm::class, null, [
            'action' => $this->generateUrl('wildberries-products:admin.card.get'),
        ]);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->has('wb_product_card_get'))
        {
            /**
             * Предотвращаем обновление чаще раз в 5 минут
             * @var CacheInterface $AppCache
             */
            $AppCache = $cache->init('WildberriesProductsUpgrade');

            /** @var CacheItemInterface $item */
            $item = $AppCache->getItem((string) $this->getProfileUid());

            if(!$item->isHit())
            {
                $item->set(true);
                $item->expiresAfter(DateInterval::createFromDateString('5 minutes'));
                $AppCache->save($item);

                /* Отправляем сообщение в шину профиля */
                $messageDispatch->dispatch(
                    message: new WbCardNewMessage($this->getProfileUid()),
                    transport: (string) $this->getProfileUid(),
                );

                $this->addFlash
                (
                    'admin.page.get',
                    'admin.success.get',
                    'admin.wb.products.card',
                );

            }
            else
            {
                $this->addFlash
                (
                    'admin.page.get',
                    'admin.danger.get',
                    'admin.wb.products.card'
                );
            }


            return $this->redirectToRoute('wildberries-products:admin.card.index');
        }

        return $this->render(['form' => $form->createView()]);
    }
}
