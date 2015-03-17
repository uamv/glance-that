/**
 * Handles Glance That icon and form interaction
 */
jQuery(document).ready(function( $ ) {

	$(function() {

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

	});


	$('#visible-icon').click(
		function() {
			if( $('#iconlist').is(':visible') ) {
				$('#iconlist').hide();
			} else {
				$('#iconlist').css('display','block');
				$('#dashboard_right_now .inside').css('overflow','visible');
			}
		});

	$('#gt-item').change(
		function() {
			$gtselection = $(this).find(':selected');

			if ( '' != $gtselection.attr('data-dashicon') ) {

				$gticon = $('#iconlist').find('div[data-dashicon="'+$gtselection.attr('data-dashicon')+'"]');

				$('#visible-icon').attr('alt',$gticon.attr('alt'));
				$('#visible-icon').removeClass();
				$('#visible-icon').addClass($gticon.attr('class'));
				$('input[data-dashicon="selected"]').attr('value',$gticon.attr('alt'));
			}

			if ( 'shown' == $gtselection.attr('data-glancing') ) {
				$('#add-gt-item').hide();
				$('#remove-gt-item').show();
			} else if ( 'hidden' == $gtselection.attr('data-glancing') ) {
				$('#add-gt-item').show();
				$('#remove-gt-item').hide();
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

	$('#show-gt-form').click(
		function() {
			$('#gt-form').css('display','block');
			$(this).css('display','none');
			});

	$('#remove-gt-item').click(
		function() {
			$('#gt-form').attr('action','index.php?action=remove-gt-item');
		});

});
