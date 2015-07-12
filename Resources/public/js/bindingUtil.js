var bindingUtil = {
    redirect: function (url) {
        location.href = url;
    },
    formSubmit: function (form) {
        $('body').append('<div class="loading"></div>');
        $.post($(form).attr('action'), $(form).serializeArray(), function (data) {
            $.updateAjaxResponse(data);
        }, "json");
    },
    sendPost: function (action) {
        $('body').append('<div class="loading"></div>');
        $.post(action, function (data) {
            $('.popover').popover('hide');
            $.updateAjaxResponse(data);
        }, "json");
    },
    sendPostConfirm: function (action, message) {
        if (confirm(message)) {
            $('body').append('<div class="loading"></div>');
            $.post(action, function (data) {
                $('.popover').popover('hide');
                $.updateAjaxResponse(data);
            }, "json");
        }
    },
    sendPost2: function (action, params) {
        $('body').append('<div class="loading"></div>');
        $.post(action, params, function (data) {
            $.updateAjaxResponse(data);
        }, "json");
    },
    sendPost2Confirm: function (action, params, message) {
        if (confirm(message)) {
            $('body').append('<div class="loading"></div>');
            $.post(action, params, function (data) {
                $.updateAjaxResponse(data);
            }, "json");
        }
    },
    updateAjaxResponse: function (data, options) {
        if (data.status == undefined) {
            return;
        }
        if (data.status == 0) {
            if (data.response.href != undefined) {
                location.href = data.response.href;
            }
        }

        if (data.response.render != undefined) {
            executeRender(data.response.render);
        }

        $('.loading').remove();

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
        $.each(data.warnings, function (i, e) {
            alert(e);
        });
        $.each(data.messages, function (i, e) {
            $().toastmessage('showToast', {
                text: e,
                position: 'top-center',
                type: 'success'
            });
        });
    }
}
