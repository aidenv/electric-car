(function ($) {
    var $messageModal = $('#modal-message-container');
    var valid_extensions = /(\.jpg|\.jpeg|\.png|\.pdf)$/i;
    var fileContainer = [];

    $(document).ready(function () {

        $('.coupled').modal({
            allowMultiple: true
        });

        $('.dateTime').datetimepicker({
            format: "MM/DD/YYYY"
        });

        $(document).on('click', '#searchPayoutBatch', function () {
            searchPayoutBatchList();
        });

        $(document).on('keypress', '#searchKeyword', function (e) {
            if (e.keyCode === 13) {
                searchPayoutBatchList();
            }
        });

        $(document).on('click', '.remove-batch-payout-head', function () {
            var $this = $(this);
            var payoutBatchHeadId = parseInt($this.attr('data-value'));

            $('#modal-remove-payout-batch-head')
                .modal('show')
                .modal({
                    onApprove: function () {

                        $.ajax({
                            url     : Routing.generate('yilinker_backend_batch_payout_head_remove'),
                            method  : 'post',
                            type    : 'json',
                            data    : { payoutBatchHeadId: payoutBatchHeadId},
                            success : function (response) {
                                $messageModal.find('.header-content').html(response.message);
                                $messageModal.find('.detail-content').html('');
                                $messageModal.modal('show');

                                if (response.isSuccessful === true) {
                                    location.reload();
                                }

                            }
                        })
                    }
                });

        });

        $(document).on('click', '#modal-payout-batch-save', function () {
            var $this = $(this);
            var payoutBatchHeadId = parseInt($('#payout-batch-head-id').val());

            if (payoutBatchHeadId === 0) {
                $messageModal.find('.header-content').html('No batch payout selected');
                $messageModal.find('.detail-content').html('');
                $messageModal.modal('show');
                return false;
            }

            $.ajax({
                url        : Routing.generate('yilinker_backend_batch_payout_update'),
                method     : 'post',
                type       : 'json',
                data       : {
                    payoutBatchHeadId   : payoutBatchHeadId,
                    remarks             : $('#modal-remarks').val().trim(),
                    payoutBatchStatusId : $('#modal-select-payout-batch-status').val()
                },
                beforeSend : function () {
                    $this.attr('disabled', true);
                    $this.html('Loading..');
                },
                success    : function (response) {
                    $messageModal.find('.header-content').html(response.message);
                    $messageModal.find('.detail-content').html('');
                    $messageModal.modal('show');
                    $this.attr('disabled', false);
                    $this.html('Save');

                    if (response.isSuccessful === true && $('#modal-select-payout-batch-status').val() == $('#payout-batch-status-deposited').val()) {
                        $('.tr-' + payoutBatchHeadId).find('.remove-batch-payout-head').remove();
                        $this.remove();
                        $('.receipt-upload').hide();
                        $('#add-payout-request').hide();
                        $('.typeahead').hide();
                        $('.remove-payout-batch-detail, .remove-payout-batch-file').remove();
                    }

                }
            });

        });

        $(document).on('click', '#modal-payout-trigger', function() {
            var $this = $(this);
            $('#modal-select-payout-batch-status').dropdown('set selected', 1);
            $('#modal-remarks').val('');
            $('#modal-payout-batch-detail-list').html('');
            $('#payout-request-table').hide();
            $('#receipt-container').html('');
            fileContainer = [];
            $('.typeahead').typeahead('val','');
            $('.receipt-upload').show();
            $('#add-payout-request').show();
            $('.typeahead').show();

            $.ajax({
                url        : Routing.generate('yilinker_backend_batch_payout_create'),
                method     : 'POST',
                type       : 'json',
                data       : {
                    isPayoutBatchList: true
                },
                beforeSend : function () {
                    $this.attr('disabled', true);
                    $this.html('Loading..');
                },
                success    : function (reponse) {
                    $this.attr('disabled', false);
                    $this.html('Add');
                    var $txtPayoutBatchHeadId = $('#payout-batch-head-id');
                    var $payoutBatchDetailList = $('#modal-payout-batch-detail-list');
                    var payoutBatchDetailRow = '';
                    $payoutBatchDetailList.html(payoutBatchDetailRow);
                    $txtPayoutBatchHeadId.val(0);

                    if (reponse.isSuccessful === true) {
                        var payoutBatchHead = reponse.data.payoutBatchHead;

                        $txtPayoutBatchHeadId.val(payoutBatchHead.payoutBatchHeadId);
                        $('#modal-batch-number').html(payoutBatchHead.batchNumber);
                        $('#modal-process-by').html(payoutBatchHead.adminUser.fullName);
                        $('#modal-date-added').html(payoutBatchHead.dateAdded);
                        $('#modal-select-payout-batch-status').dropdown('set selected', payoutBatchHead.payoutBatchStatus.id);
                        $('#modal-remarks').html(payoutBatchHead.remarks);

                        $('#modal-batch-payout').modal('setting', 'closable', false).modal('show').modal({blurring: true});
                    }
                    else {
                        $messageModal.find('.header-content').html('Server Error, try again later');
                        $messageModal.find('.detail-content').html('');
                        $messageModal.modal('show');
                    }

                }
            });

        });

        $(document).on('change', '#file-receipt', function () {
            var $this = $(this);
            var $receiptRowContainer = $('#receipt-container');
            var fileName = $this.prop('files')[0]['name'];
            var payoutBatchHeadId = parseInt($('#payout-batch-head-id').val());
            var receiptRowHtml = '';

            if (payoutBatchHeadId === 0) {
                $messageModal.find('.header-content').html('Invalid Batch Payout ID');
                $messageModal.find('.detail-content').html('');
                $messageModal.modal('show');
                return false;
            }

            if (valid_extensions.test(fileName.toLowerCase())) {
                var formData = new FormData();
                formData.append('receipt', $this.prop('files')[0]);
                formData.append('payoutBatchHeadId', payoutBatchHeadId);

                $.ajax({
                    url: Routing.generate('yilinker_backend_payout_batch_file_upload'),
                    method: 'post',
                    type: 'json',
                    cache: false,
                    processData: false,
                    contentType: false,
                    data: formData,
                    success: function (response) {
                        $messageModal.find('.header-content').html(response.message);
                        $messageModal.find('.sub-header-content').html('');
                        $messageModal.modal('show');
                        $this.val('');

                        if (response.isSuccessful === true) {
                            var downloadImageUrl = Routing.generate('yilinker_backend_payout_batch_file_download') + '/' + response.data.payoutBatchFileId;
                            $('#receipt-div').show();
                            receiptRowHtml = '' +
                                '<li id="li-' + response.data.payoutBatchFileId + '">' + response.data.fileName +
                                    '<a href="#" class="delete remove-payout-batch-file" data-value="' + response.data.payoutBatchFileId + '"><span class="item-icon icon-times"></span> delete</a>' +
                                    '<a href="' + downloadImageUrl + '" class="download"><span class="item-icon icon-arrow-short-down"></span> download</a>' +
                                '</li>';
                            $receiptRowContainer.append(receiptRowHtml);
                        }

                    }
                });
            }
            else {
                $messageModal.find('.header-content').html('Please upload a valid file.');
                $messageModal.find('.sub-header-content').html('Allowed file extensions are jpeg, png or pdf.');
                $messageModal.modal('show');
                $this.val('');
            }
        });

        $(document).on('click', '.remove-payout-batch-file', function () {
            var $this = $(this);
            var batchPayoutFileId = parseInt($this.attr('data-value'));
            $('#remove-payout-batch-file').attr('data-id', batchPayoutFileId);
            $('#modal-remove-payout-batch-file').modal('show');
        });

        $(document).on('click', '#remove-payout-batch-file', function () {
            var $this = $(this);
            var batchPayoutFileId = parseInt($this.attr('data-id'));

            if (batchPayoutFileId > 0) {
                $.ajax({
                    url     : Routing.generate('yilinker_backend_payout_batch_file_remove'),
                    method  : 'post',
                    type    : 'json',
                    data    : { batchPayoutFileId: batchPayoutFileId},
                    success : function (response) {
                        $messageModal.find('.header-content').html(response.message);
                        $messageModal.find('.detail-content').html('');
                        $messageModal.modal('show');

                        if (response.isSuccessful === true) {
                            $('#li-' + batchPayoutFileId).remove();
                        }

                    }
                })
            }
        });

        $('.typeahead')
            .typeahead(
                {
                    hint: true,
                    highlight: true,
                    minLength: 1,
                    autoselect: 'first'
                },
                {
                    name: 'payoutBatchDetail',
                    displayKey: 'referenceNumber',
                    source: function (query, process) {
                        return $.get(Routing.generate('yilinker_backend_retrieve_payout_batch_detail'), { searchKeyword: query }, function (data) {
                            return process(data);
                        });
                    }
                }
            )
            .blur(function (e) {
                $(this).val($(this).attr('data-value'));
                $('#add-payout-request').attr('data-id', $(this).attr('data-id'));
            })
            .on("typeahead:selected typeahead:autocompleted typeahead:change", function(e, response) {
                $(this).val(response.referenceNumber).attr('data-value', response.referenceNumber);
                $('#add-payout-request').attr('data-id', response.payoutRequestId)
                                        .attr('data-payout-request', JSON.stringify(response));
            }
        );

        $(document).on('click', '#add-payout-request', function () {
            var $this = $(this);

            if (typeof $(this).attr('data-payout-request') === "undefined") {
                $messageModal.find('.header-content').html('Invalid Payout request.');
                $messageModal.find('.detail-content').html('');
                $messageModal.modal('show');
                return false;
            }

            var payoutRequest = JSON.parse($(this).attr('data-payout-request'));

            if ($('.tr-' + payoutRequest.payoutRequestId).length == 0) {

                $.ajax({
                    url         : Routing.generate('yilinker_backend_create_payout_batch_detail'),
                    method      : 'post',
                    type        : 'json',
                    data        : {
                        payoutBatchHeadId : $('#payout-batch-head-id').val(),
                        payoutRequestId   : payoutRequest.payoutRequestId
                    },
                    beforeSend  : function () {
                        $this.attr('disabled', true);
                    },
                    success     : function (response) {
                        $this.attr('disabled', false);

                        if (response.isSuccessful == true) {
                            var $payoutBatchDetailList = $('#modal-payout-batch-detail-list');
                            var payoutRequestRow = '';
                            $('#payout-request-table').show();

                            payoutRequestRow += '' +
                                '<tr id="tr-' + response.data.payoutBatchDetailId + '" class="payout-request-row tr-'+ payoutRequest.payoutRequestId +'" data-amount="' + payoutRequest.netAmount + '">' +
                                    '<td class="tr-row-count">' + 1 + '</td>' +
                                    '<td>' + payoutRequest.dateAdded + '</td>' +
                                    '<td>' +
                                        '' + payoutRequest.referenceNumber + '<br>' +
                                        '<big>' + payoutRequest.requestBy.fullName + '</big><br>' +
                                        '<i>' + payoutRequest.requestSellerType.name + ' </i>' +
                                    '</td>' +
                                    '<td>' + payoutRequest.payoutRequestMethod.name + '</td>' +
                                    '<td>' + payoutRequest.bankName + '</td>' +
                                    '<td>' +
                                        '' + payoutRequest.bankAccountName + '<br>' +
                                        '' + payoutRequest.bankAccountNumber + '</td>' +
                                    '<td>' +
                                        'P' + numberFormat(payoutRequest.requestedAmount) + '' +
                                    '</td>' +
                                    '<td><span class="red-color">' + numberFormat(payoutRequest.charge) + '</span></td>' +
                                    '<td><big>P' + numberFormat(payoutRequest.netAmount) + '</big></td>' +
                                    '<td>' +
                                        '<a href="#" class="delete remove-payout-batch-detail" data-value="' + response.data.payoutBatchDetailId + '"><span class="item-icon icon-times"></span></a>' +
                                    '</td>' +
                                '</tr>';

                            $payoutBatchDetailList.append(payoutRequestRow);
                        }
                        else {
                            $messageModal.find('.header-content').html(response.message);
                            $messageModal.find('.detail-content').html('');
                            $messageModal.modal('show');
                            return false;
                        }
                        displayTotalBatchAmount ();
                        fixRowCount();
                    }
                });
            }
        });

        $(document).on('click', '.remove-payout-batch-detail', function () {
            var $this = $(this);
            var payoutBatchDetailId = parseInt($this.attr('data-value'));
            $('#remove-payout-request').attr('data-id', payoutBatchDetailId);
            $('#modal-remove-payout-batch-detail').modal('show');
        });

        $(document).on('click', '#remove-payout-request', function () {
            var $this = $(this);
            var payoutBatchDetailId = parseInt($this.attr('data-id'));

            if (payoutBatchDetailId > 0) {
                $.ajax({
                    url     : Routing.generate('yilinker_backend_batch_payout_detail_remove'),
                    method  : 'post',
                    type    : 'json',
                    data    : { payoutBatchDetailId: payoutBatchDetailId},
                    success : function (response) {
                        $messageModal.find('.header-content').html(response.message);
                        $messageModal.find('.detail-content').html('');
                        $messageModal.modal('show');

                        if (response.isSuccessful === true) {
                            $('#tr-' + payoutBatchDetailId).remove();
                            displayTotalBatchAmount();
                        }

                    }
                })
            }

        });

        $(document).on('click', '.update-row', function () {
            var $this = $(this);
            var payoutBatchHeadId = parseInt($this.parent().attr('data-id'));
            var isEditable = $this.parent().attr('data-is-editable');

            if (payoutBatchHeadId > 0) {
                $.ajax({
                    url: Routing.generate('yilinker_backend_get_payout_data'),
                    method: 'post',
                    type: 'json',
                    data: {
                        payoutBatchHeadId : payoutBatchHeadId
                    },
                    beforeSend: function () {
                        $this.attr('disabled', true);
                    },
                    success: function (response) {
                        var $payoutBatchDetailList = $('#modal-payout-batch-detail-list');
                        var payoutRequestRow = '';
                        var $receiptRowContainer = $('#receipt-container');
                        var receiptRow = '';
                        $receiptRowContainer.html('');
                        $('#receipt-div').hide();

                        if (response.isSuccessful == false) {
                            $messageModal.find('.header-content').html(response.message);
                            $messageModal.find('.detail-content').html('');
                            $messageModal.modal('show');
                            return false;
                        }

                        var payoutBatchHeadData = response.data.payoutBatchHead;
                        var payoutBatchDetailData = response.data.payoutBatchDetail;
                        var payoutBatchFileData = response.data.payoutBatchFile;

                        $('#modal-select-payout-batch-status').dropdown('set selected', payoutBatchHeadData.payoutBatchStatus.id);
                        $('#modal-remarks').val(payoutBatchHeadData.remarks);
                        $('#payout-request-table').show();
                        $('#payout-batch-head-id').val(payoutBatchHeadData.payoutBatchHeadId);
                        $('#modal-batch-number').html(payoutBatchHeadData.batchNumber);
                        $('#modal-process-by').html(payoutBatchHeadData.adminUser.fullName);
                        $('#modal-date-added').html(payoutBatchHeadData.dateAdded);
                        $payoutBatchDetailList.html('');

                        if (payoutBatchDetailData.length > 0) {

                            $.each(payoutBatchDetailData, function (key, payoutBatchDetail) {

                                payoutRequestRow += '' +
                                    '<tr id="tr-' + payoutBatchDetail.payoutBatchDetailId + '" class="payout-request-row" data-amount="' + payoutBatchDetail.payoutRequest.netAmount + '">' +
                                        '<td class="tr-row-count">' + 1 + '</td>' +
                                        '<td>' + payoutBatchDetail.payoutRequest.dateAdded + '</td>' +
                                        '<td>' +
                                            '' + payoutBatchDetail.payoutRequest.referenceNumber + '<br>' +
                                            '<big>' + payoutBatchDetail.payoutRequest.requestBy.fullName + '</big><br>' +
                                            '<i>' + payoutBatchDetail.payoutRequest.requestSellerType.name + ' </i>' +
                                        '</td>' +
                                        '<td>' + payoutBatchDetail.payoutRequest.payoutRequestMethod.name + '</td>' +
                                        '<td>' + payoutBatchDetail.payoutRequest.bankName + '</td>' +
                                        '<td>' +
                                            '' + payoutBatchDetail.payoutRequest.bankAccountName + '<br>' +
                                            '' + payoutBatchDetail.payoutRequest.bankAccountNumber + '</td>' +
                                        '<td>' +
                                            'P' + numberFormat(payoutBatchDetail.payoutRequest.requestedAmount) + '' +
                                        '</td>' +
                                        '<td><span class="red-color">' + numberFormat(payoutBatchDetail.payoutRequest.charge) + '</span></td>' +
                                        '<td><big>P' + numberFormat(payoutBatchDetail.payoutRequest.netAmount) + '</big></td>' +
                                        '<td>' +
                                            '<a href="#" class="delete remove-payout-batch-detail" data-value="' + payoutBatchDetail.payoutBatchDetailId + '"><span class="item-icon icon-times"></span></a>' +
                                        '</td>' +
                                    '</tr>';

                            });

                            $payoutBatchDetailList.html(payoutRequestRow);
                        }
                        displayTotalBatchAmount ();
                        fixRowCount();

                        if (payoutBatchFileData.length > 0) {

                            $.each(payoutBatchFileData, function (key, payoutBatchFile) {
                                var downloadImageUrl = Routing.generate('yilinker_backend_payout_batch_file_download') + '/' + payoutBatchFile.payoutBatchFileId;
                                receiptRow = '' +
                                    '<li id="li-' + payoutBatchFile.payoutBatchFileId + '">' + payoutBatchFile.fileName +
                                        '<a href="#" class="delete remove-payout-batch-file" data-value="' + payoutBatchFile.payoutBatchFileId + '"><span class="item-icon icon-times"></span> delete</a>' +
                                        '<a href="' + downloadImageUrl + '" class="download"><span class="item-icon icon-arrow-short-down"></span> download</a>' +
                                    '</li>';
                            });

                            $receiptRowContainer.html(receiptRow);
                            $('#receipt-div').show();
                        }

                        if (isEditable == 'false') {
                            $('#modal-payout-batch-save').remove();
                            $('.receipt-upload').hide();
                            $('#add-payout-request').hide();
                            $('.typeahead').hide();
                            $('.remove-payout-batch-detail, .remove-payout-batch-file').remove();
                        }

                        $('#modal-batch-payout').modal('show').modal({blurring: true});

                    }
                })
            }

        });

        $(document).on('click', '.close-modal', function () {
            window.location.reload();
        });

        displayDataInUrl();
    });

    /**
     * Display data using url payload
     */
    function displayDataInUrl ()
    {
        var $dateFrom = $('#dateFrom');
        var $dateTo = $('#dateTo');
        var searchKeyword = getParameterByName('searchKeyword');
        var dateFrom = getParameterByName('dateFrom');
        var dateTo = getParameterByName('dateTo');

        $('#searchKeyword').val(searchKeyword);
        $dateFrom.val(dateFrom);
        $dateTo.val(dateTo);

        if (dateFrom === '' && dateTo === '') {
            $dateFrom.val(getDate (-1));
            $dateTo.val(getDate ());
        }

    }

    /**
     * Search and display payout request list
     *
     * @returns {boolean}
     */
    function searchPayoutBatchList ()
    {
        var searchKeyword = $('#searchKeyword').val().trim();
        var dateFrom = $('#dateFrom').val().trim();
        var dateTo = $('#dateTo').val().trim();
        var param = '';

        if ((new Date(dateFrom).getTime() > new Date(dateTo).getTime())) {
            $messageModal.find('.header-content').html('Invalid date range.');
            $messageModal.find('.detail-content').html('');
            $messageModal.modal('show');
            return false;
        }

        if (searchKeyword !== '') {
            param += '?searchKeyword=' + searchKeyword;
        }

        if (dateFrom !== '') {
            param += (param === '' ? '?' : '&') + 'dateFrom=' + dateFrom;
        }

        if (dateTo !== '') {
            param += (param === '' ? '?' : '&') + 'dateTo=' + dateTo;
        }

        window.location = location.protocol + '//' + location.host + location.pathname + param;
    }

    /**
     * Recalculate and display total batch amount
     */
    function displayTotalBatchAmount()
    {
        var totalBatchAmount = 0;

        $.each($('.payout-request-row'), function (key, val) {
            totalBatchAmount += parseFloat($(this).attr('data-amount'));
        });
        $('#modal-total-batch-amount').html('P' + numberFormat(totalBatchAmount));

    }

    /**
     * Add count to row
     */
    function fixRowCount()
    {
        $.each($('.payout-batch-detail-row'), function (key, val) {
            $(this).find('.tr-row-count').html(++key);
        });
    }

})(jQuery);
