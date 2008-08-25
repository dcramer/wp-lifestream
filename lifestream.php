<?php
/*
Plugin Name: LifeStream
Plugin URI: http://www.davidcramer.net/my-projects/lifestream
Description: Displays your social activity in a lifestream. (Requires PHP5)
Author: David Cramer
Version: 0.51
Author URI: http://www.davidcramer.net
*/

// since so many people miss the installation requirements
if (phpversion() >= 5 )
{
    include('_lifestream.php');
}
else
{
    if (isset($_GET['activate']) && $_GET['activate'] == 'true')
    {
        echo '<p style="font-weight: bold; font-size: 20px; padding: 10px; color: red;">LifeStream will not function under PHP 4. You need to upgrade to PHP 5 and reactivate the plugin.</p>';
    }
}