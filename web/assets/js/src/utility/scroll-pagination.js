/*
 * requires: 
 *      https://github.com/thesmart/jquery-scrollspy.git;
 *      js/bower/scrollspy.js;
 *      js/src/utility/scroll.js
 *      js/src/utility/pagination.js
 *
 *
 */
(function($) {
    'use strict';

    var options = {
        pagination: '[data-scroll-pagination]',
        content: '[data-scroll-pagination-content]',
        getPageContent: function(page) {
            page = page > 1 ? page: '';
            var $pageContent = $('[data-scroll-pagination-content="'+page+'"]');

            return $pageContent;
        },
        pageLoader: function(page) {},
        loadPage: function(page) {
            if (!this.paginationObj) return;
            this.paginationObj.lockPage = true;
            var settings = this;
            var pageData = this.pageLoader(page);
            $.when(pageData).done(function(html) {
                var $html = $(html);
                $html.attr('data-scroll-pagination-content', page);

                var $contents = $(settings.content);
                $contents.sort(function(a, b) {
                    return $(b).data('scrollPaginationContent') - $(a).data('scrollPaginationContent');
                });
                var $precedingContent;
                $contents.each(function() {
                    var $elem = $(this);
                    var currentPage = $elem.data('scrollPaginationContent');
                    if (page > currentPage && !$precedingContent) {
                        $precedingContent = $elem;
                    }
                });

                $html.insertAfter($precedingContent);
                settings.scrollSpy($html);
                $html.scrollTo(500, -200);
            }).always(function() {
                settings.paginationObj.lockPage = false;
            });
        },
        lockScrollSpy: false,
        scrollSpy: function($elem) {
            var settings = this;
            var $firstProduct = $elem.find('.col-for-product-group [data-addtocart]').first();
            $firstProduct.scrollSpy();
            $firstProduct.on('scrollSpy:enter', function() {
                if (!settings.lockScrollSpy) {
                    var page = $elem.data('scrollPaginationContent');
                    page = page > 1 ? page: 1;
                    if (settings.paginationObj) {
                        settings.paginationObj.silenceChange = true;
                        settings.paginationObj.setPage(page);
                        settings.paginationObj.silenceChange = false;
                        settings.paginationObj.container.trigger('scrollPageChanged', page);
                    }
                }
            });
        },
        setPage: function(page) {
            var settings = this;
            var $pageContent = this.getPageContent(page);
            if ($pageContent.length > 0) {
                settings.lockScrollSpy = true;
                $pageContent.scrollTo(500, -200, function() {
                    settings.lockScrollSpy = false;
                });
            }
            else {
                this.loadPage(page);
            }
        },
        scrollBottomBehaviour: function() {
            var settings = this;
            var $win = $(window);
            $win.on('scroll', function () {
                if ($win.height() + $win.scrollTop() == $(document).height()) {
                    if (settings.paginationObj) {
                        settings.paginationObj.nextPage();
                    }
                }
            });
        },
        initializePagination: function() {
            var settings = this;
            var $pagination = $(this.pagination);
            this.paginationObj = $pagination.pagination();
            $pagination.on('pageChanged', function(evt, page) {
                settings.setPage(page);
            });
            $pagination.stickyScroll();
        }
    }

    $.fn.scrollPaginate = function(settings) {
        settings = $.extend({}, options, settings);
        settings.initializePagination();
        settings.scrollBottomBehaviour();

        var $firstPage = $(settings.content);
        settings.scrollSpy($firstPage);

        return settings;   
    };
})(jQuery);