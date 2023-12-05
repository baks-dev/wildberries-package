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

namespace BaksDev\Wildberries\Products\UseCase\Barcode\NewEdit;

//use App\Module\Products\Category\Repository\CategoryChoice\CategoryChoiceInterface;
//use App\Module\Products\Category\Repository\PropertyFieldsByCategoryChoiceForm\PropertyFieldsByCategoryChoiceFormInterface;
//use App\Module\Products\Category\Type\Id\CategoryUid;
use BaksDev\Products\Category\Repository\CategoryChoice\CategoryChoiceInterface;
use BaksDev\Products\Category\Repository\PropertyFieldsCategoryChoice\PropertyFieldsCategoryChoiceInterface;
use BaksDev\Products\Category\Type\Id\ProductCategoryUid;
use BaksDev\Products\Category\Type\Section\Field\Id\ProductCategorySectionFieldUid;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\ButtonType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class WbBarcodeForm extends AbstractType
{

    private CategoryChoiceInterface $categoryChoice;
    private PropertyFieldsCategoryChoiceInterface $propertyFields;

    public function __construct(
        CategoryChoiceInterface $categoryChoice,
        PropertyFieldsCategoryChoiceInterface $propertyFields,
    )
    {
        $this->categoryChoice = $categoryChoice;
        $this->propertyFields = $propertyFields;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /* TextType */

        $builder->add('category', ChoiceType::class, [
            'choices' => $this->categoryChoice->getCategoryCollection(),
            'choice_value' => function(?ProductCategoryUid $category) {
                return $category?->getValue();
            },
            'choice_label' => function(ProductCategoryUid $category) {
                return $category->getOptions();
            },
        ]);

        $builder->add('offer', CheckboxType::class, ['required' => false]);
        $builder->add('variation', CheckboxType::class, ['required' => false]);
        $builder->add('counter', IntegerType::class);

        $builder->add('property', CollectionType::class, [
            'entry_type' => Property\WbBarcodePropertyForm::class,
            'entry_options' => ['label' => false],
            'label' => false,
            'by_reference' => false,
            'allow_delete' => true,
            'allow_add' => true,
            'prototype_name' => '__property__'
        ]);

        $builder->add('custom', CollectionType::class, [
            'entry_type' => Custom\WbBarcodeCustomForm::class,
            'entry_options' => ['label' => false],
            'label' => false,
            'by_reference' => false,
            'allow_delete' => true,
            'allow_add' => true,
            'prototype_name' => '__custom__'
        ]);

        /* Прототип списка свойств */
        $builder->add('offer_prototype', HiddenType::class, ['mapped' => false, 'disabled' => true]);


        /* Добавить коллекцию ******************************************************/
        $builder->add
        (
            'addProperty',
            ButtonType::class,
            ['label_html' => true, 'attr' => ['class' => 'btn-sm btn-outline-primary border-0'], 'disabled' => true]);


        /* Добавить коллекцию ******************************************************/
        $builder->add
        (
            'addCustom',
            ButtonType::class,
            ['label_html' => true, 'attr' => ['class' => 'btn-sm btn-outline-primary border-0']]);


        $formModifier = function(FormInterface $form, ?ProductCategoryUid $category = null) {
            if($category)
            {
                $choice = $this->propertyFields->getPropertyFieldsCollection($category);

                $form
                    ->add('offer_prototype', ChoiceType::class, [
                        'choices' => $choice,
                        'choice_value' => function(?ProductCategorySectionFieldUid $type) {
                            return $type?->getValue();
                        },
                        'choice_label' => function(ProductCategorySectionFieldUid $type) {
                            return $type->getAttr();
                        },

                        'label' => false,
                        'expanded' => false,
                        'multiple' => false,
                        'mapped' => false,
                        'attr' => ['style' => 'display: none;'],
                        'disabled' => empty($choice),
                    ]);


                $form->add('property', CollectionType::class, [
                    'entry_type' => Property\WbBarcodePropertyForm::class,
                    'entry_options' => ['label' => false, 'offer_fields' => $choice],
                    'label' => false,
                    'by_reference' => false,
                    'allow_delete' => true,
                    'allow_add' => true,
                    'prototype_name' => '__property__'
                ]);


                /* Добавить коллекцию ******************************************************/
                $form->add
                (
                    'addProperty',
                    ButtonType::class,
                    [
                        'label' => 'Add',
                        'label_html' => true,
                        'attr' => ['class' => 'btn-sm btn-outline-primary border-0'],
                        'disabled' => empty($choice)
                    ]);
            }

        };

        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function(FormEvent $event) use ($formModifier) {
                /** @var WbBarcodeDTO $data */
                $data = $event->getData();
                $builder = $event->getForm();

                if($data->getCategory() && $data->isHiddenCategory())
                {
                    $category = $this->categoryChoice->getProductCategory($data->getCategory());

                    $builder->add('category', HiddenType::class, [
                        'label' => $category?->getOptions()
                    ]);
                }

                $formModifier($builder, $data->getCategory());
            }
        );

        $builder->get('category')->addEventListener(
            FormEvents::POST_SUBMIT,
            function(FormEvent $event) use ($formModifier) {
                $category = $event->getForm()->getData();

                if($category)
                {
                    $formModifier($event->getForm()->getParent(), $category);
                }
            }
        );

//        $builder->get('category')->addModelTransformer(
//            new CallbackTransformer(
//                function($category) {
//                    return $category instanceof ProductCategoryUid ? $category->getValue() : $category;
//                },
//                function($category) {
//
//                    return new ProductCategoryUid($category);
//                }
//            )
//        );


        /* Сохранить ******************************************************/
        $builder->add
        (
            'wb_barcode',
            SubmitType::class,
            ['label' => 'Save', 'label_html' => true, 'attr' => ['class' => 'btn-primary']]);

    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults
        (
            [
                'data_class' => WbBarcodeDTO::class,
                'method' => 'POST',
                'attr' => ['class' => 'w-100'],
            ]);
    }

}
