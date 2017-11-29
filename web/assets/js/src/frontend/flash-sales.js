$(document).ready(function() {

    var $promoInstanceId;
    var $tabs = [];

    window.onscroll = function(ev) {
        if (
            (window.innerHeight + window.scrollY) >= document.body.offsetHeight &&
            $(".products-container#tab-content"+$promoInstanceId).hasClass("scrollable")
        ) {
            var $page = $tabs[$promoInstanceId];
            loadPromoInstanceProducts($promoInstanceId, $page);
        }
    };


    $(".promo-tab-head").click(function(){
        $promoInstanceId = $(this).data("id");
        
        if(
            $tabs[$promoInstanceId] == 1 && 
            $(".products-container#tab-content"+$promoInstanceId).hasClass("scrollable")
        ){
            loadPromoInstanceProducts($promoInstanceId, 1);
        }
    });

    var hasActive = $(".products-container.currently-active-promo").length > 0;
    if(!hasActive){
        $(".products-container").first().addClass('currently-active-promo');
    }

    $(".products-container").each(function($index, $container){
        var $id = $($container).data("promo-instance");
        if($($container).hasClass('currently-active-promo')){            
            $promoInstanceId = $id;
            var promoDataPromise = loadPromoInstanceProducts($id, 1);
            $tabs[$id] = 2;
            promoDataPromise.done(function(){
                $('.promo-tab-head[data-id='+$id+']').click();
            });
        }
        else{
            $tabs[$id] = 1;
        }
    });

    $('#time-section li a:not(:first)').addClass('inactive');
    $('.products-container').hide();
    $('.products-container:first').show();
        
    $('#time-section li a').click(function(){
        var t = $(this).attr('rel');
        if($(this).hasClass('inactive')){
            $('#time-section li a').addClass('inactive');
            $(this).removeClass('inactive');
            $('.products-container').hide();
            $('#'+ t).fadeIn('slow');
        }
    });

    $(window).on('load resize', function () {
        var WindowWidth = $(window).innerWidth();
        if (WindowWidth <= 1921) {
            var totalwidth = $('#time-section li').length * 192;
            $('#time-section').css({'min-width': totalwidth+'px'});
        }
        if (WindowWidth <= 767) {
            var totalwidth = $('#time-section li').length * 192;
            $('#time-section').css({'min-width': totalwidth+'px'});
        }
        if (WindowWidth <= 414) {
            var totalwidth = $('#time-section li').length * 104;
            $('#time-section').css({'min-width': totalwidth+'px'});
        }
        if (WindowWidth <= 375) {
            var totalwidth = $('#time-section li').length * 94;
            $('#time-section').css({'min-width': totalwidth+'px'});
        }
        if (WindowWidth <= 320) {
            var totalwidth = $('#time-section li').length * 80;
            $('#time-section').css({'min-width': totalwidth+'px'});
        }
    });

    function loadPromoInstanceProducts($id, $page){
        var deferredObject = $.Deferred();

        $.ajax({
            url: Routing.generate("get_flash_sale_products"),
            type: 'POST',
            data: {
                promoInstanceId : $id,
                page : $page
            },
            beforeSend: function(){
                $("div.products-container[data-promo-instance='" + $id + "']")
                .find(".loading-container").removeClass("hidden");
            },
            success: function(response) {
                if(response.isSuccessful){
                    if(response.data.products.length > 0){
                        $list = "";
                        $.each(response.data.products, function($index, $productDetails){
                            var $container = $(".product-container-clone").clone();
                            var $webPath = Routing.generate("product_details")+"/"+$productDetails.slug;
                            var $mobilePath = Routing.generate("api_product_detail")+"?productId="+$productDetails.productId;

                            var $discountElement = $container.find("span.discount-tag");

                            $discountElement.text($productDetails.discount + $discountElement.data("concat"));
                            
                            $container.find("img.product-image").attr("src", $productDetails.medium);
                            $container.find("a.product-name").text($productDetails.name);
                            $container.find("p.product-short-description").text($productDetails.shortDescription);
                            $container.find("span.product-price").text('P ' + $productDetails.discountedPrice);
                            $container.find("span.original-price").text('P ' + $productDetails.price);
                            $container.find("span.product-sold").prepend($productDetails.productsSold+"/"+$productDetails.maxQuantity);
                            $container.find(".promo-instance-product-status").attr("data-product-id", $productDetails.productId);
                            $container.find("span.promo-instance-product-percentage").css({
                                width : $productDetails.promoProductState+"%"
                            });

                            if($productDetails.promoProductState == 100 || $productDetails.isActive == false){
                                $container.find(".promo-instance-product-status").addClass("btn-inactive");
                                
                                $container.find("a.product-image").removeAttr("href");
                                $container.find("a.product-name").removeAttr("href");
                                $container.find(".promo-instance-product-status").removeAttr("href");
                                $container.find("a.product-image").removeAttr("href");
                                $container.find("a.product-name").removeAttr("href");
                                $container.find(".promo-instance-product-status").removeAttr("href");
                            }
                            else{
                                $container.find("a.product-image").attr("href", $mobilePath);
                                $container.find("a.product-name").attr("href", $mobilePath);
                                $container.find(".promo-instance-product-status").attr("href", $mobilePath);
                                $container.find("a.product-image").attr("data-mobile-href", $mobilePath);
                                $container.find("a.product-name").attr("data-mobile-href", $mobilePath);
                                $container.find(".promo-instance-product-status").attr("data-mobile-href", $mobilePath);
                            }

                            $list += $container.html();
                        });

                        $tabs[$id]++; 
                        $(".products-container#tab-content"+$id+" .main-product-container").append($list);
                    }
                    else{
                        $(".products-container#tab-content"+$promoInstanceId).removeClass("scrollable")
                    }
                }
            },
            complete: function(){
                $("div.products-container[data-promo-instance='" + $promoInstanceId + "']")
                .find(".loading-container").addClass("hidden");
                deferredObject.resolve();
            }
        });
        
        return deferredObject.promise();
    }
});
