(function($) {

    /**
     * Marked for refactor:
     *
     * 1. The content of this js file are very unrelated.
     * 2. This file shouldn't be in js/vendors/
     * 3. This file doesn't really have anything to do with forms
     */
	$(".form-ui-checkbox label input[type='checkbox']").css('visibility', 'hidden');

	$('head').append('<style>.form-ui-checkbox label:before{display:inline-block !important;}</style>');

	$(".form-ui-checkbox label, .form-ui-checkbox label input[type='checkbox'], .form-ui-checkbox input[type='checkbox']").click(function(){
		$(this).parents(".form-ui-checkbox").toggleClass("checked");
	});

})( jQuery );
