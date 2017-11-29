var arrayOfBankAccounts = [];
var partialBankInfoId = 0;
var numbersAndDashes = /^\d+(-\d+)*$/;
var isAddressEdited = false;

var successMessageHeader = 'Bank account information has been successfully added';
var successMessageContent = '';

var successUpdateMessageHeader = 'Bank account information has been successfully updated';
var successUpdateMessageContent = '';

var successSaveMessageHeader = 'Bank account information has been successfully saved';
var successSaveMessageContent = '';

var errorSaveMessageHeader = 'Bank account information was not save.';
var errorSaveMessageContent = '';

var errorBankAccountEmptyMessageHeader = 'Create bank account to continue.';
var errorBankAccountEmptyMessageContent = '';

var formRules = {
    accountTitle: {
        identifier  : 'txt-bank-account-title',
        rules: [
            {
                type   : 'empty',
                prompt : 'Please enter your bank account title'
            },
            {
                type   : 'maxLength[255]',
                prompt : 'Bank account title must not reach 255 characters'
            }
        ]
    },
    bankId: {
        identifier  : 'drop-down-bank-name',
        rules: [
            {
                type   : 'empty',
                prompt : 'Please enter a valid bank name'
            }
        ]
    },
    accountNumber: {
        identifier  : 'txt-bank-account-number',
        rules: [
            {
                type   : 'empty',
                prompt : 'Please enter your account number'
            },
            {
                type   : 'maxLength[25]',
                prompt : 'Account number must not reach 25 characters'
            }
        ]
    },
    accountName: {
        identifier  : 'txt-bank-account-name',
        rules: [
            {
                type   : 'empty',
                prompt : 'Please enter your account name'
            },
            {
                type   : 'maxLength[255]',
                prompt : 'Account name must not reach 25 characters'
            }
        ]
    }
};

