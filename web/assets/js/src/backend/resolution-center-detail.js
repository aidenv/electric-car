(function ($) {
    var $messageModal = $('#modal-message-container');

    $(document).ready(function () {

        $('#btnDisputeMessage').on('click', function () {
            var $this = $(this);
            var disputeId = parseInt($this.attr('data-dispute-id'));
            var disputeMessage = $('#disputeMessage').val().trim();

            if (disputeId === 0) {
                return false;
            }

            if (disputeMessage === '') {
                alert('Invalid Dispute Message');
                return false;
            }

            $.ajax ({
                url: Routing.generate('yilinker_backend_resolution_center_add_message'),
                method: 'post',
                dataType: 'json',
                data: {
                    disputeId: disputeId,
                    disputeMessage: disputeMessage
                },
                beforeSend: function () {
                    $this.attr('disabled', true).html('loading...');
                },
                success: function (response) {
                    $this.html('Submit');

                    if (response) {
                        location.replace('/resolution-center-detail?disputeId=' + disputeId);
                    }

                }
            });

        });

        $('#orderProductTable').on('click', '.btnOrderProductHistory', function () {
            var orderProductId = $(this).attr('data-order-product-id');

            getOrderProductDetailById (orderProductId);
        });

        $('#order-product-check-all').on('click', function () {
            var isChecked = $(this).is(':checked');
            var $disputeDetailCheckbox = $('.dispute-detail-check-box');

            if (isChecked) {
                $disputeDetailCheckbox.prop('checked', true);
            }
            else {
                $disputeDetailCheckbox.prop('checked', false);
            }

        });

        $('#modal-dispute-approve-trigger').on('click', function () {
            var disputeDetailIds = $('.dispute-detail-check-box:checked').map(function() { return $(this).attr('data-dispute-detail-id'); }).get();

            if (disputeDetailIds.length === 0) {
                alert('Kindly check one to proceed');
                return false;
            }

            var $modalDisputeApprove =  $('.modal-dispute-approve');
            var approveAction = $('[name="approve_action"]:checked').val();
            $modalDisputeApprove
                .find('[data-message]')
                .text('Are you sure you want to approve '+(approveAction == '1' ? 'Replacement': 'Refund')+' of selected items?')
            ;
           
            $modalDisputeApprove.modal('show');
        });

        $('#modal-dispute-reject-trigger').on('click', function () {
            var disputeDetailIds = $('.dispute-detail-check-box:checked').map(function() { return $(this).attr('data-dispute-detail-id'); }).get();

            if (disputeDetailIds.length === 0) {
                alert('Kindly check one to proceed');
                return false;
            }

            var confirmBox = confirm('Are you sure you want to continue?');

            if (confirmBox) {
                rejectCase (disputeDetailIds)
            }

        });

        $('#btnApproveDispute').on('click', function () {
            approveCase ($(this));
        });

        $('.datePicker').datetimepicker({
            format: "MM/DD/YYYY"
        });
    });

    /**
     * Get Order Product Detail by OrderProductId
     * @param orderProductId
     */
    function getOrderProductDetailById (orderProductId)
    {

        $.ajax({
            url: Routing.generate('yilinker_backend_transaction_order_product_detail_history'),
            method: 'GET',
            type: 'json',
            data: {orderProductId: orderProductId},
            beforeSend: function () {
            },
            success: function (response) {

                displayOrderProductDetailAndHistory (response);

            }
        });

    }

    /**
     * Display OrderProduct History
     * @param response
     */
    function displayOrderProductDetailAndHistory (response)
    {
        var historyData = response.orderProductHistory;
        var orderProductData = response.orderProduct;
        var $historyContainer = $('#historyInformationModal-historyContainer');
        var $orderProductContainer = $('#historyInformationModal-orderProductDetailsContainer');
        var htmlHistoryTr = '';
        var htmlOrderProduct = '';
        $historyContainer.html(htmlHistoryTr);
        $orderProductContainer.html(htmlOrderProduct);

        $.each(historyData, function (key, value) {
            htmlHistoryTr += '<tr>' +
                '<td>' + value.historyId + '</td>' +
                '<td>' + value.orderProductStatus + '</td>' +
                '<td>' + value.dateAdded + '</td>' +
                '</tr>';
        });

        $.each(orderProductData, function (key, value) {
            htmlOrderProduct += '' +
                '<tr>' +
                '<td>' + value.orderProductId + '</td>' +
                '<td>' + value.fullName + '</td>' +
                '<td>' + value.productName + '</td>' +
                '<td>' + value.quantity + '</td>' +
                '<td>' + numberFormat(value.unitPrice) + '</td>' +
                '<td>' + numberFormat(value.handlingFee) + '</td>' +
                '<td>' + numberFormat(value.totalPrice) + '</td>' +
                '<td>' + value.orderProductStatus + '</td>' +
                '</tr>';
        });

        $orderProductContainer.html(htmlOrderProduct);
        $historyContainer.html(htmlHistoryTr);
        $('.modal-order-two').modal('show').modal();
    }

    function approveCase ($this)
    {
        var disputeDetailIds = $('.dispute-detail-check-box:checked').map(function() { return $(this).attr('data-dispute-detail-id'); }).get();
        var approveAction = $('[name="approve_action"]:checked').val();

        $.ajax({
            url: Routing.generate('yilinker_backend_resolution_center_approve'),
            method: 'POST',
            dataType: 'json',
            data: {
                disputeDetailIds: disputeDetailIds,
                approveAction: approveAction,
                isApproved: true
            },
            beforeSend: function () {
                $this.attr('disabled', true).html('Loading...');
            },
            success: function (response) {
                $this.attr('disabled', false).html('Submit');
                $messageModal.find('.header-content').html('Successful Approved');
                $messageModal.find('.detail-content').html('');
                $messageModal.modal('show');

                location.replace('/resolution-center-detail?disputeId=' + $('#dispute-id').val());

            }
        });

    }

    function rejectCase (disputeDetailIds)
    {

        $.ajax({
            url: Routing.generate('yilinker_backend_resolution_center_reject'),
            method: 'POST',
            dataType: 'json',
            data: {
                disputeDetailIds: disputeDetailIds,
                isApproved: false
            },
            beforeSend: function () {
                $('#modal-dispute-reject-trigger').attr('disabled', true).html('Loading...');
            },
            success: function (response) {
                $messageModal.find('.header-content').html('Successful Rejected');
                $messageModal.find('.detail-content').html('');
                $messageModal.modal('show');
                location.replace('/resolution-center-detail?disputeId=' + $('#dispute-id').val());

            }
        });

    }

})(jQuery);
