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

namespace BaksDev\Wildberries\Products\UseCase\Settings\NewEdit;

use BaksDev\Products\Category\Repository\PropertyFieldsCategoryChoice\PropertyFieldsCategoryChoiceInterface;

use BaksDev\Wildberries\Api\Token\Reference\Characteristics\WbCharacteristicByObjectName;
use BaksDev\Wildberries\Api\Token\Reference\Characteristics\WbCharacteristicByObjectNameDTO;
use BaksDev\Wildberries\Products\UseCase\Settings\NewEdit\Property\WbProductSettingsPropertyDTO;
use BaksDev\Wildberries\Repository\WbTokenByProfile\WbTokenByProfileInterface;
use DomainException;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class WbProductsSettingsForm extends AbstractType
{
    private PropertyFieldsCategoryChoiceInterface $propertyFields;

    private WbCharacteristicByObjectName $wbCharacteristic;

    private WbTokenByProfileInterface $wbTokenByProfile;


    public function __construct(
        PropertyFieldsCategoryChoiceInterface $propertyFields,
        WbCharacteristicByObjectName $wbCharacteristic,
        WbTokenByProfileInterface $wbTokenByProfile,
    )
    {
        $this->propertyFields = $propertyFields;
        $this->wbCharacteristic = $wbCharacteristic;
        $this->wbTokenByProfile = $wbTokenByProfile;
    }


    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function(FormEvent $event) {

            /** @var WbProductsSettingsDTO $data */
            $data = $event->getData();

            $form = $event->getForm();

            /** Коллекция свойств категории для выпадающего списка */
            $property_fields = $this->propertyFields->getPropertyFieldsCollection($data->getSettings());

            $profile = $this->wbTokenByProfile->getCurrentUserProfile();

            $characteristics = [];



            if($profile)
            {
                try
                {
                    $characteristics = $this->wbCharacteristic
                        ->profile($profile)
                        ->name($data->getName())
                        ->findCharacteristics();



                } catch(DomainException $e)
                {
                    /** Если токен авторизации не найден */
                    $characteristics = [];
                }
            }

            /** @var WbCharacteristicByObjectNameDTO $characteristic */
            foreach($characteristics as $characteristic)
            {

                if($characteristic->getName() === 'Наименование' || $characteristic->getName() === 'Описание')
                {
                    continue;
                }

                $new = true;


                /** @var WbProductSettingsPropertyDTO $property */
                foreach($data->getProperty() as $property)
                {
                    if($property->getType() === $characteristic->getName())
                    {
                        $property->setRequired($characteristic->isRequired());
                        $property->setUnit($characteristic->getUnit());
                        $property->setPopular($characteristic->isPopular());

                        $new = false;
                        break;
                    }
                }

                if($new)
                {
                    /**
                     * "objectName": "Косухи", - Наименование подкатегории
                     * "name": "Особенности модели", - Наименование характеристики
                     * "required": false, - Характеристика обязательна к заполнению
                     * "unitName": "", - Единица измерения (см, гр и т.д.)
                     * "maxCount": 1, - Максимальное кол-во значений которое можно присвоить данной характеристике
                     * "popular": false, - Характеристика популярна у пользователей
                     * "charcType": 1 - Тип характеристики (1 и 0 - строка или массив строк; 4 - число или массив чисел)
                     */

                    $WbProductSettingsPropertyDTO = new WbProductSettingsPropertyDTO();
                    $WbProductSettingsPropertyDTO->setType($characteristic->getName());

                    $WbProductSettingsPropertyDTO->setRequired($characteristic->isRequired());
                    $WbProductSettingsPropertyDTO->setUnit($characteristic->getUnit());
                    $WbProductSettingsPropertyDTO->setPopular($characteristic->isPopular());

                    $data->addProperty($WbProductSettingsPropertyDTO);
                }

            }

            /* Category Trans */
            $form->add('property', CollectionType::class, [
                'entry_type' => Property\WbProductSettingsPropertyForm::class,
                'entry_options' => ['label' => false, 'property_fields' => $property_fields],
                'label' => false,
                'by_reference' => false,
                'allow_delete' => true,
                'allow_add' => true,
            ]);

        });


        /* Сохранить ******************************************************/
        $builder->add
        (
            'product_settings',
            SubmitType::class,
            ['label' => 'Save', 'label_html' => true, 'attr' => ['class' => 'btn-primary']],
        );

    }


    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => WbProductsSettingsDTO::class,
            'method' => 'POST',
            'attr' => ['class' => 'w-100'],
        ],);
    }

}
