(function($) {

    var orderProductIds = '';

    $(document).ready(function () {
        $(".modal-remarks-approved-trigger").click(function(){
            $('.modal-remarks-approved').modal('show').modal({ blurring: true });
        });

        $(".modal-remarks-reject-trigger").click(function(){
            $('.modal-remarks-reject').modal('show').modal({ blurring: true });
        });

        $('.datetimepicker').datetimepicker({
            format: "MM/DD/YYYY"
        });

        var $orderDetailModal = $("#orderDetailsModal-orderProductDetailContainer");

        $('#searchTransaction').on('click', function () {
            var $this = $(this);
            searchTransaction ($this);
        });

        $('#exportTransaction').on('click', function () {
            var $this = $(this);
            exportTransaction ($this);
        });
        

        $('#searchKeyword').on('keypress', function (e) {
            var $this = $(this);

            if (e.keyCode == 13) {
                searchTransaction ($this);
            }

        });

        $(".modal-order-one-trigger").on('click', function () {
            var orderId = $(this).attr('data-id');

            getOrderProductDetail (orderId);
        });

        $orderDetailModal.on('click', '.showOrderDetails', function () {
            var orderProductId = $(this).attr('data-id');
            orderProductIds = '';

            getOrderProductDetailById (orderProductId);
        });

        $('#cancelTransactionsBtn').on('click', function () {
            orderProductIds = $('.cancelOrderProduct:checked').map(function() { return $(this).attr('data-id'); }).get();

            if (orderProductIds.length === 0) {
                alert('Kindly check one to proceed cancellation');
                return false;
            }

            $('#reasonId').dropdown('set selected', '0');
            $('#remarks').val('');
            $('#cancelTransactionModal').modal('show');

        });

        $('#proceedToCancel').on('click', function () {
            var reasonId = parseInt($('#reasonId').val());
            var remarks = $('#remarks').val().trim();

            if (reasonId === 0) {
                alert('Please select reason for cancellation.');
                return false;
            }

            if (remarks === '') {
                alert('Please add remarks for cancellation.');
                return false;
            }

            cancelTransaction (orderProductIds, reasonId, $(this), remarks);
        });

        $('.closeModalAction').on('click', function () {
            $('.modal-order-one').modal('hide');
        });

        $('#checkAllBoxes').on('click', function () {
            var $this = $(this);
            var isChecked = $this.is(':checked') ? true : false;

            if (isChecked) {
                $('.cancelOrderProduct').prop('checked', true);
            }
            else {
                $('.cancelOrderProduct').prop('checked', false);
            }

        });

        $('#approveTransactionBtn').on('click', function () {
            orderProductIds = $('.cancelOrderProduct:checked').map(function() { return $(this).attr('data-id'); }).get();

            if (orderProductIds.length === 0) {
                alert('Kindly check one to proceed');
                return false;
            }

            $('#approveCancelTransaction-remarks').val('');
            $('#approveCancelTransactionModal').modal('show');
        });

        $('#denyTransactionBtn').on('click', function () {
            orderProductIds = $('.cancelOrderProduct:checked').map(function() { return $(this).attr('data-id'); }).get();

            if (orderProductIds.length === 0) {
                alert('Kindly check one to proceed');
                return false;
            }

            $('#denyCancelTransaction-remarks').val('');
            $('#denyCancelTransactionModal').modal('show');
        });

        $('#approveCancelTransaction-proceedToCancel').on('click', function () {
            var remarks = $('#approveCancelTransaction-remarks').val().trim();

            approveOrDenyCancelTransaction (remarks, orderProductIds, 1, $(this), $('#approveCancelTransactionModal'))
        });

        $('#denyCancelTransactionModal-proceedToCancel').on('click', function () {
            var remarks = $('#denyCancelTransaction-remarks').val().trim();

            approveOrDenyCancelTransaction (remarks, orderProductIds , 0, $(this), $('#denyCancelTransactionModal'))
        });

        displayDataInUrl();
    });

})(jQuery);

