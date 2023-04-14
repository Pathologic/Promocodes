# Promocodes
Промокоды для Evolution CMS Commerce. Возможности:
* генерация промокодов по шаблону, в том числе массовая;
* скидка по промокоду в процентах или фиксированная;
* возможность ограничить время действия промокода, а также количество использований;
* возможность задать минимальную стоимость товаров для применения скидки;
* возможность ограничить применение промокода категориями товаров и отдельными товарами;
* события для вмешательства в работу компонента.

Спасибо за поддержку <a href="https://github.com/sashabeep">@sashabeep<a>, <a href="https://github.com/webtechmasterru">@webtechmasterru</a>, <a href="https://github.com/raven2323">@raven2323</a>, <a href="https://github.com/mikhaelw">@mikhaelw</a>.

## Установка
Установить, запустить модуль. Перед этим обязательно должен быть установлен Commerce. На страницах с формой для ввода промокода необходимо подключить скрипт assets/modules/promocodes/js/promocodes.js.

## Настройка
В параметрах плагина Promocodes можно изменить:
* шаблон для генерации промокодов по умолчанию;
* шаблон для вывода скидки по промокоду в корзине;
* шаблоны категорий товаров, через запятую;
* шаблоны товаров, через запятую.

## Форма для ввода промокодов
Можно использовать для вывода формы сниппет Promocodes c параметрами:
* instance - тип корзины, по умолчанию - products;
* tpl - шаблон формы в формате DocLister, если не указывать, то будет выведена форма по умолчанию;
* templatesPath, templatesExtension - см. DocLister;
* class - имя класса, который задается контейнеру при зарегистрированном промокоде.

В шаблоне доступны плейсхолдеры:
* [+instance+] - тип корзины;
* [+promocode+] - зарегистрированный промокод;
* [+class+] - имя класса, который задается при зарегистрированном промокоде;
* [+instance+] - тип корзины;

При использовании скрипта promocodes.js контейнер для ввода промокода должен иметь аттрибут data-promocodes, поле для ввода - аттрибут data-promocodes-instance="тип корзины", кнопки для действий -  data-promocodes-action="register", data-promocodes-action="remove".

## Серверные события

### OnBeforePromocodeSave
Вызывается перед сохранением промокода в БД. Для отмены сохранения необходимо вернуть из плагина сообщение.

Параметры:
<table width="100%">
<tr><th>Имя параметра</th><th>Передается по ссылке</th><th>Описание</th></tr>
<tr><td>mode</td><td>Нет</td><td>new при создании, upd при редактировании</td></tr>
<tr><td>promocode</td><td>Да</td><td>объект MODxAPI</td></tr>
</table>

### OnPromocodeSave
Вызывается после сохранения промокода.

Параметры:
<table width="100%">
<tr><th>Имя параметра</th><th>Передается по ссылке</th><th>Описание</th></tr>
<tr><td>mode</td><td>Нет</td><td>new при создании, upd при редактировании</td></tr>
<tr><td>promocode</td><td>Да</td><td>объект MODxAPI</td></tr>
</table>

### OnBeforePromocodeApply
Вызывается перед применением промокода. Позволяет самостоятельно вычислить стоимость корзины для применения промокода, а также отменить его применение. Можно возвращать сообщение.

Параметры:
<table width="100%">
<tr><th>Имя параметра</th><th>Передается по ссылке</th><th>Описание</th></tr>
<tr><td>instance</td><td>Нет</td><td>Тип корзины</td></tr>
<tr><td>prevent</td><td>Да</td><td>Флаг отмены действия. Если переключить в <code>true</code>, промокод не будет применен.</td></tr>
<tr><td>promocode</td><td>Да</td><td><p>Объект MODxAPI</td></tr>
<tr><td>cart</td><td>Да</td><td><p>Объект корзины</td></tr>
<tr><td>total</td><td>Да</td><td><p>Стоимость корзины для вычисления скидки по промокоду. Если не менять, то стоимость вычисляется или как сумма стоимости всех товаров, или как сумма стоимости товаров из привязанных к промокоду категорий и товаров, привязанных к промокоду</td></tr>
</table>

