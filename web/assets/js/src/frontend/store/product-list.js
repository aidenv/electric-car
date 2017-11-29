(function($) {
    var $body = $('body');
    var $dataProductSearch = $('[data-product-search]');
    var getParams = getUrlParameters();

    var getProductSearchData = function(data) {
        data = data ? data: {};
        $dataProductSearch.each(function() {
            var $elem = $(this);
            var key = $elem.data('product-search');
            if ($elem.is('input[type="checkbox"]')) {
                data[key] = [];
                $('[data-product-search="'+key+'"]:checked').each(function() {
                    data[key].push($(this).val());
                });
            }
            else if (!$elem.is('[data-value]')) {
                data[key] = $elem.val();
            }
            else if ($elem.is('[data-value].active')) {
                data[key] = $elem.data('value');
            }
        });

        return data;
    };
    var $searchWrapper = $('#searchWrapper');
    $searchWrapper.yiLoader({
        fixed: true
    });

    var scrollPaginate = {
        pageLoader: function(page) {
            var data = getProductSearchData();
            data.page = page;
            data.justrow = true;

            var deferredData = $.ajax({
                beforeSend: function() {
                    $searchWrapper.trigger('loader.start');
                },
                type: 'POST',
                data: data,
                success: function() {
                    $('.list-view-trigger.active').trigger('click', {animate: false});
                },
                complete: function() {
                    $searchWrapper.trigger('loader.stop');
                }
            });

            return deferredData;
        }
    };
    var scrollPagination = $.fn.scrollPaginate(scrollPaginate);

    var $scrollPagination = $('[data-scroll-pagination]');
    var $scrollPaginationPage = $scrollPagination.find('[data-pagination-page]');

    var $miniPager = $('.pagination-short');
    var miniPagerSettings = $miniPager.miniPager(
        $scrollPaginationPage.first().data('pagination-page'),
        $scrollPaginationPage.last().data('pagination-page')
    );

    $('body').on('pageChanged, scrollPageChanged', '[data-scroll-pagination]', function(evt, page) {
        $miniPager.trigger('setPage', page);
    });

    $miniPager.on('nextPage', function(evt, page) {
        scrollPagination.setPage(page);
    });

    $miniPager.on('previousPage', function(evt, page) {
        scrollPagination.setPage(page);
    });

    $body.on('updateList', function(evt) {
        var $target = $(evt.target),
            data = getProductSearchData(data);

        if ($target.is('[data-value]')) {
            var key = $target.data('product-search');
            data[key] = $target.data('value');
        }
        data = $.extend(getParams, data);
        $.ajax({
            type: 'POST',
            data: data,
            beforeSend: function() {
                $searchWrapper.trigger('loader.start');
            },
            success: function(html) {
                $('.search-body-wrapper').replaceWith(html);
                $body.trigger('adjustImageDisplay');
                var scrollPaginateSettings = $.fn.scrollPaginate(scrollPaginate);
                var lastPage = 1;
                if (scrollPaginateSettings.paginationObj && scrollPaginateSettings.paginationObj.lastPage) {
                    lastPage = scrollPaginateSettings.paginationObj.lastPage;
                }
                miniPagerSettings.setPage(0, 1);
                miniPagerSettings.setLastPage(lastPage);

                $('.list-view-trigger.active').trigger('click', {animate: false, removeActive: true});
            },
            complete: function() {
                $searchWrapper.trigger('loader.stop');
            }
        });
    });

    $('.price-range-container').on('mouseup', function(evt) {
        $('input#priceRange').trigger('updateList');
    });

    $dataProductSearch.each(function() {
        var $elem = $(this);
        $elem.on('change click', function(evt) {
            evt.preventDefault();
            if (!$elem.is('input#priceRange') && !$elem.is('input#main-search-input')) {
                $elem.trigger('updateList');
            }
        });
    });

    $("div.store-search-form-container").on('keypress', 'input#main-search-input', function(event) {
        var keycode = (event.keyCode ? event.keyCode : event.which),
            self = $(this);

        if(keycode == 13){
            self.trigger('updateList');
        }
    });

    var $dataActivator = $('[data-activator]');
    $dataActivator.on('click', function(evt) {
        var $elem = $(this);
        var target = $elem.data('activator');
        var $targets = $elem.find(target);
        $targets.removeClass('active');
        var $clickTarget = $(evt.target);
        $clickTarget.addClass('active');
    });

})(jQuery);