function displayDataInUrl ()
{
    var searchKeyword = getParameterByName('searchKeyword');
    var orderStatus = getParameterByName('orderStatus');
    var paymentMethod = getParameterByName('paymentMethod');
    var hasAction = getParameterByName('hasAction');
    var dateFrom = getParameterByName('dateFrom');
    var dateTo = getParameterByName('dateTo');

    $('#searchKeyword').val(searchKeyword);
    $('#orderStatus').dropdown('set selected', orderStatus);
    $('#paymentMethod').dropdown('set selected', paymentMethod);
    $('#hasAction').dropdown('set selected', hasAction);
    $('#dateFrom').val(dateFrom);
    $('#dateTo').val(dateTo);

    if (dateFrom === '' && dateTo === '') {
        $('#dateFrom').val(getDate (-1));
        $('#dateTo').val(getDate ());
    }

}

/**
 * Filter Transaction
 * @param $this
 */
function searchTransaction ($this)
{
    var searchKeyword = $('#searchKeyword').val().trim();
    var orderStatus = $('#orderStatus').val().trim();
    var paymentMethod = $('#paymentMethod').val().trim();
    var hasAction = $('#hasAction').val().trim();
    var dateFrom = $('#dateFrom').val().trim();
    var dateTo = $('#dateTo').val().trim();
    var param = '';

    if( (new Date(dateFrom).getTime() > new Date(dateTo).getTime()) )
    {
        alert('Invalid Date Range');
        return false;
    }

    if (searchKeyword !== '') {
        param += '?searchKeyword=' + searchKeyword;
    }

    if (orderStatus !== '') {
        param += (param === '' ? '?' : '&') + 'orderStatus=' + orderStatus;
    }

    if (paymentMethod !== '') {
        param += (param === '' ? '?' : '&') + 'paymentMethod=' + paymentMethod;
    }

    if (hasAction !== '') {
        param += (param === '' ? '?' : '&') + 'hasAction=' + hasAction;
    }

    if (dateFrom !== '') {
        param += (param === '' ? '?' : '&') + 'dateFrom=' + dateFrom;
    }

    if (dateTo !== '') {
        param += (param === '' ? '?' : '&') + 'dateTo=' + dateTo;
    }

    window.location = location.protocol + '//' + location.host + location.pathname + param;
}

function exportTransaction ($this)
{
    var dateFrom = $('#dateFrom').val().trim();
    var dateTo = $('#dateTo').val().trim();
    var param = '';

    if( (new Date(dateFrom).getTime() > new Date(dateTo).getTime()) )
    {
        alert('Invalid Date Range');
        return false;
    }

    if (dateFrom !== '') {
        param += (param === '' ? '?' : '&') + 'dateFrom=' + dateFrom;
    }

    if (dateTo !== '') {
        param += (param === '' ? '?' : '&') + 'dateTo=' + dateTo;
    }

    window.location = location.protocol + '//' + location.host + '/transactions/export' + param;
}

/**
 * Get Order Product Details
 * @param orderId
 */
function getOrderProductDetail (orderId)
{
    $.ajax({
        url: Routing.generate('yilinker_backend_transaction_order_details'),
        type: 'json',
        method: 'GET',
        data: {
            orderId: orderId
        },
        beforeSend: function () {
        },
        success: function (reponse) {

            if (reponse.orderProducts.length > 0) {
                displayOrderProductDetails (reponse, orderId)
            }

            $('.modal-order-one').modal('show');
        }
    });
}

/**
 * Display Order Product Details in modal
 * @param response
 */
