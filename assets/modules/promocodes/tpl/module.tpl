<!DOCTYPE html>
<html>
<head>
    <title>Управление промокодами</title>
    <link rel="stylesheet" type="text/css" href="[+manager_url+]media/style/[+theme+]/style.css"/>
    <link rel="stylesheet" href="[+manager_url+]media/style/common/font-awesome/css/font-awesome.min.css"/>
    <link rel="stylesheet" href="[+site_url+]assets/js/easy-ui/themes/modx/easyui.css"/>
    <link rel="stylesheet" href="[+site_url+]assets/modules/promocodes/js/datepicker/air-datepicker.css"/>
    <link rel="stylesheet" href="[+site_url+]assets/modules/promocodes/js/slimselect/slimselect.css"/>
    <script type="text/javascript" src="[+manager_url+]media/script/jquery/jquery.min.js"></script>
    <script type="text/javascript" src="[+site_url+]assets/js/easy-ui/jquery.easyui.min.js"></script>
    <script type="text/javascript" src="[+site_url+]assets/js/easy-ui/locale/easyui-lang-ru.js"></script>
    <script type="text/javascript" src="[+site_url+]assets/modules/promocodes/js/randexp.min.js"></script>
    <script type="text/javascript" src="[+site_url+]assets/modules/promocodes/js/xlsx.full.min.js"></script>
    <script type="text/javascript" src="[+site_url+]assets/modules/promocodes/js/datepicker/air-datepicker.js"></script>
    <script type="text/javascript" src="[+site_url+]assets/modules/promocodes/js/slimselect/slimselect.min.js"></script>
    <script type="text/javascript" src="[+site_url+]assets/modules/promocodes/js/module.js"></script>
    <script>
        const connector = '[+connector+]';
        const defaultPattern = '[+pattern+]';
    </script>
    <style>
        body {
            overflow-y: scroll;
        }

        #grid, #categories_grid, #products_grid {
            width: 100%;
            min-height: 100px;
        }

        #editWnd {
            overflow: hidden;
            min-height: 418px;
        }

        .used {
            color: #1944cc;
        }

        .outdated {
            color: #990404;
        }

        .active {
            color: #0b7e0b;
        }

        .delete, .btn-red {
            color: red;
        }

        .btn-green {
            color: green;
        }

        .delete:hover {
            color: #990404;
        }

        #begin, #end, #searchBegin, #searchEnd {
            z-index: 100;
            pointer-events: auto;
        }

        .help-block {
            font-size: 0.8em;
            color: green;
        }

        .error .help-block {
            color: red;
        }

        .form-check-input {
            margin-top: 0.16rem;
        }

        .datagrid-row-selected {
            background: #d3f0ff;
        }

        .tabs-icon {
            margin-top: -6px;
        }

        .l-btn-focus {
            outline: none;
        }

        #searchBar {
            padding: 4px;
        }
        mark.ss-search-highlight {
            padding:0;
        }
    </style>
</head>
<body>
<h1 class="pagetitle">
  <span class="pagetitle-icon">
    <i class="fa fa-certificate"></i>
  </span>
    <span class="pagetitle-text">
    Управление промокодами
  </span>
</h1>
<div id="actions">
    <ul class="btn-group">
        <li><a class="btn btn-secondary" href="#" onclick="document.location.href='index.php?a=106';">Закрыть модуль</a>
        </li>
    </ul>
</div>
<div style="padding:20px;box-shadow: 0 0 0.3rem 0 rgba(0,0,0,.1);background:#fff;">
    <table id="grid"></table>
</div>
<script type="text/template" id="generateForm">
    <div class="form-row">
        <div class="form-group col-md-4" style="padding-left: 5px;" data-field="amount">
            <label for="amount">Максимальное количество</label>
            <input type="number" class="form-control" id="amount" name="amount" min="1" value="1">
        </div>
        <div class="form-group col-md-8" style="padding-left: 5px;" data-field="pattern">
            <label for="pattern">Паттерн</label>
            <div class="input-group input-group-sm">
                <div class="input-group-append w-100">
                    <span class="input-group-text"><i class="fa fa-hashtag"></i></span>
                    <input type="text" class="form-control" name="pattern" id="pattern" value="{%pattern%}">
                </div>
            </div>
        </div>
    </div>
