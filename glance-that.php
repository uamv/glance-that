<?php
/**
 * Plugin Name: Glance That
 * Plugin URI: http://typewheel.xyz/wp/
 * Description: Adds content control to At a Glance on the Dashboard
 * Version: 3.7
 * Author: uamv
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
 * @version 3.7
 * @author uamv
 * @copyright Copyright (c) 2013-2017, uamv
 * @link http://typewheel.xyz/wp/
 * @license http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 */

/**
 * Define plugins globals.
 */

define( 'GT_VERSION', '3.7' );
define( 'GT_DIR_PATH', plugin_dir_path( __FILE__ ) );
define( 'GT_DIR_URL', plugin_dir_url( __FILE__ ) );

// Determine whether items with zero published items are shown
! defined( 'GT_SHOW_ZERO_COUNT' ) ? define( 'GT_SHOW_ZERO_COUNT', TRUE ) : FALSE;

// Determine wehether add new post item is shown
! defined( 'GT_SHOW_ADD_NEW') ? define( 'GT_SHOW_ADD_NEW', TRUE ) : FALSE;

// Determine whether statuses are to be shown (keep GT_SHOW_ALL for backwards compatibility - pre v2.1)
( ! defined( 'GT_SHOW_ALL_STATUS' ) || ( defined( 'GT_SHOW_ALL' ) && GT_SHOW_ALL ) ) ? define( 'GT_SHOW_ALL_STATUS', TRUE ) : FALSE;

// Determine whether statuses with zero items are shown
! defined( 'GT_SHOW_ZERO_COUNT_STATUS' ) ? define( 'GT_SHOW_ZERO_COUNT_STATUS', FALSE ) : FALSE;

// Determine whether advanced plugin statuses are shown
! defined( 'GT_SHOW_MUSTUSE' ) ? define( 'GT_SHOW_MUSTUSE', FALSE ) : FALSE;
! defined( 'GT_SHOW_DROPINS' ) ? define( 'GT_SHOW_DROPINS', FALSE ) : FALSE;

// Determine whether all dashicons are to be shown (otherwise un-post-type-like icons are removed)
! defined( 'GT_SHOW_ALL_DASHICONS' ) ? define( 'GT_SHOW_ALL_DASHICONS', FALSE ) : FALSE;

// Set a capability required for editing of one's glances and admining all glances
! defined( 'GT_EDIT_GLANCES' ) ? define( 'GT_EDIT_GLANCES', 'read' ) : FALSE;
! defined( 'GT_ADMIN_GLANCES' ) ? define( 'GT_ADMIN_GLANCES', 'edit_dashboard' ) : FALSE;

/**
 * Get instance of class if in admin.
 */

global $pagenow;

