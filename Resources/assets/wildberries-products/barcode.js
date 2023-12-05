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

// initOfferCategory();
//
//
// function initOfferCategory() {

     category = document.getElementById('wb_barcode_form_category');


        changeObjectOffers(category);

         addProperty = document.getElementById('wb_barcode_form_addProperty');
        if (addProperty) {
            addProperty.addEventListener('click', addPropertyPrototype);
        }


         addCustom = document.getElementById('wb_barcode_form_addCustom');
        if (addCustom) {
            addCustom.addEventListener('click', addCustomPrototype);
        }



    //
    // let add_incoming_object_material_defect = document.getElementById('incoming_material_defect_form_addIncoming');
    //
    // if (add_incoming_object_material_defect) {
    //
    //     add_incoming_object_material_defect.addEventListener('click', addMaterialIncomingPrototype);
    //
    // } else {
    //     eventEmitter.addEventListener('complete', function () {
    //         let add_incoming_object_material_defect = document.getElementById('incoming_material_defect_form_addIncoming');
    //         if (add_incoming_object_material_defect) {
    //             add_incoming_object_material_defect.addEventListener('click', addMaterialIncomingPrototype);
    //         }
    //     });
    // }
//}

$blockCollection = document.getElementById('collectionProperty');

/* Удаляем при клике колекцию СЕКЦИЙ */
$blockCollection.querySelector('.del-item-custom').addEventListener('click', function () {

    this.closest('.item-collection-custom').remove();
    index = addCustom.dataset.index * 1;
    addCustom.dataset.index = (index - 1).toString();
});


/* Удаляем при клике колекцию */
$blockCollection.querySelector('.del-item-property').addEventListener('click', function () {

    this.closest('.item-collection-property').remove();
    index = addProperty.dataset.index * 1;
    addProperty.dataset.index = (index - 1).toString();

});




function addCustomPrototype() {
    let $blockCollection = document.getElementById('collectionProperty');

    /* получаем прототип коллекции  */
    let $addButtonCustom = this;

    let newForm = $addButtonCustom.dataset.prototype;
    let index = $addButtonCustom.dataset.index * 1;

    /* Замена '__name__' в HTML-коде прототипа на
    вместо этого будет число, основанное на том, сколько коллекций */
    newForm = newForm.replace(/__custom__/g, index);
    //newForm = newForm.replace(/__FIELD__/g, index);

    /* Вставляем новую коллекцию */
    let div = document.createElement('div');
    div.classList.add('item-collection-custom')
    div.classList.add('w-100')
    div.innerHTML = newForm;
    $blockCollection.append(div);

    /* Удаляем при клике колекцию СЕКЦИЙ */
    div.querySelector('.del-item-custom').addEventListener('click', function () {

        this.closest('.item-collection-custom').remove();
        index = $addButtonCustom.dataset.index * 1;
        $addButtonCustom.dataset.index = (index - 1).toString();
    });


    div.querySelector('#wb_barcode_form_custom_' + index + '_sort').value = (index + 1);

    /* Увеличиваем data-index на 1 после вставки новой коллекции */
    $addButtonCustom.dataset.index = (index + 1).toString();



}


function addPropertyPrototype() {


    let $blockCollection = document.getElementById('collectionProperty');

    /* получаем прототип коллекции  */
    let $addButtonProperty = this;

    let newForm = $addButtonProperty.dataset.prototype;
    let index = $addButtonProperty.dataset.index * 1;

    /* Замена '__name__' в HTML-коде прототипа на
    вместо этого будет число, основанное на том, сколько коллекций */
    newForm = newForm.replace(/__property__/g, index);
    //newForm = newForm.replace(/__FIELD__/g, index);

    /* Вставляем новую коллекцию */
    let div = document.createElement('div');
    div.classList.add('item-collection-property')
    div.classList.add('w-100')
    div.innerHTML = newForm;
    $blockCollection.append(div);


    //let incoming_object_material_defect = div.querySelector('#incoming_material_defect_form_material_' + index + '_material');
    //changeObjectMaterialIncoming(incoming_object_material_defect, index);


    /* Удаляем при клике колекцию */
    div.querySelector('.del-item-property').addEventListener('click', function () {

        this.closest('.item-collection-property').remove();
        index = $addButtonProperty.dataset.index * 1;
        $addButtonProperty.dataset.index = (index - 1).toString();

    });


    /*div.querySelector('#wb_barcode_form_property_'+index+'_offer').addEventListener('click', function () {

    }*/

    /* Получаем прототип списка свойств */

    //var arr = Array.prototype.slice.call( $prototypeOffer.children );
    //console.log($prototypeOffer.children);
    //$prototypeOffer.style.display = 'unset';

    div.querySelector('#wb_barcode_form_property_' + index + '_sort').value = (index + 1);

    let $offer = div.querySelector('#wb_barcode_form_property_' + index + '_offer');
    //Array.from($offer).forEach((option) => { $offer.removeChild(option); });

    /* Заполняем список новыми элеиентами */
    $prototypeOffer = document.getElementById('wb_barcode_form_offer_prototype');
    Array.from($prototypeOffer.children).forEach((option) => {
        $offer.appendChild(option.cloneNode(true));
    });

    /*    Array.prototype.slice.call( $prototypeOffer.children ).map((optionData) => {
            $offer.appendChild(optionData);
        });*/


    //console.log($offer.replaceChild($prototypeOffer.children, null));


    /* Увеличиваем data-index на 1 после вставки новой коллекции */
    $addButtonProperty.dataset.index = (index + 1).toString();


    div.querySelectorAll('[data-select="select2"]').forEach(function (item) {
        new NiceSelect(item, {searchable: true});
    });

    /* применяем select2 */
    //new NiceSelect(div.querySelector('[data-select="select2"]'), { searchable: true });

}


/** Обновляем список торговых предложений в SELECT */
function changeObjectOffers(category, index = 0) {

    category.addEventListener('change', function () {


        let replaceId = 'wb_barcode_form_offer_prototype';

        let replaceElement = document.getElementById(replaceId + '_select2');
        if (replaceElement) {
            replaceElement.classList.add('disabled');
        }

        /* Создаём объект класса XMLHttpRequest */
        const requestModalName = new XMLHttpRequest();
        requestModalName.responseType = "document";

        /* Имя формы */
        let incomingForm = document.forms.wb_barcode_form;
        let formData = new FormData();

        let categoryName = document.getElementById('wb_barcode_form_category');
        formData.append(categoryName.getAttribute('name'), this.value);

        requestModalName.open(incomingForm.getAttribute('method'), incomingForm.getAttribute('action'), true);

        /* Получаем ответ от сервера на запрос*/
        requestModalName.addEventListener("readystatechange", function () {
            /* request.readyState - возвращает текущее состояние объекта XHR(XMLHttpRequest) */
            if (requestModalName.readyState === 4 && requestModalName.status === 200) {

                let result = requestModalName.response.getElementById('wb_barcode_form_offer_prototype');

                /* Замена прототипа коллекции */
                document.getElementById(replaceId).replaceWith(result);

                /* Активируем кнопку Добавить колелкцию */
                let addProperty = document.getElementById('wb_barcode_form_addProperty');
                addProperty.dataset.index = '0';
                addProperty.disabled = result.disabled;

                /* Сбрасываем список ранее заполненных коллекция */
                document.getElementById('collectionProperty').innerHTML = '';

            }

            return false;
        });

        requestModalName.send(formData);

    })
}