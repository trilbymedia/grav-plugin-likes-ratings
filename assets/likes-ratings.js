((function($) {
    $(document).ready(function() {
        $(document).on('click', '[data-likes-ratings] [data-likes-type]', function(event) {
            var target = $(event.currentTarget);
            var container = target.closest('[data-likes-ratings');
            var data = container.data('likesRatings');
            var type = target.data('likesType');

            if (data.options.readOnly || container.data('disableRatings')) { return true; }

            $.ajax({
                type: 'POST',
                url: data.uri,
                data: { id: data.id, type: type },
                success: function (response) {
                    if (response.status) {
                        var query = '[data-likes-id="' + data.id + '"] [data-likes-type="' + type + '"] [data-likes-status]';
                        $(query).text(response.count);
                        if (data.options.disableAfterRate) {
                            container.data('disableRatings', true);
                        }
                    }
                }
            });
        });
    });
})(jQuery));
