(function ($) {
    var $page = 1;

    $(document).ready(function() {

        window.onscroll = function(ev) {

            if ( (window.innerHeight + window.scrollY) >= document.body.offsetHeight && $("#store-container").hasClass("scrollable") ) {
                loadStores ($page++);
            }

        };

    });

    /**
     * Request & load store data
     *
     * @param $page
     */
    function loadStores ($page)
    {
        $.ajax({
            url: Routing.generate("get_store_webview"),
            type: 'GET',
            data: {
                page : $page
            },
            beforeSend: function() {
                $('.loading-container').removeClass("hidden");
            },
            complete: function() {
                $(".loading-container").addClass("hidden");
            },
            success: function(response) {

                if (response.isSuccessful) {

                    if (response.data !== 0) {
                        var $list = "";

                        $.each(response.data.data, function($index, $store) {
                            var imageHtml = '';
                            var $container = $(".clone-store-div").clone();

                            if ($store.images) {
                                $.each ($store.images, function (key, image) {
                                    var className = key == 0 ? 'col-xs-12' : 'store-product-box';
                                    imageHtml += '<a data-mobile-href="' + image.url + '" href="' + image.url + '" class="' + className + '">' +
                                                     '<img src="'+ image.dir +'">' +
                                                 '</a>';
                                });
                            } else {
                                $.each ($store.productDetails, function (key, image) {
                                    var $apiPath = Routing.generate("api_product_list") + "?productId=" + $productDetails.productId;
                                    var className = key == 0 ? 'col-xs-12' : 'store-product-box';
                                    imageHtml += '<a data-mobile-href="' + $apiPath + '" href="' + $apiPath + '" class="' + className + '">' +
                                                     '<img src="'+ image.dir +'">' +
                                                 '</a>';
                                });
                            }

                            $container.find('.seller-logo').attr('src', $store.logo);
                            $container.find('.seller-name').html($store.storeName)
                                                           .attr('data-sellerid', $store.userId)
                                                           .attr('href', $store.storeSlug)
                                                           .attr('data-mobile-href', $store.storeSlug);
                            $container.find('.store-specialty').html('Specialty: ' + $store.specialty);
                            $container.find('.store-product').html(imageHtml);

                            $list += $container.html();
                        });

                        $("#store-container").append($list);
                    } else {
                        $("#store-container").removeClass("scrollable")
                    }

                }

            }
        });
    }

})(jQuery);
