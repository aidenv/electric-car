(function($) {
    'use strict';

    var $sendOTP = $('[data-send-otp]'),
        $body = $('body');

    if ($sendOTP.length > 0) {
        
        var url = Routing.generate('core_send_token');

        $sendOTP.each(function() {
            var $elem = $(this);
            var loaderTarget = $elem.data('loader');
            loaderTarget = loaderTarget ? loaderTarget: 'body';
            var $loader = $(loaderTarget);
            $loader.yiLoader();
            var type = $elem.data('send-otp');

            $elem.on('click', function(evt) {

                if($elem.hasClass('hasFormError')){
                    return false
                }
                
                var contactNumber = $elem.data('contact-number');
                var $contactNumber = $(contactNumber);
                if ($contactNumber.length) {
                    contactNumber = $contactNumber.val();
                }
                
                evt.preventDefault();
                $.ajax({
                    url: url,
                    method: 'POST',
                    beforeSend: function() {
                        $loader.trigger('loader.start');
                        $elem.trigger('otp.start');
                    },
                    data: {
                        type: type,
                        contactNumber: contactNumber
                    },
                    success: function() {
                        $body.trigger('alert.success', 'Confirmation Code successfully sent.');
                    },
                    error: function (xhr) {
                        var jsonResponse = $.parseJSON(xhr.responseText);                        
                        $body.trigger('alert.error', jsonResponse.message);
                    },
                    complete: function(xhr) {
                        $loader.trigger('loader.stop')
                        $elem.trigger('otp.stop', {
                            status: xhr.status,
                            response: xhr.responseText
                        });
                    }
                });
            });
        });
    }

})(jQuery);
