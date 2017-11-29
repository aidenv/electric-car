(function ($) {

    $(document).ready(function () {
        var imageLocation = '/assets/images/uploads/products/';
        var productImageContainer = [];
        var modalCount = 0;
        var imageCropContainer = [];
        var isCropping = false;
        var primaryImageId = 0;
        var $messageModal = $('#modal-message-container');
        var assetHostname = $('#asset-hostname').val();
        var $formUpdateData = $('#is-form-update');
        var $generalErrorMessage = $('#generalErrorMessage');
        var locale = $('#locale').val().trim();
        var csrfToken = $("meta[name=csrf-token]").attr("content");
        var attributeUnitArray = [];

        CKEDITOR.replace('completeDescription', {
            filebrowserBrowseUrl    : '/merchant/upload/detail/image-view',
            filebrowserUploadUrl    : '/merchant/upload/detail/image',
            imgurClientId           : '213',
            filebrowserWindowWidth  : 800,
            filebrowserWindowHeight : 500
        });

        $(".row-header-box").stick_in_parent({
            parent: ".product-upload-wrapper"
        });

        $(".translate-combinations-trigger").on("click", function(){
            $('body').animate({
                scrollTop: $("#combinationsTables").offset().top
            }, 300);
        });

        //Dropzone upload module
        Dropzone.autoDiscover = false;
        var $dropzone = new Dropzone (
            ".dropzone-product-image",
            {
                url: Routing.generate("product_upload_add_image"),
                addRemoveLinks: !$(".dropzone-product-image").data('readonly'),
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
                                id            : imageId,
                                isNew         : false,
                                image         :  $this.attr('data-image'),
                                defaultLocale :  $this.attr('data-default-locale')
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

        $('#crop-btn').on('click', function() {
            isCropping = false;
            var $image = $('#crop-img');

            $image.cropper('getCroppedCanvas').toBlob( function (blob) {
                processImageToResize($image, blob);
            });

        });

        //Select Primary Image
        $(document).on('click', '.dz-preview', function() {
            changePrimaryImage($(this));
        });

        $(document).on('change', '.translated-attr-name', function () {
            var $this = $(this);
            var id = $this.attr('attr-name-id');
            var translation = $this.val().trim();

            if (translation == '') {
                translation = $this.attr('placeholder');
            }

            $('.attribute-name-' + id).html(translation);

        });

        $(document).on('change', '.translated-attr-value', function () {
            var $this = $(this);
            var ids = $this.attr('attr-value-id');
            var translation = $this.val().trim();

            if (translation == '') {
                translation = $this.attr('placeholder');
            }

            $.each(ids.split('-'), function (key, id) {
                $('.attribute-value-' + id).html(translation);
            });

        });

        $(document).on('click', '#inputSave', function () {
            var $this = $(this);
            var productId = parseInt($formUpdateData.attr('product-id'));
            var $inputProductName = $('#inputName');
            var $inputDescription = CKEDITOR.instances.completeDescription.getData();
            var $inputShortDescription = $('textarea#inputShortDescription');
            var attrNames = [];
            var attrValues = [],
                productGroups = [];

            if ($('.translated-attr-name').length > 0 && $('.spn-attr-value').length > 0) {
                var isAttributeFilledUp = true;

                $.each($('.translated-attr-name'), function (key, value) {
                    var $this = $(this);
                    var attrName = $this.val().trim();

                    if (attrName == '') {
                        isAttributeFilledUp = false;
                    }

                    attrNames.push({
                        id    : $this.attr('attr-name-id'),
                        value : attrName
                    });
                });

                $.each($('.translated-attr-value'), function (key, value) {
                    var $this = $(this);
                    var attrValue = $this.val().trim();

                    if (attrValue == '') {
                        isAttributeFilledUp = false;
                    }
                });

                $.each($('.spn-attr-value'), function (key, value) {
                    var $this = $(this);
                    attrValues.push({
                        id    : $this.attr('attr-value-id'),
                        value : $this.html().trim()
                    });
                });

                if (isAttributeFilledUp == false) {
                    $generalErrorMessage.html('-Product attribute name and value translation is required').show();
                    $('html,body').animate({scrollTop: $('.sticky-header').offset().top}, 800);
                    return false;
                }

            }

            $.each($('.product-groups'), function (key, value) {
                var self = $(this);
                productGroups.push({
                    id    : self.data('group-id'),
                    value : self.val().trim() == '' ? self.attr('placeholder') : self.val().trim()
                });
            });

            var formData = {
                'productId'         : productId,
                'name'              : $inputProductName.val().trim(),
                'description'       : $inputDescription,
                'shortDescription'  : $inputShortDescription.val().trim(),
                'attrNames'         : JSON.stringify(attrNames),
                'attrValues'        : JSON.stringify(attrValues),
                'productImages'     : JSON.stringify(productImageContainer),
                'primaryImageId'    : primaryImageId,
                'locale'            : locale,
                '_token'            : csrfToken,
                'productGroups'     : JSON.stringify(productGroups)
            };

            $.ajax({
                url        : Routing.generate('translate_product'),
                type       : 'json',
                data       : formData,
                method     : 'POST',
                beforeSend : function () {
                    $this.attr('disabled', true);
                    $this.find(".text").hide();
                    $this.find(".loader").show();
                },
                success    : function (response) {
                    $this.attr('disabled', false);
                    $this.find(".text").show();
                    $this.find(".loader").hide();

                    if (response.isSuccessful) {
                        $messageModal.find('.header-content').html('Product Successfully Translated');
                        $messageModal.find('.detail-content').html('');
                        $messageModal.modal('show');
                        window.location.replace('/product-detail/' + response.slug);
                    }
                    else {
                        var errorMessage = '';
                        $.each(response.message, function (key, val) {
                            errorMessage += '- ' + val + '<br>';
                        });

                        $generalErrorMessage.html(errorMessage).show();
                        $('html,body').animate({scrollTop: $('.sticky-header').offset().top}, 800);
                    }

                }

        });

        });

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
         * Change primary image
         *
         * @param $this
         */
        function changePrimaryImage($this)
        {
            var $dropzone = $this.parents('.dropzone');
            if ($dropzone && $dropzone.data('readonly')) {
                return;
            }

            $this.siblings('.dz-preview').each(function() {
                var $preview = $(this);
                $preview.removeClass('active');
                $preview.find('.primary-tag').remove();
            });
            $this.addClass('active');
            $this.prepend('<span class="primary-tag"><i class="icon icon-check"></i>Primary</span>');
            primaryImageId = $this.attr('product-image-id');
        }

    });

})(jQuery);
