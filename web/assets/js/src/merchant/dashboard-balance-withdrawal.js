(function($) {
    var $payoutRequestAmount = $('#payout_request_requestedAmount');
    var $bankChargeInformation = $('[data-bankcharge-information]');
    var bankChargeUrl = Routing.generate('dashboard_balance_withdrawal_bank_charge');

    var $depositBank = $('[data-deposit-bank]');
    var $depositCheck = $('[data-deposit-check]');
    $('[name="payout_request[payoutRequestMethod]"]').on('change', function() {
        var requestMethod = $(this).val();
        if (requestMethod == 1) {
            $depositBank.show();
            $depositCheck.hide();
        }
        else if (requestMethod == 2) {
            $depositBank.hide();
            $depositCheck.show();
        }
    });

    $.fn.form.settings.rules.validWithdrawal = function(value) {
        var $availableBalance = $('[data-available-balance]');
        var available = $availableBalance.text().replace(/,/g, '');
        value = parseFloat(value.replace(/,/g, ''));

        return value >= 100 && value <= parseFloat(available);
    };

    var $payoutRequestForm = $('form[name="payout_request"]');
    var $summaryModal = $('.summary-modal');
    $summaryModal.yiLoader();

    if ($payoutRequestForm.length) {
        $payoutRequestForm.form({
            fields: {
                'payout_request[requestedAmount]': {
                    identifier: 'payout_request[requestedAmount]',
                    rules: [
                        {
                            type: 'validWithdrawal',
                            prompt: 'payout_request[requestedAmount]|Withdraw amount has a minimum of P100.00 and should not exceed the available balance'
                        }
                    ]
                },
                'payout_request[confirmationCode]': {
                    identifier: 'payout_request[confirmationCode]',
                    rules: [{
                        type: 'empty',
                        prompt: 'payout_request[confirmationCode]|Confirmation code is required'
                    }]
                }
            },
            onFailure: function(errors) {
                $payoutRequestForm.find('.form-error-prompt').hide();

                $.each(errors, function(index, value) {
                    var error = value.split('|');
                    var $elem = $('[name="'+error.shift()+'"]');
                    $elem
                        .next('.form-error-prompt')
                        .show()
                        .html('<ul><li>'+error+'</li></ul>')
                    ;
                });

                return false;
            },
            onSuccess: function() {
                $summaryModal.modal('show');
                var requestAmount = $payoutRequestAmount.val();
                requestAmount = parseFloat(requestAmount.replace(/,/g, '')).toFixed(2);
                $payoutRequestAmount.val(requestAmount);

                $.ajax({
                    url: bankChargeUrl,
                    beforeSend: function() {
                        $summaryModal.trigger('loader.start');
                    },
                    data: {
                        requestAmount: requestAmount
                    },
                    success: function(html) {
                        $bankChargeInformation.html(html);
                    },
                    complete: function() {
                        $summaryModal.trigger('loader.stop');
                    }
                });

                
            }
        });
    }

    var preventSubmit = function(evt) {
        evt.preventDefault();
    };

    $(".summary-modal-trigger").on("click", function(evt){
        $payoutRequestForm.one('submit', preventSubmit);
        $payoutRequestForm.submit();
    });

    var 
        $confirmationCodeBtn = $('[data-confirmation-code-btn]'),
        $confirmationCodeBtnTimer = $('[data-confirmation-code-timer]')
    ;

    $confirmationCodeBtn.on('otp.stop', function(evt, response) {
        
        if(parseInt(response.status,10) === 200 ){        
            $confirmationCodeBtnTimer.countdown(moment().add(1, 'minutes')._d, function(event) {
                $(this).text(
                    event.strftime('%M:%S')
                );
            }).on('finish.countdown', function() {
                $confirmationCodeBtn.show();
                $confirmationCodeBtnTimer.hide();
            });
            $confirmationCodeBtn.hide();
            $confirmationCodeBtnTimer.show();
        }       
    });

})(jQuery);
