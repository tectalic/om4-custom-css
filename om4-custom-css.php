<?php
/*
Plugin Name: OM4 Custom CSS
Plugin URI: https://github.com/OM4/om4-custom-css
Description: Add custom CSS rules using the WordPress Dashboard. Access via Dashboard, Appearance, Custom CSS.
Version: 1.0.7
Author: OM4
Author URI: https://github.com/OM4/
Text Domain: om4-custom-css
Git URI: https://github.com/OM4/om4-custom-css
Git Branch: release
License: GPLv2
*/

/*

   Copyright 2012-2015 OM4 (email: info@om4.com.au    web: http://om4.com.au/)

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


if ( ! class_exists( 'OM4_Plugin_Appearance' ) )
	require_once('includes/OM4_Plugin_Appearance.php');


/**
 * Custom CSS feature implementation:
 * - Adds Dashboard -> Appearance -> Custom CSS, which is accessible to any WordPress Administrator
 * - Outputs the Custom CSS rule stylesheet into any theme that has the 'wp_head' hook
 *
 * Should work with OM4 Theme, any WooTheme, and (hopefully) any other WordPress theme.
 */
class OM4_Custom_CSS extends OM4_Plugin_Appearance {

	public function __construct() {

		$this->screen_title = 'Custom CSS';
		$this->screen_name = 'customcss';

		$this->wp_editor_defaults['textarea_rows'] = 30;

		if ( is_admin() ) {
			$this->add_load_dashboard_page_hook( 'add_thickbox' );
			add_action( 'admin_post_update_custom_css', array($this, 'dashboard_screen_save') );
		} else {
			add_action('init', array($this, 'init_frontend'), 100000 );
		}

		// Once a day, remove old css files
		if ( !wp_next_scheduled( 'om4_custom_css_cleanup' ) ) {
			wp_schedule_event( time(), 'daily', 'om4_custom_css_cleanup' );
		}
		
		add_action( 'om4_custom_css_cleanup', array($this, 'cleanup') );
		
		add_action( 'template_redirect', array($this, 'template_redirect'), 11 ); // After WordPress' redirect_canonical

		add_action( 'om4_new_site_initialised', array($this, 'new_site_initialised') );

		parent::__construct();
	}

	/**
	 * Tasks to perform when a new website is created/initialised.
	 * @return bool
	 */
	public function new_site_initialised() {
		// Generate the initial Custom CSS file
		return $this->save_custom_css_to_file();
	}

	public function init_frontend() {

		// Attempt to ensure that our Custom CSS rules are the last thing output before </head>
		$hook = 'wp_head';
		if ( function_exists( 'om4_generated_css_rules' ) ) {
			// OM4 Theme
			// Maintain backwards-compatibility with OM4 theme
			$hook = 'om4_theme_end_head';
		} else if ( function_exists('woo_head') ) {
			// WooTheme
			$hook = 'woo_head';
		}
		add_action( $hook, array($this, 'output_custom_css_stylesheet'), 100000 );
	}

	public function get_custom_css() {
		return get_option( 'om4_freeform_css', '' );
	}

	private function set_custom_css( $css ) {
		delete_option( 'om4_freeform_css' );
		return add_option( 'om4_freeform_css', $css, '', 'no' );
	}

	/**
	 * Save the specified Custom CSS rules.
	 *
	 * Save them to the database (for easy retrieval), and save them to the filesystem (for easy display via the frontend)
	 *
	 * @param string $css
	 *
	 * @return bool True on success, false on failure
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

	private function get_custom_css_file_url() {
		return $this->upload_url( $this->get_custom_css_filename() );
	}
	
	private function get_custom_css_filenames_old() {
		$old_files = get_option( 'om4_freeform_css_old_files', false );
		if ( false === $old_files ) {
			add_option( 'om4_freeform_css_old_files', array(), '',  'no' );
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

	private function set_custom_css_filename( $filename ) {
		// The old filenames are stored for cleanup later
		// This stops caching issues where old files are requested but no longer exist
		$old_files = $this->get_custom_css_filenames_old();
		$old_files[] = $this->get_custom_css_filename();
		update_option( 'om4_freeform_css_old_files', $old_files );
		return update_option( 'om4_freeform_css_filename', $filename );
	}

	public function dashboard_screen() {
		?>
	<div class='wrap'>
		<div id="om4-header">
			<h2><?php echo esc_attr($this->screen_title); ?></h2>
			<?php
			if ( !$this->can_access_dashboard_screen() ) {
				echo '<div class="error"><p>You do not have permission to access this feature.</p></div></div></div>';
				return;
			}

			if ( isset($_GET['updated']) && $_GET['updated'] == 'true' ) {
				?>
				<div id="message" class="updated fade"><p>Custom CSS rules saved. You can <a href="<?php echo site_url(); ?>">view your site by clicking here</a>.</p></div>
				<div id="message" class="updated fade"><p>It is recommended that you <?php echo $this->validate_css_link('validate your CSS rules'); ?> to help you find errors, typos and incorrect uses of CSS.</p></div>
				<?php
			} else if ( isset($_GET['updated']) && $_GET['updated'] == 'false' ) {
				?>
				<div id="message" class="error fade"><p>There was an error saving your Custom CSS rules. Please try again.</p></div>
				<?php
			}

			?>
			<form action="<?php echo $this->form_action(); ?>" method="post">
				<div style="float: right;"><?php echo $this->validate_css_button(); ?></div>
				<p>To use <strong>Custom CSS</strong> rules to change the appearance of your site, enter them in this text box. Custom CSS rules will override your theme's CSS using the inheritance rules of CSS.<br />
				Rules must have a selector followed by rules in curly braces, for example <code>.mystyle { color: blue; }</code><br />
				Make sure you close all curly brace pairs to avoid errors.  CSS is powerful but hard to understand.  If interested, look at this <a href="http://www.w3schools.com/css/css_intro.asp">introduction</a>, or this <a href="http://www.w3.org/MarkUp/Guide/Style">one</a>.  </p>
				<div style="clear: both;"></div>
				<?php

				wp_editor( $this->get_custom_css(), 'css', $this->wp_editor_defaults );

				?>
				<input type="hidden" name="action" value="update_custom_css" />
				<?php
				wp_nonce_field('update_custom_css');
				?>
				<p class="submit"><input type="submit" name="submit" id="submit" class="button-primary" value="Save CSS Rules"></p>
				</form>
		</div>
	</div>
	<?php
	}

	/**
	 * Handler that saves the dashboard screen's options/values, then redirects back to the Dashboard Screen
	 */
	public function dashboard_screen_save() {

		$url = $this->dashboard_url();

		if ( $this->can_access_dashboard_screen() ) {
			check_admin_referer('update_custom_css');
			$url = $this->save_custom_css( stripslashes($_POST['css']) ) ? $this->dashboard_url_saved() : $this->dashboard_url_saved_error();
		}

		wp_redirect( $url );
		exit;
	}

