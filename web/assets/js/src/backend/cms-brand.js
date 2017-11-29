(function ($) {
    var $loader = "<div class='ui active centered small inline inverted loader'>&nbsp;</div>";
    var validImageExtensions = /(\.jpg|\.jpeg|\.png)$/i;
    var $messageModal = $('#modal-message');

    $(document).ready(function () {

        $('#product-brands .typeahead')
            .typeahead(
            {
                hint: true,
                highlight: true,
                minLength: 1,
                autoselect: 'first'
            },
            {
                source: function (query, process) {
                    $('#brand-id').attr('data-name', query);
                    $('#brand-id').val('');
                    return $.ajax({
                        url: Routing.generate('cms_product_search_brand'),
                        data: {brandKeyword: query, excludedBrandId:$('#old-brand-id').val()},
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
        )
            .on("typeahead:selected typeahead:autocompleted typeahead:change",
            function (e, brand) {
                $('#brand-id').val(brand.id).attr('data-name', brand.value);
            }
        );

        $(document).on('click', '.modal-trigger-add-product', function() {
            var $this = $(this);
            var $errorContainer = $('.error-container');
            var $slugs = $('#txt-slugs');
            $('.modal-add-product')
                .modal({
                    closable: false,
                    blurring: true,
                    onShow  : function () {
                        $errorContainer.html('').hide();
                        $slugs.val('');
                    },
                    onApprove: function () {
                        var $button = $this.find('.approve');
                        var slugs = $slugs.val().split('\n');

                        $.ajax({
                            url        : Routing.generate('cms_get_product_by_slug'),
                            dataType   : 'json',
                            type       : 'post',
                            data       : {slugs: slugs},
                            beforeSend : function () {
                                $button.html($loader).attr('disabled', true);
                            },
                            success: function (response) {
                                $errorContainer.html('').hide();
                                $button.html('Save').attr('disabled', false);

                                if (response.isSuccessful == true) {
                                    var products = response.data;
                                    var htmlRow = '';

                                    $.each(products, function (key, productDetail) {
                                        var product = productDetail.product;
                                        var defaultUnit = productDetail.productUnit;

                                        if ($('.product-' + product.id).length == 0) {
                                            htmlRow += '' +
                                                '<tr class="product-' + product.id + ' row-data">' +
                                                    '<td>' + product.dateCreated.date + '</td>' +
                                                    '<td>' + product.title + '</td>' +
                                                    '<td>' + defaultUnit.discount + '%</td>' +
                                                    '<td>P'+ numberFormat(defaultUnit.price) +'</td>' +
                                                    '<td>' +
                                                        '<strong>P '+ numberFormat(defaultUnit.discountedPrice) +'</strong>' +
                                                    '</td>' +
                                                    '<td>'+ defaultUnit.quantity +'</td>' +
                                                    '<td>' +
                                                        '<input type="checkbox" class="productIds" name="product-id" value="' + product.id + '">' +
                                                    '</td>' +
                                                '</tr>';
                                        }
                                    });

                                    $('.noProductsRow').remove();
                                    $('.productRowContainer').append(htmlRow);
                                    $('.modal-add-product').modal('hide');
                                }
                                else {
                                    $errorContainer.html(response.message).show();
                                }
                            }
                        });

                        return false;
                    }
                })
                .modal('show');
        });

        $(document).on('change', '#selectAll', function () {
            $(".productIds").prop('checked', $(this).prop("checked"));
        });

        $(document).on('click', '#remove-products', function () {
            // TODO: UnCheck this
            $('.productIds:checkbox:checked').each(function() {
                $('.product-'+$(this).val()).remove();
            });

            if ($('.row-data').length === 0) {
                $('.productRowContainer').html('<tr class="noProductsRow"><td colspan="7" align="center">No products found.</td></tr>');
            }
        });

        $(document).on('change', '.brand-file', function () {
            var $this = $(this);
            var file = $this.prop('files')[0];

            if (!validImageExtensions.test(file['name'].toLowerCase())) {
                $messageModal.find('.header-content').html('Please upload a valid Image file.');
                $messageModal.find('.sub-header-content').html('Allowed file extensions are jpeg and png(max size 10MB).');
                $messageModal.modal('show');
                $this.val('');
            }
            else {
                readURL(this, $('.brand-file-name'));
                $this.attr('data-is-new', 1);
            }
        });

        $(document).on('click', '#update-brand', function () {
            var $this = $(this);
            var productIds = [];
            var brandId = $('#brand-id').val();
            var $brandImage = $('.brand-file');
            $('input[name=product-id]').each(function() {productIds.push($(this).val())});
            var $data = new FormData();
            $data.append('brandId', brandId);
            $data.append('isImageNew', $brandImage.attr('data-is-new'));
            $data.append('description', $('.brand-description').val());
            $data.append('imageFileName', $('.brand-file-name').attr('data-file-name'));
            $data.append('productIds', productIds);
            $data.append('applyImmediate', $('#apply-immediate').is(':checked'));

            if (productIds.length === 0) {
                $messageModal.find('.header-content').html('Please add at least one product.');
                $messageModal.find('.sub-header-content').html('');
                $messageModal.modal('show');
                return false;
            }

            if (brandId == 0) {
                $messageModal.find('.header-content').html('Brand is required.');
                $messageModal.find('.sub-header-content').html('');
                $messageModal.modal('show');
                return false;
            }

            if ($brandImage.attr('data-is-new') == 1 && typeof $brandImage.prop('files')[0] == 'undefined') {
                $messageModal.find('.header-content').html('Brand logo is required.');
                $messageModal.find('.sub-header-content').html('');
                $messageModal.modal('show');
                return false;
            }

            if ($brandImage.attr('data-is-new') == 1) {
                $data.append('image', $brandImage.prop('files')[0]);
            }

            $.ajax({
                url         : Routing.generate('cms_update_brand'),
                type        : 'post',
                cache       : false,
                dataType    : 'json',
                processData : false,
                contentType : false,
                data        : $data,
                beforeSend: function () {
                    $this.html($loader).attr('disabled', true);
                },
                success: function (response) {
                    $this.html('Save').attr('disabled', false);

                    if (response.isSuccessful == true) {
                        $messageModal.find('.header-content').html('Successfully update!');
                        $messageModal.find('.sub-header-content').html('');
                        $messageModal
                            .modal({
                                closable  : false,
                                onApprove : function () {
                                    window.location.replace(Routing.generate('cms_render_brand_list'));
                                }
                            })
                            .modal('show');
                    }
                    else {
                        $messageModal.find('.header-content').html(response.message);
                        $messageModal.find('.sub-header-content').html('');
                        $messageModal.modal('show');
                    }
                }
            });

        });

        function readURL($this, $image) {
            var $input = $($this)[0];
            if ($input.files && $input.files[0]) {
                var reader = new FileReader();

                reader.onload = function (e) {
                    $image.attr('src', e.target.result);
                };

                reader.readAsDataURL($input.files[0]);
            }
        }

    });

})(jQuery);