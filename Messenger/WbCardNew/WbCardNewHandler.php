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

namespace BaksDev\Wildberries\Products\Messenger\WbCardNew;


use BaksDev\Core\Messenger\MessageDispatchInterface;
use BaksDev\Products\Category\Type\Id\ProductCategoryUid;
use BaksDev\Products\Product\Repository\ProductByArticle\ProductEventByArticleInterface;
use BaksDev\Products\Product\UseCase\Admin\Delete\ProductDeleteDTO;
use BaksDev\Products\Product\UseCase\Admin\Delete\ProductDeleteHandler;
use BaksDev\Products\Product\UseCase\Admin\NewEdit\Category\CategoryCollectionDTO;
use BaksDev\Products\Product\UseCase\Admin\NewEdit\Description\ProductDescriptionDTO;
use BaksDev\Products\Product\UseCase\Admin\NewEdit\Trans\ProductTransDTO;
use BaksDev\Users\Profile\UserProfile\Type\Id\UserProfileUid;
use BaksDev\Wildberries\Api\Token\Card\WildberriesCards\Card;
use BaksDev\Wildberries\Api\Token\Card\WildberriesCards\WildberriesCards;
use BaksDev\Wildberries\Api\Token\Stocks\GetStocks\Stocks;
use BaksDev\Wildberries\Api\Token\Stocks\GetStocks\WildberriesStocks;
use BaksDev\Wildberries\Api\Token\Warehouse\PartnerWildberries\PartnerWarehouses;
use BaksDev\Wildberries\Products\Messenger\WbCardNew\Card\WbCardMessage;
use BaksDev\Wildberries\Products\Repository\Cards\ExistCardOfferByNomenclature\ExistCardOfferByNomenclatureInterface;
use BaksDev\Wildberries\Products\Repository\Settings\ProductSettingsByParentAndName\ProductSettingsByParentAndNameInterface;
use BaksDev\Wildberries\Products\UseCase\Settings\NewEdit\WbProductsSettingsDTO;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class WbCardNewHandler
{
    private WildberriesCards $wildberriesCards;
    private ProductSettingsByParentAndNameInterface $productSettingsByParentAndName;
    private ExistCardOfferByNomenclatureInterface $existCardOfferByNomenclature;
    private PartnerWarehouses $wildberriesWarehouses;
    private WildberriesStocks $wildberriesStocks;
    private MessageDispatchInterface $messageDispatch;
    private LoggerInterface $messageDispatchLogger;
    private UserProfileUid $profile;
    private ProductEventByArticleInterface $productEventByArticle;
    private ProductDeleteHandler $productDeleteHandler;

    public function __construct(
        WildberriesCards $wildberriesCards,
        ProductSettingsByParentAndNameInterface $productSettingsByParentAndName,
        ExistCardOfferByNomenclatureInterface $existCardOfferByNomenclature,
        PartnerWarehouses $wildberriesWarehouses,
        WildberriesStocks $wildberriesStocks,
        MessageDispatchInterface $messageDispatch,
        LoggerInterface $messageDispatchLogger,
        ProductEventByArticleInterface $productEventByArticle,
        ProductDeleteHandler $productDeleteHandler

    )
    {

        $this->wildberriesCards = $wildberriesCards;
        $this->productSettingsByParentAndName = $productSettingsByParentAndName;
        $this->existCardOfferByNomenclature = $existCardOfferByNomenclature;
        $this->wildberriesWarehouses = $wildberriesWarehouses;
        $this->wildberriesStocks = $wildberriesStocks;
        $this->messageDispatch = $messageDispatch;
        $this->messageDispatchLogger = $messageDispatchLogger;
        $this->productEventByArticle = $productEventByArticle;
        $this->productDeleteHandler = $productDeleteHandler;
    }

    /**
     * Получаем карточки товаров и добавляем отсутствующие
     */
    public function __invoke(WbCardNewMessage $message)
    {
        $this->profile = $message->getProfile();

        /** Все карточки товаров (Wildberries Api) */
        $WildberriesCards = $this
            ->wildberriesCards
            ->limit(1000)
            ->profile($this->profile);


        $count = 0;

        while(true)
        {
            /** @var Card $Card */
            $cards = $WildberriesCards->findAll();

            foreach($cards as $Card)
            {
                /* Пропускаем, если карточка без фото */
                if($Card->countMedia() === 0)
                {
                    $error = sprintf(
                        '%s: Торговое предложение с номенклатурой %s без фото (%s)',
                        $message->getProfile(),
                        $Card->getNomenclature(),
                        $Card->getArticle(),
                    );

                    $this->messageDispatchLogger->warning($error);
                }

                /* Если имеется торговое предложение имеется */
                if($this->existCardOfferByNomenclature->isExist($Card->getNomenclature()))
                {
                    continue;
                }

                /* Если нет номенклатуры, но имеется карточка с артикулом */
                if($ProductEvent = $this->productEventByArticle->findProductEventByArticle($Card->getArticle()))
                {
                    // Удаляем карточку
                    $ProductDeleteDTO = $ProductEvent->getDto(ProductDeleteDTO::class);
                    $this->productDeleteHandler->handle($ProductDeleteDTO);
                }

                $WbProductsSettingsDTO = $this->getSettingsWildberriesRelationCategory($Card->getCategory());

                if(!$WbProductsSettingsDTO)
                {
                    $error = sprintf(
                        '%s: Для товара %s не найдено настройки соотношений %s',
                        $message->getProfile(),
                        $Card->getArticle(),
                        $Card->getCategory(),
                    );

                    $this->messageDispatchLogger->warning($error);

                    continue;
                }

                $this->messageDispatch->dispatch(
                    $Card,
                    transport: (string) $this->profile
                );


            }

            if($WildberriesCards->getCount() < $WildberriesCards->getLimit())
            {

                $error = sprintf(
                    '%s: Карточки успешно обновлены в очередь',
                    $message->getProfile()
                );

                $this->messageDispatchLogger->info($error);
                break;
            }
        }
    }


    /** Настройки соотношений категории */
    public function getSettingsWildberriesRelationCategory(string $category): ?WbProductsSettingsDTO
    {
        $WbProductSettingsEvent = $this->productSettingsByParentAndName
            ->get($category);

        return $WbProductSettingsEvent?->getDto(WbProductsSettingsDTO::class);
    }

}