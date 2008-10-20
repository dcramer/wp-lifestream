<?php
/*
Plugin Name: LifeStream
Plugin URI: http://www.davidcramer.net/my-projects/lifestream
Description: Displays your social activity in a lifestream. (Requires PHP5)
Author: David Cramer
Version: 0.77b
Author URI: http://www.davidcramer.net
*/

// since so many people miss the installation requirements
if (phpversion() >= 5)
{
    define(LIFESTREAM_VERSION, 0.77b);
    define(LIFESTREAM_PLUGIN_FILE, __FILE__);

    include('_lifestream.php');
}
else
{
    echo '<p style="font-weight: bold; font-size: 20px; padding: 10px; color: red;">LifeStream will not function under PHP 4. You need to upgrade to PHP 5 and reactivate the plugin.</p>';
}