function displayOrderProductDetails (response, orderId)
{
    var userOrder = response.transactions[0];
    var orderProducts = response.orderProducts;
    var canCancel = response.canCancel;
    var canApproveOrDeny = response.canApproveOrDeny;
    var $orderProductContainer = $('#orderDetailsModal-orderProductDetailContainer');
    var html = '';
    $orderProductContainer.html(html);
    $('#orderDetailsModal-invoice').html(userOrder.invoiceNumber);
    $('#orderDetailsModal-buyer').html(userOrder.buyerName);
    $('#orderDetailsModal-buyer-shipping-address').html(userOrder.buyerFullAddress);
    $('#orderDetailsModal-buyer-contact-number').html(userOrder.contactNumber);
    $('#orderDetailsModal-status').html(userOrder.orderStatus);
    $('#orderDetailsModal-dateCreated').html(userOrder.dateCreated);
    $('.orderDetailsModal-totalAmount').html('PHP ' + numberFormat(userOrder.orignalPrice));

    $('#orderDetailsModal-yilinker-charge').html('PHP ' + numberFormat(userOrder.yilinkerCharge));
    $('#orderDetailsModal-freight-charge').html('PHP ' + numberFormat(userOrder.handlingFee));
    $('#orderDetailsModal-additional-charge').html('PHP ' + numberFormat(userOrder.additionalCost));

    $('#orderDetailsModal-sub-total').html('PHP ' + numberFormat(userOrder.net));

    var $voucherDetail = "";
    $.each(userOrder.vouchers, function (key, voucher) {
        $voucherDetail += "\
        <div class='col-md-7'>\
            <strong>Voucher</strong><br>\
            <em>Name</em>: "+voucher.voucher.name+"<br>\
            <em>Code</em>: "+voucher.code+"\
        </div>\
        <div class='col-md-5 txtright'> - PHP "+numberFormat(voucher.amount)+"</div>\
        ";
    });

    $(".voucher-details").show();
    if (userOrder.vouchers.length <= 0) {
        $(".voucher-details").hide();
    }

    $("#voucher-container").html($voucherDetail);

    $('#orderDetailsModal-net').html('PHP ' + numberFormat(userOrder.orignalPrice - userOrder.voucherDeduction));
    $('#orderDetailsModal-shippingAddress').html(userOrder.address);
    $('#orderDetailsModal-contactNumber').html(userOrder.contactNumber);
    $('.returnToPreviousModal').attr('data-id', orderId);
    $('#checkAllBoxes').prop('checked', false);

    if(userOrder.consigneeName != null && userOrder.consigneeName != ""){
        $('#orderDetailsModal-buyer-consignee-name').html(userOrder.consigneeName);
    }
    else{
        $('#orderDetailsModal-buyer-consignee-name').html(userOrder.buyerName);
    }

    if(userOrder.consigneeContactNumber != null && userOrder.consigneeContactNumber != ""){
        $('#orderDetailsModal-buyer-consignee-contact-number').html(userOrder.consigneeContactNumber);
    }
    else{
        $('#orderDetailsModal-buyer-consignee-contact-number').html(userOrder.consigneeContactNumber);
    }

    $('#cancelTransactionsBtn, .approveDeny').hide();

    $.each(orderProducts, function (key, orderProduct) {
        var checkBox = '';
        var attributes = 'None';

        if ((canCancel && orderProduct.canCancel) || (canApproveOrDeny && orderProduct.canApproveOrDeny)) {
            checkBox = '<input type="checkbox" class="cancelOrderProduct" id="checkboxId_' + orderProduct.orderProductId + '"' +
                ' data-id="' + orderProduct.orderProductId + '" />';
        }

        if (orderProduct.attributes !== null) {
            var attributeList = '';

            $.each(orderProduct.attributes, function (attributeName, attributeValue) {
                attributeList += '<li><strong>' + attributeName + '</strong>: ' + attributeValue + '</li>';
            });

            attributes = '<ul class="attribute-list">' + attributeList + '</ul>';
        }

        html += '<tr>' +
                    '<td>' + checkBox + '</td>' +
                    '<td>' + orderProduct.orderProductId + '</td>' +
                    '<td><a href="'+ Routing.generate('yilinker_backend_product_listings', {productId:orderProduct.productId}) +'">' + orderProduct.productName + '</a></td>' +
                    '<td>' + orderProduct.fullName + '</td>' +
                    '<td>' + orderProduct.quantity + '</td>' +
                    '<td>PHP ' + numberFormat(orderProduct.unitPrice) + '</td>' +
                    '<td>PHP ' + numberFormat(orderProduct.handlingFee) + '</td>' +
                    '<td>PHP ' + numberFormat(orderProduct.totalPrice) + '</td>' +
                    '<td>' + orderProduct.orderProductStatus + '</td>' +
                    '<td>' + attributes + '</td>' +
                    '<td>' +
                        '<button class="button tiny blue showOrderDetails" data-id="' + orderProduct.orderProductId + '">' +
                            'view' +
                        '</button>' +
                    '</td>' +
                '</tr>';
    });

    if (canApproveOrDeny) {
        $('.approveDeny').show();
    }
    else if (canCancel) {
        $('#cancelTransactionsBtn').show();
    }

    $orderProductContainer.html(html);
    displayRemarks (response.listOfRemarks);
}

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
    var orderProductData = response.orderProduct[0];
    var shipmentInformation = response.shipmentInformation;
    var $historyContainer = $('#historyInformationModal-historyContainer');
    var $orderProductContainer = $('#historyInformationModal-orderProductDetailsContainer');
    var $shipmentHistoryContainer = $('#historyInformationModal-shipment-information');
    var htmlHistoryTr = '';
    var htmlOrderProduct = '';
    var htmlShippingInformation = '';
    $historyContainer.html(htmlHistoryTr);
    $orderProductContainer.html(htmlOrderProduct);

    $.each(historyData, function (key, value) {
        htmlHistoryTr += '<tr>' +
                             '<td>' + value.historyId + '</td>' +
                             '<td>' + value.orderProductStatus + '</td>' +
                             '<td>' + value.dateAdded + '</td>' +
                         '</tr>';
    });

    htmlOrderProduct += '' +
        '<tr>' +
            '<td>' + orderProductData.orderProductId + '</td>' +
            '<td>' + orderProductData.fullName + '</td>' +
            '<td>' + orderProductData.productName + '</td>' +
            '<td>' + orderProductData.quantity + '</td>' +
            '<td>' + numberFormat(orderProductData.unitPrice) + '</td>' +
            '<td>' + numberFormat(orderProductData.handlingFee) + '</td>' +
            '<td>' + numberFormat(orderProductData.totalPrice) + '</td>' +
            '<td>' + orderProductData.orderProductStatus + '</td>' +
        '</tr>';

    $('#historyInformationModal-seller-shipping-address').html(orderProductData.sellerFullAddress);
    $('#historyInformationModal-seller-contact-number').html(orderProductData.contactNumber);
    $('#table-shipping-history').hide();
    $('#no-shipping-history').hide().html('');

    if (shipmentInformation.length > 0) {

        $.each(shipmentInformation, function (key, value) {
            htmlShippingInformation += '' +
                '<tr>' +
                    '<td>' + value.waybillNumber + '</td>' +
                    '<td>' + value.warehouse + '</td>' +
                    '<td>' + value.quantity + '</td>' +
                    '<td>' + value.dateAdded + '</td>' +
                '</tr>';
        });

        $('#table-shipping-history').show();
        $shipmentHistoryContainer.html(htmlShippingInformation);
    }
    else {
        $('#no-shipping-history').show().html('No Shipping history');
    }

    $orderProductContainer.html(htmlOrderProduct);
    $historyContainer.html(htmlHistoryTr);
    $('.modal-order-two').modal('show').modal();
}

