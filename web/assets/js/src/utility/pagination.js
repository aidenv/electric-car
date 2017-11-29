(function($){
    'use strict';

    var options = {
        page: 1,
        extremePagesLimit: 2,
        nearbyPagesLimit: 3,
        partialPage: function(data) {
            data = $.extend({
                class: ''
            }, data);

            return '<li data-pagination-page="'+data.page+'" class="'+data.class+'"><a href="#">'+data.page+'</a></li>';
        },
        partialLeftControl: function() {
            var $partial = $(
                '<li data-pagination-previous-page class="page-navigation">'+
                    '<a href="#">'+
                        '&larr; previous'+
                    '</a>'+
                '</li>'
            );

            return $partial;
        },
        partialRightControl: function() {
            var $partial = $(
                '<li data-pagination-next-page class="page-navigation">'+
                    '<a href="#">'+
                        'next &rarr;'+
                    '</a>'+
                '</li>'
            );

            return $partial;
        },
        controlBehaviour: function(page) {
            if (page <= 1) {
                this.container.find('[data-pagination-previous-page]').hide();
            }
            else {
                this.container.find('[data-pagination-previous-page]').show();   
            }
            if (page >= this.lastPage) {
                this.container.find('[data-pagination-next-page]').hide();
            }
            else {
                this.container.find('[data-pagination-next-page]').show();
            }
        },
        partialPages: function(page, lastPage) {
            var partial = '';

            if (page > 1) {
                for (var i = 1; i <= this.extremePagesLimit; i++) {
                    if (i < page - this.nearbyPagesLimit) {
                        partial += this.partialPage({page: i});
                    }
                }

                if (this.extremePagesLimit + 1 < page - this.nearbyPagesLimit) {
                    partial += this.partialPage({page: '...', class: 'disabled'});
                }

                for (var i = page - this.nearbyPagesLimit; i <= page - 1; i++) {
                    if (i > 0) {
                        partial += this.partialPage({page: i});
                    }
                }
            }

            partial += this.partialPage({page: page});

            if (page < lastPage) {
                for (var i = page + 1; i <= page + this.nearbyPagesLimit; i++) {
                    if (i <= lastPage) {
                        partial += this.partialPage({page: i});
                    }
                }

                if ((lastPage - this.extremePagesLimit) > (page + this.nearbyPagesLimit)) {
                    partial += this.partialPage({page: '...', class: 'disabled'});
                }

                for (var i = lastPage - this.extremePagesLimit + 1; i <= lastPage; i++) {
                    if (i > page + this.nearbyPagesLimit) {
                        partial += this.partialPage({page: i});
                    }
                }
            }

            return $(partial);
        },
        render: function(page, lastPage) {
            if (page > lastPage) {
                return;
            }

            var $leftControl = this.partialLeftControl();
            var $pages = this.partialPages(page, lastPage);
            var $rightControl = this.partialRightControl();
            this.container.empty();
            var $div = $('<div class="pagination pagination-centered"></div>');
            this.container.append($div);

            var $ul = $('<ul class="list-unstyled"></ul>');
            $div.append($ul);
            $ul.append($leftControl)
                      .append($pages)
                      .append($rightControl)
            ;

            this.setPage(page);
            page > 1 ? $leftControl.show(): $leftControl.hide();
            page < lastPage ? $rightControl.show(): $rightControl.hide();
        },
        previousPage: function(page) {
            var page = this.getPage();
            this.setPage(--page);

            return page;
        },
        lockPage: false,
        silenceChange: false,
        setPage: function(page) {
            if (this.lockPage) {
                return;
            }
            var $active = this.container.find('ul > li.active');
            var $toActive = this.container.find('ul > li[data-pagination-page="'+page+'"]');
            if ($toActive.length > 0) {
                $active.removeClass('active');
                $toActive.addClass('active');
                this.controlBehaviour(page);
                if (!this.silenceChange) {
                    this.container.trigger('pageChanged', page);
                }
            }
            else {
                this.render(page, this.lastPage);
            }

        },
        getPage: function() {
            var $active = this.container.find('ul > li.active a');
            var page = $active.text();

            return page;
        },
        nextPage: function() {
            var page = this.getPage();
            this.setPage(++page);

            return page;
        },
        attachEvents: function() {
            var $this = this;
            this.container.on('click', '[data-pagination-next-page]', function(evt) {
                evt.preventDefault();
                $this.nextPage();
            });
            this.container.on('click', '[data-pagination-previous-page]', function(evt) {
                evt.preventDefault();
                $this.previousPage();
            });
            this.container.on('click', '[data-pagination-page]', function(evt) {
                evt.preventDefault();
                var page = $(this).data('pagination-page');
                $this.setPage(page);
            });
        }
    };

    $.fn.pagination = function(settings) {
        settings = $.extend({}, this.data(), options, settings);
        if (settings.lastPage < 2) {
            return;
        }

        settings.container = this;
        settings.container.empty();
        settings.render(settings.page, settings.lastPage);
        settings.attachEvents();

        return settings;
    };
})(jQuery);