(function($) {
    var $ajaxLoading = false;

    //Checkbox customize design
    $(".ui.checkbox").checkbox();

    //Single selection select box
    $(".single.selection").dropdown();

    $(".new-bank-account-modal-trigger").click(function(){
        $(".new-bank-account-modal").modal("show");

        $('.coupled-new-bank-account').modal({
            allowMultiple: false
        });
    });

    $(document).ready(function() {

        $(".show-prev-remarks").on("click", function() {
            $(".prev-remarks").slideToggle({direction: "up" }, 400);
            $(this).toggleClass("show-txt-remarks");
        });

        var $addBankAccountForm = $("form[name='add-new-bank-account']");
        var $updateBankAccountForm = $("form[name='update-bank-account']");

        var $formRules =  {
            accountTitle: {
                identifier  : 'accountTitle',
                rules: [
                    {
                        type   : 'empty',
                        prompt : 'Account title is required'
                    },
                    {
                        type   : 'maxLength[255]',
                        prompt : 'Account title can only be up to 255 characters'
                    }
                ]
            },
            accountNumber: {
                identifier  : 'accountNumber',
                rules: [
                    {
                        type   : 'empty',
                        prompt : 'Account number is required'
                    },
                    {
                        type   : 'maxLength[25]',
                        prompt : 'Account number can only be up to 25 characters'
                    }
                ]
            },
            accountName: {
                identifier  : 'accountName',
                rules: [
                    {
                        type   : 'empty',
                        prompt : 'Account name is required'
                    },
                    {
                        type   : 'maxLength[255]',
                        prompt : 'Account name can only be up to 255 characters'
                    }
                ]
            },
            bankId: {
                identifier  : 'bankId',
                rules: [
                    {
                        type   : 'empty',
                        prompt : 'Bank is required'
                    }
                ]
            }
        };

        var $addBankAccountFormSettings = {
            fields : {},
            onSuccess : function(){

                if(!$ajaxLoading){

                    $ajaxLoading = true;

                    var $postData = {
                        accountTitle    : $addBankAccountForm.form("get value", "accountTitle"),
                        accountNumber   : $addBankAccountForm.form("get value", "accountNumber"),
                        accountName     : $addBankAccountForm.form("get value", "accountName"),
                        bankId          : $addBankAccountForm.form("get value", "bankId")
                    };

                    var $bankDom = $("#clone-bank-account-table").clone().children();

                    $.ajax({
                        url: Routing.generate('merchant_bank_account_add'),
                        type: 'POST',
                        data: $postData,
                        beforeSend: function(){
                            applyLoading($addBankAccountForm);
                        },
                        success: function(response) {
                            if(response.isSuccessful){
                                var $bankAccount = response.data;
                                var $isDefault = $bankAccount.isDefault;

                                var $bankAccountCollection = $("#bank-account-collection");
                                var $successNewBankAccountMessage = $(".success-new-bank-account-message");
                                var $emptyBankAccount = $(".empty-bank-account");
                                var $notEmptyBankAccount = $(".not-empty-bank-account");
                                var $dropdown = $(".dropdown");

                                $bankDom.attr("data-bank-account-id", $bankAccount.bankAccountId);
                                $bankDom.find(".ui.checkbox").checkbox();
                                $bankDom.find(".item-name").text($bankAccount.accountTitle);
                                $bankDom.find(".account-number").text($bankAccount.accountNumber);
                                $bankDom.find(".account-name").text($bankAccount.accountName);
                                $bankDom.find(".account-bank").text($bankAccount.bankName);
                                $bankDom.find("input[name='isDefault']").attr("data-id", $bankAccount.bankAccountId);
                                $bankDom.find("input[name='isDefault']").removeClass("hidden");
                                $bankDom.find(".item.delete").attr("data-id", $bankAccount.bankAccountId);
                                $bankDom.find(".item.edit").attr("data-id", $bankAccount.bankAccountId);
                                $bankDom.find(".item.edit").attr("data-bank", JSON.stringify($bankAccount));
                                $bankDom.find(".ellipsis-dropdown").dropdown();

                                if($isDefault){
                                    $bankDom.find("input[name='isDefault']").attr("checked", "true");
                                    $bankDom.find(".bank-account-segment").addClass("active");
                                }

                                $bankAccountCollection.prepend($bankDom).masonry('prepended', $bankDom);
                                $successNewBankAccountMessage
                                    .modal({
                                        closable: false,
                                        onApprove: function () {
                                            location.reload();
                                        }
                                    })
                                    .modal('show');

                                $emptyBankAccount.addClass("hidden");
                                $notEmptyBankAccount.removeClass("hidden");

                                $bankAccountCollection.masonry({
                                    itemSelector: '.col-md-6.col-xl-4',
                                    columnWidth: '.col-md-6.col-xl-4',
                                    percentPosition: true
                                });

                                $addBankAccountForm.form("clear");
                                $dropdown.dropdown('restore defaults');
                            }
                        },
                        complete: function(){
                            $ajaxLoading = false;
                            unloadButton($addBankAccountForm);
                        }
                    });

                    return false;
                }
            }
        };

        var $updateBankAccountFormSettings = {
            fields: {},
            onSuccess : function(){

                if(!$ajaxLoading){

                    $ajaxLoading = true;

                    var $bankAccountId = $("form[name='update-bank-account']").attr("data-id");
                    var $bankDom = $("div[data-bank-account-id='" + $bankAccountId + "']");
                    var $postData = {
                        bankAccountId   : $bankAccountId,
                        accountTitle    : $updateBankAccountForm.form("get value", "accountTitle"),
                        accountNumber   : $updateBankAccountForm.form("get value", "accountNumber"),
                        accountName     : $updateBankAccountForm.form("get value", "accountName"),
                        bankId          : $updateBankAccountForm.form("get value", "bankId")
                    };

                    $.ajax({
                        url: Routing.generate('merchant_bank_account_edit'),
                        type: 'POST',
                        data: $postData,
                        beforeSend: function(){
                            applyLoading($updateBankAccountForm);
                        },
                        success: function(response) {
                            if(response.isSuccessful){
                                var $bankAccount = response.data;

                                var $bankAccountCollection = $("#bank-account-collection");
                                var $successUpdateBankMessage = $(".success-edit-bank-account-message");
                                var $dropdown = $(".dropdown");

                                $bankDom.find(".item-name").text($bankAccount.accountTitle);
                                $bankDom.find(".account-number").text($bankAccount.accountNumber);
                                $bankDom.find(".account-name").text($bankAccount.accountName);
                                $bankDom.find(".account-bank").text($bankAccount.bankName);
                                $bankDom.find(".item.edit").attr("data-bank", JSON.stringify($bankAccount));

                                $successUpdateBankMessage
                                    .modal({
                                        closable: false,
                                        onApprove: function () {
                                            location.reload();
                                        }
                                    })
                                    .modal('show');

                                $bankAccountCollection.masonry({
                                    itemSelector: '.col-md-6.col-xl-4',
                                    columnWidth: '.col-md-6.col-xl-4',
                                    percentPosition: true
                                });

                                $updateBankAccountForm.form("clear");
                                $dropdown.dropdown('restore defaults');
                            }
                        },
                        complete: function(){
                            $ajaxLoading = false;
                            unloadButton($updateBankAccountForm);
                        }
                    });

                    return false;
                }
            },
            onFailure: function(){
                $updateBankAccountForm.find(".hidden.ui.error.message.message-box.red.with-close-message")
                .removeClass("hidden");

                return false;
            }
        };

        $addBankAccountFormSettings.fields = $formRules;
        $updateBankAccountFormSettings.fields = $formRules;

        $addBankAccountForm.form($addBankAccountFormSettings);
        $updateBankAccountForm.form($updateBankAccountFormSettings);

        $('#bank-account-collection').masonry({
            itemSelector: '.col-md-6',
            columnWidth: '.col-md-6',
            percentPosition: true
        });

        $(document).on("click", "#bank-account-collection input[type='checkbox'], #bank-account-collection .ui.checkbox", function(){

            var $this = $(this);
            var $checkbox = $(".checkbox");
            var $bankAccountSegment = $(".bank-account-segment");
            var $bankAccountId = $this.find("input[name='isDefault']").data("id");

            //reset checkbox
            $checkbox.checkbox("uncheck");
            $this.checkbox("set checked");

            //reset active container
            $bankAccountSegment.removeClass("active");
            $this.parents(".bank-account-segment").addClass("active");

            $.ajax({
                url: Routing.generate("merchant_bank_account_set_default"),
                type: 'POST',
                data: {bankAccountId:$bankAccountId},
                success: function(response) {
                }
            });
        });

        $(document).on("click", ".bank-account-segment .edit", function(){

            var $bankAccount = $.parseJSON($(this).attr("data-bank"));
            var $form = $(".edit-bank-account-modal form[name='update-bank-account']");
            var $editBankAccountModal = $(".edit-bank-account-modal");
            var $coupledEdit = $('.coupled-edit');
            var bankAccountId = $bankAccount.bankAccountId;

            $editBankAccountModal.modal("show");

            $coupledEdit.modal({
                allowMultiple: false
            });

            $form.attr("data-id", bankAccountId);
            $form.find("input[name='accountTitle']").val($bankAccount.accountTitle);
            $form.find("input[name='accountNumber']").val($bankAccount.accountNumber);
            $form.find("input[name='accountName']").val($bankAccount.accountName);
            $(".bankId").val($bankAccount.bankId);
            $('.banks .typeahead').typeahead('val', $bankAccount.bankName);
        });

        $(document).on("click", ".bank-account-segment .delete", function(){
            var $this = $(this);
            var $bankAccountId = $this.data("id");
            var $bankDom = $("div[data-bank-account-id='" + $bankAccountId + "']")
            var $successDeleteModal = $(".delete-success-bank-account-modal");
            var $bankAccountCollection = $("#bank-account-collection");

            $(".delete-bank-account-modal").modal({
                onApprove: function(){

                    if(!$ajaxLoading){

                        $ajaxLoading = true;

                        var $button = $(this).find(".submit-button");
                        $.ajax({
                            url: Routing.generate("merchant_bank_account_delete"),
                            type: 'POST',
                            data: {bankAccountId:$bankAccountId},
                            beforeSend: function(){
                                $button.attr('disabled', true);
                                $button.find(".text").hide();
                                $button.find(".loader").show();
                            },
                            success: function(response) {
                                if(response.isSuccessful){

                                    $bankDom.remove();

                                    $successDeleteModal.modal("show");
                                }
                            },
                            error: function(response){
                                var $alertDefaultBankAccountModal = $(".alert-default-bank-account-modal");
                                var $bankAccountCollection = $("#bank-account-collection .bank-account");
                                var $status = response.status;
                                if($status == 400){
                                    if($bankAccountCollection.length > 1){
                                        $alertDefaultBankAccountModal.find(".sub-header").text("Please set other default bank account before deleting this account.");
                                    }
                                    else{
                                        $alertDefaultBankAccountModal.find(".sub-header").text("You must have atleast one bank account.");
                                    }

                                    $alertDefaultBankAccountModal.modal("show");
                                }
                            },
                            complete: function(){
                                $button.attr('disabled', false);
                                $button.find(".text").show();
                                $button.find(".loader").hide();

                                $bankAccountCollection.masonry({
                                    itemSelector: '.col-md-6.col-xl-4',
                                    columnWidth: '.col-md-6.col-xl-4',
                                    percentPosition: true
                                });
                            }
                        });

                        return false;

                    }
                }
            }).modal("show");
        });

        $('.banks .typeahead').typeahead({
                hint: true,
                highlight: true,
                minLength: 1,
                autoselect: 'first'
            },
            {
                source: function (query, process) {
                    var $dropDown = $('.bank-input');
                    $dropDown.attr('data-name', query);
                    $dropDown.val('');
                    return $.ajax({
                        url        : Routing.generate('bank_search_by_keyword'),
                        data       : { bankKeyword: query },
                        method     : 'get',
                        dataType   : 'json',
                        beforeSend : function () {
                            $('#bank-loader-image').addClass('loading');
                        },
                        success    : function (data) {
                            $('#bank-loader-image').removeClass('loading');
                            return process(data);
                        }
                    });
                }
            }
        ).on("typeahead:selected typeahead:autocompleted typeahead:change", function(e, bank) {
                $('.bank-input').val(bank.id).attr('data-name', bank.value);
            });

    });
})(jQuery);
