
(function($) {

    var STORE_TYPE_MERCHANT = 0;
    var STORE_TYPE_RESELLER = 1;
    var $ajaxLoading = false;

    //Single selection select box
    $(".single.selection").dropdown();

    //Check and uncheck all checkbox under shipping location table
    $('.check-all-product').change(function(){
        if($(this).is(':checked')){
            $('.product-checkbox:not("[disabled]")').prop("checked", true);
        } else {
            $('.product-checkbox:not("[disabled]")').prop("checked", false);
        }
    });

     $('.product-checkbox').change(function(){
 		if(!$(this).is(':checked')){
            $('.check-all-product').prop("checked", false);
        }
     });

     $(".button-control-table-product").click(function(e){
        var $statusUri = parseInt($("#product-list").data("status"));
        var $status = parseInt($(this).data("status"));

        $checkboxes = $(".product-checkbox:checked");

        $productIds = [];
        $checkboxes.each(function(index, checkbox){
            $productIds.push($(checkbox).val());
        });

        if($productIds.length > 0 && !isNaN($status)){
            var $modal = $(".update-product-modal");
            var $type = $("[data-store-type]").data("store-type");
            var $statusFrom = $(".current-tab.active[data-val]").data("val")
            var $subheader = null;

            $productIds = JSON.stringify($productIds);

            switch($status){
                case 1:
                case 2:
                    $modal.find("i.icon").attr("class", "icon icon-active");
                    $subheader = $modal.find(".header>.content .sub-header");
                    $modal.find(".header>.content").text("Activate selected products?");

                    if($statusFrom == 6){
                        $subheader.text("Activated products will automatically be available on our website.");
                    }
                    else{
                        $subheader.text("Previously deleted products will need to be reviewed before it goes live on our website.");
                    }

                    break;
                case 3:
                    $modal.find("i.icon").attr("class", "icon icon-trash");
                    $subheader = $modal.find(".header>.content .sub-header");
                    $modal.find(".header>.content").text("Delete selected products?");

                    if($type == STORE_TYPE_MERCHANT){
                        $subheader.text("Deleted products will go through another review process with our Product Specialist before you can activate them again.");
                    }
                    else{
                        $subheader.text("Deleted products will go back to Select Product if you wish to activate again.");
                    }
                    break;
                case 4:
                    $modal.find("i.icon").attr("class", "icon icon-trash");
                    $subheader = $modal.find(".header>.content .sub-header");
                    $modal.find(".header>.content").text("Delete selected products?");
                    $subheader.text("This action will permanently delete the selected products.");
                    break;
                case 6:
                    $modal.find("i.icon").attr("class", "icon icon-inactive");
                    $subheader = $modal.find(".header>.content .sub-header");
                    $modal.find(".header>.content").text("Deactivate selected products?");
                    $subheader.text("Deactivated products will no longer be available on our website.");
                    break;
            }

            $modal.find(".header>.content").append($subheader);

            $modal.modal({
                onApprove: function(){
                    updateUserProducts($statusUri, $status, $productIds);
                    return false;
                }
            }).modal("show");
        }
     });

     //Call for tooltip
    $(".tooltip").tipso({
        speed: 200,
        background: '#000000',
        color: '#ffffff',
        position : 'bottom'
    });

    $(document).ready(function(){

        $('.product-daterange').daterangepicker({
            "autoApply": true,
            "opens": "left"
         });

        var $defaultDateRangeValue = "01/01/2015 - " + moment().format('L');
        var filterOnChange = function() {
            var $dropdown = $(this);
            var $name = $dropdown.find("select").attr("name");
            var $dateRangeValue = $("input[name='dateRange']").val();
            var $dateFrom = "";
            var $dateTo = "";
            var $categoryId = 0;
            var $period = 1;
            var $countryCode = "";

            if($name == 'categoryId'){
                $categoryId = $("select[name='categoryId']").val();
                if($dateRangeValue != $defaultDateRangeValue){
                    $dateRangeValue = $dateRangeValue.split(" - ");

                    $dateFrom = moment($dateRangeValue[0], "MM-DD-YYYY").format("YYYY-MM-DD");
                    $dateTo = moment($dateRangeValue[1], "MM-DD-YYYY").endOf('day').format("YYYY-MM-DD HH:mm:ss");
                }
                else{
                    var $value = parseInt($("select[name='byPeriod']").dropdown("get value"));
                    $period = $value;

                    switch($value){
                        case 2:
                            $dateFrom = moment().startOf('week').format("YYYY-MM-DD");
                            $dateTo = moment().endOf('week').endOf('day').format("YYYY-MM-DD HH:mm:ss");
                            break;
                        case 3:
                            $dateFrom = moment().startOf('month').format("YYYY-MM-DD");
                            $dateTo = moment().endOf('month').endOf('day').format("YYYY-MM-DD HH:mm:ss");
                            break;
                        case 4:
                            $dateFrom = moment().startOf('year').format("YYYY-MM-DD");
                            $dateTo = moment().endOf('year').endOf('day').format("YYYY-MM-DD HH:mm:ss");
                            break;
                    }
                }
            }
            else if($name == 'byPeriod'){
                var $value = parseInt($dropdown.dropdown("get value"));
                $period = $value;
                $categoryId = $("select[name='categoryId']").val();

                switch($value){
                    case 2:
                        $dateFrom = moment().startOf('week').format("YYYY-MM-DD");
                        $dateTo = moment().endOf('week').endOf('day').format("YYYY-MM-DD HH:mm:ss");
                        break;
                    case 3:
                        $dateFrom = moment().startOf('month').format("YYYY-MM-DD");
                        $dateTo = moment().endOf('month').endOf('day').format("YYYY-MM-DD HH:mm:ss");
                        break;
                    case 4:
                        $dateFrom = moment().startOf('year').format("YYYY-MM-DD");
                        $dateTo = moment().endOf('year').endOf('day').format("YYYY-MM-DD HH:mm:ss");
                        break;
                }
            }
            else if($name == '_country') {
                $countryCode = $("select[name='_country']").val();
            }

            filterUpdate($categoryId, $dateFrom, $dateTo, $period, $countryCode);
        };

        var autocompleteCountriesUrl = Routing.generate('autocomplete_countries');
        $(".countryfilter").dropdown({
            placeholder: 'All Countries',
            apiSettings: {
                url: autocompleteCountriesUrl+'?q={query}'
            },
            onChange: filterOnChange
        });

        $(".single.selection:not(.countryfilter)").dropdown({
            onChange: filterOnChange
        });

        $(document).on("change", "input[name='dateRange']", function(){
            var $value = $(this).val();

            if($value != $defaultDateRangeValue){
                var $dateFrom = "";
                var $dateTo = "";
                var $categoryId = $("select[name='categoryId']").val();

                $dateRangeValue = $value.split(" - ");

                $dateFrom = moment($dateRangeValue[0], "MM-DD-YYYY").format("YYYY-MM-DD");
                $dateTo = moment($dateRangeValue[1], "MM-DD-YYYY").endOf('day').format("YYYY-MM-DD HH:mm:ss");

                filterUpdate($categoryId, $dateFrom, $dateTo, 1);
            }
        });
    });

    function updateUserProducts($statusUri, $status, $productIds){
        var $button = $(".change-status");
        var $modal = $(".update-success-product-modal");
        var $type = $("[data-store-type]").data("store-type");

        if(!$ajaxLoading){
            $ajaxLoading = true;

            $.ajax({
                url: Routing.generate("merchant_update_product_status"),
                type: 'POST',
                data: {status:$status, productId:$productIds},
                beforeSend: function(){
                    $button.html("<div class='ui active centered small inline inverted loader'>&nbsp;</div>").addClass("disabled");
                },
                success: function(response) {
                    if(response.isSuccessful){
                        $rows = $("table#product-list > tbody").find("input[type='checkbox']:checked").closest("tr");

                        var $totalResultContainer = $("#total-result-count");
                        var $totalResultCount = parseInt($totalResultContainer.text());
                        var message = '',
                            type = 'active';

                        $products = $.parseJSON($productIds);
                        $rows.remove();

                        $totalResultContainer.text($totalResultCount - $products.length);

                        $("table#product-list").find("input[type='checkbox']:checked").prop("checked", false);

                        $productIds = JSON.stringify($productIds);

                        switch($status){
                            case 2:
                                message = "Product has successfully been activated.";
                                type = 'active';

                                break;
                            case 1:
                                message = "Products successfully submitted for review.";
                                type = 'active';

                                break;
                            case 3:
                            case 4:
                                message = "Product has successfully been deleted.";
                                type = 'trash';

                                break;
                            case 6:
                                message = "Product has successfully been deactivated.";
                                type = 'inactive';

                                break;
                        }

                        showDefaultModal({
                            message: message,
                            type: type,
                            reload: true
                        })
                    }
                },
                error: function(response){
                    var $response = response.responseJSON;
                    var $subheader = $modal.find(".header>.content .sub-header");
                    var $message = $response.data.errors ? $response.data.errors.join(", "): $response.message;

                    $modal.find(".header>.content").text("Sold Out");
                    $subheader.text("Quantity cannot be zero for " + $message);

                    $modal.find("i.icon").attr("class", "icon icon-inactive");

                    $modal.find(".header>.content").append($subheader);
                    $(".update-success-product-modal").modal("show");
                },
                complete: function(){
                    $ajaxLoading = false;
                    $button.html("Submit").removeClass('disabled');
                }
            });
        }
    }

    function filterUpdate(categoryId, dateFrom, dateTo, period, countryCode){
        var filterString = "dateFrom=" + dateFrom + "&dateTo=" + dateTo + "&categoryId="+ categoryId + "&period=" + period;

        if (countryCode) {
            filterString += "&_country=" + countryCode;
        }
        /**
         * Reload page with ne 
         */
        var reloadUrl = window.location.origin + window.location.pathname + "?" + filterString;
        window.location.href = reloadUrl;
    }

    //Checkbox item trggers
    $(".select-item-trigger").on("click", function(){
        $(this).parents(".product-item-wrapper").toggleClass("active");
        $(this).parents(".product-item-border").toggleClass("active");
        $(this).parents(".product-item-wrapper").find(".product-checkbox").trigger("click");
        $(this).find(".select").toggleClass("hidden");
        $(this).find(".deselect").toggleClass("hidden");
    });

    $(".select-all-trigger").on("click", function(){
        $(".product-list").find(".product-item-wrapper").addClass("active");
        $(".product-list").find(".product-item-border").addClass("active");
        $(".product-list").find(".check-all-product").trigger("click");
        $(".product-list").find(".select-item-trigger .select").hide();
        $(".product-list").find(".select-item-trigger .deselect").show();
        $(".deselect-all-trigger").show();
        $(this).hide();
    });

    $(".deselect-all-trigger").on("click", function(){
        $(".product-list").find(".product-item-wrapper").removeClass("active");
        $(".product-list").find(".product-item-border").removeClass("active");
        $(".product-list").find(".check-all-product").trigger("click");
        $(".product-list").find(".select-item-trigger .select").show();
        $(".product-list").find(".select-item-trigger .deselect").hide();
        $(".select-all-trigger").show();
        $(this).hide();
    });

    //Existing language removal
    // $(".language .flag-icon.language, .go-back-translation-trigger").on("click", function(){
    //     $(".language-option-modal").modal("show");
    // });

    $(".remove-translation-trigger").on("click", function(){
        $(".remove-translation-dimmer").dimmer("show");
    });

    $(".remove-confirm-trigger").on("click", function(){
        $(".remove-confirm-modal").modal("show");
    });

    $(".remove-translation-dimmer .back").on("click", function(){
        $(".remove-translation-dimmer").dimmer("hide");
    });

    //Add language
    var productSlug;
    $(".language .flag-icon.add-language").on("click", function(){
        var $modal = $(".add-language-modal");
        var $elem = $(this);
        var $productCard = $elem.parents('[data-id]');
        productSlug = $productCard.data('slug');
        $modal.find('img').replaceWith($productCard.find('img').clone());
        var $flagIcons = $productCard.find('[data-product-language]');
        $modal.find('[data-countries]').html($flagIcons.clone());
        $modal.find('[data-title]').html($productCard.find('[data-title]').html());
        $modal.find('[data-description]').html($productCard.find('[data-description]').html());

        $modal.modal("show");
    });

    //Add Country
    var productId;
    $(".country .flag-icon.add-country").on("click", function(){
        var $modal = $(".add-country-modal");
        var $elem = $(this);
        var $productCard = $elem.parents('[data-id]');
        productId = $productCard.data('id');
        $modal.find('img').replaceWith($productCard.find('img').clone());
        var $flagIcons = $productCard.find('[data-product-country]');
        $modal.find('[data-countries]').html($flagIcons.clone());
        $modal.find('[data-title]').html($productCard.find('[data-title]').html());
        $modal.find('[data-description]').html($productCard.find('[data-description]').html());

        $modal.modal({
            onShow: function() {
                var $languages = $productCard.find('[data-product-language]');
                var languageCodes = [];
                $languages.each(function() {
                    languageCodes.push($(this).data('product-language'));
                });

                var $countrySelection = $modal.find('[data-country-selection]');
                var url = $countrySelection.data('autocomplete');
                var i = url.indexOf('?');
                if (i > -1) {
                    url = url.substring(0, i);
                }
                var params = languageCodes.join('&lc[]=');
                url += '?lc[]='+params;
                $countrySelection.data('autocomplete', url);
                $countrySelection.dropdown('search', '');
            }
        });
        $modal.modal("show");
    });

    var $countrySelection = $('[data-country-selection]');
    $('[data-add-country]').on('click', function() {
        var countryCode = $countrySelection.val();
        if (countryCode) {
            var url = Routing.generate('dashboard_country_setup', {countryCode: countryCode, productId: productId});
            window.location = url;
        }
        else {
            $countrySelection.trigger('floating.error', 'Please select a country');
        }
    });

    var $languageSelection = $('[data-language-selection]');
    $('[data-add-language]').on('click', function() {
        var languageCode = $languageSelection.val();
        if (languageCode) {
            var url = Routing.generate('product_translation', {languageCode: languageCode, slug: productSlug});
            window.location = url;
        }
        else {
            $languageSelection.trigger('floating.error', 'Please select a language');
        }
    });

    $(document).on("ready load resize", function(){
        $('#row-list').masonry({
            itemSelector: '.col-xl-3.col-xs-4',
            columnWidth: '.col-xl-3.col-xs-4',
            isResizeBound: true
        });
    });

})(jQuery);
