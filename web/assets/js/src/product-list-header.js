(function ($) {

    var priceFrom = 0;
    var priceTo = 0;
    var queryString = "";

    $(document).ready(function() {

        $(".sidebar").css({
            left: "-60px"
        });

        $(".wrapper-outer").css({
            paddingLeft: 0
        });

        $(".navbar .dropdown-menu").addClass("toggled");

        queryString = getUrlParameter('query');
        var defaultPriceFrom = getUrlParameter('priceFrom');
        var defaultPriceTo = getUrlParameter('priceTo');

        var defaultSubcategoryString = getUrlParameter('subcategories');
        var defaultSubcategoryids = [];
        if(typeof defaultSubcategoryString != 'undefined'){
            defaultSubcategoryids = defaultSubcategoryString.split(",");
        }

        var searchedCategorieLength = $('.subcategory-filter-checkbox').length;

        if((defaultSubcategoryids.length+1) == searchedCategorieLength){
            $('.subcategory-filter-checkbox[name="category_filter_all"]').prop('checked', true);
        }
        else{
            $.each(defaultSubcategoryids, function(index, value){
                $("input[type='checkbox'][data-mobile-product-search='categories'][value='" + value + "']").prop('checked', true);
                $('.subcategory-filter-checkbox[data-subcategoryid="'+value+'"]').prop('checked', true);
            });
        }

        var defaultBrandString = getUrlParameter('brands');
        var defaultBrands = [];
        if(typeof defaultBrandString != 'undefined'){
            defaultBrands = defaultBrandString.split(",");
        }

        var searchedBrandLength = $('.brand-checkbox').length;

        if((defaultBrands.length+1) == searchedBrandLength){
            $('.brand-checkbox[name="brand_filter_all"]').prop('checked', true);
        }
        else{
            $.each(defaultBrands, function(index, value){
                $("input[type='checkbox'][data-mobile-product-search='brands'][value='" + value + "']").prop('checked', true);
                $('.brand-checkbox[data-brand="'+value+'"]').prop('checked', true);
            });
        }

        var defaultSortBy = getUrlParameter('sortBy');
        var defaultSortDirection = getUrlParameter('sortDirection');
        var sortingKeyword = defaultSortBy + '~' + defaultSortDirection;

        $('.sort-by-filter .item[data-value="'+sortingKeyword+'"]').click();
        var $priceRange = $("#priceRange");
        var $priceRangeMobile = $("#priceRangeMobile");
        var updatePriceRangeResult = function(data){
            priceFrom = data.from;
            priceTo = data.to;
        }

        $priceRange.ionRangeSlider({
            type: "double",
            min: parseFloat($priceRange.data('min')),
            max: parseFloat($priceRange.data('max')),
            prettify_enabled: true,
            prettify_separator: ",",
            from: defaultPriceFrom,
            to: defaultPriceTo,
            onStart: function(data){
                updatePriceRangeResult(data);
            },
            onChange: updatePriceRangeResult,
            onFinish: updatePriceRangeResult
        });

        var $slider = $priceRange.data("ionRangeSlider");
        var updateOriginalPriceRangeResult = function(){
            var $val = $priceRangeMobile.val();
            var $split = $val.split(";");

            $priceRange.val($val);

            priceFrom = $split[0];
            priceTo = $split[1];

            $slider.update({
                from: parseFloat($split[0]),
                to: parseFloat($split[1])
            });
        }

        $priceRangeMobile.ionRangeSlider({
            type: "double",
            min: parseFloat($("[data-mobile-product-search='priceRange']").attr("data-min")),
            max: parseFloat($("[data-mobile-product-search='priceRange']").attr("data-max")),
            prettify_enabled: true,
            prettify_separator: ",",
            from: defaultPriceFrom,
            to: defaultPriceTo,
            force_edges: true,
            onChange: updateOriginalPriceRangeResult,
        });

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
            applyCategoryFilter();
        });

        var isPriceSliderOpen = false;
        $('#price-slider-trigger').on('click', function(){
            isPriceSliderOpen = !isPriceSliderOpen;
            if(isPriceSliderOpen == false){
                applyCategoryFilter();
            }
        });

        //List view trigger
        $( ".list-view-trigger" ).click(function(evt, data) {
            var $this = $(this);
            if (data && data.hasOwnProperty('removeActive') && data.removeActive) {
                $this.removeClass('active');
            }

            if (!$this.hasClass('active')) {
                $(".button-view").not($this).removeClass("active");
                $this.addClass("active");
                var adjustList = function(){
                    $( ".search-body-wrapper" ).addClass("list-view");
                    $(".col-for-product-group").removeClass("col-xl-2 col-md-2 col-sm-2 col-xs-6").addClass("col-sm-6 col-xs-12");
                    $( ".col-price-button-container" ).removeClass("col-md-7").addClass("col-xs-6");
                    $( ".col-star-rating-container" ).removeClass("col-md-5").addClass("col-xs-6");
                    $('.search-body-wrapper').stop().animate({opacity:1},"fast");
                };

                if (data && data.hasOwnProperty('animate') && !data.animate) {
                    setTimeout(function() {
                        adjustList();
                    }, 1);
                }
                else {
                    $('.search-body-wrapper').animate({opacity:0}, adjustList);
                }
            }
        });

        //Grid view trigger
        $( ".grid-view-trigger" ).click(function() {
            var $this = $(this);
            if (!$this.hasClass('active')) {
                $(".button-view").not($this).removeClass("active");
                $this.addClass("active");
                $('.search-body-wrapper').animate({
                    opacity:0
                },function(){
                    $( ".search-body-wrapper" ).removeClass("list-view");
                    $(".col-for-product-group").removeClass("col-sm-6 col-xs-12").addClass("col-xl-2 col-md-2 col-sm-2 col-xs-6");
                    $( ".col-price-button-container" ).removeClass("col-xs-6").addClass("col-md-7");
                    $( ".col-star-rating-container" ).removeClass("col-xs-6").addClass("col-md-5");
                    $('.search-body-wrapper').stop().animate({opacity:1},"fast");

                    adjustImageDisplay();
                });
            }
        });

        $("#subCategoryFilter").stick_in_parent({
            parent: "#searchWrapper",
            offset_top: 50
        });

        $(".control-dropdown-menu").on('click', function(evt) {
            evt.stopPropagation();
        });

        $('.dropdown.icon').on('click', function(evt) {
            evt.preventDefault();
            $(this).closest('.control-dropdown').click();
        });
        //Dropdown menu
        $(".control-dropdown").click(function(evt){
            var $controlDropdown = $(this);
            var dropdownMenu =  $(this).parents(".control-type-container").find(".control-dropdown-menu");
            dropdownMenu.slideToggle("fast");
            $(".control-dropdown-menu").not(dropdownMenu).slideUp("fast");
            $(".control-dropdown").not($(this)).removeClass("active")
            $(this).toggleClass("active");
        });

        $(document).bind('click', function (e) {
            var $clicked = $(e.target);
            if (!$clicked.parents().hasClass("control-type-container")){

                if($(".control-dropdown#price-slider-trigger").hasClass("active")){
                    applyCategoryFilter();
                }

                $(".control-dropdown-menu").stop().slideUp("1000", function(){
                    $(".control-dropdown").removeClass("active");
                });
            }
        });

        //For category header list
        $(".expand-category-trigger").click(function(){
            var listCategoryHeight = $(".list-search-result-type").outerHeight() + 7;
            $(".search-category-header").animate({
                height: listCategoryHeight
             });

            $(this).transition({
                animation: "scale",
                onComplete : function() {
                    $(".compress-category-trigger").transition({
                        animation: "scale",
                        interval:   500
                    });
                }
            });
        });

        $(".compress-category-trigger").click(function(){
            $(".search-category-header").animate({
                height: "33px"
             });

            $(this).transition({
                animation: "scale",
                onComplete : function() {
                    $(".expand-category-trigger").transition({
                        animation: "scale",
                        interval:   500
                    });
                }
            });
        });
    });

    $(document).on("load scroll", function(){

         $(".list-search-category > li").each(function(){
            var distanceFromTopPage = $(this).offset().top + 28;
            var windowTop = $(window).scrollTop();
            var SubCategoryContainerTop = distanceFromTopPage-windowTop;

            $(this).find(".category-bottom-header").css({
                top: SubCategoryContainerTop
            });
        });
    });

    $(document).on("ready load resize", function(){
        var listCategoryHeight = $(".list-search-result-type").outerHeight();

        if(listCategoryHeight<29){
            $(".search-header-more").css({
                display: "none"
            });
        }
        else{
            $(".search-header-more").css({
                display: "table-cell"
            });
        }
    });

    var applyCategoryFilter = function()
    {
        var priceRangeQuery = "priceFrom="+priceFrom+"&priceTo="+priceTo,
            brandQuery = "brands=";

        if($('.brand-checkbox[name=brand_filter_all]:checked').length){
            $('.brand-filter-checkbox').prop('checked', false);
            $('.brand-filter-checkbox[name=brand_filter_all]').prop('checked', true);
        }
        else {
            $('.brand-checkbox:checked').each(function(){
                if($(this).attr("name") != "brand_filter_all"){
                    brandQuery = brandQuery + $(this).data('brand')+',';
                }
            });

            brandQuery = brandQuery.charAt(brandQuery.length-1) == "," ? brandQuery.slice(0,-1) : brandQuery;
        }

        var sortData = $('.sort-by-filter option:selected').val();

        if(typeof sortData != "undefined" && sortData.indexOf('~')){
            sortData = sortData.split("~");
        }

        var sortQuery = "sortBy="+sortData[0]+"&sortDirection="+sortData[1];

        var sortData = $('.sort-by-filter option:selected').val().split('~'),
            sortQuery = "sortBy="+sortData[0]+"&sortDirection="+sortData[1],
            countryFilterString = "",
            countryFilter = $("#country-filter");

        if (countryFilter.length > 0 && countryFilter.val().trim() != "") {
            countryFilterString = "country=" + countryFilter.val().trim();
        }

        var subcategoryQuery = "subcategories=";

        if($('.subcategory-filter-checkbox[name=category_filter_all]:checked').length){
            $('.subcategory-filter-checkbox').prop('checked', false);
            $('.subcategory-filter-checkbox[name=category_filter_all]').prop('checked', true);
        }
        else {
            $('.subcategory-filter-checkbox:checked').each(function(){
                if($(this).attr("name") != "category_filter_all"){
                    subcategoryQuery = subcategoryQuery +  $(this).data('subcategoryid')+',';
                }
            });
            subcategoryQuery = subcategoryQuery.charAt(subcategoryQuery.length-1) == ","
                               ? subcategoryQuery.slice(0,-1)
                               : subcategoryQuery;
        }

        filterString = priceRangeQuery + "&" + subcategoryQuery + "&" + brandQuery + "&" +sortQuery + "&" + countryFilterString;
        if(typeof queryString !== 'undefined'){
            filterString =  "query=" + queryString + "&" + filterString;
        }

        var reloadUrl = window.location.origin + window.location.pathname + "?" + filterString;
        window.location.href = reloadUrl;
    }

    var adjustImageDisplay = function() {
        //Assign image height of the product container
        var widthOfImageWrapper = $(".image-display").outerWidth();
        var getTheAdditionalHeight = widthOfImageWrapper*0.04;
        var heightOfImageWrapper = widthOfImageWrapper + getTheAdditionalHeight;

        $(".image-display").css({
            height: heightOfImageWrapper
        });
    };

    if($(".search-control-area.filter .control-type-container").length > 4){
        $(".more-filter").css({
            display: "inline-block"
        });
    }

    $(".more-filter-trigger").on("click", function(){
        if($(".search-control-area.filter").hasClass("less")){
            $(".search-control-area.filter").removeClass("less").addClass("more");
            $(".control-type-container").animate({
                "opacity": "1"
            });
            $(this).text("Less Filter")
        }else{
            $(".search-control-area.filter").removeClass("more").addClass("less");
            $(".control-type-container:nth-of-type(1n+5)").animate({
                "opacity": "0"
            });
            $(this).text("More Filter")
        }
    });

    $(".open-filter-trigger").on("click", function(){
        $(".main-container, .left-wing-mobile, .navbar").removeClass("open");
        $(".main-container, .filter-wing-mobile, .navbar").toggleClass("open-filter");
        $(".filter-wing-mobile").toggleClass("open");
    });

    $(".ui.accordion").accordion();

    $("form[name='mobile-keyword-search']").submit(function(e){
        var $path = $(this).data("path");
        if($path == "" || typeof $path == 'undefined' || $path == null || $path == false){
            e.preventDefault();
        }
    });

    $(".checkbox[data-mobile-search]").click(function(){

        var $this = $(this);
        var $section = $this.find("[data-mobile-product-search]").attr("data-mobile-product-search");
        var $value = $this.find("input[type='checkbox']").val();

        var $checkbox = $("input[type='checkbox'][data-product-search='" + $section + "'][value='" + $value + "']");
        if($this.hasClass("checked")){
            $checkbox.checkbox("set checked");
            $checkbox.attr("checked", true);
        }
        else{
            $checkbox.checkbox("uncheck");
            $checkbox.removeAttr("checked");
        }
    });

    $(".sort-modal.product-search-sort li").click(function(){
        var $value = $(this).find("a").attr("value");

        $(".product-search-sort.sort-by-filter").dropdown("set selected", $value);
    });

    $(".filter-apply:not(.store .filter-apply)").click(function(){
        applyCategoryFilter()
    });

    $("#listView").on("click", function(){
        $this = $(this);
        if (!$this.hasClass('active')) {
            var adjustList = function(){
                $(".view-table .sort-item").not($this).removeClass("active");
                $this.addClass("active");
                $(".col-item").removeClass("col-md-3 col-xs-6").addClass("col-md-6 col-xs-12");
                $('.row-products').addClass("list").stop().animate({opacity:1},"fast");
            };
            $('.row-products').stop().animate({opacity:0},adjustList);
        }
    });

    $("#gridView").on("click", function(){
        $this = $(this);
        if (!$this.hasClass('active')) {
            var adjustList = function(){
                $(".view-table .sort-item").not($this).removeClass("active");
                $this.addClass("active");
                $(".col-item").removeClass("col-md-6 col-xs-12").addClass("col-md-3 col-xs-6");
                $('.row-products').removeClass("list").stop().animate({opacity:1},"fast");
            };
            $('.row-products').stop().animate({opacity:0},adjustList);
        }
    });
}(jQuery));