</script>
<script type="text/template" id="editForm">
    <div>
        <form>
            <div id="editFormTabs" class="easyui-tabs">
                <div title="Свойства" style="padding:5px 15px;" data-options="iconCls:'fa fa-cog'">
                    <input type="hidden" name="id" value="{%id%}">
                    <input type="hidden" name="formid" value="promocode">
                    <div class="form-row" id="promocode-generation">
                        <div class="form-group col-md-6" style="padding-left:5px;" data-field="promocode">
                            <label for="promocode">Промокод</label>
                            <div class="input-group input-group-sm">
                                <div class="input-group-append w-100">
                                    <input type="text" class="form-control" name="promocode" id="promocode"
                                           value="{%promocode%}">
                                    <button class="btn btn-success" type="button" id="copyPromocode"><i
                                            class="fa fa-copy" title="Скопировать"></i></button>
                                </div>
                            </div>
                        </div>
                        <div class="form-group col-md-6" style="padding-left:5px;">
                            <label for="pattern">Паттерн</label>
                            <div class="input-group input-group-sm">
                                <div class="input-group-append w-100">
                                    <span class="input-group-text"><i class="fa fa-hashtag"></i></span>
                                    <input type="text" class="form-control" name="pattern" id="pattern"
                                           value="{%pattern%}">
                                    <button class="btn btn-success" type="button" id="generatePromocode"><i
                                            class="fa fa-magic" title="Сгенерировать"></i></button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="form-group" data-field="description">
                        <label for="description">Описание</label>
                        <textarea name="description" id="description" class="form-control"
                                  rows="2">{%description%}</textarea>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-4" style="padding-left: 5px;" data-field="min_amount">
                            <label for="min_amount">Мин. сумма корзины</label>
                            <input type="number" min="0" class="form-control" id="min_amount" name="min_amount"
                                   value="{%min_amount%}">
                        </div>
                        <div class="form-group col-md-4" style="padding-left: 5px;" data-field="discount">
                            <label for="discount">Скидка</label>
                            <input type="number" min="0" class="form-control" id="discount" name="discount"
                                   value="{%discount%}">
                        </div>
                        <div class="form-group col-md-4" style="padding-left: 5px;">
                            <label style="padding-left: 5px;">Тип скидки</label>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="discount_type" id="discount_type0"
                                       value="0" {%discount_type0_checked%}>
                                <label class="form-check-label" for="discount_type0">Процент</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="discount_type" id="discount_type1"
                                       value="1" {%discount_type1_checked%}>
                                <label class="form-check-label" for="discount_type1">Сумма</label>
                            </div>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-4" style="padding-left: 5px;">
                            <label for="begin">Начало действия</label>
                            <input type="text" class="form-control" id="begin" name="begin" readonly value="{%begin%}">
                        </div>
                        <div class="form-group col-md-4" style="padding-left: 5px;">
                            <label for="end">Завершение действия</label>
                            <input type="text" class="form-control" id="end" name="end" readonly value="{%end%}">
                        </div>
                        <div class="form-group col-md-4" style="padding-left: 5px;">
                            <label for="limit">Лимит</label>
                            <input type="number" min="0" class="form-control" id="limit" name="limit" value="{%limit%}">
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="1" name="active" id="active"
                                   {%active_checked%}>
                            <label class="form-check-label" for="active">
                                Включить
                            </label>
                        </div>
                    </div>
                </div>
                <div title="Категории" style="padding:5px 15px;" data-options="iconCls:'fa fa-link'">
                    <div class="alert alert-info mt-1 mb-2">Скидка по промокоду применяется к товарам из выбранных
                        категорий.
                    </div>
                    <div class="form-group">
                        <label for="description">Выберите категорию</label>
                        <div class="input-group input-group-sm">
                            <div class="input-group-append w-100">
                                <select class="form-control" id="category_select"><option data-placeholder="true"></option></select>
                                <button class="btn btn-success" type="button" id="addCategoryLink"><i class="fa fa-plus" title="Добавить"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div>
                        <table id="categories_grid"></table>
                    </div>
                </div>
                <div title="Товары" style="padding:5px 15px;" data-options="iconCls:'fa fa-link'">
                    <div class="alert alert-info mt-1 mb-2">Скидка по промокоду применяется к выбранным товарам.</div>
                    <div class="form-group">
                        <label for="product_select">Выберите товар</label>
                        <div class="input-group input-group-sm">
                            <div class="input-group-append w-100">
                                <select class="form-control" id="product_select"><option data-placeholder="true"></option></select>
                                <button class="btn btn-success" type="button" id="addProductLink"><i class="fa fa-plus" title="Добавить"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div>
                        <table id="products_grid"></table>
                    </div>
                </div>
            </div>
        </form>
    </div>
</script>
<div style="display: none; visibility: hidden;" id="toolbar">
    <div id="actionsBar">
        <a href="#" class="easyui-linkbutton" data-options="iconCls:'fa fa-file',plain:true"
           onclick="Module.create(); return false;">Новый промокод</a>
        <a href="#" class="easyui-linkbutton" data-options="iconCls:'fa fa-copy',plain:true"
           onclick="Module.generate(); return false;">Генерация промокодов</a>
        <a href="#" class="easyui-linkbutton" data-options="iconCls:'fa fa-download',plain:true"
           onclick="Export.init(); return false;">Экспорт</a>
        <a href="#" class="easyui-linkbutton" data-options="iconCls:'fa fa-trash delete',plain:true"
           onclick="Module.delete(); return false;">Удалить</a>
        <a href="#" class="easyui-linkbutton" data-options="iconCls:'fa fa-search',toggle:true,plain:true"
           onclick="Search.init(this); return false;">Поиск</a>
    </div>
    <div id="searchBar" style="display: none;">
        <form>
            <div class="form-row align-items-center mb-0">
                <div class="col-2" style="padding-left:5px;">
                    <input type="text" class="form-control form-control-sm" id="searchPromocode" name="promocode"
                           placeholder="Промокод">
                </div>
                <div class="col-2" style="padding-left:5px;">
                    <input type="text" readonly class="form-control form-control-sm" id="searchBegin" name="begin"
                           placeholder="Начало периода">
                </div>
                <div class="col-auto">-</div>
                <div class="col-2" style="padding-left:5px;">
                    <input type="text" readonly class="form-control form-control-sm" id="searchEnd" name="end"
                           placeholder="Конец периода">
                </div>
                <div class="col-auto">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" value="1" name="active" id="searchActive">
                        <label class="form-check-label" for="searchActive">
                            Активные
                        </label>
                    </div>
                </div>
                <div class="col-auto">
                    <a href="#" class="easyui-linkbutton" data-options="iconCls:'btn-green fa fa-check'"
                       onclick="Search.process(); return false;">Найти</a>
                    <a href="#" class="easyui-linkbutton" data-options="iconCls:'btn-red fa fa-ban'"
                       onclick="Search.reset(); return false;">Отмена</a>
                </div>
            </div>
        </form>
    </div>
</div>
<script>
    $.parser.onComplete = function () {
        $('#toolbar').css('visibility', 'visible');
    }
    Module.init();
</script>
</body>
</html>
