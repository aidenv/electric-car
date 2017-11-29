(function($) {
    'use strict';

    var url = window.location.href;

    if ($('#referral-code').length > 0) {
        url = window.location.href + '?referralCode=' + $('#referral-code').val().trim();
    }

    var 
        $googleShares = $('[data-google-share]'),
        data = $googleShares.data('google-share'),
        SHARE_URL = 'https://plus.google.com/share'
    ;

    $googleShares.each(function() {
        var $googleShare = $(this),
            selfData = $googleShare.data('google-share');
        $googleShare.on('click', function() {
            if (typeof selfData.customUrl !== 'undefined') {
                url = selfData.customUrl;
            }

            var location = SHARE_URL+'?url='+ url,
                w = 550,
                h = 420,
                left = (screen.width/2)-(w/2),
                top = (screen.height/2)-(h/2),
                share_win = window.open(location, 'sharewin','left='+left+',top='+top+',width='+w+',height='+h+',toolbar=1,resizable=0')
            ;

        });
    });

})(jQuery);