### OnPromocodeApply
Вызывается после применения промокода. Позволяет изменить вывод скидки по промокоду в корзине, а также сумму скидки. Можно возвращать сообщение.

Параметры:
<table width="100%">
<tr><th>Имя параметра</th><th>Передается по ссылке</th><th>Описание</th></tr>
<tr><td>instance</td><td>Нет</td><td>Тип корзины</td></tr>
<tr><td>promocode</td><td>Да</td><td><p>Объект MODxAPI</td></tr>
<tr><td>cart</td><td>Да</td><td><p>Объект корзины</td></tr>
<tr><td>total</td><td>Нет</td><td><p>Стоимость корзины для вычисления скидки по промокоду</td></tr>
<tr><td>discount_price</td><td>Да</td><td><p>Сумма скидки</td></tr>
<tr><td>discount_title</td><td>Да</td><td><p>Название скидки в корзине</td></tr>
</table>

### OnBeforePromocodeRegister
Вызывается перед сохранением в сессии введенного в форме промокода. Сохраненный промокод при возможности применяется. Можно отменить сохранение. Можно возвращать сообщение.

Параметры:
<table width="100%">
<tr><th>Имя параметра</th><th>Передается по ссылке</th><th>Описание</th></tr>
<tr><td>instance</td><td>Нет</td><td>Тип корзины</td></tr>
<tr><td>prevent</td><td>Да</td><td>Флаг отмены действия. Если переключить в <code>true</code>, промокод не будет зарегистрирован.</td></tr>
<tr><td>promocode</td><td>Да</td><td><p>Объект MODxAPI</td></tr>
</table>

### OnPromocodeRegister
Вызывается после сохранения промокода в сессию. Можно возвращать сообщение.

Параметры:
<table width="100%">
<tr><th>Имя параметра</th><th>Передается по ссылке</th><th>Описание</th></tr>
<tr><td>instance</td><td>Нет</td><td>Тип корзины</td></tr>
<tr><td>promocode</td><td>Да</td><td><p>Объект MODxAPI</td></tr>
</table>

### OnBeforePromocodeDelete
Вызывается перед удалением промокода из БД. Для отмены удаления необходимо вернуть сообщение.

Параметры:
<table width="100%">
<tr><th>Имя параметра</th><th>Передается по ссылке</th><th>Описание</th></tr>
<tr><td>ids</td><td>Да</td><td>ID записей для удаления</td></tr>
</table>

### OnPromocodeDelete
Вызывается после удаления промокода из БД.

Параметры:
<table width="100%">
<tr><th>Имя параметра</th><th>Передается по ссылке</th><th>Описание</th></tr>
<tr><td>ids</td><td>Нет</td><td>ID записей для удаления</td></tr>
</table>

### OnBeforePromocodeLinksSearch
Вызывается перед получением списка документов для создания связи с промокодом. Можно изменять значения параметров для вызова DocLister.

Параметры:
<table width="100%">
<tr><th>Имя параметра</th><th>Передается по ссылке</th><th>Описание</th></tr>
<tr><td>type</td><td>Нет</td><td>0 для категорий, 1 для товаров</td></tr>
<tr><td>search</td><td>Нет</td><td>Запрос для поиска</td></tr>
<tr><td>config</td><td>Да</td><td>Массив параметров для DocLister</td></tr>
</table>

### OnPromocodeLinksSearch
Вызывается после получения списка документов для создания связи с промокодом. Можно изменять список.

Параметры:
<table width="100%">
<tr><th>Имя параметра</th><th>Передается по ссылке</th><th>Описание</th></tr>
<tr><td>type</td><td>Нет</td><td>0 для категорий, 1 для товаров</td></tr>
<tr><td>docs</td><td>Да</td><td>Список документов</td></tr>
</table>

### OnBeforePromocodeLinksLoad
Вызывается перед получением списка связей промокода. Можно изменять значения параметров для вызова DocLister.

Параметры:
<table width="100%">
<tr><th>Имя параметра</th><th>Передается по ссылке</th><th>Описание</th></tr>
<tr><td>type</td><td>Нет</td><td>0 для категорий, 1 для товаров</td></tr>
<tr><td>pcid</td><td>Нет</td><td>ID промокода</td></tr>
<tr><td>config</td><td>Да</td><td>Массив параметров для DocLister</td></tr>
</table>

