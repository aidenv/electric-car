(function ($) {
    var arrayOfBankAccounts = [];
    var partialBankInfoId = 0;

    var formRules = {
        accountTitle: {
            identifier  : 'txt-bank-account-title',
            rules: [
                {
                    type   : 'empty',
                    prompt : 'This field is required'
                },
                {
                    type   : 'maxLength[255]',
                    prompt : 'Please enter at most 255 characters'
                }
            ]
        },
        bankId: {
            identifier  : 'drop-down-bank-name',
            rules: [
                {
                    type   : 'empty',
                    prompt : 'This field is required'
                },
                {
                    type   : 'integer',
                    prompt : 'Please enter an integer value'
                }
            ]
        },
        accountNumber: {
            identifier  : 'txt-bank-account-number',
            rules: [
                {
                    type   : 'empty',
                    prompt : 'This field is required'
                },
                {
                    type   : 'maxLength[25]',
                    prompt : 'Please enter at most 25 characters'
                },
                {
                    type   : 'integer',
                    prompt : 'Please enter an integer value'
                }
            ]
        },
        accountName: {
            identifier  : 'txt-bank-account-name',
            rules: [
                {
                    type   : 'empty',
                    prompt : 'This field is required'
                },
                {
                    type   : 'maxLength[255]',
                    prompt : 'Please enter at most 255 characters'
                }
            ]
        }
    };

    $(document).ready(function () {

    });


})(jQuery);
