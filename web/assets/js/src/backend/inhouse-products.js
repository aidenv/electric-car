(function($) {
    'use strict';

    var getManufacturersURL = Routing.generate('core_widget_get_manufacturers');
    var $inhouseManufacturerFilter = $('[data-manufacturer-filter]');
    $inhouseManufacturerFilter.dropdown({
        apiSettings: {
            url: getManufacturersURL+'?q={query}'
        }
    });

    var $inhouseProductRow = $('[data-inhouse-product-row]');
    var $inhouseProductModal = $('[data-inhouse-product-modal]');
    $inhouseProductModal.yiLoader();
    $inhouseProductModal.modal({
        blurring: true
    });

    var getBrandsURL = Routing.generate('core_widget_get_brands');
    var getProductCategoriesURL = Routing.generate('core_widget_get_product_categories');
    var uploadTempURL = Routing.generate('core_widget_upload_temp');
    var uploadImageURL = Routing.generate('core_widget_upload_image');
    var reinitializeJS = function() {
        var $tabs = $inhouseProductModal.find('.tabular.menu .item');
        $tabs.tab();
        $tabs.first().click();
        var initDropdown = function($dropdownEl, url) {
            var optionData = $dropdownEl.data('dropdown-default-option');
            if (optionData) {
                var optionExists = $dropdownEl.find('[value="'+optionData.value+'"]');
                if (!optionExists.length) {
                    $dropdownEl.prepend('<option selected="selected" value="'+optionData.value+'">'+optionData.name+'</option>');
                }
            }

            $dropdownEl.dropdown({
                apiSettings: {
                    url: url
                }
            });

            if (optionData) $dropdownEl.dropdown('set text', optionData.name);
        };

        var $brandDropdown = $inhouseProductModal.find('[name="inhouse_product[brand]"]');
        initDropdown($brandDropdown, getBrandsURL+'?q={query}');

        var $productCategoryDropdown = $inhouseProductModal.find('[name="inhouse_product[productCategory]"]');
        initDropdown($productCategoryDropdown, getProductCategoriesURL+'?q={query}');

        CKEDITOR.replace('inhouse_product_description', {
            filebrowserBrowseUrl: '',
            filebrowserUploadUrl: uploadImageURL,
            imgurClientId: '213',
            filebrowserWindowWidth  : 800,
            filebrowserWindowHeight : 500,
            removePlugins: 'imgur'
        });


        setTimeout(function() {
            $inhouseProductModal.modal('refresh');
        }, 500);

        var $photoUploadDiv = $('div#manufacturer-product-images');
        $photoUploadDiv.dropzone({
            url: uploadTempURL+'?directory='+$photoUploadDiv.data('directory'),
            acceptedFiles: 'image/*',
            addRemoveLinks: true,
            init: function() {
                this.on('success', function(file, data) {
                    var $photoThumbnail = $(file.previewTemplate).find('[data-dz-thumbnail]');
                    $photoThumbnail.attr('alt', data.name);
                    $(file.previewTemplate).data('manufacturer-product-image-id', data.name);
                });
            }
        });

        var $photoRemoves = $inhouseProductModal.find('[data-dz-remove]');
        $photoRemoves.on('click', function() {
            var $photoRemove = $(this);
            $photoRemove.closest('.dz-preview.dz-image-preview').remove();
        });
    };

    var inhouseProductId = 0;
    $inhouseProductRow.on('click', function() {
        var $elem = $(this);
        $inhouseProductModal.modal('show');
        inhouseProductId = $elem.data('inhouse-product-row');
        $.ajax({
            beforeSend: function() {
                $inhouseProductModal.trigger('loader.start');
            },
            url: Routing.generate('admin_inhouse_form')+'?inhouseProductId='+inhouseProductId,
            success: function(html) {
                var $manufacturerProductForm = $inhouseProductModal.find('form[name="inhouse_product"]');
                $manufacturerProductForm.replaceWith(html);
                reinitializeJS();
            },
            complete: function() {
                $inhouseProductModal.trigger('loader.stop');
            }
        });
    });

    var $inhouseFormSave = $('[data-inhouse-form-save]');
    $inhouseFormSave.on('click', function() {
        var $form = $inhouseProductModal.find('form[name="inhouse_product"]');
        var description = CKEDITOR.instances.inhouse_product_description.getData();
        var $manufacturerProductDescription = $inhouseProductModal.find('[name="inhouse_product[description]"]');
        $manufacturerProductDescription.val(description);
        var $photoThumbnails = $inhouseProductModal.find('[data-dz-thumbnail]');
        var images = [];
        $photoThumbnails.each(function() {
            var $photoThumbnail = $(this);
            var alt = $photoThumbnail.attr('alt');
            images.push(alt.replace(/^\d+\//g, ''));
        });
        $inhouseProductModal.find('[name="inhouse_product[photoImages]"]').val(images);

        $.ajax({
            beforeSend: function() {
                $inhouseProductModal.trigger('loader.start');
            },
            url: $form.attr('action')+'?inhouseProductId='+inhouseProductId,
            type: $form.attr('method'),
            data: $form.serialize(),
            success: function(html) {
                var $html = $(html);
                var $errors = $html.find('.form-ui-note ul li');
                if ($errors.length) {
                    var $manufacturerProductForm = $inhouseProductModal.find('form[name="inhouse_product"]');
                    var $html = $(html);
                    $manufacturerProductForm.replaceWith($html);
                    $html.find('[data-form-error]').show();

                    reinitializeJS();
                }
                else {
                    location.reload();
                }
            },
            complete: function() {
                $inhouseProductModal.trigger('loader.stop');
            }
        });
    });

    $inhouseProductModal.on('click', '.dz-preview', function(evt) {
        var $elem = $(this);
        $elem.siblings('.dz-preview').each(function() {
            var $preview = $(this);
            $preview.removeClass('active');
            $preview.find('.primary-tag').remove();
        });
        $elem.addClass('active');
        $elem.prepend('<span class="primary-tag"><i class="icon icon-check"></i>Primary</span>');
        var manufacturerProductImageId = $elem.data('manufacturer-product-image-id');
        $inhouseProductModal.find('[name="inhouse_product[primaryImage]"]').val(manufacturerProductImageId);
    });    

})(jQuery);
