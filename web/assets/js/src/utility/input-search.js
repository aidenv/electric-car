(function($) {

    var $dataSearch = $('[data-search]');

    if ($dataSearch.length > 0) {
        $dataSearch.each(function() {
            var $elem = $(this);

            var clickTarget = $elem.data('search');
            var $clickTarget = $(clickTarget);

            var getParams = function() {
                var get_params = {};
                window.location.search.substr(1).split('&').forEach(function(item) {
                    if (item) {
                        tmp = item.split('=');
                        get_params[tmp[0]] = decodeURIComponent(tmp[1]);
                    }
                });

                return get_params;
            };

            var params = getParams();
            if (params.hasOwnProperty('q')) {
                $elem.val(params.q);
            }

            var search = function(evt) {
                var get_params = getParams();

                if (get_params.hasOwnProperty('page')) {
                    delete get_params.page;
                }

                get_params.q = $elem.val();

                var query = '?';

                for (var param in get_params) {
                    query += (query != '?' ? '&' : '')+param+'='+encodeURIComponent(get_params[param]);
                }

                window.location = query;
            };

            $clickTarget.on('click', search);

            //if pressed enter
            $elem.on('keyup', function(evt) {
                if (evt.which == 13) {
                    search(evt);
                }
            });
        });
    }
})(jQuery);
