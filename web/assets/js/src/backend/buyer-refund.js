(function ($) {

    var orderProductIds = '';
    var selectedBuyerId = 0;
    var formdata = false;

    $(document).ready(function () {

        if(window.FormData){
            formdata = new FormData();
        }

        $('.datePicker').datetimepicker({
            format: "MM/DD/YYYY"
        });

        $('#searchKeyword').on('keypress', function (e) {

            if (e.keyCode === 13) {
                searchSeller ();
            }

        });

        $('#searchBtn').on('click', function () {
            searchSeller ();
        });

        $(document).on('click', '.buyerRow', function () {
            var $this = $(this);
            var buyerId = $this.attr('data-id');
            var orderProductIds = $.parseJSON($this.attr('data-order-product-ids'));
            selectedBuyerId = buyerId;

            getUserOrderProductByBuyerId(buyerId, orderProductIds, $this.data('dispute-id'));
        });

        $("#userOrderModal-userOrderContainer").on('click', '.showOrderProductHistory', function() {
            orderProductIds = '';
            var orderProductId = $(this).attr('data-order-product-id');
            getOrderProductDetailById (orderProductId);
        });

        displayDataInUrl ();
    });

    /**
     * Display Data After search
     */
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

    /**
     * Search filter for seller
     * @returns {boolean}
     */
    function searchSeller ()
    {
        var searchKeyword = $('#searchKeyword').val().trim();
        var dateFrom = $('#dateFrom').val().trim();
        var dateTo = $('#dateTo').val().trim();
        var params = '';

        if( (new Date(dateFrom).getTime() > new Date(dateTo).getTime()) ) {
            alert('Invalid Date Range');
            return false;
        }

        if (searchKeyword !== '') {
            params += '?searchKeyword=' + searchKeyword;
        }

        if (dateFrom !== '') {
            params += (params === '' ? '?' : '&') + 'dateFrom=' + dateFrom;
        }

        if (dateTo !== '') {
            params += (params === '' ? '?' : '&') + 'dateTo=' + dateTo;
        }

        window.location = location.protocol + '//' + location.host + location.pathname + params;
    }

    /**
     * Get User Order by seller id
     * @param sellerId
     */
    function getUserOrderProductByBuyerId(buyerId, orderProductIds, disputeId)
    {
        $('.modal-buyer-refund-one').data('disputeId', disputeId);

        $.ajax({
            url: Routing.generate('yilinker_backend_buyer_refund_order_product'),
            method: 'GET',
            dataType: 'json',
            data: {
                buyerId: buyerId,
                disputeId: disputeId,
                orderProductIds: orderProductIds,
                orderProductStatusIds:[
                    $('#status-cancel-request-by-seller').val(),
                    $('#status-cancellation-before-approve').val(),
                    $('#status-refunded-product-inspection-approved').val()
                ]
            },
            beforeSend: function () {
            },
            success: function (response) {
                displayProductOrder(response);
            }
        });

    }

    /**
     * Display Product Order in Order details modal
     * @param orderProducts
     */
    function displayProductOrder(response)
    {
        var $orderProductContainer = $('#userOrderModal-userOrderContainer'),
            html = '',
            remarksHtml = '',
            hasPayout = 0;
            orderProducts = response.orderProducts,
            disputeMessages = response.disputeRemarks;

        $orderProductContainer.html(html);

        $('#proceedRefundBtn').show();

        $.each(disputeMessages, function (key, disputeMessage) {
            var userType = disputeMessage.isAdmin == true ? 'Admin' : 'Complainant',
                classUserType = disputeMessage.isAdmin == true ? 'csr' : 'customer';

            remarksHtml += '\
                <div class="'+classUserType+'">\
                    <p class="person">\
                        <img src="'+disputeMessage.image+'" border="0">\
                        <span class="user">\
                            <strong>'+disputeMessage.authorName+'</strong>\
                            '+userType+'\
                        </span>\
                    </p>\
                    <p class="words">'+disputeMessage.message+'</p>\
                    <p class="time-stamp">Posted on '+disputeMessage.dateAdded+'</p>\
                </div>\
            ';
        });

        $('.remarks-container').html(remarksHtml);

        $.each(orderProducts, function (key, orderProduct) {
            var checkBox = '';

            if (
                parseInt(orderProduct.orderProductStatusId) === 7 ||
                parseInt(orderProduct.orderProductStatusId) === 8 ||
                parseInt(orderProduct.orderProductStatusId) === 13
            ) {
                checkBox = '<input type="checkbox" class="refundOrderProduct" id="checkboxId_' + orderProduct.orderProductId + '"' +
                    ' data-id="' + orderProduct.orderProductId + '" />';
                hasPayout++;
            }

            html += '' +
                '<tr>' +
                '<td>' + checkBox + '</td>' +
                '<td>' + orderProduct.userId + '</td>' +
                '<td>' + orderProduct.fullName + '</td>' +
                '<td>' + orderProduct.productId + '</td>' +
                '<td>' + orderProduct.productName + '</td>' +
                '<td>' + orderProduct.quantity + '</td>' +
                '<td>' + numberFormat(orderProduct.unitPrice) + '</td>' +
                '<td>' + numberFormat(orderProduct.totalPrice) + '</td>' +
                '<td>' + orderProduct.orderProductStatus + '</td>' +
                '<td>' +
                '<button class="button tiny blue showOrderProductHistory" data-order-product-id="' + orderProduct.orderProductId + '">' +
                'view' +
                '</button>' +
                '</td>' +
                '</tr>';
        });

        if (hasPayout === 0) {
            $('#proceedRefundBtn').hide();
        }

        $orderProductContainer.html(html);
        $('.modal-buyer-refund-one').modal('show').modal();
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
            dataType: 'json',
            data: {orderProductId: orderProductId},
            beforeSend: function () {
            },
            success: function (response) {
                displayOrderProductDetailAndHistory (response, orderProductId);
            }
        });

    }

    /**
     * Display OrderProduct History
     * @param response
     */
    function displayOrderProductDetailAndHistory (response, orderProductId)
    {
        var historyData = response.orderProductHistory;
        var orderProductData = response.orderProduct;
        var shipmentInformationData = response.shipmentInformation;
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
        $('#historyInformationModal-pickUpDate').html(shipmentInformationData.pickUpDate);
        $('#historyInformationModal-trackingNumber').html(shipmentInformationData.trackingNumber);
        $('#historyInformationModal-wayBillNumber').html(shipmentInformationData.wayBillNumber);
        $('#historyInformationModal-status').html(shipmentInformationData.status);
        $('#historyInformationModal-lastUpdatedDate').html(shipmentInformationData.lastUpdatedDate);

        $orderProductContainer.html(htmlOrderProduct);
        $historyContainer.html(htmlHistoryTr);
        $('.modal-buyer-refund-two').find('.buyerRow').attr('data-id', selectedBuyerId);
        $('.modal-buyer-refund-two').modal('show').modal();

    }

    /**
     * Proceed to Refund
     * @param orderProductIds
     */
    function proceedRefund(orderProductIds, $this)
    {
        var csrftoken = $("meta[name=csrf-token]").attr("content");
        var $modalBuyerRefundModal = $('.modal-buyer-refund-one');
        var disputeId = $modalBuyerRefundModal.length ? $modalBuyerRefundModal.data('disputeId'): null;

        if(formdata){
            $.each(orderProductIds, function(index, value){
                formdata.append('orderProductIds[]', value); 
            });
            formdata.append('_token', csrftoken);
            formdata.append('remark', $('[name="refund_remark"]').val());
            formdata.append('disputeId', disputeId);
        }
        else{
            formdata = {
                orderProductIds : orderProductIds,
                _token : csrftoken,
                remark : $('[name="refund_remark"]').val(),
                disputeId: disputeId
            };
        }

        $.ajax({
            url: Routing.generate('yilinker_backend_buyer_refund_proceed_refund'),
            method: 'POST',
            dataType: 'json',
            data: formdata,
            processData: false,
            contentType: false,
            beforeSend: function () {
                $this.attr('disabled', true);
            },
            success: function (response) {
                $this.attr('disabled', false);
                if (response.isSuccessful === true) {
                    alert('Successfully Changed Product Status');
                    window.location = Routing.generate('yilinker_backend_buyer_refund_overview') + "?payout=" + response.payoutId;
                }
            }
        });
    }

    function showUploadedItem (source) {
        var $list = $(".deposit-slip-list"),
        li   = document.createElement("li"),
        img  = document.createElement("img");
        img.src = source;
        li.appendChild(img);
        $list.append(li);
    }


    $('.deposit-slip').on('change', function(){
        var i, len = this.files.length, img, reader, file;
        for(i = 0; i < len; i++){
            file = this.files[i];
        }

        if ( window.FileReader ) {
            // browser supports ajax file upload
            reader = new FileReader();
            reader.onloadend = function (e) {
                showUploadedItem(e.target.result);
            };
            reader.readAsDataURL(file);
        }
        if (formdata !== false) {
            formdata.append("depositSlips[]", file);
        }
    });

    $('.modal .tabular.menu .item').tab();

    $(".proceed-confirm").on("click", function(){
        orderProductIds =  $('.refundOrderProduct:checked').map(function() { return $(this).attr('data-id'); }).get();
        if (orderProductIds.length === 0) {
            alert('Kindly Select at least One to proceed payment.');
            return false;
        }

        $(".confirm-refund-modal").modal("show")
                                  .modal({
                                      onApprove: function () {
                                        proceedRefund(orderProductIds, $(this).find('.actions').find('button.approve'));

                                        return false;
                                      }
                                  });
    });

    $(".back-modal-buyer-refund-one").on("click", function(){
        $(".modal-buyer-refund-one").modal("show");
    });

    var refundHistoryDetailURL = Routing.generate('yilinker_backend_buyer_refund_history_detail');
    var $modalRefundHistory = $(".modal-buyer-refund-history");

    $("[data-refund-history-row]").on("click", function(){
        var $elem = $(this);
        var payoutId = $elem.data('refund-history-row');
        $modalRefundHistory.modal("show");
        $modalRefundHistory.trigger('loader.start');
        $.ajax({
            url: refundHistoryDetailURL+'?payoutId='+payoutId,
            success: function(html) {
                $modalRefundHistory.find('[data-content]').html(html);
                $modalRefundHistory.modal("refresh");
            },
            complete: function() {
                $modalRefundHistory.trigger('loader.stop');
            }
        });
        
    });
})(jQuery);
