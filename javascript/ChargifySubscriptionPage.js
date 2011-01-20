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

	$('a.showCancelLink').click(function() {
		$('p.showCancelLink').hide();
		$('p.cancelLink').show();
		return false;
	});

	$('a.changedMind').click(function() {
		$('p.showCancelLink').show();
		$('p.cancelLink').hide();
		return false;
	});

	$('a.cancelLink').click(function() {
		var msg = 'Are you sure you want to cancel your subscription? You ' +
			'can re-active it later by visiting this page again.';

		if (!confirm(msg)) {
			return false;
		}
	});
})(jQuery);