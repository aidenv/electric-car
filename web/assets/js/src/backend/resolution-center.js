(function ($) {

    $(document).ready(function () {

        $('.datePicker').datetimepicker({
            format: "MM/DD/YYYY"
        });

        $('#searchKeyword').on('keypress', function (e) {

            if (e.keyCode === 13) {
                searchTransaction ();
            }

        });

        $('#searchBtn').on('click', function () {
            searchTransaction ();
        });

        $('.disputeLink').on('click', function () {
            var disputeId = parseInt($(this).attr('data-dispute-id'));
            window.location.replace('/resolution-center-detail?disputeId=' + disputeId);
        });

        displayDataInUrl ();

    });

})(jQuery);

/**
 * Display Data After search
 */
function displayDataInUrl ()
{
    var searchKeyword = getParameterByName('searchKeyword');
    var dateFrom = getParameterByName('dateFrom');
    var dateTo = getParameterByName('dateTo');
    var orderProductStatusId = getParameterByName('orderProductStatusId');
    var disputeStatusTypeId = getParameterByName('disputeStatusTypeId');

    $('#searchKeyword').val(searchKeyword);
    $('#dateFrom').val(dateFrom);
    $('#dateTo').val(dateTo);
    $('#orderProductStatusId').dropdown('set selected', orderProductStatusId);
    $('#disputeStatusTypeId').dropdown('set selected', disputeStatusTypeId);

    if (dateFrom === '' && dateTo === '') {
        $('#dateFrom').val(getDate (-1));
        $('#dateTo').val(getDate ());
    }

}

/**
 * Search filter for dispute transactions
 */
function searchTransaction ()
{
    var searchKeyword = $('#searchKeyword').val().trim();
    var dateFrom = $('#dateFrom').val().trim();
    var dateTo = $('#dateTo').val().trim();
    var orderProductStatusId = $('#orderProductStatusId').val().trim();
    var disputeStatusTypeId = $('#disputeStatusTypeId').val().trim();
    var params = '';

    if ( (new Date(dateFrom).getTime() > new Date(dateTo).getTime()) ) {
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

    if (orderProductStatusId !== '') {
        params += (params === '' ? '?' : '&') + 'orderProductStatusId=' + orderProductStatusId;
    }

    if (disputeStatusTypeId !== '') {
        params += (params === '' ? '?' : '&') + 'disputeStatusTypeId=' + disputeStatusTypeId;
    }

    window.location = location.protocol + '//' + location.host + location.pathname + params;
}
