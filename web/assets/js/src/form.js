(function ($) {
	//Limited character textarea
    var getMaxLengthValue = $(".textarea-limited-character").attr("maxLength");
    var maxLength = parseInt(getMaxLengthValue);
    $('.textarea-limited-character-length').text(maxLength);
    $('.textarea-limited-character').keyup(function() {
        var length = $(this).val().length;
        var length = maxLength-length;
        $('.textarea-limited-character-length').text(length);
    });
})(jQuery);