(function($){
    'use strict';

    var $ajaxExecuting = false;

    var getProductSearchData = function(data) {
        data = data ? data: {};
        $('[data-product-search]').each(function() {
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

    function searchProducts(data){

        var $searchData = getProductSearchData(data);

        if(!$ajaxExecuting){

            var $url = window.location.href.split("?")[0];
            $.ajax({
                url: $url,
                type: 'POST',
                data: $searchData,
                beforeSend: function(){
                    $ajaxExecuting = true;
                    $("[data-yi-loader]").trigger("loader.start");
                },
                success: function(response) {
                    $(".search-body-wrapper").replaceWith(response);
                },
                complete: function(){
                    $ajaxExecuting = false;
                    $("[data-yi-loader]").trigger("loader.stop");
                }
            });
        }
    }

    $(document).ready(function(){

        $(".checkbox:not([data-mobile-search]), .list-search-category li").click(function(e){

            var $this = $(this);
            var $parent = $this.parent();
            var $section = $this.find("[data-product-search]").attr("data-product-search");
            var $value = $this.find("input[type='checkbox']").val();
            var $checkbox = $("input[type='checkbox'][data-mobile-product-search='" + $section + "'][value='" + $value + "']");

            e.preventDefault();

            if($parent.hasClass("list-search-category")){
                $(".list-search-category li a").removeClass("active");
                $this.children("a").addClass("active");
            }

            if($this.hasClass("checked")){
                $checkbox.checkbox("set checked");
                $checkbox.attr("checked", true);
            }
            else{
                $checkbox.checkbox("uncheck");
                $checkbox.removeAttr("checked");
            }

            searchProducts();
        });

        $(".sort-modal.store-search-sort li").click(function(){
            var $option = $(this).find("a").attr("data-option");
            var $value = $(this).find("a").attr("value");

            $(".sort-modal").removeClass("visible active").addClass("hidden");
            $("[data-product-search='sorting']").dropdown("set selected", $value);
        });

        $(".store .filter-apply").click(function(){
            searchProducts();

            $(".filter-modal").removeClass("visible active").addClass("hidden");
        });

        $("[data-product-search]:not([data-product-search='priceRange'])").change(function(){
            searchProducts();
        });

        $("input[name='q']:not(.search-mobile-field)").on("keyup", function(e){
            if(e.keyCode == 13){
                searchProducts();
            }
        });

        $(".search-mobile-field[name='q']").on("keyup", function(e){
            e.preventDefault();
            if(e.keyCode == 13){
                searchProducts();
            }
            else{
                $("input[name='q']:not(.search-mobile-field)").val($(this).val());
            }
        });

        $(".control-dropdown.price-slider-trigger").click(function(){
            if(!$(this).hasClass("active")){
                searchProducts();
            }
        });

        $(document).on("click", ".search-pagination-container li", function(e){
            
            var $page = $(this).children("a").attr("href").replace("?page=", "");

            e.preventDefault();
            searchProducts({page : $page});
        });
    });

})(jQuery);