(function($) {
    var MAX_UPLOAD = 15;

    $(document.body).on('change', '.product-category-selection', function(){
        var $this = $(this);
        var $scrollableContainer = $this.closest('.select-product-modal')
                                        .find('.reseller-product-container');
        $scrollableContainer.attr('data-page', 0);
        $scrollableContainer.attr('data-iscomplete', 'false');
        var promise = retrieveManufacturerProducts($scrollableContainer);
        promise.done(function($listContent){
            if(!$listContent){
                return false;
            }
            $("#reseller-all-products").empty();
            $("#reseller-all-products").append($listContent);
        });
    });

    var $scrollableContainer = $('.reseller-product-container');
    $scrollableContainer.bind('scroll', function(){
        getMoreProducts($(this));
    });
    var promise = retrieveManufacturerProducts($scrollableContainer);
    promise.done(function($listContent){
        if(!$listContent){
            return false;
        }
        $("#reseller-all-products").empty();
        $("#reseller-all-products").append($listContent);
        $('.selected-product-search').val('');
        $('.reseller-search').val('');
        $('#selected-products-container').empty();
        $('.select-product-modal .product-selection-prompt').hide();
        
        var $submitProductSelection = $('.submit-product-selection');
        $submitProductSelection.on('click', function() {
            var manufacturerProductIds = [];
            var csrfToken = $("meta[name=csrf-token]").attr("content");
            $('#selected-products-container .product-list-item').each(function(){
                manufacturerProductIds.push($(this).attr('data-manufacturerproductid'));
            });

            $.ajax({
                type: "POST",
                url: Routing.generate('reseller_upload_products'),
                data: {
                    '_token': csrfToken,
                    'productIds': manufacturerProductIds
                },
                beforeSend : function(){
                    $submitProductSelection.addClass('disabled');
                },
                success : function(data){
                    if(data.isSuccessful){
                        $('.success-reseller-modal').modal('show');
                    }
                    else{
                        var $errorPrompt = $('.select-product-modal .product-selection-prompt');
                        $errorPrompt.find('.message-box').html(data.message);
                        $errorPrompt.fadeIn();
                    }
                },
                complete : function(data){
                    $submitProductSelection.removeClass('disabled');
                    $scrollableContainer.attr('data-page', 0);
                    $scrollableContainer.attr('data-iscomplete', 'false');

                    var modalSettings = {
                        message: 'Successfully added products to your list'
                    };
                    if (!data.responseJSON.isSuccessful) {
                        modalSettings.message = data.responseJSON.message;
                        modalSettings.callbacks = {
                            onHide: function() {}
                        };
                    }

                    showDefaultModal(modalSettings);
                }
            });
        });
    });

    $(document).on('click', '.add-reseller-product', function(){
        var $errorPrompt = $('.select-product-modal .product-selection-prompt');
        $errorPrompt.find('.message-box').html('Product has already been selected.');

        if ($('#selected-products-container li').length >= MAX_UPLOAD) {
            $errorPrompt.find('.message-box').html('You have reached the number of products per upload.');
            $errorPrompt.fadeIn();
            setTimeout(function(){
                $errorPrompt.fadeOut();
            }, 1500);
            return;
        }

        $productCard = $(this).closest('.product-list-item');       
        var productId = $productCard.attr('data-manufacturerproductid');

        var $selectedProductContainer = $('#selected-products-container');
        $productCard.fadeOut(function(){
            if($selectedProductContainer.find('.product-list-item[data-manufacturerproductid="'+productId+'"]').length > 0){
                var $errorPrompt = $('.select-product-modal .product-selection-prompt');
                $errorPrompt.find('.message-box').html('Product has already been selected.');
                $errorPrompt.fadeIn();
                setTimeout(function(){
                    $errorPrompt.fadeOut();
                }, 1500);
                return false;
            }
            var $footer = $productCard.find('.custom-product-footer');
            $footer.find('.add-reseller-product').hide();
            $footer.find('.remove-reseller-product').show();
            $selectedProductContainer.prepend($productCard.fadeIn());

            if ($("#reseller-all-products li").length == 0) {
                var $this = $('.reseller-search');
                var $scrollableContainer = $this.closest('.product-selection-container')
                    .find('.reseller-product-container');
                $scrollableContainer.attr('data-page', 0);
                $scrollableContainer.attr('data-iscomplete', 'false');
                var promise = retrieveManufacturerProducts($scrollableContainer);
                promise.done(function($listContent){
                    if(!$listContent){
                        return false;
                    }
                    $("#reseller-all-products").empty();
                    $("#reseller-all-products").append($listContent);
                });
            }

        });

    });

    $(document).on('click', '.remove-reseller-product', function(){

        $productCard = $(this).closest('.product-list-item');       
        var productId = $productCard.attr('data-manufacturerproductid');
        var $allProductContainer = $('#reseller-all-products');
        $productCard.fadeOut(function(){
            if($allProductContainer.find('.product-list-item[data-manufacturerproductid="'+productId+'"]').length > 0){
                var $errorPrompt = $('.select-product-modal .product-selection-prompt');
                $errorPrompt.find('.message-box').html('Product has already been selected.');
                $errorPrompt.fadeIn();
                setTimeout(function(){
                    $errorPrompt.fadeOut();
                }, 1500);
                return false;
            }

            console.log($productCard);
            var $footer = $productCard.find('.custom-product-footer');

            $footer.find('.add-reseller-product').show();
            $footer.find('.remove-reseller-product').hide();

            $allProductContainer.prepend($productCard);

            console.log($productCard);
            $(".product-list-item[data-manufacturerproductid='" + productId + "']").fadeIn();
        });
    });

    $(document.body).on('keypress', '.reseller-search', function(event){
        if ( event.which == 13 ) {
            event.preventDefault();
            var $this = $(this);
            var $scrollableContainer = $this.closest('.product-selection-container')
                .find('.reseller-product-container');
            $scrollableContainer.attr('data-page', 0);
            $scrollableContainer.attr('data-iscomplete', 'false');
            var promise = retrieveManufacturerProducts($scrollableContainer);
            promise.done(function($listContent){
                if(!$listContent){
                    return false;
                }
                $("#reseller-all-products").empty();
                $("#reseller-all-products").append($listContent);
            });
        }
    });

    $(document.body).on('keypress', '.selected-product-search', function(event){
        if ( event.which == 13 ) {
            event.preventDefault();
            var $this = $(this);
            var stringFilter = $.trim($this.val());
            if(stringFilter === ""){
                $("#selected-products-container").find('.product-list-item').show();
            }
            else{
                $("#selected-products-container").find('.product-list-item')
                                                 .not('[data-productname*="'+stringFilter+'"]')
                                                 .hide();
            }
        }
    });

    function getMoreProducts($container)
    {
        container = $container[0];
        if(container.scrollTop + container.clientHeight >= container.scrollHeight){
            var isComplete = $container.attr('data-iscomplete');
            if($.parseJSON(isComplete)){
                return false;
            }

            var promise = retrieveManufacturerProducts($container);
            promise.done(function($listContent){
                if(!$listContent){
                    return false;
                }
                $("#reseller-all-products").append($listContent);
            });
        }
    }

    function retrieveManufacturerProducts($container)
    {        
        $modal = $container.closest('.select-product-modal');
        $modal.yiLoader();

        var deferredObject = $.Deferred();
        var isComplete = JSON.parse($container.attr('data-iscomplete'));
        var isProcessing = JSON.parse($container.attr('data-isprocessing'));
        var page = parseInt($container.attr('data-page'), 10) + 1;
        var categoryIdFilter = parseInt($('.product-category-selection :selected').val(), 10);

        var $searchForm = $container.siblings('.reseller-search-form');
        var searchString = $searchForm.find('.reseller-search').val().trim();
        var manufacturerProductIds = [];

        if(isProcessing || isComplete ){
            deferredObject.resolve(false);
        }

        $('#selected-products-container .product-list-item').each(function(){
            manufacturerProductIds.push($(this).attr('data-manufacturerproductid'));
        });

        var data = {'page': page};
        if(searchString !== ''){
            data['query'] = searchString;
        }
        if(categoryIdFilter !== 0 && !isNaN(categoryIdFilter)){
            data['categoryId'] = categoryIdFilter;
        }

        if (manufacturerProductIds.length > 0) {
            data['manufacturerProductIds'] = manufacturerProductIds;
        }

        $.ajax({
            type: "GET",
            dataType: "json",
            url: Routing.generate('reseller_upload_product_list'),
            data: data,
            beforeSend : function(){
                $container.attr('data-isprocessing', 'true');
                $modal.trigger('loader.start');
            },
            success: function(data){
                $modal.trigger('loader.stop');
                var manufacturerProducts = data.data;
                var $listContents = [];
                $container.attr('data-isprocessing', 'false');
                if(data.data.length === 0){
                    $container.attr('data-iscomplete', 'true');
                }
                else{
                    $container.attr('data-page', page);
                    var $baseProductCard = $('#product-base-card');
                    $.each(manufacturerProducts, function(key, manufacturerProduct){
                        var commision = manufacturerProduct.defaultUnit.commission == null ? 0 : manufacturerProduct.defaultUnit.commission;
                        $cloneProductCard = $baseProductCard.clone();
                        var attributeNames = [];
                        $.each(manufacturerProduct.attributes, function(attributeKey, attribute){

                            var $groupName = attribute.groupName;
                            var $items = [];

                            $.each(attribute.items, function(key, value){
                                $items.push(value.name);
                            });

                            attributeNames.push(attribute.groupName + "(" + $items.join() + ")");
                        });

                        $cloneProductCard.find('a.product-view-button')
                                         .attr('href', Routing.generate('reseller_view_product', {
                                             inhouseProductId : manufacturerProduct.id
                                          }));
                        $cloneProductCard.find('.product-list-item')
                            .attr('data-manufacturerproductid', manufacturerProduct.id)
                            .attr('data-productname', manufacturerProduct.productName)
                        $cloneProductCard.find('.product-title') 
                            .html(manufacturerProduct.productName);
                        $cloneProductCard.find('.product-image')
                            .attr('src', manufacturerProduct.image);
                        $cloneProductCard.find('.product-description')
                            .html(manufacturerProduct.shortDescription);
                        $cloneProductCard.find('.product-price')
                            .html('P ' + numberFormat(manufacturerProduct.defaultUnit.retailPrice));
                        $cloneProductCard.find('.product-comission')
                            .html('P ' + numberFormat(commision));
                        if(attributeNames.length > 0){
                            $cloneProductCard.find('.detail-name').css('display', 'inline-block')
                            $cloneProductCard.find('.product-detail')
                                .html(attributeNames.join());
                        }
                        
                        $cloneProductCard.css('display', 'block');
                        $cloneProductCard.removeAttr('id');
                        $listContents.push($cloneProductCard);
                    });
                }
                deferredObject.resolve($listContents);
            }
        });
        return deferredObject.promise();
    }


})(jQuery);
