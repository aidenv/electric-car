if ($("#success-default-modal").length <= 0) {
    console.error("Can't find element '#success-default-modal'");
}
else {
    var showDefaultModal = function(args) {
            var defaultModal = $("#success-default-modal"),
                defaultSetting = {
                    message: '',
                    reload: true,
                    type: 'success',
                    callbacks: {
                        onHide: function() {
                            if (defaultSetting.reload == true) {
                                window.location.reload(true);
                            }
                        }
                    }
                },
                types = {
                    error: 'icon-alert-exclamation',
                    success: 'icon-check-circle',
                    trash: 'icon-trash',
                    active: 'icon-active',
                    inactive: 'icon-inactive'
                },
                icon = types.success;

            if (typeof args == 'undefined') {
                args = {};
            }

            if (typeof defaultSetting.callbacks.onHide == 'undefined') {
                defaultSetting.callbacks.onHide = function() {
                    if (defaultSetting.reload == true) {
                        window.location.reload();
                    }
                }
            }

            $.extend(defaultSetting, args);

            if (typeof types[defaultSetting.type] != 'undefined') {
                icon = types[defaultSetting.type];
            }

            defaultModal.find('i.icon').addClass(icon);

            defaultModal.find("#success-default-modal-action-message").html(defaultSetting.message);

            defaultModal.modal('show').modal(defaultSetting.callbacks);
        },
        showConfirmModal = function(args) {
            var confirmModal = $("#confirm-default-modal"),
                defaultSetting = {
                    message: '',
                    callbacks: {},
                    labels: {
                        approve: 'Okay',
                        deny: 'Cancel'
                    }
                };

                this.loadApproveButton = function(state)
                {
                    var buttonContainer = confirmModal.find('div.actions'),
                        buttons = buttonContainer.find('button'),
                        approveButton = buttonContainer.find('button.approve'),
                        text = approveButton.find('span'),
                        loader = approveButton.find('div');

                    if (typeof state == 'undefined' || state) {
                        buttons.attr('disabled', true);
                        loader.show();
                        text.hide();
                    }
                    else {
                        buttons.attr('disabled', false);
                        loader.hide();
                        text.show();
                    }
                }

            if (typeof args == 'undefined') {
                args = {};
            }

            $.extend(defaultSetting, args);

            confirmModal.find("button.approve > span.text").html(defaultSetting.labels.approve);
            confirmModal.find("button.cancel").html(defaultSetting.labels.deny);

            confirmModal.find("#confirm-default-modal-action-message").html(defaultSetting.message);

            confirmModal.modal('show').modal(defaultSetting.callbacks);
        }
}