	/**
	 * Create a button that when clicked opens a thickbox window that shows the CSS validation results
	 * @param string $buttonText Button anchor text
	 */
	private function validate_css_button($buttonText = 'Validate CSS Rules') {
		return '<input type="button" name="W3C CSS Validation Results" value="' . $buttonText . '" class="thickbox button-secondary" onclick="return false;" alt="' . $this->validate_css_url() . '" style="margin-left: 3em;" />';
	}

	/**
	 * Obtain the URL to the CSS validation service
	 * @return string
	 */
	private function validate_css_url() {
		return esc_url( 'http://jigsaw.w3.org/css-validator/validator?warning=no&uri=' . urlencode( $this->get_custom_css_file_url() ) . '&TB_iframe=true&width=900&height=600' );
	}

	/**
	 * Create a link that when clicked opens a thickbox window that shows the CSS validation results
	 * @param string $anchor Link anchor text
	 * @return string
	 */
	private function validate_css_link($anchor) {
		return '<a onlick="return false;" class="thickbox"href="' . $this->validate_css_url() . '" name="W3C CSS Validation Results">' . $anchor . '</a>';
	}

	public function output_custom_css_stylesheet() {
		if ( ( '' != $this->get_custom_css_filename() ) ) {
			echo "\n" . '<link rel="stylesheet" href="' . $this->get_custom_css_file_url() . '" type="text/css" media="screen" />' . "\n";
		}
	}


	public function save_custom_css_to_file() {

		$css = "/* CSS Generated " . date('r') . " by User ID " . get_current_user_id() . " */\n\n" . $this->get_custom_css();

//      $random = time() . '-' . uniqid();
		$random = time();
		$filename = "custom-$random.css";

		// Save the CSS rules to a unique file

		// Tell WordPress temporarily that .css files can be uploaded
		add_filter('upload_mimes', array( $this, 'mime_types') );
		$result = wp_upload_bits( $filename, null, $css );
		remove_filter('upload_mimes', array( $this, 'mime_types') );

		if ( !$result['error'] ) {
			// Save the filename (and yyyy/mm folder names if applicable) to the newly generated stylesheet
			$dir = wp_upload_dir();
			$filename = str_replace($dir['baseurl'], '', $result['url']);

			// Create the new CSS file
			$this->set_custom_css_filename( $filename );

			// Allow other plugins to perform actions whenever the Custom CSS rules are saved
			do_action( 'om4_custom_css_saved' );

		} else {
			// Error saving css file. This really shouldn't happen, but just in case.
			trigger_error( sprintf( __( 'Error creating Custom CSS stylesheet: %s', 'om4-custom-css' ), $filename ) );
			return false;
		}
		return true;
	}
	
	public function cleanup() {
		// Delete the previous CSS stylesheets
		
		$old_files = $this->get_custom_css_filenames_old();
		$dir = wp_upload_dir();
		
		foreach ( $old_files as $old_filename ) {
			$old_filename = $dir['basedir'] . $old_filename;
			if ( file_exists($old_filename) && is_file($old_filename) ) {
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
			// WordPress is about to emit a 404 error
			// Check to see if the request looks like it is for an old custom css file.

			// The requested URL path
			$requested_url = is_ssl() ? 'https://' : 'http://';
			$requested_url .= $_SERVER['HTTP_HOST'];
			$requested_url .= $_SERVER['REQUEST_URI'];
			$requested_url = @parse_url( $requested_url );
			$requested_url = $requested_url['path'];

			// The URL path to the current/latest custom css file
			$current_stylesheet_url = $this->get_custom_css_file_url();
			$current_stylesheet_url = @parse_url( $current_stylesheet_url );
			$current_stylesheet_url = $current_stylesheet_url['path'];

			// Cater for an optional /yyyy/mm/ prefix, which may have changed to another year/month (or been removed completely)
			$pattern                = '/([0-9]{4}\/[0-9]{2}\/)?custom-([0-9]+).css/';
			$requested_url          = preg_replace( $pattern, 'custom-*.css', $requested_url );
			$current_stylesheet_url = preg_replace( $pattern, 'custom-*.css', $current_stylesheet_url );

			if ( $requested_url == $current_stylesheet_url ) {
				wp_redirect( $this->get_custom_css_file_url(), 301 );
				exit;
			}
		}
	}

	public function mime_types($mimes) {
		$mimes['css'] = 'text/css';
		return $mimes;
	}

	/**
	 *
	 * @param string $path Optional. Path relative to the upload url.
	 * @return string full URL to the uploaded file
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