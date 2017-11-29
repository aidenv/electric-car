$(document).ready(function() {
    $(window).on('load resize', function () {
        var WindowHeight = $(window).height();
        $('.category-content').css({'min-height': WindowHeight+'px'});
    });
    
    $('.category-container nav li:not(:first)').addClass('inactive');
    $('.category-content').hide();
    $('.category-content:first').show();
    $('.category-content:first div').show();
        
    $('.category-container nav li').click(function(){
        var t = $(this).attr('rel');
        if($(this).hasClass('inactive')){
            $('.category-container nav li').addClass('inactive');
            $(this).removeClass('inactive');
            $('.category-content').hide();
            $('.category-content div').hide();
            $('#'+ t).show();
            $('#'+ t).children('div').fadeIn('slow');
        }
    });
});