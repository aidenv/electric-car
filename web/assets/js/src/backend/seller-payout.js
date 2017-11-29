(function ($) {

    var orderProductIds = '';
    var $messageModal = $('#modal-message-container');
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

        $('#sellerContainer').on('click', '.sellerPayoutRow', function () {
            var $this = $(this);
            var sellerId = $this.attr('data-id');
            var accountName = $this.attr('data-account-name');
            var accountNumber = $this.attr('data-account-number');
            var bank = $this.attr('data-bank');
            var orderProductIds = $this.attr('data-order-product-ids').split(',');

            var isAffiliate = parseInt($this.data("is-affiliate"));

            if(isAffiliate === 1){
                $(".modal-supplier-name-container, .modal-supplier-payout-container").show();
                $(".modal-commission-amount-container .commission-amount").text("Commission Amount");
            }
            else{
                $(".modal-supplier-name-container, .modal-supplier-payout-container").hide();
                $(".modal-commission-amount-container .commission-amount").text("Payout Amount");
            }

            getUserOrderBySellerId (sellerId, accountName, accountNumber, bank, orderProductIds);
        });

        $("#userOrderModal-userOrderContainer").on('click', '.showOrderProductHistory', function() {
            orderProductIds = '';
            var orderProductId = $(this).attr('data-order-product-id');
            getOrderProductDetailById (orderProductId);
        });

        $('#proceedPaymentBtn').on('click', function () {
            orderProductIds =  $('.payoutOrderProduct:checked').map(function() { return $(this).attr('data-id'); }).get();

            if (orderProductIds.length === 0) {
                $messageModal.find('.header-content').html('Kindly Select at least One to proceed payment.');
                $messageModal.find('.detail-content').html('');
                $messageModal.modal('show');

                return false;
            }

            proceedPayment (orderProductIds, $(this));
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
        var sellerType = getParameterByName('sellerType');

        $('#searchKeyword').val(searchKeyword);
        $('#dateFrom').val(dateFrom);
        $('#dateTo').val(dateTo);
        $('#drop-down-seller-type').dropdown('set selected', sellerType);

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
        var sellerType = $('#drop-down-seller-type').val().trim();
        var params = '';

        if( (new Date(dateFrom).getTime() > new Date(dateTo).getTime()) )
        {
            $messageModal.find('.header-content').html('Invalid Date Range');
            $messageModal.find('.detail-content').html('');
            $messageModal.modal('show');
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

        if (sellerType !== '') {
            params += (params === '' ? '?' : '&') + 'sellerType=' + sellerType;
        }

        window.location = location.protocol + '//' + location.host + location.pathname + params;
    }

    /**
     * Get User Order by seller id
     * @param sellerId
     */
    function getUserOrderBySellerId (sellerId, accountName, accountNumber, bank, orderProductIds)
    {

        $.ajax({
            url: Routing.generate('yilinker_backend_order_product'),
            method: 'GET',
            dataType: 'json',
            data: {
                sellerId: sellerId,
                orderProductIds: orderProductIds,
                orderProductStatusIds: [
                    $('#status-payout-unheld').val(),
                    $('#status-item-received').val()
                ]
            },
            beforeSend: function () {
            },
            success: function (response) {
                displayProductOrder (response, accountName, accountNumber, bank);
            }
        });

    }

    /**
     * Display Product Order in Order details modal
     * @param orderProducts
     * @param accountName
     * @param accountNumber
     * @param bank
     */
    function displayProductOrder (orderProducts, accountName, accountNumber, bank)
    {
        /**
         * Reset deposit slip image references
         */
        formdata = new FormData();
        $('.deposit-slip').val('');
        $('.deposit-slip-list').empty();
        $('.payout-error-container').hide();
        $('#modal-account-name').html(accountName);
        $('#modal-account-number').html(accountNumber);
        $('#modal-bank').html(bank);

        var $affiliateContent = $('.affiliate-content');
        var $orderProductContainer = $('#userOrderModal-userOrderContainer');
        var html = '';
        var hasPayout = 0;
        var isAffiliate = false;
        var suppliers = [];
        var totalCommission = 0;
        var supplierPayout = 0;

        $orderProductContainer.html(html);
        $('#proceedPaymentBtn').show();

        $.each(orderProducts, function (key, orderProduct) {
            var checkBox = '';

            /**
             * ITEM RECEIVED BY BUYER and SELLER PAYOUT UNHELD
             */
            if (parseInt(orderProduct.orderProductStatusId) === 4 || parseInt(orderProduct.orderProductStatusId) === 22) {
                checkBox = '<input type="checkbox" class="payoutOrderProduct" id="checkboxId_' + orderProduct.orderProductId + '"' +
                    ' data-id="' + orderProduct.orderProductId + '" />';
                hasPayout++;
            }

            html += '' +
                '<tr>' +
                '<td class="align-center">' +checkBox + '</td>' +
                '<td>' + orderProduct.orderId + '</td>' +
                '<td>' + orderProduct.invoiceNumber + '</td>' +
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

            if (orderProduct.isAffiliate) {

                if (orderProduct.supplierName != '') {
                    suppliers.push(orderProduct.supplierName);
                }

                totalCommission = totalCommission + parseFloat(orderProduct.totalPrice, 4);
                supplierPayout = supplierPayout + parseFloat(orderProduct.supplierPayout, 4);
                isAffiliate = orderProduct.isAffiliate;
            }

        });

        if (isAffiliate) {
            $affiliateContent.find('#supplier-name').html(suppliers.join());
            $affiliateContent.find('#commission-amount').html(numberFormat(totalCommission));
            $affiliateContent.find('#supplier-payout-amount').html(numberFormat(supplierPayout));
            $affiliateContent.show();
        } else {
            $affiliateContent.hide();
        }


        if (hasPayout === 0) {
            $('#proceedPaymentBtn').hide();
        }

        $orderProductContainer.html(html);
        $('.modal-seller-payout-one').modal('show').modal();
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

            $shipmentHistoryContainer.html(htmlShippingInformation);
        }
        else {
            $('#historyInformationModal-shipment-information-container').html('No Shipping history');
        }

        $orderProductContainer.html(htmlOrderProduct);
        $historyContainer.html(htmlHistoryTr);
        $('.modal-seller-payout-two').modal('show').modal();

    }

    /**
     * Proceed to payment
     * @param orderProductIds
     */
    function proceedPayment (orderProductIds, $this)
    {
        var csrftoken = $("meta[name=csrf-token]").attr("content");
        if(formdata){
            $.each(orderProductIds, function(index, value){
                formdata.append('orderProductIds[]', value);
            });
            formdata.append('_token', csrftoken);
        }
        else{
            formdata = {
                orderProductIds : orderProductIds,
                _token : csrftoken
            };
        }

        $.ajax({
            url: Routing.generate('yilinker_backend_seller_payout_proceed_payment'),
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
                    $messageModal.find('.header-content').html('Successfully Changed Product Status');
                    $messageModal.find('.detail-content').html('');
                    $messageModal.modal('show');
                    $('.payoutOrderProduct:checked').map(function() { return $(this).remove(); });
                    location.reload();
                }
                else{
                    $('.payout-error-container .message-box').html(response.message);
                    $('.payout-error-container').fadeIn().delay(5000).fadeOut();
                }
            }
        });
    }

    function emptyUploadedItem(){
        var $list = $(".deposit-slip-list");
        $list.empty();
    }

    function showUploadedItem (source) {
        var $list = $(".deposit-slip-list"),
        li   = document.createElement("li"),
        img  = document.createElement("img");
        img.src = source;
        li.appendChild(img);
        $list.append(li);
        $('.modal-seller-payout-one').modal('refresh');
    }


    $('.deposit-slip').on('change', function(){
        var files = this.files;
        var i, len = files.length, img, reader, file;
        for(i = 0; i < len; i++){
            file = files[i];
            
            if ( window.FileReader ) {
                // Only process image files.
                if (!file.type.match('image.*')) {
                    continue;
                }
                reader = new FileReader();
                reader.onloadend = function (e) {
                    showUploadedItem(e.target.result);
                };
                reader.readAsDataURL(file);
            }
            if (formdata !== false) {
                formdata.append("depositSlips[]", file);
            }
        }
    });

    //Tabs
    $('.tabular.menu .item:not(".main-tab")').tab();

    // For modal Tabs
    $('.modal .tabular.menu .item').tab();

    $(".modal-seller-payout-three-trigger").click(function(){

        var $payout = $(this).data("payout");

        $('.modal-seller-payout-three').modal({
            blurring: true,
            onShow: function(){
                var $this = $(this);
                var $orderProducts = "";
                var $documents = "";
                var $orderProductDOM = $(".order-products");
                var $payoutDocumentsDOM = $(".payout-documents");

                $this.find(".reference-number-data").text($payout.referenceNumber);
                $this.find(".email-data").text($payout.email);
                $this.find(".amount-data").text($payout.amount);
                $this.find(".status-data").text($payout.status);

                $this.find(".support-csr-data").text($payout.supportCsr);
                $this.find(".date-created-data").text($payout.dateCreated);
                $this.find(".date-modified-data").text($payout.dateModified);

                $orderProductDOM.html("");
                $payoutDocumentsDOM.html("");

                $payout.orderProducts.forEach(function($orderProduct){
                    $orderProducts +=   "<tr>" +
                                          "<td>" + $orderProduct.orderProductId + "</td>" +
                                          "<td>" + $orderProduct.name + "</td>" +
                                          "<td>" + $orderProduct.amount + "</td>" +
                                          "<td>" + $orderProduct.dateCreated + "</td>" +
                                        "</tr>";
                });

                $payout.documents.forEach(function($document){
                    $documents += "<li><img src=" + $document.path + "></li>";
                });

                $orderProductDOM.html($orderProducts);
                $payoutDocumentsDOM.html($documents);
            }
        }).modal('show');
    });

})(jQuery);
