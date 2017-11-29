(function ($) {

    $(".request-payout-tbl tr:not(:first-child)").click(function(e) {
        var payoutTransaction =  new PayoutTransaction();
        payoutTransaction.details($(this).data('seller'));
    });

    var PayoutTransaction = function() {

        'strict';

        var details = function(seller) {

            var params = {seller: seller};

            $.get('/payout-transaction', params)
                .done(function( data ) {

                    var $payoutTransactionDetail = $('#payout-transaction');

                    $payoutTransactionDetail.remove();
                    
                    $(data).appendTo(document.body);
                    
                    $('#payout-transaction').modal('show');
                    
                    
                });
        }

        var response = {
            'details' : details
        }

        return response;
    }

})(jQuery);