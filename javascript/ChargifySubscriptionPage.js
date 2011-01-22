;(function($) {
	$('a.chargify-dialog').click(function() {
		var anchor = $(this);
		var text   = anchor.text();
		var href   = anchor.text('Loading...').attr('href');
		var dialog = $('<div></div>').hide().appendTo('body');

		dialog.load(href, function() {
			dialog.dialog({
				title:     anchor.attr('title'),
				modal:     true,
				resizable: false,
				width:     'auto',
				height:    'auto'
			});
			anchor.text(text);
		});

		return false;
	});

	$('a.chargify-button').hover(
		function() { $(this).addClass('ui-state-hover'); },
		function() { $(this).removeClass('ui-state-hover'); }
	);
	
	$('a.chargify-confirm').click(function() {
		if (!confirm('Are you sure?')) return false;
	});
	
	$('#chargify-show-cancel-link').click(function() {
		$('#chargify-show-cancel').hide();
		$('#chargify-do-cancel').show();
		return false;
	});

	$('#chargify-cancel-changed-mind').click(function() {
		$('#chargify-show-cancel').show();
		$('#chargify-do-cancel').hide();
		return false;
	});

	$('#chargify-do-cancel-link').click(function() {
		var msg =
			'Are you sure you want to cancel your subscription? You ' +
			'can re-active it later by visiting this page again.';

		if (!confirm(msg)) {
			return false;
		}
	});
})(jQuery);