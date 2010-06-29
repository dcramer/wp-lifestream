<?php
/*
Plugin Name: Lifestream
Plugin URI: http://www.enthropia.com/labs/wp-lifestream/
Description: Displays your activity from various social networks. (Requires PHP 5 and MySQL 5)
Version: 0.99.9.8-BETA
Author: David Cramer <dcramer@gmail.com>
Author URI: http://www.davidcramer.net
*/

if (!function_exists('plugin_basename'))
{
	// so we can easily test for parse errors
	function plugin_basename($filename){return $filename;}
	function register_activation_hook() {}
	function register_deactivation_hook() {}
	function add_filter() {}
	function add_action() {}
}

if (version_compare(PHP_VERSION, '5.0', '<'))
{
	if ($_GET['activate'] == true)
	{
		echo '<div class="updated fade error" style="font-weight:bold;color:red;"><p>Error: Lifestream requires PHP 5.0 or newer and you are running '.PHP_VERSION.'</p></div>';
	}
	function lifestream() { return ''; }
	$lifestream = null;
}
else
{
	function get_lifestream_folder_name()
	{
		$x = explode('/', str_replace('\\', '/', dirname(__FILE__)));
		return $x[count($x)-1];
	}

	define('LIFESTREAM_VERSION', '0.99.9.8');
	define('LIFESTREAM_PLUGIN_FILE', plugin_basename(__FILE__));
	define('LIFESTREAM_PLUGIN_DIR', get_lifestream_folder_name());
	define('LIFESTREAM_PATH', dirname(__FILE__));
	define('LIFESTREAM_URL', plugins_url($path = '/'.LIFESTREAM_PATH));
	define('LIFESTREAM_FEEDS_PER_PAGE', 10);
	define('LIFESTREAM_EVENTS_PER_PAGE', 25);
	define('LIFESTREAM_ERRORS_PER_PAGE', 25);

	require_once(LIFESTREAM_PATH . '/inc/core.php');
}

?>
