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

namespace BaksDev\Wildberries\Products\UseCase\Barcode\NewEdit\Property;


use BaksDev\Products\Category\Type\Section\Field\Id\ProductCategorySectionFieldUid;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ButtonType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class WbBarcodePropertyForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('sort', IntegerType::class);
        $builder->add('name', TextType::class);
        
        $builder->add('offer', ChoiceType::class);
    
    
    
        $builder->addEventListener(
          FormEvents::PRE_SET_DATA,
          function (FormEvent $event) use ($options)
          {
              if(!empty($options['offer_fields']))
              {
                  $form = $event->getForm();

                  $form
                    ->add('offer', ChoiceType::class, [
                      'choices' => $options['offer_fields'],  // array_flip(Main::LANG),
                      'choice_value' => function (?ProductCategorySectionFieldUid $type)
                      {
                          return $type?->getValue();
                      },
                      'choice_label' => function (ProductCategorySectionFieldUid $type)
                      {
                          return $type->getAttr();
                      },
        
                      'label' => false,
                      'expanded' => false,
                      'multiple' => false
                    ]);
    
              }
          }
        );
        
        
        $builder->add
        (
          'DeleteProperty',
          ButtonType::class,
          [
            'label_html' => true,
            'attr' =>
              ['class' => 'btn btn-outline-danger border-0 del-item-property'],
          ]);
        
    }
    
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults
        (
          [
            'data_class' => WbBarcodePropertyDTO::class,
            'method' => 'POST',
            'attr' => ['class' => 'w-100'],
            'category' => null,
            'offer_fields' => null,
          ]);
    }
    
}
