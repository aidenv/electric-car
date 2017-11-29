(function ($) {
    var $loader = "<div class='ui active centered small inline inverted loader'>&nbsp;</div>";
    var $messageModal = $('#modal-message');

    $(document).ready(function () {
        var maxProducts = $('#max-allowable-store-products').val();

        $('#stores .typeahead')
            .typeahead(
            {
                hint: true,
                highlight: true,
                minLength: 1,
                autoselect: 'first'
            },
            {
                source: function (query, process) {
                    $('#store-id').attr('data-name', query);
                    $('#store-id').val('');
                    return $.ajax({
                        url: Routing.generate('cms_search_store'),
                        data: {storeKeyword: query, excludedStoreId: $('#old-store-id').val(), storeListNodeId: $('#store-list-node-id').val()},
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
                $('#store-id').val(brand.id).attr('data-name', brand.value);
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

        $(document).on('click', '#update-store', function () {
            var $this = $(this);
            var productIds = [];
            var storeId = parseInt($('#store-id').val());
            $('input[name=product-id]').each(function() {productIds.push($(this).val())});
            var $data = new FormData();
            $data.append('storeId', storeId);
            $data.append('storeListNodeId', $('#store-list-node-id').val());
            $data.append('isNew', $('#is-new').val());
            $data.append('productIds', productIds);
            $data.append('applyImmediate', $('#apply-immediate').is(':checked'));
            $data.append('oldStoreId', $('#old-store-id').val());

            if (productIds.length === 0) {
                $messageModal.find('.header-content').html('Please add at least one product.');
                $messageModal.find('.sub-header-content').html('');
                $messageModal.modal('show');
                return false;
            }

            if (productIds.length > maxProducts) {
                $messageModal.find('.header-content').html('You have reached the max allowable products per store.');
                $messageModal.find('.sub-header-content').html('Max allowable products per store is ' + maxProducts);
                $messageModal.modal('show');
                return false;
            }

            if (isNaN(storeId) == true || storeId == 0) {
                $messageModal.find('.header-content').html('Store is required.');
                $messageModal.find('.sub-header-content').html('');
                $messageModal.modal('show');
                return false;
            }

            $.ajax({
                url         : Routing.generate('cms_update_seller'),
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
                                    window.location.replace(Routing.generate('cms_render_store_list'));
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

    });

})(jQuery);