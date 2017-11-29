(function ($) {

    $(document).ready(function () {

        $(document).on('click', '.btn-render-user-detail-modal', function () {
            var userId = $(this).attr('data-id');
            getUserDetail (userId);
        });

        $(document).on('click', '#btn-generate-code', function () {
            var $this = $(this);
            var userId = $this.attr('data-id');

            if (parseInt(userId) === 0) {
                return;
            }

            $.ajax({
                url: Routing.generate('admin_seller_generate_referral_code'),
                method: 'POST',
                dataType: 'json',
                data: {
                    userId: userId
                },
                beforeSend: function () {
                    $this.attr('disabled', true);
                },
                success: function (response) {
                    $this.attr('disabled', false);

                    if (response.isSuccessful) {
                        $('#div-generated-code').html(response.referralCode);
                    }

                }
            });

        });

        $('#searchBtn').on('click', function () {
            searchBuyer();
        });

        $('#searchKeyword').on('keypress', function (e) {

            if (e.keyCode === 13) {
                searchBuyer();
            }

        });

        displayDataInUrl ();

        function displayDataInUrl ()
        {
            var searchKeyword = getParameterByName('searchKeyword');

            $('#searchKeyword').val(searchKeyword);
        }

        function searchBuyer ()
        {
            var searchKeyword = $('#searchKeyword').val().trim();
            var url = '';

            if (searchKeyword !== '') {
                url += (url === '' ? '?' : '&') + 'searchKeyword=' + searchKeyword;
            }

            window.location = url;
        }

        /**
         * Get Details by seller Id
         *
         * @param userId
         */
        function getUserDetail (userId)
        {
            $.ajax({
                url: Routing.generate('admin_user_detail'),
                method: 'get',
                dataType: 'json',
                data: {
                    userId: userId
                },
                beforeSend: function () {
                    $('#div-address').html('');
                    $('#div-generated-code').html('');
                    $('#btn-generate-code').attr('data-id', 0);
                },
                success: function (response) {

                    if (response.isSuccessful) {
                        renderUserDetail (response.details, userId);
                    }

                }
            });
        }

        /**
         * Render Details in Modal
         *
         * @param details
         */
        function renderUserDetail (details, userId)
        {
            var fullLocation = details.userAddress !== '' && details.userAddress.fullLocation !== null ? details.userAddress.fullLocation : 'No Address';
            var referralCode = details.referralCode !== '' && details.referralCode !== null ? details.referralCode : '';
            var $bankDetailContainer = $('.bank-details');

            $('#div-address').html(fullLocation);
            $('#div-generated-code').html(referralCode);
            $('#btn-generate-code').attr('data-id', userId);
            $('.bank-edit-form').addClass('hidden');
            $bankDetailContainer.find('.user-id').val(userId);
            if(details.defaultBank){
                $bankDetailContainer.removeClass('hidden');
                $bankDetailContainer.find('.account-number').html(details.defaultBank.accountNumber);
                $bankDetailContainer.find('.account-name').html(details.defaultBank.accountName);
                $bankDetailContainer.find('.bank-name').html(details.defaultBank.bankName);
                $bankDetailContainer.find('.bank-id').val(details.defaultBank.bankId);
                $bankDetailContainer.find('.account-title').html(details.defaultBank.accountTitle);
                $bankDetailContainer.find('.bank-account-id').val(details.defaultBank.bankAccountId);
                $('.empty-bank-container').addClass('hidden');
                $('.bank-account-prompt').hide();
                
            }
            else{
                $('.bank-details').addClass('hidden');
                $('.empty-bank-container').removeClass('hidden');
            }

            $('.user-detail-menu .item:first').trigger('click');
            $('#modal-register-user').modal('show');
        }

        $('.modal .tabular.menu .item').tab();

        $('.trigger-edit-bank, .trigger-add-bank').on('click', function(event){

            var $editBankForm = $('.bank-edit-form');
            var $bankDetailContainer = $('.bank-details');
            var accountNumber = "";
            var accountName = "";
            var accountTitle = ""
            var accountId = ""
            var bankId = 0;
            var $bankDropdown = $editBankForm.find(".bank-list-dropdown");
            $bankDropdown.dropdown('restore defaults');
            if($(event.target).hasClass('trigger-edit-bank')){
                accountNumber = $bankDetailContainer.find('.account-number').html();
                accountName = $bankDetailContainer.find('.account-name').html();
                accountTitle = $bankDetailContainer.find('.account-title').html();
                accountId = $bankDetailContainer.find('.bank-account-id').val();
                bankId = $bankDetailContainer.find('.bank-id').val();
            }

            $editBankForm.find('.account-title-input').val(accountTitle);
            $editBankForm.find('.account-number-input').val(accountNumber);
            $editBankForm.find('.account-name-input').val(accountName);
            $editBankForm.find('.account-id-input').val(accountId); 
            $bankDropdown.dropdown('set selected', bankId);

            $editBankForm.removeClass('hidden');
            $bankDetailContainer.addClass('hidden');
            $('.empty-bank-container').addClass('hidden');
            $('#modal-register-user').modal('refresh');
        });
        
        $('.trigger-save-bank').on('click', function(){
            var $button = $(this);
            var $editBankForm = $('.bank-edit-form');
            
            var $accountTitleInput = $editBankForm.find('.account-title-input');
            var $accountNumberInput = $editBankForm.find('.account-number-input');
            var $accountNameInput = $editBankForm.find('.account-name-input');
            var $bankDropdown = $editBankForm.find(".bank-list-dropdown");
            var accountId = parseInt($editBankForm.find('.account-id-input').val(), 10);
            var userId = parseInt($('.bank-details .user-id').val(), 10);
            var route = accountId > 0 ? 'admin_edit_user_bank' : 'admin_add_user_bank';
            var accountName = $accountNameInput.val();
            var accountNumber = $accountNumberInput.val();
            var accountTitle = $accountTitleInput.val();
            var bankId = parseInt($bankDropdown.dropdown('get value'), 10);
            
            $.ajax({
                url: Routing.generate(route),
                method: 'POST',
                dataType: 'json',
                data: {
                    userId : userId,
                    accountName : accountName,
                    accountNumber : accountNumber,
                    accountTitle : accountTitle,
                    bankId : bankId,
                    accountId : accountId
                },
                beforeSend : function() {
                    $button.addClass('disabled');
                    $('.bank-account-prompt').hide();
                },
                complete : function() {
                    $button.removeClass('disabled');
                },
                success: function (response) {
                    var $promptContainer;
                    if(response.isSuccessful){
                        $promptContainer = $('.bank-account-prompt.success');
                        setTimeout(function(){
                            location.reload();
                        }, 1500);
                    }
                    else{
                        $promptContainer = $('.bank-account-prompt.error');
                    }
                    $promptContainer.find('.message-box').html(response.message);
                    $promptContainer.show();                    
                }
            });
        });

        $('.trigger-cancel-bank').on('click', function(){
            var $editBankForm = $('.bank-edit-form');
            var $bankDetailContainer = $('.bank-details');
            var $emptyBankContainer = $('.empty-bank-container');
            $editBankForm.addClass('hidden');
            if(parseInt($editBankForm.find('.account-id-input').val(), 10) > 0){
                $bankDetailContainer.removeClass('hidden');
                $emptyBankContainer.addClass('hidden');
            }
            else{
                $bankDetailContainer.addClass('hidden');
                $emptyBankContainer.removeClass('hidden');
            }
            $('#modal-register-user').modal('refresh');
        });


    });

    $(".bank-list-dropdown").dropdown();

})(jQuery);
