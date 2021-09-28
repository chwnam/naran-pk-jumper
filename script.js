(function ($) {
    $(document).ready(function () {
        $('.npj-checkbox').on('click', function (e) {
            var rel = e.currentTarget.getAttribute('rel'),
                ai = $('#' + rel);
            if (undefined === ai.attr('disabled')) {
                ai.attr('disabled', 'disabled');
            } else {
                ai.removeAttr('disabled');
            }
        });

        $('#show-only-builtin').on('change', function (e) {
            var checked = $(e.currentTarget).is(':checked');

            if (checked) {
                $('.npj-table > tbody > tr:not(.npj-builtin)').addClass('hidden');
            } else {
                $('.npj-table > tbody > tr').removeClass('hidden');
            }
        }).trigger('change');

        $('form').submit(function (e) {
            if (!confirm('Did you backup your database? Are you sure?')) {
                e.preventDefault();
                return false;
            }
        });
    });
})(jQuery);