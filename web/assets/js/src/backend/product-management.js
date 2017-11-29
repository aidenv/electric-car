(function ($) {
   
    var hasChanges = false;
    var $productListingModal = $('.modal-product-listing');
    
    $(document).ready(function() {
        $('.error').find('.with-close-message').html('Something went wrong. Please try again later');
        $productListingModal.yiLoader();
    });

    $('.product-filters').on('submit', function(evt){
        var $form = $(this);
        var submittedQueryString = $form.find('.query-string').val();
        var previousQueryString = getUrlParameter('q');
        if(typeof previousQueryString == "undefined" && submittedQueryString.length > 0){
            var relevanceOptionValue = $form.find('.sort-by-relevance').val();
            $form.find('.search-sort').dropdown('set selected', relevanceOptionValue);
        }
    });

    //Single selection select box
    $(".single.selection").dropdown();

    $(document).on('click', '.product-description-btn', function(){
        var $this = $(this);
        var $descriptionModal = $('.modal-product-description');
        $descriptionModal.find('.content').html($this.data('content'));
        console.log($this.data('content'));
        console.log($descriptionModal);
        $descriptionModal.modal('show');
    });
    
    $(document).on('click', '.modal-product-listing-trigger', function() {
        $modalTrigger = $(this);
        var productId = $modalTrigger.data('productid');
        var countryCode = $modalTrigger.data('countrycode');
        
        $.ajax({
            url: Routing.generate("yilinker_backend_product_detail", {
                productId: productId,
                countryCode: countryCode
            }),
            type: 'GET',
            beforeSend: function(){
                $productListingModal.trigger('loader.start');
            },
            complete: function(){
                $productListingModal.trigger('loader.stop');
            },
            success: function(response) {
                if (response.isSuccessful) {

                    var $productModal = $('.modal-product-listing');
                    var data = response.data;
                    var template = twig({
                        href: "/assets/js/twig/backend/product_listings_modal.html.twig?v="+Date.now(),
                        load: function(template) {
                            $productModal.html(template.render(data));
                            
                            initiliazeImageViewer($productModal);
                            initializeShippingTypeahead();
                            $('.modal .tabular.menu .item').tab();                            
                            openProductModal();
                        }
                    });
                    
                    return false;
                }              
            }
        });
    });

    function openProductModal()
    {
        var $productModal = $('.modal-product-listing');
        var youtubeUrl = $modalTrigger.attr('data-youtube-url');
        var $youtubeMessage = $('#youtube-message');
        var $youtubeFrame = $('#youtube-frame');
        var $errorContainer = $('.product-update-prompt');
        
        $productModal.modal({
            closable: false,
            onShow : function() {
                $('[data-tab="modal-tab-details"]').click();
                $errorContainer.hide();
                $youtubeMessage.show();
                $youtubeFrame.hide();
                if (youtubeUrl != '') {
                    $youtubeMessage.hide();
                    var youtubeId = getYoutubeIdByUrl(youtubeUrl.trim());
                    var youtubeVideoUrl = 'https://www.youtube.com/embed/' + youtubeId;
                    $youtubeFrame.show().attr('src', youtubeVideoUrl);
                }
                $productModal.modal('refresh');

                $shippingErrorMessage =  $('.shipping-category-message-container > .error-message');
                $shippingSuccessMessage = $('.shipping-category-message-container > .success-message');
            },
            onApprove : function($element){
                var $button = $element;
                var action = $button.data('action');
                var productId = $button.data('productid');
                var csrfToken = $("meta[name=csrf-token]").attr("content");

                $.ajax({
                    type: "POST",
                    url: Routing.generate('yilinker_backend_product_update_status', {
                        countryCode: $button.data('country')
                    }),
                    data: {
                        'productId' : productId,
                        'action' : action,
                        '_token' : csrfToken,
                    },
                    beforeSend: function(){
                        $button.addClass('disabled');
                    },
                    success: function(data){
                        if(data.isSuccessful){
                            $('.success-product-update-modal').modal({
                                onHidden : function() {
                                    location.reload();
                                }
                            }).modal('show');
                        }
                        else{
                            $errorContainer.find('.message-box').html(data.message);
                            $errorContainer.show();
                        }
                    },
                    complete : function(){
                        $button.removeClass('disabled');
                    }
                });

                return false;
            },
            onDeny: function () {
                if (hasChanges == true) {
                    location.reload();
                }
            }
        }).modal('show');

    }

    var $modalProductFullDescription = $('[data-modal-product-full-description]');
    $('[data-feeder]').on('click', function() {
        var $elem = $(this);
        var $productFullDescription = $elem.find('[data-product-full-description]');
        if ($productFullDescription.length) {
            $modalProductFullDescription.html($productFullDescription.html());
        }
    });
    
    $(document).on('click', '.btn-submit-remarks', function () {
        $('.product-update-prompt').hide();
        var $this = $(this);
        var productId = $this.data('productid');
        var remarks = $('[text-area-message]').val();

        if (remarks == '') {
            $('.error').show().find('.with-close-message').html('Remarks should not be empty!').show();
            return;
        }

        $.ajax({
            url        : Routing.generate('yilinker_backend_send_remarks', {
                             countryCode: $this.data('country')
                         }),
            dataType   : 'json',
            type       : 'POST',
            data       : {productId: productId, remarks: remarks},
            beforeSend : function ()
            {
                $this.html('Loading..');
                $this.attr('disabled', true);
            },
            success    : function (response)
            {
                $this.html('Send Reject Remark');
                $this.attr('disabled', false);

                if (response.isSuccessful == true) {
                    hasChanges = true;
                    $('.product-update-button').hide();
                    $('.product-approve-button').show();
                    $('[text-area-message]').val('');
                    var html = '' +
                        '<div class="csr">' +
                            '<p class="person">' +
                                '<span class="user">' +
                                    '<strong>' + response.data.adminUserFullName + '</strong> Customer Support Representative' +
                                '</span>' +
                            '</p>' +
                            '<p class="words">' + response.data.remarks + '</p>' +
                            '<p class="time-stamp">Posted on ' + response.data.formattedDateAdded + '</p>' +
                        '</div>';
                    $('#product-remarks-container').prepend(html);
                }
                else {
                    $('.product-update-prompt').show();
                }

            }
        })
    });

    $(document).on('click', '.reject-button', function () {
        $('[data-tab="modal-tab-remarks"]').click();
        $('.remarks-field').show();
    });

    // search shipping category
    var $shippingCategories = [];
    var $shippingErrorMessage = null;
    var $shippingSuccessMessage = null;
    
    function initializeShippingTypeahead()
    {
        $('.input-shipping-category').typeahead({
            hint: true,
            highlight: true,
            minLength: 1,
            autoselect: true
        },{
            displayKey: 'name',
            source: function (query, process) {            
                var $loader = $("#shipping-category-form").find('img.loading-img');
                $shippingErrorMessage.hide();
                $shippingSuccessMessage.hide();
                return $.ajax({
                    url: Routing.generate('yilinker_core_shipping_category_search'),
                    data: {query: query},
                    method: 'GET',
                    dataType: 'json',
                    beforeSend: function () {
                        $loader.show();
                    },
                    success: function (response) {
                        $loader.hide();
                        $.each(response.data, function (i, value) {
                            $shippingCategories.push(value.name);
                        });
                        
                        return process(response.data);
                    }
                });
            }
        }).on("typeahead:selected typeahead:autocompleted typeahead:change", function(e, data) {
            $('.shipping-category-id').val(data.shippingCategoryId);
        }).blur(function(){
            if ($shippingCategories.indexOf($(this).val()) < 0) {
                $('.shipping-category-id').val('');
            }
        });
    }
       

    $(document).on('click', 'button.shipping-category-update-button', function() {
        var $self = $(this),
            $productId = $self.data('productid'),
            $loader = $("#shipping-category-form").find('img.loading-img');

        $shippingErrorMessage.hide();
        $shippingSuccessMessage.hide();

        $.ajax({
            url: Routing.generate('yilinker_backend_product_update_shipping_category'),
            data: {product: $productId, shippingCategory: $('.shipping-category-id').val()},
            method: 'post',
            dataType: 'json',
            beforeSend: function () {
                $loader.show();
                $self.prop('disabled', true);
            },
            success: function (response) {
                $loader.hide();
                $self.prop('disabled', false);
                if (response.isSuccessful) {
                    $shippingErrorMessage.hide();
                    $shippingSuccessMessage.show().delay(3000).fadeOut();
                }
                else {
                    $shippingErrorMessage.html(response.message)
                                         .show().delay(3000).fadeOut();
                }
            }
        });
    });

    // search seller
    $('.input-search-store').typeahead({
        hint: true,
        highlight: true,
        minLength: 1,
        autoselect: true,
    },
    {
        displayKey: 'storeName',
        source: function (query, process) {
            var $loader = $("#store-search").find('img.loading-img');
            return $.ajax({
                url: Routing.generate('yilinker_core_seller_store_search'),
                data: {query: query},
                method: 'get',
                dataType: 'json',
                beforeSend: function () {
                    $loader.show();
                },
                success: function (response) {
                    $loader.hide();
                    return process(response.data);
                }
            });
        }
    });

    $(document).on('click', '.user-detail-menu .item', function () {
        var $this = $(this);
        var tab = $this.attr('data-tab');
        var $rejectButton = $('.reject-button');

        if ($('#product-status').val() == $('#under-review-status').val()) {

            if (tab == 'modal-tab-remarks') {
                $rejectButton.hide();
            }
            else {
                $rejectButton.show();
            }

        }
    });

    /**
     * Get Youtube Id by url
     *
     * @param string url
     * @return string/int
     */
    function getYoutubeIdByUrl (url)
    {
        var regExp = /^.*(youtu.be\/|v\/|u\/\w\/|embed\/|watch\?v=|\&v=)([^#\&\?]*).*/;
        var match = url.match(regExp);

        if (match && match[2].length == 11) {
            return match[2];
        } else {
            return false;
        }

    }

    /**
     * Initializes the image viewer
     */
    function initiliazeImageViewer($modal)
    {
        $modal.find('.listing-image-gallery.default, .listing-image-gallery.translated').magnificPopup({
            delegate: 'a',
            type: 'image',
            tLoading: 'Loading image #%curr%...',
            mainClass: 'mfp-img-mobile',
            gallery: {
                enabled: true,
                navigateByImgClick: true,
                preload: [0,1] // Will preload 0 - before current, and 1 after the current image
            },
            image: {
                tError: '<a href="%url%">The image #%curr%</a> could not be loaded.'
            }
        });
    }




})(jQuery);
