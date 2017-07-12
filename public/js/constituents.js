var constituents = {
	delete_element : '',
	init : function() {
		$('.delete-cons').on('click', function() {
			$('#delete_modal').modal('show');
			constituents.delete_element = $(this).parent().find('.delete-hidden-cons');
		});

		$('.delete-confirm').on('click', function() {
			$(constituents.delete_element).trigger('click');
		});
	}	
}

constituents.init();