/**
 * Cancel Transaction
 *
 * @param orderProductIds
 * @param reasonId
 * @param $this
 * @param remarks
 */
function cancelTransaction (orderProductIds, reasonId, $this, remarks)
{
    $.ajax({
        url: Routing.generate('yilinker_backend_transaction_cancel'),
        method: 'POST',
        dataType: 'json',
        data: {
            orderProductIds: orderProductIds,
            reasonId: reasonId,
            remarks: remarks
        },
        beforeSend: function () {
            $this.attr('disabled', true);
        },
        success: function (response) {
            $this.attr('disabled', false);

            if (response === true) {
                alert('Successfully Cancelled Transaction');
                $('.cancelOrderProduct:checked').map(function() { return $(this).remove(); });
                $('#cancelTransactionModal').modal('hide');
                $('#remarks').val('');
                location.reload();
            }

        }
    })
}

/**
 * Approve Or Deny Cancelled Transaction
 *
 * @param remarks
 * @param orderProductIds
 * @param isApprove
 * @param $this
 * @param $modal
 */
function approveOrDenyCancelTransaction (remarks, orderProductIds, isApprove, $this, $modal)
{

    $.ajax({
        url: Routing.generate('yilinker_backend_approve_deny_cancelled_transaction'),
        method: 'POST',
        dataType: 'json',
        data: {
            orderProductIds: orderProductIds,
            isApprove: isApprove,
            remarks: remarks
        },
        beforeSend: function () {
            $this.attr('disabled', true);
        },
        success: function (response) {
            $this.attr('disabled', false);

            if (response === true) {
                alert('Successfully Changed Status');
                $('.cancelOrderProduct:checked').map(function() { return $(this).remove(); });
                $modal.modal('hide');
            }

        }
    })
}

