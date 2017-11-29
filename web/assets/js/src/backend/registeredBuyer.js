(function ($) {
    var arrayOfAddress = [];
    var locations = [];
    var partialAddressId = 0;
    var $messageModal = $('#modal-message');

    var successfullyAddedAddressMsgHead = 'Address has been successfully added';
    var successfullyAddedAddressMsgDetail = 'An entry has been added to your available addresses. ';

    var successUpdateMessageHeader = 'Address has been successfully updated';
    var successUpdateMessageDetail = '';

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
                {type: 'empty'},
                {type: 'maxLength[45]'},
                {type: 'number'}
            ]
        }
    };

    $(document).ready(function () {
        $('.hidden').hide();

        $('.datePicker').datetimepicker({
            format: "MM/DD/YYYY"
        });

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

        $(document).on('change', '.drop-down-location', function () {
            getLocationChildren ($(this));
        });

        $(document).on('click', '#modal-register-user .btn-update', function () {
            cleanAddressModal ();
            var $this = $(this);
            var addressId = parseInt($this.attr('data-address-id'));

            if (addressId !== 0) {
                prepareData ($this.attr('data-json-address'));
                var addressDetail = getAddress(addressId);

                var $addressForm = $('#form-address');
                $addressForm.form('clear');

                $addressForm.form('set values', {
                    'txt-title'         : addressDetail.addressTitle,
                    'txt-unit-number'   : addressDetail.unitNumber,
                    'txt-building-name' : addressDetail.buildingName,
                    'txt-street-number' : addressDetail.streetNumber,
                    'txt-street-name'   : addressDetail.streetName,
                    'txt-subdivision'   : addressDetail.subdivision,
                    'txt-zip-code'      : addressDetail.zipCode
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
            } else {
                $('#user-address-id').val('');
            }

            $('#modal-address').modal('show');
        });

        //END OF ADDRESS RELATED CODE

        $(document).on('click', '.btn-render-user-detail-modal', function () {
            var userId = $(this).attr('data-id');
            $('#user-id').val(userId);
            getUserDetail (userId);
        });

        $('#searchBtn').on('click', function () {
            applyFilters();
        });
     
        $('#active-select').on('change', function() {
            applyFilters();
        });
        
        $('#searchKeyword').on('keypress', function (e) {
            if (e.keyCode === 13) {
                applyFilters();
            }
        });

        displayDataInUrl ();
    });

    /**
     * Get Details by seller Id
     *
     * @param userId
     */
    function getUserDetail (userId)
    {
        $.ajax({
            url: Routing.generate('admin_user_detail'),
            method: 'get',
            dataType: 'json',
            data: {
                userId: userId
            },
            beforeSend: function () {
                $('#div-address').html('');
                $('#div-generated-code').html('');
                $('#btn-generate-code').attr('data-id', 0);
            },
            success: function (response) {

                if (response.isSuccessful) {
                    renderUserDetail (response.details, userId);
                }

            }
        })
    }

    function displayDataInUrl ()
    {
        var searchKeyword = getParameterByName('searchKeyword');
        $('#searchKeyword').val(searchKeyword);
    }

    function applyFilters ()
    {
        var searchKeyword = $('#searchKeyword').val().trim();
        var isActive = $('#active-select').val().trim();

        var parametersUrl = 'isActive=' + isActive;

        if (searchKeyword !== '') {
            parametersUrl = parametersUrl + '&searchKeyword=' + searchKeyword;
        }

        /**
         * Reload page with query parameters
         */
        var reloadUrl = window.location.origin + window.location.pathname + "?" + parametersUrl;
        window.location.href = reloadUrl;
    }

    /**
     * Render Details in Modal
     *
     * @param details
     */
    function renderUserDetail (details, userId)
    {
        var fullLocation = details.userAddress !== '' && details.userAddress.fullLocation !== null ? details.userAddress.fullLocation : 'No Address';
        var userAddress = "'" + JSON.stringify(details.userAddress) + "'";
        var userAddressId = details.userAddress.userAddressId === null ? 0 : details.userAddress.userAddressId;
        var addressHtml = '' +
            '<div class="address-row">' +
                '<p class="div-full-address mrg-bt-10"> ' + fullLocation + '</p>' +
                '<button class="button gray small btn-update" data-json-address=' + userAddress + ' data-address-id="' + userAddressId + '">UPDATE</button>' +
            '</div>';

        $('#div-address').html(addressHtml);
        $('#modal-register-user').modal('show');
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
        var $errorMessageContainer = $('.server-error-message');
        var provinceDropDown = $('.province-input').dropdown('get text');
        var cityDropDown = $('.city-input').dropdown('get text');
        var barangayDropDown = $('.barangay-input').dropdown('get text');

        $.ajax({
            url: Routing.generate('backend_store_address_validate'),
            method: 'post',
            dataType: 'json',
            data: {
                addressTitle  : addressTitle,
                unitNumber    : unitNumber,
                buildingName  : buildingName,
                streetNumber  : streetNumber,
                streetName    : streetName,
                subdivision   : subdivision,
                zipCode       : zipCode,
                locationId    : locationId,
                userId        : $('#user-id').val()
            },
            beforeSend: function () {
                $errorMessageContainer.addClass('hidden');
                $btnValidate.attr('disabled', true);
                $btnValidate.find('.text').addClass('hidden');
                $btnValidate.find('.loader').removeClass('hidden');
            },
            success: function (response) {
                $btnValidate.attr('disabled', false);
                $btnValidate.find('.text').removeClass('hidden');
                $btnValidate.find('.loader').addClass('hidden');

                if (response.isSuccessful) {
                    var fullAddress = unitNumber + ' ' + buildingName + ' ' + streetNumber + ' ' + streetName + ' ' + subdivision + ' ' +
                        provinceDropDown + ' ' + cityDropDown + ' ' + barangayDropDown + ' ,' + zipCode;

                    if (userAddressId) {
                        var addressDetail = getAddress (userAddressId);

                        arrayOfAddress = removeAddressInArray (userAddressId);

                        arrayOfAddress.push({
                            id           : userAddressId,
                            locationId   : locationId,
                            addressTitle : addressTitle,
                            unitNumber   : unitNumber,
                            buildingName : buildingName,
                            streetNumber : streetNumber,
                            streetName   : streetName,
                            subdivision  : subdivision,
                            zipCode      : zipCode,
                            provinceId   : provinceId,
                            cityId       : cityId,
                            barangayId   : barangayId,
                            isNew        : addressDetail.isNew,
                            isDefault    : addressDetail.isDefault,
                            isRemoved    : false,
                            isChanged    : true
                        });

                        if (!addressDetail.isNew) {
                            userAddressId = 'address-id-' + userAddressId;
                        }

                        $messageModal.find('.header-content').html(successUpdateMessageHeader);
                        $messageModal.find('.sub-header-content').html(successUpdateMessageDetail);
                    } else {
                        var addressId = 'partial-' + partialAddressId++;
                        var isDefault = false;

                        if (arrayOfAddress.length === 0) {
                            isDefault = true;
                        }

                        arrayOfAddress.push({
                            id           : addressId,
                            locationId   : locationId,
                            addressTitle : addressTitle,
                            unitNumber   : unitNumber,
                            buildingName : buildingName,
                            streetNumber : streetNumber,
                            streetName   : streetName,
                            subdivision  : subdivision,
                            zipCode      : zipCode,
                            provinceId   : provinceId,
                            cityId       : cityId,
                            barangayId   : barangayId,
                            isDefault    : isDefault,
                            isNew        : true,
                            isRemoved    : false,
                            isChanged    : false
                        });

                        $messageModal.find('.header-content').html(successfullyAddedAddressMsgHead);
                        $messageModal.find('.sub-header-content').html(successfullyAddedAddressMsgDetail);
                    }

                    submitAddresses ();
                    $messageModal.modal('show');
                    getUserDetail ($('#user-id').val());
                } else {
                    var errorMessages = response.message;
                    var errorHtml = '';

                    $.each(errorMessages, function (key, errorMessage) {
                        errorHtml += errorMessage + ' <br>';
                    });

                    $errorMessageContainer.removeClass('hidden').find('.message-box').html(errorHtml);
                    $errorMessageContainer.show();
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
                url: Routing.generate('backend_location_children'),
                data: {locationId: locationId},
                success : function(response) {
                    locations[locationId] = response.data.locations;
                    updateChildrenLocationDropDown($locationDropDown, deferredObject);
                }
            });
        } else {
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
     * @param addressId
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
     * Prepare data on modal load
     */
    function prepareData (address)
    {
        arrayOfAddress = [];
        $('#btn-validate').attr('disabled', false);
        $('#btn-submit-business-information').attr('disabled', false);
        var userAddress = JSON.parse(address);

        if (userAddress) {

            arrayOfAddress.push({
                id           : userAddress.userAddressId,
                locationId   : userAddress.locationId,
                addressTitle : userAddress.title,
                unitNumber   : userAddress.unitNumber,
                buildingName : userAddress.buildingName,
                streetNumber : userAddress.streetNumber,
                streetName   : userAddress.streetName,
                subdivision  : userAddress.subdivision,
                zipCode      : userAddress.zipCode,
                provinceId   : userAddress.provinceId,
                cityId       : userAddress.cityId,
                barangayId   : userAddress.barangayId,
                isDefault    : true,
                isNew        : false,
                isRemoved    : false,
                isChanged    : false
            });

        }
    }

    /**
     * Submit Addresses
     */
    function submitAddresses ()
    {
        $.ajax({
            url: Routing.generate('backend_user_address_submit'),
            method: 'post',
            dataType: 'json',
            data: {
                addresses : arrayOfAddress,
                userId    : $('#user-id').val()
            },
            beforeSend: function () {
            },
            success: function (response) {
            }
        })
    }

    /**
     * Get Details by seller Id
     *
     * @param userId
     */
    function getUserDetail (userId)
    {
        $.ajax({
            url: Routing.generate('admin_user_detail'),
            method: 'get',
            dataType: 'json',
            data: {
                userId: userId
            },
            beforeSend: function () {
                $('#div-address').html('');
                $('#div-generated-code').html('');
                $('#btn-generate-code').attr('data-id', 0);
            },
            success: function (response) {

                if (response.isSuccessful) {
                    renderUserDetail (response.details, userId);
                }

            }
        })
    }

    function displayDataInUrl ()
    {
        var searchKeyword = getParameterByName('searchKeyword');
        var dateFrom = getParameterByName('dateFrom');
        var dateTo = getParameterByName('dateTo');

        $('#searchKeyword').val(searchKeyword);
        $('#dateFrom').val(dateFrom);
        $('#dateTo').val(dateTo);

        if (dateFrom === '' && dateTo === '') {
            $('#dateFrom').val(getDate (-1));
            $('#dateTo').val(getDate ());
        }

    }

    function applyFilters ()
    {
        var searchKeyword = $('#searchKeyword').val().trim();
        var isActive = $('#active-select').val().trim();
        var dateFrom = $('#dateFrom').val().trim();
        var dateTo = $('#dateTo').val().trim();

        var parametersUrl = 'isActive=' + isActive;

        if (searchKeyword !== '') {
            parametersUrl = parametersUrl + '&searchKeyword=' + searchKeyword;
        }

        if (dateFrom !== '') {
            parametersUrl += (parametersUrl === '' ? '?' : '&') + 'dateFrom=' + dateFrom;
        }

        if (dateTo !== '') {
            parametersUrl += (parametersUrl === '' ? '?' : '&') + 'dateTo=' + dateTo;
        }

        /**
         * Reload page with query parameters
         */
        var reloadUrl = window.location.origin + window.location.pathname + "?" + parametersUrl;
        window.location.href = reloadUrl;
    }

    /**
     * Render Details in Modal
     *
     * @param details
     */
    function renderUserDetail (details, userId)
    {
        var fullLocation = details.userAddress !== '' && details.userAddress.fullLocation !== null ? details.userAddress.fullLocation : 'No Address';

        $('#div-address').html(fullLocation);
        $('#modal-register-user').modal('show');
    }

})(jQuery);
