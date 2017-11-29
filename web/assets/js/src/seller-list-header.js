(function ($) {

    $(document).ready(function() {

        queryString = getUrlParameter('query');
        var defaultSortBy = getUrlParameter('sortBy');
        var defaultSortDirection = getUrlParameter('sortDirection');
        var sortingKeyword = defaultSortBy + '~' + defaultSortDirection;
        $('.sort-by-filter .item[data-value="'+sortingKeyword+'"]').click();        

        var $paginationContainer = $('.simple-pagination');
        var $paginationUnorderedList = $paginationContainer.find('ul');
        var totalPages = $paginationUnorderedList.data('lastpage');
        var currentPage = $paginationUnorderedList.data('currentpage');
        var hrefprefix = $paginationUnorderedList.length > 0 ? $paginationUnorderedList.data('hrefprefix'): '';
        var isQmarkFound = hrefprefix.indexOf('?') > -1;

        if ($paginationContainer.length > 0) {
            $paginationContainer.pagination({
                pages: totalPages, 
                displayedPages: 10,
                currentPage: currentPage,
                hrefTextPrefix: hrefprefix + (isQmarkFound ? '&' : '?') + 'page=',
                prevText: '<i class="icon icon-arrow-short-left"></i>Previous',
                nextText: 'Next <i class="icon icon-arrow-short-right"></i>'
            });
        }
        
        
        $('.category-filter').on('change', function(){
            var sortData = $('.sort-by-filter option:selected').val().split('~');
            var sortQuery = "sortBy="+sortData[0]+"&sortDirection="+sortData[1];
            
            var filterString = sortQuery;
            if(typeof queryString !== 'undefined'){
                filterString =  "query=" + queryString + "&" + filterString;
            }
            /**
             * Reload page with query parameters
             */
            var reloadUrl = window.location.origin + window.location.pathname + "?" + filterString;
            window.location.href = reloadUrl;
        });


        $(".sort-modal.product-search-sort li").click(function(){
            var $value = $(this).find("a").attr("value");

            $(".sort-by-filter").dropdown("set selected", $value);
        });
    });

}(jQuery));
