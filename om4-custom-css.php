<?php
/*
Plugin Name: OM4 Custom CSS
Plugin URI: https://github.com/OM4/om4-custom-css
Description: Add custom CSS rules using the WordPress Dashboard. Access via Dashboard, Appearance, Custom CSS.
Version: 1.5.2
Author: OM4
Author URI: https://github.com/OM4/
Text Domain: om4-custom-css
Git URI: https://github.com/OM4/om4-custom-css
Git Branch: release
License: GPLv2
*/

/*

   Copyright 2012-2016 OM4 (email: plugins@om4.com.au    web: https://om4.com.au/)

   This program is free software; you can redistribute it and/or modify
   it under the terms of the GNU General Public License as published by
   the Free Software Foundation; either version 2 of the License, or
   (at your option) any later version.

   This program is distributed in the hope that it will be useful,
   but WITHOUT ANY WARRANTY; without even the implied warranty of
   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
   GNU General Public License for more details.

   You should have received a copy of the GNU General Public License
   along with this program; if not, write to the Free Software
   Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/


if ( ! class_exists( 'OM4_Plugin_Appearance' ) ) {
	require_once( 'includes/OM4_Plugin_Appearance.php' );
}


/**
 * Custom CSS feature implementation:
 * - Adds Dashboard -> Appearance -> Custom CSS, which is accessible to any WordPress Administrator
 * - Outputs the Custom CSS rule stylesheet into any theme that has the 'wp_head' hook
 *
 * Should work with any WordPress theme that has the 'wp_head' hook
 */
class OM4_Custom_CSS extends OM4_Plugin_Appearance {

	/**
	 * CodeMirror version number (used when enqueing static JS/CSS files).
	 *
	 * @var string
	 */
	protected $codemirror_version = '5.21.0';


