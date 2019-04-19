/**
 * Handles Glance That icon and form interaction
 */
jQuery(document).ready(function( $ ) {

	// Hide native glance items
	$('#dashboard_right_now .post-count,#dashboard_right_now .page-count,#dashboard_right_now .comment-count,#dashboard_right_now .comment-mod-count').remove();

	function addSortable() {

	    $( "#dashboard_right_now .inside .main ul" ).sortable({
	      placeholder: "element-holder",
	      forcePlaceholderSize: true,
	      containment: 'parent',
	      cursor: 'move',
	      cursorAt: { top: 15, left: 15 },
	    });

	    $( "#dashboard_right_now .inside .main ul" ).disableSelection();

	    /* Send ajax after sort change */
	    $('#dashboard_right_now .inside .main ul').sortable({
		  update: function(evt, ui) {
		    $.post(Glance.ajaxurl, {
		      action: 'sort_glances',
		      gt_sort: $('#dashboard_right_now .inside .main ul').sortable('toArray'),
		      userID: $('#gt-form').data('userid'),
			}, function (response) {

			});
		  }
		});

	};

	$( addSortable );

	$(function() {

		$('#gt-form').submit(function(event) {
		    event.preventDefault();

			$.post(Glance.ajaxurl, {
				action: 'add_remove_glance',
				gt_action: $('#submit-gt-item').val()+'_gt_item',
				gt_item: $('#gt-item').val(),
				gt_item_icon: $('#gt-item-icon').val(),
				userID: $('gt-form').data('userid'),
			}, function (response) {

				if ( response.success ) {

					var glance_list = $('#dashboard_right_now ul.ui-sortable').empty();

					$.each(response.elements, function( intIndex, e ){
						glance_list.append($('<li class="ui-sortable-handle">'+e+'</li>'));
					});
					glance_list.append($('<li style="display:none;"></li>'));

					var gtitems = $('#dashboard_right_now li:not(\'.post-count,.page-count,.comment-count\')').each(function(index){
						if ( $(this).find('.gt-item').hasClass('unordered') ) {
							var order = $(this).find('.gt-item').attr('data-order');
							$(this).attr('id',order);
							$(this).find('.gt-item').removeClass('unordered');
						}
					});

					var gtoption = $('#gt-item').find('option[value="'+response.glance+'"]');

					if ( 'shown' == gtoption.attr('data-glancing') ) {
						gtoption.attr('data-glancing','hidden');
					} else if ( 'hidden' == gtoption.attr('data-glancing') ) {
						gtoption.attr('data-glancing','shown');
					}

					$('#gt-item').val('');

					$('#visible-icon').attr('alt','f159');
					$('#visible-icon').removeClass();
					$('#visible-icon').addClass('dashicon dashicons-marker');
					$('input[data-dashicon="selected"]').attr('value','f159');

					$('.gt-message').remove();
					$('#wpbody-content .wrap > h1').after(response.notice);

				}

			});
		});

	});

	$('#gt-item').change(
		function() {
			$gtselection = $(this).find(':selected');
			$gticoncode = $gtselection.attr('data-dashicon');

			if ( 'formidableform' == $gticoncode
				|| 'gravityform' == $gticoncode
				|| 'gravityview' == $gticoncode
			 	|| 'give' == $gticoncode
			 	|| 'sliced' == $gticoncode
		 		|| 'sliced' == $gticoncode ) {

				// $gticon = $('#iconlist').find('div[data-dashicon="'+$gticoncode+'"]');

				$('#visible-icon').attr('alt',$gticoncode);
				$('#visible-icon').removeClass();
				$('#visible-icon').addClass($gticoncode);
				$('input[data-dashicon="selected"]').attr('value',$gticoncode);

			} else if ( '' != $gticoncode ) {

				$('#visible-icon').attr('alt',$gticoncode);
				$('#visible-icon').removeClass();
				$('#visible-icon').addClass('dashicon dashicons-'+gticons[$gticoncode]); // FIX THIS!!!!!!!!!!!!!!!!!!!!
				$('input[data-dashicon="selected"]').attr('value',$gticoncode);
			}

			$('#submit-gt-item').show();

			if ( 'shown' == $gtselection.attr('data-glancing') ) {
				$('#submit-gt-item').val('Remove');
			} else if ( 'hidden' == $gtselection.attr('data-glancing') ) {
				$('#submit-gt-item').val('Add');
			}

		});

	/* Dashicons Picker */
	$.fn.dashiconsPicker = function( options ) {

		return this.each( function() {

			var $button = $(this);

			$button.on('click.dashiconsPicker', function() {
				if ( 'formidableform' != $('#gt-item').find(':selected').val()
							&& 'gravityform' != $('#gt-item').find(':selected').val()
							&& 'gravityview' != $('#gt-item').find(':selected').val()
							&& 'give_forms' != $('#gt-item').find(':selected').val()
							&& 'sliced_quote' != $('#gt-item').find(':selected').val()
							&& 'sliced_invoice' != $('#gt-item').find(':selected').val() ) {
					createPopup($button);
				}
			});

			function createPopup($button) {

				$target = $('#gt-item-icon');
				current = $target.val();

				$popup = $('<div class="dashicon-picker-container"> \
						<div class="dashicon-picker-control" /> \
						<ul class="dashicon-picker-list" /> \
					</div>')
					.css({
						'top': $button.offset().top + 32,
						'left': $button.offset().left
					});

				var $list = $popup.find('.dashicon-picker-list');
				var active = '';
				// var page = 0;
				for (var i in gticons) {
					if (i == current)
					{
						active = ' active';
						// page = i;
					}
					else
					{
						active = '';
						// page = 0;
					}
					$list.append('<li data-icon="'+gticons[i]+'" class="icon'+active+'"><a href="#" title="'+gticons[i]+'" data-code="'+i+'"><span class="dashicons dashicons-'+gticons[i]+'"></span></a></li>');
				};

				$('a', $list).click(function(e) {
					e.preventDefault();
					var code = $(this).data('code');
					var title = $(this).attr('title');
					$target.val(code);
					$('#visible-icon').attr('alt',code);
					$('#visible-icon').removeClass().addClass('dashicon dashicons-'+title+' dashicons-picker');
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
						$('li:gt(' + (gticons.length - 26) + ')', $list).each(function() {
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

});
