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
use BaksDev\Products\Product\Entity\Product;
use BaksDev\Wildberries\Products\Entity\Cards\WbProductCard;
use BaksDev\Wildberries\Products\Messenger\WbCardNew\WbCardNewMessage;
use BaksDev\Wildberries\Products\UseCase\Cards\Delete\WbProductCardDeleteDTO;
use BaksDev\Wildberries\Products\UseCase\Cards\Delete\WbProductCardDeleteForm;
use BaksDev\Wildberries\Products\UseCase\Cards\Delete\WbProductCardDeleteHandler;
use DateInterval;
use Psr\Cache\CacheItemInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
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
#[RoleSecurity('ROLE_WB_CARDS_UPDATE')]
final class UpdateController extends AbstractController
{
    /**
     * Обновляем все карточки с Wildberries
     */
    #[Route('/admin/wb/product/card/update/{id}', name: 'admin.card.update', methods: ['GET', 'POST'])]
    public function Update(
        Request $request,
        #[MapEntity] WbProductCard $WbProductCard,
        WbProductCardDeleteHandler $WbProductCardHandler,
        AppCacheInterface $cache,
        MessageDispatchInterface $messageDispatch
    ): Response
    {
        $WbProductCardUpdateDTO = new WbProductCardDeleteDTO();
        $WbProductCard->getDto($WbProductCardUpdateDTO);

        $form = $this->createForm(WbProductCardDeleteForm::class, $WbProductCardUpdateDTO, [
            'action' => $this->generateUrl('wildberries-products:admin.card.update', ['id' => $WbProductCardUpdateDTO->getId()]),
        ]);

        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid() && $form->has('wb_product_card_delete'))
        {
            $handle = $WbProductCardHandler->handle($WbProductCardUpdateDTO);


            /**
             * Запускаем команду на обновление
             */
            if($handle instanceof Product)
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
                        'admin.page.update',
                        $handle instanceof Product ? 'admin.success.update' : 'admin.danger.update',
                        'admin.wb.products.card',
                        $handle
                    );

                }
                else
                {
                    $this->addFlash
                    (
                        'admin.page.update',
                        'Уже было обновление! Запустите ручное обновление через 5 минут!',
                        'admin.wb.products.card'
                    );
                }
            }

            return $this->redirectToRoute('wildberries-products:admin.card.index');
        }

        return $this->render([
            'form' => $form->createView(),
            //'name' => $WbProductCardEvent->getNameByLocale($this->getLocale()), // название согласно локали
        ]);
    }
}
