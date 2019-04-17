/* Dashicons Picker */

(function($) {

	$.fn.dashiconsPicker = function( options ) {

		var icons = [
			"menu",
			"admin-site",
			"dashboard",
			"admin-post",
			"admin-media",
			"admin-links",
			"admin-page",
			"admin-comments",
			"admin-appearance",
			"admin-plugins",
			"admin-users",
			"admin-tools",
			"admin-settings",
			"admin-network",
			"admin-home",
			"admin-generic",
			"admin-collapse",
			"welcome-write-blog",
			"welcome-add-page",
			"welcome-view-site",
			"welcome-widgets-menus",
			"welcome-comments",
			"welcome-learn-more",
			"format-aside",
			"format-image",
			"format-gallery",
			"format-video",
			"format-status",
			"format-quote",
			"format-links",
			"format-chat",
			"format-audio",
			"camera",
			"images-alt",
			"images-alt2",
			"video-alt",
			"video-alt2",
			"video-alt3",
			"media-archive",
			"media-audio",
			"media-code",
			"media-default",
			"media-document",
			"media-interactive",
			"media-spreadsheet",
			"media-text",
			"media-video",
			"playlist-audio",
			"playlist-video",
			"controls-play",
			"controls-pause",
			"controls-forward",
			"controls-skipforward",
			"controls-back",
			"controls-skipback",
			"controls-repeat",
			"controls-volumeon",
			"controls-volumeoff",
			"image-crop",
			"image-rotate-left",
			"image-rotate-right",
			"image-flip-vertical",
			"image-flip-horizontal",
			"undo",
			"redo",
			"editor-bold",
			"editor-italic",
			"editor-ul",
			"editor-ol",
			"editor-quote",
			"editor-alignleft",
			"editor-aligncenter",
			"editor-alignright",
			"editor-insertmore",
			"editor-spellcheck",
			"editor-expand",
			"editor-contract",
			"editor-kitchensink",
			"editor-underline",
			"editor-justify",
			"editor-textcolor",
			"editor-paste-word",
			"editor-paste-text",
			"editor-removeformatting",
			"editor-video",
			"editor-customchar",
			"editor-outdent",
			"editor-indent",
			"editor-help",
			"editor-strikethrough",
			"editor-unlink",
			"editor-rtl",
			"editor-break",
			"editor-code",
			"editor-paragraph",
			"align-left",
			"align-right",
			"align-center",
			"align-none",
			"lock",
			"calendar",
			"calendar-alt",
			"visibility",
			"post-status",
			"edit",
			"trash",
			"external",
			"arrow-up",
			"arrow-down",
			"arrow-right",
			"arrow-left",
			"arrow-up-alt",
			"arrow-down-alt",
			"arrow-right-alt",
			"arrow-left-alt",
			"arrow-up-alt2",
			"arrow-down-alt2",
			"arrow-right-alt2",
			"arrow-left-alt2",
			"sort",
			"leftright",
			"randomize",
			"list-view",
			"exerpt-view",
			"grid-view",
			"share",
			"share-alt",
			"share-alt2",
			"twitter",
			"rss",
			"email",
			"email-alt",
			"facebook",
			"facebook-alt",
			"googleplus",
			"networking",
			"hammer",
			"art",
			"migrate",
			"performance",
			"universal-access",
			"universal-access-alt",
			"tickets",
			"nametag",
			"clipboard",
			"heart",
			"megaphone",
			"schedule",
			"wordpress",
			"wordpress-alt",
			"pressthis",
			"update",
			"screenoptions",
			"info",
			"cart",
			"feedback",
			"cloud",
			"translation",
			"tag",
			"category",
			"archive",
			"tagcloud",
			"text",
			"yes",
			"no",
			"no-alt",
			"plus",
			"plus-alt",
			"minus",
			"dismiss",
			"marker",
			"star-filled",
			"star-half",
			"star-empty",
			"flag",
			"location",
			"location-alt",
			"vault",
			"shield",
			"shield-alt",
			"sos",
			"search",
			"slides",
			"analytics",
			"chart-pie",
			"chart-bar",
			"chart-line",
			"chart-area",
			"groups",
			"businessman",
			"id",
			"id-alt",
			"products",
			"awards",
			"forms",
			"testimonial",
			"portfolio",
			"book",
			"book-alt",
			"download",
			"upload",
			"backup",
			"clock",
			"lightbulb",
			"microphone",
			"desktop",
			"tablet",
			"smartphone",
			"phone",
			"index-card",
			"carrot",
			"building",
			"store",
			"album",
			"palmtree",
			"tickets-alt",
			"money",
			"smiley"
			];

		return this.each( function() {

			var $button = $(this);

			$button.on('click.dashiconsPicker', function() {
				createPopup($button);
			});

			function createPopup($button) {

				$target = $($button.data('target'));
				current = $target.val().replace('dashicons-','');

				$popup = $('<div class="dashicon-picker-container"> \
						<div class="dashicon-picker-control" /> \
						<ul class="dashicon-picker-list" /> \
					</div>')
					.css({
						'top': $button.offset().top,
						'left': $button.offset().left
					});

				var $list = $popup.find('.dashicon-picker-list');
				var active = '';
				var page = 0;
				for (var i in icons) {
					if (icons[i] == current)
					{
						active = ' active';
						page = i;
					}
					else
					{
						active = '';
						page = 0;
					}
					$list.append('<li data-icon="'+icons[i]+'" class="icon'+active+'"><a href="#" title="'+icons[i]+'"><span class="dashicons dashicons-'+icons[i]+'"></span></a></li>');
				};

				$('a', $list).click(function(e) {
					e.preventDefault();
					var title = $(this).attr("title");
					$target.val("dashicons-"+title);
					removePopup();
				});

				var $control = $popup.find('.dashicon-picker-control');
				$control.html('<a data-direction="back" href="#"><span class="dashicons dashicons-arrow-left-alt2"></span></a> \
				<input type="text" class="" placeholder="Search" /> \
				<a data-direction="forward" href="#"><span class="dashicons dashicons-arrow-right-alt2"></span></a>');

				$('a', $control).click(function(e) {
					e.preventDefault();
					if ($(this).data('direction') === 'back') {
						//move last 25 elements to front
						$('li:gt(' + (icons.length - 26) + ')', $list).each(function() {
							$(this).prependTo($list);
						});
					} else {
						//move first 25 elements to the end
						$('li:lt(25)', $list).each(function() {
							$(this).appendTo($list);
						});
					}
				});

				$popup.appendTo('body').show();

				$('input', $control).on('keyup', function(e) {
					var search = $(this).val();
					if (search === '') {
						//show all again
						$('li:lt(25)', $list).show();
					} else {
						$('li', $list).each(function() {
							if ($(this).data('icon').toLowerCase().indexOf(search.toLowerCase()) !== -1) {
								$(this).show();
							} else {
								$(this).hide();
							}
						});
					}
				});

				$(document).mouseup(function (e){
					if (!$popup.is(e.target) && $popup.has(e.target).length === 0) {
						removePopup();
					}
				});
			}

			function removePopup(){
				$(".dashicon-picker-container").remove();
			}
		});
	}

	$(function() {
		$('.dashicons-picker').dashiconsPicker();
	});

}(jQuery));
