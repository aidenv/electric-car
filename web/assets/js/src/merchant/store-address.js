(function($) {
    var $ajaxLoading = false;

    //Checkbox customize design
    $(".ui.checkbox").checkbox();

     //New Address modal
    $(".new-address-modal-trigger").click(function(){
        $(".new-address-modal").modal({
            onShow : function(){
                var $modal = $(".new-address-modal");
                var $provinceDropdown = $modal.find(".province-input");
                var $cityDropdown = $modal.find(".city-input");
                var $barangayDropdown = $modal.find(".barangay-input");

                $modal.find(".ui.error.message-box").html("");
                $modal.find(".field.error").removeClass("error");
                $modal.find("input").val("");
                $modal.find("select").find("option").prop("selected", false);

                $provinceDropdown.dropdown('set selected', 0);
                $cityDropdown.dropdown('set selected', 0);
                $barangayDropdown.dropdown('set selected', 0);

            }
        }).modal("show");

        $('.coupled-new-address').modal({
            allowMultiple: false
        });
    });
    
    $(document).on("ready load resize", function(){
        $('#store-address-collection').masonry({
            itemSelector: '.col-md-6.col-xl-4',
            columnWidth: '.col-md-6.col-xl-4',
            percentPosition: true,
            isResizeBound: true
        });
    });

    $(document).ready(function(){

        var $updateCityId = null;
        var $updateBarangayId = null;

        var $addNewAddressForm = $("form[name='add-new-address']");
        var $updateAddressForm = $("form[name='update-address']");

        var $formRules = {
            title: {
                identifier  : 'title',
                rules: [
                    {
                        type   : 'empty',
                        prompt : 'Address title is required'
                    },
                    {
                        type   : 'maxLength[255]',
                        prompt : 'Address title can only be up to 255 characters'
                    }
                ]
            },
            unitNumber: {
                identifier  : 'unitNumber',
                rules: [
                    {
                        type   : 'maxLength[45]',
                        prompt : 'Unit number can be up to 45 characters'
                    }
                ]
            },
            buildingName: {
                identifier  : 'buildingName',
                rules: [
                    {
                        type   : 'maxLength[255]',
                        prompt : 'Building name can only be up to 255 characters'
                    }
                ]
            },
            streetNumber: {
                identifier  : 'streetNumber',
                rules: [
                    {
                        type   : 'maxLength[25]',
                        prompt : 'Street number can only be up to 25 characters'
                    }
                ]
            },
            streetName: {
                identifier  : 'streetName',
                rules: [
                    {
                        type   : 'empty',
                        prompt : 'Street name is required'
                    },
                    {
                        type   : 'maxLength[255]',
                        prompt : 'Street name can only be up to 25 characters'
                    }
                ]
            },
            subdivision: {
                identifier  : 'subdivision',
                rules: [
                    {
                        type   : 'maxLength[255]',
                        prompt : 'Subdivision can only be up to 255 characters'
                    }
                ]
            },
            zipCode: {
                identifier  : 'zipCode',
                rules: [
                    {
                        type   : 'maxLength[45]',
                        prompt : 'Zip code can only be up to 45 characters'
                    },
                    {
                       type   : 'number',
                       prompt : 'Please enter a valid zip code'
                    }
                ]
            },
            province: {
                identifier  : 'province',
                rules: [
                    {
                        type   : 'empty',
                        prompt : 'Province is required'
                    },
                    {
                        type   : 'regExp[/^[1-9][0-9]*$/]',
                        prompt : 'Invalid province.'
                    }
                ]
            },
            city: {
                identifier  : 'city',
                rules: [
                    {
                        type   : 'empty',
                        prompt : 'City is required'
                    },
                    {
                        type   : 'regExp[/^[1-9][0-9]*$/]',
                        prompt : 'Invalid city.'
                    }
                ]
            },
            barangay: {
                identifier  : 'barangay',
                rules: [
                    {
                        type   : 'empty',
                        prompt : 'Barangay is required'
                    },
                    {
                        type   : 'regExp[/^[1-9][0-9]*$/]',
                        prompt : 'Invalid barangay.'
                    }
                ]
            }
        };

        var $addNewAddressFormSettings = {
            fields: {},
            onSuccess: function(){
                var $cityId = $addNewAddressForm.form("get value", "city");
                var $barangayId = $addNewAddressForm.form("get value", "barangay");
                var $locationId = parseInt($barangayId) != 0 && !isNaN($barangayId) ? $barangayId : $cityId;
                var $postData = {
                    title           : $addNewAddressForm.form("get value", "title"),
                    unitNumber      : $addNewAddressForm.form("get value", "unitNumber"),
                    buildingName    : $addNewAddressForm.form("get value", "buildingName"),
                    streetNumber    : $addNewAddressForm.form("get value", "streetNumber"),
                    streetName      : $addNewAddressForm.form("get value", "streetName"),
                    subdivision     : $addNewAddressForm.form("get value", "subdivision"),
                    zipCode         : $addNewAddressForm.form("get value", "zipCode"),
                    locationId      : $locationId
                };

                var $addressDom = $("#clone-address-table").clone().children();

                if(!$ajaxLoading){
                    $ajaxLoading = true;

                    $.ajax({
                        url: Routing.generate('core_address_add'),
                        type: 'POST',
                        data: $postData,
                        beforeSend: function(){
                            applyLoading($addNewAddressForm);
                        },
                        success: function(response) {
                            if(response.isSuccessful){
                                var $userAddress = response.data;
                                var $isDefault = $userAddress.isDefault;
                                var $storeAddressCollection = $("#store-address-collection");
                                var $successNewAddressMessage = $(".success-new-address-message");
                                var $emptyUserAddress = $(".empty-user-address");
                                var $notEmptyUserAddress = $(".not-empty-user-address");
                                var $dropdown = $(".dropdown");

                                $addressDom.attr("data-user-address-id", $userAddress.userAddressId);
                                $addressDom.find(".ui.checkbox").checkbox();
                                $addressDom.find(".item-name").text($userAddress.title);
                                $addressDom.find(".item-address-line").text($userAddress.fullLocation);
                                $addressDom.find("input[name='isDefault']").attr("data-id", $userAddress.userAddressId);
                                $addressDom.find(".item.delete").attr("data-id", $userAddress.userAddressId);
                                $addressDom.find(".item.edit").attr("data-id", $userAddress.userAddressId);
                                $addressDom.find(".item.edit").attr("data-address", JSON.stringify($userAddress));
                                $addressDom.find("input[name='isDefault']").removeClass("hidden");
                                $addressDom.find(".ellipsis-dropdown").dropdown();

                                if($isDefault){
                                    $addressDom.find("input[name='isDefault']").attr("checked", "true");
                                    $addressDom.find(".store-address-segment").addClass("active");
                                }

                                $storeAddressCollection.prepend($addressDom).masonry('prepended', $addressDom);
                                $successNewAddressMessage.modal('show')
                                $successNewAddressMessage.find(".set-as-default").attr("data-address", $userAddress.userAddressId);

                                $emptyUserAddress.addClass("hidden");
                                $notEmptyUserAddress.removeClass("hidden");


                                $storeAddressCollection.masonry({
                                    itemSelector: '.col-md-6.col-xl-4',
                                    columnWidth: '.col-md-6.col-xl-4',
                                    percentPosition: true,
                                    isResizeBound: true
                                });

                                $addNewAddressForm.form("clear");
                                $dropdown.dropdown('restore defaults');
                            }

                            $ajaxLoading = false;
                        },
                        complete: function(){
                            unloadButton($addNewAddressForm);
                        }
                    });
                }

                return false;
            }
        };

        var $updateAddressFormSettings = {
            fields: {},
            onSuccess: function(){
                var $userAddressId = $("form[name='update-address']").attr("data-id");
                var $cityId = $updateAddressForm.form("get value", "city");
                var $barangayId = $updateAddressForm.form("get value", "barangay");
                var $addressDom = $("div[data-user-address-id='" + $userAddressId + "']")
                var $postData = {
                    userAddressId   : $userAddressId,
                    title           : $updateAddressForm.form("get value", "title"),
                    unitNumber      : $updateAddressForm.form("get value", "unitNumber"),
                    buildingName    : $updateAddressForm.form("get value", "buildingName"),
                    streetNumber    : $updateAddressForm.form("get value", "streetNumber"),
                    streetName      : $updateAddressForm.form("get value", "streetName"),
                    subdivision     : $updateAddressForm.form("get value", "subdivision"),
                    zipCode         : $updateAddressForm.form("get value", "zipCode"),
                    locationId      : parseInt($barangayId) != 0 && !isNaN($barangayId) ? $barangayId : $cityId
                };

                if(!$ajaxLoading){
                    $ajaxLoading = true;

                    $.ajax({
                        url: Routing.generate("core_address_edit"),
                        type: 'POST',
                        data: $postData,
                        beforeSend: function(){
                            applyLoading($updateAddressForm);
                        },
                        success: function(response) {
                            if(response.isSuccessful){
                                var $userAddress = response.data;
                                var $storeAddressCollection = $("#store-address-collection");
                                var $successUpdateAddressMessage = $(".edit-success-address-modal");
                                var $dropdown = $(".dropdown");

                                $addressDom.find(".item-name").text($userAddress.title);
                                $addressDom.find(".item-address-line").text($userAddress.fullLocation);
                                $addressDom.find(".item.edit").attr("data-address", JSON.stringify($userAddress));

                                $successUpdateAddressMessage.modal('show');

                                $storeAddressCollection.masonry({
                                    itemSelector: '.col-md-6.col-xl-4',
                                    columnWidth: '.col-md-6.col-xl-4',
                                    percentPosition: true
                                });

                                $updateAddressForm.form("clear");
                                $dropdown.dropdown('restore defaults');
                            }

                            $ajaxLoading = false;
                        },
                        complete: function(){
                            unloadButton($updateAddressForm);
                        }
                    });

                    return false;
                }
            }
        };
        
        $addNewAddressFormSettings.fields = $formRules;
        $updateAddressFormSettings.fields = $formRules;

        $addNewAddressForm.form($addNewAddressFormSettings);        
        $updateAddressForm.form($updateAddressFormSettings);    

        $(document).on("click", ".set-as-default", function(){

            var $this = $(this);
            var $userAddressId = $this.attr("data-address");
            var $checkbox = $(".checkbox");
            var $storeSegment = $(".store-address-segment");
            var $newAddress = $(".store-address[data-user-address-id='" + $userAddressId + "']")

            //reset checkbox
            $checkbox.checkbox("uncheck");
            $newAddress.find(".ui.checkbox").checkbox("set checked");

           //reset active container
            $storeSegment.removeClass("active");
            $newAddress.find(".store-address-segment").addClass("active");

            if(!$ajaxLoading){
                $ajaxLoading = true;
                $.ajax({
                    url: Routing.generate("core_address_default"),
                    type: 'POST',
                    data: {userAddressId:$userAddressId},
                    success: function(response) {
                        $(".success-new-address-message").modal("hide");
                        $ajaxLoading = false;
                    }
                });
            }
        });


        $(document).on("click", ".store-address-segment .delete", function(){
            var $this = $(this);
            var $userAddressId = $this.data("id");
            var $addressDom = $("div[data-user-address-id='" + $userAddressId + "']")
            var $successDeleteModal = $(".delete-success-address-modal");
            var $storeAddressCollection = $("#store-address-collection");

            $(".delete-address-modal").modal({
                onApprove: function(){
                    var $button = $(this).find(".button-submit");
                    if(!$ajaxLoading){
                        $ajaxLoading = true;

                        $.ajax({
                            url: Routing.generate("core_address_delete"),
                            type: 'POST',
                            data: {userAddressId:$userAddressId},
                            beforeSend: function(){
                                $button.html("<div class='ui active centered small inline inverted loader'>&nbsp;</div>").addClass("disabled");
                            },
                            success: function(response) {
                                if(response.isSuccessful){
                                    $addressDom.remove();
                                    $successDeleteModal.modal("show");
                                }

                                $ajaxLoading = false;
                            },
                            error: function(response){
                                var $status = response.status;
                                if($status == 400){
                                    $(".alert-default-address-modal").modal("show");
                                }

                                $ajaxLoading = false;
                            },
                            complete: function(){
                                $button.html("Delete").removeClass('disabled');

                                $storeAddressCollection.masonry({
                                    itemSelector: '.col-md-6.col-xl-4',
                                    columnWidth: '.col-md-6.col-xl-4',
                                    percentPosition: true,
                                    isResizeBound: true
                                });

                                $ajaxLoading = false;
                            }
                        });

                        return false;
                    }
                }
            }).modal("show");
        });

        $(document).on("click", ".store-address-segment .edit", function(){

            var $userAddress = $.parseJSON($(this).attr("data-address"));
            var $form = $(".edit-address-modal form[name='update-address']");
            var $editAddressModal = $(".edit-address-modal");
            var $coupledEdit = $('.coupled-edit');
            var $provinceDropdown = $form.find(".province-input");
            var $cityDropdown = $form.find(".city-input");
            var $barangayDropdown = $form.find(".barangay-input");
            var userAddressId = $userAddress.userAddressId;

            $editAddressModal.modal("show");

            $coupledEdit.modal({
                allowMultiple: false
            });

            $updateCityId = $userAddress.cityId;
            $updateBarangayId = $userAddress.barangayId;


            $editAddressModal.find(".ui.error.message-box").html("");
            $editAddressModal.find(".field.error").removeClass("error");
            
            $form.attr("data-id", userAddressId);
            $form.find("input[name='title']").val($userAddress.title);
            $form.find("input[name='unitNumber']").val($userAddress.unitNumber);
            $form.find("input[name='buildingName']").val($userAddress.buildingName);
            $form.find("input[name='streetNumber']").val($userAddress.streetNumber);
            $form.find("input[name='streetName']").val($userAddress.streetName);
            $form.find("input[name='subdivision']").val($userAddress.subdivision);
            $form.find("input[name='zipCode']").val($userAddress.zipCode);

            var provincePromise = renderCityDropdown($form, $userAddress.provinceId)
            provincePromise.done(function(){
                $provinceDropdown.dropdown('set selected', $userAddress.provinceId);
                var cityPromise = renderBarangayDropdown($form, $userAddress.cityId);
                cityPromise.done(function(){
                    $cityDropdown.dropdown('set selected', $userAddress.cityId);
                    setTimeout(function(){
                        $barangayDropdown.dropdown('set selected', $userAddress.barangayId);
                    }, 300);
                });
            });
        });

        $(".province-input").on("click", ".menu .item", function(e){
            var $this = $(this);
            var $form = $this.parents("form");
            var $provinceId = $form.find(".province-input").dropdown("get value");
            var $cityDropdown = $form.find(".city-input");
            var $barangayDropdown = $form.find(".barangay-input");

            $cityDropdown.dropdown('restore defaults');
            $barangayDropdown.dropdown('restore defaults');

            $cityDropdown.dropdown('set selected', 0);
            $barangayDropdown.dropdown('set selected', 0);

            renderCityDropdown($form, $provinceId);
        });

        $(".city-input").on("click", ".menu .item", function(e){
            var $this = $(this);
            var $form = $this.parents("form");
            var $cityId = $form.find(".city-input").dropdown("get value");
            var $barangayDropdown = $form.find(".barangay-input");

            $barangayDropdown.dropdown('restore defaults');

            $barangayDropdown.dropdown('set selected', 0);

            renderBarangayDropdown($form, $cityId);
        });

        $(document).on("click", "#store-address-collection input[type='checkbox'], #store-address-collection .ui.checkbox", function(){

            var $this = $(this);
            var $checkbox = $(".checkbox");
            var $storeSegment = $(".store-address-segment");
            var $userAddressId = $this.find("input[name='isDefault']").data("id");

            //reset checkbox
            $checkbox.checkbox("uncheck");
            $this.checkbox("set checked");

           //reset active container
            $storeSegment.removeClass("active");
            $this.parents(".store-address-segment").addClass("active");

            if(!$ajaxLoading){
                $ajaxLoading = true;
                $.ajax({
                    url: Routing.generate("core_address_default"),
                    type: 'POST',
                    data: {userAddressId:$userAddressId},
                    success: function(response) {
                        $ajaxLoading = false;
                    }
                });
            }
        });
    });

    function renderCityDropdown(form, provinceId){
        var deferredObject = $.Deferred();

        if(!$ajaxLoading){
            $ajaxLoading = true;
            $.ajax({
                url: Routing.generate("core_get_child_cities"),
                type: 'POST',
                data: {provinceId:provinceId},
                success: function(response) {
                    var $citiesHtml = "<option value='0'>Select your city</option>";
                    if(response.isSuccessful){
                        response.data.forEach(function($city){
                            $citiesHtml += "<option value='" + $city.cityId + "'>" + $city.location + "</option>";
                        });

                        form.find("select[name='city']").html($citiesHtml).promise().done(function(){
                            form.find("select[name='city']").dropdown("refresh");
                            $ajaxLoading = false;
                            deferredObject.resolve();
                        });
                    }
                }
            });
        }

        return deferredObject.promise();
    }

    function renderBarangayDropdown(form, cityId){
        var deferredObject = $.Deferred();

        if(!$ajaxLoading){
            $ajaxLoading = true;
            $.ajax({
                url: Routing.generate("core_get_barangays_by_city"),
                type: 'POST',
                data: {cityId:cityId},
                success: function($response) {
                    var $barangaysHtml = "<option value='0'>Select your barangay</option>";
                    if($response.isSuccessful){
                        $response.data.forEach(function($barangay){
                            $barangaysHtml += "<option value='" + $barangay.barangayId + "'>" + $barangay.location + "</option>";
                        });

                        form.find("select[name='barangay']").html($barangaysHtml).promise().done(function(){
                            form.find("select[name='barangay']").dropdown("refresh");
                            $ajaxLoading = false;
                            deferredObject.resolve();
                        });
                    }
                }
            });
        }

        return deferredObject.promise();
    }

    function fillerPromise(){
        var deferredObject = $.Deferred();
        deferredObject.resolve();
        return deferredObject.promise();
    }
})(jQuery);