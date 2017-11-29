(function ($) {

    $(".feedback-list tr:not(:first-child)").click(function(e) {
        var mobilefeedback =  new MobileFeedBack();
        mobilefeedback.details($(this).data('id'));
    });

    var MobileFeedBack = function() {

        'strict';

        var details = function(id) {

            var params = {id: id};

            $.get('/feedback/details', params)
                .done(function( data ) {

                    var $feedbackdetail = $('#feedback-details');
                    
                    $feedbackdetail.remove();
                    $(data).appendTo(document.body);
                    
                    $('#feedback-details').modal('show');
                    $('.feedback-list tr[data-id='+ params.id +']').addClass('read');
                    
                });
        }

        var response = {
            'details' : details
        }

        return response;
    }

})(jQuery);
    
