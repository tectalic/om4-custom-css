<?php

// There are no core functions to read these constants.
define( 'ABSPATH', './' );
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );
define( 'WPMU_PLUGIN_DIR', './' );
define( 'EMPTY_TRASH_DAYS', 30 * 86400 );

// Constants for expressing human-readable intervals.
define( 'MINUTE_IN_SECONDS', 60 );
define( 'HOUR_IN_SECONDS', 60 * MINUTE_IN_SECONDS );
define( 'DAY_IN_SECONDS', 24 * HOUR_IN_SECONDS );
define( 'WEEK_IN_SECONDS', 7 * DAY_IN_SECONDS );
define( 'MONTH_IN_SECONDS', 30 * DAY_IN_SECONDS );
define( 'YEAR_IN_SECONDS', 365 * DAY_IN_SECONDS );

// Constants for expressing human-readable data sizes in their respective number of bytes.
define( 'KB_IN_BYTES', 1024 );
define( 'MB_IN_BYTES', 1024 * KB_IN_BYTES );
define( 'GB_IN_BYTES', 1024 * MB_IN_BYTES );
define( 'TB_IN_BYTES', 1024 * GB_IN_BYTES );

// wpdb method parameters.
define( 'OBJECT', 'OBJECT' );
define( 'OBJECT_K', 'OBJECT_K' );
define( 'ARRAY_A', 'ARRAY_A' );
define( 'ARRAY_N', 'ARRAY_N' );

/**
 * Test functions to overwrite WP functions
 */
function is_admin(): bool {
	return false;
}

function add_action(): bool {
	return true;
}

function wp_next_scheduled(): bool {
	return false;
}

function wp_schedule_event(): bool {
	return true;
}

/** @return mixed */
function get_option( string $input ) {
	switch ( $input ) {
		case 'om4_freeform_css':
			global $input_file;
			global $input_content;
			if ( isset( $input_content ) ) {
				return $input_content;
			}
			return file_get_contents( $input_file );
		case 'om4_freeform_css_old_files':
			return array();
		default:
			return false;
	}
}

/** @return array{error:bool, url:string} */
function wp_upload_bits( string $name, ?string $deprecated, string $bits, string $time = null ): array {
	global $output_file;
	global $output_content;
	$output_content = $bits;
	return array(
		'error' => false,
		'url'   => $output_file,
	);
}

/** @return array{baseurl:string} */
function wp_upload_dir(): array {
	global $output_file;
	return array( 'baseurl' => $output_file );
}

function get_current_user_id(): int {
	return 123;
}

function add_filter(): bool {
	return true;
}

function remove_filter(): bool {
	return true;
}

function update_option(): bool {
	return true;
}

function do_action(): void {
}
