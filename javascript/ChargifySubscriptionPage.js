;(function($) {
	$('a.chargifyDialog').click(function() {
		var anchor = $(this);
		var text   = anchor.text();
		var href   = anchor.text('Loading...').attr('href');
		var dialog = $('<div></div>').hide().appendTo('body');

		dialog.load(href, function() {
			dialog.dialog({
				title: anchor.attr('title'),
				modal: true,
				resizable: false,
				width: 'auto',
				height: 'auto'
			});
			anchor.text(text);
		});

		return false;
	});
})(jQuery);