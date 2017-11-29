(function ($) {
    
    var $page = 2;

    $(document).ready(function() {

        window.onscroll = function(ev) {

            if ( (window.innerHeight + window.scrollY) >= document.body.offsetHeight && $("#store-container").hasClass("scrollable") ) {
                loadProducts ($page++);
            }

        };

    });

    /**
     * Request & load product data
     *
     * @param $page
     */
    function loadProducts ($page)
    {

        $.ajax({
            url: Routing.generate("get_products_webview"),
            type: 'GET',
            data: {
                page : $page,
                node : $('#node').val()
            },
            beforeSend: function() {
                $('.loading-container').removeClass("hidden");
            },
            complete: function() {
                $(".loading-container").addClass("hidden");
            },
            error: function() {
                console.log('hi error ine')
                $("#store-container").removeClass("scrollable");
            },
            success: function(response) {

                if (response.isSuccessful) {
                    
                    if (response.data.data.length > 0) {
                        var $list = "";

                        $.each(response.data.data, function($index, $productDetails) {
                            var $apiPath = Routing.generate("api_product_detail") + "?productId=" + $productDetails.product.id;
                            var $container = $(".clone-store-div").clone();
                            var hasFirstUnit = $productDetails.firstUnit != null && typeof $productDetails.firstUnit == "object";

                            $container.find('.product-link').attr('href', $apiPath)
                                                            .attr('data-mobile-href', $apiPath);
                            $container.find('.product-image').attr('src', $productDetails.product.images[0].fullImageLocation);
                            $container.find('.product-name').html($productDetails.product.title)
                                                            .attr('href', $apiPath)
                                                            .attr('data-mobile-href', $apiPath);
                            
                            $container.find('.price').html('PHP ' + hasFirstUnit ? numberFormat($productDetails.firstUnit.appliedDiscountPrice) : '0.00');
                            $container.find('.discounted-price').html('PHP ' + hasFirstUnit ? numberFormat($productDetails.firstUnit.appliedBaseDiscountPrice) : '0.00');

                            $list += $container.html();
                        });

                        $("#store-container").append($list);
                    }
                    else {
                        $("#store-container").removeClass("scrollable")
                    }

                }

            }
        });
    }

})(jQuery);
