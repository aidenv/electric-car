(function($) {
    'use strict';

    var url = window.location.href;

    if ($('#referral-code').length > 0) {
        url = window.location.href + '?referralCode=' + $('#referral-code').val().trim();
    }

    var 
        defaultOptions = {
            url: url,
            originalRefer: url,
            text: '',
            via: 'Yilinker',
            related: '',
            hashtags: '',
            lang: '',

        },
        $twitterShares = $('[data-twitter-share]'),
        TWEET_URL = "https://twitter.com/intent/tweet"
    ;

    $twitterShares.each(function() {
        var 
            $twitterShare = $(this),
            options = $twitterShare.data('twitter-share')
        ;

        options = $.extend({}, defaultOptions, options);
        var query = $.param(options);

        $twitterShare.on('click', function() {
            var 
                location = TWEET_URL+'?'+query,
                w = 550,
                h = 420,
                left = (screen.width/2)-(w/2),
                top = (screen.height/2)-(h/2),
                share_win = window.open(location, 'sharewin','left='+left+',top='+top+',width='+w+',height='+h+',toolbar=1,resizable=0')
            ;
        });
    });

})(jQuery);