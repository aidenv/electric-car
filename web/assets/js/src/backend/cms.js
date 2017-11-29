(function($) {
    var $loader = "<div class='ui active centered small inline inverted loader'>&nbsp;</div>";
    var validImageExtensions = /(\.jpg|\.jpeg|\.png)$/i;
    var $messageModal = $('#modal-message');
    var alphaNumericRestriction = /^[a-zA-z0-9 ]*$/;
    var $SECTION_ITEMS_YOU_MAY_LIKE = $('#section-items-you-may-like').val();

    $(document).ready(function () {
        var $title = $('#txt-title');
        var $sectionId = $('#section-id');
        $title.attr('disabled', false);
        $('#selected-section')
            .dropdown('set selected', $sectionId.val())
            .dropdown({
                onChange: function (value, text) {
                    if (value == $SECTION_ITEMS_YOU_MAY_LIKE) {
                        $title.val('').attr('disabled', true);
                    }
                    else {
                        $title.attr('disabled', false);
                    }
                }
            });

        if ($sectionId.val() == 100) {
            $title.val('').attr('disabled', true);
        }
        else {
            $title.attr('disabled', false);
        }

        $(document).on('click', '.modal-trigger-add-product', function() {
            var $this = $(this);
            var $errorContainer = $('.error-container');
            var $slugs = $('#txt-slugs');
            $('.modal-add-product')
                .modal({
                    closable: false,
                    blurring: true,
                    onShow: function () {
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

        $(document).on('change', '.file-image', function () {
            var $this = $(this);
            var file = $this.prop('files')[0];

            if (!validImageExtensions.test(file['name'].toLowerCase())) {
                $messageModal.find('.header-content').html('Please upload a valid Image file.');
                $messageModal.find('.sub-header-content').html('Allowed file extensions are jpeg and png(max size 10MB).');
                $messageModal.modal('show');
                $this.val('');
            }

        });

        $(document).on('change', '#selectAll', function () {
            $(".productIds").prop('checked', $(this).prop("checked"));
        });

        $(document).on('click', '#removeProducts', function () {
            // TODO: UnCheck this
            $('.productIds:checkbox:checked').each(function() {
                $('.product-'+$(this).val()).remove();
            });

            if ($('.row-data').length === 0) {
                $('.productRowContainer').html('<tr class="noProductsRow"><td colspan="7" align="center">No products found.</td></tr>');
            }

        });

        $(document).on('click', '#btn-save', function () {
            var $this = $(this);
            var productIds = [];
            var node = $('#txt-title').val().trim();
            var sectionId = $('#selected-section').val();
            $('input[name=product-id]').each(function() {productIds.push($(this).val())});
            var $data = new FormData();
            $data.append('title', node);
            $data.append('sectionId', sectionId);
            $data.append('homePageBannerSrc', $('#fileHomePageBanner').prop('files')[0]);
            $data.append('homePageBannerUrl', $('#txtHomePageBannerUrl').val().trim());
            $data.append('featuredProductBannerFileName', $('#fileHomePageBanner').attr('data-file-name').trim());
            $data.append('innerPageBannerSrc', $('#innerPageBanner').prop('files')[0]);
            $data.append('innerPageBannerUrl', $('#txtInnerPageBannerUrl').val().trim());
            $data.append('innerPageBannerFileName', $('#innerPageBanner').attr('data-file-name').trim());
            $data.append('applyImmediate', $('#applyImmediate').is(':checked'));
            $data.append('products', productIds);

            if (sectionId != $SECTION_ITEMS_YOU_MAY_LIKE && node == '') {
                $messageModal.find('.header-content').html('Product title is required.');
                $messageModal.find('.sub-header-content').html('');
                $messageModal.modal('show');
                return false;
            }

            if (productIds.length === 0) {
                $messageModal.find('.header-content').html('Please add at least one product.');
                $messageModal.find('.sub-header-content').html('');
                $messageModal.modal('show');
                return false;
            }

            //remove validation for title
            // if (!alphaNumericRestriction.test(node) && $sectionId.val() != 100) {
            //     $messageModal.find('.header-content').html('Invalid Title.');
            //     $messageModal.find('.sub-header-content').html('title must only consists of alphanumeric characters.');
            //     $messageModal.modal('show');
            //     return false;
            // }

            $.ajax({
                url         : Routing.generate('cms_update_product_detail'),
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
                                closable: false,
                                onApprove : function () {
                                    window.location.replace(Routing.generate('cms_render_product_list'));
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
            })
        });
    });

    function camelize(str) {
        return str.replace(/(?:^\w|[A-Z]|\b\w)/g, function(letter, index) {
            return index == 0 ? letter.toLowerCase() : letter.toUpperCase();
        }).replace(/\s+/g, '');
    }

    //Tabs
    $('.tabular.menu .item').tab();

    $(".modal-send-notification-trigger").click(function(){
        $('.modal-send-notification').modal('show').modal({ blurring: true });
    });

    //datepicker
    $('.datetimepicker').datetimepicker({
        format: "MM/DD/YYYY (hh:mm:ss)"
    });

})(jQuery);
