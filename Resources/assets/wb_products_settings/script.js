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


let object_name = document.getElementById('preform_form_name');


object_name.addEventListener('change', function () {
    let replaceId = 'preform_form_parent';

    let replaceElement = document.getElementById(replaceId + '_select2');
    replaceElement.classList.add('disabled');

    /* Создаём объект класса XMLHttpRequest */
    const requestModalName = new XMLHttpRequest();
    requestModalName.responseType = "document";

    let preformForm = document.forms.preform_form;
    let formData = new FormData();
    formData.append(this.getAttribute('name'), this.value);

    requestModalName.open(preformForm.getAttribute('method'), preformForm.getAttribute('action'), true);

    /* Указываем заголовки для сервера */
    requestModalName.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

    /* Получаем ответ от сервера на запрос*/
    requestModalName.addEventListener("readystatechange", function () {
        /* request.readyState - возвращает текущее состояние объекта XHR(XMLHttpRequest) */
        if (requestModalName.readyState === 4 && requestModalName.status === 200) {

            let result = requestModalName.response.getElementById(replaceId);

            /* Удаляем предыдущий Select2 */
            let select2 = document.getElementById(replaceId + '_select2');
            if (select2) {
                select2.remove();
            }

            document.getElementById(replaceId).replaceWith(result);

            new NiceSelect(document.getElementById(replaceId), {searchable: true, id: 'select2-' + replaceId});
        }

        return false;
    });

    requestModalName.send(formData);

});
