(function ($) {

    var $dateRangeValues = [];
    var $ajaxExecuting = false;
    var $promoModal = $(".modal-promo");
    var $addPromoProductErrorContainer = $(".modal-promo-add .error-container");
    var $promoErrorContainer = $(".modal-promo .error-container");
    var $promoListing = $(".promo-listing-count strong");
    var $promoForm = $("form[name='promo']");
    var $hasInstance = false;
    var $currentPromo = null;
    var $action = null;
    var $timerInterval = null;

    var FIXED       = 1,
        BULK        = 2,
        PER_HOUR    = 3,
        FLASH_SALE  = 4;

    //Check and uncheck all checkbox under shipping location table
    $('.check-all-promo').change(function(){
        if($(this).is(':checked')){
            $('.promo-checkbox:not("[disabled]")').prop("checked", true);
        } else {
            $('.promo-checkbox:not("[disabled]")').prop("checked", false);
        }
    });

     $('.promo-checkbox').change(function(){
        if(!$(this).is(':checked')){
            $('.check-all-promo').prop("checked", false);
        }
     });

     $("form[name='promo'] input[type='checkbox']").change(function(){
        if($(this).is(':checked')){
            $(this).attr("checked", true);
        } else {
            $(this).attr("checked", false);
        }
     });

    $(document).ready(function(){
        var $promoType = $(".promo-type-input");
        var $addPromoProductsForm = $("form[name='addPromoProducts']");

        var $promoFormRules =  {
            fields: {
                title: {
                    identifier  : 'title',
                    rules: [
                        {
                            type   : 'empty',
                            prompt : 'Promo name field is required'
                        },
                        {
                            type   : 'maxLength[255]',
                            prompt : 'Please enter at most 255 characters'
                        }
                    ]
                },
                promoType: {
                    identifier  : 'promoType',
                    rules: [
                        {
                            type   : 'empty',
                            prompt : 'Promo field is required'
                        }
                    ]
                },
                dateStart: {
                    identifier  : 'dateStart',
                    rules: [
                        {
                            type   : 'empty',
                            prompt : 'Start date field is required'
                        }
                    ]
                },
                dateEnd: {
                    identifier  : 'dateEnd',
                    rules: [
                        {
                            type   : 'empty',
                            prompt : 'End date field is required'
                        }
                    ]
                }
            },
            onSuccess: function(){
                $(".quantity-required, .minimum-percentage, .percent-per-hour").removeClass("error");
                var $errors = secondLevelValidate();
                var $errorList = "<ul class='list'>";

                if(parseInt($errors.length) > 0){
                    $promoForm.removeClass("hidden success").addClass("error");

                    $errors.forEach(function($error){
                        $errorList += "<li>" +  $error + "</li>";
                    });

                    $errorList += "</ul>";

                    $promoErrorContainer.html($errorList);
                }
                else{
                    if(!$ajaxExecuting){
                        switch($action){
                            case "add":
                                createPromoInstance();
                                break;
                            case "edit":
                                updatePromoInstance();
                                break;
                        }

                        $ajaxExecuting = false;
                    }
                }

                return false;
            },
            onFailure: function(){
                $promoErrorContainer.removeClass("hidden");
                return false;
            }
        };

        var $addPromoProductsFormRules =  {
            fields: {
                productSlugs: {
                    identifier  : 'productSlugs',
                    rules: [
                        {
                            type   : 'empty',
                            prompt : 'This field is required'
                        }
                    ]
                }
            },
            onSuccess: function(){
                var $productSlugs = $addPromoProductsForm.find("textarea[name='productSlugs']").val();
                var $promoType = $promoForm.find("select[name='promoType']").val();

                if(!$ajaxExecuting){
                    $addPromoProductErrorContainer.html("").addClass("hidden");
                    $ajaxExecuting = true;
                    $.ajax({
                        url: Routing.generate('yilinker_backend_check_promo_products'),
                        type: 'POST',
                        data: {productSlugs:$productSlugs},
                        success: function(response) {
                            var $tbody = $promoModal.find("tbody.promoProducts");
                            var $products = response.data.products;
                            var $list = "";

                            var $header = $promoModal.find("thead");

                            createDefaultHeader();

                            $products.forEach(function(product){
                                if($tbody.find("[data-unit-id='" + product.productUnitId + "']").length < 1){
                                    $list += generatePromoProductLi(product, $promoType);
                                }
                            });

                            $tbody.append($list);

                            renderPromoProductTables(parseInt($promoType), false);

                            $promoModal.modal('show').modal({ blurring: true });
                            $addPromoProductsForm.find("textarea[name='productSlugs']").val("");
                            $ajaxExecuting = false;
                        },
                        error: function(response){
                            var responseJson = response.responseJSON;
                            var $warnings = responseJson.data.products.join(", ");

                            $addPromoProductErrorContainer.html($warnings + " does not exists.").removeClass("hidden");
                            $ajaxExecuting = false;
                        },
                        complete: function(){
                            $('.modal-promo-add').modal("hide");
                        }
                    });

                }

                return false;
            }
        };

        $promoForm.form($promoFormRules);
        $addPromoProductsForm.form($addPromoProductsFormRules);

        $('.datetimepicker').datetimepicker({
            format: "MM-DD-YYYY HH:mm:ss"
        });

        $("input[name='searchInput'], input[name='dateFrom'], input[name='dateTo']").keypress(function(e){
            if(e.which == 13) {

                var $dateFrom = $("input[name='dateFrom']").val();
                var $dateTo = $("input[name='dateTo']").val();
                var $keyword = $(this).val();

                filterUpdate($keyword, getDateValue($dateFrom), getDateValue($dateTo));
            }
        });

        $("#searchPromo").click(function(){
            var $dateFrom = $("input[name='dateFrom']").val();
            var $dateTo = $("input[name='dateTo']").val();
            var $keyword = $(this).val();

            filterUpdate($keyword, getDateValue($dateFrom), getDateValue($dateTo));
        });

        $("button.enable-promo-success-trigger, button.disable-promo-success-trigger").click(function(){
            var $promoInstanceIds = getCheckedPromos();

            if($promoInstanceIds.length > 0 && !$ajaxExecuting){
                $ajaxExecuting = true;
                $promoInstanceIds = JSON.stringify($promoInstanceIds);

                if($(this).hasClass("enable-promo-success-trigger")){
                    $(".enable-promo-modal").modal({
                        onApprove: function(){
                            $.ajax({
                                url: Routing.generate("yilinker_backend_change_promo_status"),
                                type: 'POST',
                                data: {promoInstanceIds:$promoInstanceIds, isEnabled:true},
                                success: function(response) {
                                    if(response.isSuccessful){
                                        $promoInstanceIds = JSON.parse($promoInstanceIds);

                                        $promoInstanceIds.forEach(function(promoInstance){
                                            $("tr[data-id='" + promoInstance + "']").find(".is-enabled").text("Enabled");
                                        });

                                        $(".promo-checkbox:checked, .check-all-promo").prop("checked", false);
                                        $(".enable-success-promo-modal").modal("show");
                                    }
                                }
                            });
                        },
                        onHidden: function(){
                            $ajaxExecuting = false;
                        }
                    }).modal("show");
                }
                else{
                    $(".disable-promo-modal").modal({
                        onApprove: function(){
                            $.ajax({
                                url: Routing.generate("yilinker_backend_change_promo_status"),
                                type: 'POST',
                                data: {promoInstanceIds:$promoInstanceIds, isEnabled:false},
                                success: function(response) {
                                    if(response.isSuccessful){
                                        $promoInstanceIds = $.parseJSON($promoInstanceIds);

                                        $promoInstanceIds.forEach(function(promoInstance){
                                            $("tr[data-id='" + promoInstance + "']").find(".is-enabled").text("Disabled");
                                        });

                                        $(".promo-checkbox:checked, .check-all-promo").prop("checked", false);
                                        $(".disable-success-promo-modal").modal("show");
                                    }
                                }
                            });
                        },
                        onHidden: function(){
                            $ajaxExecuting = false;
                        }
                    }).modal("show");
                }
            }
        });

        $(".delete-promo-success-trigger").click(function(){
            var $promoInstanceIds = getCheckedPromos();
            var $instanceIdsCount = $promoInstanceIds.length;
            var $currentListingCount = parseInt($promoListing.text());

            if($promoInstanceIds.length > 0 && !$ajaxExecuting){
                $ajaxExecuting = true;
                $promoInstanceIds = JSON.stringify($promoInstanceIds);

                $(".delete-promo-modal").modal({
                    onApprove: function(){
                        $.ajax({
                            url: Routing.generate("yilinker_backend_delete_promo"),
                            type: 'POST',
                            data: {promoInstanceIds:$promoInstanceIds},
                            success: function(response) {
                                if(response.isSuccessful){
                                    $promoInstanceIds = $.parseJSON($promoInstanceIds);

                                    $promoInstanceIds.forEach(function(promoInstance){
                                        $("tr[data-id='" + promoInstance + "']").remove();
                                    });

                                    $promoListing.text($currentListingCount - $instanceIdsCount);
                                    $(".promo-checkbox:checked, .check-all-promo").prop("checked", false);
                                    $(".delete-success-promo-modal").modal("show");
                                }
                            }
                        });
                    },
                    onHidden: function(){
                        $ajaxExecuting = false;
                    }
                }).modal("show");
            }
        });

        $(document).on("click", ".modal-edit-promo-trigger", function(){
            var $promoDetails = JSON.parse($(this).attr("data-instance"));
            var $productList = "";
            $action = "edit";

            $currentPromo = $promoDetails.promoInstanceId;

            $promoForm.find("input[name='title']").val($promoDetails.title);
            $promoForm.find("select[name='promoType']").dropdown("set selected", $promoDetails.promoType.promoTypeId);
            $promoForm.find("input[name='advertisement']").val($promoDetails.advertisement);
            $promoForm.find("input[name='dateStart']").val(getDateTimeToMomentDash($promoDetails.dateStart.date));
            $promoForm.find("input[name='dateEnd']").val(getDateTimeToMomentDash($promoDetails.dateEnd.date));

            createDefaultHeader();
            $.map($promoDetails.productUnits, function($productUnit, $id){
                $productList += generatePromoProductLi($productUnit);
            });

            $(".promoProducts").html($productList);

            renderPromoProductTables(parseInt($promoDetails.promoType.promoTypeId), false);

            if($promoDetails.isEnabled){
                $promoForm.find("input[name='isEnabled']").prop("checked", true);

                if(
                    moment($promoDetails.dateEnd.date) > moment() &&
                    moment() > moment($promoDetails.dateStart.date)
                ){
                    clearInterval($timerInterval);
                    $timerInterval = setInterval(function(){
                        var now  = moment();
                        var then = moment($promoDetails.dateEnd.date);

                        var minuteSeconds = moment(then,"DD/MM/YYYY HH:mm:ss").diff(moment(now,"DD/MM/YYYY HH:mm:ss"));
                        var duration = moment.duration(minuteSeconds);

                        var days = Math.floor(duration.asHours()/24);
                        var hours = Math.floor(duration.asHours()) - (days * 24);
                        var result = (days + ":" + hours + moment.utc(minuteSeconds).format(":mm:ss")).split(":");

                        var subDays = $(".timer .days sub");
                        var subHours = $(".timer .hours sub");
                        var subMinutes = $(".timer .minutes sub");
                        var subSeconds = $(".timer .seconds sub");

                        $(".timer .days").text(result[0] + " : ").append(subDays);
                        $(".timer .hours").text(result[1] + " : ").append(subHours);
                        $(".timer .minutes").text(result[2] + " : ").append(subMinutes);
                        $(".timer .seconds").text(result[3] + " : ").append(subSeconds);
                    }, 1000);

                    $(".enabled-timer").hide();
                    $(".disabled-timer").show();
                    $(".countdown-timer-container, .active-notification").removeClass("hidden");
                }
                else{
                    $(".countdown-timer-container, .active-notification").addClass("hidden");
                }
            }

            $('.modal-promo').modal({
                selector: {
                    close: ".cancel, .close",
                    deny: ".cancel"
                },
                closable  : false,
                allowMultiple: true,
                onDeny    : function(){
                  return false;
                },
                blurring: true,
                onShow: function(){
                },
                onHidden: function(){
                    $ajaxExecuting = false;
                }
            }).modal('show');
        });

        $(".add-promo-success-trigger").click(function(){

            $promoModal.find("input:not(input[name='_token'])").val("");
            $promoModal.find("input[type='checkbox']").prop("checked", false);
            $promoModal.find(".checkbox").checkbox("uncheck");
            $promoModal.find(".dropdown").dropdown("set selected", 1);
            $promoModal.find("tbody").html("");

            createDefaultHeader();

            $(".modal-promo").modal({
                selector: {
                    close: ".cancel, .close",
                    deny: ".cancel"
                },
                closable  : false,
                allowMultiple: true,
                onDeny    : function(){
                  return false;
                },
                blurring: true,
                onShow: function(){
                    $action = "add";
                    $(".countdown-timer-container, .active-notification").addClass("hidden");
                },
                onHidden: function(){
                    $ajaxExecuting = false;
                }
            }).modal("show");
        });

        $(document).on("change", "select[name='promoType']", function(){
            var $promoTypeId = parseInt($promoType.dropdown("get value"));
            renderPromoProductTables($promoTypeId, true);
        });

        $(document).on("click", ".delete-product", function(){
            $(this).closest("tr").remove();
        });

        $(document).on("keyup", ".extra-field.discounted-price", function(){
            var $val = $(this).val();
            var $discount = 0;

            var $originalPrice = $(this).parent("td").prev().text();

            $originalPrice = parseFloat($originalPrice.replace("P ", "").replace(",",""));

            $discount = getDiscount($val, $originalPrice);

            $(this).parent("td").parent("tr").find(".extra-percent").text($discount + "%");
        });
    });

    $(window).on("ready load resize", function(){
        var windowWidth = $(this).width();
        var sliderWrapperHeight = $(".slider.wrapper").outerHeight();
        var gridFeaturedItemsHeight = $(".grid-featured-items").outerHeight();
        $(".grid-featured-items").css({
            height: sliderWrapperHeight
        });
        $(".grid.extra").css({
            height: gridFeaturedItemsHeight
        });
    });

    //Single selection select box
    $(".single.selection").dropdown();

    //Multiple selection select box with tokens
    $(".multiple.search.selection").dropdown({
        maxSelections: 5,
        allowAdditions: true
    });

    $(".modal-promo-add-products-trigger").click(function(){
        $('.modal-promo-add').modal({
            blurring: true,
            allowMultiple: true,
         }).modal("show");
    });

    //Tabs
    $('.tabular.menu .item').tab();

    //Tabs
    $('.tabular.menu .item').tab();

    $(".requestor").dropdown({
      onChange: function (val) {
          $('.cancellation-reason').removeClass('disabled');
      }
    });

    $(".expander").click(function () {
        $header = $(this);
        //getting the next element
        $content = $header.next();
        //open up the content needed - toggle the slide- if visible, slide up, if not slidedown.
        $content.slideToggle(500, function () {
            //execute this after slideToggle is done
            //change text of header based on visibility of content div
            $header.text(function () {
                //change text based on condition
                return $content.is(":visible") ? "-" : "+";
            });
        });

    });

    function resetFields(){
        $promoForm.find("input[name='title']").val("");
        $promoForm.find("select[name='promoType']").dropdown("restore defaults");
        $promoForm.find("input[name='advertisement']").val("");
        $promoForm.find("input[name='dateStart']").val("");
        $promoForm.find("input[name='dateEnd']").val("");
        $promoForm.find("input[name='maxPercentage']").val("");
        $promoForm.find("input[name='isEnabled']").prop("checked", false);
        $promoForm.find("input[name='quantityRequired']").val("");
        $promoForm.find("input[name='percentPerHour']").val("");
        $promoForm.find("input[name='minimumPercentage']").val("");
        $(".promoProducts").html("");
    }

    function createPromoInstance(){

        $postData = {
            "title"               : $promoForm.find("input[name='title']").val(),
            "promoType"           : $promoForm.find("select[name='promoType']").val(),
            "advertisement"       : $promoForm.find("input[name='advertisement']").val(),
            "dateStart"           : $promoForm.find("input[name='dateStart']").val(),
            "dateEnd"             : $promoForm.find("input[name='dateEnd']").val(),
            "isEnabled"           : $promoForm.find("input[name='isEnabled']:checked").length > 0? true:false,
            "_token"              : $promoForm.find("input[name='_token']").val()
        };

        $promoErrorContainer.addClass("hidden").html("");
        $promoErrorContainer.html("").addClass("hidden");
        $ajaxExecuting = true;

        $products = getPromoProducts();
        $postData.products = $products;

        $.ajax({
            url: Routing.generate('yilinker_backend_create_promo'),
            type: 'POST',
            data: $postData,
            success: function(response) {
                $ajaxExecuting = false;
                if(response.isSuccessful){
                    var $promoTr = generatePromoInstanceTr(response.data.promoInstance);
                    var $currentListingCount = parseInt($promoListing.text());

                    $(".promo-list>tbody").prepend($promoTr);

                    if($(".promo-list>tbody>tr").length > 20){
                        $(".promo-list>tbody>tr").last().remove();
                    }

                    $promoListing.text($currentListingCount + 1);

                    $(".bulk-discount, .countdown-discount").addClass("hidden");
                    $(".promoProducts").html("");
                    $(".create-success-promo-modal").modal("show");
                }
            },
            error: function(response){
                var $errorList = "<ul class='list'>";
                var $responseJson = response.responseJSON;
                var $errors = $responseJson.data.errors;
                $ajaxExecuting = false;

                $promoForm.removeClass("hidden success").addClass("error");

                $errors.forEach(function($error){
                    $errorList += "<li>" +  $error + "</li>";
                });

                $errorList += "</ul>";

                $promoErrorContainer.html($errorList);
            }
        });
    }

    function updatePromoInstance(){

        $postData = {
            "promoInstanceId"     : $currentPromo,
            "title"               : $promoForm.find("input[name='title']").val(),
            "promoType"           : $promoForm.find("select[name='promoType']").val(),
            "advertisement"       : $promoForm.find("input[name='advertisement']").val(),
            "dateStart"           : $promoForm.find("input[name='dateStart']").val(),
            "dateEnd"             : $promoForm.find("input[name='dateEnd']").val(),
            "isEnabled"           : $promoForm.find("input[name='isEnabled']:checked").length > 0? true:false,
            "_token"              : $promoForm.find("input[name='_token']").val()
        };

        $promoErrorContainer.addClass("hidden").html("");
        $promoErrorContainer.html("").addClass("hidden");
        $ajaxExecuting = true;

        $products = getPromoProducts();
        $postData.products = $products;

        $.ajax({
            url: Routing.generate('yilinker_backend_update_promo'),
            type: 'POST',
            data: $postData,
            success: function(response) {
                $ajaxExecuting = false;
                if(response.isSuccessful){
                    var $promoInstance = response.data.promoInstance;
                    var $promoTr = $(".promo-list tr[data-id='" + $currentPromo + "']");
                    var $isEnabled = $promoInstance.isEnabled? "Enabled" : "Disabled";

                    $promoTr.find("td.title").text($promoInstance.title);
                    $promoTr.find("td.promo-type").text($promoInstance.promoType.name);
                    $promoTr.find("td.date-start").text(getDateTimeToMomentSlash($promoInstance.dateStart.date));
                    $promoTr.find("td.date-end").text(getDateTimeToMomentSlash($promoInstance.dateEnd.date));
                    $promoTr.find("td.product-count").text($promoInstance.productUnitsCount);
                    $promoTr.find("td.is-enabled").text($isEnabled);
                    $promoTr.find("button.modal-edit-promo-trigger").attr("data-instance", JSON.stringify($promoInstance));

                    $currentPromo = null;
                    $(".bulk-discount, .countdown-discount").addClass("hidden");
                    $(".promoProducts").html("");
                    $(".create-success-promo-modal").modal("show");
                }
            },
            error: function(response){
                var $errorList = "<ul class='list'>";
                var $responseJson = response.responseJSON;
                var $errors = $responseJson.data.errors;
                $ajaxExecuting = false;

                $promoForm.removeClass("hidden success").addClass("error");

                $errors.forEach(function($error){
                    $errorList += "<li>" +  $error + "</li>";
                });

                $errorList += "</ul>";

                $promoErrorContainer.html($errorList);
            }
        });
    }

    function getPromoProducts(){
        var $productUnits = {};

        $("tbody.promoProducts tr").each(function($index, $product){
            var $productUnitId = parseInt($($product).attr("data-unit-id"));

            $productUnits[$productUnitId] = {
                productUnitId : $productUnitId,
                discountedPrice : $("input[name='discountedPrice-" + $productUnitId + "']").val(),
                maxQuantity : $("input[name='maxQuantity-" + $productUnitId + "']").val(),
                minimumPercentage : $("input[name='minimumPercentage-" + $productUnitId + "']").val(),
                maximumPercentage : $("input[name='maximumPercentage-" + $productUnitId + "']").val(),
                percentPerHour : $("input[name='percentPerHour-" + $productUnitId + "']").val(),
                quantityRequired : $("input[name='quantityRequired-" + $productUnitId + "']").val()
            };
        });

        return $productUnits;
    }

    function secondLevelValidate(){
        var $errors = [];
        var $promoForm = $("form[name='promo']");
        var $dateFrom = $promoForm.find("input[name='dateStart']").val();
        var $dateTo = $promoForm.find("input[name='dateEnd']").val();

        $dateFrom = moment($dateFrom, "MM-DD-YYYY HH:mm:ss").format("X");
        $dateTo = moment($dateTo, "MM-DD-YYYY HH:mm:ss").format("X");

        if($dateFrom > $dateTo){
            $(".start-date, .end-date").addClass("error");
            $errors.push("Start date should be less than end date.")
        }

        return $errors;
    }

    function generatePromoInstanceTr(promoInstance){
        var $isEnabled = promoInstance.isEnabled? 'Enabled' : 'Disabled';

        var $tr = '<tr data-id="' + promoInstance.promoInstanceId + '">' +
                    '<td align="center" valign="middle">' +
                      '<div>' +
                        '<div class="ui checkbox">' +
                          '<input type="checkbox" class="promo-checkbox toggle-one" value="' + promoInstance.promoInstanceId + '" data-toggle="toggle">' +
                          '<label for="">&nbsp;</label>' +
                        '</div>' +
                      '</div>' +
                    '</td>' +
                    '<td class="title" valign="middle">' + promoInstance.title + '</td>' +
                    '<td class="promo-type" valign="middle">' + promoInstance.promoType.name + '</td>' +
                    '<td class="date-start" valign="middle">' + getDateTimeToMomentSlash(promoInstance.dateStart.date) + '</td>' +
                    '<td class="date-end" valign="middle">' + getDateTimeToMomentSlash(promoInstance.dateEnd.date) + '</td>' +
                    '<td class="product-count" align="center" valign="middle">'+ promoInstance.productUnitsCount + '</td>' +
                    '<td class="is-enabled" align="center" valign="middle">' + $isEnabled + '</td>' +
                    '<td align="center" valign="middle">' +
                      '<button class="options button small default-light modal-edit-promo-trigger">' +
                        'view promo' +
                      '</button>' +
                    '</td>' +
                  '</tr>';

        var $dom = $.parseHTML($tr);

        $($dom).find(".modal-edit-promo-trigger").attr("data-instance", JSON.stringify(promoInstance));
        return $dom;
    }

    function generatePromoProductLi(product, promoType){

        var $row = '<tr data-unit-id="' + product.productUnitId + '">' +
                        '<td>' + product.name + '</td>' +
                        '<td>' + product.sku + '</td>' +
                        '<td>P ' + numberFormat(product.price, 2) + '</td>';

        var $discountedPrice = (typeof product.discountedPrice != 'undefined')? product.discountedPrice : "";
        var $maxQuantity = (typeof product.maxQuantity != 'undefined')? product.maxQuantity : "";
        var $minimumPercentage = (typeof product.minimumPercentage != 'undefined')? product.minimumPercentage : "";
        var $maximumPercentage = (typeof product.maximumPercentage != 'undefined')? product.maximumPercentage : "";
        var $percentPerHour = (typeof product.percentPerHour != 'undefined')? product.percentPerHour : "";
        var $quantityRequired = (typeof product.quantityRequired != 'undefined')? product.quantityRequired : "";

        var $discount = getDiscount($discountedPrice, product.price);

        $discount = parseFloat($discount) > 0? $discount : 0;

        $row += "<td class='extra-field discounted-price'>" +
                    "<input type='text' class='extra-field discounted-price' name='discountedPrice-"+ product.productUnitId +"' value=' "+ $discountedPrice + "' />" +
                "</td>" +
                "<td>" +
                    "<input type='text' class='extra-field max-quantity' name='maxQuantity-"+ product.productUnitId +"' value=' "+ $maxQuantity + "' />" +
                "</td>" +
                "<td class='extra-field minimum-percentage'>" +
                    "<input type='text' class='extra-field minimum-percentage' name='minimumPercentage-"+ product.productUnitId +"' value=' "+ $minimumPercentage + "' />" +
                "</td>" +
                "<td class='extra-field maximum-percentage'>" +
                    "<input type='text' class='extra-field maximum-percentage' name='maximumPercentage-"+ product.productUnitId +"' value=' "+ $maximumPercentage + "' />" +
                "</td>" +
                "<td class='extra-field percent-per-hour'>" +
                    "<input type='text' class='extra-field percent-per-hour' name='percentPerHour-"+ product.productUnitId +"' value=' "+ $percentPerHour + "' />" +
                "</td>" +
                "<td class='extra-field quantity-required'>" +
                    "<input type='text' class='extra-field quantity-required' name='quantityRequired-"+ product.productUnitId +"' value=' "+ $quantityRequired + "' />" +
                "</td>" +
                "<td><span class='extra-percent' data-name-percent='" + product.productUnitId + "'>" + $discount + "%</span></td>" +
                "<td align='right'>" +
                      "<button class='options button simple red tiny delete-product'>" +
                        "<i class='item-icon icon-times'></i>" +
                      "</button>" +
                    "</td>" +
                "</tr>";

        return $row;
    }

    function getCheckedPromos(){
        var $checkboxes = $(".promo-checkbox:checked");

        var $promoInstanceIds = [];
        $checkboxes.each(function(index, checkbox){
            $promoInstanceIds.push($(checkbox).val());
        });

        return $promoInstanceIds;
    }

    function getDateValue($value){
        var $date = moment($value, "MM-DD-YYYY HH:mm:ss").format("YYYY-MM-DD HH:mm:ss");

        if($date.indexOf("Invalid date") == 0){
            return "";
        }
        else{
            return $date;
        }
    }

    function getDateTimeToMomentSlash($value){
        var $date = moment($value, "YYYY-MM-DD HH:mm:ss").format("MM/DD/YYYY HH:mm:ss");

        if($date.indexOf("Invalid date") == 0){
            return "";
        }
        else{
            return $date;
        }
    }

    function getDateTimeToMomentDash($value){
        var $date = moment($value, "YYYY-MM-DD HH:mm:ss").format("MM-DD-YYYY HH:mm:ss");

        if($date.indexOf("Invalid date") == 0){
            return "";
        }
        else{
            return $date;
        }
    }

    function getDateToValue($value){
        var $date = moment($value, "MM-DD-YYYY HH:mm:ss").endOf('day').format("YYYY-MM-DD HH:mm:ss");

        if($date.indexOf("Invalid date") == 0){
            return "";
        }
        else{
            return $date;
        }
    }

    function filterUpdate(keyword, dateFrom, dateTo){

        var filterString = "dateFrom=" + dateFrom + "&dateTo=" + dateTo + "&keyword="+ keyword;

        /**
         * Reload page with ne
         */
        var reloadUrl = window.location.origin + window.location.pathname + "?" + filterString;
        window.location.href = reloadUrl;
    }

    function createDefaultHeader(){
        var $header = "" +
            "<tr>" +
              "<th data-name='name'>Item Name</th>" +
              "<th data-name='sku'>Sku</th>" +
              "<th data-name='price'>Price</th>" +
              "<th class='extra-head discounted-price' data-name='discountedPrice'>Discounted Price</th>" +
              "<th class='extra-head max-quantity' data-name='maxQuantity'>Max Qty</th>" +
              "<th class='extra-head minimum-percentage' data-name='minimumPercentage'>Min %</th>" +
              "<th class='extra-head maximum-percentage' data-name='maximumPercentage'>Max %</th>" +
              "<th class='extra-head percent-per-hour' data-name='percentPerHour'>%/hr</th>" +
              "<th class='extra-head quantity-required' data-name='quantityRequired'>Qty Req</th>" +
              "<th>Discount Percentage</th>" +
              "<th></th>" +
            "</tr>" +
        "";

        $promoModal.find("thead").html($header);
    }

    function renderPromoProductTables($promoTypeId, $isReset)
    {
        if($isReset){
            $(".extra-field:not(.max-quantity)").val("")
            $(".extra-percent").text("0%");
        }

        $(".extra-field:not(.max-quantity)").addClass("hidden");
        $(".extra-head:not(.max-quantity)").addClass("hidden");

        switch($promoTypeId){
            case BULK:
                $(".quantity-required").removeClass("hidden");
                $(".discounted-price").removeClass("hidden");
                break;
            case PER_HOUR:
                $(".minimum-percentage").removeClass("hidden");
                $(".maximum-percentage").removeClass("hidden");
                $(".percent-per-hour").removeClass("hidden");
                break;
            default:
                $(".discounted-price").removeClass("hidden");
                break;
        }
    }

    function getDiscount($val, $originalPrice){
        if (parseFloat($val) > 0 && parseFloat($originalPrice) > 0) {
            var $discount = ((1 - ($val / $originalPrice)) * 100).toString();
            $parts = $discount.split(".");
            $decimal = ".";

            if(typeof $parts[1] != "undefined" && $parts[1].length > 2){
                for(x = 0; x < 2; x++){
                    $decimal += $parts[1][x];
                }
            }
            else if(typeof $parts[1] != "undefined" && $parts[1].length > 0){
                toInsert = 2-$parts[1].length;

                $decimal += $parts[1];
                for(x = 0; x < toInsert; x++){
                    $decimal += "0";
                }

            }
            else{
                $decimal += "00";
            }

            return parseFloat($parts[0] + $decimal);
        }

        return 0;
    }

    /**
     * temp to be delete
     */

     $("a.toggle-me").click(function() {
       $( "div.toggle-me" ).toggle();
     });

})(jQuery);
