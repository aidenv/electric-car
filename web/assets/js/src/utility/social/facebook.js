(function($) {
    var url = window.location.href;

    if ($('#referral-code').length > 0) {
        url = window.location.href + '?referralCode=' + $('#referral-code').val().trim();
    }

    'use strict';

    var $fbShares = $('[data-fb-share]');

    $.ajaxSetup({ cache: true });
    var onReady = function(){
        var data = $fbShares.data('fb-share');
        FB.init({
            appId: data.appId,
            version: 'v2.4'
        });

        $fbShares.each(function() {
            var $fbShare = $(this),
                selfData = $fbShare.data('fb-share');
            $fbShare.on('click', function() {
                if (typeof selfData.customUrl !== 'undefined') {
                    url = selfData.customUrl;
                }

                FB.ui({
                    method: 'share',
                    href: url
                }, function(response){});
            });
        });
    };

    $.getScript('//connect.facebook.net/en_US/sdk.js', onReady);

})(jQuery);