function displayRemarks (listOfRemarks)
{
    $('#remarksContainer').html('');

    if (listOfRemarks) {

        $.each(listOfRemarks, function (key, value) {
            var remarksCsrHtml = '';
            var remarksCustomerHtml = '';
            var resolvedClass = '';
            var resolvedTag = '';
            var isCancelledByCsr = false;
            var adminRemarks = value['details']['admin'];
            var sellerRemarks = value['details']['seller'];
            var products = $.map(value["products"], function(l) { return l; });

            if (typeof sellerRemarks === 'undefined' && parseInt(value.isOpen) === 0) {
                isCancelledByCsr = true;
            }

            if (parseInt(value.isOpen) === 0) {
                resolvedClass = 'resolved';
                resolvedTag = '<p class="tag-resolved">resolved</p>';
            }

            if (isCancelledByCsr) {
                remarksCustomerHtml = '' +
                    '<div class="customer">' +
                        resolvedTag +
                        '<p class="person">' +
                            '<span class="user">' +
                            '<strong>' + adminRemarks.user + '</strong>' +
                            'Canceled By CSR' +
                            '</span>' +
                        '</p>' +
                        '<p class="words">' +
                            '<strong>' + value["reason"] + '</strong>' + adminRemarks.remarks +
                        '</p>' +
                        '<p class="attachment">' +
                            '<span>requested items for cancellations</span>' +
                            '<span class="items">Order ID ' + value["orderId"] + ': ' + products.join(', ') + '</span>' +
                        '</p>' +
                        '<p class="time-stamp">Posted on ' + adminRemarks.dateAdded + '</p>' +
                    '</div>';
            }
            else {

                if (typeof sellerRemarks !== 'undefined') {

                    remarksCustomerHtml = '' +
                        '<div class="customer '+ resolvedClass + '">' +
                            resolvedTag +
                            '<p class="person">' +
                                '<span class="user">' +
                                '<strong>' + sellerRemarks.user + '</strong>' +
                                'Complainant' +
                                '</span>' +
                            '</p>' +
                            '<p class="words">' +
                                '<strong>' + value["reason"] + '</strong>' + sellerRemarks.remarks +
                            '</p>' +
                            '<p class="attachment">' +
                                '<span>requested items for cancellations</span>' +
                                '<span class="items">Order ID ' + value["orderId"] + ': ' + products.join(', ') + '</span>' +
                            '</p>' +
                            '<p class="time-stamp">Posted on ' + sellerRemarks.dateAdded + '</p>' +
                        '</div>';

                }

                if (typeof adminRemarks !== 'undefined') {

                    remarksCsrHtml = '' +
                        '<div class="csr">' +
                            '<p class="person">' +
                                '<span class="user">' +
                                '<strong>' + adminRemarks.user + '</strong>' +
                                'customer support representative' +
                                '</span>' +
                            '</p>' +
                            '<p class="words">' + adminRemarks.remarks +
                            '</p>' +
                        '</div>';

                }
            }

            var remarksBodyHtml = '<div class="form remarks '+ resolvedClass + '">' + remarksCustomerHtml + remarksCsrHtml + '</div>';

            $('#remarksContainer').prepend(remarksBodyHtml);
        });

    }
}