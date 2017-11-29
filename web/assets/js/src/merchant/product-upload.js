var productAttributeArray = [];
var attributeUnitArray = [];
var attributeUnitKey = 0;
var productImageContainer = [];
var restriction = /^[a-zA-Z0-9. ]*$/;
var numbersAndDotOnly = /^[0-9.]*$/;
var numbersOnly = /^[0-9]*$/;
var imageLocation = '/assets/images/uploads/products/';
var assetHostname = "";
var modalCount = 0;
var imageCropContainer = [];
var isCropping = false;
var isUpdate = $('#isFormUpdate').val().trim();
var selectedCategoryId = parseInt($('#inputCategory').val());
var $messageModal = $('#modal-message-container');
var $confirmModal = $('#modal-confirm-container');
var primaryImageId = 0,
    shippingCategoryElem = $("#shipping-category-id");

(function ($) {

    $(document).ready(function() {

        var assetHostname = $('#asset-hostname').val();

        var $imageDropzone = $('.dropzone-product-image');
        if ($imageDropzone.data('readonly')) {
            $imageDropzone.find('.dz-remove').hide();
        }

        var $addDetailsButton = $('#add-variant-trigger');
        if ($addDetailsButton.hasClass('disabled')) {
            $('.editAttributeNameValue').addClass('disabled');
            $('.deleteAttributeNameValue').addClass('disabled');
        }

        var $addDetailsCombination = $('#addProductCombinationBtn');
        if ($addDetailsCombination.hasClass('disabled')) {
            $('.editAttributeCombination').addClass('disabled');
        }

        var $confirmModal = $('#modal-confirm-container');
        var isUpdate = $('#isFormUpdate').val().trim();

        CKEDITOR.replace('completeDescription', {
            filebrowserBrowseUrl: '/merchant/upload/detail/image-view',
            filebrowserUploadUrl: '/merchant/upload/detail/image',
            imgurClientId: '213',
            filebrowserWindowWidth  : 800,
            filebrowserWindowHeight : 500
        });

        CKEDITOR.on( 'dialogDefinition', function( ev ){
            // Take the dialog name and its definition from the event data.
            var dialogName = ev.data.name;
            var dialogDefinition = ev.data.definition;

            //Remove unncessary button and field for image upload
            var imageInfoTab = dialogDefinition.getContents( 'info' );

            imageInfoTab.remove('txtHSpace');
            imageInfoTab.remove('txtVSpace');
            imageInfoTab.remove('btnBrowseServer');
        });


        //Image Uploader
        $(".row-header-box").stick_in_parent({
            parent: ".product-upload-wrapper"
        });

        $('#inputCancel').on('click', function () {
            history.back();
        });

        $('#crop-btn').on('click', function() {
            isCropping = false;
            var $image = $('#crop-img');

            $image.cropper('getCroppedCanvas', { fillColor: '#FFFFFF' }).toBlob( function (blob) {
                processImageToResize($image, blob);
            });

        });

        //Dropzone upload module
        Dropzone.autoDiscover = false;
        var $dropzone = new Dropzone (
            ".dropzone-product-image",
            {
            url: Routing.generate("product_upload_add_image"),
            addRemoveLinks: true,
            uploadMultiple: false,
            maxFilesize: 2,
            acceptedFiles: ".jpeg, .jpg, .png",
            autoProcessQueue: false,
            clickable: !$(".dropzone-product-image").data('readonly'),
            init: function() {
                var $dpThis = this;
                var $updateProductImage = $('.updateProductImage');

                $dpThis.on("error", function(file) {

                    if (file.accepted == false) {
                        $messageModal.find('.header-content').html('Kindly upload a valid Image.');
                        $messageModal.find('.detail-content').html('Accepted file size is less than 2MB, refrain High resolution image and accepted file formats are jpg and png only.');
                        $messageModal.modal('show');
                        $dpThis.removeFile(file);
                    }

                });

                if ($updateProductImage.length > 0) {

                    $updateProductImage.each(function (key, val) {
                        var $this = $(this);
                        var imageId = $this.attr('data-id');
                        var imagePath = assetHostname + imageLocation + $this.val().trim();
                        var mockFile = { imageName: $this.attr('data-image'), imageId: imageId, isCropped: true};
                        $dpThis.emit("addedfile", mockFile);
                        $dpThis.options.thumbnail.call($dpThis, mockFile, imagePath);
                        $(mockFile.previewTemplate).attr('product-image-id', imageId);

                        if ($this.attr('data-is-primary') == true) {
                            changePrimaryImage($(mockFile.previewTemplate));
                        }

                        var imageArray = {
                            id    : imageId,
                            isNew : false,
                            image :  $this.attr('data-image')
                        };
                        productImageContainer.push(imageArray);

                    });

                }

                this.on("success", function(file, response) {

                    $('#crop-btn').attr('disabled', false).text('Crop');
                    if (response.isSuccessful) {
                        $('#generalErrorMessage').hide();
                        var imageData = response.data;

                        productImageContainer.push(imageData);
                        file.imageId = imageData.id;
                        file.imageName = imageData.image;
                        file.isCropped = true;
                        $(file.previewTemplate).attr('product-image-id', imageData.id);
                    }
                    else {
                        $('#generalErrorMessage').append(response.message).show();
                    }

                });
            },
            removedfile: function(file) {
                var _ref;

                if (isCropping === false && typeof file.isCropped !== 'undefined' && file.isCropped === true) {

                    if (isImageIsUsedInCombination(file.imageId)) {
                        $messageModal.find('.header-content').html('This image is used in Variant and cannot be deleted. \n' +
                            'Remove Image in Variant combination to proceed.');
                        $messageModal.find('.detail-content').html('');
                        $messageModal.modal('show');
                        return;
                    }

                    if (!confirm('Are you sure you want to remove Image?')) {
                        return;
                    }

                }

                deleteInArrayById (productImageContainer, file.imageId);

                return (_ref = file.previewElement) != null ? _ref.parentNode.removeChild(file.previewElement) : void 0;
            }
        });

        $dropzone.on('thumbnail', function (file) {
            modalCount++;

            if (file.cropped) {
                return;
            }

            imageCropContainer.push({
                id: modalCount,
                file: file
            });

            $dropzone.removeFile(file);

            if (isCropping === false) {
                renderImageToModal (imageCropContainer[0].file, imageCropContainer[0].id);
            }
        });

        //Select Primary Image
        $(document).on('click', '.dz-preview', function() {
            changePrimaryImage($(this));
        });

        //Dropdown menu
        $(".navbar-dropdown").click(function(){
            $(this).parents(".navbar-item").find(".navbar-dropdown-container").transition("scale");
            $(this).parents(".navbar-item").toggleClass("active");
        });

        $(document).click(function(e) {
             var target = e.target;
            if (!$(target).is('.navbar-dropdown') && !$(target).parents().is('.navbar-dropdown') && $(".navbar-dropdown-container").is(":visible")) {
                $('.navbar-dropdown-container').transition("scale").hide();
                $(".navbar-dropdown-container").parents(".navbar-item").removeClass("active");
            }
        });

        //Single selection select box
        $(".single.selection").dropdown();

        //Multiple selection select box with tokens
        $(".multiple.search.selection").dropdown({
            maxSelections: 5,
            allowAdditions: true
        });

        //Checkbox customize design
        $("input[type=checkbox]").checkbox();

        //Add extra padding for dropdown itemms
        $('.ui.dropdown .item, .ui.selection.dropdown .menu>.message, .category-dropdown').attr('style', 'padding: 0.5em 1.25em !important');
        $('.ui.dropdown .item, .menu>.message').attr('style', 'padding: 1em 1.25em !important');

        //Trigger add product combination modal
        $(".add-product-combination-modal-trigger").click(function(){
            $('.add-product-combination-modal').modal({
                observeChanges: true
            }).modal('show');
        });

        $('.coupled-first').modal({
            allowMultiple: true
        });

        //Trigger add product combination image list
        $(".add-product-combination-image-trigger").click(function() {

            if (productImageContainer.length === 0) {
                $messageModal.find('.header-content').html('No Image Uploaded');
                $messageModal.find('.detail-content').html('');
                $messageModal.modal('show');
            }else{
                $(".add-image-product-combination-button-container").transition("scale");
                $('.image-selection-list-container').transition("scale");
            }

        });

        //Tokenized field for variant choices
        $('#tokenfield').tokenfield();

        //Trigger shipping location table through free shipping checkbox
        $('.free-shipping-checkbox').change(function(){
            if($(this).is(':checked')){
                $('.table-shipping-locations-group').transition("scale").show();
            } else {
                $('.table-shipping-locations-group').transition("scale").hide();
            }
        });

        //Check and uncheck all checkbox under shipping location table
        $('.check-all-shipping-location').change(function(){
            if($(this).is(':checked')){
                $('.shipping-location-checkbox').prop("checked", true);
            } else {
                $('.shipping-location-checkbox').prop("checked", false);
            }
        });

        /**
         * show attribute modal
         */
        $("#add-variant-trigger").click(function() {
            $('#tokenfield').tokenfield('setTokens', { value: '', label: '' });
            $('#attributeName').val('');
            $('#modalCategoryAttributeSubmit').attr('data-isUpdate', '0');
            $('.add-variant-modal').modal('show');
        });

        /**
         * edit attribute
         */
        $('#attributeRowContainer').on('click', '.editAttributeNameValue', function () {
            var attrId = $(this).attr('data-id');
            var attribute = productAttributeArray.filter(function(v) { return v.id == attrId; })[0];
            $('#tokenfield').tokenfield('setTokens', attribute.values.join(','));
            $('#attributeName').val(attribute.name);
            $('.add-variant-modal').modal('show');
            $('#modalCategoryAttributeSubmit').attr('data-isUpdate', attribute.id).attr('data-oldName', attribute.name);
        });

        $('#tokenfield')
        .on('tokenfield:createtoken', function (event) {
            var existingTokens = $(this).tokenfield('getTokens');
            $.each(existingTokens, function(index, token) {
                if (token.value === event.attrs.value) {
                    $messageModal.find('.header-content').html('Duplicate Entry for ' + token.value);
                    $messageModal.find('.detail-content').html('');
                    $messageModal.modal('show');
                    event.preventDefault();
                }
            });
        });

        /**
         * delete attribute
         */
        $('#attributeRowContainer').on('click', '.deleteAttributeNameValue', function () {
            var attrId = $(this).attr('data-id');
            var attribute = productAttributeArray.filter(function(v) { return v.id == attrId; })[0];

            showConfirmModal({
                message: 'Are you sure you want to delete?',
                callbacks: {
                    onApprove: function() {
                        attributeUnitArray = [];
                        $('#categoryAttributeRow_' + attrId).remove();

                        productAttributeArray = deleteInArrayById (productAttributeArray, attrId);

                        if (productAttributeArray.length === 0) {
                            $('#attributeContainer .table, #attributeCombinationDiv').hide();
                            $("#div-dimensions-weight, #productUnitSkuQtyDiv").show();
                        }
                        else {
                            autoGenerateCombination ();
                        }
                    }
                }
            });
        });

        /**
         * edit combination
         */
        $("#attributeCombinationRow").on('click', '.editAttributeCombination', function() {
            var combinationId = $(this).attr('data-id');
            var attributeCombination = attributeUnitArray.filter(function(v) { return v.key == combinationId; })[0];
            var discount = attributeCombination.price == 0 ? 0 : 100 - (100 / (attributeCombination.price / attributeCombination.discountedPrice) );

            $('#modalUnitPrice').val(parseFloat(attributeCombination.price).format(2));
            $('#modalUnitFinalPrice').val(parseFloat(attributeCombination.discountedPrice).format(2));
            $('#modalUnitDiscount').val(discount);
            $('#modalUnitSku').val(attributeCombination.sku);
            $('#modalUnitLength').val(attributeCombination.unitLength);
            $('#modalUnitWidth').val(attributeCombination.unitWidth);
            $('#modalUnitHeight').val(attributeCombination.unitHeight);
            $('#modalUnitWeight').val(attributeCombination.unitWeight);
            $('#modalAttributeCombinationBtn').attr('data-isUpdate', combinationId).attr('data-id', attributeCombination.id);
            retrieveAttributeInModal (productAttributeArray);

            $.each(attributeCombination.attributes, function (key, attribute) {
                var val = attribute.value;
                $('#modalAttributeDiv').find('.select_' + attribute.name.split(' ').join('_')).dropdown('set selected', val);
            });

            $(".add-image-product-combination-button-container").removeClass('hidden').show();

            if (attributeCombination.images.length > 0) {
                $.each(attributeCombination.images, function (key, image) {

                    $('.unitImage').each(function () {
                        var $this = $(this);
                        if ($this.attr('data-name') === image.name) {
                            $this.prop('checked', true)
                        }

                    });

                });

                $(".add-image-product-combination-button-container").hide();
                $('.image-selection-list-container').transition("scale");
            }

            $('.add-product-combination-modal').modal('show');
            $('#modalAttributeDiv').find('.attributeNameAndValue').addClass('disabled');
        });

        $('#modalCategorySearch').typeahead(
            {
                hint: true,
                highlight: true,
                minLength: 1,
                autoselect: 'first'
            },
            {
                name: 'productCategory',
                displayKey: 'breadCrumb',
                source: function (query, process) {
                    return $.get(Routing.generate('product_search_category'), { categoryKeyword: query }, function (data) {
                        return process(data);
                    });
                }
            }
        ).blur(function (e) {
            $(this).val($(this).attr('data-value'));
        }).on("typeahead:selected typeahead:autocompleted typeahead:change",
            function(e, productCategory) {
                $(this).val(productCategory.name).attr('data-value', productCategory.name);
                $("#modalCategoryButton").attr("disabled", true);
                var productCategoryId = productCategory.id;
                var isTriggerClick = false;

                if (parseInt(productCategory.hasChild) === 0) {
                    productCategoryId = productCategory.parentId;
                    isTriggerClick = true;
                }

                retrieveProductCategory (productCategoryId, productCategory.name, isTriggerClick)
        });

        $('#productBrands .typeahead').typeahead({
                hint: true,
                highlight: true,
                minLength: 1,
                autoselect: 'first'
            },
            {
                source: function (query, process) {
                    $('#inputBrand').attr('data-name', query);
                    $('#inputBrand').val(1);
                    return $.ajax({
                        url: Routing.generate('product_search_brand'),
                        data: { brandKeyword: query },
                        method: 'get',
                        dataType: 'json',
                        beforeSend: function () {
                            $('#brand-loader-image').addClass('loading');
                        },
                        success: function (data) {
                            $('#brand-loader-image').removeClass('loading');
                            return process(data);
                        }
                    });
                }
            }
        ).on("typeahead:selected typeahead:autocompleted typeahead:change", function(e, brand) {
            $('#inputBrand').val(brand.id).attr('data-name', brand.value);
        });

        $(document).on('click', '#inputDraft', function () {
            var $this = $(this);
            var $inputProductName = $('#inputName');
            var $inputDescription = CKEDITOR.instances.completeDescription.getData();
            var $inputShortDescription = $('textarea#inputShortDescription');
            var $inputCategory = $('#inputCategory');
            var $inputBrand = $('#inputBrand');
            var $inputCondition = $('#inputCondition');
            var $inputIsFreeShipping = $('#inputIsFreeShipping');
            var $inputLength = $('#inputLength');
            var $inputHeight = $('#inputHeight');
            var $inputWidth = $('#inputWidth');
            var $inputWeight = $('#inputWeight');
            var $inputBasePrice = $('#inputBasePrice');
            var $inputDiscount = $('#inputDiscountedPrice');
            var $inputSku = $('#inputSku');
            var $youtubeUrl = $('#youtube-url');
            var csrfToken = $("meta[name=csrf-token]").attr("content");
            var productUnitId = 0
                shippingCategory = shippingCategoryElem.val();

            var formData = {
                'productId'         : 0,
                'name'              : $inputProductName.val().trim(),
                'description'       : $inputDescription,
                'shortDescription'  : $inputShortDescription.val().trim(),
                'discountedPrice'   : $inputDiscount.val().trim().replace(/\,/g,''),
                'sku'               : $inputSku.val().trim(),
                'basePrice'         : $inputBasePrice.val().trim().replace(/\,/g,''),
                'productCategory'   : parseInt($inputCategory.val().trim()) !== 0 ? $inputCategory.val().trim() : null,
                'brand'             : $inputBrand.val().trim(),
                'productCondition'  : $inputCondition.val().trim(),
                'isFreeShipping'    : $inputIsFreeShipping.is(':checked') ? 1 : 0,
                'length'            : $inputLength.val().trim(),
                'height'            : $inputHeight.val().trim(),
                'width'             : $inputWidth.val().trim(),
                'weight'            : $inputWeight.val().trim(),
                'productProperties' : JSON.stringify(attributeUnitArray),
                'productImages'     : JSON.stringify(productImageContainer),
                'customBrand'       :  $inputBrand.attr('data-name').trim(),
                'productUnitId'     : productUnitId,
                'youtubeUrl'        : $youtubeUrl.val().trim(),
                'primaryImageId'    : primaryImageId,
                '_token'            : csrfToken,
                'shippingCategory'  : shippingCategory,
                'productGroups'     : $("#product-groups").val()
            };

            if ($inputProductName.val().trim() === '') {
                $messageModal.find('.header-content').html('Product name is required');
                $messageModal.find('.detail-content').html('');
                $messageModal.modal('show');
                return false;
            } else if ($youtubeUrl.val().trim() !== '' && getYoutubeIdByUrl($youtubeUrl.val().trim()) === false) {
                $messageModal.find('.header-content').html('Invalid Youtube URL');
                $messageModal.find('.detail-content').html('');
                $messageModal.modal('show');
                return false;
            }

            $.ajax({
                url: Routing.generate('product_upload_draft'),
                type: 'json',
                data: formData,
                method: 'POST',
                beforeSend: function () {
                    $this.attr('disabled', true);
                    $this.find(".text").hide();
                    $this.find(".loader").show();
                },
                success: function (response) {
                    $this.attr('disabled', false);
                    $this.find(".text").show();
                    $this.find(".loader").hide();

                    if (response.isSuccessful) {
                        $messageModal.find('.header-content').html('Product Successfully Saved as Draft');
                        $messageModal.find('.detail-content').html('');
                        $messageModal.modal('show');
                        window.location.replace('/product-detail/' + response.slug);
                    }
                    else {
                        var errorMessage = '';
                        $.each(response.message, function (key, val) {
                            errorMessage += '- ' + val + '<br>';
                        });

                        $('#generalErrorMessage').html(errorMessage).show();
                        $('html,body').animate({scrollTop: $('.sticky-header').offset().top}, 800);
                    }

                }

            });

        });

        $('#inputSave').on('click', function() {
            var $this = $(this);
            var productId = $('#isFormUpdate').attr('data-product-id');
            var $inputProductName = $('#inputName');
            var $inputDescription = CKEDITOR.instances.completeDescription.getData();
            var $inputShortDescription = $('textarea#inputShortDescription');
            var $inputCategory = $('#inputCategory');
            var $inputBrand = $('#inputBrand');
            var $inputCondition = $('#inputCondition');
            var $inputIsFreeShipping = $('#inputIsFreeShipping');
            var $inputLength = $('#inputLength');
            var $inputHeight = $('#inputHeight');
            var $inputWidth = $('#inputWidth');
            var $inputWeight = $('#inputWeight');
            var $inputBasePrice = $('#inputBasePrice');
            var $inputDiscount = $('#inputDiscountedPrice');
            var $inputSku = $('#inputSku');
            var $youtubeUrl = $('#youtube-url');
            var csrfToken = $("meta[name=csrf-token]").attr("content");
            var productUnitId = 0,
                shippingCategory = shippingCategoryElem.val();

            if (productImageContainer.length === 0) {
                $messageModal.find('.header-content').html('Product Image is required');
                $messageModal.find('.detail-content').html('');
                $messageModal.modal('show');
                return false;
            } else if ($youtubeUrl.val().trim() !== '' && getYoutubeIdByUrl($youtubeUrl.val().trim()) === false) {
                $messageModal.find('.header-content').html('Invalid Youtube URL');
                $messageModal.find('.detail-content').html('');
                $messageModal.modal('show');
                return false;
            }

            if ($('#product-unit-id').length > 0) {
                productUnitId = $('#product-unit-id').val();
            }

            var formData = {
                'productId'        : productId,
                'name'             : $inputProductName.val().trim(),
                'description'      : $inputDescription,
                'shortDescription' : $inputShortDescription.val().trim(),
                'sku'              : $inputSku.val().trim(),
                'productCategory'  : $inputCategory.val().trim(),
                'brand'            : $inputBrand.val().trim(),
                'productCondition' : $inputCondition.val().trim(),
                'isFreeShipping'   : $inputIsFreeShipping.is(':checked') ? 1 : 0,
                'length'           : $inputLength.val().trim(),
                'height'           : $inputHeight.val().trim(),
                'width'            : $inputWidth.val().trim(),
                'weight'           : $inputWeight.val().trim(),
                'productProperties': JSON.stringify(attributeUnitArray),
                'productImages'    : JSON.stringify(productImageContainer),
                'customBrand'      :  $inputBrand.attr('data-name').trim(),
                'productUnitId'    : productUnitId,
                'youtubeUrl'       : $youtubeUrl.val().trim(),
                'primaryImageId'   : primaryImageId,
                '_token'           : csrfToken,
                'shippingCategory' : shippingCategory,
                'productGroups'    : $("#product-groups").val()
            };

            if (parseInt(productId) !== 0) {
                $.ajax({
                    url: Routing.generate('product_upload_edit_detail'),
                    type: 'json',
                    data: formData,
                    method: 'POST',
                    beforeSend: function () {
                        $this.attr('disabled', true);
                        $this.find(".text").hide();
                        $this.find(".loader").show();
                    },
                    success: function (response) {
                        $this.attr('disabled', false);
                        $this.find(".text").show();
                        $this.find(".loader").hide();

                        if (response.isSuccessful) {
                            $messageModal.find('.header-content').html('Product Successfully Updated');
                            $messageModal.find('.detail-content').html('');
                            $messageModal.modal('show');
                            window.location.replace('/product-detail/' + response.slug);
                        }
                        else {
                            var errorMessage = '';
                            $.each(response.message, function (key, val) {
                                errorMessage += '- ' + val + '<br>';
                            });

                            $('#generalErrorMessage').html(errorMessage).show();
                            $('html,body').animate({scrollTop: $('.sticky-header').offset().top}, 800);
                        }

                    }

                });
            }
            else {
                $.ajax({
                    url: Routing.generate('product_upload_detail'),
                    type: 'json',
                    data: formData,
                    method: 'POST',
                    beforeSend: function () {
                        $this.attr('disabled', true);
                        $this.find(".text").hide();
                        $this.find(".loader").show();
                    },
                    success: function (response) {
                        $this.attr('disabled', false);
                        $this.find(".text").show();
                        $this.find(".loader").hide();

                        if (response.isSuccessful) {
                            $messageModal.find('.header-content').html('Product Successfully Uploaded');
                            $messageModal.find('.detail-content').html('');
                            $messageModal.modal('show');
                            window.location.replace('/product-detail/' + response.slug);
                        }
                        else {
                            var errorMessage = '';
                            $.each(response.message, function (key, val) {
                                errorMessage += '- ' + val + '<br>';
                            });

                            $('#generalErrorMessage').html(errorMessage).show();
                            $('html,body').animate({scrollTop: $('.sticky-header').offset().top}, 800);
                        }

                    }

                });
            }

        });

        $(".category-selector-modal-trigger, .inputCategoryIcon").click(function() {

            if($('.category-selector-modal-trigger').data('readonly')){
                return false;
            }

            var parentCategoryId = parseInt($('#inputCategory').val()) === 0 ? 1 : parseInt($('#inputCategory').val());
            var categoryName = parseInt($('#inputCategory').val()) === 0 ? '' : $('#productCategoryName').val();
            var isTriggerClick = parseInt($('#inputCategory').val()) === 0 ? false : true;
            retrieveProductCategory(parentCategoryId, categoryName, isTriggerClick);
            $('#modalCategoryButton').attr('disabled', true);
            $('#modalCategorySearch').val('');
            $('.category-modal-breadcrumb').html('');

            $('.product-category-selector').modal({
                onVisible: function(){
                    //Set the position of the category button wrapper in the middle
                    $(".category-button-group .wrapper").each(function(){
                        var thisWrapper = $(this);
                        var heightOfcategoryWrapperContainer = $(this).outerHeight();
                        var heightOfcategoryButtonGroup = $(".category-button-group").outerHeight();

                        var negativeMarginTopOfCategoryWrapper = (heightOfcategoryButtonGroup - heightOfcategoryWrapperContainer -30)/2;

                        if(heightOfcategoryWrapperContainer < heightOfcategoryButtonGroup){
                            thisWrapper.animate({
                                marginTop : negativeMarginTopOfCategoryWrapper
                            });
                        }
                    });
                }
            }).modal('show');
        });

        $('#parentCategoryContainer').on('click', '.fetchCategory', function () {
            var $this = $(this);
            var categoryName = $this.text();
            var productCategoryId = parseInt($this.attr('data-id').trim());
            $("#modalCategoryButton").attr("disabled", true);

            if (parseInt($this.attr('data-hasChild')) === 1) {
                retrieveProductCategory(productCategoryId, categoryName);
            }
            else {
                $(".icon.icon-check").remove();
                $("#parentCategoryContainer .button.default").not($(this)).removeClass("active");
                $this.addClass("active");
                $this.append("<i class='icon icon-check hidden'></i>");
                $this.attr('data-name', categoryName);
                $(".icon.icon-check").transition({
                    animation: "horizontal flip",
                    duration: 800
                });
                $("#modalCategoryButton").attr("disabled", false);
            }
        });

        $('#modalCategoryButton').on('click', function () {
            var $inputCategory = $('#inputCategory');
            var activeCategory = $('#parentCategoryContainer').find('.active');
            var productCategoryId = activeCategory.attr('data-id').trim();

            if (parseInt($inputCategory.val()) !== 0) {
                var confirmChangeOfCategory = confirm('Changing of category will remove custom variants and variant combinations.' +
                    ' \n Do you still want to coninue?');

                if (!confirmChangeOfCategory) {

                    $('#modalCategoryCancel').trigger('click');
                    return false;
                }
            }

            if (productCategoryId !== 0) {
                $inputCategory.val(productCategoryId);
                $inputCategory.attr('data-parent-id', activeCategory.attr('data-parent-id'));
                $("#productCategoryName").val(activeCategory.attr('data-name').trim());

                productAttributeArray = [];
                attributeUnitArray = [];
                retrieveProductCategoryAttribute(productCategoryId);
            }

        });

        $('.category-modal-breadcrumb').on('click', '.fetchCategoryByBc', function () {
            var productCategoryId = $(this).attr('data-id');

            retrieveProductCategory(productCategoryId, 'get');
        });

        $('#modalCategoryAttributeSubmit').on('click', function (e) {
            var $this = $(this);
            var isUpdate = parseInt($this.attr('data-isupdate'));
            var newAttributeValues = $('#tokenfield').tokenfield('getTokens');
            var $attributeName = $('#attributeName');
            var attributeValues = [];
            var productAttributeId = isUpdate === 0 ? productAttributeArray.length + 1 : isUpdate;
            var valuesError = 0;
            var errorCount = 0;
            var successMessage = '';
            var oldAttributeName = $this.attr('data-oldName');

            $.each(newAttributeValues, function (key, attribute) {
                attributeValues.push(attribute.value);

                if(!restriction.test(attribute.value.trim())){
                    valuesError++;
                }

            });

            if (!restriction.test($attributeName.val().trim()) || $attributeName.val().trim() === '') {
                $attributeName.parent().addClass('form error');
                errorCount++;
            }
            else {
                $attributeName.parent().removeClass('form')
                                       .removeClass('error');
            }

            if (errorCount > 0) {
                return false;
            }

            if (attributeValues.length === 0) {
                $messageModal.find('.header-content').html('Variant Choice should not be empty');
                $messageModal.find('.detail-content').html('');
                $messageModal.modal('show');
                return false;
            }

            if (valuesError > 0) {
                $messageModal.find('.header-content').html('Variant choice should not contain special character.');
                $messageModal.find('.detail-content').html('');
                $messageModal.modal('show');
                return false;
            }

            if (checkIfAttributeNameExists($attributeName.val().trim(), oldAttributeName, isUpdate)) {
                $messageModal.find('.header-content').html('Variant name exists.');
                $messageModal.find('.detail-content').html('');
                $messageModal.modal('show');
                return false;
            }

            var newAttribute = {
                id: productAttributeId,
                name: $attributeName.val().trim(),
                values: attributeValues
            };

            if (isUpdate === 0) {
                successMessage = 'Variant Successfully Added';
                addAttributeNameAndValueHTML(newAttribute, false);
            }
            else {
                successMessage = 'Variant Successfully Updated';
                //addAttributeNameAndValueHTML(newAttribute, false);
                updateAttributeNameAndValueHTML(newAttribute, isUpdate);
            }

            $('#success-message').html(successMessage);
            $(".ui.modal").modal("hide");
            $(".success.modal").modal("show");

        });

        $('#addProductCombinationBtn').on('click', function () {
            $('#modalAttributeCombinationBtn').attr('data-isUpdate', '').attr('data-id', '');
            $('#modalUnitPrice, #modalUnitDiscount, #modalUnitSku, #modalUnitFinalPrice').val('');
            $(".add-image-product-combination-button-container").removeClass('hidden').show();
            retrieveAttributeInModal (productAttributeArray);
        });

        $('#modalAttributeCombinationBtn').on('click', function (e) {
            var $this = $(this);
            var combinationKey = $this.attr('data-isUpdate');
            var combinationId = $this.attr('data-id');
            var $unitPrice = $('#modalUnitPrice');
            var $unitSku = $('#modalUnitSku');
            var $unitFinalPrice = $('#modalUnitFinalPrice');
            var $unitDiscount = $('#modalUnitDiscount');
            var $unitLength = $('#modalUnitLength');
            var $unitHeight = $('#modalUnitHeight');
            var $unitWidth = $('#modalUnitWidth');
            var $unitWeight = $('#modalUnitWeight');
            var attributes = [];
            var id = combinationId === '' ? '' : combinationId;
            var errorCount = 0;
            var images = [];
            var newId = '';

            if ($unitSku.val().trim() === '' || parseInt($unitSku.val().trim()) === 0) {
                $unitSku.parent().addClass('error');
                errorCount++;
            }
            else {
                $unitSku.parent().removeClass('error');
            }

            if (!numbersAndDotOnly.test($unitLength.val().trim()) || $unitLength.val().trim() === '' || parseFloat($unitLength.val().trim()) === 0) {
                $unitLength.parent().addClass('error');
                errorCount++;
            }
            else {
                $unitLength.parent().removeClass('error');
            }

            if (!numbersAndDotOnly.test($unitWidth.val().trim()) || $unitWidth.val().trim() === '' || parseFloat($unitWidth.val().trim()) === 0) {
                $unitWidth.parent().addClass('error');
                errorCount++;
            }
            else {
                $unitWidth.parent().removeClass('error');
            }

            if(!numbersAndDotOnly.test($unitHeight.val().trim()) || $unitHeight.val().trim() === '' || parseFloat($unitHeight.val().trim()) === 0) {
                $unitHeight.parent().addClass('error');
                errorCount++;
            }
            else {
                $unitHeight.parent().removeClass('error');
            }

            if(!numbersAndDotOnly.test($unitWeight.val().trim()) || $unitWeight.val().trim() === '' || parseFloat($unitWeight.val().trim()) === 0) {
                $unitWeight.parent().addClass('error');
                errorCount++;
            }
            else {
                $unitWeight.parent().removeClass('error');
            }

            if (errorCount > 0) {
                return false;
            }

            var attributeValueErrorMsg = '';
            $('#modalAttributeDiv').find('.attributeNameAndValue').each(function () {
                var value = $(this).dropdown('get value');
                var attributeName = $(this).find('select').attr('data-name').trim();

                if (value == 0 || value == '') {
                    attributeValueErrorMsg += ' ' + (errorCount === 0 ? '' : ', ' ) + attributeName + ' \n';
                    errorCount++;
                }

            });

            if (errorCount > 0) {
                $messageModal.find('.header-content').html('Select variant choice for: \n' + attributeValueErrorMsg);
                $messageModal.find('.detail-content').html('');
                $messageModal.modal('show');
                return false;
            }

            $('.attributeNameAndValue').each(function () {
                var $this = $(this);
                var attributeName = $this.find('select').attr('data-name');
                var value = $this.dropdown('get value');
                var newAttribute = {
                    name: attributeName,
                    value:value
                };

                if (combinationId === '') {
                    id += value;
                }
                newId += value;

                attributes.push(newAttribute);
            });

            if (checkCombinationIfExists(id, newId, combinationId)) {
                $messageModal.find('.header-content').html('Variant Combination Exists.');
                $messageModal.find('.detail-content').html('');
                $messageModal.modal('show');
                return false;
            }

            $('.unitImage').each(function() {
                var $this = $(this);
                if ($this.is(":checked")) {
                    images.push({
                        id: $this.attr("data-name"),
                        name: $this.attr("data-name"),
                        isNew: $this.attr("data-isNew")
                    });
                }
            });

            var productAttributeUnit = {
                id: id.toUpperCase(),
                key: combinationKey,
                newIdCombination: newId,
                price: $unitPrice.val().trim().replace(/\,/g,''),
                discountedPrice: $unitFinalPrice.val().trim().replace(/\,/g,''),
                discount: $unitDiscount.val().trim(),
                sku: $unitSku.val().trim(),
                quantity: 0,
                unitHeight: $unitHeight.val().trim(),
                unitWidth: $unitWidth.val().trim(),
                unitLength: $unitLength.val().trim(),
                unitWeight: $unitWeight.val().trim(),
                images: images,
                attributes: attributes,
                autoGenerated: false
            };

            if (combinationKey === '') {
                $messageModal.find('.header-content').html('Combination Successfully Created.');
                $messageModal.find('.detail-content').html();
                productAttributeUnit.key = attributeUnitArray.length + 1;
                addCombinationHTML (productAttributeUnit);
            }
            else {
                $messageModal.find('.header-content').html('Combination Successfully Updated.');
                $messageModal.find('.detail-content').html();
                updateCombinationHTML (productAttributeUnit);
            }

            $('#attributeCombinationDiv table').show();
            $('#productUnitSkuQtyDiv, #div-dimensions-weight').hide();
            $('#inputSku, #modalUnitDiscount, #modalUnitFinalPrice').val('');
            $unitLength.val('');
            $unitWidth.val('');
            $unitHeight.val('');
            $unitWeight.val('');
            $(".add-product-combination-modal").modal("hide");
            $messageModal.modal('show');
        });

        $('#inputName').on('focusout', function () {
            var $this = $(this);
            var parent = $this.parent();

            if ($this.val() === '') {
                parent.addClass('error');
            }
            else {
                parent.attr('class', 'form');
            }

        });

        $('#inputBasePrice, #inputDiscount').on('focusout', function () {
            var basePrice = $('#inputBasePrice').val().replace(/\,/g,'');
            var discount = $('#inputDiscount').val().trim().replace(/\,/g,'');
            $('#inputDiscountedPrice').val('');

            if(!numbersAndDotOnly.test(basePrice)) {
                $messageModal.find('.header-content').html("Price should only contain numbers.");
                $messageModal.find('.detail-content').html('');
                $messageModal.modal('show');
                $('#inputBasePrice').val('');
                return false;
            }
            else if (!numbersAndDotOnly.test(discount)) {
                $messageModal.find('.header-content').html("Discount should only contain numbers.");
                $messageModal.find('.detail-content').html('');
                $messageModal.modal('show');
                $('#inputDiscount').val('');
                return false;
            }

            if (basePrice === '') {
                return false;
            }

            if (discount > 100) {
                $messageModal.find('.header-content').html('Discount cannot be greater than 100%.');
                $messageModal.find('.detail-content').html('');
                $messageModal.modal('show');
                $('#inputDiscountedPrice').val(basePrice);
                $('#inputDiscount').val('');
                return false;
            }

            if (basePrice !== 0 && discount === '') {
                discount = 0;
            }

            var sum = basePrice - ((parseFloat(discount) / 100) * parseFloat(basePrice)) ;
            $('#inputDiscountedPrice').val(sum.format(2));
            $('#inputBasePrice').val(parseFloat(basePrice).format(2));
        });

        $('#modalUnitPrice, #modalUnitDiscount').on('focusout', function () {
            var basePrice = $('#modalUnitPrice').val().replace(/\,/g,'');
            var discount = $('#modalUnitDiscount').val().replace(/\,/g,'');
            $('#modalUnitFinalPrice').val('');

            if(!numbersAndDotOnly.test(basePrice)) {
                $messageModal.find('.header-content').html('Price should only contain numbers.');
                $messageModal.find('.detail-content').html('');
                $messageModal.modal('show');
                $('#modalUnitPrice').val('');
                return false;
            }
            else if (!numbersAndDotOnly.test(discount)) {
                $messageModal.find('.header-content').html('Discount should only contain numbers.');
                $messageModal.find('.detail-content').html('');
                $messageModal.modal('show');
                $('#modalUnitDiscount').val('');
                return false;
            }

            if (basePrice === '') {
                return false;
            }

            if (discount > 100) {
                $messageModal.find('.header-content').html('Discount cannot be greater than 100%.');
                $messageModal.find('.detail-content').html('');
                $messageModal.modal('show');
                $('#modalUnitDiscount').val('');
                $('#modalUnitFinalPrice').val(basePrice);
                return false;
            }

            if (basePrice !== 0 && discount === '') {
                discount = 0;
            }

            var sum = basePrice - ((parseFloat(discount) / 100) * parseFloat(basePrice)) ;
            $('#modalUnitFinalPrice').val(sum.format(2));
            $('#modalUnitPrice').val(parseFloat(basePrice).format(2));

        });

        $(document).on('click', '#btn-validate-youtube-url', function () {
            var youtubeUrl = $('#youtube-url').val().trim();
            var youtubeId = getYoutubeIdByUrl(youtubeUrl);
            var thumbnail = 'http://img.youtube.com/vi/' + youtubeId + '/0.jpg';
            var youtubeVideoUrl = 'https://www.youtube.com/embed/' + youtubeId;
            var $youtubeValidContainer = $('#youtube-valid-url');
            var $youtubeInvalidContainer = $('#youtube-invalid-url');
            $youtubeValidContainer.hide();
            $youtubeInvalidContainer.hide();

            if (youtubeId === false) {
                $youtubeInvalidContainer.show();
            } else {
                $youtubeValidContainer.find('img.embed-video').attr('src', thumbnail);
                $youtubeValidContainer.show();
                $('#video-success-message').show();
                $('#youtube-frame').attr('src', youtubeVideoUrl);
            }

        });

        $(document).on('click', '#remove-youtube-video', function () {
            var $youtubeValidContainer = $('#youtube-valid-url');
            var $youtubeInvalidContainer = $('#youtube-invalid-url');
            $('#youtube-url').val('');
            $youtubeValidContainer.hide();
            $youtubeInvalidContainer.hide();
            $youtubeValidContainer.find('img').attr('src', '');
            $('#video-success-message').hide();
            $('#youtube-frame').attr('src', '');
        });

        $('#generalErrorMessage, #attributeContainer .table').hide();
        $('#addProductCombinationBtn, #attributeCombinationDiv, #attributeCombinationDiv table').hide();

        if (parseInt(isUpdate) === 0) {
            onLoadAction();
        }
        else {
            /**
             * Update Product , Display Product Details
             */
            var $updateProductAttributeCategory = $('.updateProductAttributeCategory');
            var $updateProductUnit = $('.updateProductUnit');
            var youtubeUrl = $('#youtube-url').val().trim();
            productAttributeArray = [];

            //Category Attributes
            $updateProductAttributeCategory.each(function (key, value) {
                var $this = $(this);
                var attribute = JSON.parse($this.val());
                attribute['id'] = productAttributeArray.length + 1;
                addAttributeNameAndValueHTML(attribute, true);
            });

            if ($updateProductUnit.length > 0) {

                //AttributeCombination / Unit
                $updateProductUnit.each(function (key, value) {
                    var $this = $(this);
                    var productUnit = JSON.parse($this.val());
                    productUnit['key'] = 'old-' + productUnit['productUnitId'];
                    addCombinationHTML (productUnit);
                });

                $('#productUnitSkuQtyDiv, #div-dimensions-weight').hide();
                $('#inputSku').val('');

            }

            if (youtubeUrl !== '') {
                $('#btn-validate-youtube-url').trigger('click');
            }

        }

        var $img = $('.cropper-profile-photo > img');
        $img.attr('src', '').attr('data-name', '').attr('data-id', '');
        $img.cropper({
            aspectRatio: 16 / 18,
            autoCropArea: 1,
            strict: false,
            minContainerHeight: 400,
            maxContainerHeight: 400,
            minContainerWidth: 400,
            maxContainerWidth: 400,
            minCropBoxWidth: 400,
            maxCropBoxWidth: 400,
            minCropBoxHeight: 400,
            maxCropBoxHeight: 400,
            guides: false,
            highlight: false,
            dragCrop: false,
            cropBoxMovable: false,
            cropBoxResizable: false,
            responsive: false
        });

        function processImageToResize ($this, file)
        {
            var browserWindow = window.URL || window.webkitURL;
            var objectUrl = browserWindow.createObjectURL(file);

            var $promise = createImage(objectUrl);

            $promise.done(function($object) {
                resize($object, $this);
            });
        }

        /**
         * Create image
         *
         * @param src
         * @returns {*}
         */
        function createImage (src)
        {
            var deferred = $.Deferred();
            var img = new Image();

            img.onload = function() {
                $(img).attr("naturalWidth", this.naturalWidth);
                $(img).attr("naturalHeight", this.naturalHeight);

                deferred.resolve(img);
            };

            img.src = src;
            return deferred.promise();
        }

        function resize (image, $this)
        {
            var $height = parseInt($(image).attr("naturalheight"));
            var $width = parseInt($(image).attr("naturalwidth"));

            mainCanvas = document.createElement("canvas");

            if($width > $height){
                var $multiplier = 1 - (($width/$height)%1);
                mainCanvas.width = 1280;
                mainCanvas.height = 1280 * $multiplier;
            }
            else{
                var $multiplier = 1 - (($height/$width)%1);
                mainCanvas.height = 1280;
                mainCanvas.width = 1280 * $multiplier;
            }

            var ctx = mainCanvas.getContext("2d");

            ctx.drawImage(image, 0, 0, mainCanvas.width, mainCanvas.height);

            var newFile = dataURItoBlob(mainCanvas.toDataURL("image/jpeg"));

            newFile.cropped = true;
            newFile.isCropping = false;
            newFile.name = $this.attr('data-name');

            $(this).attr('disabled', true).text('Loading');
            $('#crop-modal').modal('hide');
            $dropzone.addFile(newFile);
            $dropzone.processQueue();
            deleteInArrayById (imageCropContainer, $this.attr('data-id'));

            /**
             * Re opens another modal to crop another image and sends to server
             */
            if (imageCropContainer.length > 0) {
                renderImageToModal (imageCropContainer[0].file, imageCropContainer[0].id);
            }

        }

    });

    /**
     * Retrieve Product Category in Modal
     * @param productCategoryId
     * @param name
     * @param isTriggerClick
     */
    function retrieveProductCategory (productCategoryId, name, isTriggerClick)
    {
        var $categoryContainer = $('#parentCategoryContainer');

        $.ajax({
            url: Routing.generate('product_search_child_category'),
            type: 'json',
            data: {productCategoryId: productCategoryId},
            method: 'GET',
            beforeSend: function () {
            },
            success: function (response) {

                if (response.productCategory) {
                    var htmlCategories = '';

                    $.each(response.productCategory, function (key, value) {
                        htmlCategories += '<button class="button default fetchCategory"' +
                            ' data-id="'+ value.productCategoryId +'"' +
                            ' data-hasChild="'+ value.hasChildren +'"' +
                            ' data-parent-id="'+ productCategoryId +'"' +
                            '>' + value.name + '</button>'
                    });

                    $categoryContainer.transition({
                        animation: "scale",
                        onComplete : function() {
                            $categoryContainer.transition({
                                animation: "scale",
                                interval:   500
                            });

                            if (name !== '') {
                                retrieveBreadCrumb(response.parents)
                            }

                            $categoryContainer.html(htmlCategories);

                            if (isTriggerClick) {
                                $("#modalCategoryButton").attr("disabled", false);

                                $.each($('.fetchCategory'), function (key, value) {
                                    var $this = $(this);
                                    if ($this.html().trim() === name) {
                                        $this.trigger('click');
                                    }
                                });
                            }

                        }
                    });
                }

            }

        });
    }

    /**
     * Retrieve BreadCrumb in Selection of category modal
     * @param productCategoryParents
     */
    function retrieveBreadCrumb (productCategoryParents)
    {
        var $breadCrumbContainer = $('.category-modal-breadcrumb');
        var html = '<li><a href="#" class="fetchCategoryByBc" data-id="1">All Categories</a></li>';

        $.each(productCategoryParents, function (key, value) {
            html += '<li><a href="#" class="fetchCategoryByBc" data-id="' + value.product_category_id + '">' + value.name + '</a></li>';
        });

        $breadCrumbContainer.html(html);
    }

    /**
     * Get and retrieves product category attribute in list
     * @param productCategoryId
     */
    function retrieveProductCategoryAttribute (productCategoryId)
    {
        $('#attributeRowContainer').parent().hide();
        $('#addProductCombinationBtn, #attributeCombinationDiv').hide();
        $('#attributeRowContainer').html('');
        $('#productUnitSkuQtyDiv, #div-dimensions-weight').show();
        $('#attributeCombinationRow').html('');
        $('#attributeCombinationDiv .table').hide();

        $.ajax({
            url: Routing.generate('product_search_category_attribute'),
            type: 'json',
            method: 'GET',
            data: {productCategoryId: productCategoryId},
            beforeSend: function () {
                productAttributeArray = [];
            },
            success: function (response) {

                if (response.length > 0) {

                    $.each(response, function (key, attribute) {
                        attribute['id'] = productAttributeArray.length + 1;
                        addAttributeNameAndValueHTML(attribute, false);
                    });

                }

            }
        });

    }

    /**
     * Add Attribute name and value in list and in array container
     * @param newAttribute
     */
    function addAttributeNameAndValueHTML (newAttribute, isUpdate)
    {
        var $attributeRowContainer = $('#attributeRowContainer');
        var htmlAttributeValue = '';

        if (typeof newAttribute.values === "object" ) {
            var arrayAttributes = [];

            $.each(newAttribute.values, function(id, value) {
                arrayAttributes.push(value);
            });

            newAttribute.values = arrayAttributes;
        }

        $.each(newAttribute.values, function (id, value) {
            htmlAttributeValue += '<span class="badge">' + value + '</span>';
        });

        var html =
            '<tr id="categoryAttributeRow_' + newAttribute.id + '">' +
                '<td>' + newAttribute.name + '</td>' +
                '<td>' + htmlAttributeValue + '</td>' +
                '<td>' +
                    '<button class="button confirm small editAttributeNameValue" data-id="' + newAttribute.id + '" >Edit</button> ' +
                    '<button class="button small cancel deleteAttributeNameValue" data-id="' + newAttribute.id + '" >Remove</button>' +
                '</td>' +
            '</tr>';

        productAttributeArray.push(newAttribute);
        $attributeRowContainer.append(html);
        $('#attributeContainer .table').show();
        $('#addProductCombinationBtn, #attributeCombinationDiv').show();

        if (isUpdate === false) {
            autoGenerateCombination();
        }

    }

    /**
     * Update Attribute name and value in list and in array container
     * @param attribute
     * @param id
     */
    function updateAttributeNameAndValueHTML (attribute, id)
    {
        var $attributeRow = $('#categoryAttributeRow_' + id);
        var htmlAttributeValue = '';

        $.each(attribute.values, function (id, value) {
            htmlAttributeValue += '<span class="badge">' + value + '</span>';
        });

        var html =
            '<td>' + attribute.name + '</td>' +
            '<td>' + htmlAttributeValue + '</td>' +
            '<td>' +
                '<button class="button confirm small editAttributeNameValue" data-id="' + id + '" >Edit</button> ' +
                '<button class="button small cancel deleteAttributeNameValue" data-id="' + id + '" >Remove</button>' +
            '</td>';


        $.each(productAttributeArray, function (key, val) {

            if (parseInt(val.id) === parseInt(id)) {
                //updateAttributeInCombination (productAttributeArray[key], attribute);
                productAttributeArray[key]['name'] = attribute.name;
                productAttributeArray[key]['values'] = attribute.values;
            }

        });

        attributeUnitArray = [];
        autoGenerateCombination ();

        $('#modalCategoryAttributeSubmit').attr('data-isUpdate', '0');
        $attributeRow.html(html);
    }

    /**
     * Remove Attribute In Combination
     */
    function removeAttributeInCombination (attribute)
    {

        for (var valueKey = 0; valueKey < attribute['values'].length; valueKey++) {

            for (var unitKey = 0; unitKey < attributeUnitArray.length; unitKey++) {
                var canRemoveCombination = false;

                for (var ctr = 0; ctr < attributeUnitArray[unitKey]['attributes'].length; ctr++) {

                    if (attributeUnitArray[unitKey]['id'] == attribute['values'][valueKey].toUpperCase()) {
                        canRemoveCombination = true;
                    }

                    if (attributeUnitArray[unitKey]['attributes'][ctr]['name'] == attribute.name) {
                        attributeUnitArray[unitKey]['attributes'].splice(ctr, 1);
                    }

                }

                if (canRemoveCombination === true) {
                    $('#attributeCombination_' + attributeUnitArray[unitKey]['key']).remove();
                    attributeUnitArray.splice(unitKey, 1);
                }

            }

        }

        /**
         * Refresh attribute id and Update HTML Combination
         */
        for (var key = 0; key < attributeUnitArray.length; key++) {
            var id = '';

            $.each(attributeUnitArray[key]['attributes'], function (attributeKey, attribute) {
                id += attribute.value.toUpperCase();
            });

            attributeUnitArray[key]['id'] = id;

            if (id === '') {
                attributeUnitArray.splice(key, 1);
            }
            else {
                updateCombinationHTML (attributeUnitArray[key]);
            }

        }

        /**
         *
         */
        for (var key2 = 0; key2 < attributeUnitArray.length; key2++) {
            var unitId = '';
            var isDuplicate = false;

            $.each(attributeUnitArray[key2]['attributes'], function (attributeKey, attribute) {
                unitId += attribute.value.toUpperCase();
            });

            attributeUnitArray[key2]['id'] = unitId;

            $.each(attributeUnitArray, function (unitKey, unit) {
                if (unit.id == unitId && unit.key != attributeUnitArray[key2]['key']) {
                    isDuplicate = true;
                }
            });

            if (isDuplicate === true) {
                $('#attributeCombination_' + attributeUnitArray[key2]['key']).remove();
                deleteInCombinationByKey (attributeUnitArray, attributeUnitArray[key2]['key']);
            }

        }

        if (attributeUnitArray.length === 0) {
            $('#attributeCombinationDiv .table').hide();
            $('#productUnitSkuQtyDiv, #div-dimensions-weight').show();
        }

    }

    /**
     * Update Attribute in combination
     *
     * @param attribute
     * @param updatedAttribute
     */
    function updateAttributeInCombination (attribute, updatedAttribute)
    {
        var removedAttributeValues = $(attribute.values).not(updatedAttribute.values).get();

        /**
         * - Search for attributeName
         *      update attributeName
         * - Check if attributeValue still exist
         *      if exist = do nothing
         *      if not = get first attributeValue
         */
        $.each(attributeUnitArray, function (key, combination) {

            $.each(combination.attributes, function (combinationAttributeKey, combinationAttribute) {

                if (combinationAttribute.name == attribute.name) {
                    var isRemoved = false;
                    attributeUnitArray[key]['attributes'][combinationAttributeKey]['name'] = updatedAttribute.name;

                    $.each(removedAttributeValues, function (removedKey, removedValue) {

                        if (removedValue == combinationAttribute.value) {
                            isRemoved = true;
                        }

                    });

                    if (isRemoved === true) {
                        attributeUnitArray[key]['attributes'][combinationAttributeKey]['value'] = updatedAttribute.values[0];
                    }

                }

            });

        });

        /**
         * Refresh attribute id and Update HTML Combination
         */
        for (var key = 0; key < attributeUnitArray.length; key++) {
            var id = '';

            $.each(attributeUnitArray[key]['attributes'], function (attributeKey, attribute) {
                id += attribute.value.toUpperCase();
            });

            attributeUnitArray[key]['id'] = id;

            if (id === '') {
                attributeUnitArray.splice(key, 1);
            }
            else {
                updateCombinationHTML (attributeUnitArray[key]);
            }

        }

        /**
         * Remove duplicate entry
         */
        for (var key2 = 0; key2 < attributeUnitArray.length; key2++) {
            var unitId = '';
            var isDuplicate = false;

            $.each(attributeUnitArray[key2]['attributes'], function (attributeKey, attribute) {
                unitId += attribute.value.toUpperCase();
            });

            attributeUnitArray[key2]['id'] = unitId;

            $.each(attributeUnitArray, function (unitKey, unit) {
                if (unit.id == unitId && unit.key != attributeUnitArray[key2]['key']) {
                    isDuplicate = true;
                }
            });

            if (isDuplicate === true) {
                $('#attributeCombination_' + attributeUnitArray[key2]['key']).remove();
                deleteInCombinationByKey (attributeUnitArray, attributeUnitArray[key2]['key']);
            }

        }

    }

    /**
     * Auto Generate Combination Base on Variants/Attribute
     */
    function autoGenerateCombination ()
    {
        var attributeValuesArray = [];
        attributeUnitArray = [];
        $('#attributeCombinationRow').html('');

        $.each(productAttributeArray, function (attributeKey, attribute) {
            attributeValuesArray.push(attribute.values);
        });

        $.each(getAllPossibleCombination(attributeValuesArray), function (key, combination) {
            var attributeValueCombination = combination.slice(0, -1).split("~");
            var attributes = [];
            var attributeId = '';

            $.each(attributeValueCombination, function (key, value) {
                attributes.push({
                    name: getAttributeNameByValue(value),
                    value: value
                });
                attributeId += value;
            });

            var productAttributeUnit = {
                id: attributeId.toUpperCase(),
                key: ++attributeUnitKey,
                price: 0,
                discountedPrice: 0,
                discount: 0,
                sku: 0,
                quantity: 0,
                unitHeight: 0,
                unitWidth: 0,
                unitLength: 0,
                unitWeight: 0,
                images: [],
                attributes: attributes,
                autoGenerated: true
            };

            addCombinationHTML (productAttributeUnit);
            $('#productUnitSkuQtyDiv, #div-dimensions-weight').hide();
            $('#inputSku, #modalUnitDiscount, #modalUnitFinalPrice').val('');
        });

    }

    /**
     * Get Attribute name by value
     *
     * @param value
     * @returns {string}
     */
    function getAttributeNameByValue(value)
    {
        var attributeName = '';

        $.each(productAttributeArray, function (attributeKey, attribute) {

            $.each(attribute.values, function (attributeValueKey, attributeValue) {
                if (attributeValue === value) {
                    attributeName = attribute.name;
                }
            })

        });

        return attributeName;
    }

    /**
     * Get all possible combinations
     *
     * @param array
     * @param prefix
     * @returns {*}
     */
    function getAllPossibleCombination(array, prefix)
    {
        prefix = prefix || '';

        if (!array.length) {
            return prefix;
        }

        return results = array[0].reduce(function (result, value) {
            return result.concat(getAllPossibleCombination(array.slice(1), prefix + value + '~'));
        }, []);
    }

    /**
     * Add Attribute in combination
     *
     * @param newAttribute
     */
    function addAttributeInCombination (newAttribute)
    {
        var attribute = {
            name: newAttribute.name,
            value: newAttribute.values[0]
        };

        $.each(attributeUnitArray, function (key, productUnit) {
            var id = '';
            attributeUnitArray[key]['attributes'].push(attribute);

            $.each(attributeUnitArray[key]['attributes'], function (key, attribute) {
                id += attribute.value.toUpperCase();
            });

            attributeUnitArray[key]['id'] = id;

            updateCombinationHTML (attributeUnitArray[key]);
        });

    }

    /**
     * Shows the attribute name and value including image in creating product combination modal.
     * @param attributeNameAndValue
     */
    function retrieveAttributeInModal (attributeNameAndValue)
    {
        var productId = $('#isFormUpdate').attr('data-product-id');
        var $attributeDiv = $('#modalAttributeDiv');
        var $chosenImageDiv = $('#chosenImageDiv');
        var html = '';
        var imageHtml = '';

        $.each(attributeNameAndValue, function (id, attribute) {
            var htmlAttributeValue = '';

            $.each(attribute.values, function (id, value) {
                htmlAttributeValue += '<option value="' + value + '">' + value + '</option>';
            });

            html +='' +
                '<div class="col-md-4">' +
                    '<label for="">' + attribute.name.toUpperCase() + '</label>' +
                    '<select class="form-ui ui single selection dropdown attributeNameAndValue select_'+ attribute.name.split(' ').join('_') +'" data-name="'+ attribute.name +'" >' +
                        '<option value="">Select ' + attribute.name + ' Here</option>' +
                        htmlAttributeValue +
                    '</select>' +
                '</div>'
                ;

        });

        /**
         * Retrieve Image
         */
        $.each(productImageContainer, function (id, image) {
            var imageFolder = 'temp/';

            if (typeof image.isRemoved == 'undefined' || image.isRemoved === false) {

                if (image.isNew === false || image.isNew === 'false') {
                    imageFolder = productId + '/';
                }

                imageHtml += '<div class="product-generic-image-list-wrapper">' +
                                 '<input type="checkbox" class="unitImage" data-isNew="' + image.isNew + '" data-id="' + image.id + '" data-name="' + image.image + '"/>' +
                                 '<img src="' + assetHostname + imageLocation + imageFolder + image.image + '" alt="" class="img-responsive img-auto-place">' +
                             '</div>';
            }

        });

        $('.image-selection-list-container').removeClass('visible').hide();
        $chosenImageDiv.html(imageHtml);
        $attributeDiv.html(html);
        $('.attributeNameAndValue').dropdown();
    }

    /**
     * Add chosen combination in list and in array
     * @param attributeCombination
     */
    function addCombinationHTML (attributeCombination)
    {
        var productId = $('#isFormUpdate').attr('data-product-id');
        var $attributeCombinationRow = $('#attributeCombinationRow');
        var htmlAttributeValue = '';
        var htmlImage = '';
        var autoGeneratedClass = typeof attributeCombination.autoGenerated == 'undefined' || attributeCombination.autoGenerated == false ? '' : 'class="auto-generated"';

        $.each(attributeCombination.attributes, function (key, val) {
            htmlAttributeValue += '<span class="badge"><b>' + val.name + ': </b> ' + val.value + '</span> ';
        });

        $.each(attributeCombination.images, function (key, image) {
            var imageFolder = 'temp/';

            if (image.isNew === false || image.isNew === 'false') {
                imageFolder = productId + '/';
            }

            htmlImage += '<div class="product-combination-image-wrapper">' +
                '<img src="' + assetHostname + imageLocation + imageFolder + image.name + '" alt="" class="img-responsive img-auto-place">' +
                '</div>';
        });

        html =
            '<tr id="attributeCombination_' + attributeCombination.key +'" '+ autoGeneratedClass +'>' +
                '<td>' +
                    '<div class="product-combination-image-group">' + htmlImage +
                    '</div>' +
                '</td>' +
                '<td>' + htmlAttributeValue +'</td>' +
                '<td>' + attributeCombination.sku + '</td>' +
                '<td>' +
                    '<button class="button confirm small editAttributeCombination" data-id="' + attributeCombination.key +'">Edit</button> ' +
                '</td>' +
            '</tr>';

        attributeUnitArray.push(attributeCombination);
        $attributeCombinationRow.append(html);
        $('#attributeCombinationDiv table').show();
    }

    /**
     * Update chosen Combination in list and in array
     * @param attributeCombination
     */
    function updateCombinationHTML (attributeCombination)
    {
        var productId = $('#isFormUpdate').attr('data-product-id');
        var $attributeCombinationRow = $('#attributeCombination_' + attributeCombination.key);
        var htmlAttributeValue = '';
        var htmlImage = '';

        $.each(attributeCombination.attributes, function (key, val) {
            htmlAttributeValue += '<span class="badge"><b>' + val.name + ': </b> ' + val.value + '</span> ';
        });

        $.each(attributeCombination.images, function (key, image) {
            var imageFolder = 'temp/';

            if (image.isNew === false || image.isNew === 'false') {
                imageFolder = productId + '/';
            }

            htmlImage += '<div class="product-combination-image-wrapper">' +
                             '<img src="' + assetHostname + imageLocation + imageFolder + image.name + '" alt="" class="img-responsive img-auto-place">' +
                         '</div>';
        });

        html =
            '<td>' +
            '<div class="product-combination-image-group">' + htmlImage +
            '</div>' +
            '</td>' +
            '<td>' + htmlAttributeValue +'</td>' +
            '<td>' + attributeCombination.sku + '</td>' +
            '<td>' +
                '<button class="button confirm small editAttributeCombination" data-id="' + attributeCombination.key +'">Edit</button> ' +
            '</td>';

        /**
         * Update Product Combination
         */
        $.each(attributeUnitArray, function (key, val) {

            if (val.key == attributeCombination.key) {

                if (typeof attributeCombination.newIdCombination != 'undefined') {
                    attributeUnitArray[key]['id'] = attributeCombination.newIdCombination;
                }
                attributeUnitArray[key]['price'] = attributeCombination.price;
                attributeUnitArray[key]['discountedPrice'] = attributeCombination.discountedPrice;
                attributeUnitArray[key]['discount'] = attributeCombination.discount;
                attributeUnitArray[key]['quantity'] = attributeCombination.quantity;
                attributeUnitArray[key]['sku'] = attributeCombination.sku;
                attributeUnitArray[key]['unitLength'] = attributeCombination.unitLength;
                attributeUnitArray[key]['unitWidth'] = attributeCombination.unitWidth;
                attributeUnitArray[key]['unitHeight'] = attributeCombination.unitHeight;
                attributeUnitArray[key]['unitWeight'] = attributeCombination.unitWeight;
                attributeUnitArray[key]['attributes'] = attributeCombination.attributes;
                attributeUnitArray[key]['images'] = attributeCombination.images;
                attributeUnitArray[key]['autoGenerated'] = false;
            }

        });

        $('#modalAttributeCombinationBtn').attr('data-isUpdate', '');
        $attributeCombinationRow.html(html);
        $attributeCombinationRow.removeClass('auto-generated');
    }

    /**
     * Remove unnecessary strings in inputs
     */
    function onLoadAction ()
    {
        $('#inputName, #productCategoryName, #inputBasePrice, #inputDiscount, #inputSku,' +
            ' #inputLength, #inputWidth, #inputHeight, #inputWeight,' +
            '#inputShortDescription, #inputDescription, #inputDiscountedPrice').val('');
        $('#inputCategory').val(0);
        $('#inputBrand').val(1);
        $('#productBrands .typeahead').val('');
    }

    /**
     * Check if Attribute Name exist.
     * @param attributeName
     * @param oldAttributeName
     * @param isUpdate
     * @returns {boolean}
     */
    function checkIfAttributeNameExists (attributeName, oldAttributeName, isUpdate)
    {
        var isExists = false;

        $.each(productAttributeArray, function (key, attribute) {

            if (attribute.name.toUpperCase() === attributeName.toUpperCase()) {
                isExists = true;

                if (isUpdate !== 0 && (attribute.name.toUpperCase() === oldAttributeName.toUpperCase())) {
                    isExists = false;
                }

            }

        });

        return isExists;
    }

    /**
     * Check if Combination exist
     * @param id
     * @param newId
     * @param isUpdate
     * @returns {boolean}
     */
    function checkCombinationIfExists (id, newId, isUpdate)
    {
        var isExists = false;

        for(var i = 0; i < attributeUnitArray.length; i++) {

            if (attributeUnitArray[i].id === id.toUpperCase()) {
                isExists = true;

                if (isUpdate !== '') {
                    var checker = false;

                    for(var i = 0; i < attributeUnitArray.length; i++) {

                        if ((newId.toUpperCase() === attributeUnitArray[i].id) &&
                            (newId.toUpperCase() !== id.toUpperCase())) {
                            checker = true;
                            break;
                        }

                    }

                    if (checker === false) {
                        isExists = false;
                    }
                }


                break;
            }

        }

        return isExists;
    }

    /**
     * Checks if image is used in combination
     * @param imageId
     * @returns {boolean}
     */
    function isImageIsUsedInCombination (imageId)
    {
        var isUsedCount = 0;

        $.each(attributeUnitArray, function (key, unit) {

            $.each(unit.images, function (key, image) {

                if (image.id == imageId) {
                    isUsedCount++;
                }

            })

        });

        return isUsedCount > 0;
    }

    /**
     * Native way in deleting array in obj
     * @param container
     * @param key
     */
    function deleteInCombinationByKey (container, key)
    {
        var arrayContainer = container;
        for(var i = 0; i < arrayContainer.length; i++) {
            if (arrayContainer[i].key == key) {

                if (arrayContainer[i].isNew === false) {
                    arrayContainer[i].isRemoved = true;
                }
                else {
                    arrayContainer.splice(i, 1);
                }

                break;
            }
        }

        return arrayContainer;
    }

    /**
     * Native way in deleting array in obj
     * @param container
     * @param id
     */
    function deleteInArrayById (container, id)
    {
        var arrayContainer = container;
        for(var i = 0; i < arrayContainer.length; i++) {
            if (arrayContainer[i].id == id) {

                if (arrayContainer[i].isNew === false) {
                    arrayContainer[i].isRemoved = true;
                }
                else {
                    arrayContainer.splice(i, 1);
                }

                break;
            }
        }

        return arrayContainer;
    }

    /**
     * Check if Attribute/Variant is selected in product combination
     */
    function isAttributeUsedInCombination (attributeValue)
    {

        var isAttributeExists = false;

        $.each(attributeUnitArray, function (attributeUnitKey, attributeUnit) {
            var attributeArray = attributeUnit.attributes;

            $.each(attributeArray, function (attributeKey, attribute) {

                if (attributeValue !== '') {

                    if (attributeValue == attribute.value) {
                        isAttributeExists = true;
                    }

                }
                else {

                    $.each(productAttributeArray, function (key, productAttribute) {
                        var attributeValues = productAttribute.values;

                        $.each(attributeValues, function (valueKey, value) {

                            if (value === attribute.value) {
                                isAttributeExists = true;
                            }

                        });

                    });

                }

            })

        });

        return isAttributeExists;
    }

    function dataURItoBlob(dataURI) {
        var byteString = atob(dataURI.split(',')[1]);
        var ab = new ArrayBuffer(byteString.length);
        var ia = new Uint8Array(ab);
        for (var i = 0; i < byteString.length; i++) {
            ia[i] = byteString.charCodeAt(i);
        }
        return new Blob([ab], { type: 'image/jpeg' });
    }

    function renderImageToModal (file, id)
    {
        var fileName = file.name;
        var reader = new FileReader();
        reader.onloadend = function () {
            isCropping = true;
            var $img = $('.cropper-profile-photo > img');
            $img.attr('data-name', fileName).attr('data-id', id);
            $img.cropper('replace', reader.result);
            $('#crop-modal')
                .modal({
                    closable: false,
                    onDeny: function () {
                        imageCropContainer = [];
                        isCropping = false;
                    }
                })
                .modal('show');
        };

        reader.readAsDataURL(file);
    }

    /**
     * Toggle button Loader
     *
     * @param $this
     * @param isShow
     */
    function toggleButtonLoader ($this, isShow)
    {

        if (typeof isShow == 'undefined') {
            isShow = true;
        }

        if (isShow) {
            $this.find(".text").hide();
            $this.find(".loader").show();
            $this.attr('disabled', true);
        }
        else {
            $this.find(".text").show();
            $this.find(".loader").hide();
            $this.attr('disabled', false);
        }

    }

    /**
     * Number.prototype.format(n, x)
     *
     * @param integer n: length of decimal
     * @param integer x: length of sections
     */
    Number.prototype.format = function(n, x) {
        var re = '\\d(?=(\\d{' + (x || 3) + '})+' + (n > 0 ? '\\.' : '$') + ')';
        return this.toFixed(Math.max(0, ~~n)).replace(new RegExp(re, 'g'), '$&,');
    };

    $(".video-modal-trigger").on("click", function(){
        $(".video-modal").modal("show");
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

    var dropdownGroupElem = $(".product-group-dropdown");

    dropdownGroupElem.dropdown({
        allowAdditions: true
    });

    dropdownGroupElem.dropdown('set selected', $.parseJSON($("#currentProductGroups").val()));

    /**
     * Change primary image
     *
     * @param $this
     */
    function changePrimaryImage($this)
    {
        $this.siblings('.dz-preview').each(function() {
            var $preview = $(this);
            $preview.removeClass('active');
            $preview.find('.primary-tag').remove();
        });
        $this.addClass('active');
        $this.prepend('<span class="primary-tag"><i class="icon icon-check"></i>Primary</span>');
        primaryImageId = $this.attr('product-image-id');
    }

    // shipping category
    var $results = [],
        $requestShippingCategory;
    $('.input-shipping-category').typeahead({
        hint: true,
        highlight: true,
        minLength: 1,
        autoselect: true
    },
    {
        displayKey: 'name',
        source: function (query, process) {
            var $loader = $("#shipping-category-form").find('img.loading-img');

            if ($requestShippingCategory) {
                $requestShippingCategory.abort();
            }

            $requestShippingCategory = $.ajax({
                url: Routing.generate('yilinker_core_shipping_category_search'),
                data: {query: query},
                method: 'get',
                dataType: 'json',
                beforeSend: function () {
                    $loader.show();
                },
                success: function (response) {
                    $.each(response.data, function (i, value) {
                        $results.push(value.name);
                    });

                    return process(response.data);
                },
                complete: function() {
                    $loader.hide();
                }
            });
        }
    }).on("typeahead:selected typeahead:autocompleted typeahead:change", function(e, data) {
        shippingCategoryElem.val(data.shippingCategoryId);
    }).blur(function(){
        if ($results.indexOf($(this).val()) < 0) {
            shippingCategoryElem.val('');
        }
    });


})(jQuery);
