(function($) {

    var locations = [];
    var $ajaxLoading = false;
    var $addressGrid;
    var IMAGE_TYPE_AVATAR = 0;
    var locationDrodownChange = function(value, text, $selectedItem){
        var $this = $(this);
        if($this.hasClass('disable-onchange') === false){
            var locationPromise = queryLocationChildren($this);
        }
    },
        $copyReferralCodeTrigger = $("#copy-referral-code-trigger"),
        client = new ZeroClipboard($copyReferralCodeTrigger, {
            moviePath: "/assets/js/bower/ZeroClipboard.swf"
        });;

    $(document).ready(function(){
        client.on( "load", function(client) {
          client.on( "complete", function(client, args) {
            // `this` is the element that was clicked
            this.style.display = "none";
          });
        });

        $copyReferralCodeTrigger.click(function(){
            $(".copied-referral-code-modal").modal("show");
        });

        $(".copied-referral-code-modal .confirm").click(function(){
            $(".copied-referral-code-modal").modal("hide");
        });

        $(".ui.checkbox").checkbox();
        
        $addressGrid = $('.shipping-address-container').masonry({
            itemSelector: '.col-md-6.col-xl-4',
            columnWidth: '.col-md-6.col-xl-4',
            percentPosition: true,
            isResizeBound: true
        });

        var defaultLatitude = $("meta[name='default-latitude']").attr("content");
        var defaultLongitude = $("meta[name='default-longitude']").attr("content");

        var addressMap = initMap( $('.address-map-modal').find(".google-map")[0], defaultLatitude, defaultLongitude, 530, 650);
        google.maps.event.addListener(addressMap, "idle", function(){
            google.maps.event.trigger(addressMap, 'resize'); 
        });
        
        var marker = createMarker(addressMap, new google.maps.LatLng(defaultLatitude,defaultLongitude));

        $('.address-map-modal')
            .modal({
                allowMultiple: true,
                closable: false,
                onShow: function(){
                    var $this = $(this);
                    initialLatitude = parseFloat($this.find('.initial-latitude').val());
                    initialLongitude = parseFloat($this.find('.initial-longitude').val());
                    var latlng = new google.maps.LatLng(initialLatitude, initialLongitude);
                    marker.setPosition(latlng);
                    window.setTimeout(function(){
                        addressMap.panTo(marker.getPosition());
                    }, 500);                                        
                },
                onApprove: function(){
                    var $this = $(this);
                    var markerPosition = marker.getPosition();
                    $('.latitude-input').val(markerPosition.lat());
                    $('.longitude-input').val(markerPosition.lng());

                }
            }).modal('attach events', '.new-address-modal .map-trigger', 'show')
              .modal('attach events', '.edit-address-modal .map-trigger', 'show');

        $(document).on("click", ".shipping-address-segment input[type='checkbox'], .shipping-address-segment .ui.checkbox", function(){

            var $this = $(this);
            var $checkbox = $(".checkbox");
            var $addressSegment = $(".shipping-address-segment");
            var $userAddressId = $this.find("input[name='isDefault']").data("id");

            //reset checkbox
            $checkbox.checkbox("uncheck");
            $this.checkbox("set checked");

           //reset active container
            $addressSegment.removeClass("active");
            $this.parents(".shipping-address-segment").addClass("active");

            if(!$ajaxLoading){
                $ajaxLoading = true;
                $.ajax({
                    url: Routing.generate("core_address_default"),
                    type: 'POST',
                    data: {userAddressId:$userAddressId},
                    success: function(response) {
                    },
                    complete: function(){
                        $ajaxLoading = false;
                    }
                });
            }
        });

        $('.mobile-change-profile-photo').on('click', function(){
            $('.mobile-change-profile-photo-input').click();
        });

        $(document).on("change", ".mobile-change-profile-photo-input", function(){
            var $image = this.files[0];
            
            var $formData = new FormData();
            var $promise = handleClientResize($image);



            $promise.done(function($object){
                
                var $src = resize($object);
                var $responseErrorBox = $(".message-box");

                var $image = document.createElement('img');

                $image.addEventListener('load', function(){

                    $formData.append("image", dataURItoBlob($src), $(".mobile-change-profile-photo-input")[0].files[0].name);
                    $formData.append("imageType", IMAGE_TYPE_AVATAR);
                    $formData.append("x", 0);
                    $formData.append("y", 0);
                    $formData.append("width", $image.width);
                    $formData.append("height", $image.height);

                    $.ajax({
                        url: Routing.generate('core_upload_user_photo'),
                        type: 'POST',
                        data: $formData,
                        processData: false,
                        contentType: false,
                        beforeSend: function() {
                        },
                        success: function(response) {
                            if(response.isSuccessful){
                                $('.mobile-change-profile-photo-input').val("");
                                $(".photo-placeholder").text("Edit Photo");
                                $(".user-image-profile > img").attr("src", response.data.mediumUrl);
                                $("img.mobile-change-profile-photo").attr("src", response.data.mediumUrl);
                                $("#user-avatar > img").attr("src", response.data.thumbnailUrl);
                            }
                        },
                        error: function(response){
                            var $responseJson = response.responseJSON;
                            var $errors = $responseJson.data.errors;
                            var $errorList = "<ul>";

                            $errors.forEach(function(value){
                                $errorList += "<li>" + value + "</li>"
                            });

                            $errorList += "</ul>";

                            $responseErrorBox.html($errorList);
                            $responseErrorBox.removeClass("hidden");
                        },
                        complete: function(){
                        }
                    });
                });

                $image.src = $src;
            });
        });

        $('.location-dropdown').dropdown({
            onChange: locationDrodownChange
        });

        $('form#add-referral-code').on('submit', function(e){
            e.preventDefault();
            var self = $(this),
                referralInfoContainer = $(".referral-info-container"),
                referralErrorElem = referralInfoContainer.find('.referral-error'),
                referralSuccessElem = referralInfoContainer.find('.referral-success'),
                addReferralButton = $("#add-referral");

            if (addReferralButton.length) {
                $.post(Routing.generate('profile_add_referrer_code'), self.serialize(), function(response) {
                    if (response.isSuccessful) {
                        referralSuccessElem.show();
                        referralErrorElem.hide();
                        addReferralButton.remove();
                        $("#referral-code").prop('readonly', true);
                        $("#referral-user").val(response.data.referrerName);
                    }
                    else {
                        referralSuccessElem.hide();
                        referralErrorElem.find('span').html(response.message);
                        referralErrorElem.show();
                    }
                });
            }
        });

        $(".new-address-modal-trigger").click(function(){

            $(".new-address-modal").modal({
                allowMultiple: true,
                closable: false,
                onShow : function() {
                    var $this = $(this);
                    var $addressModal = $('.address-map-modal');
                    var $newAddressModal = $(".new-address-modal");
                    
                    var latitude = defaultLatitude;
                    var longitude = defaultLongitude;

                    emptyAddressModal($newAddressModal);

                    if (navigator.geolocation) {
                        navigator.geolocation.getCurrentPosition(function(position) {
                            $addressModal.find('.initial-latitude').val(position.coords.latitude);
                            $addressModal.find('.initial-longitude').val(position.coords.longitude);
                            emptyAddressModal($this);
                        });
                    }
                    else{
                        emptyAddressModal($this);
                    }
                },
                onApprove : function() {
                    persistAddress(true, $(this))
                    return false;
                },
            }).modal("show");
        });

        $('.new-address-modal input, .new-address-modal select').on('change', function(){
            $(this).parent('div').removeClass('error');
        });

        $('.shipping-address-container').on('click', '.address-delete-btn', function(){
            var $button = $(this);
            var addressId = $button.closest('table').data('addressid');
            $('.profile-address-delete-prompt.error').hide();
            $(".delete-address-modal").modal({
                onApprove  : function(){
                    var $modal = $(this);
                    var $submitButton = $modal.find('.submit-to-success');
                    $.ajax({
                        type: "POST",
                        url: Routing.generate('profile_delete_address'),
                        data: {
                            'addressId': addressId,
                        },
                        beforeSend : function(){
                            $submitButton.html("<div class='ui active centered small inline inverted loader'>&nbsp;</div>").addClass("disabled");
                        },
                        success:function(data) {
                            $submitButton.html("Submit").removeClass('disabled');
                            if(data.isSuccessful){
                                $modal.modal('hide');
                                $containerToRemove = $('.shipping-address-table[data-addressid="'+addressId+'"]').closest('div.masonry-item');
                                $addressGrid.masonry( 'remove', $containerToRemove ).masonry({
                                    itemSelector: '.col-md-6.col-xl-4',
                                    columnWidth: '.col-md-6.col-xl-4',
                                    percentPosition: true,
                                    isResizeBound: true
                                });
                            }
                            else{
                                $('.profile-address-delete-prompt.error .message-box').text(data.message);
                                $('.profile-address-delete-prompt.error').show();
                            }
                        },
                        complete: function(){
                            $('.shipping-address-container').masonry({
                                itemSelector: '.col-md-6.col-xl-4',
                                columnWidth: '.col-md-6.col-xl-4',
                                percentPosition: true,
                                isResizeBound: true
                            });
                        }
                    });

                    return false;
                }
            }).modal("show");

        });

        $('.request-verify-mobile').click(function(){
            var $button = $(this);
            
            if(!$ajaxLoading){
                $ajaxLoading = true;

                var contactNumberVerifyType = $('#contact-number-verify-type').val();
                var contactNumber = $('.profile-input-contactnumber').val();
                var buttonText = "";
                
                $.ajax({
                    url: Routing.generate('core_send_token'),
                    method: 'POST',
                    data:{
                        'contactNumber': contactNumber,
                        'type': contactNumberVerifyType,
                    },
                    beforeSend: function(){
                        buttonText = $button.html();
                        $button.html('Please wait ...');
                    },
                    success: function(response) {
                        if(response.isSuccessful){
                            var expiration = new Date(response.data.expiration.replace(/-/g,"/"));
                            var sentOn = new Date(response.data.sentOn.replace(/-/g,"/"));
                            var expirationInMillis = expiration.getTime() - sentOn.getTime();
                            openContactNumberVerificationModal(expirationInMillis, contactNumber, contactNumberVerifyType);
                        }
                        else{
                            var otpErrorModal = $(".otp-cooldown-error-modal");
                            otpErrorModal.find('.error-message').html(response.message);
                            otpErrorModal.modal("show");
                        }
                        $ajaxLoading = false;       
                    },
                    error: function(response){
                        $ajaxLoading = false;
                        var data = $.parseJSON(response.responseText);
                        var otpErrorModal = $(".otp-cooldown-error-modal");
                        otpErrorModal.find('.error-message').html(data.message);
                        otpErrorModal.modal("show");
                    },
                    complete: function(){
                        $button.html(buttonText);
                    }
                });
            }
        });

        $('.request-verify-email').click(function(){
            if(!$ajaxLoading){
                $ajaxLoading = true;

                $.ajax({
                    url: Routing.generate('core_resend_email_verification'),
                    success: function(response) {
                        if(response.isSuccessful){
                            $(".success-resend-email-verification").modal("show");
                        }
                        $ajaxLoading = false;       
                    },
                    error: function(response){
                        $ajaxLoading = false;
                    }
                });
            }
        });

        $('.shipping-address-container').on("click", ".shipping-address-segment .edit", function(){
            var $addressTable = $(this).closest('table');

            $(".edit-address-modal").modal({
                allowMultiple: true,
                closable: false,
                onShow : function(){

                    var $modal = $(this);
                    emptyAddressModal($modal);
                    var $citySelect = $modal.find('.city-input');
                    var $provinceSelect = $modal.find('.province-input');
                    var $barangaySelect = $modal.find('.barangay-input');
                    var $latitudeInput = $modal.find('.latitude-input');
                    var $longitudeInput = $modal.find('.longitude-input');

                    var $addressTitle = $modal.find('.title-input');
                    var $unitNumber = $modal.find('.unitnumber-input');
                    var $buildingName = $modal.find('.buildingname-input');
                    var $streetNumber = $modal.find('.streetnumber-input');
                    var $streetName = $modal.find('.streetname-input');
                    var $subdivision = $modal.find('.subdivision-input');
                    var $zipCode = $modal.find('.zipcode-input');
                    var $addressId = $modal.find('.addressid-input');

                    $addressId.val($addressTable.attr('data-addressid'));
                    $addressTitle.val($addressTable.attr('data-title'));
                    $streetName.val($addressTable.attr('data-streetname'));
                    $streetNumber.val($addressTable.attr('data-streetnumber'));
                    $unitNumber.val($addressTable.attr('data-unitnumber'));
                    $buildingName.val($addressTable.attr('data-buildingname'));
                    $subdivision.val($addressTable.attr('data-subdivision'));

                    $zipCode.val($addressTable.attr('data-zipcode'));
                    var locationTree = $.parseJSON($addressTable.attr('data-locationtree'));

                    $provinceSelect.addClass('disable-onchange');
                    $provinceSelect.dropdown('set selected', locationTree.province.locationId);
                    var cityQueryPromise = queryLocationChildren($provinceSelect);
                    cityQueryPromise.done(function(){
                        $provinceSelect.removeClass('disable-onchange');
                        $citySelect.dropdown('refresh');
                        $citySelect.addClass('disable-onchange');
                        $citySelect.dropdown('set selected', locationTree.city.locationId);
                        var barangayQueryPromise = queryLocationChildren($citySelect);
                        barangayQueryPromise.done(function(){
                            $citySelect.removeClass('disable-onchange');
                            if(typeof locationTree.barangay !== 'undefined'){
                                $barangaySelect.dropdown('refresh');
                                $barangaySelect.dropdown('set selected', locationTree.barangay.locationId);
                            }
                        });
                    });

                    var latitude = parseFloat($addressTable.attr('data-latitude'));
                    var longitude = parseFloat($addressTable.attr('data-longitude'));
                    $latitudeInput.val(latitude);
                    $longitudeInput.val(longitude);
                    if(!isNaN(latitude) && !isNaN(longitude)){
                        var $addressMapModal = $('.address-map-modal');
                        $addressMapModal.find('.initial-latitude').val(latitude);
                        $addressMapModal.find('.initial-longitude').val(longitude);
                    }

                },
                onApprove : function(){
                    persistAddress(false, $(this));
                    return false;
                }
                
            }).modal("show");
            
        });

        function persistAddress($isCreate, $addressModal)
        {
            var $submitButton = $addressModal.find('.submit-address');
            var $streetName = $addressModal.find('.streetname-input');
            var $zipCode = $addressModal.find('.zipcode-input');
            var $addressTitle = $addressModal.find('.title-input');
            var $citySelect = $addressModal.find('.city-input');
            var $provinceSelect = $addressModal.find('.province-input');
            var $barangaySelect = $addressModal.find('.barangay-input');
           
            var unitNumber = $addressModal.find('.unitnumber-input').val().trim();
            var buildingName = $addressModal.find('.buildingname-input').val().trim();
            var streetNumber = $addressModal.find('.streetnumber-input').val().trim();
            var subdivision = $addressModal.find('.subdivision-input').val().trim();
            var addressTitle = $addressTitle.val().trim();
            var streetName = $streetName.val().trim();
            var zipCode = $zipCode.val().trim();
            var latitude = $addressModal.find('.latitude-input').val().trim();
            var longitude = $addressModal.find('.longitude-input').val().trim();

            var province = parseInt($provinceSelect.dropdown('get value'),10);
            var city = parseInt($citySelect.dropdown('get value'),10);
            var barangay = parseInt($barangaySelect.dropdown('get value'),10);
            
            var hasError = false;
            if(streetName === ""){
                $streetName.parent('div').addClass('error');
                hasError = true;
            }
            if(city === 0 || isNaN(city)){
                $citySelect.parent('div').addClass('error');
                hasError = true;
            }
            if(province === 0 || isNaN(province)){
                $provinceSelect.parent('div').addClass('error');
                hasError = true;
            }
            if(barangay === 0 || isNaN(barangay)){
                $barangaySelect.parent('div').addClass('error');
                hasError = true;
            }
            if(addressTitle === ""){
                $addressTitle.parent('div').addClass('error');
                hasError = true;
            }

            if(hasError){
                return false;
            }

            var addressId = $('.addressid-input').val();
            var locationId = barangay;
            if(locationId === 0 || isNaN(locationId)){
                locationId = city;
            }

            $.ajax({
                type: "POST",
                url: $isCreate ? Routing.generate('profile_create_address') : Routing.generate('profile_update_address'),
                data: {
                    'locationId': locationId,
                    'addressTitle': addressTitle,
                    'unitNumber': unitNumber,
                    'buildingName': buildingName,
                    'streetNumber' : streetNumber,
                    'streetName' : streetName,
                    'subdivision' : subdivision,
                    'zipCode' : zipCode,
                    'addressId' : addressId,
                    'latitude' : latitude,
                    'longitude' : longitude
                },
                beforeSend : function(){
                    $submitButton.html("<div class='ui active centered small inline inverted loader'>&nbsp;</div>").addClass("disabled");
                },
                success:function(data) {
                    $submitButton.html("Submit").removeClass('disabled');
                    if(data.isSuccessful){

                        var $emptyAddressContainer = $('#empty-address-container').clone();
                        var $addressTable = $isCreate ? $emptyAddressContainer.find('table') : 
                            $('.shipping-address-table[data-addressid="'+data.data.address_id+'"]');
                        
                        $addressTable.attr('data-addressid', data.data.address_id);
                        $addressTable.attr('data-unitnumber', data.data.unitNumber);
                        $addressTable.attr('data-buildingname', data.data.buildingName);
                        $addressTable.attr('data-streetnumber', data.data.streetNumber);
                        $addressTable.attr('data-streetname', data.data.streetName);
                        $addressTable.attr('data-subdivision', data.data.subdivision);
                        $addressTable.attr('data-zipcode', data.data.zipCode);
                        $addressTable.attr('data-title', data.data.title);
                        $addressTable.attr('data-locationtree', JSON.stringify(data.data.locationTree));
                        $addressTable.find('.item-name').html(data.data.title);
                        $addressTable.find('.item-address-line').html(data.data.fullAddress);
                        $addressTable.find('input[name="isDefault"]').attr("data-id", data.data.address_id);
                        $addressTable.attr('data-latitude', data.data.latitude);
                        $addressTable.attr('data-longitude', data.data.longitude);
                        var $isDefaultCheckbox = $addressTable.find('.checkbox');
                        var $shippingAddressBorder = $emptyAddressContainer.find('.shipping-address-segment');
                        if(data.data.isDefault){
                            $isDefaultCheckbox.checkbox('set checked');
                            $shippingAddressBorder.addClass('active');
                        }
                        else{
                            $isDefaultCheckbox.checkbox('set unchecked');
                            $shippingAddressBorder.removeClass('active');
                        }

                        if($isCreate){
                            $(".success-new-address-message").modal('show');
                            $addressModal.modal('hide');
                            $emptyAddressContainer.removeAttr('id');               
                            $emptyAddressContainer.css('display', 'block');
                            $addressGrid.find(".row").append($emptyAddressContainer);
                            $addressGrid.masonry('appended', $emptyAddressContainer);
                            $emptyAddressContainer.find('.ellipsis-dropdown').dropdown();
                        }
                        else{
                            $(".edit-success-address-modal").modal('show');
                            $addressGrid.masonry();
                        }
                    }
                    else{
                        var $errorPromptContainer = $addressModal.find('.address-prompt.error');
                        $errorPromptContainer.find('.message-box').html(data.message);
                        $errorPromptContainer.show();
                    }
                },
                complete: function(){
                    $('.shipping-address-container').masonry({
                        itemSelector: '.col-md-6.col-xl-4',
                        columnWidth: '.col-md-6.col-xl-4',
                        percentPosition: true,
                        isResizeBound: true
                    });
                }
            });
        }

        
    });
    
    function emptyAddressModal($modalContainer)
    {
        $modalContainer.find('.address-prompt').hide();
        $modalContainer.find('.coordinate').val('');
        $modalContainer.find('input').val('');
        $modalContainer.find('input').val('');
        $modalContainer.find('input,div.dropdown').parent('div')
                       .removeClass('error');
        $modalContainer.find('.location-dropdown')
                       .dropdown('clear')
                       .dropdown('restore defaults')
                       .dropdown('refresh');
    }

    $('.change-contactnumber-form, .verify-contact-number-form').on('submit', function(e){
        e.preventDefault();
    });

    $('.change-contactnumber-form input').on('change', function(){
        $(this).parent('div').removeClass('error');
    });

    $('.edit-mobile-phone-number-modal-trigger').on('click', function (e){
        var $newContactNumberInput = $('input.new-contact-number');
        var $errorPrompt = $('.contact-number-prompt');
        var csrftoken = $("meta[name=csrf-token]").attr("content");
        
        $(".edit-mobile-phone-number-modal").modal({
            onShow : function(){
                var $inputs = $('.change-contactnumber-form input');
                $inputs.parent('div').removeClass('error');
                $inputs.val('');
                $errorPrompt.hide();
            },
            onApprove : function(){
                var $submitButton = $('.edit-mobile-phone-number-modal .submit-to-verify');
                var newContactNumber = $.trim($newContactNumberInput.val());
                if(newContactNumber === ""){
                    $newContactNumberInput.parent('div').addClass('error');
                    return false;
                }

                $.ajax({
                    type: "POST",
                    url: Routing.generate('core_change_contact_number_request'),
                    data: {
                        'newContactNumber': newContactNumber
                    },
                    beforeSend : function(){
                        $submitButton.html("<div class='ui active centered small inline inverted loader'>&nbsp;</div>").addClass("disabled");
                    },
                    success : function(data) {
                        if(data.isSuccessful){
                            var expirationInMillis = data.data.expiration_in_minutes * 60 * 1000;
                            $(".verified-mobile-number").addClass("hidden");
                            $(".unverified-mobile-number").removeClass("hidden");
                            openContactNumberVerificationModal(expirationInMillis, newContactNumber);
                            $('.profile-input-contactnumber').val(newContactNumber);
                        }
                        else{
                            $errorPrompt.find('.message-box').html(data.message);
                            $errorPrompt.show();
                        }
                    },
                    error: function(response){
                        var $responseJson = response.responseJSON;
                        var $errors = $responseJson.data.errors;
                        var $errorList = "<ul>";

                        $errors.forEach(function(value){
                            $errorList += "<li>" + value + "</li>"
                        });

                        $errorList += "</ul>";

                        if($errors.length > 0){
                            $errorPrompt.find('.message-box').html($errorList);
                            $errorPrompt.show();
                        }
                    },
                    complete: function( xhr ) {
                        $submitButton.html("Submit").removeClass('disabled');
                    }
                });

                return false;
            }
        }).modal("show");
    });

    function openContactNumberVerificationModal(endCountDownInMillis, newContactNumber, type)
    {
        var csrftoken = $("meta[name=csrf-token]").attr("content");
        var currentDate = new Date();
        var endDate = new Date(currentDate.getTime() + endCountDownInMillis);
        type = typeof type !== "undefined" ? type : "change-contact-number";       
        $('.time-limit').countdown(endDate, function(event) {
            $(this).text(
                event.strftime('%M:%S')
            );
        }).on('finish.countdown', function(event) {
            window.location.reload();
        });

        var $verificationCodeInput = $('.verification-code-input');
        var $errorPrompt = $('.contact-verify-prompt');
        $('.verify-contact-number-modal').modal({
            onShow : function(){
                $verificationCodeInput.val('');
                $errorPrompt.hide();
            },
            onApprove : function(){
                var $submitButton = $('.verify-contact-number-modal .approve');
                var verificationCode = $verificationCodeInput.val();
                if(verificationCode === ""){
                    $verificationCodeInput.parent('div').addClass('error');
                    $errorPrompt.show().delay(3000).fadeOut();
                    return false;
                }
                
                $.ajax({
                    type: "POST",
                    url: Routing.generate('core_validate_token'),
                    data: {
                        'contactNumber' : newContactNumber,
                        'verificationCode': verificationCode,
                        'type' : type,
                    },
                    beforeSend : function(){
                        $submitButton.addClass('disabled');
                    },
                    success : function(data) {        
                        $(".verified-mobile-number").removeClass("hidden");
                        $(".unverified-mobile-number").addClass("hidden");
                        $('.success-change-contact-number-modal').modal("show");
                    },
                    error: function(data) {
                        $verificationCodeInput.val('');
                        $errorPrompt.show().delay(3000).fadeOut();
                    },
                    complete: function( xhr ) {
                        $submitButton.removeClass('disabled');
                    }
                });
                
                return false;
            }
        }).modal('show');

    }


    $('.profile-modal-input').keyup(function(e){
        var $this = $(this);
        $this.closest('.profile-modal').find('.profile-change-prompt.error').hide();
        $this.parent('div').removeClass('error');
    })
    
    $('.dashboard-body-container').on('click', ".profile-information-save:not(.disabled)", function (e){

        var $button = $(this);
        var $errorPrompt = $('.profile-information-prompt.error');
        var $lastNameInput =  $('.info-lastname');
        var $firstNameInput =  $('.info-firstname');

        var lastname = $lastNameInput.val().trim();
        var firstname = $firstNameInput.val().trim();

        $('.profile-information-prompt').hide();
        $('.profile-info-input').parent('div').removeClass('error');

        var $defaultAddressContainer =  $('.address-default-checkbox:checked').closest('.shipping-address-table');
        var defaultAddressId = $defaultAddressContainer.attr('data-addressid');

        var hasError = false;
        if(lastname.length === 0){
            hasError = true;
            $lastNameInput.parent('div').addClass('error');
        }

        if(firstname.length === 0){
            hasError = true;
            $firstNameInput.parent('div').addClass('error');
        }

        if(typeof defaultAddressId == 'undefined'){
            hasError = true;
            $errorPrompt.find('.message-box').html('A default address must be set');
            $errorPrompt.show().delay(3000).fadeOut();
        }

        if(hasError){
            $('html, body').animate({ scrollTop: $("#basicInfo").offset().top + (-85) }, 500);
            return false;
        }

        var originalButtonText = $button.text();
        $.ajax({
            type: "POST",
            url: Routing.generate('profile_info_update'),
            data: {
                'lastName': lastname,
                'firstName': firstname,
                'defaultAddressId': defaultAddressId,
                'country': $('[name="country"]').val(),
                'language': $('[name="language"]').val()
            },
            beforeSend : function(){
                $button.html("<div class='ui active centered small inline inverted loader'>&nbsp;</div>").addClass("disabled");
            },
            success : function(data) {
                if(data.isSuccessful){
                    $('.profile-information-prompt.success').show();
                    var $addressItem = $defaultAddressContainer.closest('.masonry-item');
                    var $addressContainer = $('.shipping-address-container');
                    var $clonedAddressitem = $addressItem.clone();
                    $addressGrid.masonry('remove', $addressItem);
                    $addressGrid.find(".row").prepend($clonedAddressitem);
                    $addressGrid.masonry('prepended', $clonedAddressitem, true);
                    $('.ellipsis-dropdown').dropdown();
                    $('[data-fullname]').text(firstname+' '+lastname);
                }
                else{
                    $errorPrompt.find('.message-box').html(data.message);
                    $errorPrompt.show();
                }
                $('html, body').animate({ scrollTop: 0 }, 500);
            },
            complete: function( xhr ) {
                $button.html("Save Changes").removeClass('disabled');
            }
        });
    });

    $(".edit-email-trigger").click(function(){
        $(".edit-email-modal").modal({
            onShow : function(){
                var $modal = $(this);
                var $inputs = $modal.find('.profile-modal-input');
                $inputs.parent('div').removeClass('error');
                $inputs.val('');
                $modal.find('.profile-change-prompt.error').hide();
            },
            onApprove : function(){
                var $modal = $(this);
                var $submitButton = $modal.find('.confirm');
                var $emailInput =  $('.email-input');
                var $confirmEmailInput =  $('.email-confirm-input');
                var email = $emailInput.val().trim();
                var confirmEmail = $confirmEmailInput.val().trim();
                var hasError = false;

                var $errorPromptContainer = $modal.find('.profile-change-prompt.error');
                var $errorMessageBox = $errorPromptContainer.find('.message-box');

                if(email === "" || isEmailValid(email) === false){
                    $errorPromptContainer.show();
                    $errorMessageBox.html('Invalid email address.');
                    $emailInput.parent('div').addClass('error');
                    hasError = true;
                }
                
                if(confirmEmail === "" || isEmailValid(confirmEmail) === false){
                    $errorPromptContainer.show();
                    $errorMessageBox.html('Invalid email address.');
                    $confirmEmailInput.parent('div').addClass('error');
                    hasError = true;
                }
                if(hasError){
                   return false;
                }

                if(email !== confirmEmail){
                    $errorPromptContainer.show();
                    $errorMessageBox.html('The email you entered must match.');

                    return false;
                }

                $.ajax({
                    type: "POST",
                    url: Routing.generate('profile_change_email'),
                    data: {
                        'email': email,
                    },
                    beforeSend : function(){
                        $submitButton.html("<div class='ui active centered small inline inverted loader'>&nbsp;</div>").addClass("disabled");
                    },
                    success : function(response) {
                        $(".unverified-email").addClass("hidden");
                        $(".verified-email").removeClass("hidden");
                        $submitButton.html("Submit").removeClass('disabled');
                        if(response.isSuccessful){
                            $('.edit-email-trigger').html('Change');
                            $('.missing-email-message').hide();
                            $('.verify-email-btn').show();
                            $('.email-readonly-input').val(email);
                            $('.email-update-modal').modal('show');
                        }
                        else{
                            $errorMessageBox.html(response.message);
                            $errorPromptContainer.show();
                        }
                    },
                    error : function(response){
                        var $response = response.responseJSON;
                        $errorMessageBox.html($response.message);
                        $errorPromptContainer.show();
                    },
                    complete: function(){
                        $submitButton.html("Submit").removeClass('disabled');
                    }
                });
                return false;
            },
        }).modal("show");
    });

                    
    $(".edit-password-trigger").click(function(){
        $(".edit-password-modal").modal({
            onShow : function(){
                var $modal = $(this);
                var $inputs = $modal.find('.profile-modal-input');
                $inputs.parent('div').removeClass('error');
                $inputs.val('');
                $modal.find('.profile-change-prompt.error').hide();
            },
            onApprove : function(){               
                var $modal = $(this);
                var $submitButton = $modal.find('.confirm');
                var $currentPasswordInput =  $('.current-password-input');
                var $newPasswordInput =  $('.new-password-input');
                var $confirmNewPasswordInput =  $('.confirm-password-input');

                var currentPassword = $currentPasswordInput.val().trim();
                var newPassword = $newPasswordInput.val().trim();
                var confirmNewPassword = $confirmNewPasswordInput.val().trim();
                var hasError = false;

                var $errorPromptContainer = $modal.find('.profile-change-prompt.error');
                var $errorMessageBox = $errorPromptContainer.find('.message-box');

                if(currentPassword === ""){
                    $currentPasswordInput.parent('div').addClass('error');
                    hasError = true;
                }
                if(newPassword === ""){
                    $newPasswordInput.parent('div').addClass('error');
                    hasError = true;
                }
                if(confirmNewPassword === ""){
                    $confirmNewPasswordInput.parent('div').addClass('error');
                    hasError = true;
                }
                if(hasError){
                    return false;
                }

                $.ajax({
                    type: "POST",
                    url: Routing.generate('profile_change_password'),
                    data: {
                        'oldPassword': currentPassword,
                        'newPassword': newPassword,
                        'confirmNewPassword': confirmNewPassword
                    },
                    beforeSend : function(){
                        $submitButton.html("<div class='ui active centered small inline inverted loader'>&nbsp;</div>").addClass("disabled");
                    },
                    success : function(response) {
                        $submitButton.html("Submit").removeClass('disabled');
                        if(response.isSuccessful){
                            $('.password-update-modal').modal('show');
                            setTimeout(function() {
                                window.location = Routing.generate('user_buyer_login');
                            }, 2500 );
                        }
                        else{
                            $modal.find('.profile-modal-input').val('');
                            $errorMessageBox.html(response.message);
                            $errorPromptContainer.show();
                        }
                    },
                    error : function(response){
                        var $response = response.responseJSON;
                        $errorMessageBox.html($response.message);
                        $errorPromptContainer.show();
                    },
                    complete: function(){
                        $submitButton.html("Submit").removeClass('disabled');
                    }
                    
                });
                return false;
            },
        }).modal("show");
    });

    function queryLocationChildren($locationDropdown)
    {
        locationId = $locationDropdown.dropdown('get value');
        var deferredObject = $.Deferred();
        if(locationId == 0){
            return deferredObject.promise();
        }
        if(!(locationId in locations)){
            $.ajax({
                type: "POST",
                url: Routing.generate('profile_location_children'),
                data: {
                    'locationId': locationId,
                },
                success : function(response) {
                    locations[locationId] = response.data.locations;
                    updateChildrenLocationDropdown($locationDropdown, deferredObject);
                }
            });
        }
        else{
            updateChildrenLocationDropdown($locationDropdown, deferredObject);
        }
        return deferredObject.promise();
    }

    function updateChildrenLocationDropdown($triggerContainer, deferredObject)
    {
        var locationId = $triggerContainer.dropdown('get value');
        var $addressModal = $triggerContainer.closest('.address-modal-container');

        var optionsStrings = "";
        $.each(locations[locationId], function(key, value){
            optionsStrings = optionsStrings + "<div class='item' data-value='"+value.locationId+"'>"+value.location+"</div>";
        });

        if($triggerContainer.hasClass('province-input')){
            var $citySelector = $addressModal.find('.city-input');
            var $barangaySelector = $addressModal.find('.barangay-input');
            $citySelector.dropdown('restore defaults');
            $citySelector.dropdown('refresh');
            $citySelector.find('.menu').html(optionsStrings);
            $barangaySelector.dropdown('restore defaults');
            $barangaySelector.dropdown('refresh');
            $barangaySelector.find('.menu').empty();
        }
        else if($triggerContainer.hasClass('city-input')){
            var $barangaySelector = $addressModal.find('.barangay-input');
            $barangaySelector.dropdown('restore defaults');
            $barangaySelector.dropdown('refresh');
            $barangaySelector.find('.menu').html(optionsStrings);
        }
        
        if(typeof deferredObject !== 'undefined'){
            deferredObject.resolve();
        }
    }

    $(".verify-contact-number-from-checkout-register-trigger").on("click", function(){
        $(".verify-contact-number-from-checkout-register-modal").modal("show");
    });

     $(".verify-email-from-checkout-register-trigger").on("click", function(){
        $(".verify-email-from-checkout-register-modal").modal("show");
    });
})(jQuery);