if ( is_admin() && ( 'index.php' == $pagenow || 'admin-ajax.php' == $pagenow ) ) {
	Glance_That::get_instance();
}

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
	 * Plugin version, used for cache-busting of style and script file references.
	 *
	 * @since   1.0
	 *
	 * @var     string
	 */
	protected $version = GT_VERSION;

	/**
	 * Instance of this class.
	 *
	 * @since    1.0
	 *
	 * @var      object
	 */
	protected static $instance = null;

	/**
	 * Notices.
	 *
	 * @since    1.0
	 *
	 * @var      array
	 */
	protected $notices;

	/**
	 * glances.
	 *
	 * @since    1.0
	 *
	 * @var      array
	 */
	protected $glances;

	/**
	 * glances.
	 *
	 * @since    1.0
	 *
	 * @var      array
	 */
	protected $glances_indexed;

	/**
	 * editable
	 *
	 * @since    2.4
	 *
	 * @var      array
	 */
	protected $editable;

	/**
	 * editable
	 *
	 * @since    2.4
	 *
	 * @var      array
	 */
	protected $adminable;

	/*---------------------------------------------------------------------------------*
	 * Consturctor
	 *---------------------------------------------------------------------------------*/

	/**
	 * Initialize the plugin by setting localization, filters, and administration functions.
	 *
	 * @since     1.0
	 */
	private function __construct() {

		add_action( 'plugins_loaded', array( $this, 'check_user_cap' ) );

		// Process the form
		add_action( 'init', array( $this, 'get_users_glances' ) );

		// Load the administrative Stylesheets and JavaScript
		add_action( 'admin_enqueue_scripts', array( $this, 'add_stylesheets_and_javascript' ) );

		// Add custom post types to end of At A Glance table
		add_filter( 'dashboard_glance_items', array( $this, 'customize_items' ), 10, 1 );

		// Account for icons selected via Post State Tags plugin
		add_action( 'admin_head', array( $this, 'check_override_status_icons' ) );

		// Add post statuses to native types
		add_action( 'admin_footer', array( $this, 'add_sort_order' ) );

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

		// Process the form
		add_action( 'wp_ajax_add_remove_glance', array( $this, 'process_form' ) );

		// Set custom labels
		add_filter( 'gt_labels', array( $this, 'customize_labels' ), 10, 3 );

	} // end constructor

	/*---------------------------------------------------------------------------------*
	 * Public Functions
	 *---------------------------------------------------------------------------------*/

	/**
	 * Return an instance of this class.
	 *
	 * @since     1.0
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
	 *
	 * @since    1.0
	 */
	public function check_user_cap() {

		$this->editable = current_user_can( GT_EDIT_GLANCES ) ? TRUE : FALSE;
		$this->adminable = current_user_can( GT_ADMIN_GLANCES ) ? TRUE : FALSE;

	} // end check_user_cap

	/**
	 * Registers the plugin's administrative stylesheets and JavaScript
	 *
	 * @since    1.0
	 */
	public function add_stylesheets_and_javascript() {

		wp_enqueue_style( 'glance', GT_DIR_URL . 'glance.css', array(), GT_VERSION );
		wp_enqueue_script( 'glance-that', GT_DIR_URL . 'glance.js', array( 'jquery' ), GT_VERSION );
		wp_localize_script( 'glance-that', 'Glance', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) ) );

	} // end add_stylesheets_and_javascript

	/**
	 * Adds order to list item for use by sortable
	 *
	 * @since    1.4
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
	 * Adds settings control script
	 *
	 * @since    1.4
	 */
	public function settings_control() {

		if ( apply_filters( 'gt_show_all_status', GT_SHOW_ALL_STATUS ) ) {

			$visibility_action = $this->get_user_status_visibility() ? 'hide' : 'show';
			$visibility_style = $this->get_user_status_visibility() ? 'style="display:none;"' : '';
			$hidden_style = ! $this->get_user_status_visibility() ? 'style="display:none;"' : '';

			$buttons = '<div id="gt-settings-wrapper"><button id="gt-toggle-status" type="button" class="button-link gt-settings" data-action="' . $visibility_action . '"><span class="dashicons dashicons-visibility" ' . $visibility_style . ' title="Click to Reveal More Glances"></span><span class="dashicons dashicons-hidden" ' . $hidden_style . ' title="Click to Hide"></span></button>';

			if ( $this->editable || $this->adminable ) {
				$buttons .= '<button id="gt-toggle-form" type="button" class="button-link gt-settings" data-gt-form="closed"><span class="dashicons dashicons-filter" title="Click to Add or Remove Glances"></span></button>';
			}

			if ( $this->adminable ) {
				$buttons .= '<button id="gt-save-defaults" type="button" class="button-link gt-settings"><span class="dashicons dashicons-migrate" title="Save as Default Setup for All Users"></span></button>';
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
	 *
	 * @since    1.0
	 */
	public function label( $item, $label, $count ) {

		return esc_html( apply_filters( 'gt_labels', $label, $item, $count ) );

	} // end label

	/**
	 * Adds custom post types to the end of At a Glance table
	 *
	 * @since    1.0
	 */
	public function customize_items( $elements = array() ) {

		foreach ( $elements as $key => $element ) {
			if ( strpos( $element, 'give_forms' ) > 1 ) {
				unset( $elements[ $key ] );
			}
		}

		$this->get_users_glances();
		$status_visibility = $this->get_user_status_visibility() ? '' : ' style="display: none;"';

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
							case 'theme':
								$num_themes = count( get_themes() );
								if ( $num_themes && current_user_can( 'switch_themes' ) ) {
									$text = _n( '%s ' . $this->label( $item, 'Theme', $num_themes ), '%s ' . $this->label( $item, 'Themes', $num_themes ), $num_themes );

									$text = sprintf( $text, number_format_i18n( $num_themes ) );

									if ( current_user_can( 'install_themes' ) && apply_filters( 'gt_show_add_new', GT_SHOW_ADD_NEW ) ) {
										$new_theme = '<a href="theme-install.php" class="gt-add-new"><span class="dashicons dashicons-plus" title="Add New ' . $this->label( $item, 'Theme', 1 ) . '"></span></a>';
									} else {
										$new_theme = '';
									}

									if ( apply_filters( 'gt_show_all_status', GT_SHOW_ALL_STATUS ) ) {
										$statuses = '<div class="gt-statuses"' . $status_visibility . '>';
											$moderation = count( get_theme_updates() ) > 0 ? 'gt-moderate' : '';
											$statuses .= ( count( get_theme_updates() ) > 0 || apply_filters( 'gt_show_zero_count_status', GT_SHOW_ZERO_COUNT_STATUS ) ) ? '<div class="gt-status ' . $moderation . '"><a href="update-core.php#update-themes-table" class="gt-update" title="Update Available">' . count( get_theme_updates() ) . '</a></div>' : FALSE;
										$statuses .= '</div>';
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

								if ( $num_posts && ( $num_posts->inherit || apply_filters( 'gt_show_zero_count', GT_SHOW_ZERO_COUNT ) ) && current_user_can( get_post_type_object( $item )->cap->edit_posts ) ) {
									$text = _n( '%s ' . $this->label( $item, get_post_type_object( $item )->labels->singular_name, $num_posts->inherit ), '%s ' . $this->label( $item, get_post_type_object( $item )->labels->name, $num_posts->inherit ), $num_posts->inherit );

									$text = sprintf( $text, number_format_i18n( $num_posts->inherit ) );

									if ( current_user_can( 'upload_files' ) && apply_filters( 'gt_show_add_new', GT_SHOW_ADD_NEW ) ) {
										$new_attachment = '<a href="media-new.php" class="gt-add-new"><span class="dashicons dashicons-plus" title="Add New ' . $this->label( $item, get_post_type_object( $item )->labels->singular_name, 1 ) . '"></span></a>';
									} else {
										$new_attachment = '';
									}

									if ( apply_filters( 'gt_show_all_status', GT_SHOW_ALL_STATUS ) ) {
										$statuses = '<div class="gt-statuses"' . $status_visibility . '>';
										$statuses .= ( $unattached > 0 || apply_filters( 'gt_show_zero_count_status', GT_SHOW_ZERO_COUNT_STATUS ) ) ? '<div class="gt-status"><a href="upload.php?detached=1" class="gt-unattached" title="Unattached ' . $this->label( $item, get_post_type_object( $item )->labels->singular_name, $unattached ) . '">' . $unattached . '</a></div>' : FALSE;
										$statuses .= '</div>';
									}

									ob_start();
										printf( '<div class="' . $classes . '" data-order="gt_' . ( $key + 1 ) . '"><style type="text/css">#dashboard_right_now li a[data-gt="%1$s"]:before{content:\'\\' . $options['icon'] . '\';}</style><a data-gt="%1$s" href="upload.php" class="glance-that" title="All ' . $this->label( $item, get_post_type_object( $item )->labels->name, 2 ) . '">%2$s</a>%4$s%3$s</div>', $item, $text, $statuses, $new_attachment );
									$elements[] = ob_get_clean();
								}
								break;

							case 'comment':
								$num_comments = wp_count_comments();

								if ( ( $num_comments->approved || apply_filters( 'gt_show_zero_count', GT_SHOW_ZERO_COUNT ) ) && current_user_can( 'moderate_comments' ) && current_user_can( 'edit_posts' ) ) {
									$text = _n( '%s ' . $this->label( $item, 'Comment', $num_comments->approved ), '%s ' . $this->label( $item, 'Comments', $num_comments->approved ), $num_comments->approved );

									$text = sprintf( $text, number_format_i18n( $num_comments->approved ) );

									if ( apply_filters( 'gt_show_all_status', GT_SHOW_ALL_STATUS ) ) {
										$moderation = intval( $num_comments->moderated ) > 0 ? 'gt-moderate' : '';
										$statuses = '<div id="gt-statuses-comments" class="gt-statuses"' . $status_visibility . '>';
										$statuses .= ( $num_comments->moderated > 0 || apply_filters( 'gt_show_zero_count_status', GT_SHOW_ZERO_COUNT_STATUS ) ) ? '<div class="gt-status ' . $moderation . '"><a href="edit-comments.php?comment_status=moderated" class="gt-pending" title="Pending">' . $num_comments->moderated . '</a></div>' : FALSE;
										$statuses .= ( $num_comments->spam > 0 || apply_filters( 'gt_show_zero_count_status', GT_SHOW_ZERO_COUNT_STATUS ) ) ? '<div class="gt-status"><a href="edit-comments.php?comment_status=spam" class="gt-spam" title="Spam">' . $num_comments->spam . '</a></div>' : FALSE;
										$statuses .= ( $num_comments->trash > 0 || apply_filters( 'gt_show_zero_count_status', GT_SHOW_ZERO_COUNT_STATUS ) ) ? '<div class="gt-status"><a href="edit-comments.php?comment_status=trash" class="gt-trash" title="Trash">' . $num_comments->trash . '</a></div>' : FALSE;
										$statuses .= '</div>';
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
								}

								if ( apply_filters( 'show_advanced_plugins', true, 'dropins' ) ) {
									$plugin_stats['dropins'] = count( get_dropins() );
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

								if ( current_user_can( 'install_plugins' ) && apply_filters( 'gt_show_add_new', GT_SHOW_ADD_NEW ) ) {
									$new_plugin = '<a href="plugin-install.php" class="gt-add-new"><span class="dashicons dashicons-plus" title="Add New ' . $this->label( $item, 'Plugin', 1 ) . '"></span></a>';
								} else {
									$new_plugin = '';
								}

								// Display plugin glance
								if ( ( $plugin_stats['all'] || apply_filters( 'gt_show_zero_count', GT_SHOW_ZERO_COUNT ) ) && current_user_can( 'activate_plugins' ) ) {
									$text = _n( '%s ' . $this->label( $item, 'Plugin', $plugin_stats['all'] ), '%s ' . $this->label( $item, 'Plugins', $plugin_stats['all'] ), $plugin_stats['all'] );

									$text = sprintf( $text, number_format_i18n( $plugin_stats['all'] ) );

									if ( apply_filters( 'gt_show_all_status', GT_SHOW_ALL_STATUS ) ) {
										$statuses = '<div class="gt-statuses"' . $status_visibility . '>';
											$statuses .= ( $plugin_stats['active'] > 0 || apply_filters( 'gt_show_zero_count_status', GT_SHOW_ZERO_COUNT_STATUS ) ) ? '<div class="gt-status"><a href="plugins.php?plugin_status=active" class="gt-active" title="Active">' . $plugin_stats['active'] . '</a></div>' : FALSE;
											$statuses .= ( $plugin_stats['inactive'] > 0 || apply_filters( 'gt_show_zero_count_status', GT_SHOW_ZERO_COUNT_STATUS ) ) ? '<div class="gt-status"><a href="plugins.php?plugin_status=inactive" class="gt-inactive" title="Inactive">' . $plugin_stats['inactive'] . '</a></div>' : FALSE;
											$statuses .= ( $plugin_stats['recent'] > 0 || apply_filters( 'gt_show_zero_count_status', GT_SHOW_ZERO_COUNT_STATUS ) ) ? '<div class="gt-status"><a href="plugins.php?plugin_status=recently_activated" class="gt-recent" title="Recently Active">' . $plugin_stats['recent'] . '</a></div>' : FALSE;
											$moderation = intval( $plugin_stats['update'] ) > 0 ? 'gt-moderate' : '';
											$statuses .= ( $plugin_stats['update'] > 0 || apply_filters( 'gt_show_zero_count_status', GT_SHOW_ZERO_COUNT_STATUS ) ) ? '<div class="gt-status ' . $moderation . '"><a href="plugins.php?plugin_status=upgrade" class="gt-update" title="Update Available">' . $plugin_stats['update'] . '</a></div>' : FALSE;
											$statuses .= ( null !== $plugin_stats['favorites'] && ( $plugin_stats['favorites'] > 0 || apply_filters( 'gt_show_zero_count_status', GT_SHOW_ZERO_COUNT_STATUS ) ) ) ? '<div class="gt-status"><a href="plugin-install.php?tab=favorites" class="gt-favorites" title="Favorites: ' . $user . '">' . $plugin_stats['favorites'] . '</a></div>' : FALSE;
											$statuses .= ( $plugin_stats['mustuse'] > 0 && apply_filters( 'gt_show_mustuse', GT_SHOW_MUSTUSE ) ) ? '<div class="gt-status"><a href="plugins.php?plugin_status=mustuse" class="gt-mustuse" title="Must-Use">' . $plugin_stats['mustuse'] . '</a></div>' : FALSE;
											$statuses .= ( $plugin_stats['dropins'] > 0 && apply_filters( 'gt_show_', GT_SHOW_DROPINS ) ) ? '<div class="gt-status"><a href="plugins.php?plugin_status=dropins" class="gt-dropins" title="Drop-ins">' . $plugin_stats['dropins'] . '</a></div>' : FALSE;
										$statuses .= '</div>';
									}

									ob_start();
										printf( '<div class="' . $classes . '" data-order="gt_' . ( $key + 1 ) . '"><style type="text/css">#dashboard_right_now li a[data-gt="%1$s"]:before{content:\'\\' . $options['icon'] . '\';}</style><div class="gt-published"><a data-gt="%1$s" href="plugins.php" class="glance-that" title="All ' . $this->label( $item, 'Plugins', 2 ) . '">%2$s</a>%4$s</div>%3$s</div>', $item, $text, $statuses, $new_plugin );
									$elements[] = ob_get_clean();
								}

								break;

							case 'user':
								$num_users = count_users();

								if ( current_user_can( 'create_users' ) && apply_filters( 'gt_show_add_new', GT_SHOW_ADD_NEW ) ) {
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

									if ( ( $num_forms['total'] || apply_filters( 'gt_show_zero_count', GT_SHOW_ZERO_COUNT ) ) && ( current_user_can( 'gform_full_access' ) || current_user_can( 'gravityforms_edit_forms' ) ) ) {
										$text = _n( '%s ' . $this->label( $item, 'Form', $num_forms['total'] ), '%s ' . $this->label( $item, 'Forms', $num_forms['total'] ), $num_forms['total'] );

										$text = sprintf( $text, number_format_i18n( $num_forms['total'] ) );

										if ( ( current_user_can( 'gravityforms_create_form' ) || current_user_can( 'update_core' ) ) && apply_filters( 'gt_show_add_new', GT_SHOW_ADD_NEW ) ) {
											$new_gravityform = '<a href="admin.php?page=gf_new_form" class="gt-add-new"><span class="dashicons dashicons-plus" title="Add New ' . $this->label( $item, 'Form', 1 ) . '"></span></a>';
										} else {
											$new_gravityform = '';
										}

										if ( apply_filters( 'gt_show_all_status', GT_SHOW_ALL_STATUS ) ) {
											$statuses = '<div class="gt-statuses"' . $status_visibility . '>';
												$statuses .= ( $num_forms['active'] > 0 || apply_filters( 'gt_show_zero_count_status', GT_SHOW_ZERO_COUNT_STATUS ) ) ? '<div class="gt-status"><a href="admin.php?page=gf_edit_forms&filter=active" class="gt-active" title="Active ' . $this->label( $item, 'Forms', $num_forms['active'] ) . '">' . $num_forms['active'] . '</a></div>' : FALSE;
												$statuses .= ( $num_forms['inactive'] > 0 || apply_filters( 'gt_show_zero_count_status', GT_SHOW_ZERO_COUNT_STATUS ) ) ? '<div class="gt-status"><a href="admin.php?page=gf_edit_forms&filter=inactive" class="gt-inactive" title="Inactive ' . $this->label( $item, 'Forms', $num_forms['inactive'] ) . 's">' . $num_forms['inactive'] . '</a></div>' : FALSE;
												$statuses .= ( $num_forms['trash'] > 0 || apply_filters( 'gt_show_zero_count_status', GT_SHOW_ZERO_COUNT_STATUS ) ) ? '<div class="gt-status"><a href="admin.php?page=gf_edit_forms&filter=trash" class="gt-trash" title="Trash">' . $num_forms['trash'] . '</a></div>' : FALSE;
											$statuses .= '</div>';
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

									if ( ( $num_forms->published || apply_filters( 'gt_show_zero_count', GT_SHOW_ZERO_COUNT ) ) && ( current_user_can( 'frm_view_forms' ) || current_user_can( 'frm_edit_forms' ) ) ) {
										$text = _n( '%s ' . $this->label( $item, 'Form', $num_forms->published ), '%s ' . $this->label( $item, 'Forms', $num_forms->published ), $num_forms->published );

										$text = sprintf( $text, number_format_i18n( $num_forms->published ) );

										if ( current_user_can( 'frm_edit_forms' ) && apply_filters( 'gt_show_zero_add_new', GT_SHOW_ADD_NEW ) ) {
											$new_formidableform = '<a href="admin.php?page=formidable&frm_action=new" class="gt-add-new"><span class="dashicons dashicons-plus" title="Add New ' . $this->label( $item, 'Form', 1 ) . '"></span></a>';
										} else {
											$new_formidableform = '';
										}

										if ( apply_filters( 'gt_show_all_status', GT_SHOW_ALL_STATUS ) ) {
											$statuses = '<div class="gt-statuses"' . $status_visibility . '>';
												$statuses .= ( $num_forms->template > 0 || apply_filters( 'gt_show_zero_count_status', GT_SHOW_ZERO_COUNT_STATUS ) ) ? '<div class="gt-status"><a href="admin.php?page=formidable&form_type=template" class="gt-template" title="' . $this->label( $item, 'Form', 1 ) . ' Templates">' . $num_forms->template . '</a></div>' : FALSE;
												$statuses .= ( $num_forms->draft > 0 || apply_filters( 'gt_show_zero_count_status', GT_SHOW_ZERO_COUNT_STATUS ) ) ? '<div class="gt-status"><a href="admin.php?page=formidable&form_type=draft" class="gt-draft" title="Drafts">' . $num_forms->draft . '</a></div>' : FALSE;
												$statuses .= ( $num_forms->trash > 0 || apply_filters( 'gt_show_zero_count_status', GT_SHOW_ZERO_COUNT_STATUS ) ) ? '<div class="gt-status"><a href="admin.php?page=formidable&form_type=trash" class="gt-trash" title="Trash">' . $num_forms->trash . '</a></div>' : FALSE;
											$statuses .= '</div>';
										}

										ob_start();
											printf( '<div class="' . $classes . '" data-order="gt_' . ( $key + 1 ) . '"><div class="gt-published"><a data-gt="%1$s" href="admin.php?page=formidable" class="glance-that unordered" title="All ' . $this->label( $item, 'Forms', 2 ) . '">%2$s</a>%4$s</div>%3$s</div>', $item, $text, $statuses, $new_formidableform );
										$elements[] = ob_get_clean();
									}
								}
								break;

							default:
								if ( post_type_exists( $item ) ) {
									$num_posts = wp_count_posts( $item );
									if ( $num_posts && ( $num_posts->publish || apply_filters( 'gt_show_zero_count', GT_SHOW_ZERO_COUNT ) ) && current_user_can( get_post_type_object( $item )->cap->edit_posts ) ) {
										$text = _n( '%s ' . $this->label( $item, get_post_type_object( $item )->labels->singular_name, $num_posts->publish ), '%s ' . $this->label( $item, get_post_type_object( $item )->labels->name, $num_posts->publish ), $num_posts->publish );

										$text = sprintf( $text, number_format_i18n( $num_posts->publish ) );

										if ( current_user_can( get_post_type_object( $item )->cap->edit_posts ) && apply_filters( 'gt_show_add_new', GT_SHOW_ADD_NEW ) ) {
											$new_post = '<a href="post-new.php?post_type=' . $item . '" class="gt-add-new"><span class="dashicons dashicons-plus" title="Add New ' . $this->label( $item, get_post_type_object( $item )->labels->singular_name, 1 ) . '"></span></a>';
										} else {
											$new_post = '';
										}

										if ( apply_filters( 'gt_show_all_status', GT_SHOW_ALL_STATUS ) ) {
											$statuses = '<div class="gt-statuses"' . $status_visibility . '>';
											if ( current_user_can( get_post_type_object( $item )->cap->publish_posts ) && ( $num_posts->future > 0 || apply_filters( 'gt_show_zero_count_status', GT_SHOW_ZERO_COUNT_STATUS ) ) ) {
												$statuses .= '<div class="gt-status"><a href="edit.php?post_type=' . $item . '&post_status=future" class="gt-future" title="Scheduled">' . $num_posts->future . '</a></div>';
											}
											if ( current_user_can( get_post_type_object( $item )->cap->edit_posts ) && ( $num_posts->pending > 0 || apply_filters( 'gt_show_zero_count_status', GT_SHOW_ZERO_COUNT_STATUS ) ) ) {
												$moderation = intval( $num_posts->pending ) > 0 ? 'gt-moderate' : '';
												$statuses .= '<div class="gt-status ' . $moderation . '"><a href="edit.php?post_type=' . $item . '&post_status=pending" class="gt-pending" title="Pending">' . $num_posts->pending . '</a></div>';
											}
											if ( current_user_can( get_post_type_object( $item )->cap->edit_posts && ( $num_posts->draft > 0 || apply_filters( 'gt_show_zero_count_status', GT_SHOW_ZERO_COUNT_STATUS ) ) ) ) {
												$statuses .= '<div class="gt-status"><a href="edit.php?post_type=' . $item . '&post_status=draft" class="gt-draft" title="Drafts">' . $num_posts->draft . '</a></div>';
											}
											if ( ( ( ! isset( get_post_type_object( $item )->cap->edit_private_posts ) && current_user_can( 'edit_private_posts' ) ) || current_user_can( get_post_type_object( $item )->cap->edit_private_posts ) ) && ( $num_posts->private > 0 || apply_filters( 'gt_show_zero_count_status', GT_SHOW_ZERO_COUNT_STATUS ) ) ) {
												$statuses .= '<div class="gt-status"><a href="edit.php?post_type=' . $item . '&post_status=private" class="gt-private" title="Private">' . $num_posts->private . '</a></div>';
											}
											if ( $this->is_archive_active() && ( ( ! isset( get_post_type_object( $item )->cap->read_private_posts ) && current_user_can( 'read_private_posts' ) ) || current_user_can( get_post_type_object( $item )->cap->read_private_posts ) ) && ( $num_posts->archive > 0 || apply_filters( 'gt_show_zero_count_status', GT_SHOW_ZERO_COUNT_STATUS ) ) ) {
												$statuses .= '<div class="gt-status"><a href="edit.php?post_type=' . $item . '&post_status=archive" class="gt-archive" title="Archived">' . $num_posts->archive . '</a></div>';
											}
											if ( ( ( ! isset( get_post_type_object( $item )->cap->delete_posts ) && current_user_can( 'delete_posts' ) && current_user_can( get_post_type_object( $item )->cap->edit_posts ) ) || ( current_user_can( get_post_type_object( $item )->cap->edit_posts ) && current_user_can( get_post_type_object( $item )->cap->delete_posts ) ) ) && ( $num_posts->trash > 0 || apply_filters( 'gt_show_zero_count_status', GT_SHOW_ZERO_COUNT_STATUS ) ) ) {
												$statuses .= '<div class="gt-status"><a href="edit.php?post_type=' . $item . '&post_status=trash" class="gt-trash" title="Trash">' . $num_posts->trash . '</a></div>';
											}
											$statuses .= '</div>';
										}

										ob_start();
											printf( '<div class="' . $classes . '" data-order="gt_' . ( $key + 1 ) . '"><style type="text/css">#dashboard_right_now li a[data-gt="%1$s"]:before{content:\'\\' . $options['icon'] . '\';}</style><div class="gt-published"><a data-gt="%1$s" href="edit.php?post_type=%1$s" class="glance-that" title="All %4$s">%2$s</a>%5$s</div>%3$s</div>', $item, $text, $statuses, $this->label( $item, get_post_type_object( $item )->labels->name, 2 ), $new_post );
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
			case 'ph-website':
				$label = ( $count > 1 || $count == 0 )  ? 'PH Sites' : 'PH Site';
				break;
			case 'ph-project':
				$label = ( $count > 1 || $count == 0 )  ? 'PH Mockups' : 'PH Mockup';
				break;
			default:
				$label = $label;
				break;
		}

		return $label;

	}

	/**
	 * Adds a form for adding/removing custom post types from the At A Glance
	 *
	 * @since    1.2
	 */
	public function add_form() {

		if ( $this->editable || $this->adminable ) {

			global $current_user;
			wp_get_current_user();

			if ( ! apply_filters( 'gt_show_all_dashicons', GT_SHOW_ALL_DASHICONS ) ) {
				// Define dashicon fields allowable icons
				$iconset = array(
					'admin-site',
					'dashboard',
					'admin-post',
					'admin-media',
					'admin-links',
					'marker',
					'admin-page',
					'admin-comments',
					'admin-appearance',
					'admin-plugins',
					'admin-users',
					'admin-tools',
					'admin-settings',
					'admin-network',
					'admin-home',
					'admin-generic',
					'admin-collapse',
					'filter',
					'admin-customizer',
					'admin-multisite',
					'welcome-write-blog',
					'welcome-view-site',
					'welcome-widgets-menus',
					'welcome-comments',
					'welcome-learn-more',
					'format-aside',
					'format-image',
					'format-gallery',
					'format-video',
					'format-status',
					'format-quote',
					'format-chat',
					'format-audio',
					'camera',
					'images-alt',
					'images-alt2',
					'video-alt',
					'video-alt2',
					'video-alt3',
					'playlist-audio',
					'playlist-video',
					'controls-volumeon',
					'image-rotate',
					'image-filter',
					'editor-quote',
					'editor-help',
					'lock',
					'calendar',
					'visibility',
					'post-status',
					'edit',
					'external',
					'sort',
					'share',
					'share-alt',
					'share-alt2',
					'twitter',
					'rss',
					'email',
					'email-alt',
					'facebook-alt',
					'googleplus',
					'networking',
					'wordpress-alt',
					'hammer',
					'art',
					'performance',
					'universal-access',
					'tickets',
					'nametag',
					'clipboard',
					'heart',
					'megaphone',
					'schedule',
					'pressthis',
					'update',
					'screenoptions',
					'info',
					'cart',
					'feedback',
					'cloud',
					'translation',
					'tag',
					'category',
					'archive',
					'tagcloud',
					'marker',
					'star-filled',
					'flag',
					'warning',
					'location',
					'location-alt',
					'vault',
					'shield',
					'shield-alt',
					'sos',
					'search',
					'slides',
					'analytics',
					'chart-pie',
					'chart-bar',
					'chart-area',
					'groups',
					'businessman',
					'id-alt',
					'products',
					'awards',
					'forms',
					'testimonial',
					'portfolio',
					'book-alt',
					'download',
					'backup',
					'clock',
					'lightbulb',
					'microphone',
					'laptop',
					'phone',
					'index-card',
					'carrot',
					'building',
					'store',
					'album',
					'palmtree',
					'tickets-alt',
					'money',
					'thumbs-up',
					'layout',
					'paperclip',
				);
			} else {
				$iconset = array();
			}

			// Assemble a form for adding/removing post types
			$html = '<form id="gt-form" method="post" action="#" data-userid="' . $current_user->ID . '"';

				// Keep form visible if submission has just been made
				$html .= ( isset( $_POST['action'] ) && 'add_remove_glance' == $_POST['action'] ) ? '>' : ' style="display:none;">';

				// Build up the list of post types
				$post_types = get_post_types( array(), 'objects' );

				// Apply filters to available post types
				$post_types = apply_filters( 'gt_post_type_selection', $post_types );

				// Get the dashicon field
				$html .= $this->get_dashicon_field( 'gt-item-icon', 'marker', $iconset );

				$html .= ' <select id="gt-item" name="gt-item">';
					$html .= '<option value""></option>';
					foreach( $post_types as $index => $post_type ) {

						// Set data-glancing attribute
						$glancing = isset( $this->glances[ $post_type->name ] ) ? 'data-glancing="shown"' : 'data-glancing="hidden"';

						// Only show revisions to admininstrators
						if ( 'revision' == $post_type->name && current_user_can( 'edit_dashboard' ) ) {
							$html .= '<option value="' . esc_attr( $post_type->name ) . '" data-dashicon="backup" ' . $glancing . '>' . $this->label( $post_type->name, $post_type->labels->name, 2 ) . '</option>';
						}

						// Only show post types on which user has edit permissions (also disallow some Formidable Form types)
						elseif ( current_user_can( $post_type->cap->edit_posts ) && 'nav_menu_item' != $post_type->name && 'frm_styles' != $post_type->name && 'frm_form_actions' != $post_type->name && 'custom_css' != $post_type->name && 'customize_changeset' != $post_type->name ) {
							$html .= '<option value="' . esc_attr( $post_type->name ) . '" data-dashicon="';
							// add default dashicons for post types
							if ( 'post' == $post_type->name ) {
								$html .= 'admin-post';
							} elseif ( 'page' == $post_type->name ) {
								$html .= 'admin-page';
							} elseif ( 'attachment' == $post_type->name ) {
								$html .= 'admin-media';
							} elseif ( ! empty( $post_type->menu_icon  ) ) {
								$html .= esc_attr( str_replace( 'dashicons-', '', $post_type->menu_icon ) );
							} else {
								$html .= 'marker';
							}
							$html .= '" ' . $glancing . '>' . $this->label( $post_type->name, $post_type->labels->name, 2 ) . '</option>';

						}

					}

					if ( class_exists( 'RGFormsModel' ) ) {
						// Set data-glancing attribute
						$glancing = isset( $this->glances['gravityform'] ) ? 'data-glancing="shown"' : 'data-glancing="hidden"';

						// Only show users option if user can edit forms
						( current_user_can( 'gform_full_access' ) || current_user_can( 'gravityforms_edit_forms' ) ) ? $html .= '<option value="gravityform" data-dashicon="gravityform" ' . $glancing . '>' . $this->label( 'gravityform', 'Gravity Forms', 2 ) . '</options>' : FALSE;
					}

					if ( class_exists( 'FrmForm' ) ) {
						// Set data-glancing attribute
						$glancing = isset( $this->glances['formidableform'] ) ? 'data-glancing="shown"' : 'data-glancing="hidden"';

						// Only show users option if user can edit forms
						( current_user_can( 'frm_view_forms' ) || current_user_can( 'frm_edit_forms' ) ) ? $html .= '<option value="formidableform" data-dashicon="formidableform" ' . $glancing . '>' . $this->label( 'formidableform', 'Formidable Forms', 2 ) . '</options>' : FALSE;
					}

					// Set data-glancing attribute
					$glancing = isset( $this->glances['comment'] ) ? 'data-glancing="shown"' : 'data-glancing="hidden"';

					// Only show users option if user can list users
					current_user_can( 'moderate_comments' ) ? $html .= '<option value="comment" data-dashicon="admin-comments" ' . $glancing . '>' . $this->label( 'comment', 'Comments', 2 ) . '</options>' : FALSE;

					// Set data-glancing attribute
					$glancing = isset( $this->glances['user'] ) ? 'data-glancing="shown"' : 'data-glancing="hidden"';

					// Only show users option if user can list users
					current_user_can( 'list_users' ) ? $html .= '<option value="user" data-dashicon="admin-users" ' . $glancing . '>' . $this->label( 'user', 'Users', 2 ) . '</options>' : FALSE;

					// Set data-glancing attribute
					$glancing = isset( $this->glances['plugin'] ) ? 'data-glancing="shown"' : 'data-glancing="hidden"';

					// Only show plugins optino if user can activate plugins
					current_user_can( 'activate_plugins' ) ? $html .= '<option value="plugin" data-dashicon="admin-plugins" ' . $glancing . '>' . $this->label( 'plugin', 'Plugins', 2 ) . '</options>' : FALSE;

					// Set data-glancing attribute
					$glancing = isset( $this->glances['theme'] ) ? 'data-glancing="shown"' : 'data-glancing="hidden"';

					// Only show themes option if user can switch themes
					current_user_can( 'switch_themes' ) ? $html .= '<option value="theme" data-dashicon="admin-appearance" ' . $glancing . '>' . $this->label( 'theme', 'Themes', 2 ) . '</options>' : FALSE;

				$html .= '</select>';

				// Set the submission buttons which are handled via jquery
				$html .= '<span style="float: right;">';
					$html .= '<input type="submit" class="button-primary" id="submit-gt-item" />';
				$html .= '</span>';

			$html .= '</form>';

			echo $html;

		}

	} // end add_form

	/**
	 * Remove post types from option list
	 *
	 * @since    2.1.0
	 */
	public function remove_post_type_options( $post_types ) {

		unset( $post_types['give_payment'] );
		unset( $post_types['give_log'] );
		unset( $post_types['oembed_cache'] );
		unset( $post_types['phw_comment_loc'] );
		unset( $post_types['ph-webpage'] );
		unset( $post_types['ph_comment_location'] );
		unset( $post_types['project_image'] );

		return $post_types;

	} // end remove_post_type_options

	/**
	 * Process any responses to the displayed notices.
	 *
	 * @since    2.1.0
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
					} elseif ( 'gravityform' == $glance ) {
						$this->notices[] = array( 'message' => '<strong>Gravity Forms</strong> were successfully added to your glances.', 'class' => 'success' );
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
					} elseif ( 'gravityform' == $glance ) {
						$this->notices[] = array( 'message' => '<strong>Gravity Forms</strong> were successfully removed from your glances.', 'class' => 'success' );
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
	 *
	 * @since    1.0
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
	 * Assembles a form field for dashicon selection.
	 *
	 * @since    1.2
	 */
	public function get_dashicon_field( $id = 'dashicon', $default = 'marker', $options = array() ) {

		$dashicons = $this->get_dashicons();

		// Allow users to filter available iconset
		$options = apply_filters( $id . '_selection', $options );

		// if dashicon set has been provided by user, replace the default dashicon set
		if ( ! empty( $options ) ) {

			// initialize limited icon array
			$limited_icons = array();

			foreach ( $dashicons as $category => $group ) {
				// Loop through all available dashicons
				foreach ( $group as $code => $icon ) {

					// Loop through user provided iconset
					foreach ( $options as $option ) {

						// If dashicon is in set, add it to the limited icon array
						$option == $dashicons[ $category ][ $code ] ? $limited_icons[ $category ][ $code ] = $dashicons[ $category ][ $code ] : FALSE;

					}

				}
			}

			// Reset the dashicons that will be used
			$dashicons = $limited_icons;

		}

		// Add registered post type dashicons, if defined
		$post_types = get_post_types( array(), 'objects' );

		// Loop through registered post types
		foreach ( $post_types as $post_type => $data ) {

			// If dashicon isset
			if ( ! is_null( $data->menu_icon ) ) {

				// If not included in options array, add it
				! in_array( str_replace( 'dashicons-', '', $data->menu_icon ), $options ) ? $options[] = str_replace( 'dashicons-', '', $data->menu_icon ) : FALSE;

			}

		}

		// Set the default icon code from default icon name
		foreach ( $dashicons as $category => $group ) {
			foreach ( $group as $code => $icon ) {
				if ( $default == $icon ) {
					$default_code = $code;
					break;
				}
			}
		}

		// Add styling for iconset
		$html = '<style type="text/css">
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
			#iconlist{
				display:none;
				position:absolute;
				padding:12px 10px;
				margin:5px 15px 0 0;
				z-index:999;
			}
			</style>';

		// Set the visible icon according to default icon
		$html .= '<div id="visible-icon" alt="' . esc_attr( $default_code ) . '" class="dashicon dashicons-' . esc_attr( $default ) . '"></div>';

		// Set the hidden form field according to provided id and default icon
		$html .= '<input id="' . esc_attr( $id ) . '" name="' . esc_attr( $id ) . '" type="hidden" data-dashicon="selected" value="' . esc_attr( $default_code ) . '" />';

		// Container div for iconset
		$html .= '<div id="iconlist" class="postbox">';

			// Show available icons (selection currently handled by external jquery)
			foreach ( $dashicons as $category => $group ) {
				foreach ( $group as $code => $icon ) {
					$html .= '<div alt="' . $code . '" class="dashicon dashicons-' . $icon . ' dashicon-option" data-dashicon="' . $icon . '" style="padding-top:6px;"></div>';
				}
			}

		$html .= '</div>';

		return $html;

	} // end get_dashicon_field

	/**
	 * Get the categorized array of dashicons.
	 *
	 * @since    2.6
	 */
	public function get_dashicons() {

		return array(
			'Admin Menu' => array(
				'f333' => 'menu',
				'f319' => 'admin-site',
				'f226' => 'dashboard',
				'f109' => 'admin-post',
				'f104' => 'admin-media',
				'f103' => 'admin-links',
				'f105' => 'admin-page',
				'f101' => 'admin-comments',
				'f100' => 'admin-appearance',
				'f106' => 'admin-plugins',
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
			),
			'Welcome Screen' => array(
				'f119' => 'welcome-write-blog',
				'f133' => 'welcome-add-page',
				'f115' => 'welcome-view-site',
				'f116' => 'welcome-widgets-menus',
				'f117' => 'welcome-comments',
				'f118' => 'welcome-learn-more',
			),
			'Post Formats' => array(
				'f123' => 'format-aside',
				'f128' => 'format-image',
				'f161' => 'format-gallery',
				'f126' => 'format-video',
				'f130' => 'format-status',
				'f122' => 'format-quote',
				'f125' => 'format-chat',
				'f127' => 'format-audio',
				'f306' => 'camera',
				'f232' => 'images-alt',
				'f233' => 'images-alt2',
				'f234' => 'video-alt',
				'f235' => 'video-alt2',
				'f236' => 'video-alt3',
			),
			'Media' => array(
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
			),
			'Image Editing' => array(
				'f165' => 'image-crop',
				'f531' => 'image-rotate',
				'f166' => 'image-rotate-left',
				'f167' => 'image-rotate-right',
				'f168' => 'image-flip-vertical',
				'f169' => 'image-flip-horizontal',
				'f533' => 'image-filter',
				'f171' => 'undo',
				'f172' => 'redo',
			),
			'TinyMCE' => array(
				'f200' => 'editor-bold',
				'f201' => 'editor-italic',
				'f203' => 'editor-ul',
				'f204' => 'editor-ol',
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
				'f474' => 'editor-break',
				'f475' => 'editor-code',
				'f476' => 'editor-paragraph',
				'f535' => 'editor-table',
			),
			'Posts Screen' => array(
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
			),
			'Sorting' => array(
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
			),
			'Social' => array(
				'f237' => 'share',
				'f240' => 'share-alt',
				'f242' => 'share-alt2',
				'f301' => 'twitter',
				'f303' => 'rss',
				'f465' => 'email',
				'f466' => 'email-alt',
				'f304' => 'facebook',
				'f305' => 'facebook-alt',
				'f462' => 'googleplus',
				'f325' => 'networking',
			),
			'WordPress' => array(
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
			),
			'Products' => array(
				'f157' => 'pressthis',
				'f463' => 'update',
				'f180' => 'screenoptions',
				'f348' => 'info',
				'f174' => 'cart',
				'f175' => 'feedback',
				'f176' => 'cloud',
				'f326' => 'translation',
			),
			'Taxonomies' => array(
				'f323' => 'tag',
				'f318' => 'category',
			),
			'Widgets' => array(
				'f480' => 'archive',
				'f479' => 'tagcloud',
				'f478' => 'text',
			),
			'Notifications' => array(
				'f147' => 'yes',
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
			),
			'Miscellaneous' => array(
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

	} // end get_dashicons

	/**
	 * Process any responses to the displayed notices.
	 *
	 * @since    1.0
	 */
	public function get_user_status_visibility() {

		global $current_user;
		wp_get_current_user();

		$status_visibility = get_user_meta( $current_user->ID, 'glance_that_status_visibility', true );

		// If user has no glances set
		if ( empty( $status_visibility ) ) {

			// Update the option
			update_user_meta( $current_user->ID, 'glance_that_status_visibility', 'visible' );

			$status_visibility = true;

		}

		if ( 'visible' == $status_visibility && apply_filters( 'gt_show_all_status', GT_SHOW_ALL_STATUS ) ) {
			return true;
		} else {
			return false;
		}

	} // end get_user_status_visibility

	/**
	 * Process any responses to the displayed notices.
	 *
	 * @since    1.0
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
	 *
	 * @since    1.8
	 */
	public function default_glances() {

		global $current_user;
		wp_get_current_user();

		// Get default set action
		$action = $_POST['gt_action'];

		$glances = get_user_meta( $current_user->ID, 'glance_that', TRUE );

		// Set default glances
		update_option( 'glance_that_default', $glances );

		if ( 'all' == $action ) {

			$users = get_users();

			foreach ( $users as $user ) {

				// Update the option
				update_user_meta( $user->ID, 'glance_that', $glances );

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
	 *
	 * @since    1.8
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
	 * Action target that sorts glances
	 *
	 * @since    1.8
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
	 * Overrides status icons if defined by Post State Tags plugin
	 *
	 * @since    2.6
	 */
	public function check_override_status_icons() {

		if ( is_plugin_active( 'post-state-tags/post-state-tags.php' ) ) {

			// Add styling for iconset
			$html = '<style type="text/css">';

			$post_state_tags_options = array(
				'future'  => get_option( 'bb-pst-color-future-icon' ),
				'draft'   => get_option( 'bb-pst-color-draft-icon' ),
				'pending' => get_option( 'bb-pst-color-pending-icon' ),
				'private' => get_option( 'bb-pst-color-private-icon' ),
				'trash'   => get_option( 'bb-pst-color-trash-icon' ),
				'archive' => get_option( 'bb-pst-color-archive-icon' )
			);

			foreach ( $post_state_tags_options as $status => $icon ) {

				if ( false !== $icon && '' != $icon ) {

					$html .= '#dashboard_right_now div.gt-status a.gt-' . $status . ':before { content: \'\\' . $this->get_dashicon_code( $icon ) . '\'; }';

				}

			}

			$html .= '</style>';

			echo $html;

		}

	} // end check_override_status_icons

	/**
	 * Checks whether Archived Post Status plugin is active
	 *
	 * @since    2.6
	 */
	public function is_archive_active() {

		return is_plugin_active( 'archived-post-status/archived-post-status.php' );

	} // end is_archive_active

	/**
	 * Retrieve dashicon character code from dashicon name
	 *
	 * @since    2.6
	 */
	public function get_dashicon_code( $dashicon ) {

		$dashicons = $this->get_dashicons();

		foreach ( $dashicons as $category => $group ) {
			foreach ( $group as $code => $icon ) {
				$icon = 'dashicons-' . $icon;
				if ( $dashicon == $icon ) {
					return $code;
					break;
				}
			}
		}

	} // end get_dashicon_code

} // end class

/**** DECLARE TYPEWHEEL NOTICES ****/
require_once( 'typewheel-notice/class-typewheel-notice.php' );

if ( apply_filters( 'gt_show_notices', true ) ) {
	add_action( 'admin_notices', 'typewheel_glance_that_notices' );
	/**
	 * Displays a plugin notices
	 *
	 * @since    1.0
	 */
	function typewheel_glance_that_notices() {

		$prefix = str_replace( '-', '_', dirname( plugin_basename(__FILE__) ) );

		if ( ! get_option( $prefix . '_activated' ) ) {

			// Notice to show on plugin activation
			$html = '<div class="updated" style="background-image:linear-gradient( to bottom right, rgb(215, 215, 215), rgb(231, 211, 186) );border-left-color:#3F3F3F;">';
				$html .= '<p style="display: inline-block;">';
					$html .= __( '<strong>Glance That</strong> is now active. Head on over to <a href="/wp-admin/index.php" style="text-decoration:none;"><i class="dashicons dashicons-dashboard"></i> your dashboard</a> to improve your glancing experience.', 'typewheel' );
				$html .= '</p>';
			$html .= '</div><!-- /.updated -->';

			echo $html;

			// Define the notices
			$typewheel_notices = array(
				$prefix . '-tutorial' => array(
					'trigger' => true,
					'time' => time() - 5,
					'dismiss' => array(),
					'type' => '',
					'content' => '<h2 style="margin:0;"><i class="dashicons dashicons-welcome-learn-more"></i> Glance That Tutorial</h2><br />Allow me to give you a brief run down on your <strong>Glance That</strong> options. You can hover over the <i class="dashicons dashicons-admin-settings"></i> settings icon at the top-right of <strong>At A Glance</strong> to reveal your controls. Clicking the <i class="dashicons dashicons-filter"></i> filter will allow you to add and remove items. You can also control <i class="dashicons dashicons-visibility"></i> visibility of available statuses for each item. Rearrange items by <i class="dashicons dashicons-move"></i> dragging them. Then, you can <i class="dashicons dashicons-migrate"></i> push your setup to other users. Let me know if you have any questions. Thanks! <a href="https://twitter.com/uamv/">@uamv</a>',
					// 'icon' => 'heart',
					'style' => array( 'background-image' => 'linear-gradient( to bottom right, rgb(215, 215, 215), rgb(231, 211, 186) )', 'border-left-color' => '#3F3F3F', 'max-width' => '700px', 'padding' => '.5em 2em' ),
					'location' => array( 'index.php' ),
					'capability' => GT_ADMIN_GLANCES,
				),
				$prefix . '-review' => array(
					'trigger' => true,
					'time' => time() + 604800,
					'dismiss' => array( 'month' ),
					'type' => '',
					'content' => 'How are you liking the <strong>Glance That</strong> plugin? Help spread the word by <a href="https://wordpress.org/support/plugin/glance-that/reviews/?rate=5#new-post" target="_blank"><i class="dashicons dashicons-star-filled"></i> giving a review</a> or <a href="https://twitter.com/intent/tweet/?url=https%3A%2F%2Fwordpress.org%2Fplugins%2Fglance-that%2F" target="_blank"><i class="dashicons dashicons-twitter"></i> tweeting your support</a>. Thanks! <a href="https://twitter.com/uamv/">@uamv</a>',
					'icon' => 'share-alt',
					'style' => array( 'background-image' => 'linear-gradient( to bottom right, rgb(215, 215, 215), rgb(231, 211, 186) )', 'border-left-color' => '#3F3F3F' ),
					'location' => array( 'index.php' ),
					'capability' => GT_ADMIN_GLANCES,
				),
				$prefix . '-give' => array(
					'trigger' => true,
					'time' => time() + 2592000,
					'dismiss' => array( 'month' ),
					'type' => '',
					'content' => 'Is <strong>Glance That</strong> still working well for you? Please consider a <a href="https://typewheel.xyz/give/?ref=Glance%20That" target="_blank">small donation</a> to encourage further development. Thanks! <a href="https://twitter.com/uamv/">@uamv</a>',
					'icon' => 'heart',
					'style' => array( 'background-image' => 'linear-gradient( to bottom right, rgb(215, 215, 215), rgb(231, 211, 186) )', 'border-left-color' => '#3F3F3F' ),
					'location' => array( 'index.php' ),
					'capability' => GT_ADMIN_GLANCES,
				),
			);

			// get the notice class
			$notices = new Typewheel_Notice( $prefix, $typewheel_notices );

			update_option( $prefix . '_activated', true );

		} else {

			$notices = new Typewheel_Notice( $prefix );

		}

	} // end display_plugin_notices
}

/**
 * Deletes activation marker so it can be displayed when the plugin is reinstalled or reactivated
 *
 * @since    1.0
 */
function typewheel_glance_that_remove_activation_marker() {

	$prefix = str_replace( '-', '_', dirname( plugin_basename(__FILE__) ) );

	delete_option( $prefix . '_activated' );

}
register_deactivation_hook( dirname(__FILE__) . '/glance-that.php', 'typewheel_glance_that_remove_activation_marker' );
