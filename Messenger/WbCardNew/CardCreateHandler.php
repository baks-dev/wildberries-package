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

use BaksDev\Core\Type\Field\InputField;
use BaksDev\Products\Category\Repository\OfferByCategory\OfferByCategoryInterface;
use BaksDev\Products\Category\Repository\VariationByCategory\VariationByOfferInterface;
use BaksDev\Products\Category\Type\Id\ProductCategoryUid;
use BaksDev\Products\Category\Type\Offers\Id\ProductCategoryOffersUid;
use BaksDev\Products\Category\Type\Offers\Variation\ProductCategoryVariationUid;
use BaksDev\Products\Category\Type\Section\Field\Id\ProductCategorySectionFieldUid;
use BaksDev\Products\Category\UseCase\Admin\NewEdit\Offers\ProductCategoryOffersDTO;
use BaksDev\Products\Category\UseCase\Admin\NewEdit\Offers\Variation\ProductCategoryVariationDTO;
use BaksDev\Products\Product\Entity\Event\ProductEvent;
use BaksDev\Products\Product\Entity\Offers\Image\ProductOfferImage;
use BaksDev\Products\Product\Entity\Offers\Variation\Image\ProductVariationImage;
use BaksDev\Products\Product\Entity\Product;
use BaksDev\Products\Product\UseCase\Admin\NewEdit\Category\CategoryCollectionDTO;
use BaksDev\Products\Product\UseCase\Admin\NewEdit\Description\ProductDescriptionDTO;
use BaksDev\Products\Product\UseCase\Admin\NewEdit\Offers\Image\ProductOfferImageCollectionDTO;
use BaksDev\Products\Product\UseCase\Admin\NewEdit\Offers\Price\ProductOfferPriceDTO;
use BaksDev\Products\Product\UseCase\Admin\NewEdit\Offers\ProductOffersCollectionDTO;
use BaksDev\Products\Product\UseCase\Admin\NewEdit\Offers\Quantity\ProductOfferQuantityDTO;
use BaksDev\Products\Product\UseCase\Admin\NewEdit\Offers\Variation\Image\ProductVariationImageCollectionDTO;
use BaksDev\Products\Product\UseCase\Admin\NewEdit\Offers\Variation\Price\ProductVariationPriceDTO;
use BaksDev\Products\Product\UseCase\Admin\NewEdit\Offers\Variation\ProductOffersVariationCollectionDTO;
use BaksDev\Products\Product\UseCase\Admin\NewEdit\Offers\Variation\Quantity\ProductVariationQuantityDTO;
use BaksDev\Products\Product\UseCase\Admin\NewEdit\Price\PriceDTO;
use BaksDev\Products\Product\UseCase\Admin\NewEdit\ProductDTO;
use BaksDev\Products\Product\UseCase\Admin\NewEdit\ProductHandler;
use BaksDev\Products\Product\UseCase\Admin\NewEdit\Property\PropertyCollectionDTO;
use BaksDev\Products\Product\UseCase\Admin\NewEdit\Trans\ProductTransDTO;
use BaksDev\Wildberries\Api\Token\Card\WildberriesCardImage;
use BaksDev\Wildberries\Api\Token\Card\WildberriesCards\Card;
use BaksDev\Wildberries\Api\Token\Prices\PricesInfo\PricesInfo;
use BaksDev\Wildberries\Api\Token\Stocks\GetStocks\Stocks;
use BaksDev\Wildberries\Api\Token\Stocks\GetStocks\WildberriesStocks;
use BaksDev\Wildberries\Api\Token\Warehouse\PartnerWildberries\PartnerWarehouses;
use BaksDev\Wildberries\Products\Entity\Cards\WbProductCard;
use BaksDev\Wildberries\Products\Entity\Settings\Event\WbProductSettingsEvent;
use BaksDev\Wildberries\Products\Repository\Settings\ProductSettingsByParentAndName\ProductSettingsByParentAndNameInterface;
use BaksDev\Wildberries\Products\UseCase\Cards\NewEdit\Offer\WbProductCardOfferDTO;
use BaksDev\Wildberries\Products\UseCase\Cards\NewEdit\Variation\WbProductCardVariationDTO;
use BaksDev\Wildberries\Products\UseCase\Cards\NewEdit\WbProductCardDTO;
use BaksDev\Wildberries\Products\UseCase\Cards\NewEdit\WbProductCardHandler;
use BaksDev\Wildberries\Products\UseCase\Settings\NewEdit\Property\WbProductSettingsPropertyDTO;
use BaksDev\Wildberries\Products\UseCase\Settings\NewEdit\WbProductsSettingsDTO;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\TaggedIterator;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class CardCreateHandler
{

    private Card $Card;
    private ProductDTO $ProductDTO;
    private WbProductCardDTO $WbProductCardDTO;

    private iterable $reference;
    private ProductSettingsByParentAndNameInterface $productSettingsByParentAndName;
    private OfferByCategoryInterface $offerByCategory;
    private VariationByOfferInterface $variationByOffer;
    private LoggerInterface $messageDispatchLogger;
    private WildberriesCardImage $wildberriesCardImage;
    private PricesInfo $wildberriesPricesInfo;
    private WildberriesStocks $wildberriesStocks;
    private PartnerWarehouses $wildberriesPartnerWarehouses;
    private ProductHandler $productHandler;
    private WbProductCardHandler $wbProductCardHandler;
    private EntityManagerInterface $entityManager;

    public function __construct(
        #[TaggedIterator('baks.reference.choice')] iterable $reference,
        ProductSettingsByParentAndNameInterface $productSettingsByParentAndName,
        OfferByCategoryInterface $offerByCategory,
        VariationByOfferInterface $variationByOffer,
        LoggerInterface $messageDispatchLogger,
        WildberriesCardImage $wildberriesCardImage,
        PricesInfo $wildberriesPricesInfo,
        WildberriesStocks $wildberriesStocks,
        PartnerWarehouses $wildberriesPartnerWarehouses,
        ProductHandler $productHandler,
        WbProductCardHandler $wbProductCardHandler,
        EntityManagerInterface $entityManager
    )
    {
        $this->productSettingsByParentAndName = $productSettingsByParentAndName;
        $this->offerByCategory = $offerByCategory;
        $this->variationByOffer = $variationByOffer;
        $this->reference = $reference;
        $this->messageDispatchLogger = $messageDispatchLogger;
        $this->wildberriesCardImage = $wildberriesCardImage;
        $this->wildberriesPricesInfo = $wildberriesPricesInfo;
        $this->wildberriesStocks = $wildberriesStocks;
        $this->wildberriesPartnerWarehouses = $wildberriesPartnerWarehouses;
        $this->productHandler = $productHandler;
        $this->wbProductCardHandler = $wbProductCardHandler;
        $this->entityManager = $entityManager;
    }

    public function __invoke(Card $Card): void
    {
        $this->Card = $Card->getCardDetail();

        /* Создаем системный продукт */
        $this->createProduct();

        /* Получаем настройку соотношений Wildberries */
        $WbProductsSettingsDTO = $this->getSettingsWildberriesCategory();

        /* Присваиваем корневую категорию  */
        $this->createProductRootCategory($WbProductsSettingsDTO->getSettings());


        /** Характеристики продукции */
        /** @var WbProductSettingsPropertyDTO $property */
        foreach($WbProductsSettingsDTO->getProperty() as $property)
        {
            $characteristics = $Card->getCharacteristic($property->getType());

            if($characteristics)
            {
                $this->createProductProperty($property->getField(), $characteristics);
            }
        }

        if($Card->getColor())
        {
            /**
             * Торговое предложение продукции
             */

            $SettingsCategoryOffers = $this->getSettingsCategoryOffers($WbProductsSettingsDTO->getSettings());
            $ProductOffer = $this->createProductOffer($SettingsCategoryOffers?->getId());
            $ProductOffer->setValue($Card->getColor());

            /* Если свойство из справочника */
            if($SettingsCategoryOffers && $SettingsCategoryOffers->getReference())
            {
                $this->setReference($ProductOffer, $SettingsCategoryOffers->getReference(), $Card->getColor());
            }

            /* Если торговое предложение с артикулом */
            if($SettingsCategoryOffers->getArticle())
            {
                $ProductOffer->setArticle($Card->getArticle());
                $this->ProductDTO->getInfo()->setArticle($Card->getBaseArticle());
            }

            /* Если торговое предложение с возможностью загрузки пользовательского изображение */
            if($SettingsCategoryOffers->getImage())
            {
                $this->createMediaFile($ProductOffer, ProductOfferImage::TABLE, ProductOfferImageCollectionDTO::class);
            }

            /* Если торговое предложение с ценой  */
            if($SettingsCategoryOffers->getPrice())
            {
                $this->createProductOfferPrice($ProductOffer);
            }

            /* Если торговое предложение с количественным учетом */
            if($SettingsCategoryOffers->getQuantitative())
            {
                $this->createProductOfferQuantity($ProductOffer, $Card->getCurrentBarcode());
            }


            /**
             * Множественный варианты продукции
             */

            $SettingsCategoryVariation = $this->getSettingsCategoryVariation($WbProductsSettingsDTO->getSettings());


            if(!$SettingsCategoryVariation || !$Card->isOffers())
            {
                /** Всегда создаем по умолчанию множественный вариант с нулевым значением */
                $ProductVariation = $this->createProductVariation($ProductOffer, $Card->getCurrentBarcode(), $SettingsCategoryVariation?->getId());
                $ProductVariation->setValue($Card->getCurrentValue());
            }
            else
            {
                foreach($this->Card->getOffersCollection() as $barcode => $value)
                {
                    $ProductVariation = $this->createProductVariation($ProductOffer, $barcode, $SettingsCategoryVariation->getId());
                    $ProductVariation->setValue($value);


                    /* Если множественное свойство из справочника */
                    if($SettingsCategoryVariation->getReference())
                    {
                        $this->setReference($ProductVariation, $SettingsCategoryVariation->getReference(), $value);
                    }

                    /* Если множественный вариант с артикулом */
                    if($SettingsCategoryVariation->getArticle())
                    {
                        $ProductVariation->setArticle($Card->getArticle());
                        $this->ProductDTO->getInfo()->setArticle($Card->getBaseArticle());
                    }

                    /* Если множественный вариант с возможностью загрузки пользовательского изображение */
                    if($SettingsCategoryVariation->getImage())
                    {
                        $this->createMediaFile($ProductVariation, ProductVariationImage::TABLE, ProductVariationImageCollectionDTO::class);
                    }

                    /* Если множественный вариант с ценой  */
                    if($SettingsCategoryVariation->getPrice())
                    {
                        $this->createProductVariationPrice($ProductVariation);
                    }

                    /* Если множественный вариант с количественным учетом */
                    if($SettingsCategoryVariation->getQuantitative())
                    {
                        $this->createProductVariationQuantity($ProductVariation, $barcode);
                    }
                }
            }

        }


        $Product = $this->productHandler->handle($this->ProductDTO);

        if($Product instanceof Product)
        {
            $this->WbProductCardDTO->setProduct($Product);

            $ProductCard = $this->wbProductCardHandler->handle($this->WbProductCardDTO);

            if(!$ProductCard instanceof WbProductCard)
            {
                /** Удаляем продукт */

                $DeleteEvent = $this->entityManager->getRepository(ProductEvent::class)->findBy(
                    ['product' => $Product->getId()],
                );

                foreach($DeleteEvent as $delete)
                {
                    $this->entityManager->remove($delete);
                }

                $DeleteProduct = $this->entityManager->getRepository(Product::class)->find($Product->getId());
                $this->entityManager->remove($DeleteProduct);

                $this->entityManager->flush();
            }


        }
    }

    public function createProduct(): void
    {
        $this->ProductDTO = new ProductDTO();

        /* Присваиваем неизменную информацию о продукте */
        $ProductInfo = $this->ProductDTO->getInfo();
        $ProductInfo->setUrl(uniqid('', false));
        $ProductInfo->setProfile($this->Card->getProfile());
        $ProductInfo->setArticle($this->Card->getColor() ? $this->Card->getBaseArticle() : $this->Card->getArticle());
        $this->ProductDTO->setInfo($ProductInfo);


        /* Название файла */
        /** @var ProductTransDTO $ProductTransDTO */
        foreach($this->ProductDTO->getTranslate() as $ProductTransDTO)
        {
            $ProductTransDTO->setName($this->Card->getCategory());
        }

        /* Описание файла */
        /** @var ProductDescriptionDTO $ProductDescriptionDTO */
        foreach($this->ProductDTO->getDescription() as $ProductDescriptionDTO)
        {
            $ProductDescriptionDTO->setPreview($this->Card->getName());
            $ProductDescriptionDTO->setPreview($this->Card->getDescription());
        }

        /** Стоимость продукции Wildberries всегда общая для всех торговых предложений */
        $wildberriesPrices = $this->wildberriesPricesInfo->profile($this->Card->getProfile())->prices();
        $Price = $wildberriesPrices->getPriceByNomenclature($this->Card->getNomenclature());

        $PriceDTO = new PriceDTO();
        $PriceDTO->setPrice($Price?->getPrice());
        $this->ProductDTO->setPrice($PriceDTO);

        /** Остатки продукции по всем баркодам (Wildberries Api) */
        $wildberriesStocks = $this->getWildberriesStocks();

        if($wildberriesStocks)
        {
            $Quantity = $wildberriesStocks->getAmount($this->Card->getCurrentBarcode());
            $PriceDTO->setQuantity($Quantity);
        }


        /* Создаем карточку Wildberries */
        $WbProductCardDTO = new WbProductCardDTO();
        $WbProductCardDTO->setImtId($this->Card->getNomenclature());
        $this->WbProductCardDTO = $WbProductCardDTO;
    }


    public function createProductOffer(?ProductCategoryOffersUid $offer): ProductOffersCollectionDTO
    {
        $OffersCollectionDTO = new ProductOffersCollectionDTO();
        $OffersCollectionDTO->setCategoryOffer($offer);
        $this->ProductDTO->addOffer($OffersCollectionDTO);

        /* Создаем торговое предложение в карточке Wildberries */
        $WbProductCardOfferDTO = new WbProductCardOfferDTO();
        $WbProductCardOfferDTO->setOffer($OffersCollectionDTO->getConst());
        $WbProductCardOfferDTO->setNomenclature($this->Card->getNomenclature());
        $this->WbProductCardDTO->addOffer($WbProductCardOfferDTO);

        return $OffersCollectionDTO;
    }

    public function createProductVariation(
        ProductOffersCollectionDTO $ProductOffer,
        int|string $barcode,
        ?ProductCategoryVariationUid $variation = null
    ): ProductOffersVariationCollectionDTO
    {
        $ProductOffersVariationCollectionDTO = new ProductOffersVariationCollectionDTO();
        $ProductOffersVariationCollectionDTO->setCategoryVariation($variation);
        $ProductOffer->addVariation($ProductOffersVariationCollectionDTO);


        /** Присваиваем карточке баркод и константу множественного варианта */
        $WbProductCardVariationDTO = new WbProductCardVariationDTO();
        $WbProductCardVariationDTO->setBarcode((string) $barcode);
        $WbProductCardVariationDTO->setVariation($ProductOffersVariationCollectionDTO->getConst());

        $this->WbProductCardDTO->addVariation($WbProductCardVariationDTO);

        return $ProductOffersVariationCollectionDTO;
    }


    public function createProductRootCategory(ProductCategoryUid $category): void
    {
        /* Корневая категория */
        $CategoryCollectionDTO = new CategoryCollectionDTO();
        $CategoryCollectionDTO->setRoot(true);
        $CategoryCollectionDTO->setCategory($category);
        $this->ProductDTO->addCategory($CategoryCollectionDTO);
    }

    public function getSettingsCategoryOffers(ProductCategoryUid $category): ?ProductCategoryOffersDTO
    {
        /** Настройка торгового предложения категории  */
        $ProductCategoryOffer = $this->offerByCategory
            ->findProductCategoryOffer($category);

        return $ProductCategoryOffer?->getDto(ProductCategoryOffersDTO::class);
    }

    public function getSettingsCategoryVariation(ProductCategoryUid $category): ?ProductCategoryVariationDTO
    {
        /** Настройка торгового предложения категории  */
        $ProductCategoryOffer = $this->offerByCategory
            ->findProductCategoryOffer($category);

        if($ProductCategoryOffer)
        {
            $ProductCategoryVariation =
                $this->variationByOffer->findProductCategoryVariation(
                    $ProductCategoryOffer->getId(),
                );

            return $ProductCategoryVariation?->getDto(ProductCategoryVariationDTO::class);

        }

        return null;
    }


    /** Присваивает значение из справочника */
    public function setReference(
        ProductOffersCollectionDTO|ProductOffersVariationCollectionDTO $object,
        InputField $inputFieldReference,
        mixed $value
    ): void
    {
        foreach($this->reference as $reference)
        {
            if($reference->type() === $inputFieldReference->getType())
            {
                $referenceClass = $reference->class();
                $offerReference = new $referenceClass($value);

                /** Если нет значения в библиотеке - пропускаем */
                if(empty((string) $offerReference))
                {
                    $error = sprintf('%s: В библиотеке отсутствует значение', $this->Card->getProfile());

                    $this->messageDispatchLogger->warning($error,
                        [
                            'class' => $referenceClass,
                            'value' => $this->Card->getColor(),
                            __FILE__.':'.__LINE__

                        ]);

                    continue;
                }

                $object->setValue((string) $offerReference);
                break;
            }
        }
    }


    public function createMediaFile(
        ProductOffersCollectionDTO|ProductOffersVariationCollectionDTO $parent,
        string $table,
        string $class
    ): void
    {
        $root = true;

        foreach($this->Card->getMedia() as $mediaFile)
        {
            $arrImage = ['png', 'jpg', 'jpeg', 'webp', 'gif'];

            $mediaFileInfo = pathinfo($mediaFile);

            if(!in_array($mediaFileInfo['extension'], $arrImage))
            {
                continue;
            }

            $ImageCollectionDTO = new $class();

            $this->wildberriesCardImage->get(
                $mediaFile,
                $ImageCollectionDTO,
                $table
            );

            $ImageCollectionDTO->setRoot($root);
            $parent->addImage($ImageCollectionDTO);

            $root = false;
        }
    }


    public function getSettingsWildberriesCategory(): WbProductsSettingsDTO
    {
        $category = $this->Card->getCategory();
        /** @var WbProductSettingsEvent $WbProductSettingsEvent */
        $WbProductSettingsEvent = $this->productSettingsByParentAndName->get($category);
        return $WbProductSettingsEvent?->getDto(WbProductsSettingsDTO::class);
    }

    private function createProductOfferPrice(ProductOffersCollectionDTO $ProductOffer): void
    {
        $Price = $this->ProductDTO->getPrice()->getPrice();

        $ProductOfferPriceDTO = new ProductOfferPriceDTO();
        $ProductOfferPriceDTO->setPrice($Price);
        $ProductOffer->setPrice($ProductOfferPriceDTO);

        $this->ProductDTO->getPrice()->setPrice(null);
    }


    private function createProductVariationPrice(ProductOffersVariationCollectionDTO $ProductVariation): void
    {
        $Price = $this->ProductDTO->getPrice()->getPrice();

        $ProductVariationPriceDTO = new ProductVariationPriceDTO();
        $ProductVariationPriceDTO->setPrice($Price);
        $ProductVariation->setPrice($ProductVariationPriceDTO);

        $this->ProductDTO->getPrice()->setPrice(null);
    }


    /** Получаем все остатки указанной продукции */
    public function getWildberriesStocks(): ?Stocks
    {
        $partnerWarehouses = $this->wildberriesPartnerWarehouses
            ->profile($this->Card->getProfile())
            ->warehouses();

        foreach($partnerWarehouses as $warehouse)
        {
            $this
                ->wildberriesStocks
                ->profile($this->Card->getProfile())
                ->warehouse($warehouse->getId())
                ->resetBarcode();

            foreach($this->Card->getOffersCollection() as $barcode => $item)
            {
                $this->wildberriesStocks->addBarcode((string) $barcode);
            }

            return $this->wildberriesStocks->stocks();
        }

        return null;
    }


    private function createProductOfferQuantity(ProductOffersCollectionDTO $ProductOffer, int|string $barcode): void
    {
        /** Остатки продукции по всем баркодам (Wildberries Api) */
        $wildberriesStocks = $this->getWildberriesStocks();

        if($wildberriesStocks)
        {
            $currentQuantity = $wildberriesStocks->getAmount($barcode);

            $ProductOfferQuantityDTO = new ProductOfferQuantityDTO();
            $ProductOfferQuantityDTO->setQuantity($currentQuantity);
            $ProductOffer->setQuantity($ProductOfferQuantityDTO);

            $this->ProductDTO->getPrice()->setQuantity(null);
        }
    }


    private function createProductVariationQuantity(ProductOffersVariationCollectionDTO $ProductVariation, int|string $barcode): void
    {
        /** Остатки продукции по всем баркодам (Wildberries Api) */
        $wildberriesStocks = $this->getWildberriesStocks();

        if($wildberriesStocks)
        {
            $currentQuantity = $wildberriesStocks->getAmount($barcode);

            $ProductVariationQuantityDTO = new ProductVariationQuantityDTO();
            $ProductVariationQuantityDTO->setQuantity($currentQuantity);
            $ProductVariation->setQuantity($ProductVariationQuantityDTO);

            $this->ProductDTO->getPrice()->setQuantity(null);
        }
    }

    private function createProductProperty(ProductCategorySectionFieldUid $field, mixed $value) : void
    {
        $ProductPropertyDto = new PropertyCollectionDTO();
        $ProductPropertyDto->setField($field);
        $ProductPropertyDto->setValue((string) $value);
        $this->ProductDTO->addProperty($ProductPropertyDto);
    }


}