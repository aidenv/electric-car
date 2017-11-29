(function($) {
    'use strict';

    var miniPager = {
        elem: null,
        setPage: function(evt, page) {
            miniPager.elem.find('.current-page').text(page);
            if (page < 2) {
                miniPager.elem.find('[data-mini-pager-previous]').hide();
            }
            else {
                miniPager.elem.find('[data-mini-pager-previous]').show();
            }

            var totalPage = miniPager.elem.find('.total-page').text();
            if (page >= totalPage) {
                miniPager.elem.find('[data-mini-pager-next]').hide();
            }
            else {
                miniPager.elem.find('[data-mini-pager-next]').show();
            }
        },
        setLastPage: function(lastPage) {
            miniPager.elem.find('.total-page').text(lastPage);
        },
        init: function($elem, page, lastPage) {
            miniPager.elem = $elem;
            miniPager.setPage(page);
            miniPager.setLastPage(lastPage);

            $elem.find('[data-mini-pager-previous]').on('click', function(evt) {
                evt.preventDefault();
                var currentPage = miniPager.elem.find('.current-page').text();
                $elem.trigger('previousPage', --currentPage);
            });

            $elem.find('[data-mini-pager-next]').on('click', function(evt) {
                evt.preventDefault();
                var currentPage = miniPager.elem.find('.current-page').text();
                $elem.trigger('nextPage', ++currentPage);
            });

            $elem.on('setPage', miniPager.setPage);

            return this;
        }
    };

    $.fn.miniPager = function(page, lastPage) {
        return miniPager.init(this, page, lastPage);
    };

})(jQuery);