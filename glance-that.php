<?php
/**
 * Plugin Name: Glance That
 * Plugin URI: http://typewheel.xyz/
 * Description: Adds content control to At a Glance on the Dashboard
 * Version: 4.5
 * Author: Typewheel
 * Author URI: http://typewheel.xyz
 *
 * The Glance That plugin was created to extend At A Glance.
 *
 * This program is free software; you can redistribute it and/or modify it under the terms of the GNU
 * General Public License version 2, as published by the Free Software Foundation.  You may NOT assume
 * that you can use any other version of the GPL.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without
 * even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 * @package Glance That
 * @version 4.5
 * @author uamv
 * @copyright Copyright (c) 2013-2019, uamv
 * @link http://typewheel.xyz/
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 */

/**
 * Define plugins globals.
 */

define( 'GT_VERSION', '4.5' );
define( 'GT_DIR_PATH', plugin_dir_path( __FILE__ ) );
define( 'GT_DIR_URL', plugin_dir_url( __FILE__ ) );

// Set a capability required for editing of one's glances and admining all glances
! defined( 'GT_EDIT_GLANCES' ) ? define( 'GT_EDIT_GLANCES', 'read' ) : FALSE;
! defined( 'GT_ADMIN_GLANCES' ) ? define( 'GT_ADMIN_GLANCES', 'edit_dashboard' ) : FALSE;

/**
 * Get instance of class if in admin.
 */

add_action( 'plugins_loaded', function() {

	global $pagenow;

	if ( is_admin() && ( 'index.php' == $pagenow || 'admin-ajax.php' == $pagenow ) ) {
		Glance_That::get_instance();
	}

}, 10 );

/**
 * Glance That Class
 *
 * Extends functionality of the Dashboard's At a Glance metabox
 *
 * @package Glance That
 * @author  UaMV
 */
class Glance_That {

	/*---------------------------------------------------------------------------------*
	 * Attributes
	 *---------------------------------------------------------------------------------*/

	/**
	 * Instance of this class.
	 *
	 * @var      object
	 */
	protected static $instance = null;

	/**
	 * glances.
	 *
	 * @var      array
	 */
	protected $glances;

	/**
	 * notices.
	 *
	 * @var      array
	 */
	protected $notices;

	/**
	 * glances.
	 *
	 * @var      array
	 */
	protected $glances_indexed;

	/**
	 * status_visibility.
	 *
	 * @var      array
	 */
	protected $status_visibility;

	/**
	 * info_visibility.
	 *
	 * @var      array
	 */
	protected $info_visibility;

	/**
	 * icons.
	 *
	 * @var      array
	 */
	protected $icons;

	/**
	 * options
	 *
	 * @var      array
	 */
	protected $options;

	/**
	 * editable
	 *
	 * @var      array
	 */
	protected $editable;

	/**
	 * editable
	 *
	 * @var      array
	 */
	protected $adminable;

	/*---------------------------------------------------------------------------------*
	 * Consturctor
	 *---------------------------------------------------------------------------------*/

	/**
	 * Initialize the plugin by setting localization, filters, and administration functions.
	 */
	private function __construct() {

		$this->icons = $this->get_icons();
		$this->options = array(
			'show_zero_count' => apply_filters( 'gt_show_zero_count', true ),
			'show_mine' => apply_filters( 'gt_show_mine', false ),
			'show_zero_count_status' => apply_filters( 'gt_show_zero_count_status', false ),
			'show_add_new' => apply_filters( 'gt_show_add_new', true ),
			'show_all_status' => apply_filters( 'gt_show_all_status', true ),
			'show_settings' => apply_filters( 'gt_show_settings', true ),
			'show_mustuse' => apply_filters( 'gt_show_mustuse', false ),
			'show_dropins' => apply_filters( 'gt_show_dropins', false ),
			'show_archive' => apply_filters( 'gt_show_archive', true ),
			'edit_glances' => apply_filters( 'gt_edit_glances', GT_EDIT_GLANCES ),
			'admin_glances' => apply_filters( 'gt_admin_glances', GT_ADMIN_GLANCES ),
		);

		add_action( 'plugins_loaded', array( $this, 'check_user_cap' ), 20 );

		// Process the form
		add_action( 'init', array( $this, 'get_users_glances' ) );
		add_action( 'init', array( $this, 'get_user_status_visibility' ) );
		add_action( 'init', array( $this, 'get_user_info_visibility' ) );

		// Load the administrative Stylesheets and JavaScript
		add_action( 'admin_enqueue_scripts', array( $this, 'add_stylesheets_and_javascript' ) );

		// Add custom post types to end of At A Glance table
		add_filter( 'dashboard_glance_items', array( $this, 'customize_items' ), 10, 1 );

		// Account for icons selected via Post State Tags plugin
		add_action( 'admin_head', array( $this, 'check_override_status_icons' ) );

		// Add post statuses to native types
		add_action( 'admin_footer', array( $this, 'add_sort_order' ) );

		// Define javascript variable for available dashicons
		add_action( 'admin_footer', array( $this, 'add_dashicon_var' ) );

		// Add post status visibility control
		add_action( 'admin_footer', array( $this, 'settings_control' ) );

		// Filter post type available in drop down to account for certain plugins that add unneccesarily viewable types
		add_filter( 'gt_post_type_selection', array( $this, 'remove_post_type_options' ), 20, 1 );

		// Add form to end of At A Glance
		add_action( 'activity_box_end', array( $this, 'add_form' ) );

		// Add ajax call to modify sort order
		add_action( 'wp_ajax_sort_glances', array( $this, 'sort_glances' ) );

		// Add ajax call to modify sort order
		add_action( 'wp_ajax_default_glances', array( $this, 'default_glances' ) );

		// Add ajax call to toggle visibility
		add_action( 'wp_ajax_toggle_status_visibility', array( $this, 'toggle_status_visibility' ) );
		add_action( 'wp_ajax_toggle_info_visibility', array( $this, 'toggle_info_visibility' ) );

		// Process the form
		add_action( 'wp_ajax_add_remove_glance', array( $this, 'process_form' ) );

		// Set custom labels
		add_filter( 'gt_labels', array( $this, 'customize_labels' ), 10, 3 );

		// Filter post type default icon displayed when drop down option is selected
		add_filter( 'gt_option_icons', array( $this, 'customize_post_type_icon' ), 10, 3 );

		// Modify capability for viewing At a Glance
		add_action( 'wp_dashboard_setup', array( $this, 'at_a_glance' ) );

		// Record date & results of last site health check
		add_action( 'set_transient_health-check-site-status-result', function( $value, $expiration, $transient ) {
			update_option( 'gt_health-check-site-status-result', $value );
			update_option( 'gt_health-check-site-status-date', time() );
		}, 10, 3 );

	} // end constructor

	/*---------------------------------------------------------------------------------*
	 * Public Functions
	 *---------------------------------------------------------------------------------*/

	/**
	 * Return an instance of this class.
	 *
	 * @return    object    A single instance of this class.
	 */
	public static function get_instance() {

		// If the single instance hasn't been set, set it now.
		if ( null == self::$instance ) {
			self::$instance = new self;
		} // end if

		return self::$instance;

	} // end get_instance

	/**
	 * Set user capabilities for the plugin
	 */
	public function check_user_cap() {

		$this->editable = current_user_can( $this->options['edit_glances'] ) ? TRUE : FALSE;
		$this->adminable = current_user_can( $this->options['admin_glances'] ) ? TRUE : FALSE;

	} // end check_user_cap

