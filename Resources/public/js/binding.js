/**
 * text: value: $(element).text(value)
 * html: value: $(element).html(value)
 * attr: {name:value}: $(element).attr(name, value)
 * val: value: $(element).val(value)
 * addClass: value: $(element).addClass(value);
 * removeClass: value: $(element).removeClass(value);
 * remove: : $(element).remove();
 * modal: value: $(element).modal(value);
 * popover: value: $(element).popover(value);
 * append: value: $(element).append($(value));
 * id: value - parameter of appendOnce
 * appendOnce: value, id - append dialog
 * appendModal:  - append modal dialog
 * reload: : location.reload();
 * multiple: [{bindName: bindParams}]
 * jquery: value: $(element)[value]()
 */
// region render binding

var bindings = {
    /**
     * Заглушка для передачи доп параметра в биндинг
     * @param element
     * @param value
     * @param allBind
     */
    id: function (element, value, allBind) {
    },
    /**
     * Заглушка для передачи доп параметра в биндинг
     * @param element
     * @param value
     * @param allBind
     */
    options: function (element, value, allBind) {
    },
    /**
     * text() из jquery
     * @param element
     * @param value
     * @param allBind
     */
    text: function (element, value, allBind) {
        $(element).text(value);
    },
    hide: function (element, value, allBind) {
        $(element).hide();
    },
    show: function (element, value, allBind) {
        $(element).show();
    },
    html: function (element, value, allBind) {
        $(element).html(value);
    },
    attr: function (element, value, allBind) {
        for (var name in value) {
            $(element).attr(name, value[name]);
        }
    },
    val: function (element, value, allBind) {
        $(element).val(value);
    },
    addClass: function (element, value, allBind) {
        $(element).addClass(value);
    },
    removeClass: function (element, value, allBind) {
        $(element).removeClass(value);
    },
    remove: function (element, value, allBind) {
        $(element).remove();
    },
    modal: function (element, value, allBind) {
        $(element).modal(value);
    },
    popover: function (element, value, allBind) {
        $(element).popover(value);
    },
    append: function (element, value, allBind) {
        $(element).append($(value));
    },
    /**
     * Добавляет html в элемент 1 раз.
     * Доп параметры
     * options.id = id контэйнера для проверки уникальности
     * options.template = шаблон контэйнера, по умолчанию span
     *
     * @param element
     * @param value
     * @param allBind
     */
    appendOnce: function (element, value, allBind) {
        var o = {
            id: allBind.id,
            template: '<span></span>'
        };
        if (allBind.options) {
            o = $.extend(o, allBind.options);
        }
        var container = $(element).find('[data-render-id=' + o.id + ']');
        if (container.length == 0) {
            container = $(o.template).attr('data-render-id', o.id).appendTo($(element));
        }
        container.html(value);
    },
    appendModal: function (element, value, allBind) {
        var ids = [];
        var id = null;
        $(value).each(function () {
            if ($(this).hasClass('modal')) {
                if (id == null) {
                    id = $(this).attr('id');
                }
                ids.push($(this).attr('id'));
            }
            return true;
        });
        $('#' + ids.join(',#')).modal('hide').remove();
        $(value).appendTo(element);
        $('#' + id).modal('show');
        $.each(ids, function (i, e) {
            ko.applyBindings(viewModel, document.getElementById(e));
        });
    },
    reload: function () {
        location.reload();
    },
    redirect: function (element, value) {
        location.href = value;
    },
    multiple: function (element, value, allBind) {
        var bind = bindings[value.name];
        $.each(value.values, function (i, e) {
            bind(element, e, allBind);
        });
    },
    jquery: function (element, value, allBind) {
        if (typeof(value) == "object") {
            $.each(value, function (i, e) {
                if (typeof(e) == "object") {
                    console.log(e);
                    console.log(allBind);
                    // todo
                } else {
                    $(element)[i](e);
                }
            });
        } else {
            $(element)[value]();
        }
    }
};

$.executeRenderBind = function (element, name, value, allBind) {
    var bind = bindings[name];
    try {
        bind(element, value, allBind);
    }
    catch (err) {
        console.log(err);
        console.log("Error binding " + name + ' ' + err.name);
    }
};

$.executeRender = function (data) {
    for (var i in data) {
        //noinspection JSUnfilteredForInLoop
        var value = data[i];
        $(i).each(function (i, e) {
            if (typeof(value) == 'object') {
                for (var name in value) {
                    $.executeRenderBind(e, name, value[name], value);
                }
            } else {
                $.executeRenderBind(e, value, null, {value: null});
            }
        });
    }
};

// endregion render binding