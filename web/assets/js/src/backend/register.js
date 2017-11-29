(function($) {
    var $messageModal = $('#modal-message-container');

    $(document).ready(function () {
        $('#registerBtn').text('Register');

        $('#registerBtn').on('click', function () {
            var $this = $(this);
            var $username = $('#username');
            var $password = $('#password');
            var $firstName = $('#firstName');
            var $lastName = $('#lastName');
            var $userRole = $('#userRole');
            var $confirmPassword = $('#confirmPassword');
            var username = $username.val().trim();
            var password = $password.val().trim();
            var firstName = $firstName.val().trim();
            var lastName = $lastName.val().trim();
            var userRole = $userRole.val();
            var confirmPassword = $confirmPassword.val().trim();
            var csrfToken = $("meta[name=csrf-token]").attr("content");
            var $tableRow = $('#adminUserRow');
            var adminUserId = parseInt($this.attr('data-id'));
            var $errorContainer = $('#errorContainer');
            var $errorMessage = $('#errorMessage');
            $errorContainer.hide();
            $errorMessage.html('');

            if (username === '') {
                $errorContainer.show();
                $errorMessage.append('<br> Username is required');
                return false;
            }

            if (password === '') {
                $errorContainer.show();
                $errorMessage.append('<br> Password is required');
                return false;
            }

            if (password !== confirmPassword) {
                $errorContainer.show();
                $errorMessage.append('<br> Password should match Confirm Password');
                return false;
            }

            if (firstName === '') {
                $errorContainer.show();
                $errorMessage.append('<br> First name is required');
                return false;
            }

            if (lastName === '') {
                $errorContainer.show();
                $errorMessage.append('<br> Last name is required');
                return false;
            }

            if (parseInt(userRole) === 0) {
                $errorContainer.show();
                $errorMessage.append('<br> User role is required');
                return false;
            }

            if(/^[a-zA-Z- ]*$/.test(firstName) == false || /^[a-zA-Z- ]*$/.test(lastName) == false) {
                $errorContainer.show();
                $errorMessage.append('<br> First name and Last name should not contain special characters or numbers');
                return false;
            }

            var formData = {
                'userId': adminUserId,
                'username': username,
                'password': password,
                'confirmPassword': confirmPassword,
                'firstName': firstName,
                'lastName': lastName,
                'userRole': userRole,
                'csrfToken': csrfToken
            };

            if (adminUserId !== 0) {

                $.ajax({
                    url: Routing.generate('admin_account_edit'),
                    data: formData,
                    method: 'POST',
                    dataType: 'json',
                    beforeSend: function () {
                        $this.attr('disabled', true);
                        $errorContainer.hide();
                        $errorMessage.html('');
                    },
                    success: function (response) {
                        $this.attr('disabled', false);

                        if (response.isSuccessful) {
                            $('#registerBtn').text('Register');
                            $messageModal.find('.header-content').html('Account successfully updated');
                            $messageModal.find('.detail-content').html();
                            $messageModal.modal('show');
                            var isChecked = response.data.isActive ? 'checked' : '';
                            var jsonData = JSON.stringify(response.data);
                            var $tableRow = $('#adminId_' + response.data.id);
                            $tableRow.attr('data', jsonData);
                            var html =
                                '<td>' + response.data.id + '</td>' +
                                '<td>' + response.data.username + '</td>' +
                                '<td>' + response.data.firstName + ' ' + response.data.lastName + '</td>' +
                                '<td>' + response.data.role + '</td>' +
                                '<td class="txtcenter">' +
                                    '<div>' +
                                        '<input class="toggle-one deactivate" type="checkbox" data-id="' + response.data.id + '" data-toggle="toggle" ' + isChecked + ' />' +
                                    '</div>' +
                                '</td>';

                            $tableRow.html(html);

                            $('#registerBtn').attr('data-id', '0');
                            $username.val('').attr('disabled', false);
                            $password.val('').attr('disabled', false);
                            $confirmPassword.val('').attr('disabled', false);
                            $firstName.val('');
                            $lastName.val('');
                            $userRole.dropdown('set selected', 0);
                        }
                        else {
                            $errorContainer.show();
                            $errorMessage.html(response.message);
                        }

                    }
                });
            }
            else {

                $.ajax({
                    url: Routing.generate('admin_account_register'),
                    data: formData,
                    method: 'POST',
                    dataType: 'json',
                    beforeSend: function () {
                        $this.attr('disabled', true);
                        $errorContainer.hide();
                        $this.attr('disabled', true);
                    },
                    success: function (response) {
                        $this.attr('disabled', false);

                        if (response.isSuccessful) {
                            $messageModal.find('.header-content').html('Account successfully registered');
                            $messageModal.find('.detail-content').html();
                            $messageModal.modal('show');
                            $username.val('');
                            $firstName.val('');
                            $lastName.val('');
                            $password.val('');
                            $confirmPassword.val('');
                            $userRole.dropdown('set selected', 0);
                            var isChecked = response.data.isActive ? 'checked' : '';
                            var jsonData = JSON.stringify(response.data);
                            var html = "<tr id='adminId_" + response.data.id + "' class='tableRow' data='" + jsonData + "'>" +
                                '<td>' + response.data.id + '</td>' +
                                '<td>' + response.data.username + '</td>' +
                                '<td>' + response.data.firstName + ' ' + response.data.lastName + '</td>' +
                                '<td>' + response.data.role + '</td>' +
                                '<td class="txtcenter">' +
                                '<div>' +
                                    '<input class="toggle-one deactivate" type="checkbox" data-id="' + response.data.id + '" data-toggle="toggle" ' + isChecked + ' />' +
                                '</div>' +
                                '</td>' +
                                '</tr>';

                            $tableRow.append(html);
                        }
                        else {
                            $errorContainer.show();
                            $errorMessage.html(response.message);
                        }

                    }
                });
            }

        });

        $('.edit-password-modal-trigger').on('click', function() {
            var $button = $(this);
            var $modal = $('.edit-password-modal');
            var $passwordInput = $modal.find('.new-password-input');
            var $confirmPasswordInput = $modal.find('.confirm-password-input');
            var $errorContainer = $modal.find('.profile-change-prompt');
            $modal.modal({
                onShow: function(){
                    $passwordInput.val('');
                    $confirmPasswordInput.val('');
                    $errorContainer.hide();
                },
                onApprove: function(){
                    var $submitButton = $modal.find('.confirm');
                    console.log($submitButton);
                    var password = $passwordInput.val();
                    var confirmPassword = $confirmPasswordInput.val();
                    var csrfToken = $("meta[name=csrf-token]").attr("content");
                    var errorMessage = "";
                    var hasError = false;

                    if(password == ""){
                        errorMessage = 'Password is required';
                        hasError = true;
                    }
                    else if(password !== confirmPassword){
                        errorMessage = 'Password must match';
                        hasError = true;
                    }

                    if(hasError){
                        $errorContainer.find('.message-box').html(errorMessage);
                        $errorContainer.show();
                    }
                    
                    var adminId = parseInt($button.attr('data-admin-id'));
                    $.ajax({
                        url: Routing.generate('admin_account_password_change'),
                        data: {
                            adminId: adminId,
                            password: password,
                            confirmPassword: confirmPassword,
                            _token: csrfToken
                        },
                        method: 'POST',
                        dataType: 'json',
                        beforeSend: function () {
                            $submitButton.addClass('disabled');
                            $errorContainer.hide();
                        },
                        success: function (response) {
                            if(response.isSuccessful){
                                var $sucessModal = $('#modal-message-container');
                                $sucessModal.find('.detail-content').html(response.message);
                                $sucessModal.modal('show');
                                $('#registerBtn').text('Register');
                            }
                            else{
                                $errorContainer.find('.message-box').html(response.message);
                                $errorContainer.show();
                            }
                        }
                    });

                    return false;


                },
            }).modal('show');
        });

        $('#adminUserRow').on('click', '.tableRow', function () {
            $('#registerBtn').text('Update');
            var $this= $(this);
            var data = JSON.parse($this.attr('data'));
            var $username = $('#username');
            var $password = $('#password');
            var $confirmPassword = $('#confirmPassword');
            var $firstName = $('#firstName');
            var $lastName = $('#lastName');
            var $userRole = $('#userRole');

            $('#errorContainer').hide();
            $username.val(data.username).attr('disabled', true);
            $firstName.val(data.firstName);
            $lastName.val(data.lastName);
            $userRole.dropdown('set selected', data.adminRoleId);
            $password.val('password').attr('disabled', true);
            $confirmPassword.val('password').attr('disabled', true);

            var $editPasswordButton = $('.password-container .edit-password-modal-trigger');
            $editPasswordButton.attr('data-admin-id', data.id)
                               .show();
            $('.confirm-password-container').fadeOut();

            $('#registerBtn').attr('data-id', data.id);
        });


        $('#adminUserRow').on('click', '.deactivate', function () {
            var $this = $(this);
            var id = $this.attr('data-id');
            var isActive = $this.is(':checked');

            $.ajax({
                url: Routing.generate('admin_account_deactivate'),
                data: {
                    id: id,
                    isActive: isActive
                },
                method: 'POST',
                dataType: 'json',
                success: function (response) {

                    if (response.isSuccessful) {
                        var message = 'Account successfully Deactivated.';

                        if (isActive === true) {
                            message = 'Account successfully Activated.';
                        }

                        $messageModal.find('.header-content').html(message);
                        $messageModal.find('.detail-content').html();
                        $messageModal.modal('show');
                    }

                }

            });
        });

    });

})(jQuery);
