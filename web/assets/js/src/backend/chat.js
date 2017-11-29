//Collapse Menu Control

jQuery(document).ready(function(){
	var accordionsMenu = $('.cd-accordion-menu');

	if( accordionsMenu.length > 0 ) {

		accordionsMenu.each(function(){
			var accordion = $(this);
			//detect change in the input[type="checkbox"] value
			accordion.on('change', 'input[type="checkbox"]', function(){
				var checkbox = $(this);
				console.log(checkbox.prop('checked'));
				( checkbox.prop('checked') ) ? checkbox.siblings('ul').attr('style', 'display:none;').addClass('change-text').slideDown(300) : checkbox.siblings('ul').attr('style', 'display:block;').removeClass('change-text').slideUp(300);

			});
		});
	}

	$('.chatmate-list li').click(function(){
	  $('.chatmate-list li').removeClass('active');
	  $(this).addClass('active');
	});

	$('.prev').click(function() {
	  $(this).toggleClass('-off -on');
	});

});
