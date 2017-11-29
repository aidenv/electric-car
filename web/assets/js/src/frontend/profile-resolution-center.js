(function($) {
    var $refundId = 10;
    var $replacementId = 16;
    var $messageModal = $('#modal-message-container');

    $(document).ready(function () {
        $('.coupled').modal({
            allowMultiple: true
        });

        $(".new-case-modal-trigger").on('click', function() {
            $('#disputeTitle, #disputeRemarks').val('');
            $('#disputeOrderProductStatus, #disputeUserOrderId').dropdown('restore defaults');
            $('#disputeOrderProductStatus').parent().find('.default').text('Select dispute type here');
            $('#disputeUserOrderId').parent().find('.default').text('Select transaction type here');
            $('#reason-container').hide();
            $(".new-case-modal").modal("show").find('.error').removeClass('error');
            $('#fileDisputeBtn').attr('disabled', false).html('SUBMIT');

        });

        $(".case-modal-trigger").on('click', function() {
            var disputeId = $(this).parent().parent().attr('data-dispute-id');
            $(".case-modal-" + disputeId).modal({
                onVisible: function(){
                    $(".modal .tabular.menu .item").on("click", function(){
                        $(".case-modal-" + disputeId).modal("refresh");
                    })
                }
            }).modal("show").modal("refresh");
        });

        $('#fileDisputeBtn').on('click', function () {
            fileDispute($(this));
        });

        $('#disputeUserOrderId').on('change', function () {
            var orderId = parseInt($(this).val().trim());

            if (orderId !== 0) {
                getOrderProductByOrder (orderId);
            }
            else {
                $('#orderProductContainer').html('');
                $('#orderProductDiv').hide();
            }

        });

        displayDataInUrl ();

        $(document).on('change', '#filter-by-status-type', function () {
            searchTransaction ();
        });

        $(document).on('keypress', '#txt-search-keyword', function (e) {
            if (e.keyCode == 13) {
                searchTransaction ();
            }
        });

        $(document).on('click', '#btn-search-keyword', function () {
            searchTransaction ();
        });

        $(document).on('change', '#disputeOrderProductStatus', function () {
            var $this = $(this);
            var disputeStatusType = parseInt($this.val());

            if (disputeStatusType !== 0) {
                $('#reason-container').show();
                $('.drop-down-reason').addClass('hidden');

                if (disputeStatusType === $refundId) {
                    $('#drop-down-reason-for-refund').dropdown('restore defaults');
                    $('#drop-down-reason-for-refund').parent().find('.default').text('Select reason here');
                    $('#div-reason-for-refund').removeClass('hidden');
                }
                else if (disputeStatusType === $replacementId) {
                    $('#drop-down-reason-for-replacement').dropdown('restore defaults');
                    $('#drop-down-reason-for-replacement').parent().find('.default').text('Select reason here');
                    $('#div-reason-for-replacement').removeClass('hidden');
                }

            }
            else {
                $('#reason-container').addClass('hidden');
            }

        });

        $('#orderProductDiv').hide();

    });

    function displayDataInUrl ()
    {
        var searchKeyword = getParameterByName('searchKeyword');
        var statusType = getParameterByName('disputeStatusType');

        $('#txt-search-keyword').val(searchKeyword);
        $('#filter-by-status-type').dropdown('set selected', statusType);
    }

    /**
     * Filter Transaction
     */
    function searchTransaction ()
    {
        var searchKeyword = $('#txt-search-keyword').val().trim();
        var statusType = $('#filter-by-status-type').val().trim();
        var param = '';

        if (searchKeyword !== '') {
            param += '?searchKeyword=' + searchKeyword;
        }

        if (statusType !== '') {
            param += (param === '' ? '?' : '&') + 'disputeStatusType=' + statusType;
        }

        window.location = location.protocol + '//' + location.host + location.pathname + param;
    }

    function fileDispute ($this)
    {
        var $disputeTitle = $('#disputeTitle');
        var $disputeUserOrder = $('#disputeUserOrderId');
        var $disputeRemarks = $('#disputeRemarks');
        var $disputeOrderProductStatus = $('#disputeOrderProductStatus');
        var orderProductIds = $('.orderProductCheckBox:checked').map(function() { return $(this).attr('data-order-product-id'); }).get();
        var errorCount = 0;
        var csrfToken = $("meta[name=csrf-token]").attr("content");
        var $reasonId = 0;

        if ($disputeTitle.val().trim() === '') {
            $messageModal.find('.header-content').html('Please enter dispute title.');
            $messageModal.find('.detail-content').html('');
            $messageModal.modal('show');
            return false;
        }

        if (parseInt($disputeOrderProductStatus.val().trim()) === 0) {
            $messageModal.find('.header-content').html('Please select dispute type.');
            $messageModal.find('.detail-content').html('');
            $messageModal.modal('show');
            return false;
        }

        if (parseInt($disputeUserOrder.val().trim()) === 0 || $disputeUserOrder.val().trim() === '') {
            $messageModal.find('.header-content').html('Please select transaction.');
            $messageModal.find('.detail-content').html('');
            $messageModal.modal('show');
            return false;
        }

        if ($disputeRemarks.val().trim() === '') {
            $messageModal.find('.header-content').html('Please enter remarks.');
            $messageModal.find('.detail-content').html('');
            $messageModal.modal('show');
            return false;
        }

        if (parseInt($disputeOrderProductStatus.val().trim()) === $refundId) {
            $reasonId = $('#drop-down-reason-for-refund');

            if (parseInt($reasonId.val().trim()) === 0 || $reasonId.val().trim() === '') {
                $messageModal.find('.header-content').html('Please select reason for refund.');
                $messageModal.find('.detail-content').html('');
                $messageModal.modal('show');
                return false;
            }

        }
        else if (parseInt($disputeOrderProductStatus.val().trim()) === $replacementId) {
            $reasonId = $('#drop-down-reason-for-replacement');

            if (parseInt($reasonId.val().trim()) === 0 || $reasonId.val().trim() === '') {
                $messageModal.find('.header-content').html('Please select reason for replacement.');
                $messageModal.find('.detail-content').html('');
                $messageModal.modal('show');
                return false;
            }

        }

        if (orderProductIds.length === 0) {
            $messageModal.find('.header-content').html('Kindly select at least 1 (One) product to proceed');
            $messageModal.find('.detail-content').html('');
            $messageModal.modal('show');
            return false;
        }

        if (orderProductIds.length === 0) {
            alert('Kindly select at least 1 (One) product to proceed');
            return false;
        }

        $.ajax({
            url: Routing.generate('profile_resolution_center_add'),
            method: 'post',
            dataType: 'json',
            data: {
                title: $disputeTitle.val().trim(),
                remarks: $disputeRemarks.val().trim(),
                orderProductStatus: $disputeOrderProductStatus.val().trim(),
                orderProductIds: orderProductIds,
                reasonId: $reasonId.val(),
                transactionNumber : $disputeUserOrder.val(),
                csrfToken: csrfToken
            },
            beforeSend: function () {
                $this.html("<div class='ui active centered small inline inverted loader'>&nbsp;</div>").addClass("disabled");
            },
            success: function (response) {
                $this.html("Submit").removeClass('disabled');

                if (response.isSuccessful) {
                    $(".success-new-case-modal").modal("show");
                    location.reload();
                }
                else {
                    $messageModal.find('.header-content').html(response.responseMessage);
                    $messageModal.find('.detail-content').html('');
                    $messageModal.modal('show');
                }

            }
        });

    }

    /**
     * Get Order Product by order
     *
     * @param orderId
     */
    function getOrderProductByOrder (orderId)
    {

        $.ajax({
            url: Routing.generate('profile_get_order_products'),
            method: 'get',
            dataType: 'json',
            data: {
                orderId: orderId
            },
            beforeSend: function () {
            },
            success: function (response) {

                if (response.orderProducts.length > 0) {
                    displayOrderProducts (response.orderProducts)
                }

            }
        });

    }

    /**
     * Display Order Products
     *
     * @param orderProducts
     */
    function displayOrderProducts (orderProducts)
    {
        var $orderProductContainer = $('#orderProductContainer');
        var orderProductsHtml = '';

        $.each(orderProducts, function (key, orderProduct) {
            orderProductsHtml += '' +
                '<div class="item-picker-container">' +
                '<div class="col-md-12">' +
                '<div class="item">' +
                '<div class="ui checkbox">' +
                '<input type="checkbox" class="orderProductCheckBox" data-order-product-id="' + orderProduct.orderProductId + '" />' +
                '<label for="">' + orderProduct.productName + '</label>' +
                '</div>' +
                '</div>' +
                '</div>' +
                '</div>';
        });

        if (orderProductsHtml) {
            $('#orderProductDiv').show();
        }

        $orderProductContainer.html(orderProductsHtml);
        $('.new-case-modal').modal('refresh');
    }

    $('.modal .tabular.menu .item').tab();

    $('.ui.accordion').accordion();
})(jQuery);
