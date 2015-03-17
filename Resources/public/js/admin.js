$(function () {
    $.fn.select2.locales['ru'] = {
        formatMatches: function (matches) {
            if (matches === 1) {
                return "Одно занчение доступно, Enter для выбора.";
            }
            return matches + " значений доступно, используйте кнопки вверх/вниз для навигации";
        },
        formatNoMatches: function () {
            return "Совпадений не найдено";
        },
        formatAjaxError: function (jqXHR, textStatus, errorThrown) {
            return "Загрузка завершена с ошибкой";
        },
        formatInputTooShort: function (input, min) {
            var n = min - input.length;
            return "Введите " + n + " или больше символов" + (n == 1 ? "" : "s");
        },
        formatInputTooLong: function (input, max) {
            var n = input.length - max;
            return "Удалите " + n + " символ" + (n == 1 ? "" : "ов");
        },
        formatSelectionTooBig: function (limit) {
            return "Вы можете выбрать только " + limit + " значени" + (limit == 1 ? "е" : "й");
        },
        formatLoadMore: function (pageNumber) {
            return "Загрузка результатов…";
        },
        formatSearching: function () {
            return "Поиск…";
        }
    };
});