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
	      cancel: '#dashboard_right_now .inside .main ul li:last-child',
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


	$('#visible-icon').click(
		function() {
			if( $('#iconlist').is(':visible') ) {
				$('#iconlist').hide();
			} else if ( 'formidableform' != $('#gt-item').find(':selected').val()
						&& 'gravityform' != $('#gt-item').find(':selected').val()
						&& 'give_forms' != $('#gt-item').find(':selected').val() ) {
				$('#iconlist').css('display','block');
				$('#dashboard_right_now .inside').css('overflow','visible');
			}
		});

	$('#gt-item').change(
		function() {
			$gtselection = $(this).find(':selected');

			if ( 'formidableform' == $gtselection.attr('data-dashicon')
				 || 'gravityform' == $gtselection.attr('data-dashicon')
			 	 || 'give' == $gtselection.attr('data-dashicon') ) {

				$gticon = $('#iconlist').find('div[data-dashicon="'+$gtselection.attr('data-dashicon')+'"]');

				$('#visible-icon').attr('alt',$gtselection.attr('data-dashicon'));
				$('#visible-icon').removeClass();
				$('#visible-icon').addClass($gtselection.attr('data-dashicon'));
				$('input[data-dashicon="selected"]').attr('value',$gtselection.attr('data-dashicon'));

			} else if ( '' != $gtselection.attr('data-dashicon') ) {

				$gticon = $('#iconlist').find('div[data-dashicon="'+$gtselection.attr('data-dashicon')+'"]');

				$('#visible-icon').attr('alt',$gticon.attr('alt'));
				$('#visible-icon').removeClass();
				$('#visible-icon').addClass($gticon.attr('class'));
				$('input[data-dashicon="selected"]').attr('value',$gticon.attr('alt'));
			}

			$('#submit-gt-item').show();

			if ( 'shown' == $gtselection.attr('data-glancing') ) {
				$('#submit-gt-item').val('Remove');
			} else if ( 'hidden' == $gtselection.attr('data-glancing') ) {
				$('#submit-gt-item').val('Add');
			}

		});

	$('.dashicon-option').click(
		function() {
			$('#visible-icon').attr('alt',$(this).attr('alt'));
			$('#visible-icon').removeClass();
			$('#visible-icon').addClass($(this).attr('class'));
			$('input[data-dashicon="selected"]').attr('value',$(this).attr('alt'));
			$('#iconlist').hide();
		});

});
