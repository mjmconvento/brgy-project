var brgy = {
	delete_element : '',
	init : function() {
		$('.delete-brgy').on('click', function() {
			$('#delete_modal').modal('show');
			brgy.delete_element = $(this).parent().find('.delete-hidden-brgy');
		});

		$('.delete-confirm').on('click', function() {
			$(brgy.delete_element).trigger('click');
		});
	}	
}

brgy.init();
