<?php

// There are no core functions to read these constants.
define('ABSPATH', './');
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WPMU_PLUGIN_DIR', './');
define('EMPTY_TRASH_DAYS', 30 * 86400);

// Constants for expressing human-readable intervals.
define('MINUTE_IN_SECONDS', 60);
define('HOUR_IN_SECONDS', 60 * MINUTE_IN_SECONDS);
define('DAY_IN_SECONDS', 24 * HOUR_IN_SECONDS);
define('WEEK_IN_SECONDS', 7 * DAY_IN_SECONDS);
define('MONTH_IN_SECONDS', 30 * DAY_IN_SECONDS);
define('YEAR_IN_SECONDS', 365 * DAY_IN_SECONDS);

// Constants for expressing human-readable data sizes in their respective number of bytes.
define('KB_IN_BYTES', 1024);
define('MB_IN_BYTES', 1024 * KB_IN_BYTES);
define('GB_IN_BYTES', 1024 * MB_IN_BYTES);
define('TB_IN_BYTES', 1024 * GB_IN_BYTES);

// wpdb method parameters.
define('OBJECT', 'OBJECT');
define('OBJECT_K', 'OBJECT_K');
define('ARRAY_A', 'ARRAY_A');
define('ARRAY_N', 'ARRAY_N');

// Overwrite WP functions
function is_admin()
{
	return false;
}
function add_action()
{
	return true;
}
function wp_next_scheduled()
{
	return false;
}
function wp_schedule_event()
{
	return true;
}
function get_option($input)
{
	switch ($input) {
		case 'om4_freeform_css':
			global $inputFile;
			global $inputContent;
			if (isset($inputContent)) {
				return $inputContent;
			}
			return file_get_contents($inputFile);
		case 'om4_freeform_css_old_files':
			return [];
		default:
			return false;
	}
}
function wp_upload_bits($name, $deprecated,  $bits, $time = null )
{
	global $outputFile;
	global $outputContent;
	$outputContent = $bits;
	return [
		'error' => false,
		'url' => $outputFile,
	];
}
function wp_upload_dir()
{
	global $outputFile;
	return ['baseurl' => $outputFile];
}
function get_current_user_id()
{
	return 123;
}
function add_filter()
{
	return true;
}
function remove_filter()
{
	return true;
}
function update_option()
{
	return true;
}
function do_action()
{
}
