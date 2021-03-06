/**
 * Аякс форма:  <form method="POST" class="ajax-form" action="/url" onsubmit="return false">
 *     submit-change - класс сабмитит форму на любом изменении
 *     submit-click - Сабмитит ворму на клик
 *
 *     binding-loading - При загрузке аякса вешается на кнопку
 *     binding-content-before - контент кнопки видимый до загрузки
 *     binding-content-loading - контент кнопки видимый во время загрузки
 *
 *     data-binding-action - урл для аякса
 *     data-binding-method - метод для запроса
 *     data-binding-data - данные для запроса
 *     data-binding-once - запускать аякс 1 раз
 *     data-binding-scroll - запускать аякс на скроле
 *     data-binding-disable - отключеный
 */
$(function () {
    $('body')
        .on('submit', '.ajax-form', function () { /* Отправка формы аяксом */
            $.bindingsUtil.formSubmit($(this));
        })
        .on('change', '.submit-change input', function () { /* обрабтка автоотправляемых элементов */
            $(this).closest('form').submit();
        })
        .on('blur', '.submit-change textarea', function () { /* обрабтка автоотправляемых элементов */
            $(this).closest('form').submit();
        })
        .on('click', '.submit-click', function () {
            $(this).closest('form').submit();
        })
        .on('click', '[data-binding-action]', function (event) { /* аякс клик */
            if (!$(this).data('binding-disable')) { /* Проверка на дизайбл */
                if ($(this).data('binding-once')) { /* если запускать 1 раз - то дизаблим сразу */
                    $(this).data('binding-disable', true).addClass('binding-disable')
                }
                var d = $(this).data('binding-data');
                var method = $(this).data('binding-method');
                if (!method && d) {
                    method = 'post'
                }
                var self = $(this);
                self.addClass('binding-loading');
                $.ajax({
                    method: method ? method : 'get',
                    url: $(this).data('binding-action'),
                    data: d,
                    success: function (data) {
                        $.bindingsUtil.updateAjaxResponse(data);
                        self.removeClass('binding-loading')
                    },
                    error: function (data) {
                        console.log(data);
                        self.removeClass('binding-loading')
                    },
                    dataType: 'json'
                });
            }
        });


    $(window).scroll(function () { /* Обработка запускаемых на скроле */
        var scrollTop = $(this).scrollTop();
        var h = $(this).height();
        $('[data-binding-scroll=true]').each(function (i, e) {
            if ($(e).offset().top < scrollTop + h) {
                $(e).click();
            }
        });
    });

});
$.bindingsUtil = {
    /**
     * Редирект по урлу
     * @param url
     */
    redirect: function (url) {
        location.href = url;
    },

    /**
     * Отправка формы
     * @param form
     */
    formSubmit: function (form) {
        $('body').append('<div class="loading"></div>');
        $.post($(form).attr('action'), $(form).serializeArray(), function (data) {
            $.bindingsUtil.updateAjaxResponse(data);
        }, "json");
    },

    /**
     * Отправка постом без параметров
     * @param action
     */
    sendPost: function (action) {
        $('body').append('<div class="loading"></div>');
        $.post(action, function (data) {
            $('.popover').popover('hide');
            $.bindingsUtil.updateAjaxResponse(data);
        }, "json");
    },

    /**
     * Отправка постом с подтверждением
     * @param action
     * @param message
     */
    sendPostConfirm: function (action, message) {
        if (confirm(message)) {
            $('body').append('<div class="loading"></div>');
            $.post(action, function (data) {
                $('.popover').popover('hide');
                $.bindingsUtil.updateAjaxResponse(data);
            }, "json");
        }
    },

    /**
     * отправка постом с доп параметрами
     * @param action
     * @param params
     * @param callable
     */
    sendPost2: function (action, params, callable) {
        var c = callable;
        $('body').append('<div class="loading"></div>');
        $.post(action, params, function (data) {
            bindingsUtil.updateAjaxResponse(data);
            if (c) {
                c(data);
            }
        }, "json");
    },

    /**
     * Отправка постом с подтверждеием
     * @param action
     * @param params
     * @param message
     */
    sendPost2Confirm: function (action, params, message) {
        if (confirm(message)) {
            $('body').append('<div class="loading"></div>');
            $.post(action, params, function (data) {
                $.bindingsUtil.updateAjaxResponse(data);
            }, "json");
        }
    },

    /**
     * Обработка ответа
     * @param data
     * @param options
     */
    updateAjaxResponse: function (data, options) {
        if (data) {
            /**
             * Неизвестный запрос
             */
            if (data.status == undefined) {
                return;
            }

            /**
             * Статус успешный
             */
            if (data.status == 0) {
                if (data.response.href != undefined) {
                    location.href = data.response.href;
                }
            }

            /**
             * В ответе есть данные для биндинга
             */
            if (data.response && data.response.render != undefined) {
                $.executeRender(data.response.render);
            }

            /**
             * В ответе массив биндингов
             */
            if (data.response && data.response.renders != undefined) {
                $.each(data.response.renders, function (i, e) {
                    $.executeRender(e);
                });
            }
            /**
             * Удаляем индиатор загрузки
             */
            $('.loading').remove();

            /**
             * Обрабатываем ошибки
             */
            if (data.errors) {
                $.each(data.errors, function (i, e) {
                    var element = $('#' + i);
                    if (element.length > 0) {
                        element.closest('.form-group').addClass('has-error');
                        element.popover({placement: 'bottom', 'content': e}).popover('show');
                        setTimeout(function () {
                            element.popover('destroy');
                        }, 3000);
                    } else {
                        $().toastmessage('showToast', {
                            text: e,
                            position: 'top-center',
                            type: 'error'
                        });
                    }
                });
            }

            /**
             * Обрабатываем варнинги
             */
            if (data.warnings) {
                $.each(data.warnings, function (i, e) {
                    alert(e);
                });
            }

            /**
             * Обрабатываем сообщения
             */
            if (data.messages) {
                $.each(data.messages, function (i, e) {
                    $().toastmessage('showToast', {
                        text: e,
                        position: 'top-center',
                        type: 'success'
                    });
                });
            }
        }
    }
};
