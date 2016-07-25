/**
 * Site menu toggle
 */
define(function(require) {
	$ = require('jquery');

	$(document).on('click', '.elgg-menu-item-site-menu-toggle', function(e) {
		e.preventDefault();

		var menu = $('.elgg-menu-site');

		if (menu.width() == 0) {
			var newWidth = "100%";
			var overflow = 'scroll';
		} else {
			var newWidth = "0";
			var overflow = 'hidden';
		}

		$('.elgg-menu-site').css('overflow-y', overflow).animate({
			width: newWidth,
		}, "fast");
	});

	/**
	 * Toggle site submenus
	 */
	$(document).on('click', '.elgg-menu-site .elgg-menu-parent', function(event) {
		$(this).siblings().slideToggle('medium');
		$(this).toggleClass('elgg-menu-closed elgg-menu-opened');
		event.preventDefault();
	});
});