	/**
	 * Registers the plugin's administrative stylesheets and JavaScript
	 */
	public function add_stylesheets_and_javascript() {

		wp_enqueue_style( 'glance', GT_DIR_URL . 'glance.css', array(), GT_VERSION );
		wp_enqueue_script( 'glance-that', GT_DIR_URL . 'glance.js', array( 'jquery' ), GT_VERSION );
		wp_localize_script( 'glance-that', 'Glance', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) ) );

	} // end add_stylesheets_and_javascript

	/**
	 * Extends capability for those able to view At a Glance
	 */
	function at_a_glance() {

		if ( is_blog_admin() && current_user_can( apply_filters( 'gt_view_at_a_glance', 'edit_posts' ) ) ) {

			wp_add_dashboard_widget( 'dashboard_right_now', __( 'At a Glance' ), 'wp_dashboard_right_now' );

		}

	}

	/**
	 * Adds order to list item for use by sortable
	 */
	public function add_sort_order() { ?>

		<script type="text/javascript" language="javascript">
			jQuery(document).ready(function($) {

				var gtitems = $('#dashboard_right_now li:not(\'.post-count,.page-count,.comment-count\')').each(function(index){
					if ( $(this).find('.gt-item').hasClass('unordered') ) {
						var order = $(this).find('.gt-item').attr('data-order');
						$(this).attr('id',order);
						$(this).find('.gt-item').removeClass('unordered');
					}
				});

			});
		</script>
		<?php

	} // end add_statuses

	/**
	 * Adds order to list item for use by sortable
	 */
	public function add_dashicon_var() { ?>

		<script type="text/javascript" language="javascript">
			var gticons = {
				<?php

					$dashicons = $this->get_dashicons();

					foreach ( $dashicons as $code => $title ) {
						echo $code . ': \'' . $title . '\',';
					}

				?>
			};
		</script>

	<?php } // end add_statuses

	/**
	 * Adds settings control script
	 */
	public function settings_control() {

		if ( $this->options['show_settings'] ) {

			$status_visibility_action = $this->status_visibility == 'visible' ? 'hide' : 'show';
			$status_visibility_style = $this->status_visibility == 'visible' ? 'style="display:none;"' : '';
			$status_hidden_style = $this->status_visibility != 'visible' ? 'style="display:none;"' : '';

			$info_visibility_action = $this->info_visibility == 'visible' ? 'hide' : 'show';
			$info_visibility_style = $this->info_visibility == 'visible' ? 'style="display:none;"' : '';
			$info_hidden_style = $this->info_visibility != 'visible' ? 'style="display:none;"' : '';

			$buttons = '<div id="gt-settings-wrapper">';

			if ( $this->options['show_all_status'] ) {
				$buttons .='<button id="gt-toggle-status" type="button" class="button-link gt-settings" data-action="' . $status_visibility_action . '"><span class="dashicons dashicons-visibility" ' . $status_visibility_style . ' title="Click to Reveal More Glances"></span><span class="dashicons dashicons-hidden" ' . $status_hidden_style . ' title="Click to Hide"></span></button>';
			}

			if ( $this->editable || $this->adminable ) {
				$buttons .= '<button id="gt-toggle-form" type="button" class="button-link gt-settings" data-gt-form="closed"><span class="dashicons dashicons-filter" title="Click to Add or Remove Glances"></span></button>';
			}

			if ( $this->adminable ) {
				$buttons .= '<button id="gt-save-defaults" type="button" class="button-link gt-settings"><span class="dashicons dashicons-migrate" title="Save as Default Setup for All Users"></span></button>';
			}

			$buttons .='<button id="gt-toggle-info" type="button" class="button-link gt-settings" data-action="' . $info_visibility_action . '"><span class="dashicons dashicons-wordpress" ' . $info_visibility_style . ' title="Click to Reveal WP Info"></span><span class="dashicons dashicons-wordpress-alt" ' . $info_hidden_style . ' title="Click to Hide WP Info"></span></button>';
			if ( $this->info_visibility != 'visible' ) { ?>
				<style>
					#wp-version-message, #wp-version-message + p,
				 	#dashboard_right_now .inside .sub > *:not(#gt-form)
						{ display: none; }
				</style>
			<?php }

			if ( $this->adminable && apply_filters( 'gt_show_applause', TRUE ) ) {
				$buttons .= '<button id="gt-show-applause" type="button" class="button-link gt-applause" data-action="show"><span class="dashicons dashicons-awards" title="Click to Reveal Applause Actions"></span></button>';
				$buttons .= '<div id="gt-applause-wrapper">';
				$buttons .= '<a href="https://typewheel.xyz/give/?ref=Glance%20That" target="_blank" class="gt-applause"><icon title="Applaud the Author (Donation)" class="dashicons dashicons-heart"></icon></a>';
				$buttons .= '<a href="https://wordpress.org/support/plugin/glance-that/reviews/?rate=5#new-post" target="_blank" class="gt-applause"><icon title="Applaud the Author (Review)" class="dashicons dashicons-star-filled"></icon></a>';
				$buttons .= '<a href="https://twitter.com/intent/tweet/?url=https%3A%2F%2Fwordpress.org%2Fplugins%2Fglance-that%2F" target="_blank" class="gt-applause"><icon title="Applaud the Author (Tweet)" class="dashicons dashicons-twitter"></icon></a>';
				$buttons .= '</div>';
			}

			$buttons .= '</div>';

			?>

			<script type="text/javascript" language="javascript">
				jQuery(document).ready(function($) {

					$('#dashboard_right_now .handlediv').after('<button id="gt-show-settings" type="button" class="button-link gt-settings" data-action="show"><span class="dashicons dashicons-admin-settings" title="Click to Reveal Glance That Actions"></span></button>');

					$('#dashboard_right_now .handlediv').after('<?php echo $buttons; ?>');

					$('#gt-show-settings').hover(
						function() {
							$('#gt-show-settings').hide();
							$('#gt-settings-wrapper').show();
						});

					$('#gt-show-applause').click(
						function() {
							// $('#gt-show-applause').hide();
							$('#gt-applause-wrapper').toggle();
							if ( 'show' == $(this).data('action') ) {
								$(this).data('action','hide');
							} else {
								$(this).data('action','show');
							}
						});

					$('#gt-toggle-status').click(
						function() {
							$.post(Glance.ajaxurl, {
								action: 'toggle_status_visibility',
								gt_action: $(this).data('action'),
							}, function (response) {

								if ( response.success ) {

									$('.gt-statuses').toggle();
									$('#gt-toggle-status .dashicons').toggle();
									if ( 'show' == $(this).data('action') ) {
										$(this).data('action','hide');
									} else {
										$(this).data('action','show');
									}

								}
							}
					)});

					$('#gt-toggle-info').click(
						function() {
							$.post(Glance.ajaxurl, {
								action: 'toggle_info_visibility',
								gt_action: $(this).data('action'),
							}, function (response) {

								if ( response.success ) {

									$('#wp-version-message, #wp-version-message + p').toggle();
									$('#dashboard_right_now .inside .sub > *:not(#gt-form)').toggle();
									$('#gt-toggle-info .dashicons').toggle();
									if ( 'show' == $(this).data('action') ) {
										$(this).data('action','hide');
									} else {
										$(this).data('action','show');
									}

								}
							}
					)});

					$('#wpbody-content .wrap > h1').after('<div id="gt-defaults" class="notice notice-info" style="display:none;"><p>To whom would you like to apply the current glance configuration? <button id="gt-save-defaults-all" type="button" class="button-link" data-action="all"><span class="dashicons dashicons-groups" title="Apply to all existing users"></span>All Users</button> <button id="gt-save-defaults-new" type="button" class="button-link" data-action="new"><span class="dashicons dashicons-admin-users" title="Apply to all new users"></span>New Users</button></p></div>');

					$('#gt-save-defaults').click(
						function() {
							$('.gt-message').remove();
							$('#gt-defaults').show();
						});

					$('button[id^="gt-save-defaults-"]').click(
						function() {
							$.post(Glance.ajaxurl, {
								action: 'default_glances',
								gt_action: $(this).data('action'),
							}, function (response) {

								if ( response.success ) {

									$('#gt-defaults').hide();
									$('#wpbody-content .wrap > h1').after(response.notice);

								}
							}
					)});

					$('#gt-toggle-form').click(
						function() {
							$('#gt-form').toggle();
						});
				});
			</script>
			<?php

		}

	} // end status_visibility

	/**
	 * Return glance labels
	 */
	public function label( $item, $label, $count ) {

		return esc_html( apply_filters( 'gt_labels', $label, $item, $count ) );

	} // end label

	/**
	 * Adds custom post types to the end of At a Glance table
	 */
	public function customize_items( $elements = array() ) {

		foreach ( $elements as $key => $element ) {
			if ( strpos( $element, 'give_forms' ) > 1 ) {
				unset( $elements[ $key ] );
			}
		}

		$this->get_users_glances();
		$status_visibility = $this->status_visibility == 'visible' ? '' : ' style="display: none;"';

		// If not empty, add items
		if ( '' != $this->glances_indexed ) {

			// Set classes for glanced items
			$classes = ( $this->editable || $this->adminable ) ? 'gt-item gt-editable unordered' : 'gt-item unordered';

			// Sort array of glanced items for display
			$order = array();
			foreach ( $this->glances as $item => $data )
			{
			    $order[ $item ] = isset ( $data['sorted'] ) ? $data['sorted'] : NULL;
			}
			array_multisort( $order, SORT_DESC, $this->glances );

			// Reverse the order
			$this->glances = array_reverse( $this->glances );

			foreach ( $this->glances as $glance => $options ) {

				foreach ( $this->glances_indexed as $key => $data ) {

					if ( $glance == $data['glance'] ) {

						$item = $data['glance'];
						$options = $data['data'];

						switch ( $item ) {
							case 'health-check-site-status':
								$site_status_result = json_decode( get_option( 'gt_health-check-site-status-result' ), true );
								$site_status_date = get_option( 'gt_health-check-site-status-date' );

								if ( $site_status_result && current_user_can( 'install_plugins' ) ) {
									$tests_total = intval( $site_status_result['good'] ) + intval( $site_status_result['recommended'] ) + intval( $site_status_result['critical'] ) * 1.5;
									$tests_failed = intval( $site_status_result['recommended'] ) + intval( $site_status_result['critical'] ) * 1.5;
									$tests_result = 100 - ceil( ( $tests_failed / $tests_total ) * 100 );

									$text = $tests_result . '% Site Health';

									$site_info = '<a href="site-health.php?tab=debug" class="gt-view-info"><span class="dashicons dashicons-info" title="View Site Info"></span></a>';

									if ( $this->options['show_all_status'] ) {
										$critical = intval( $site_status_result['critical'] ) > 0 ? 'gt-critical gt-moderate' : '';
										$recommended = intval( $site_status_result['recommended'] ) > 0 ? 'gt-moderate' : '';
										$statuses = '<div id="gt-statuses-health-check-site-status" class="gt-statuses"' . $status_visibility . '>';
										$statuses .= ( intval( $site_status_result['good'] ) > 0 || $this->options['show_zero_count_status'] ) ? '<div class="gt-status"><a href="site-health.php" class="gt-good" title="Good">' . $site_status_result['good'] . '</a></div>' : FALSE;
										$statuses .= ( intval( $site_status_result['recommended'] ) > 0 || $this->options['show_zero_count_status'] ) ? '<div class="gt-status ' . $recommended . '"><a href="site-health.php" class="gt-recommended" title="Recommended">' . $site_status_result['recommended'] . '</a></div>' : FALSE;
										$statuses .= ( intval( $site_status_result['critical'] ) > 0 || $this->options['show_zero_count_status'] ) ? '<div class="gt-status ' . $critical . '"><a href="site-health.php" class="gt-critical" title="Critical">' . $site_status_result['critical'] . '</a></div>' : FALSE;
										$statuses .= '</div>';
									} else {
										$statuses = '';
									}

									ob_start();
										printf( '<div class="' . $classes . '" data-order="gt_' . ( $key + 1 ) . '"><style type="text/css">#dashboard_right_now li a[data-gt="%1$s"]:before{content:\'\\' . $options['icon'] . '\';}</style><div class="gt-published"><a data-gt="%1$s" href="site-health.php" class="glance-that unordered" title="Site Health (last checked ' . date( get_option('date_format'), $site_status_date ) . ')">%2$s</a>%3$s</div>%4$s</div>', $item, $text, $site_info, $statuses );
									$elements[] = ob_get_clean();
								} else if ( ! $site_status_result && current_user_can( 'install_plugins' ) ){
									$text = 'Site Health (Never Checked)';
									ob_start();
										printf( '<div class="' . $classes . '" data-order="gt_' . ( $key + 1 ) . '"><style type="text/css">#dashboard_right_now li a[data-gt="%1$s"]:before{content:\'\\' . $options['icon'] . '\';}</style><div class="gt-published"><a data-gt="%1$s" href="site-health.php" class="glance-that unordered" title="Site Health (Never Checked)">%2$s</a></div></div>', $item, $text );
									$elements[] = ob_get_clean();
								}
								break;

							case 'theme':
								$themes = get_themes();

								$theme_stats = array();

								$theme_stats['all'] = count( get_themes() );

								// Get paused themese
								if ( function_exists( 'wp_is_recovery_mode' ) && wp_is_recovery_mode() ) {
									$theme_stats['paused'] = count( wp_paused_themes()->get_all() );
								} else {
									$theme_stats['paused'] = null;
								}

								if ( $theme_stats['all'] && current_user_can( 'switch_themes' ) ) {
									$text = _n( '%s ' . $this->label( $item, 'Theme', $theme_stats['all'] ), '%s ' . $this->label( $item, 'Themes', $theme_stats['all'] ), $theme_stats['all'] );

									$text = sprintf( $text, number_format_i18n( $theme_stats['all'] ) );

									if ( current_user_can( 'install_themes' ) && $this->options['show_add_new'] ) {
										$new_theme = '<a href="theme-install.php" class="gt-add-new"><span class="dashicons dashicons-plus" title="Add New ' . $this->label( $item, 'Theme', 1 ) . '"></span></a>';
									} else {
										$new_theme = '';
									}

									if ( $this->options['show_all_status'] ) {
										$statuses = '<div class="gt-statuses"' . $status_visibility . '>';
											$moderation = count( get_theme_updates() ) > 0 ? 'gt-moderate' : '';
											$statuses .= ( count( get_theme_updates() ) > 0 || $this->options['show_zero_count_status'] ) ? '<div class="gt-status ' . $moderation . '"><a href="update-core.php#update-themes-table" class="gt-update" title="Update Available">' . count( get_theme_updates() ) . '</a></div>' : FALSE;
											$statuses .= $theme_stats['paused'] > 0 ? '<div class="gt-status gt-moderate"><a href="themes.php" class="gt-paused" title="Paused">' . $theme_stats['paused'] . '</a></div>' : FALSE;
										$statuses .= '</div>';
									} else {
										$statuses = '';
									}

									ob_start();
										printf( '<div class="' . $classes . '" data-order="gt_' . ( $key + 1 ) . '"><style type="text/css">#dashboard_right_now li a[data-gt="%1$s"]:before{content:\'\\' . $options['icon'] . '\';}</style><a data-gt="%1$s" href="themes.php" class="glance-that" title="All ' . $this->label( $item, 'Themes', 2 ) . '">%2$s</a>%3$s%4$s</div>', $item, $text, $new_theme, $statuses );
									$elements[] = ob_get_clean();
								}
								break;

							case 'revision':
								$num_posts = wp_count_posts( $item );
								if ( $num_posts && $num_posts->inherit && current_user_can( get_post_type_object( $item )->cap->edit_posts ) ) {
									$text = _n( '%s ' . $this->label( $item, get_post_type_object( $item )->labels->singular_name, $num_posts->inherit ), '%s ' . $this->label( $item, get_post_type_object( $item )->labels->name, $num_posts->inherit ), $num_posts->inherit );

									$text = sprintf( $text, number_format_i18n( $num_posts->inherit ) );

									ob_start();
										printf( '<div class="' . $classes . '" data-order="gt_' . ( $key + 1 ) . '"><style type="text/css">#dashboard_right_now li a[data-gt="%1$s"]:before{content:\'\\' . $options['icon'] . '\';}</style><a data-gt="%1$s" href="#" class="glance-that" style="pointer-events:none;color:#444;">%2$s</a><div class="gt-statuses"' . $status_visibility . '></div></div>', $item, $text );
									$elements[] = ob_get_clean();
								}
								break;

							case 'attachment':
								$num_posts = wp_count_posts( $item );
								$unattached = get_posts( array( 'post_type' => 'attachment', 'numberposts' => -1, 'post_status' => NULL, 'post_parent' => 0 ) );
								$unattached = count( $unattached );

								if ( $num_posts && ( $num_posts->inherit || $this->options['show_zero_count'] ) && current_user_can( get_post_type_object( $item )->cap->edit_posts ) ) {
									$text = _n( '%s ' . $this->label( $item, get_post_type_object( $item )->labels->singular_name, $num_posts->inherit ), '%s ' . $this->label( $item, get_post_type_object( $item )->labels->name, $num_posts->inherit ), $num_posts->inherit );

									$text = sprintf( $text, number_format_i18n( $num_posts->inherit ) );

									if ( current_user_can( 'upload_files' ) && $this->options['show_add_new'] ) {
										$new_attachment = '<a href="media-new.php" class="gt-add-new"><span class="dashicons dashicons-plus" title="Add New ' . $this->label( $item, get_post_type_object( $item )->labels->singular_name, 1 ) . '"></span></a>';
									} else {
										$new_attachment = '';
									}

									if ( $this->options['show_all_status'] ) {
										$statuses = '<div class="gt-statuses"' . $status_visibility . '>';
										$statuses .= ( $unattached > 0 || $this->options['show_zero_count_status'] ) ? '<div class="gt-status"><a href="upload.php?detached=1" class="gt-unattached" title="Unattached ' . $this->label( $item, get_post_type_object( $item )->labels->singular_name, $unattached ) . '">' . $unattached . '</a></div>' : FALSE;
										$statuses .= '</div>';
									} else {
										$statuses = '';
									}

									ob_start();
										printf( '<div class="' . $classes . '" data-order="gt_' . ( $key + 1 ) . '"><style type="text/css">#dashboard_right_now li a[data-gt="%1$s"]:before{content:\'\\' . $options['icon'] . '\';}</style><a data-gt="%1$s" href="upload.php" class="glance-that" title="All ' . $this->label( $item, get_post_type_object( $item )->labels->name, 2 ) . '">%2$s</a>%4$s%3$s</div>', $item, $text, $statuses, $new_attachment );
									$elements[] = ob_get_clean();
								}
								break;

							case 'user_request-export_personal_data':
							case 'user_request-remove_personal_data':

								// WP uses the `user_request` post type for both Data Export Requests and Data Erasure Requests – weird.
								$request_type = str_replace( 'user_request-', '', $item );

								global $wpdb;
								$query = "
									SELECT post_status, COUNT( * ) AS num_posts
									FROM {$wpdb->posts}
									WHERE post_type = %s
									AND post_name = %s
									GROUP BY post_status";
								$requests = (array) $wpdb->get_results( $wpdb->prepare( $query, 'user_request', $request_type ), ARRAY_A );
								$num_requests  = array_fill_keys( get_post_stati(), 0 );
								foreach ( $requests as $row ) {
									$num_requests[ $row['post_status'] ] = $row['num_posts'];
								}

								if ( $num_requests && ( ( $num_requests['request-pending'] + $num_requests['request-confirmed'] > 0 ) || $this->options['show_zero_count'] ) && current_user_can( 'manage_options' ) ) {
									$text = _n( '%s ' . $this->label( $item, $request_type, $num_requests['request-pending'] + $num_requests['request-confirmed'] ), '%s ' . $this->label( $item, $request_type, $num_requests['request-pending'] + $num_requests['request-confirmed'] ), $num_requests['request-pending'] + $num_requests['request-confirmed'] );

									$text = sprintf( $text, number_format_i18n( $num_requests['request-pending'] + $num_requests['request-confirmed'] ) );

									if ( $this->options['show_add_new'] ) {
										$new_post = '<a href="/wp-admin/tools.php?page=' . $request_type . '" class="gt-add-new"><span class="dashicons dashicons-plus" title="Add New ' . $this->label( $item, $request_type, 1 ) . '"></span></a>';
									} else {
										$new_post = '';
									}

									if ( $this->options['show_all_status'] ) {
										$statuses = '<div class="gt-statuses"' . $status_visibility . '>';

										if ( $num_requests['request-pending'] > 0 || $this->options['show_zero_count_status'] ) {
											$statuses .= '<div class="gt-status"><a href="/wp-admin/tools.php?page=' . $request_type . '&filter-status=request-pending" class="gt-pending" title="Pending">' . $num_requests['request-pending'] . '</a></div>';
										}
										if ( $num_requests['request-confirmed'] > 0 || $this->options['show_zero_count_status'] ) {
											$moderation = intval( $num_requests['request-confirmed'] ) > 0 ? 'gt-moderate' : '';
											$statuses .= '<div class="gt-status ' . $moderation . '"><a href="/wp-admin/tools.php?page=' . $request_type . '&filter-status=request-confirmed" class="gt-confirmed" title="Confirmed">' . $num_requests['request-confirmed'] . '</a></div>';
										}
										if ( $num_requests['request-failed'] > 0 || $this->options['show_zero_count_status'] ) {
											$statuses .= '<div class="gt-status"><a href="/wp-admin/tools.php?page=' . $request_type . '&filter-status=request-failed" class="gt-failed" title="Failed">' . $num_requests['request-failed'] . '</a></div>';
										}
										if ( $num_requests['request-completed'] > 0 || $this->options['show_zero_count_status'] ) {
											$statuses .= '<div class="gt-status"><a href="/wp-admin/tools.php?page=' . $request_type . '&filter-status=request-completed" class="gt-completed" title="Completed">' . $num_requests['request-completed'] . '</a></div>';
										}

										$statuses .= '</div>';
									} else {
										$statuses = '';
									}


									ob_start();
										printf( '<div class="' . $classes . '" data-order="gt_' . ( $key + 1 ) . '" data-glance="' . $item . '"><style type="text/css">#dashboard_right_now li a[data-gt="%1$s"]:before{content:\'\\' . $options['icon'] . '\';}</style><div class="gt-published"><a data-gt="%1$s" href="tools.php?page=%1$s" class="glance-that" title="All %4$s">%2$s</a>%5$s</div>%3$s</div>', $request_type, $text, $statuses, $this->label( $item, $request_type, 2 ), $new_post );
									$elements[] = ob_get_clean();


								}
								break;

							case 'comment':
								$num_comments = wp_count_comments();

								if ( ( $num_comments->approved || $this->options['show_zero_count'] ) && current_user_can( 'moderate_comments' ) && current_user_can( 'edit_posts' ) ) {
									$text = _n( '%s ' . $this->label( $item, 'Comment', $num_comments->approved ), '%s ' . $this->label( $item, 'Comments', $num_comments->approved ), $num_comments->approved );

									$text = sprintf( $text, number_format_i18n( $num_comments->approved ) );

									if ( $this->options['show_all_status'] ) {
										$moderation = intval( $num_comments->moderated ) > 0 ? 'gt-moderate' : '';
										$statuses = '<div id="gt-statuses-comments" class="gt-statuses"' . $status_visibility . '>';
										$statuses .= ( $num_comments->moderated > 0 || $this->options['show_zero_count_status'] ) ? '<div class="gt-status ' . $moderation . '"><a href="edit-comments.php?comment_status=moderated" class="gt-pending" title="Pending">' . $num_comments->moderated . '</a></div>' : FALSE;
										$statuses .= ( $num_comments->spam > 0 || $this->options['show_zero_count_status'] ) ? '<div class="gt-status"><a href="edit-comments.php?comment_status=spam" class="gt-spam" title="Spam">' . $num_comments->spam . '</a></div>' : FALSE;
										$statuses .= ( $num_comments->trash > 0 || $this->options['show_zero_count_status'] ) ? '<div class="gt-status"><a href="edit-comments.php?comment_status=trash" class="gt-trash" title="Trash">' . $num_comments->trash . '</a></div>' : FALSE;
										$statuses .= '</div>';
									} else {
										$statuses = '';
									}

									ob_start();
										printf( '<div class="' . $classes . '" data-order="gt_' . ( $key + 1 ) . '"><style type="text/css">#dashboard_right_now li a[data-gt="%1$s"]:before{content:\'\\' . $options['icon'] . '\';}</style><div class="gt-published"><a data-gt="%1$s" href="edit-comments.php" class="glance-that unordered" title="All ' . $this->label( $item, 'Comments', 2 ) . '">%2$s</a></div>%3$s</div>', $item, $text, $statuses );
									$elements[] = ob_get_clean();
								}
								break;

							case 'plugin':
								$plugins = get_plugins();

								$plugin_stats = array();

								$plugin_stats['all'] = count( $plugins );

								$plugin_stats['active'] = 0;
								foreach ( $plugins as $plugin => $data ) {
									is_plugin_active( $plugin ) ? $plugin_stats['active']++ : FALSE;
								}

								$plugin_stats['inactive'] = $plugin_stats['all'] - $plugin_stats['active'];

								$plugin_stats['update'] = count( get_plugin_updates() );

								if ( apply_filters( 'show_advanced_plugins', true, 'mustuse' ) ) {
									$plugin_stats['mustuse'] = count( get_mu_plugins() );
								} else {
									$plugin_stats['mustuse'] = NULL;
									add_filter( 'gt_show_mustuse', '__return_false' );
								}

								if ( apply_filters( 'show_advanced_plugins', true, 'dropins' ) ) {
									$plugin_stats['dropins'] = count( get_dropins() );
								}  else {
									$plugin_stats['dropins'] = NULL;
									add_filter( 'gt_show_dropins', '__return_false' );
								}

								$plugin_stats['recent'] = count( get_site_option( 'recently_activated', array() ) );

								// Get user favorites
								include( ABSPATH . 'wp-admin/includes/plugin-install.php' );

								$user = get_user_option( 'wporg_favorites' );

								if ( false !== $user ) {
									$args = array( 'user' => $user );
									$args = apply_filters( "install_plugins_table_api_args_favorites", $args );

									$api = plugins_api( 'query_plugins', $args );

									$plugin_stats['favorites'] = count( $api->plugins );
								} else {
									$plugin_stats['favorites'] = null;
								}

								// Get paused plugins
								if ( function_exists( 'wp_is_recovery_mode' ) && wp_is_recovery_mode() ) {
									$plugin_stats['paused'] = count( wp_paused_plugins()->get_all() );
								} else {
									$plugin_stats['paused'] = null;
								}

								if ( current_user_can( 'install_plugins' ) && $this->options['show_add_new'] ) {
									$new_plugin = '<a href="plugin-install.php" class="gt-add-new"><span class="dashicons dashicons-plus" title="Add New ' . $this->label( $item, 'Plugin', 1 ) . '"></span></a>';
								} else {
									$new_plugin = '';
								}

								// Display plugin glance
								if ( ( $plugin_stats['all'] || $this->options['show_zero_count'] ) && current_user_can( 'activate_plugins' ) ) {
									$text = _n( '%s ' . $this->label( $item, 'Plugin', $plugin_stats['all'] ), '%s ' . $this->label( $item, 'Plugins', $plugin_stats['all'] ), $plugin_stats['all'] );

									$text = sprintf( $text, number_format_i18n( $plugin_stats['all'] ) );

									if ( $this->options['show_all_status'] ) {
										$statuses = '<div class="gt-statuses"' . $status_visibility . '>';
											$statuses .= ( $plugin_stats['active'] > 0 || $this->options['show_zero_count_status'] ) ? '<div class="gt-status"><a href="plugins.php?plugin_status=active" class="gt-active" title="Active">' . $plugin_stats['active'] . '</a></div>' : FALSE;
											$statuses .= ( $plugin_stats['inactive'] > 0 || $this->options['show_zero_count_status'] ) ? '<div class="gt-status"><a href="plugins.php?plugin_status=inactive" class="gt-inactive" title="Inactive">' . $plugin_stats['inactive'] . '</a></div>' : FALSE;
											$statuses .= ( $plugin_stats['recent'] > 0 || $this->options['show_zero_count_status'] ) ? '<div class="gt-status"><a href="plugins.php?plugin_status=recently_activated" class="gt-recent" title="Recently Active">' . $plugin_stats['recent'] . '</a></div>' : FALSE;
											$moderation = intval( $plugin_stats['update'] ) > 0 ? 'gt-moderate' : '';
											$statuses .= ( $plugin_stats['update'] > 0 || $this->options['show_zero_count_status'] ) ? '<div class="gt-status ' . $moderation . '"><a href="plugins.php?plugin_status=upgrade" class="gt-update" title="Update Available">' . $plugin_stats['update'] . '</a></div>' : FALSE;
											$statuses .= ( null !== $plugin_stats['favorites'] && ( $plugin_stats['favorites'] > 0 || $this->options['show_zero_count_status'] ) ) ? '<div class="gt-status"><a href="plugin-install.php?tab=favorites" class="gt-favorites" title="Favorites: ' . $user . '">' . $plugin_stats['favorites'] . '</a></div>' : FALSE;
											$statuses .= ( $plugin_stats['mustuse'] > 0 && $this->options['show_mustuse'] ) ? '<div class="gt-status"><a href="plugins.php?plugin_status=mustuse" class="gt-mustuse" title="Must-Use">' . $plugin_stats['mustuse'] . '</a></div>' : FALSE;
											$statuses .= ( $plugin_stats['dropins'] > 0 && $this->options['show_dropins'] ) ? '<div class="gt-status"><a href="plugins.php?plugin_status=dropins" class="gt-dropins" title="Drop-ins">' . $plugin_stats['dropins'] . '</a></div>' : FALSE;
											$statuses .= $plugin_stats['paused'] > 0 ? '<div class="gt-status gt-moderate"><a href="plugins.php?plugin_status=paused" class="gt-paused" title="Paused">' . $plugin_stats['paused'] . '</a></div>' : FALSE;
										$statuses .= '</div>';
									} else {
										$statuses = '';
									}

									ob_start();
										printf( '<div class="' . $classes . '" data-order="gt_' . ( $key + 1 ) . '"><style type="text/css">#dashboard_right_now li a[data-gt="%1$s"]:before{content:\'\\' . $options['icon'] . '\';}</style><div class="gt-published"><a data-gt="%1$s" href="plugins.php" class="glance-that" title="All ' . $this->label( $item, 'Plugins', 2 ) . '">%2$s</a>%4$s</div>%3$s</div>', $item, $text, $statuses, $new_plugin );
									$elements[] = ob_get_clean();
								}

								break;

							case 'user':
								$num_users = count_users();

								if ( current_user_can( 'create_users' ) && $this->options['show_add_new'] ) {
									$new_user = '<a href="user-new.php" class="gt-add-new"><span class="dashicons dashicons-plus" title="Add New ' . $this->label( $item, 'User', 1 ) . '"></span></a>';
								} else {
									$new_user = '';
								}

								if ( current_user_can( 'list_users' ) ) {
									$text = _n( '%s ' . $this->label( $item, 'User', $num_users['total_users'] ), '%s ' . $this->label( $item, 'Users', $num_users['total_users'] ), $num_users['total_users'] );

									$text = sprintf( $text, number_format_i18n( $num_users['total_users'] ) );

									ob_start();
										printf( '<div class="' . $classes . '" data-order="gt_' . ( $key + 1 ) . '"><style type="text/css">#dashboard_right_now li a[data-gt="user"]:before{content:\'\\' . $options['icon'] . '\';}</style><a data-gt="user" href="users.php" class="glance-that" title="All ' . $this->label( $item, 'Users', 2 ) . '">%1$s</a>%2$s</div>', $text, $new_user );
									$elements[] = ob_get_clean();
								}
								break;

							case 'gravityform':
								if ( class_exists( 'RGFormsModel' ) ) {
									$num_forms = RGFormsModel::get_form_count();

									if ( ( $num_forms['total'] || $this->options['show_zero_count'] ) && ( current_user_can( 'gform_full_access' ) || current_user_can( 'gravityforms_edit_forms' ) ) ) {
										$text = _n( '%s ' . $this->label( $item, 'Form', $num_forms['total'] ), '%s ' . $this->label( $item, 'Forms', $num_forms['total'] ), $num_forms['total'] );

										$text = sprintf( $text, number_format_i18n( $num_forms['total'] ) );

										if ( ( current_user_can( 'gravityforms_create_form' ) || current_user_can( 'update_core' ) ) && $this->options['show_add_new'] ) {
											$new_gravityform = '<a href="admin.php?page=gf_new_form" class="gt-add-new"><span class="dashicons dashicons-plus" title="Add New ' . $this->label( $item, 'Form', 1 ) . '"></span></a>';
										} else {
											$new_gravityform = '';
										}

										if ( $this->options['show_all_status'] ) {
											$statuses = '<div class="gt-statuses"' . $status_visibility . '>';
												$statuses .= ( $num_forms['active'] > 0 || $this->options['show_zero_count_status'] ) ? '<div class="gt-status"><a href="admin.php?page=gf_edit_forms&filter=active" class="gt-active" title="Active ' . $this->label( $item, 'Forms', $num_forms['active'] ) . '">' . $num_forms['active'] . '</a></div>' : FALSE;
												$statuses .= ( $num_forms['inactive'] > 0 || $this->options['show_zero_count_status'] ) ? '<div class="gt-status"><a href="admin.php?page=gf_edit_forms&filter=inactive" class="gt-inactive" title="Inactive ' . $this->label( $item, 'Forms', $num_forms['inactive'] ) . 's">' . $num_forms['inactive'] . '</a></div>' : FALSE;
												$statuses .= ( $num_forms['trash'] > 0 || $this->options['show_zero_count_status'] ) ? '<div class="gt-status"><a href="admin.php?page=gf_edit_forms&filter=trash" class="gt-trash" title="Trash">' . $num_forms['trash'] . '</a></div>' : FALSE;
											$statuses .= '</div>';
										} else {
											$statuses = '';
										}

										ob_start();
											printf( '<div class="' . $classes . '" data-order="gt_' . ( $key + 1 ) . '"><div class="gt-published"><a data-gt="%1$s" href="admin.php?page=gf_edit_forms" class="glance-that unordered" title="All ' . $this->label( $item, 'Forms', 2 ) . '">%2$s</a>%4$s</div>%3$s</div>', $item, $text, $statuses, $new_gravityform );
										$elements[] = ob_get_clean();
									}
								}
								break;

							case 'formidableform':
								if ( class_exists( 'FrmForm' ) ) {
									$num_forms = FrmForm::get_count();

									if ( ( $num_forms->published || $this->options['show_zero_count'] ) && ( current_user_can( 'frm_view_forms' ) || current_user_can( 'frm_edit_forms' ) ) ) {
										$text = _n( '%s ' . $this->label( $item, 'Form', $num_forms->published ), '%s ' . $this->label( $item, 'Forms', $num_forms->published ), $num_forms->published );

										$text = sprintf( $text, number_format_i18n( $num_forms->published ) );

										if ( current_user_can( 'frm_edit_forms' ) && $this->options['show_add_new'] ) {
											$new_formidableform = '<a href="admin.php?page=formidable&frm_action=new" class="gt-add-new"><span class="dashicons dashicons-plus" title="Add New ' . $this->label( $item, 'Form', 1 ) . '"></span></a>';
										} else {
											$new_formidableform = '';
										}

										if ( $this->options['show_all_status'] ) {
											$statuses = '<div class="gt-statuses"' . $status_visibility . '>';
												$statuses .= ( $num_forms->template > 0 || $this->options['show_zero_count_status'] ) ? '<div class="gt-status"><a href="admin.php?page=formidable&form_type=template" class="gt-template" title="' . $this->label( $item, 'Form', 1 ) . ' Templates">' . $num_forms->template . '</a></div>' : FALSE;
												$statuses .= ( $num_forms->draft > 0 || $this->options['show_zero_count_status'] ) ? '<div class="gt-status"><a href="admin.php?page=formidable&form_type=draft" class="gt-draft" title="Drafts">' . $num_forms->draft . '</a></div>' : FALSE;
												$statuses .= ( $num_forms->trash > 0 || $this->options['show_zero_count_status'] ) ? '<div class="gt-status"><a href="admin.php?page=formidable&form_type=trash" class="gt-trash" title="Trash">' . $num_forms->trash . '</a></div>' : FALSE;
											$statuses .= '</div>';
										} else {
											$statuses = '';
										}

										ob_start();
											printf( '<div class="' . $classes . '" data-order="gt_' . ( $key + 1 ) . '"><div class="gt-published"><a data-gt="%1$s" href="admin.php?page=formidable" class="glance-that unordered" title="All ' . $this->label( $item, 'Forms', 2 ) . '">%2$s</a>%4$s</div>%3$s</div>', $item, $text, $statuses, $new_formidableform );
										$elements[] = ob_get_clean();
									}
								}
								break;

							case 'ninjaform':
								if ( class_exists( 'Ninja_Forms' ) ) {
									$ninjaforms = Ninja_Forms()->form()->get_forms();

									$num_forms = count($ninjaforms);

									if ( ( $num_forms || $this->options['show_zero_count'] ) && ( current_user_can( apply_filters( 'ninja_forms_admin_parent_menu_capabilities', 'manage_options' ) ) ) ) {
										$text = _n( '%s ' . $this->label( $item, 'Form', $num_forms ), '%s ' . $this->label( $item, 'Forms', $num_forms ), $num_forms );

										$text = sprintf( $text, number_format_i18n( $num_forms ) );

										if ( current_user_can( apply_filters( 'ninja_forms_admin_parent_menu_capabilities', 'manage_options' ) ) && $this->options['show_add_new'] ) {
											$new_ninjaform = '<a href="admin.php?page=ninja-forms#new-form" class="gt-add-new"><span class="dashicons dashicons-plus" title="Add New ' . $this->label( $item, 'Form', 1 ) . '"></span></a>';
										} else {
											$new_ninjaform = '';
										}

										ob_start();
											printf( '<div class="' . $classes . '" data-order="gt_' . ( $key + 1 ) . '"><style type="text/css">#dashboard_right_now li a[data-gt="%1$s"]:before{content:\'\\' . $options['icon'] . '\';}</style><div class="gt-published"><a data-gt="%1$s" href="admin.php?page=ninja-forms" class="glance-that unordered" title="All ' . $this->label( $item, 'Forms', 2 ) . '">%2$s</a>%3$s</div></div>', $item, $text, $new_ninjaform );
										$elements[] = ob_get_clean();
									}
								}
								break;

							default:
								if ( post_type_exists( $item ) ) {
									$num_posts = wp_count_posts( $item );
									if ( $num_posts && ( $num_posts->publish || $this->options['show_zero_count'] ) && current_user_can( get_post_type_object( $item )->cap->edit_posts ) ) {
										$text = _n( '%s ' . $this->label( $item, get_post_type_object( $item )->labels->singular_name, $num_posts->publish ), '%s ' . $this->label( $item, get_post_type_object( $item )->labels->name, $num_posts->publish ), $num_posts->publish );

										$text = sprintf( $text, number_format_i18n( $num_posts->publish ) );

										if ( current_user_can( get_post_type_object( $item )->cap->edit_posts ) && $this->options['show_add_new'] ) {
											$new_post = '<a href="post-new.php?post_type=' . $item . '" class="gt-add-new"><span class="dashicons dashicons-plus" title="Add New ' . $this->label( $item, get_post_type_object( $item )->labels->singular_name, 1 ) . '"></span></a>';
										} else {
											$new_post = '';
										}

										if ( get_post_type_archive_link( $item ) && $this->options['show_archive'] ) {
											$archive = '<a href="' . get_post_type_archive_link( $item ) . '" class="gt-view-archive"><span class="dashicons dashicons-external" title="View ' . $this->label( $item, get_post_type_object( $item )->labels->singular_name, 1 ) . ' Archive"></span></a>';
										} else {
											$archive = '';
										}

										if ( $this->options['show_all_status'] ) {
											$statuses = '<div class="gt-statuses"' . $status_visibility . '>';

											// get my post count
											$exclude_states   = get_post_stati( array(
												'show_in_admin_all_list' => false,
											) );
											global $wpdb;
											$author = get_current_user_id();
											$user_num_posts = intval( $wpdb->get_var( $wpdb->prepare( "
												SELECT COUNT( 1 )
												FROM $wpdb->posts
												WHERE post_type = %s
												AND post_status NOT IN ( '" . implode( "','", $exclude_states ) . "' )
												AND post_author = %d
											", $item, $author ) ) );


											if ( current_user_can( get_post_type_object( $item )->cap->edit_posts ) && ( $user_num_posts > 0 || $this->options['show_zero_count_status'] ) && $this->options['show_mine'] ) {
												$statuses .= '<div class="gt-status"><a href="edit.php?post_type=' . $item . '&author=' . $author . '" class="gt-mine" title="Mine">' . $user_num_posts . '</a></div>';
											}
											if ( current_user_can( get_post_type_object( $item )->cap->publish_posts ) && ( $num_posts->future > 0 || $this->options['show_zero_count_status'] ) ) {
												$statuses .= '<div class="gt-status"><a href="edit.php?post_type=' . $item . '&post_status=future" class="gt-future" title="Scheduled">' . $num_posts->future . '</a></div>';
											}
											if ( current_user_can( get_post_type_object( $item )->cap->edit_posts ) && ( $num_posts->pending > 0 || $this->options['show_zero_count_status'] ) ) {
												$moderation = intval( $num_posts->pending ) > 0 ? 'gt-moderate' : '';
												$statuses .= '<div class="gt-status ' . $moderation . '"><a href="edit.php?post_type=' . $item . '&post_status=pending" class="gt-pending" title="Pending">' . $num_posts->pending . '</a></div>';
											}
											if ( current_user_can( get_post_type_object( $item )->cap->edit_posts && ( $num_posts->draft > 0 || $this->options['show_zero_count_status'] ) ) ) {
												$statuses .= '<div class="gt-status"><a href="edit.php?post_type=' . $item . '&post_status=draft" class="gt-draft" title="Drafts">' . $num_posts->draft . '</a></div>';
											}
											if ( ( ( ! isset( get_post_type_object( $item )->cap->edit_private_posts ) && current_user_can( 'edit_private_posts' ) ) || current_user_can( get_post_type_object( $item )->cap->edit_private_posts ) ) && ( $num_posts->private > 0 || $this->options['show_zero_count_status'] ) ) {
												$statuses .= '<div class="gt-status"><a href="edit.php?post_type=' . $item . '&post_status=private" class="gt-private" title="Private">' . $num_posts->private . '</a></div>';
											}
											if ( is_plugin_active( 'archived-post-status/archived-post-status.php' ) && ( ( ! isset( get_post_type_object( $item )->cap->read_private_posts ) && current_user_can( 'read_private_posts' ) ) || current_user_can( get_post_type_object( $item )->cap->read_private_posts ) ) && ( $num_posts->archive > 0 || $this->options['show_zero_count_status'] ) ) {
												$statuses .= '<div class="gt-status"><a href="edit.php?post_type=' . $item . '&post_status=archive" class="gt-archive" title="Archived">' . $num_posts->archive . '</a></div>';
											}
											if ( ( ( ! isset( get_post_type_object( $item )->cap->delete_posts ) && current_user_can( 'delete_posts' ) && current_user_can( get_post_type_object( $item )->cap->edit_posts ) ) || ( current_user_can( get_post_type_object( $item )->cap->edit_posts ) && current_user_can( get_post_type_object( $item )->cap->delete_posts ) ) ) && ( $num_posts->trash > 0 || $this->options['show_zero_count_status'] ) ) {
												$statuses .= '<div class="gt-status"><a href="edit.php?post_type=' . $item . '&post_status=trash" class="gt-trash" title="Trash">' . $num_posts->trash . '</a></div>';
											}
											$statuses .= '</div>';
										} else {
											$statuses = '';
										}

										ob_start();
											printf( '<div class="' . $classes . '" data-order="gt_' . ( $key + 1 ) . '"><style type="text/css">#dashboard_right_now li a[data-gt="%1$s"]:before{content:\'\\' . $options['icon'] . '\';}</style><div class="gt-published"><a data-gt="%1$s" href="edit.php?post_type=%1$s" class="glance-that" title="All %4$s">%2$s</a>%5$s%6$s</div>%3$s</div>', $item, $text, $statuses, $this->label( $item, get_post_type_object( $item )->labels->name, 2 ), $new_post, $archive );
										$elements[] = ob_get_clean();
									}
								}
								break;
						} // end switch
					} // end if
				} // end foreach
			} // end foreach
		}

		return $elements;

	} // end customize_items

	public function customize_labels( $label, $glance, $count ) {

		switch ( $glance ) {
			case 'wp_block':
				$label = ( $count > 1 || $count == 0 )  ? 'Reusable Blocks' : 'Reusable Block';
				break;
			case 'user_request-export_personal_data':
				$label = ( $count > 1 || $count == 0 )  ? 'Data Export Requests' : 'Data Export Request';
				break;
			case 'user_request-remove_personal_data':
				$label = ( $count > 1 || $count == 0 )  ? 'Data Erasure Requests' : 'Data Erasure Request';
				break;
			case 'health-check-site-status':
				$label = ( $count > 1 || $count == 0 )  ? 'Site Health' : 'Site Health';
				break;
			case 'ph-website':
				$label = ( $count > 1 || $count == 0 )  ? 'PH Sites' : 'PH Site';
				break;
			case 'ph-project':
				$label = ( $count > 1 || $count == 0 )  ? 'PH Mockups' : 'PH Mockup';
				break;
			case 'gravityview':
				$label = ( $count > 1 || $count == 0 )  ? 'Gravity Views' : 'Gravity View';
				break;
			default:
				$label = $label;
				break;
		}

		return $label;

	}

	/**
	 * Adds a form for adding/removing custom post types from the At A Glance
	 */
	public function add_form() {

		if ( $this->editable || $this->adminable ) {

			global $current_user;
			wp_get_current_user();

			// Assemble a form for adding/removing post types
			$html = '<form id="gt-form" method="post" action="#" data-userid="' . $current_user->ID . '"';

				// Keep form visible if submission has just been made
				$html .= ( isset( $_POST['action'] ) && 'add_remove_glance' == $_POST['action'] ) ? '>' : ' style="display:none;">';

				// Build up the list of post types
				$post_types = get_post_types( array(), 'objects' );

				// Apply filters to available post types
				$post_types = apply_filters( 'gt_post_type_selection', $post_types );

				// Add styling for iconset
				$html .= '<style type="text/css">
					.dashicon{display:inline-block;}
					.dashicon:before{
						font: normal 20px/1 \'dashicons\';
						padding: 6px;
						left: -1px;
						position: relative;
						vertical-align: top;
						-webkit-font-smoothing: antialiased;
						-moz-osx-font-smoothing: grayscale;
						text-decoration: none !important;}

					</style>';

				// Set the visible icon according to default icon
				$html .= '<div id="visible-icon" alt="' . esc_attr( $this->get_icon_code( 'marker' ) ) . '" class="dashicon dashicons-' . esc_attr( 'marker' ) . ' dashicons-picker"></div>';

				// Set the hidden form field according to provided id and default icon
				$html .= '<input id="' . esc_attr( 'gt-item-icon' ) . '" name="' . esc_attr( 'gt-item-icon' ) . '" type="hidden" data-dashicon="selected" value="' . esc_attr( $this->get_icon_code( 'marker' ) ) . '" />';

				$html .= ' <select id="gt-item" name="gt-item">';
					$html .= '<option value""></option>';

					// Initialize an options array that we'll later loop through to generate options
					$options = array(
						'comment' => array(
							'glancing'   => isset( $this->glances['comment'] ),
							'capability' => 'moderate_comments',
							'icon'       => array( 'admin-comments', 'dashicons' ),
							'label'      => $this->label( 'comment', 'Comments', 2 ),
						),
						'user' => array(
							'glancing'   => isset( $this->glances['user'] ),
							'capability' => 'list_users',
							'icon'       => array( 'admin-users', 'dashicons' ),
							'label'      => $this->label( 'user', 'Users', 2 ),
						),
						'plugin' => array(
							'glancing'   => isset( $this->glances['plugin'] ),
							'capability' => 'activate_plugins',
							'icon'       => array( 'admin-plugins', 'dashicons' ),
							'label'      => $this->label( 'plugin', 'Plugins', 2 ),
						),
						'theme' => array(
							'glancing'   => isset( $this->glances['theme'] ),
							'capability' => 'switch_themes',
							'icon'       => array( 'admin-appearance', 'dashicons' ),
							'label'      => $this->label( 'theme', 'Themes', 2 ),
						),
					);

					global $wp_version;

					if ( version_compare( $wp_version, '4.9.6', '>=' ) ) {
						$options['user_request-export_personal_data'] = array(
							'glancing'   => isset( $this->glances['user_request-export_personal_data'] ),
							'capability' => 'manage_options',
							'icon'       => array( 'download', 'dashicons' ),
							'label'      => $this->label( 'user_request-export_personal_data', '', 2 ),
						);
						$options['user_request-remove_personal_data'] = array(
							'glancing'   => isset( $this->glances['user_request-remove_personal_data'] ),
							'capability' => 'manage_options',
							'icon'       => array( 'editor-removeformatting', 'dashicons' ),
							'label'      => $this->label( 'user_request-remove_personal_data', '', 2 ),
						);
					}
					if ( version_compare( $wp_version, '5.2-RC1', '>=' ) ) {
						$options['health-check-site-status'] = array(
							'glancing'   => isset( $this->glances['health-check-site-status'] ),
							'capability' => 'install_plugins',
							'icon'       => array( 'heart', 'dashicons' ),
							'label'      => $this->label( 'health-check-site-status', '', 2 ),
						);
					}

					foreach( $post_types as $index => $post_type ) {

						// Only show revisions to admininstrators
						if ( 'revision' == $post_type->name && current_user_can( 'edit_dashboard' ) ) {

							$options['revision'] = array(
								// 'glance'      => $post_type->name,
								'glancing'    => isset( $this->glances[ $post_type->name ] ),
								'capability'  => 'edit_dashboard',
								'icon'        => array( 'backup', 'dashicons' ),
								'label'       => $this->label( $post_type->name, $post_type->labels->name, 2 )
							);

						} else {

							if ( 'post' == $post_type->name ) {
								$icon = 'admin-post';
							} elseif ( 'page' == $post_type->name ) {
								$icon = 'admin-page';
							} elseif ( 'attachment' == $post_type->name ) {
								$icon = 'admin-media';
							} elseif ( ! empty( $post_type->menu_icon  ) ) {
								$icon = esc_attr( str_replace( 'dashicons-', '', apply_filters( 'gt_option_icons', $post_type->menu_icon, $post_type->name ) ) );
							} else {
								$icon = apply_filters( 'gt_option_icons', 'marker', $post_type->name );
							}

							$options[ $post_type->name ] = array(
								// 'glance'      => $post_type->name,
								'glancing'    => isset( $this->glances[ $post_type->name ] ),
								'capability'  => $post_type->cap->edit_posts,
								'icon'        => array( $icon, 'dashicons' ),
								'label'       => $this->label( $post_type->name, $post_type->labels->name, 2 )
							);

						}

					}

					if ( class_exists( 'RGFormsModel' ) ) {

						$options['gravityform'] = array(
							'glancing'   => isset( $this->glances['gravityform'] ),
							'capability' => array( 'gform_full_access', 'gravityforms_edit_forms' ),
							'icon'       => array( 'gravityform', 'custom' ),
							'label'      => $this->label( 'gravityform', 'Gravity Forms', 2 )
						);

					}

					if ( class_exists( 'FrmForm' ) ) {

						$options['formidableform'] = array(
							'glancing'   => isset( $this->glances['formidableform'] ),
							'capability' => array( 'frm_view_forms', 'frm_edit_forms' ),
							'icon'       => array( 'formidableform', 'custom' ),
							'label'      => $this->label( 'formidableform', 'Formidable Forms', 2 )
						);

					}

					if ( class_exists( 'Ninja_Forms' ) ) {

						$options['ninjaform'] = array(
							'glancing'   => isset( $this->glances['ninjaform'] ),
							'capability' => apply_filters( 'ninja_forms_admin_parent_menu_capabilities', 'manage_options' ),
							'icon'       => array( 'feedback', 'dashicons' ),
							'label'      => $this->label( 'ninjaform', 'Ninja Forms', 2 ),
						);

					}

					$html .= $this->assemble_options( $options );

				$html .= '</select>';

				// Set the submission buttons which are handled via jquery
				$html .= '<span style="float: right;">';
					$html .= '<input type="submit" class="button-primary" id="submit-gt-item" />';
				$html .= '</span>';

			$html .= '</form>';

			echo $html;

		}

	} // end add_form

	public function assemble_options( $options ) {

		$html = '';

		foreach ( $options as $glance => $args ) {

			$glancing = $args['glancing'] ? 'data-glancing="shown"' : 'data-glancing="hidden"';

			$has_cap = false;

			if ( is_array( $args['capability'] ) ) {

				foreach ( $args['capability'] as $cap ) {
					if ( current_user_can( $cap ) ) {
						$has_cap = true;
						break;
					}
				}

			} else {
				$has_cap = current_user_can( $args['capability'] );
			}

			$has_cap ? $html .= '<option value="' . $glance . '" data-dashicon="' . $this->get_icon_code( $args['icon'][0], $args['icon'][1] ) . '" ' . $glancing . '>' . $args['label'] . '</options>' : FALSE;

		}

		return $html;

	}

	/**
	 * Remove post types from option list
	 */
	public function remove_post_type_options( $post_types ) {

		unset( $post_types['give_payment'] );
		unset( $post_types['give_log'] );
		unset( $post_types['oembed_cache'] );
		unset( $post_types['phw_comment_loc'] );
		unset( $post_types['ph-webpage'] );
		unset( $post_types['ph_comment_location'] );
		unset( $post_types['project_image'] );
		unset( $post_types['nav_menu_item'] );
		unset( $post_types['customize_changeset'] );
		unset( $post_types['custom_css'] );
		unset( $post_types['frm_form_actions'] );
		unset( $post_types['frm_styles'] );
		unset( $post_types['acf-field'] );
		unset( $post_types['user_request'] );

		return $post_types;

	} // end remove_post_type_options

	/**
	 * Customize default post type icon when option is selected
	 */
	public function customize_post_type_icon( $icon, $post_type ) {

		switch ( $post_type ) {
			case 'acf-field-group':
				return 'welcome-widgets-menus';
				break;
			case 'wp_show_posts':
				return 'editor-ul';
				break;
			case 'gravityview':
				return 'gravityview';
				break;
			case 'wp_block':
				return 'layout';
				break;
			default:
				return $icon;
				break;
		}

	} // end customize_post_type_icon

	/**
	 * Process any responses to the displayed notices.
	 */
	public function process_form() {

		if ( $this->editable || $this->adminable ) {

			// Get current user
			$current_user = wp_get_current_user();

			// Get the submitted post type glance
			$glance = isset( $_POST['gt_item'] ) ? $_POST['gt_item'] : '';

			// Get all post types
			$post_types = get_post_types();

			// If adding an item
			if ( 'Add_gt_item' == $_POST['gt_action'] ) {

				// If no item is selected
				if ( '' == $glance ) {
					$this->notices[] = array( 'message' => 'You must select an item to add.', 'class' => 'error' );
					$success = false;
				}
				// Otherwise, add submitted item
				else {

					// Add item to glance_that user meta
					$this->glances[ $glance ] = array( 'icon' => $_POST['gt_item_icon'] );

					// Alphabetize the items
					ksort( $this->glances );

					// Update the meta
					update_user_meta( $current_user->ID, 'glance_that', $this->glances );

					// Display notices
					if ( in_array( $glance, $post_types ) ) {
						$this->notices[] = array( 'message' => '<strong>' . esc_html( get_post_type_object( $glance )->labels->name, 2 ) . '</strong> were successfully added to your glances.', 'class' => 'success' );
					} elseif ( 'user' == $glance ) {
						$this->notices[] = array( 'message' => '<strong>Users</strong> were successfully added to your glances.', 'class' => 'success' );
					} elseif ( 'plugin' == $glance ) {
						$this->notices[] = array( 'message' => '<strong>Plugins</strong> were successfully added to your glances.', 'class' => 'success' );
					} elseif ( 'comment' == $glance ) {
						$this->notices[] = array( 'message' => '<strong>Comments</strong> were successfully added to your glances.', 'class' => 'success' );
					} elseif ( 'user_request-export_personal_data' == $glance ) {
						$this->notices[] = array( 'message' => '<strong>Data Export Requests</strong> were successfully added to your glances.', 'class' => 'success' );
					} elseif ( 'user_request-remove_personal_data' == $glance ) {
						$this->notices[] = array( 'message' => '<strong>Data Erasure Requests</strong> were successfully added to your glances.', 'class' => 'success' );
					} elseif ( 'gravityform' == $glance ) {
						$this->notices[] = array( 'message' => '<strong>Gravity Forms</strong> were successfully added to your glances.', 'class' => 'success' );
					} elseif ( 'formidableform' == $glance ) {
						$this->notices[] = array( 'message' => '<strong>Formidable Forms</strong> were successfully added to your glances.', 'class' => 'success' );
					} elseif ( 'ninjaform' == $glance ) {
						$this->notices[] = array( 'message' => '<strong>Ninja Forms</strong> were successfully added to your glances.', 'class' => 'success' );
					}

					$success = true;

				}

			// If removing item
		} elseif ( 'Remove_gt_item' == $_POST['gt_action'] ) {

				// If no item is selected
				if ( '' == $glance ) {
					$this->notices[] = array( 'message' => 'You must select an item to remove.', 'class' => 'error' );
					$success = false;
				}
				// Otherwise, remove submitted item
				else {

					// Remove item from glance_that user meta
					unset( $this->glances[ $glance ] );

					// Update the option
					update_user_meta( $current_user->ID, 'glance_that', $this->glances );

					// Display notices
					if ( in_array( $glance, $post_types ) ) {
						$this->notices[] = array( 'message' => '<strong>' . esc_html( get_post_type_object( $glance )->labels->name, 2 ) . '</strong> were successfully removed from your glances.', 'class' => 'success' );
					} elseif ( 'user' == $glance ) {
						$this->notices[] = array( 'message' => '<strong>Users</strong> were successfully removed from your glances.', 'class' => 'success' );
					} elseif ( 'plugin' == $glance ) {
						$this->notices[] = array( 'message' => '<strong>Plugins</strong> were successfully removed from your glances.', 'class' => 'success' );
					} elseif ( 'comment' == $glance ) {
						$this->notices[] = array( 'message' => '<strong>Plugins</strong> were successfully removed from your glances.', 'class' => 'success' );
					} elseif ( 'user_request-export_personal_data' == $glance ) {
						$this->notices[] = array( 'message' => '<strong>Data Export Requests</strong> were successfully removed from your glances.', 'class' => 'success' );
					} elseif ( 'user_request-remove_personal_data' == $glance ) {
						$this->notices[] = array( 'message' => '<strong>Data Erasure Requests</strong> were successfully removed from your glances.', 'class' => 'success' );
					} elseif ( 'gravityform' == $glance ) {
						$this->notices[] = array( 'message' => '<strong>Gravity Forms</strong> were successfully removed from your glances.', 'class' => 'success' );
					} elseif ( 'formidableform' == $glance ) {
						$this->notices[] = array( 'message' => '<strong>Formidable Forms</strong> were successfully removed from your glances.', 'class' => 'success' );
					} elseif ( 'ninjaform' == $glance ) {
						$this->notices[] = array( 'message' => '<strong>Ninja Forms</strong> were successfully removed from your glances.', 'class' => 'success' );
					}

					$success = true;

				}

			}

		}

		// generate the response
		$response = array( 'success' => $success, 'notice' => $this->show_notices(), 'glance' => $glance, 'elements' => $this->customize_items() );

		wp_send_json( $response );

	} // end process_form

	/**
	 * Process any responses to the displayed notices.
	 */
	public function show_notices() {

		$message = '';

		if ( ! empty( $this->notices ) ) {
			foreach ( $this->notices as $key => $notice ) {
				if ( 'error' == $notice['class'] )
					$message = '<div class="notice notice-error gt-message"><p><strong>' . $notice['message'] . '</strong></p></div>';
				elseif ( 'warning' == $notice['class'] )
					$message = '<div class="notice notice-warning gt-message">' . $notice['message'] . '</div>';
				elseif ( 'info' == $notice['class'] )
					$message = '<div class="notice notice-info gt-message">' . $notice['message'] . '</div>';
				else
					$message = '<div class="notice notice-success fade gt-message"><p>' . $notice['message'] . '</p></div>';
			}
		}

		return $message;

	} // end show_notices

	/**
	 * Get the categorized array of dashicons.
	 */
	public function get_dashicons() {

		// Allow users to filter available iconset
		$options = apply_filters( 'gt_dashicons', array() );

		// if dashicon set has been provided by user, replace the default dashicon set
		if ( ! empty( $options ) ) {

			$dashicons = array();

			foreach ( $options as $title ) {
				$code = $this->get_icon_code( $title );
				$dashicons[ $code ] = $title;
			}

			return $dashicons;

		} else {

			return $this->icons['dashicons'];

		}

	} // end get_dashicons

	/**
	 * Get the icons.
	 */
	public function get_icons() {

		return array(
			'dashicons' => array(
				'f333' => 'menu',
				'f228' => 'menu-alt',
				'f329' => 'menu-alt2',
				'f349' => 'menu-alt3',
				'f319' => 'admin-site',
				'f11d' => 'admin-site-alt',
				'f11e' => 'admin-site-alt2',
				'f11f' => 'admin-site-alt3',
				'f226' => 'dashboard',
				'f109' => 'admin-post',
				'f104' => 'admin-media',
				'f103' => 'admin-links',
				'f105' => 'admin-page',
				'f101' => 'admin-comments',
				'f100' => 'admin-appearance',
				'f106' => 'admin-plugins',
				'f485' => 'plugins-checked',
				'f110' => 'admin-users',
				'f107' => 'admin-tools',
				'f108' => 'admin-settings',
				'f112' => 'admin-network',
				'f102' => 'admin-home',
				'f111' => 'admin-generic',
				'f148' => 'admin-collapse',
				'f536' => 'filter',
				'f540' => 'admin-customizer',
				'f541' => 'admin-multisite',
				'f119' => 'welcome-write-blog',
				'f133' => 'welcome-add-page',
				'f115' => 'welcome-view-site',
				'f116' => 'welcome-widgets-menus',
				'f117' => 'welcome-comments',
				'f118' => 'welcome-learn-more',
				'f123' => 'format-aside',
				'f128' => 'format-image',
				'f161' => 'format-gallery',
				'f126' => 'format-video',
				'f130' => 'format-status',
				'f122' => 'format-quote',
				'f125' => 'format-chat',
				'f127' => 'format-audio',
				'f306' => 'camera',
				'f129' => 'camera-alt',
				'f232' => 'images-alt',
				'f233' => 'images-alt2',
				'f234' => 'video-alt',
				'f235' => 'video-alt2',
				'f236' => 'video-alt3',
				'f501' => 'media-archive',
				'f500' => 'media-audio',
				'f499' => 'media-code',
				'f498' => 'media-default',
				'f497' => 'media-document',
				'f496' => 'media-interactive',
				'f495' => 'media-spreadsheet',
				'f491' => 'media-text',
				'f490' => 'media-video',
				'f492' => 'playlist-audio',
				'f493' => 'playlist-video',
				'f522' => 'controls-play',
				'f523' => 'controls-pause',
				'f519' => 'controls-forward',
				'f517' => 'controls-skipforward',
				'f518' => 'controls-back',
				'f516' => 'controls-skipback',
				'f515' => 'controls-repeat',
				'f521' => 'controls-volumeon',
				'f520' => 'controls-volumeoff',
				'f121' => 'text-page',
				'f165' => 'image-crop',
				'f531' => 'image-rotate',
				'f166' => 'image-rotate-left',
				'f167' => 'image-rotate-right',
				'f168' => 'image-flip-vertical',
				'f169' => 'image-flip-horizontal',
				'f533' => 'image-filter',
				'f171' => 'undo',
				'f172' => 'redo',
				'f200' => 'editor-bold',
				'f201' => 'editor-italic',
				'f203' => 'editor-ul',
				'f204' => 'editor-ol',
				'f12c' => 'editor-ol-rtl',
				'f205' => 'editor-quote',
				'f206' => 'editor-alignleft',
				'f207' => 'editor-aligncenter',
				'f208' => 'editor-alignright',
				'f209' => 'editor-insertmore',
				'f210' => 'editor-spellcheck',
				'f211' => 'editor-expand',
				'f506' => 'editor-contract',
				'f212' => 'editor-kitchensink',
				'f213' => 'editor-underline',
				'f214' => 'editor-justify',
				'f215' => 'editor-textcolor',
				'f216' => 'editor-paste-word',
				'f217' => 'editor-paste-text',
				'f218' => 'editor-removeformatting',
				'f219' => 'editor-video',
				'f220' => 'editor-customchar',
				'f221' => 'editor-outdent',
				'f222' => 'editor-indent',
				'f223' => 'editor-help',
				'f224' => 'editor-strikethrough',
				'f225' => 'editor-unlink',
				'f320' => 'editor-rtl',
				'f10c' => 'editor-ltr',
				'f474' => 'editor-break',
				'f475' => 'editor-code',
				'f476' => 'editor-paragraph',
				'f535' => 'editor-table',
				'f135' => 'align-left',
				'f136' => 'align-right',
				'f134' => 'align-center',
				'f138' => 'align-none',
				'f160' => 'lock',
				'f528' => 'unlock',
				'f145' => 'calendar',
				'f508' => 'calendar-alt',
				'f177' => 'visibility',
				'f530' => 'hidden',
				'f173' => 'post-status',
				'f464' => 'edit',
				'f182' => 'trash',
				'f537' => 'sticky',
				'f504' => 'external',
				'f142' => 'arrow-up',
				'f140' => 'arrow-down',
				'f139' => 'arrow-right',
				'f141' => 'arrow-left',
				'f342' => 'arrow-up-alt',
				'f346' => 'arrow-down-alt',
				'f344' => 'arrow-right-alt',
				'f340' => 'arrow-left-alt',
				'f343' => 'arrow-up-alt2',
				'f347' => 'arrow-down-alt2',
				'f345' => 'arrow-right-alt2',
				'f341' => 'arrow-left-alt2',
				'f156' => 'sort',
				'f229' => 'leftright',
				'f503' => 'randomize',
				'f163' => 'list-view',
				'f164' => 'exerpt-view',
				'f509' => 'grid-view',
				'f545' => 'move',
				'f237' => 'share',
				'f240' => 'share-alt',
				'f242' => 'share-alt2',
				'f301' => 'twitter',
				'f303' => 'rss',
				'f465' => 'email',
				'f466' => 'email-alt',
				'f467' => 'email-alt2',
				'f304' => 'facebook',
				'f305' => 'facebook-alt',
				'f12d' => 'instagram',
				'f462' => 'googleplus',
				'f325' => 'networking',
				'f120' => 'wordpress',
				'f324' => 'wordpress-alt',
				'f308' => 'hammer',
				'f309' => 'art',
				'f310' => 'migrate',
				'f311' => 'performance',
				'f483' => 'universal-access',
				'f507' => 'universal-access-alt',
				'f486' => 'tickets',
				'f484' => 'nametag',
				'f481' => 'clipboard',
				'f487' => 'heart',
				'f488' => 'megaphone',
				'f489' => 'schedule',
				'f10d' => 'tide',
				'f124' => 'rest-api',
				'f13a' => 'code-standards',
				'f452' => 'buddicons-activity',
				'f12b' => 'buddicons-bbpress-logo',
				'f448' => 'buddicons-buddypress-logo',
				'f453' => 'buddicons-community',
				'f449' => 'buddicons-forums',
				'f454' => 'buddicons-friends',
				'f456' => 'buddicons-groups',
				'f457' => 'buddicons-pm',
				'f451' => 'buddicons-replies',
				'f450' => 'buddicons-topics',
				'f455' => 'buddicons-tracking',
				'f157' => 'pressthis',
				'f463' => 'update',
				'f113' => 'update-alt',
				'f180' => 'screenoptions',
				'f348' => 'info',
				'f174' => 'cart',
				'f175' => 'feedback',
				'f176' => 'cloud',
				'f326' => 'translation',
				'f323' => 'tag',
				'f318' => 'category',
				'f480' => 'archive',
				'f479' => 'tagcloud',
				'f478' => 'text',
				'f147' => 'yes',
				'f12a' => 'yes-alt',
				'f158' => 'no',
				'f335' => 'no-alt',
				'f132' => 'plus',
				'f502' => 'plus-alt',
				'f460' => 'minus',
				'f153' => 'dismiss',
				'f159' => 'marker',
				'f155' => 'star-filled',
				'f459' => 'star-half',
				'f154' => 'star-empty',
				'f227' => 'flag',
				'f534' => 'warning',
				'f230' => 'location',
				'f231' => 'location-alt',
				'f178' => 'vault',
				'f332' => 'shield',
				'f334' => 'shield-alt',
				'f468' => 'sos',
				'f179' => 'search',
				'f181' => 'slides',
				'f183' => 'analytics',
				'f184' => 'chart-pie',
				'f185' => 'chart-bar',
				'f238' => 'chart-line',
				'f239' => 'chart-area',
				'f307' => 'groups',
				'f338' => 'businessman',
				'f12f' => 'businesswoman',
				'f12e' => 'businessperson',
				'f336' => 'id',
				'f337' => 'id-alt',
				'f312' => 'products',
				'f313' => 'awards',
				'f314' => 'forms',
				'f473' => 'testimonial',
				'f322' => 'portfolio',
				'f330' => 'book',
				'f331' => 'book-alt',
				'f316' => 'download',
				'f317' => 'upload',
				'f321' => 'backup',
				'f469' => 'clock',
				'f339' => 'lightbulb',
				'f482' => 'microphone',
				'f472' => 'desktop',
				'f547' => 'laptop',
				'f471' => 'tablet',
				'f470' => 'smartphone',
				'f525' => 'phone',
				'f510' => 'index-card',
				'f511' => 'carrot',
				'f512' => 'building',
				'f513' => 'store',
				'f514' => 'album',
				'f527' => 'palmtree',
				'f524' => 'tickets-alt',
				'f526' => 'money',
				'f328' => 'smiley',
				'f542' => 'thumbs-down',
				'f529' => 'thumbs-up',
				'f538' => 'layout',
				'f546' => 'paperclip',
			)
		);

	} // end get_icons

	/**
	 * Process any responses to the displayed notices.
	 */
	public function get_user_status_visibility() {

		global $current_user;
		wp_get_current_user();

		$this->status_visibility = get_user_meta( $current_user->ID, 'glance_that_status_visibility', true );

		// If user has no glances set
		if ( empty( $this->status_visibility ) ) {

			if ( get_option( 'glance_that_status_visibility_default' ) === false ) {

				$this->status_visibility = 'visible';

			} else {

				$this->status_visibility = get_option( 'glance_that_status_visibility_default' );

			}

			// Update the option
			update_user_meta( $current_user->ID, 'glance_that_status_visibility', $this->status_visibility );

		}

		$this->status_visibility = ! isset( $this->status_visibility ) ? 'visible' : $this->status_visibility;

		if ( 'visible' == $this->status_visibility && $this->options['show_all_status'] ) {
			return true;
		} else {
			return false;
		}

	} // end get_user_status_visibility

	/**
	 * Process any responses to the displayed notices.
	 */
	public function get_user_info_visibility() {

		global $current_user;
		wp_get_current_user();

		$this->info_visibility = get_user_meta( $current_user->ID, 'glance_that_info_visibility', true );

		// If user has no glances set
		if ( empty( $this->info_visibility ) ) {

			if ( get_option( 'glance_that_info_visibility_default' ) === false ) {

				$this->info_visibility = 'visible';

			} else {

				$this->info_visibility = get_option( 'glance_that_info_visibility_default' );

			}

			// Update the option
			update_user_meta( $current_user->ID, 'glance_that_info_visibility', $this->info_visibility );


		}

		$this->info_visibility = ! isset( $this->info_visibility ) ? 'visible' : $this->info_visibility;

		if ( 'visible' == $this->info_visibility ) {
			return true;
		} else {
			return false;
		}

	} // end get_user_info_visibility

	/**
	 * Process any responses to the displayed notices.
	 */
	public function get_users_glances() {

		global $current_user;
		wp_get_current_user();

		$this->glances = get_user_meta( $current_user->ID, 'glance_that', TRUE );

		// If user has no glances set
		if ( empty( $this->glances ) ) {

			if ( get_option( 'glance_that_default' ) === false ) {

				// Define standard defaults
				$gt_default_glances = array(
					'post' => array( 'icon' => 'f109', 'sort' => 1 ),
					'page' => array( 'icon' => 'f105', 'sort' => 2 ),
					'comment' => array( 'icon' => 'f101', 'sort' => 3 ),
					);

			} else {

				$gt_default_glances = get_option( 'glance_that_default' );

			}

			// Set default glances
			$this->glances = apply_filters( 'gt_default_glances', $gt_default_glances, $current_user->ID );

			// Update the option
			update_user_meta( $current_user->ID, 'glance_that', $this->glances );

		}

		// Set an indexed array of glances to reference when sorting
		$this->glances_indexed = array();
		foreach ( $this->glances as $glance => $data ) {
			$this->glances_indexed[] = array(
				'glance' => $glance,
				'data' => $data,
				);
		}

	} // end get_users_glances

	/**
	 * Action target that disperses default glances
	 */
	public function default_glances() {

		global $current_user;
		wp_get_current_user();

		// Get default set action
		$action = $_POST['gt_action'];

		$glances = get_user_meta( $current_user->ID, 'glance_that', TRUE );
		$status_visibility = get_user_meta( $current_user->ID, 'glance_that_status_visibility', TRUE );
		$info_visibility = get_user_meta( $current_user->ID, 'glance_that_info_visibility', TRUE );

		// Set default glances
		update_option( 'glance_that_default', $glances );
		update_option( 'glance_that_status_visibility_default', $status_visibility );
		update_option( 'glance_that_info_visibility_default', $info_visibility );

		if ( 'all' == $action ) {

			$users = get_users();

			foreach ( $users as $user ) {

				// Update the option
				update_user_meta( $user->ID, 'glance_that', $glances );
				update_user_meta( $user->ID, 'glance_that_status_visibility', $status_visibility );
				update_user_meta( $user->ID, 'glance_that_info_visibility', $info_visibility );

			}

			$this->notices[] = array( 'message' => 'Current glance configuration has been successfully applied to all existing and new users.', 'class' => 'success' );

		} else {

			$this->notices[] = array( 'message' => 'Current glance configuration will be applied to new users.', 'class' => 'success' );

		}

		// generate the response
		$response = array( 'success' => true, 'notice' => $this->show_notices() );

		wp_send_json( $response );

	} // end sort_glances

	/**
	 * Action target that sorts glances
	 */
	public function sort_glances() {

		// Get newly sorted glances array
		$order = $_POST['gt_sort'];

		// Remove any items not belonging to Glance That
		foreach ( $order as $key => $value) {
			if ( '' == $value ) {
				unset( $order[ $key ] );
			}
		}

		// Rekey the array
		$order = array_values( $order );

		foreach ( $order as $key => $gt_index ) {
			foreach ( $this->glances_indexed as $index => $data ) {
				$gt_index = str_replace( 'gt_', '', $gt_index );
				if ( ( $index + 1 ) == intval( $gt_index ) ) {
					$this->glances[ $data['glance'] ]['sorted'] = intval( $key );
				}
			}
		}

		// Update the option
		update_user_meta( intval( $_POST['userID'] ), 'glance_that', $this->glances );

		// generate the response
		$response = array( 'success' => true, 'order' => $order );

		wp_send_json( $response );

	} // end sort_glances

	/**
	 * Action target that sets status visibility
	 */
	public function toggle_status_visibility() {

		global $current_user;
		wp_get_current_user();

		// Get visibility action
		$action = $_POST['gt_action'];

		// Update the option
		if ( 'hide' == $action ) {

			update_user_meta( intval( $current_user->ID ), 'glance_that_status_visibility', 'hidden' );
			$response = array( 'success' => true );

		} else if ( 'show' == $action ) {

			update_user_meta( intval( $current_user->ID ), 'glance_that_status_visibility', 'visible' );
			$response = array( 'success' => true );

		} else {

			$response = array( 'success' => false );

		}

		wp_send_json( $response );

	} // end toggle_status_visibility

	/**
	 * Action target that sets info visibility
	 */
	public function toggle_info_visibility() {

		global $current_user;
		wp_get_current_user();

		// Get visibility action
		$action = $_POST['gt_action'];

		// Update the option
		if ( 'hide' == $action ) {

			update_user_meta( intval( $current_user->ID ), 'glance_that_info_visibility', 'hidden' );
			$response = array( 'success' => true );

		} else if ( 'show' == $action ) {

			update_user_meta( intval( $current_user->ID ), 'glance_that_info_visibility', 'visible' );
			$response = array( 'success' => true );

		} else {

			$response = array( 'success' => false );

		}

		wp_send_json( $response );

	} // end toggle_info_visibility

	/**
	 * Overrides status icons if defined by Post State Tags plugin
	 */
	public function check_override_status_icons() {

		if ( is_plugin_active( 'post-state-tags/post-state-tags.php' ) ) {

			// Add styling for iconset
			$html = '<style type="text/css">';

			$pst_default = get_option( 'bb-pst-default' );
			$pst_custom = get_option( 'bb-pst-custom' );

			$post_state_tags_options = array(
				'publish' => $pst_default['bb-pst-publish-icon'],
				'future'  => $pst_default['bb-pst-future-icon'],
				'draft'   => $pst_default['bb-pst-draft-icon'],
				'pending' => $pst_default['bb-pst-pending-icon'],
				'private' => $pst_default['bb-pst-private-icon'],
				'trash'   => $pst_default['bb-pst-trash-icon'],
			);

			if ( isset( $pst_custom['bb-pst-archive-icon'] ) ) {
				$post_state_tags_options['archive'] = $pst_custom['bb-pst-archive-icon'];
			}

			foreach ( $post_state_tags_options as $status => $icon ) {

				if ( false !== $icon && '' != $icon ) {

					$html .= '#dashboard_right_now div.gt-status a.gt-' . $status . ':before { content: \'\\' . $this->get_icon_code( str_replace( 'dashicons-', '', $icon ) ) . '\'; }';

				}

			}

			$html .= '</style>';

			echo $html;

		}

	} // end check_override_status_icons

	/**
	 * Retrieve dashicon character code from dashicon name
	 */
	public function get_icon_code( $icon, $family = 'dashicons' ) {

		if ( $family != 'custom' ) {

			foreach ( $this->icons[ $family ] as $code => $title ) {
				if ( $icon == $title ) {
					return $code;
					break;
				}
			}

		} else {

			return $icon;

		}

	} // end get_dashicon_code

} // end class
