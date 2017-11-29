(function ($) {
    var urlRestriction = new RegExp("^(http|https|ftp)\://([a-zA-Z0-9\.\-]+(\:[a-zA-Z0-9\.&amp;%\$\-]+)*@)*((25[0-5]|2[0-4][0-9]|[0-1]{1}[0-9]{2}|[1-9]{1}[0-9]{1}|[1-9])\.(25[0-5]|2[0-4][0-9]|[0-1]{1}[0-9]{2}|[1-9]{1}[0-9]{1}|[1-9]|0)\.(25[0-5]|2[0-4][0-9]|[0-1]{1}[0-9]{2}|[1-9]{1}[0-9]{1}|[1-9]|0)\.(25[0-5]|2[0-4][0-9]|[0-1]{1}[0-9]{2}|[1-9]{1}[0-9]{1}|[0-9])|([a-zA-Z0-9\-]+\.)*[a-zA-Z0-9\-]+\.(com|edu|gov|int|mil|net|org|biz|arpa|info|name|pro|aero|coop|museum|[a-zA-Z]{2}))(\:[0-9]+)*(/($|[a-zA-Z0-9\.\,\?\'\\\+&amp;%\$#\=~_\-]+))*$");
    var twitterUrlRestriction = /^(https?:\/\/)?((w{3}\.)?)twitter\.com\/(#!\/)?[a-z0-9_]+$/i;
    var googleUrlRestriction = /^(https?:\/\/)?((w{3}\.)?)plus.google.com\/.*/i;
    var facebookUrlRestriction = /^(https?:\/\/)?((w{3}\.)?)facebook.com\/.*/i;
    var slugCharacterRestriction = /^[a-zA-z0-9 _\-'.,]*$/;
    var numericAndDashOnly = /^(\d+-?)+\d+$/;
    var arrayOfAddress = [];
    var locations = [];
    var partialAddressId = 0;
    var socialMediaFacebookTypeId = $('#social-media-type-facebook').val();
    var socialMediaGoogleTypeId = $('#social-media-type-google').val();
    var socialMediaTwitterTypeId = $('#social-media-type-twitter').val();
    var $messageModal = $('#modal-message');
    var isAddressEdited = false;
    var $selectedStoreCategoryIds = [];

    var successfullyAddedAddressMsgHead = 'Address has been successfully added';
    var successfullyAddedAddressMsgDetail = 'An entry has been added to your available addresses. ';

    var successUpdateMessageHeader = 'Address has been successfully updated';
    var successUpdateMessageDetail = '';

    var successRemovedMessageHeader = 'Address has been successfully Removed';
    var successRemovedMessageDetail = '';

    var successSaveMessageHeader = 'Business Information Successfully saved.';
    var successSaveMessageDetail = '';

    var formRules = {
        title: {
            identifier  : 'txt-title',
            rules: [
                {type: 'empty'},
                {type: 'maxLength[255]'}
            ]
        },
        unitNumber: {
            identifier  : 'txt-unit-number',
            rules: [
                {type: 'maxLength[45]'}
            ]
        },
        buildingName: {
            identifier  : 'txt-building-name',
            rules: [
                {type: 'maxLength[255]'}
            ]
        },
        streetNumber: {
            identifier  : 'txt-street-number',
            rules: [
                {type: 'maxLength[25]'}
            ]
        },
        streetName: {
            identifier  : 'txt-street-name',
            rules: [
                {type: 'empty'},
                {type: 'maxLength[255]'}
            ]
        },
        subdivision: {
            identifier  : 'txt-subdivision',
            rules: [
                {type: 'maxLength[255]'}
            ]
        },
        province: {
            identifier  : 'drop-down-province',
            rules: [
                {type: 'empty'},
                {type: 'number'}
            ]
        },
        city: {
            identifier  : 'drop-down-city',
            rules: [
                {type: 'empty'},
                {type: 'number'}
            ]
        },
        barangay: {
            identifier  : 'drop-down-barangay',
            rules: [
                {type: 'empty'},
                {type: 'number'}
            ]
        },
        zipCode: {
            identifier  : 'txt-zip-code',
            rules: [
                {type: 'maxLength[45]'},
                {type: 'number'}
            ]
        }
    };

    $(document).ready(function () {

        //START OF ADDRESS RELATED CODE
        $(document).find('#form-address').form({
            on: 'blur',
            fields: formRules,
            onSuccess: function (e)
            {
                e.preventDefault();
                var $form = $(this);
                var addressTitle = $form.form('get value', 'txt-title');
                var unitNumber = $form.form('get value', 'txt-unit-number');
                var buildingName = $form.form('get value', 'txt-building-name');
                var streetNumber = $form.form('get value', 'txt-street-number');
                var streetName = $form.form('get value', 'txt-street-name');
                var subdivision = $form.form('get value', 'txt-subdivision');
                var provinceId = $form.form('get value', 'drop-down-province');
                var cityId = $form.form('get value', 'drop-down-city');
                var barangayId = $form.form('get value', 'drop-down-barangay');
                var zipCode = $form.form('get value', 'txt-zip-code');
                var locationId = barangayId;

                if(parseInt(locationId) === 0 || isNaN(locationId) || locationId === ''){
                    locationId = cityId;
                }

                processAddress (
                    addressTitle, unitNumber,
                    buildingName, streetNumber,
                    streetName, subdivision,
                    locationId, zipCode,
                    provinceId, cityId,
                    barangayId, $('#user-address-id').val()
                );
            }
        });

        $(document).on('click', '#trigger-modal-address', function () {
            cleanAddressModal ();
            $('#user-address-id').val('');
            $('#modal-address').modal('show');
        });

        $(document).on('change', '.drop-down-location', function () {
            getLocationChildren ($(this));
        });

        $(document).on('click', '#store-address-collection .delete', function () {
            var addressId = $(this).attr('data-id');
            var updatedAddressDetail = getAddress(addressId);

            if (updatedAddressDetail.isDefault === true) {
                $messageModal.find('.header-content').html('Unable to Remove Default Address.');
                $messageModal.find('.sub-header-content').html('');
                $messageModal.modal('show');
                return false;
            }

            $('#modal-confirm-delete')
                .modal({
                    onApprove: function () {

                        removeAddressInArray (addressId);

                        if (!updatedAddressDetail.isNew) {
                            $('.address-id-' + addressId).remove();

                            arrayOfAddress.push({
                                id: addressId,
                                isNew: false,
                                isRemoved: true,
                                isChanged: false
                            });
                        }
                        else {
                            $('.' + addressId).remove();
                        }

                        $messageModal.find('.header-content').html(successRemovedMessageHeader);
                        $messageModal.find('.sub-header-content').html(successRemovedMessageDetail);
                    }
                })
                .modal('show');
        });

        $(document).on('click', '#store-address-collection .edit', function () {
            cleanAddressModal ();
            var addressId = $(this).attr('data-id');
            var addressDetail = getAddress(addressId);

            var $addressForm = $('#form-address');
            $addressForm.form('clear');

            $addressForm.form('set values', {
                'txt-title': addressDetail.addressTitle,
                'txt-unit-number': addressDetail.unitNumber,
                'txt-building-name': addressDetail.buildingName,
                'txt-street-number': addressDetail.streetNumber,
                'txt-street-name': addressDetail.streetName,
                'txt-subdivision': addressDetail.subdivision,
                'txt-zip-code': addressDetail.zipCode
            });

            var $dropDownProvince = $addressForm.find('.province-input');
            var $dropDownCity = $addressForm.find('.city-input');
            var $dropDownBarangay = $addressForm.find('.barangay-input');

            $dropDownProvince.dropdown('set selected', addressDetail.provinceId);
            var cityQueryPromise = getLocationChildren($dropDownProvince);
            cityQueryPromise.done(function() {
                $dropDownCity.dropdown('set selected', addressDetail.cityId);
                var barangayQueryPromise = getLocationChildren($dropDownCity);
                barangayQueryPromise.done(function() {
                    if (typeof addressDetail.barangayId !== 'undefined' || addressDetail.barangayId == null) {
                        $dropDownBarangay.dropdown('set selected', addressDetail.barangayId);
                    }
                });
            });

            $('#user-address-id').val(addressId);
            $('#modal-address').modal('show');
        });

        $(document).on('click', '.check-box-is-default', function (e) {
            var $this = $(this);
            var addressId = $this.attr('data-id');
            $('.check-box-is-default').removeAttr('checked');
            $('.store-address-segment').removeClass('active');
            $('.check-box-div').removeClass('checked');

            var $container = $this.closest('.store-address-segment');
            $container.addClass('active');
            $container.find('.checkbox').addClass('checked');
            $this.prop('checked', true);

            setDefaultAddress (addressId);
        });

        prepareData ();
        //END OF ADDRESS RELATED CODE

        $(document).on('click', '.btn-delete-social-media-account', function () {
            $(this).parent().find('input').val('');
        });

        $(document).on('click', '#btn-submit-business-information', function () {
            var $messageModal = $('#modal-message');
            var $this = $(this);
            var $onlineStoreDiv = $('#online-store-div');
            var accreditationId = $('#application-accreditation-id').val();
            var sellerType = $('#store-seller-type').val();
            var storeName = $('input[name=txt-user-store-name]').val();
            var websiteUrl = $onlineStoreDiv.find('input[name=txt-website-url]').val().trim();
            var facebookUrl = $onlineStoreDiv.find('input[name=social-media-' + socialMediaFacebookTypeId + ']');
            var googleUrl = $onlineStoreDiv.find('input[name=social-media-' + socialMediaGoogleTypeId + ']');
            var twitterUrl = $onlineStoreDiv.find('input[name=social-media-' + socialMediaTwitterTypeId + ']');
            var company = $('input[name=txt-user-company]').val().trim();
            var job = $('input[name=txt-user-job]').val().trim();
            var tin = $('input[name=txt-user-tin]').val().trim();
            var storeSlug = $('input[name=storeSlug]').val().trim();
            var hasDefault = false;
            var selectedStoreCategoryIds = [];
            var $storeCategoryIds = $('input[name=store-category-id]:checked').each(function() {selectedStoreCategoryIds.push($(this).val());});
            var affiliateStoreType = $('#store-type-affiliate').val();
            var merchantStoreType = $('#store-type-merchant').val();
            var slugPattern = /^[a-z0-9]+(?:-[a-z0-9]+)*$/;

            if (arrayOfAddress.length > 0) {
                $.each(arrayOfAddress, function (key, value) {

                    if (arrayOfAddress[key]['isDefault'] == true) {
                        hasDefault = true;
                        return false;
                    }

                });
            }

            if (typeof sellerType === 'undefined') {
                $messageModal.find('.header-content').html('Kindly fill up all the required fields to continue.');
                $messageModal.find('.sub-header-content').html('');
                $messageModal.modal('show');
                return;
            }
            else if (storeName.trim() === '') {
                $messageModal.find('.header-content').html('Specify Store Name to continue');
                $messageModal.find('.sub-header-content').html('');
                $messageModal.modal('show');
                return;
            }
            else if (storeSlug.trim() === '' || !slugPattern.test(storeSlug.trim())) {
                $messageModal.find('.header-content').html('Store link is invalid');
                $messageModal.find('.sub-header-content').html('Store link should only contain numbers and lower case letters.');
                $messageModal.modal('show');
                return;
            }
            else if (company.trim() === '') {
                $messageModal.find('.header-content').html('Specify Company / School to continue');
                $messageModal.find('.sub-header-content').html('');
                $messageModal.modal('show');
                return;
            }
            else if (job.trim() === '') {
                $messageModal.find('.header-content').html('Specify Current Job / Course & Year Level to continue');
                $messageModal.find('.sub-header-content').html('');
                $messageModal.modal('show');
                return;
            }
            else if (tin.trim() === '') {
                $messageModal.find('.header-content').html('Specify TIN to continue');
                $messageModal.find('.sub-header-content').html('');
                $messageModal.modal('show');
                return;
            }
            else if (websiteUrl !== '' && urlRestriction.test(websiteUrl) === false) {
                $messageModal.find('.header-content').html('Invalid Website URL');
                $messageModal.find('.sub-header-content').html('Fill up all required fields under Business Website and Social Media Account');
                $messageModal.modal('show');
                return;
            }
            else if (facebookUrl.val().trim() !== '' && facebookUrlRestriction.test(facebookUrl.val().trim()) === false) {
                $messageModal.find('.header-content').html('Invalid Facebook URL.');
                $messageModal.find('.sub-header-content').html('Enter a valid facebook url');
                $messageModal.modal('show');
                return;
            }
            else if (googleUrl.val().trim() !== '' && googleUrlRestriction.test(googleUrl.val().trim()) === false) {
                $messageModal.find('.header-content').html('Invalid Google URL.');
                $messageModal.find('.sub-header-content').html('Enter a valid google url');
                $messageModal.modal('show');
                return;
            }
            else if (twitterUrl.val().trim() !== '' && twitterUrlRestriction.test(twitterUrl.val().trim()) === false) {
                $messageModal.find('.header-content').html('Invalid Twitter URL');
                $messageModal.find('.sub-header-content').html('');
                $messageModal.modal('show');
                return;
            }
            else if (arrayOfAddress.length > 0 && hasDefault === false) {
                $messageModal.find('.header-content').html('Set your Primary Address');
                $messageModal.find('.sub-header-content').html('');
                $messageModal.modal('show');
                return;
            }
            else if (!slugCharacterRestriction.test(storeName.trim())) {
                $messageModal.find('.header-content').html('Store name should not contain special characters');
                $messageModal.find('.sub-header-content').html('Alphabet, Number, underscore, dash, dot, single quote, brackets and comma characters are allowed.');
                $messageModal.modal('show');
                return;
            }
            else if (sellerType == affiliateStoreType && selectedStoreCategoryIds.length === 0) {
                $messageModal.find('.header-content').html('Select at least one (1) Product Category.');
                $messageModal.find('.sub-header-content').html('');
                $messageModal.modal('show');
                return;
            }
            else if (!numericAndDashOnly.test(tin)) {
                $messageModal.find('.header-content').html('TIN should only contain numbers and dash');
                $messageModal.find('.sub-header-content').html('Ex. 123-456-789-123');
                $messageModal.modal('show');
                return;
            }

            $.ajax({
                url: Routing.generate('merchant_accreditation_submit_business_information'),
                method: 'post',
                dataType: 'json',
                data: {
                    accreditationId           : accreditationId,
                    sellerType                : sellerType,
                    storeName                 : storeName,
                    storeSlug                 : storeSlug,
                    websiteUrl                : websiteUrl,
                    facebookUrl               : facebookUrl.val(),
                    googleUrl                 : googleUrl.val(),
                    twitterUrl                : twitterUrl.val(),
                    facebookUserSocialMediaId : facebookUrl.attr('data-user-social-media-id'),
                    googleUserSocialMediaId   : googleUrl.attr('data-user-social-media-id'),
                    twitterUserSocialMediaId  : twitterUrl.attr('data-user-social-media-id'),
                    addresses                 : arrayOfAddress,
                    company                   : company,
                    job                       : job,
                    tin                       : tin,
                    selectedStoreCategoryIds  : selectedStoreCategoryIds,
                    firstName                 : $('#user-first-name-txt').val().trim(),
                    lastName                  : $('#user-last-name-txt').val().trim(),
                    email                     : $('#user-email-txt').val().trim()
                },
                beforeSend: function () {
                    $this.attr('disabled', true);
                    $this.find('.text').addClass('hidden');
                    $this.find('.loader').removeClass('hidden');
                },
                success: function (response) {
                    $this.attr('disabled', false);
                    $this.find('.text').removeClass('hidden');
                    $this.find('.loader').addClass('hidden');

                    if (response.isSuccessful) {
                        $messageModal.find('.header-content').html(successSaveMessageHeader);
                        $messageModal.find('.sub-header-content').html(successSaveMessageDetail);
                        $messageModal.modal('show');

                        window.location.replace(Routing.generate('merchant_accreditation'));
                    }
                    else {
                        $messageModal.find('.header-content').html(response.message);
                        $messageModal.find('.sub-header-content').html('');
                        $messageModal.modal('show');
                    }

                }
            });

        });

        $(document).on('click', '#btn-go-back', function () {
            confirmBackAction ();
        });

    });

    //Stabilizer
    var maxHeightx = 0;
    var maxHeighty = 0;

    $(".stabilizeThird").each(function(){
      if ($(this).height() > maxHeightx) { maxHeightx = $(this).height(); }
    });

    $(".stabilizeThird").height(maxHeightx);

    $("input[name='txt-user-store-name']").slugify($("input[name='storeSlug']"));

    //START OF ADDRESS RELATED CODE
    /**
     * Prepare data on load
     */
    function prepareData ()
    {
        $('input[name=store-category-id]:checked').each(function() {$selectedStoreCategoryIds.push($(this).val());});
        $('#btn-validate').attr('disabled', false);
        $('#btn-submit-business-information').attr('disabled', false);
        var userAddresses = JSON.parse($('#user-addresses').val());

        if (userAddresses.length > 0) {

            $.each(userAddresses, function (key, userAddress) {
                arrayOfAddress.push({
                    id: userAddress.userAddressId,
                    locationId: userAddress.locationId,
                    addressTitle: userAddress.title,
                    unitNumber: userAddress.unitNumber,
                    buildingName: userAddress.buildingName,
                    streetNumber: userAddress.streetNumber,
                    streetName: userAddress.streetName,
                    subdivision: userAddress.subdivision,
                    zipCode: userAddress.zipCode,
                    provinceId: userAddress.provinceId,
                    cityId: userAddress.cityId,
                    barangayId: userAddress.barangayId,
                    isDefault: userAddress.isDefault,
                    isNew: false,
                    isRemoved: false,
                    isChanged: false
                });
            });

        }
    }

    /**
     * Validate and Add HTML in list
     *
     * @param addressTitle
     * @param unitNumber
     * @param buildingName
     * @param streetNumber
     * @param streetName
     * @param subdivision
     * @param locationId
     * @param zipCode
     * @param provinceId
     * @param cityId
     * @param barangayId
     * @param userAddressId
     */
    function processAddress (
        addressTitle, unitNumber,
        buildingName, streetNumber,
        streetName, subdivision,
        locationId, zipCode,
        provinceId, cityId,
        barangayId, userAddressId
    )
    {
        var $btnValidate = $('#btn-validate');
        var $messageModal = $('#modal-message');
        var $errorMessageContainer = $('.server-error-message');
        var provinceDropDown = $('.province-input').dropdown('get text');
        var cityDropDown = $('.city-input').dropdown('get text');
        var barangayDropDown = $('.barangay-input').dropdown('get text');

        $.ajax({
            url: Routing.generate('merchant_store_address_validate'),
            method: 'post',
            dataType: 'json',
            data: {
                addressTitle: addressTitle,
                unitNumber: unitNumber,
                buildingName: buildingName,
                streetNumber: streetNumber,
                streetName: streetName,
                subdivision: subdivision,
                zipCode: zipCode,
                locationId: locationId
            },
            beforeSend: function () {
                $errorMessageContainer.addClass('hidden');
                $btnValidate.attr('disabled', true);
                $btnValidate.find('.text').addClass('hidden');
                $btnValidate.find('.loader').removeClass('hidden');
            },
            success: function (response) {
                isAddressEdited = true;
                $btnValidate.attr('disabled', false);
                $btnValidate.find('.text').removeClass('hidden');
                $btnValidate.find('.loader').addClass('hidden');

                if (response.isSuccessful) {
                    var fullAddress = unitNumber + ' ' + buildingName + ' ' + streetNumber + ' ' + streetName + ' ' + subdivision + ' ' +
                        provinceDropDown + ' ' + cityDropDown + ' ' + barangayDropDown + ' ,' + zipCode;

                    if (userAddressId) {
                        var addressDetail = getAddress (userAddressId);

                        removeAddressInArray (userAddressId);

                        arrayOfAddress.push({
                            id: userAddressId,
                            locationId: locationId,
                            addressTitle: addressTitle,
                            unitNumber: unitNumber,
                            buildingName: buildingName,
                            streetNumber: streetNumber,
                            streetName: streetName,
                            subdivision: subdivision,
                            zipCode: zipCode,
                            provinceId: provinceId,
                            cityId: cityId,
                            barangayId: barangayId,
                            isNew: addressDetail.isNew,
                            isDefault: addressDetail.isDefault,
                            isRemoved: false,
                            isChanged: true
                        });

                        if (!addressDetail.isNew) {
                            userAddressId = 'address-id-' + userAddressId;
                        }

                        updateAddressHtml (userAddressId, addressTitle, fullAddress.trim());
                        $messageModal.find('.header-content').html(successUpdateMessageHeader);
                        $messageModal.find('.sub-header-content').html(successUpdateMessageDetail);
                    }
                    else {
                        var addressId = 'partial-' + partialAddressId++;
                        var isDefault = false;

                        if (arrayOfAddress.length === 0) {
                            isDefault = true;
                        }

                        arrayOfAddress.push({
                            id: addressId,
                            locationId: locationId,
                            addressTitle: addressTitle,
                            unitNumber: unitNumber,
                            buildingName: buildingName,
                            streetNumber: streetNumber,
                            streetName: streetName,
                            subdivision: subdivision,
                            zipCode: zipCode,
                            provinceId: provinceId,
                            cityId: cityId,
                            barangayId: barangayId,
                            isDefault: isDefault,
                            isNew: true,
                            isRemoved: false,
                            isChanged: false
                        });

                        displayAddressHtml(addressId, addressTitle, fullAddress.trim(), isDefault);
                        $messageModal.find('.header-content').html(successfullyAddedAddressMsgHead);
                        $messageModal.find('.sub-header-content').html(successfullyAddedAddressMsgDetail);
                    }
                    $messageModal.modal('show');
                }
                else {
                    var errorMessages = response.message;
                    var errorHtml = '';

                    $.each(errorMessages, function (key, errorMessage) {
                        errorHtml += errorMessage + ' <br>';
                    });

                    $errorMessageContainer.removeClass('hidden').find('.message-box').html(errorHtml);
                }

            }
        })
    }

    /**
     * Get Child Locations
     *
     * @param $locationDropDown
     * @returns {*}
     */
    function getLocationChildren ($locationDropDown)
    {
        var locationId = $locationDropDown.dropdown('get value');
        var deferredObject = $.Deferred();

        if (locationId == 0) {
            return deferredObject.promise();
        }

        if (!(locationId in locations)) {
            $.ajax({
                type: "POST",
                url: Routing.generate('merchant_location_children'),
                data: {locationId: locationId},
                success : function(response) {
                    locations[locationId] = response.data.locations;
                    updateChildrenLocationDropDown($locationDropDown, deferredObject);
                }
            });
        }
        else {
            updateChildrenLocationDropDown($locationDropDown, deferredObject);
        }

        return deferredObject.promise();
    }

    /**
     * Update Child Location Drop Down
     *
     * @param $triggerContainer
     * @param deferredObject
     */
    function updateChildrenLocationDropDown($triggerContainer, deferredObject)
    {
        var locationId = $triggerContainer.dropdown('get value');
        var $addressModal = $triggerContainer.closest('.modal-address-container');
        var optionsStrings = "";

        $.each(locations[locationId], function(key, value){
            optionsStrings = optionsStrings + "<div class='item' data-value='" + value.locationId + "'>" + value.location + "</div>";
        });

        var $barangaySelector = $addressModal.find('.barangay-input');

        if ($triggerContainer.hasClass('province-input')) {
            var $citySelector = $addressModal.find('.city-input');
            $citySelector.dropdown('restore defaults');
            $citySelector.dropdown('refresh');
            $citySelector.find('.menu').html(optionsStrings);
            $barangaySelector.dropdown('restore defaults');
            $barangaySelector.dropdown('refresh');
            $barangaySelector.find('.menu').empty();
        }
        else if ($triggerContainer.hasClass('city-input')) {
            $barangaySelector.dropdown('restore defaults');
            $barangaySelector.dropdown('refresh');
            $barangaySelector.find('.menu').html(optionsStrings);
        }

        if (typeof deferredObject !== 'undefined') {
            deferredObject.resolve();
        }

    }

    /**
     * Get address details in arrayOfAddress by id
     *
     * @param bankId
     * @returns {*}
     */
    function getAddress (addressId)
    {
        var addressDetails = null;

        $.each(arrayOfAddress, function (key, value) {

            if(value.id == addressId) {
                addressDetails = arrayOfAddress[key];
                return false;
            }

        });

        return addressDetails;
    }

    /**
     * Remove Address Detail in arrayOfAddress by id
     *
     * @param addressId
     * @returns {Array}
     */
    function removeAddressInArray (addressId)
    {
        var arrayContainer = arrayOfAddress;

        for(var i = 0; i < arrayContainer.length; i++) {
            if (arrayContainer[i].id == addressId) {
                arrayContainer.splice(i, 1);
                break;
            }
        }

        return arrayContainer;
    }

    /**
     * Display Address HTML
     *
     * @param addressId
     * @param addressTitle
     * @param fullAddress
     * @param isDefault
     */
    function displayAddressHtml (addressId, addressTitle, fullAddress, isDefault)
    {
        var $addressCollection = $('#store-address-collection');
        var $newAddress = $('.hidden ').find('.div-clone-address').clone(true);

        if (addressTitle === '') {
            addressTitle = '<div class="red">Did not specify Address Title.</div>'
        }

        $newAddress.addClass(addressId);
        $newAddress.find('.item-name').html(addressTitle);
        $newAddress.find('.item-address-line').html(fullAddress);
        $newAddress.find('.check-box-is-default').attr('data-id', addressId);
        $newAddress.find('.delete, .edit').attr('data-id', addressId);

        $newAddress.find('.ellipsis-dropdown').dropdown();

        $addressCollection.append($newAddress);

        if (isDefault === true) {
            $addressCollection.find('.' + addressId).find('.check-box-is-default').trigger('click');
        }

    }

    /**
     * Update Address HTML
     *
     * @param addressId
     * @param addressTitle
     * @param fullAddress
     */
    function updateAddressHtml (addressId, addressTitle, fullAddress)
    {
        var $addressCollection = $('#store-address-collection');
        var $newAddress = $addressCollection.find('.' + addressId);

        if (addressTitle === '') {
            addressTitle = '<div class="red">Did not specify Address Title.</div>'
        }

        $newAddress.addClass(addressId);
        $newAddress.find('.item-name').html(addressTitle);
        $newAddress.find('.item-address-line').html(fullAddress);

    }

    /**
     * Remove Data in modal
     */
    function cleanAddressModal ()
    {
        var $modalContainer = $('#modal-address');
        $modalContainer.find('.city-input .text').html('Select City/Municipality');
        $modalContainer.find('.city-input .menu').html('');
        $modalContainer.find('.barangay-input .text').html('Select Barangay');
        $modalContainer.find('.barangay-input .menu').html('');
        $modalContainer.find('.drop-down-location').dropdown('restore defaults');

        $('#form-address').form('clear');
        $('.server-error-message').addClass('hidden');
    }

    /**
     * Set Address to default address
     *
     * @param addressId
     */
    function setDefaultAddress (addressId)
    {

        $.each(arrayOfAddress, function (key, value) {

            if (arrayOfAddress[key]['isDefault'] == true) {
                arrayOfAddress[key]['isChanged'] = true;
            }

            if (value.id == addressId) {
                arrayOfAddress[key]['isDefault'] = true;
                arrayOfAddress[key]['isChanged'] = true;
            }
            else {
                arrayOfAddress[key]['isDefault'] = false;
            }


        });

    }

    //END OF ADDRESS RELATED CODE

    /**
     * Back action confirmation
     */
    function confirmBackAction ()
    {
        var $onlineStoreDiv = $('#online-store-div');
        var storeName = $('input[name=txt-user-store-name]');
        var company = $('input[name=txt-user-company]');
        var job = $('input[name=txt-user-job]');
        var tin = $('input[name=txt-user-tin]');
        var storeSlug = $('input[name=storeSlug]');
        var websiteUrl = $onlineStoreDiv.find('input[name=txt-website-url]');
        var facebookUrl = $onlineStoreDiv.find('input[name=social-media-' + socialMediaFacebookTypeId + ']');
        var googleUrl = $onlineStoreDiv.find('input[name=social-media-' + socialMediaGoogleTypeId + ']');
        var twitterUrl = $onlineStoreDiv.find('input[name=social-media-' + socialMediaTwitterTypeId + ']');
        var selectedStoreCategoryIds = [];
        $('input[name=store-category-id]:checked').each(function() {selectedStoreCategoryIds.push($(this).val());});
        var isProductCategoriesEdited = $selectedStoreCategoryIds.sort().join(',') != selectedStoreCategoryIds.sort().join(',');

        if ( storeName.val().trim() !== storeName.attr('data-value').trim() || websiteUrl.val().trim() !== websiteUrl.attr('data-value').trim() ||
            facebookUrl.val().trim() !== facebookUrl.attr('data-value').trim() || googleUrl.val().trim() !== googleUrl.attr('data-value').trim() ||
            twitterUrl.val().trim() !== twitterUrl.attr('data-value').trim() || company.val().trim() !== company.attr('data-value').trim() ||
            job.val().trim() !== job.attr('data-value').trim() || tin.val().trim() !== tin.attr('data-value').trim() ||
            storeSlug.val().trim() !== storeSlug.attr('data-value').trim() || isAddressEdited || isProductCategoriesEdited) {
            $('#modal-confirm-back')
                .modal({
                    onApprove: function () {
                        window.location.replace(Routing.generate('merchant_accreditation'));
                    }
                })
                .modal('show');
        }
        else {
            window.location.replace(Routing.generate('merchant_accreditation'));
        }

    }

})(jQuery);