	/**
	 * Initilise hooks and daily cleanup cron.
	 */
	public function __construct() {

		$this->screen_title = 'Custom CSS';
		$this->screen_name = 'customcss';

		$this->wp_editor_defaults['textarea_rows'] = 30;

		if ( is_admin() ) {
			add_action( 'admin_post_update_custom_css', array( $this, 'dashboard_screen_save' ) );
			add_action( 'wp_ajax_update_custom_css', array( $this, 'dashboard_screen_save' ) );
		} else {
			add_action( 'init', array( $this, 'init_frontend' ), 100000 );
		}

		// Once a day, remove old css files.
		if ( ! wp_next_scheduled( 'om4_custom_css_cleanup' ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', 'om4_custom_css_cleanup' );
		}

		add_action( 'om4_custom_css_cleanup', array( $this, 'cleanup' ) );
		add_action( 'template_redirect', array( $this, 'template_redirect' ), 11 ); // After WordPress' redirect_canonical.

		parent::__construct();
	}

	/**
	 * Get the URL to this plugin's folder
	 */
	public function plugin_url() {
		return untrailingslashit( plugins_url( '/', __FILE__ ) );
	}


	/**
	 * Output the Custom CSS file as late as possible just before the </head> tag.
	 *
	 * Executed during the init hook when not in the admin/dashboard.
	 */
	public function init_frontend() {

		// Attempt to ensure that our Custom CSS rules are the last thing output before </head>.
		$hook = 'wp_head';
		if ( function_exists( 'om4_generated_css_rules' ) ) {
			// OM4 Theme.
			// Maintain backwards-compatibility with OM4 theme.
			$hook = 'om4_theme_end_head';
		} else if ( function_exists( 'woo_head' ) ) {
			// WooTheme (eg Canvas).
			$hook = 'woo_head';
		} else if ( class_exists( 'FLTheme' ) ) {
			// Beaver Builder Theme.
			$hook = 'fl_head';
		}
		add_action( $hook, array( $this, 'output_custom_css_stylesheet' ), 100000 );
	}

	/**
	 * Retrieves the custom CSS rules from the database.
	 * Used when editing the CSS rules via the dashboard.
	 *
	 * @return string The CSS
	 */
	public function get_custom_css() {
		return get_option( 'om4_freeform_css', '' );
	}

	/**
	 * Saves the custom CSS rules (uncomplied SCSS/SASS) to the database so that they can be edited later.
	 *
	 * @param string $css The CSS.
	 *
	 * @return boolean False if option was not added and true if option was added
	 */
	private function set_custom_css( $css ) {
		// Use delete_option() & add_option() instead of update_option() so that we don't autoload the option.
		delete_option( 'om4_freeform_css' );
		return add_option( 'om4_freeform_css', $css, '', 'no' );
	}

	/**
	 * Save the specified Custom CSS rules.
	 *
	 * Save them to the database (for easy retrieval when editing), and save them to the filesystem (for easy display via the frontend).
	 *
	 * @param string $css The CSS.
	 *
	 * @return bool True on success, false on failure
	 * @throws Exception if compilation/saving fails.
	 */
	private function save_custom_css( $css ) {
		$this->set_custom_css_last_saved_timestamp();
		$this->set_custom_css( $css );
		return $this->save_custom_css_to_file();
	}

	private function get_custom_css_last_saved_timestamp() {
		return get_option( 'om4_freeform_css_last_saved_timestamp', 1 );
	}

	private function set_custom_css_last_saved_timestamp( $timestamp = null ) {
		if ( is_null( $timestamp) )
			$timestamp = time();
		return update_option( 'om4_freeform_css_last_saved_timestamp', $timestamp );
	}

	/**
	 * Obtain the full URL to the custom CSS stylesheet.
	 *
	 * @return string
	 */
	private function get_custom_css_file_url() {
		return $this->upload_url( $this->get_custom_css_filename() );
	}
	
	/**
	 * Retrieves the list of old CSS files from the database.
	 *
	 * Used when cleaning up previous/old CSS files.
	 *
	 * @return array The paths (relative to wp-content/uploads/)
	 */
	private function get_custom_css_filenames_old() {
		$old_files = get_option( 'om4_freeform_css_old_files' );
		if ( false === $old_files ) {
			$old_files = array();
			add_option( 'om4_freeform_css_old_files', $old_files, '',  'no' );
		}
		return $old_files;
	}

	/**
	 * Obtain the file name to the file where the custom CSS rules are saved to.
	 * This is relative to the uploads directory.
	 * Examples:
	 * /custom-122323232.css
	 * /2012/02/custom-1329690974.css
	 *
	 * @return string
	 */
	private function get_custom_css_filename() {
		return get_option( 'om4_freeform_css_filename', '' );
	}

	/**
	 * Sets the filename of the current custom stylesheet.
	 *
	 * Updates the current custom stylesheet's name in the database.
	 * Also adds the old name to the list of old files.
	 *
	 * @param string $filename The filename (excluding path).
	 */
	private function set_custom_css_filename( $filename ) {
		// The old filenames are stored for cleanup later.
		// This stops caching issues where old files are requested but no longer exist.
		$old_files = $this->get_custom_css_filenames_old();
		$old_files[] = $this->get_custom_css_filename();
		update_option( 'om4_freeform_css_old_files', $old_files );
		return update_option( 'om4_freeform_css_filename', $filename );
	}

	/**
	 * Outputs the custom CSS editor dashboard screen.
	 */
	public function dashboard_screen() {
		?>
		<div class='wrap'>
			<div id="om4-header">
				<h2><?php echo esc_attr( $this->screen_title ); ?></h2>
				<?php
				if ( ! $this->can_access_dashboard_screen() ) {
					echo '<div class="error"><p>' . esc_html__( 'You do not have permission to access this feature.', 'om4-custom-css' ) . '</p></div>';
					return;
			}

			if ( isset( $_GET['updated'] ) && $_GET['updated'] == 'true' ) {
			?>
			<div id="message" class="updated fade">
				<p><?php printf( __( 'Custom CSS rules saved. You can <a href="%s">view your site by clicking here</a>.', 'om4-custom-css' ), esc_attr( site_url() ) ); ?></p>
			</div>
			<div id="message" class="updated fade">
				<p><?php printf( __( 'It is recommended that you %1$svalidate your CSS rules%2$s to help you find errors, typos and incorrect uses of CSS.', 'om4-custom-css' ), $this->validate_css_link_start(), '</a>' ); ?></p>
			</div>
			<?php
			} else if ( isset($_GET['updated']) && $_GET['updated'] == 'false' ) {
				?>
				<div id="message" class="error fade">
					<p><?php esc_html_e( 'There was an error saving your Custom CSS rules. Please try again.', 'om4-custom-css' ); ?></p>
				</div>
				<?php
			}

			?>
			<form action="<?php echo $this->form_action(); ?>" method="post">
				<div style="float: right;"><?php echo $this->validate_css_button(); ?></div>
				<p><?php _e( 'To use <strong>Custom CSS</strong> rules to change the appearance of your site, enter them in this text box. <a href=http://sass-lang.com/documentation/file.SASS_REFERENCE.html#css_extensions" target="_blank">SCSS/SASS syntax</a> (such as variables and nesting) can also be used.', 'om4-custom-css' ); ?></p>
				<p class="submit">
					<input type="submit" name="submit" id="submit" class="button-primary" value="<?php esc_html_e( 'Save CSS Rules', 'om4-custom-css' ); ?>" title="<?php esc_html_e( '(Cmd+Enter or Ctrl+Enter)', 'om4-custom-css' ); ?>">
					<img class="loadingspinner" src="<?= admin_url( "images/wpspin_light-2x.gif" ); ?> " width="16" height="16" valign="middle" alt="<?php esc_html_e( 'Loading...', 'om4-custom-css' ); ?>" style="display: none;" />
				</p>
				<?php
				wp_editor( $this->get_custom_css(), 'css', $this->wp_editor_defaults );
				?>
				<input type="hidden" name="action" value="update_custom_css" />
				<?php
				wp_nonce_field( 'update_custom_css' );
				?>
				<p class="submit">
					<input type="submit" name="submit" id="submit" class="button-primary" value="<?php esc_html_e( 'Save CSS Rules', 'om4-custom-css' ); ?>" title="<?php esc_html_e( '(Cmd+Enter or Ctrl+Enter)', 'om4-custom-css' ); ?>">
					<img class="loadingspinner" src="<?= admin_url( "images/wpspin_light-2x.gif" ); ?> " width="16" height="16" valign="middle" alt="<?php esc_html_e( 'Loading...', 'om4-custom-css' ); ?>" style="display: none;" />
				</p>
			</form>
		</div>
		</div>
		<?php

		// CSS Editor JS/CSS.
		wp_enqueue_script( 'om4_custom_css_codemirror', $this->plugin_url() . '/CodeMirror/lib/codemirror.js', array( 'jquery' ), $this->codemirror_version );
		wp_enqueue_script( 'om4_custom_css_codemirror_css_mode', $this->plugin_url() . '/CodeMirror/mode/css/css.js', array( 'om4_custom_css_codemirror' ), $this->codemirror_version );
		wp_enqueue_style( 'om4_custom_css_codemirror', $this->plugin_url() . '/CodeMirror/lib/codemirror.css', array(), $this->codemirror_version );

		add_action( 'admin_print_footer_scripts', array( $this, 'admin_print_footer_scripts' ) );

		// Translatable strings used in JS.
		$formlabels = array(
				'saving'  => __( 'Saving...', 'om4-custom-css' ),
				'default' => __( 'Save CSS Rules', 'om4-custom-css' ),
		);
		wp_localize_script( 'om4_custom_css_codemirror', 'om4_custom_css', $formlabels );
	}

	/**
	 * CSS editor JS/CSS
	 * Initializes CodeMirror editor, sets up AJAX save events and keyboard shortcuts.
	 */
	public function admin_print_footer_scripts() {

		?>
		<style type="text/css">
			.CodeMirror {
				height: auto;
			}
		</style>
		<script>
			var textArea = document.getElementById('css');
			var myCodeMirror = CodeMirror.fromTextArea(textArea, {
				lineNumbers: true, // Show line numbers
				mode: "text/x-scss", // SCSS mode as per http://codemirror.net/mode/css/scss.html
				autofocus: true, // Autofocus the cursor into the editor on page load
				viewportMargin: Infinity, // Expand the editor to the height of the code
				lineWrapping: true // Line Wrapping
			});

			// Save the CSS rules using keyboard shortcuts as per https://codemirror.net/doc/manual.html#keymaps
			// Manually save the CodeMirror instance first, so that the textarea is updated with the latest changes.
			// Valid keyboard shortcuts are:
			// Cmd+Enter
			// Ctrl+Enter
			// Ctrl+Shift+S
			// Cmd+Shift+S
			myCodeMirror.setOption("extraKeys", {
			  'Cmd-Enter': function(cm) {
				  myCodeMirror.save();
				  jQuery('#om4-header form').submit();
			  },'Ctrl-Enter': function(cm) {
				  myCodeMirror.save();
				  jQuery('#om4-header form').submit();
			  },'Shift-Ctrl-S': function(cm) {
				  myCodeMirror.save();
				  jQuery('#om4-header form').submit();
			  },'Shift-Cmd-S': function(cm) {
				  myCodeMirror.save();
				  jQuery('#om4-header form').submit();
			  }
			});

			jQuery(document).ready(function ($) {

				// Submit/save the CSS rules via AJAX
				$('#om4-header form').submit(function (event) {
					event.preventDefault();
					// When saving update the Save buttons, add a spinning wheel, and set the editor background colour to grey
					$(this).find('input[type="submit"]').prop('disabled', true).prop( 'value', om4_custom_css.saving )
					$('#wp-css-editor-container > .CodeMirror').css('background-color', '#dfdfdf');
					$(this).find('img.loadingspinner').show();
					var data = $(this).serialize();
					jQuery.ajax({
						url: ajaxurl,
						data: data,
						dataType: 'json',
						method: 'POST',
						success: function (response) {
							if (response.success) {
								$('a.validatecss').prop('href', response.data.validateurl);
							} else {
								alert(response.data.message);
							}
						},
						error: function () {
							alert('<?php  _e( 'Error saving CSS rules. Please try again.', 'om4-custom-css' ); ?>');
						},
						complete: function () {
							$('#om4-header form input[type="submit"]').prop('disabled', false).prop( 'value', om4_custom_css.default );
							$('#om4-header form img.loadingspinner').hide();
							$('#wp-css-editor-container > .CodeMirror').css('background-color', '');
						}
					});

				});
			});
		</script>
	<?php
	}

	/**
	 * Determines whether the current request is a WordPress Ajax request.
	 *
	 * A wrapper function for WP 4.6 and older compatibility, because wp_doing_ajax() was only added in 4.7
	 *
	 * @return bool
	 */
	protected function is_doing_ajax() {
		return function_exists( 'wp_doing_ajax' ) ? wp_doing_ajax() : defined( 'DOING_AJAX' ) && DOING_AJAX;
	}

	/**
	 * Handler that saves the dashboard screen's options/values via AJAX (or POST if JS not available).
	 */
	public function dashboard_screen_save() {

		if ( $this->is_doing_ajax() ) {
			// AJAX Save.
			$data = array();
			if ( check_ajax_referer( 'update_custom_css' ) && $this->can_access_dashboard_screen() ) {
				try {
					$this->save_custom_css( stripslashes( $_POST['css'] ) );
					$data['validateurl'] = $this->validate_css_url();
					$data['cssurl']      = $this->get_custom_css_file_url();
					wp_send_json_success( $data );
				} catch ( Exception $ex ) {
					$data['message'] = $ex->getMessage();
					// Clean up the error message, removing stdin mention but preserving the line number.
					$data['message'] = str_replace( ' (stdin) ', '', $data['message'] );
					wp_send_json_error( $data );
				}
			} else {
				// User doesn't have permission to access this screen.
				$data = array();
				$data['message'] = __( 'Access Denied. Please try again.', 'om4-custom-css' );
				wp_send_json_error( $data );
			}
		}

		// POST/Form (non JS) save.
		$url = $this->dashboard_url();

		if ( $this->can_access_dashboard_screen() ) {
			check_admin_referer( 'update_custom_css' );
			try {
				$url = $this->save_custom_css( stripslashes( $_POST['css'] ) ) ? $this->dashboard_url_saved() : $this->dashboard_url_saved_error();
			} catch ( Exception $ex ) {
				$url = $this->dashboard_url_saved_error();
			}
		} else {
			// User doesn't have permission to access this screen.
			$url = $this->dashboard_url_saved_error();
		}

		wp_redirect( esc_url_raw( $url ) );
		exit;
	}

	/**
	 * Create a button that when clicked opens a new window that shows the CSS validation results
	 */
	private function validate_css_button() {
		return '<a class="validatecss" target="_blank" href="' . esc_html( $this->validate_css_url() ) . '"><input type="button" name="' . esc_attr__( 'W3C CSS Validation Results', 'om4-custom-css' ) . '" value="' . esc_html__( 'Validate CSS Rules', 'om4-custom-css' ) . '" class="button-secondary" style="margin-left: 3em;" /></a>';
	}

	/**
	 * Obtain the URL to the CSS validation service.
	 *
	 * @return string The URL to W3's CSS Validator prepopulated with the CSS file's URI.
	 */
	private function validate_css_url() {
		return 'https://jigsaw.w3.org/css-validator/validator?warning=no&uri=' . urlencode( $this->get_custom_css_file_url() );
	}

	/**
	 * Create a link that when clicked opens a new window that shows the CSS validation results.
	 *
	 * @return string A HTML link to validate the CSS.
	 */
	private function validate_css_link_start() {
		return '<a target="_blank" class="validatecss" href="' . esc_html( $this->validate_css_url() ) . '" name="' . __('W3C CSS Validation Results', 'om4-custom-css') . '">';
	}

	/**
	 * Output's the link tag to include the stylesheet
	 */
	public function output_custom_css_stylesheet() {
		if ( ( '' != $this->get_custom_css_filename() ) ) {
			echo "\n" . '<link rel="stylesheet" href="' . $this->get_custom_css_file_url() . '" type="text/css" media="screen" />' . "\n";
		}
	}

	/**
	 * Compiles the SCSS/SASS custom CSS rules, then saves them to the filesystem (uploads directory).
	 *
	 * @return boolean False if the stylesheet could not be saved, true otherwise
	 * @throws Exception if the SCSS compilation fails, or the stylesheet file can't be created.
	 */
	public function save_custom_css_to_file() {

		require( 'includes/scssphp/scss.inc.php' );

		$css_compiler = new Leafo\ScssPhp\Compiler();
		$css_compiler->setFormatter( 'Leafo\ScssPhp\Formatter\Compressed' ) ; // Compressed/minified output.
		$css = $css_compiler->compile( $this->get_custom_css() );
		$css = "/* CSS Generated " . date( 'r' ) . ' by User ID ' . get_current_user_id() . " */\n" . $css;

		$random = time();
		$filename = "custom-$random.css";

		// Save the CSS rules to a unique file.
		// Tell WordPress temporarily that .css files can be uploaded.
		add_filter( 'upload_mimes', array( $this, 'mime_types' ) );
		$result = wp_upload_bits( $filename, null, $css );
		remove_filter( 'upload_mimes', array( $this, 'mime_types' ) );

		if ( ! $result['error'] ) {
			// Save the filename (and yyyy/mm folder names if applicable) to the newly generated stylesheet.
			$dir = wp_upload_dir();
			$filename = str_replace( $dir['baseurl'], '', $result['url'] );

			// Create the new CSS file.
			$this->set_custom_css_filename( $filename );

			// Allow other plugins to perform actions whenever the Custom CSS rules are saved.
			do_action( 'om4_custom_css_saved' );

		} else {
			// Error saving css file. This really shouldn't happen, but just in case.
			throw new Exception( sprintf( __( 'Error creating Custom CSS stylesheet: %s', 'om4-custom-css' ), $filename ) );
		}
		return true;
	}

	/**
	 * Deletes old custom CSS files from the uploads directory.
	 * Run automatically each day via WP-Cron.
	 */
	public function cleanup() {
		// Delete the previous CSS stylesheets.
		$old_files = $this->get_custom_css_filenames_old();
		$dir = wp_upload_dir();
		foreach ( $old_files as $old_filename ) {
			$old_filename = $dir['basedir'] . $old_filename;
			if ( file_exists( $old_filename ) && is_file( $old_filename ) ) {
				unlink( $old_filename );
			}
		}
		update_option( 'om4_freeform_css_old_files', array() );
	}

	/**
	 * Automatically detect requests for old/previous custom CSS files/URLs, and 301 redirect them to the latest CSS file.
	 *
	 * Helps prevent issues with cached pages referring to a previous Custom CSS file that no longer exists.
	 *
	 * Unfortunately this doesn't seem to work on WP Engine - PHP never seems to get instantiated for /wp-content/uploads/ requests.
	 */
	public function template_redirect() {
		if ( is_404() ) {
			// WordPress is about to emit a 404 error.
			// Check to see if the request looks like it is for an old custom css file.
			// The requested URL path.
			$requested_url = is_ssl() ? 'https://' : 'http://';
			$requested_url .= $_SERVER['HTTP_HOST'];
			$requested_url .= $_SERVER['REQUEST_URI'];
			$requested_url = @parse_url( $requested_url );
			$requested_url = $requested_url['path'];

			// The URL path to the current/latest custom css file.
			$current_stylesheet_url = $this->get_custom_css_file_url();
			$current_stylesheet_url = @parse_url( $current_stylesheet_url );
			$current_stylesheet_url = $current_stylesheet_url['path'];

			// Cater for an optional /yyyy/mm/ prefix, which may have changed to another year/month (or been removed completely).
			$pattern                = '/([0-9]{4}\/[0-9]{2}\/)?custom-([0-9]+).css/';
			$requested_url          = preg_replace( $pattern, 'custom-*.css', $requested_url );
			$current_stylesheet_url = preg_replace( $pattern, 'custom-*.css', $current_stylesheet_url );

			if ( $requested_url === $current_stylesheet_url ) {
				wp_redirect( $this->get_custom_css_file_url(), 301 );
				exit;
			}
		}
	}

	/**
	 * Adds in a CSS MIME type to upload the stylesheet.
	 *
	 * @param array $mimes Current MIME types.
	 * @return array The same array with CSS added.
	 */
	public function mime_types( $mimes ) {
		$mimes['css'] = 'text/css';
		return $mimes;
	}

	/**
	 * Get the full URL to the specified uploaded file.
	 *
	 * @param string $path Optional. Path relative to the upload url.
	 * @return string full URL to the uploaded file.
	 */
	private function upload_url( $path = '') {
		$dir = wp_upload_dir();
		$url = $dir['baseurl'] . $path;
		// Use https:// url if the current page is already being loaded via https.
		if ( is_ssl() ) {
			$url = set_url_scheme( $url, 'https' );
		}
		return $url;
	}


}
global $om4_custom_css;
$om4_custom_css = new OM4_Custom_CSS();

/** BEGIN GLOBAL FUNCTIONS - these are used outside of this plugin file **/
function om4_save_custom_css_to_file() {
	global $om4_custom_css;
	return $om4_custom_css->save_custom_css_to_file();
}

function om4_get_custom_css() {
	global $om4_custom_css;
	return $om4_custom_css->get_custom_css();
}
/** END GLOBAL FUNCTIONS **/