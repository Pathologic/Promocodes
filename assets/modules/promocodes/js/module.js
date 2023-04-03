const datePickerOptions = {
    timepicker: true,
    position: 'top center',
    dateFormat: 'yyyy-MM-dd',
    timeFormat: 'HH:mm:00',
    autoClose: true,
    todayButton: new Date(),
    buttons: [{
        content: 'Сегодня',
        className: 'custom-button-classname',
        onClick: (dp) => {
            let date = new Date();
            dp.selectDate(date);
            dp.setViewDate(date);
        }
    }, 'clear']
};
const sanitize = function (value) {
    if (typeof value === 'string') value = value
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');

    return value;
}
const parseTemplate = function (tpl, data) {
    for (let key in data) {
        let value = data[key];
        tpl = tpl.replace(new RegExp('\{%' + key + '%\}', 'g'), sanitize(value));
    }

    return tpl;
}
const Module = {
    init: function () {
        const module = this;
        $('#grid').datagrid({
            url: connector,
            title: "Управление промокодами",
            fitColumns: true,
            pagination: true,
            idField: 'id',
            singleSelect: true,
            striped: true,
            checkOnSelect: false,
            selectOnCheck: false,
            emptyMsg: 'Промокоды не созданы',
            pageList: [25, 50, 75, 100],
            pageSize: 25,
            rowStyler: function (index, row) {
                if (row.limit > 0 && row.usages >= row.limit) {
                    return {class: 'used'}
                }
                const now = Date.now();
                if (row.end !== null && new Date(row.end).getTime() < now) {
                    return {class: 'outdated'}
                }
                if (row.active === '1' && (row.limit === '0' || row.limit > row.usages) && (row.end === null || new Date(row.end).getTime() > now) && (row.begin === null || new Date(row.begin).getTime() < now)) {
                    return {class: 'active'}
                }
            },
            columns: [[
                {field: 'select', checkbox: true},
                {
                    field: 'promocode', title: 'Промокод', width: 140, sortable: true, sanitize,
                    formatter: function (value, row) {
                        return value + '<br><small>' + row.description + '</small>';
                    }
                },
                {
                    field: 'discount', title: 'Скидка', width: 80, fixed: true, align: 'center', sortable: true,
                    formatter: function (value, row) {
                        return value + (row.discount_type === '1' ? '' : '%') + (row.min_amount > 0 ? '<br><small>' + row.min_amount + '</small>' : '');
                    }
                },
                {
                    field: 'links',
                    width: 40,
                    fixed: true,
                    align: 'center',
                    sortable: false,
                    formatter: function (value, row, index) {
                        let out = '';
                        if(row.categories) {
                            out += '<i class="fa fa-folder-open-o" title="Применяется к выбранным категориям товаров"></i>'
                        }
                        if(row.products) {
                            out += '<i class="fa fa-gift" title="Применяется к выбранным товарам"></i>'
                        }
                        if(out === '') {
                            out = '<i class="fa fa-shopping-cart" title="Применяется ко всем товарам в корзине"></i>';
                        }

                        return out;
                    }
                },
                {
                    field: 'begin', title: 'Начало<br>действия', width: 110, fixed: true, align: 'center', sortable: true,
                    formatter: function(value){
                        if(value !== null) value = value.replace(' ', '<br><small>') + '</small>';
                        return value;
                    }
                },
                {
                    field: 'end',
                    title: 'Завершение<br>действия',
                    width: 110,
                    fixed: true,
                    align: 'center',
                    sortable: true,
                    formatter: function(value){
                        if(value !== null) value = value.replace(' ', '<br><small>') + '</small>';
                        return value;
                    }
                },
                {
                    field: 'createdon', title: 'Создан', width: 110, fixed: true, align: 'center', sortable: true,
                    formatter: function(value){
                        if(value !== null) value = value.replace(' ', '<br><small>') + '</small>';
                        return value;
                    }
                },
                {
                    field: 'limit', title: 'Лимит', width: 70, fixed: true, align: 'center', sortable: true,
                    formatter: function (value, row) {
                        return row.usages + '/' + (value === '0' ? '∞' : value);
                    }
                },
                {
                    field: 'active',
                    width: 30,
                    fixed: true,
                    align: 'center',
                    title: '<span class="fa fa-lg fa-power-off"></span>',
                    sortable: true,
                    formatter: function (value, row, index) {
                        return '<input type="checkbox" value="1"' + (value === '0' ? '' : ' checked') + ' onchange="Module.toggleActive(' + index + ')">';
                    }
                },
                {
                    field: 'action',
                    width: 40,
                    title: '',
                    align: 'center',
                    fixed: true,
                    formatter: function (value, row) {
                        return '<a class="action delete" href="javascript:void(0)" onclick="Module.delete(' + row.id + ')" title="Удалить"><i class="fa fa-trash fa-lg"></i></a>';
                    }
                }
            ]],
            toolbar: '#toolbar',
            onOpen: function () {
                let options = $.extend({}, datePickerOptions, {timepicker: false, position: 'bottom center'});
                new AirDatepicker('#searchBegin', options);
                new AirDatepicker('#searchEnd', options);
            },
            onDblClickRow: function (index, row) {
                module.edit(row.id);
            }
        });
    },
    toggleActive: function (index) {
        const grid = $('#grid');
        let row = grid.datagrid('getSelected');
        if (typeof row !== 'undefined') {
            const active = row.active === '1' ? '0' : '1';
            $.post(
                connector,
                {
                    mode: 'toggleActive',
                    id: row.id,
                    active: active
                },
                function (result) {
                    if (result.status) {
                        row.active = active;
                        console.log(row);
                        grid.datagrid('updateRow', {
                            index: index,
                            row: row
                        });
                        grid.datagrid('refreshRow', index);
                    }
                },
                'json'
            );
        }
    },
    delete: function (id) {
        let ids = [];
        const grid = $('#grid');
        if (typeof id === 'undefined') {
            const rows = grid.datagrid('getChecked');
            const options = grid.datagrid('options');
            const pkField = options.idField;
            if (rows.length) {
                $.each(rows, function (i, row) {
                    ids.push(row[pkField]);
                });
            }
            if (!ids.length) {
                const row = grid.datagrid('getSelected');
                if (row) ids.push(row[pkField]);
            }
        } else {
            ids.push(id);
        }
        if (ids.length) {
            $.messager.confirm('Удаление', 'Вы уверены?', function (r) {
                if (r) {
                    $.post(
                        connector,
                        {
                            mode: 'delete',
                            ids: ids
                        },
                        function (result) {
                            if (result.status) {
                                $('#grid').datagrid('reload');
                            } else {
                                $.messager.alert('Ошибка', 'Не удалось удалить', 'error')
                            }
                        },
                        'json'
                    ).fail(function () {
                        $.messager.alert('Ошибка', 'Произошла ошибка', 'error');
                    });
                }
            });
        }
    },
    create: function () {
        const tpl = $('#editForm').html();
        const module = this;
        const form = parseTemplate(tpl, {
            pattern: defaultPattern,
            id: 0,
            min_amount: 0,
            limit: 1,
            promocode: '',
            description: '',
            discount: '0',
            discount_type0_checked: 'checked',
            discount_type1_checked: '',
            begin: '',
            end: '',
            active_checked: 'checked',
        });
        $('<div id="editWnd">' + form + '</div>').dialog({
            modal: true,
            title: 'Новый промокод',
            collapsible: false,
            minimizable: false,
            maximizable: false,
            resizable: true,
            width: 630,
            buttons: [
                {
                    text: 'Сохранить',
                    iconCls: 'btn-green fa fa-check fa-lg',
                    handler: function () {
                        const wnd = $('#editWnd');
                        const form = $('form', wnd);
                        $('.error', form).removeClass('error');
                        $('div.help-block', form).remove();
                        $.post(connector + '?mode=create',
                            form.serialize(),
                            function (response) {
                                if (response.status) {
                                    $('#grid').datagrid('reload');
                                    wnd.dialog('close', true);
                                } else {
                                    if (typeof response.errors !== 'undefined' && Object.keys(response.errors).length > 0) {
                                        for (let field in response.errors) {
                                            let $field = $('[data-field="' + field + '"]', form).addClass('error');
                                            let errors = response.errors[field];
                                            for (let error in errors) {
                                                $field.append($('<div class="help-block">' + errors[error] + '</div>'));
                                            }
                                        }
                                        $('#editFormTabs').tabs('select', 0);
                                    }
                                    if (typeof response.messages !== 'undefined' && response.messages.length > 0) {
                                        $.messager.alert('Ошибка', response.messages.join('<br>'), 'error');
                                    }
                                }
                            }, 'json'
                        ).fail(function () {
                            $.messager.alert('Ошибка', 'Произошла ошибка', 'error');
                        });
                    }
                }, {
                    text: 'Закрыть',
                    iconCls: 'btn-red fa fa-ban fa-lg',
                    handler: function () {
                        $('#editWnd').dialog('close', true);
                    }
                }
            ],
            onOpen: function () {
                module.initPromocodeForm();
                new Links($('#categories_grid'), {
                    type: 'category',
                    mode: 'create'
                });
                new Links($('#products_grid'), {
                    type: 'product',
                    mode: 'create'
                });
                $(this).window('center');
            },
            onResize: function (width, height) {
                if (height === 'auto') return;
                $('#editFormTabs').tabs('resize', {height: height});
            },
            onClose: function () {
                module.destroyWindow($('#editWnd'));
            }
        })
    },
    generate: function () {
        const tpl = $('#editForm').html();
        const module = this;
        const form = parseTemplate(tpl, {
            pattern: defaultPattern,
            id: 0,
            min_amount: 0,
            limit: 1,
            promocode: '',
            description: '',
            discount: '0',
            discount_type0_checked: 'checked',
            discount_type1_checked: '',
            begin: '',
            end: '',
            active_checked: 'checked',
        });
        $('<div id="editWnd">' + form + '</div>').dialog({
            modal: true,
            title: 'Массовое создание промокодов',
            collapsible: false,
            minimizable: false,
            maximizable: false,
            resizable: true,
            width: 630,
            height: 450,
            buttons: [
                {
                    text: 'Сгенерировать',
                    iconCls: 'btn-green fa fa-check fa-lg',
                    handler: function () {
                        const wnd = $('#editWnd');
                        const form = $('form', wnd);
                        $('.error', form).removeClass('error');
                        $('div.help-block', form).remove();
                        $.post(connector + '?mode=generate',
                            form.serialize(),
                            function (response) {
                                if (response.status) {
                                    $('#grid').datagrid('reload');
                                    wnd.dialog('close', true);
                                    $.messager.alert('Завершено', response.messages.join('<br>'), 'info');
                                } else {
                                    if (typeof response.errors !== 'undefined' && Object.keys(response.errors).length > 0) {
                                        for (let field in response.errors) {
                                            let $field = $('[data-field="' + field + '"]', form).addClass('error');
                                            let errors = response.errors[field];
                                            for (let error in errors) {
                                                $field.append($('<div class="help-block">' + errors[error] + '</div>'));
                                            }
                                        }
                                        $('#editFormTabs').tabs('select', 0);
                                    }
                                    if (typeof response.messages !== 'undefined' && response.messages.length > 0) {
                                        $.messager.alert('Ошибка', response.messages.join('<br>'), 'error');
                                    }
                                }
                            }, 'json'
                        ).fail(function () {
                            $.messager.alert('Ошибка', 'Произошла ошибка', 'error');
                        });
                    }
                }, {
                    text: 'Закрыть',
                    iconCls: 'btn-red fa fa-ban fa-lg',
                    handler: function () {
                        $('#editWnd').dialog('close', true);
                    }
                }
            ],
            onOpen: function () {
                const form = $('form', $('#editWnd'));
                const tpl = $('#generateForm').html();
                $('#promocode-generation', form).replaceWith(parseTemplate(tpl, {
                    pattern: defaultPattern
                }));
                module.initPromocodeForm();
                new Links($('#categories_grid'), {
                    type: 'category',
                    mode: 'create'
                });
                new Links($('#products_grid'), {
                    type: 'product',
                    mode: 'create'
                });
                $(this).window('center');
            },
            onClose: function () {
                module.destroyWindow($('#editWnd'));
            }
        })
    },
    edit: function (id) {
        const module = this;
        $.post(
            connector,
            {
                mode: 'get',
                id: id
            }, function (response) {
                if (response.status) {
                    const tpl = $('#editForm').html();
                    const form = parseTemplate(tpl, {
                        pattern: defaultPattern,
                        id: response.fields.id,
                        min_amount: response.fields.min_amount,
                        limit: response.fields.limit,
                        promocode: response.fields.promocode,
                        description: response.fields.description,
                        discount: response.fields.discount,
                        discount_type0_checked: (response.fields.discount_type === '0' ? 'checked' : ''),
                        discount_type1_checked: (response.fields.discount_type === '1' ? 'checked' : ''),
                        begin: response.fields.begin || '',
                        end: response.fields.end || '',
                        active_checked: (response.fields.active === '1' ? 'checked' : '')
                    });
                    $('<div id="editWnd">' + form + '</div>').dialog({
                        modal: true,
                        title: 'Редактирование промокода ' + sanitize(response.fields.promocode),
                        collapsible: false,
                        minimizable: false,
                        maximizable: false,
                        resizable: true,
                        width: 630,
                        height: 450,
                        buttons: [
                            {
                                text: 'Сохранить',
                                iconCls: 'btn-green fa fa-check fa-lg',
                                handler: function () {
                                    const wnd = $('#editWnd');
                                    const form = $('form', wnd);
                                    $('.error', form).removeClass('error');
                                    $('div.help-block', form).remove();
                                    $.post(connector + '?mode=update',
                                        form.serialize(),
                                        function (response) {
                                            if (response.status) {
                                                $('#grid').datagrid('reload');
                                                wnd.dialog('close', true);
                                            } else {
                                                if (typeof response.errors !== 'undefined' && Object.keys(response.errors).length > 0) {
                                                    for (let field in response.errors) {
                                                        let $field = $('[data-field="' + field + '"]', form).addClass('error');
                                                        let errors = response.errors[field];
                                                        for (let error in errors) {
                                                            $field.append($('<div class="help-block">' + errors[error] + '</div>'));
                                                        }
                                                    }
                                                    $('#editFormTabs').tabs('select', 0);
                                                }
                                                if (typeof response.messages !== 'undefined' && response.messages.length > 0) {
                                                    $.messager.alert('Ошибка', response.messages.join('<br>'), 'error');
                                                }
                                            }
                                        }, 'json'
                                    ).fail(function () {
                                        $.messager.alert('Ошибка', 'Произошла ошибка', 'error');
                                    });
                                }
                            }, {
                                text: 'Закрыть',
                                iconCls: 'btn-red fa fa-ban fa-lg',
                                handler: function () {
                                    $('#editWnd').dialog('close', true);
                                }
                            }
                        ],
                        onOpen: function () {
                            module.initPromocodeForm();
                            new Links($('#categories_grid'), {
                                type: 'category',
                                mode: 'edit'
                            }, id);
                            new Links($('#products_grid'), {
                                type: 'product',
                                mode: 'edit'
                            }, id);
                            $(this).window('center');
                        },
                        onClose: function () {
                            module.destroyWindow($('#editWnd'));
                        }
                    })
                } else {
                    $.messager.alert('Ошибка', 'Произошла ошибка', 'error');
                }
            },
            'json'
        ).fail(function () {
            $.messager.alert('Ошибка', 'Произошла ошибка', 'error');
        });
    },
    destroyWindow: function (wnd) {
        const mask = $('.window-mask');
        wnd.window('destroy', true);
        $('.window-shadow,.window-mask').remove();
        $('body').css('overflow', 'auto').append(mask);
    },
    initPromocodeForm: function () {
        $('#editFormTabs').tabs({height: 415});
        $('#generatePromocode').click(function () {
            $('#promocode').val(new RandExp($('#pattern').val()).gen());
        });
        $('#copyPromocode').click(function () {
            let value = $('#promocode').val();
            if (value === '') return;
            const field = $('[data-field="promocode"]').removeClass('error');
            $('.help-block', field).remove();
            let copyMessageTimeout = undefined;
            navigator.clipboard.writeText(value).then(function () {
                clearTimeout(copyMessageTimeout);
                field.append('<div class="help-block text-right">Промокод скопирован</div>');
                copyMessageTimeout = setTimeout(function () {
                    $('.help-block', field).remove();
                }, 2000);
            }, function (err) {
            });
        });
        new AirDatepicker('#begin', datePickerOptions);
        new AirDatepicker('#end', datePickerOptions);
    }
}
const Search = {
    init: function (btn) {
        if (btn.classList.contains('l-btn-selected')) {
            $('#searchBar').hide();
        } else {
            $('#searchBar').show();
        }
    },
    process: function () {
        const form = $('form', '#searchBar');
        $('#grid').datagrid('load', {
            promocode: $('#searchPromocode', form).val(),
            begin: $('#searchBegin', form).val(),
            end: $('#searchEnd', form).val(),
            active: $('#searchActive:checked').val()
        });
    },
    reset: function () {
        $('form', '#searchBar').get(0).reset();
        this.process();
    }
}
const Export = {
    processing: false,
    data: [],
    init: function () {
        let that = this;
        const form = $('form', '#searchBar');
        $.post(
            connector + '?mode=export/start',
            {
                promocode: $('#searchPromocode', form).val(),
                begin: $('#searchBegin', form).val(),
                end: $('#searchEnd', form).val(),
                active: $('#searchActive:checked').val()
            },
            function (response) {
                if (response.status) {
                    that.processing = true;
                    that.data = [];
                    $('<div id="exportDlg"><div class="dialogContent" style="padding:15px;height:52px;">' +
                        '<div id="exportProgress" style="display:none;"></div>' +
                        '</div>').dialog({
                        title: 'Экспорт промокодов',
                        width: 400,
                        modal: true,
                        onOpen: function () {
                            $('#exportProgress').show().progressbar({value: 0});
                        },
                        onClose: function () {
                            $("#exportDlg").remove();
                            that.exportProcess = false;
                            that.data = [];
                        }
                    });
                    that.process();
                } else {
                    that.handleError();
                }
            }, 'json'
        ).fail(that.handleError);
    },
    process: function () {
        var that = this;
        if (!this.processing) return;
        const form = $('form', '#searchBar');
        $.post(
            connector + '?mode=export/process',
            {
                promocode: $('#searchPromocode', form).val(),
                begin: $('#searchBegin', form).val(),
                end: $('#searchEnd', form).val(),
                active: $('#searchActive:checked').val()
            },
            function (response) {
                if (response.status) {
                    if (response.data.length > 0) {
                        that.data = that.data.concat(response.data);
                    }
                    if (!response.complete) {
                        $('#exportProgress').progressbar('setValue', Math.floor(response.processed / response.total * 100));
                        that.process();
                    } else {
                        $('#exportProgress').progressbar('setValue', 100);
                        let message = 'Экспортировано ' + response.processed + ' записей<br><br>';
                        let filename = "export.xlsx";
                        let ws_name = "Export";
                        let wb = XLSX.utils.book_new(), ws = XLSX.utils.aoa_to_sheet(that.data);
                        XLSX.utils.book_append_sheet(wb, ws, ws_name);
                        XLSX.writeFile(wb, filename);
                        $.messager.alert('Экспорт завершен', message, 'info', function () {
                            $('#exportDlg').dialog('close');
                        })
                    }
                } else {
                    that.handleError();
                }
            }, 'json'
        ).fail(that.handleError);
    },
    handleError: function () {
        $.messager.alert('Ошибка', 'Произошла ошибка', 'error', function () {
            $('#exportDlg').dialog('close');
        })
    }
}
const Links = function (grid, options, pcid = 0) {
    const defaults = {
        type: 'category',
        mode: 'create',
    };
    this.grid = $(grid);
    this.pcid = pcid;
    this._options = $.extend({}, defaults, options);

    return this.init();
}
Links.prototype = {
    init: function () {
        const that = this;
        this.selector = new SlimSelect({
            select: this._options.type === 'category' ? '#category_select' : '#product_select',
            settings: {
                allowDeselect: true,
                placeholderText: 'Выберите ' + (this._options.type === 'category' ? 'категорию' : 'товар'),
                searchText: 'Ничего не найдено',
                searchPlaceholder: 'Поиск',
                searchingText: 'Поиск...',
                searchHighlight: true
            },
            events: {
                search: (search, currentData) => {
                    return new Promise((resolve, reject) => {
                        if (search.length > 0 && search.length < 3) {
                            return;
                        }
                        $.post(connector,
                            {
                                mode: 'links/search',
                                type: that._options.type === 'category' ? 0 : 1,
                                q: search
                            }, function (response) {
                                const options = [{
                                    value: '',
                                    text: '',
                                    display: false,
                                    data: {placeholder: true}
                                }].concat(response
                                    .map((item) => {
                                        return {
                                            value: item.id,
                                            text: item.pagetitle,
                                        }
                                    })
                            );
                                resolve(options);
                            }, 'json')
                    })
                }
            }
        })
        $.post(connector,
            {
                mode: 'links/search',
                type: that._options.type === 'category' ? 0 : 1,
            }, function (response) {
                const options = [{value: '', text: '', display: false, data: {placeholder: true}}].concat(response
                    .map((item) => {
                        return {
                            value: item.id,
                            text: item.pagetitle,
                        }
                    })
                );
                that.selector.setData(options);
            }, 'json'
        )
        if (this._options.type === 'category') {
            $('#addCategoryLink').click(function (e) {
                e.preventDefault();
                const selected = that.selector.getSelected()[0];
                if (selected === '') return;
                const row = that.selector.getData().filter((item) => {
                    return item.value === selected;
                });
                if (row.length) {
                    that.add({
                        link: selected,
                        pagetitle: row[0].text
                    });
                }
            });
        } else {
            $('#addProductLink').click(function (e) {
                e.preventDefault();
                const selected = that.selector.getSelected()[0];
                if (selected === '') return;
                const row = that.selector.getData().filter((item) => {
                    return item.value === selected;
                });
                if (row.length) {
                    that.add({
                        link: selected,
                        pagetitle: row[0].text
                    });
                }
            });
        }
        const columns = [[
            {
                field: 'link', width: 10, title: 'ID', formatter: function (value) {
                    if (that._options.mode === 'create') {
                        value += '<input type="hidden" name="' + (that._options.type === 'category' ? 'categories[]' : 'products[]') + '" value="' + value + '">';
                    }

                    return value;
                }
            },
            {
                field: 'pagetitle',
                title: this._options.type === 'category' ? 'Название категории' : 'Название товара',
                width: 140,
                sortable: true,
                formatter: sanitize
            },
            {
                field: 'action',
                width: 40,
                title: '',
                align: 'center',
                fixed: true,
                formatter: function (value, row, index) {
                    return '<a class="action delete" href="#" data-id="' + row.link + '" title="Удалить"><i class="fa fa-trash fa-lg"></i></a>';
                }
            }
        ]];
        const options = {
            title: this._options.type === 'category' ? "Связи с категориями" : "Связи с товарами",
            fitColumns: true,
            idField: 'link',
            singleSelect: true,
            striped: true,
            checkOnSelect: false,
            selectOnCheck: false,
            emptyMsg: 'Связи не созданы',
            columns: columns,
            onOpen: function () {
                $(that.grid.datagrid('getPanel')).on('click', '.delete', function (e) {
                    e.preventDefault();
                    let id = $(this).data('id');
                    if (that._options.mode === 'create') {
                        let index = that.grid.datagrid('getRowIndex', id);
                        that.grid.datagrid('deleteRow', index);
                    } else {
                        that.delete(id);
                    }
                });
            }
        };
        if (this._options.mode === 'edit') {
            this.grid.datagrid($.extend({}, options, {
                url: connector,
                queryParams: {
                    mode: 'links/list',
                    type: this._options.type === 'category' ? 0 : 1,
                    pcid: this.pcid
                },
            }));
        } else {
            this.grid.datagrid(options);
        }
    },
    add: function (row) {
        const that = this;
        if (this._options.mode === 'create') {
            this.grid.datagrid('insertRow', {
                index: 0,
                row: row
            });
            this.grid.datagrid('resize');
        } else {
            $.post(
                connector,
                {
                    mode: 'links/add',
                    type: this._options.type === 'category' ? 0 : 1,
                    pcid: that.pcid,
                    link: row.link
                },
                function (result) {
                    if (result.status) {
                        that.grid.datagrid('reload');
                    } else {
                        $.messager.alert('Ошибка', 'Произошла ошибка', 'error');
                    }
                },
                'json'
            ).fail(function () {
                $.messager.alert('Ошибка', 'Произошла ошибка', 'error');
            });
        }
    },
    delete: function (link) {
        const pcid = this.pcid;
        let that = this;
        $.post(
            connector,
            {
                mode: 'links/delete',
                link: link,
                pcid: pcid
            },
            function (result) {
                if (result.status) {
                    that.grid.datagrid('reload');
                } else {
                    $.messager.alert('Ошибка', 'Не удалось удалить', 'error')
                }
            },
            'json'
        );
    }
}
