<?php
/*
Plugin Name: Lifestream
Plugin URI: http://www.ibegin.com/labs/wp-lifestream/
Description: Displays your activity from various social networks. (Requires PHP 5 and MySQL 5)
Version: 0.99.8.3
Author: David Cramer <dcramer@gmail.com>
Author URI: http://www.davidcramer.net
*/

define(LIFESTREAM_VERSION, '0.99.8.3');
//define(LIFESTREAM_PLUGIN_FILE, 'lifestream/lifestream.php');
define(LIFESTREAM_PLUGIN_FILE, plugin_basename(__FILE__));
define(LIFESTREAM_PATH, dirname(__FILE__));
define(LIFESTREAM_FEEDS_PER_PAGE, 10);
define(LIFESTREAM_EVENTS_PER_PAGE, 25);
define(LIFESTREAM_ERRORS_PER_PAGE, 25);

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
	require_once(LIFESTREAM_PATH . '/inc/core.php');
}

?>