(function ($) {

    $(document).ready(function () {
        $('#btn-save-bank-information').attr('disabled', false);

        var $newbankAccountForm = $('#form-add-new-bank-account').form({
            on: 'blur',
            fields: formRules,
            onSuccess: function (e)
            {
                e.preventDefault();

                var $form = $(this);
                var accountTitle = $form.form('get value', 'txt-bank-account-title');
                var bankId = $form.form('get value', 'drop-down-bank-name');
                var accountNumber = $form.form('get value', 'txt-bank-account-number');
                var accountName = $form.form('get value', 'txt-bank-account-name');

                processBankInformation (accountTitle, bankId, accountNumber, accountName, $('#bank-account-id').val());
            }
        });

        $(document).on('click', '.trigger-modal-create-bank-account', function () {
            $('#bank-account-id').val('');
            $('#form-add-new-bank-account').form('clear').find('.message').html('');
            $('#modal-create-bank-account').modal('show');
        });

        $(document).on('click', '#bank-account-collection .delete', function () {
            var $messageModal = $('#modal-message-container');
            var partialBankInformationId = $(this).attr('data-id');
            var updatedBankInfo = getBankAccount(partialBankInformationId);

            if (updatedBankInfo.isDefault === true) {
                $messageModal.find('.header-content').html('Unable to Remove Default Bank Account.');
                $messageModal.find('.detail-content').html('');
                $messageModal.modal('show');
                return false;
            }

            $('#modal-delete-bank-account')
                .modal({
                    onApprove: function () {

                        removeBankAccount (partialBankInformationId);

                        if (!updatedBankInfo.isNew) {
                            $('.old-' + partialBankInformationId).remove();

                            arrayOfBankAccounts.push({
                                id: updatedBankInfo.id,
                                accountTitle: updatedBankInfo.accountTitle,
                                bankId: updatedBankInfo.bankId,
                                accountNumber: updatedBankInfo.accountNumber,
                                accountName: updatedBankInfo.accountName,
                                isNew: updatedBankInfo.isNew,
                                isRemoved: true,
                                isChanged: false
                            });
                        }
                        else {
                            $('.' + partialBankInformationId).remove();
                        }

                        if (arrayOfBankAccounts.length === 0) {
                            $('.not-empty-bank-account').addClass('hidden');
                            $('.empty-bank-account').removeClass('hidden');
                        }

                    }
                })
                .modal('show');
        });

        $(document).on('click', '#bank-account-collection .edit', function () {
            var bankInformationId = $(this).attr('data-id');
            var bankInformation = getBankAccount(bankInformationId);

            var $bankInformationForm = $('#form-add-new-bank-account');
            $bankInformationForm.form('clear');

            $bankInformationForm.form('set values', {
                'txt-bank-account-title'  : bankInformation.accountTitle,
                'drop-down-bank-name'     : bankInformation.bankId,
                'txt-bank-account-number' : bankInformation.accountNumber,
                'txt-bank-account-name'   : bankInformation.accountName
            });

            $('#bank-account-id').val(bankInformationId);
            $('#modal-create-bank-account').modal('show');
            $('#banks .typeahead').typeahead('val', bankInformation.bankName);
            $('.error .message').hide();
        });

        $(document).on('click', '#btn-save-bank-information', function () {
            submitBankInformation ();
        });

        $(document).on('click', '#btn-go-back', function () {
            confirmBackAction ();
        });

        $(document).on('click', '.check-box-is-default', function (e) {
            var $this = $(this);
            var bankId = $this.attr('data-id');
            $('.check-box-is-default').removeAttr('checked');
            $('.bank-account-segment').removeClass('active');
            $('.check-box-div').removeClass('checked');

            var $container = $this.closest('.bank-account-segment');
            $container.addClass('active');
            $container.find('.checkbox').addClass('checked');
            $this.prop('checked', true);

            setDefaultBank (bankId);
        });

        $('#banks .typeahead')
            .typeahead(
                {
                    hint: true,
                    highlight: true,
                    minLength: 1,
                    autoselect: 'first'
                },
                {
                    source: function (query, process) {
                        var $dropDownBank = $('#drop-down-bank-name');
                        var $loaderImage = $('#bank-loader-image');
                        $dropDownBank.attr('data-name', query);
                        $dropDownBank.val('');
                        return $.ajax({
                            url        : Routing.generate('bank_search_by_keyword'),
                            data       : { bankKeyword: query },
                            method     : 'get',
                            dataType   : 'json',
                            beforeSend : function () {
                                $loaderImage.addClass('loading');
                            },
                            success    : function (data) {
                                $loaderImage.removeClass('loading');
                                return process(data);
                            }
                        });
                    }
                }
            )
            .on("typeahead:selected typeahead:autocompleted typeahead:change", function(e, bank) {
                $('#drop-down-bank-name').val(bank.id).attr('data-name', bank.value);
            });

        var bankAccounts = JSON.parse($('#bank-accounts').val());

        if (bankAccounts.length > 0) {

            $.each(bankAccounts, function (key, bankAccount) {
                arrayOfBankAccounts.push({
                    id            : bankAccount.bankAccountId,
                    accountTitle  : bankAccount.accountTitle,
                    bankId        : bankAccount.bankId,
                    bankName      : bankAccount.bankName,
                    accountNumber : bankAccount.accountNumber,
                    accountName   : bankAccount.accountName,
                    isDefault     : bankAccount.isDefault,
                    isNew         : false,
                    isRemoved     : false,
                    isChanged     : false
                });
            });

        }

    });

    /**
     * Submit Bank Information
     *
     * @returns {boolean}
     */
    function submitBankInformation ()
    {
        var $saveBtn = $('#btn-save-bank-information');
        var $messageModal = $('#modal-message-container');
        var hasDefault = false;

        $.each(arrayOfBankAccounts, function (key, value) {

            if (arrayOfBankAccounts[key]['isDefault'] == true) {
                hasDefault = true;
                return false;
            }

        });

        if (arrayOfBankAccounts.length === 0) {
            $messageModal.find('.header-content').html(errorBankAccountEmptyMessageHeader);
            $messageModal.find('.detail-content').html(errorBankAccountEmptyMessageContent);
            $messageModal.modal('show');
            return false;
        }
        else if (hasDefault === false) {
            $messageModal.find('.header-content').html('Set your Primary Bank to continue.');
            $messageModal.find('.detail-content').html('');
            $messageModal.modal('show');
            return false;
        }

        $.ajax({
            url: Routing.generate('merchant_bank_account_submit'),
            method: 'POST',
            dataType: 'json',
            data: {
                bankInformationContainer: arrayOfBankAccounts
            },
            beforeSend: function () {
                $saveBtn.attr('disabled', true);
            },
            success: function (response) {
                $saveBtn.attr('disabled', false);

                if (response) {
                    $messageModal.find('.header-content').html(successSaveMessageHeader);
                    $messageModal.find('.detail-content').html(successSaveMessageContent);
                    $messageModal.modal('show');

                    window.location.replace(Routing.generate('merchant_accreditation'));
                }
                else {
                    $messageModal.find('.header-content').html(errorSaveMessageHeader);
                    $messageModal.find('.detail-content').html(errorSaveMessageContent);
                    $messageModal.modal('show');

                    location.reload();
                }

            }
        });

    }

    /**
     * Validate Bank Information, Save in arrayOfBankAccounts and Render in HTML
     *
     * @param accountTitle
     * @param bankId
     * @param accountNumber
     * @param accountName
     * @param bankAccountId
     */
    function processBankInformation (accountTitle, bankId, accountNumber, accountName, bankAccountId)
    {
        var $createBtn = $('#btn-create-new-bank-account');
        var $errorMessageContainer = $('#server-error-message');
        var $modalMessage = $('#modal-message-container');
        var partialBankInformationId = 'partial-' + partialBankInfoId++;

        $.ajax({
            url: Routing.generate('merchant_bank_account_validate'),
            method: 'post',
            dataType: 'json',
            data: {
                accountTitle : accountTitle,
                bankId       : bankId,
                accountNumber: accountNumber,
                accountName  : accountName
            },
            beforeSend: function () {
                $createBtn.attr('disabled', true);
            },
            success: function (response) {
                isAddressEdited = true;
                $createBtn.attr('disabled', false);

                if (response.isSuccessful) {
                    $errorMessageContainer.html('').addClass('hidden');

                    if (bankAccountId) {
                        var bankAccountInfo = getBankAccount (bankAccountId);
                        removeBankAccount (bankAccountId);

                        arrayOfBankAccounts.push({
                            id           : bankAccountId,
                            accountTitle : accountTitle,
                            bankId       : bankId,
                            accountNumber: accountNumber,
                            accountName  : accountName,
                            bankName     : $('#drop-down-bank-name').attr('data-name'),
                            isNew        : bankAccountInfo.isNew,
                            isDefault    : bankAccountInfo.isDefault,
                            isRemoved    : false,
                            isChanged    : true
                        });

                        if (!bankAccountInfo.isNew) {
                            bankAccountId = 'old-' + bankAccountId;
                        }

                        updateBankAccountHtml (bankAccountId, accountTitle, bankId, accountNumber, accountName);

                        $modalMessage.find('.header-content').html(successUpdateMessageHeader);
                        $modalMessage.find('.detail-content').html(successUpdateMessageContent);
                    }
                    else {
                        var isDefault = false;

                        if (arrayOfBankAccounts.length === 0) {
                            isDefault = true;
                        }

                        arrayOfBankAccounts.push({
                            id           : partialBankInformationId,
                            accountTitle : accountTitle,
                            bankId       : bankId,
                            accountNumber: accountNumber,
                            accountName  : accountName,
                            bankName     : $('#drop-down-bank-name').attr('data-name'),
                            isDefault    : isDefault,
                            isNew        : true,
                            isRemoved    : false,
                            isChanged    : false
                        });

                        renderBankAccountHtml (partialBankInformationId, accountTitle, bankId, accountNumber, accountName, isDefault);

                        $modalMessage.find('.header-content').html(successMessageHeader);
                        $modalMessage.find('.detail-content').html(successMessageContent);
                    }

                    $modalMessage.modal('show');

                }
                else {
                    var errorMessages = response.message;
                    var errorHtml = '';

                    $.each(errorMessages, function (key, errorMessage) {
                        errorHtml += errorMessage + ' <br>';
                    });

                    $errorMessageContainer.html(errorHtml).removeClass('hidden');
                }

            }
        });

    }

    /**
     * Create and Render HTML for Added Bank Account
     *
     * @param partialBankInformationId
     * @param bankAccountTitle
     * @param bankId
     * @param accountNumber
     * @param accountName
     * @param isDefault
     */
    function renderBankAccountHtml (partialBankInformationId, bankAccountTitle, bankId, accountNumber, accountName, isDefault)
    {
        var $bankAccountCollection = $('#bank-account-collection');
        var $newBankAccount = $('.hidden ').find('.clone-bank-account').clone(true);
        var bankName = $('#bank-id-'+bankId).val();

        $newBankAccount.addClass(partialBankInformationId);
        $newBankAccount.find('.item-name').html(bankAccountTitle);
        $newBankAccount.find('.account-number').html(accountNumber);
        $newBankAccount.find('.account-name').html(accountName);
        $newBankAccount.find('.account-bank').html(bankName);
        $newBankAccount.find('.check-box-is-default').attr('data-id', partialBankInformationId);
        $newBankAccount.find('.edit').attr('data-id', partialBankInformationId);

        $newBankAccount.find('.ellipsis-dropdown').dropdown();

        if (arrayOfBankAccounts.length === 0) {
            $bankAccountCollection.html($newBankAccount);
        }

        $bankAccountCollection.append($newBankAccount);
        $('.not-empty-bank-account').removeClass('hidden');
        $('.empty-bank-account').addClass('hidden');

        if (isDefault === true) {
            $bankAccountCollection.find('.' + partialBankInformationId).find('.check-box-is-default').trigger('click');
        }

    }

    /**
     * Update Bank Account Information HTML
     *
     * @param bankInformationId
     * @param bankAccountTitle
     * @param bankId
     * @param accountNumber
     * @param accountName
     */
    function updateBankAccountHtml (bankInformationId, bankAccountTitle, bankId, accountNumber, accountName)
    {
        var $bankAccountCollection = $('#bank-account-collection');
        var $newBankAccount = $bankAccountCollection.find('.' + bankInformationId);
        var bankName = $('#bank-id-'+bankId).val();

        $newBankAccount.find('.item-name').html(bankAccountTitle);
        $newBankAccount.find('.account-number').html(accountNumber);
        $newBankAccount.find('.account-name').html(accountName);
        $newBankAccount.find('.account-bank').html(bankName);
    }

    /**
     * Remove Bank Account in arrayOfBankAccounts
     *
     * @param partialBankInformationId
     */
    function removeBankAccount (partialBankInformationId)
    {
        var arrayContainer = arrayOfBankAccounts;

        for(var i = 0; i < arrayContainer.length; i++) {
            if (arrayContainer[i].id == partialBankInformationId) {
                arrayContainer.splice(i, 1);
                break;
            }
        }

        return arrayContainer;
    }

    /**
     * Get Bank Account in arrayOfBankAccounts
     *
     * @param partialBankInformationId
     * @returns {*}
     */
    function getBankAccount (partialBankInformationId)
    {
        var bankInfo = null;

        $.each(arrayOfBankAccounts, function (key, value) {

            if(value.id == partialBankInformationId) {
                bankInfo = arrayOfBankAccounts[key];
                return false;
            }

        });

        return bankInfo;
    }

    /**
     * Set Default Bank
     *
     * @param bankId
     */
    function setDefaultBank (bankId)
    {
        $.each(arrayOfBankAccounts, function (key, value) {

            if (arrayOfBankAccounts[key]['isDefault'] == true) {
                arrayOfBankAccounts[key]['isChanged'] = true;
            }

            if (value.id == bankId) {
                arrayOfBankAccounts[key]['isDefault'] = true;
                arrayOfBankAccounts[key]['isChanged'] = true;
            }
            else {
                arrayOfBankAccounts[key]['isDefault'] = false;
            }

        });
    }

    /**
     * Back action confirmation
     */
    function confirmBackAction ()
    {

        if (isAddressEdited) {
            $('#modal-confirm-back')
                .modal({
                    onApprove: function () {
                        window.location.replace(Routing.generate('merchant_accreditation'));
                    }
                })
                .modal('show');
        }
        else {
            window.location.replace(Routing.generate('merchant_accreditation'));
        }

    }

})(jQuery);
