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

namespace BaksDev\Wildberries\Products\Forms\Preform;

use BaksDev\Products\Category\Repository\CategoryChoice\CategoryChoiceInterface;
use BaksDev\Products\Category\Type\Id\ProductCategoryUid;
use BaksDev\Wildberries\Api\Token\Reference\Object\WbObject;
use BaksDev\Wildberries\Api\Token\Reference\Object\WbObjectDTO;
use BaksDev\Wildberries\Repository\WbTokenByProfile\WbTokenByProfileInterface;
use DomainException;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class PreformForm extends AbstractType
{

    private CategoryChoiceInterface $categoryChoice;

    private WbObject $objectReference;

    private WbTokenByProfileInterface $tokenByProfile;

    public function __construct(
        CategoryChoiceInterface $categoryChoice,
        WbTokenByProfileInterface $tokenByProfile,
        WbObject $objectReference,
    )
    {
        $this->categoryChoice = $categoryChoice;
        $this->objectReference = $objectReference;
        $this->tokenByProfile = $tokenByProfile;
    }


    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $currentProfile = $this->tokenByProfile->getCurrentUserProfile();

        $builder
            ->add('category', ChoiceType::class, [
                'choices' => $this->categoryChoice->getCategoryCollection(),
                'choice_value' => function(?ProductCategoryUid $type) {
                    return $type?->getValue();
                },
                'choice_label' => function(ProductCategoryUid $type) {
                    return $type?->getOptions();
                },

                'label' => false,
                'expanded' => false,
                'multiple' => false,
                'required' => true,
            ]);


        $section = [];


        if($currentProfile)
        {
            try
            {
                $section =
                    iterator_to_array($this->objectReference
                    ->profile($currentProfile)
                    ->findObject());

            }
            catch(DomainException $e)
            {
                /** Если токен авторизации не найден */
                $section = [];
            }
        }


        $builder
            ->add('name', ChoiceType::class, [
                'choices' => $section,
                'choice_value' => function(?WbObjectDTO $type) {
                    return $type?->getCategory();
                },
                'choice_label' => function(WbObjectDTO $type) {
                    return $type->getCategory();
                },
                'label' => false,
                'expanded' => false,
                'multiple' => false,
                'required' => true,
            ]);


        $builder->add(
            'parent',
            ChoiceType::class,
            ['disabled' => true, 'placeholder' => 'Выберите раздел для списка категорий'],
        );

        $formModifier = function(FormInterface $form, WbObjectDTO $name = null) use ($section) {

            if($name)
            {
                $parent = [];

                //$section->rewind();

                if($section)
                {
                    $parent = $section;

                    $parent = array_filter($parent, function($k) use ($name) {
                        return $k->getCategory() === $name->getCategory();
                    });
                }

                if(!empty($parent))
                {

                    //$parentChoice = array_column($parent, 'objectName', 'objectName');

                    $form
                        ->add('parent', ChoiceType::class, [
                            'choices' => $parent,
                            'choice_value' => function(?WbObjectDTO $type) {
                                return $type?->getName();
                            },
                            'choice_label' => function(WbObjectDTO $type) {
                                return $type->getName();
                            },
                            'label' => false,
                            'expanded' => false,
                            'multiple' => false,
                            'required' => true,
                            'placeholder' => 'Выберите категорию из списка...',
                        ]);
                }
            }
        };

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function(FormEvent $event) use ($formModifier) {


            if(null === $event->getData()->name)
            {
                return;
            }

            $formModifier($event->getForm(), $event->getData()->name);
        });

        $builder->get('name')
            ->addEventListener(
                FormEvents::POST_SUBMIT,
                function(FormEvent $event) use ($formModifier) {
                    $data = $event->getForm()
                        ->getData();
                    $formModifier(
                        $event->getForm()->getParent(),
                        $data,
                    );
                },
            );

        /* Сохранить ******************************************************/
        $builder->add
        (
            'preform',
            SubmitType::class,
            ['label_html' => true, 'attr' => ['class' => 'btn-primary']],
        );

    }


    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults
        (
            [
                'data_class' => PreformDTO::class,
                'method' => 'POST',
                'attr' => ['class' => 'w-100'],
            ],
        );
    }

}
