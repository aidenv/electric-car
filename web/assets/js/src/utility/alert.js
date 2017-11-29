(function(jQuery) {
    'use strict';

    var $body = $('body');
    var $html = $(
        '<div class="ui modal tiny alert-js-modal">'+
            '<a class="close"><i class="icon-times"></i></a>'+
            '<div class="content align-center">'+
                '<h4 class="ui header">'+
                    '<i data-alert-icon class="icon icon-circle-check"></i>'+
                    '<div class="content">'+
                        '<div class="sub-header" data-message>'+
                        '</div>'+
                    '</div>'+
                '</h4>'+
            '</div>'+
        '</div>'
    );
    $body.prepend($html);

    $body.on('alert.show', function(evt, message) {
        $html.find('[data-message]').text(message);
        $html.modal('show');
    });

    $body.on('alert.success', function(evt, message) {
        $html.find('[data-alert-icon]').removeClass('icon-circle-times');
        $html.find('[data-alert-icon]').addClass('icon-circle-check');
        $html.find('[data-message]').text(message);
        $html.modal('show');
    });

    $body.on('alert.error', function(evt, message) {
        $html.find('[data-alert-icon]').removeClass('icon-circle-check');
        $html.find('[data-alert-icon]').addClass('icon-circle-times');
        $html.find('[data-message]').text(message);
        $html.modal('show');
    });
})($);