### OnPromocodeLinksLoad
Вызывается после получения списка связей промокода. Можно изменять список.

Параметры:
<table width="100%">
<tr><th>Имя параметра</th><th>Передается по ссылке</th><th>Описание</th></tr>
<tr><td>type</td><td>Нет</td><td>0 для категорий, 1 для товаров</td></tr>
<tr><td>pcid</td><td>Нет</td><td>ID промокода</td></tr>
<tr><td>docs</td><td>Да</td><td>Список связей</td></tr>
</table>

## Клиентские события



### promocode-register.promocodes
Вызывается перед регистрацией промокода.

### promocode-register-complete.promocodes
Вызывается после регистрации промокода.

### promocode-remove.promocodes
Вызывается перед отменой регистрации промокода.

### promocode-remove-complete.promocodes
Вызывается после отмены регистрации промокода.

```
document.addEventListener('promocode-register-complete.promocodes', function(e, params){
    alert('Промокод добавлен');
});
```

## Работа с промокодами

### Регистрация промокода

```
$result = ci()->promocodes->register('PROMOCODE', 'mycart'); //регистрация промокода PROMOCODE для корзины mycart (для products можно не указывать), возвращает true/false
$messages = ci()->promocodes->getMessages();

$promocode = ci()->promocodes->get('mycart'); //получение зарегистрированного промокода для корзины mycart (для products можно не указывать) или false

```

На фронте:
```
Promocodes.register('PROMOCODE', 'mycart');
```

### Применение промокода

```
$result = ci()->promocodes->apply('mycart'); //применение ранее зарегистрированного промокода для корзины mycart (для products можно не указывать), возвращает false или массив для добавления скидки в корзину
$messages = ci()->promocodes->getMessages();
```

### Отмена регистрации промокода

```
ci()->promocodes->remove('mycart'); //отмена ранее зарегистрированного промокода для корзины mycart (для products можно не указывать)
ci()->promocodes->removeAll(); //отмена всех зарегистрированных промокодов
```

На фронте:
```
Promocodes.remove('mycart');
```

### Работа с БД

На основе MODxAPI.

Получение промокода по названию:

```
use Pathologic\Commerce\Promocodes\Model;

$model = new Model($modx);
$result = $model->load('PROMOCODE', true); //получение PROMOCODE, если true, то выполняется проверка, что промокод действующий

```

Создание промокода:

```
use Pathologic\Commerce\Promocodes\Model;
use Pathologic\Commerce\Promocodes\UniquePromocodeException;

$model = new Model($modx);
try {
    $result = $model->create([
        'promocode' => 'PROMOCODE',
        'description' => 'Пример промокода',
        'min_amount' => 1000,
        'discount' => 50,
        'discount_type' => 1, //0 - процент, 1 - сумма,
        'begin' => '2023-05-01 10:00:00',
        'end' => '2023-05-10 10:00:00',
        'limit' => 5,
        'active' => 1
    ])->save(true); //с вызовом событий
    if ($result) {
        //после сохранения можно добавить связи с категориями и товарами
        $model->addCategoriesLinks([1,2,3]); //добавили связь с категориями
        $model->addProductsLinks([5,6,7]); //добавили связь с товарами
    }
} catch (UniquePromocodeException $e) {
    //Промокод не уникальный
}

```

Массовая генерация:
```
use Pathologic\Commerce\Promocodes\Model;

$model = new Model($modx);
$generated = $model->generate('[A-Z]{10}', [
    'description' => 'Пример промокода',
    'min_amount' => 1000,
    'discount' => 50,
    'discount_type' => 1, //0 - процент, 1 - сумма,
    'begin' => '2023-05-01 10:00:00',
    'end' => '2023-05-10 10:00:00',
    'limit' => 5,
    'active' => 1,
    'categories' => [1,2,3],
    'products' => [5,6,7]
], 100, true); //макс. 100 уникальных промокодов, с вызовом событий

```
