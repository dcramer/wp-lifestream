<?php
/*
Plugin Name: LifeStream
Plugin URI: http://www.ibegin.com/labs/wp-lifestream/
Description: Displays your activity from various social networks. (Requires PHP 5 and MySQL 5)
Version: 0.98h
Author: David Cramer <dcramer@gmail.com>
Author URI: http://www.davidcramer.net
*/

define(LIFESTREAM_BUILD_VERSION, '0.98h');
define(LIFESTREAM_VERSION, 0.98);
//define(LIFESTREAM_PLUGIN_FILE, 'lifestream/lifestream.php');
define(LIFESTREAM_PLUGIN_FILE, plugin_basename(__FILE__));
define(LIFESTREAM_FEEDS_PER_PAGE, 10);
define(LIFESTREAM_EVENTS_PER_PAGE, 25);
define(LIFESTREAM_ERRORS_PER_PAGE, 25);

if (!class_exists('SimplePie'))
{
	require_once(dirname(__FILE__) . '/lib/simplepie.inc.php');
}

global $wpdb, $userdata, $lifestream;

if (!function_exists('array_key_pop'))
{
	function array_key_pop($array, $key)
	{
		$value = $array[$key];
		unset($array[$key]);
		return $value;
	}
}
if (!function_exists('str_startswith'))
{
	function str_startswith($string, $chunk)
	{
		return substr($string, 0, strlen($chunk)) == $chunk;
	}
}
if (!function_exists('str_endswith'))
{
	function str_endswith($string, $chunk)
	{
		return substr($string, strlen($chunk)*-1) == $chunk;
	}
}
if (!function_exists('get_class_constant'))
{
	function get_class_constant($class, $const)
	{
		return constant(sprintf('%s::%s', $class, $const));
	}
}

class LifeStream_Error extends Exception { }
class LifeStream_ValidationError extends Exception { }
class LifeStream_FeedFetchError extends LifeStream_Error { }

class LifeStream_Event
{
	/**
	 * Represents a single event in the database.
	 */
	function __construct(&$lifestream, $row)
	{
		$this->lifestream = $lifestream;
		$this->date = $row->timestamp;
		$this->data = unserialize($row->data);
		$this->id = $row->id;
		$this->timestamp = $row->timestamp;
		$this->total = 1;
		$this->is_grouped = false;
		$this->key = $row->key;
		$this->owner = $row->owner;
		$this->owner_id = $row->owner_id;
		$this->visible = $row->visible;
		$this->link = ($this->data['link'] ? $this->data['link'] : $row->link);
		$cls = $this->lifestream->get_feed($row->feed);
		$this->feed = new $cls($this->lifestream, unserialize($row->options), $row->feed_id);
	}
	
	function __toString()
	{
		return $this->data['title'];
	}
	
	function get_event_display()
	{
		return $this->feed->get_event_display($this, $this->data);
	}
	
	function get_date()
	{
		return $this->date + LIFESTREAM_DATE_OFFSET*60*60;
	}
	
	/**
	 * Returns an HTML-ready string.
	 */
	function render($options=array())
	{
		return $this->feed->render($this, $options);
	}
	
	function get_url()
	{
		if (count($this->data) > 1)
		{
			// return the public url if it's grouped
			$url = $this->feed->get_public_url();
			if ($url) return $url;
		}
		else
		{
			$url = $this->data[0]['link'];
			if ($url) return $url;
		}
		return '#';
	}
}

class LifeStream_EventGroup extends LifeStream_Event
{
	/**
	 * Represents a grouped event in the database.
	 */
	 
	function __construct(&$lifestream, $row)
	{
		parent::__construct($lifestream, $row);
		$this->total = $row->total ? $row->total : 1;
		$this->is_grouped = true;
	}
	
	function get_event_display($bit)
	{
		return $this->feed->get_event_display($this, $bit);
	}
	
}

class Lifestream
{
	public $feeds = array();

	protected $valid_image_types = array('image/gif' => 'gif',  
		'image/jpeg' => 'jpeg',  
		'image/png' => 'png',  
		'image/gif' => 'gif',
		'image/x-icon' => 'ico',
		'image/bmp' => 'bmp',  
		'image/vnd.microsoft.icon' => 'ico'
	);

	protected $valid_image_extensions = array(
		'gif', 'jpg', 'jpeg', 'gif', 'png', 'ico'
	);
	
	function html_entity_decode($string)
	{
		$string = html_entity_decode($string);
		
		$string = preg_replace('~&#x0*([0-9a-f]+);~ei', 'chr(hexdec("\\1"))', $string);
		$string = preg_replace('~&#0*([0-9]+);~e', 'chr(\\1)', $string);

 		return $string;
	}

	function validate_image($url)
	{
		// // Check the extension
		// $bits = explode('.', basename($url));
		// if (count($bits) > 1)
		// {
		// 	$ext = $bits[count($bits)-1];
		// 	return (in_array($ext, $this->valid_image_extensions));
		// }
		$handler = $this->get_option('url_handler');

		$use_fsock = true;
		if (($handler == 'auto' && function_exists('curl_init')) || $handler == 'curl')
		{
			$use_fsock = false;
		}

		$file = new SimplePie_File($url, 10, 5, null, SIMPLEPIE_USERAGENT, $use_fsock);
		if (!$file->success)
		{
			return false;
		}
		// Attempt to check content type
		if (!empty($file->headers['content-type']))
		{
			return (in_array($file->headers['content-type'], $this->valid_image_types));
		}
		// Use GD if we can
		if (function_exists('imagecreatefromstring'))
		{
			return (imagecreatefromstring($file->body) !== false);
		}
		// Everything has failed, we'll just let it pass
		return true;
	}

	// options and their default values
	protected $_options = array(
		'day_format'		=> 'F jS',
		'hour_format'		=> 'g:ia',
		'timezone'			=> '6',
		'number_of_items'	=> '50',
		'date_interval'		=> '1 month',
		'digest_title'		=> 'Daily Digest for %s',
		'digest_body'		=> '%1$s',
		'digest_category'	=> '1',
		'digest_author'		=> '1',
		'daily_digest'		=> '0',
		'digest_interval'	=> 'daily',
		'digest_time'		=> '0',
		'update_interval'	=> '15',
		'show_owners'		=> '0',
		'use_ibox'			=> '1',
		'show_credits'		=> '1',
		'hide_details_default' => '1',
		'url_handler'		=> 'auto',
		'feed_items'		=> '10',
		'truncate_length'	=> '128',
	);
	
	function __construct()
	{
		$this->path = trailingslashit(get_bloginfo('wpurl')) . 'wp-content/plugins/lifestream';
		
		$this->_optioncache = null;
		
		add_filter('cron_schedules', array(&$this, 'get_cron_schedules'));

		add_action('admin_menu', array(&$this, 'options_menu'));
		add_action('wp_head', array(&$this, 'header'));
		add_filter('the_content', array(&$this, 'embed_callback'));
		add_action('init', array(&$this, 'init'));

		add_action('lifestream_digest_cron', array(&$this, 'digest_update'));
		add_action('lifestream_cron', array(&$this, 'update'));
		
		register_activation_hook(LIFESTREAM_PLUGIN_FILE, array(&$this, 'activate'));
		register_deactivation_hook(LIFESTREAM_PLUGIN_FILE, array(&$this, 'deactivate'));
	}
	
	function truncate($string, $length=128)
	{
		if (!($length > 0)) return $string;
		if (strlen($string) > $length)
		{
			$string = substr($string, 0, $length-3).'...';
		}
		return $string;
	}
	
	// To be quite honest, WordPress should be doing this kind of magic itself.
	
	function _populate_option_cache()
	{
		if (!$this->_optioncache)
		{
			$this->_optioncache = get_option('lifestream_options');
			if (!$this->_optioncache) $this->_optioncache = $this->_options;
		}
	}
	
	/**
	 * Fetches the value of an option. Returns `null` if the option is not set.
	 */
	function get_option($option, $default=null)
	{
		$this->_populate_option_cache();
		$value = $this->_optioncache[$option];
		if (!$value)
			return $default;
		return $value;
	}
	
	/**
	 * Removes an option.
	 */
	function delete_option($option)
	{
		$this->_populate_option-cache();
		unset($this->_optioncache[$option]);
		update_option('lifestream_options', $this->_optioncache);
	}
	
	/**
	 * Updates the value of an option.
	 */
	function update_option($option, $value)
	{
		$this->_populate_option_cache();
		$this->_optioncache[$option] = $value;
		update_option('lifestream_options', $this->_optioncache);
	}
	
	/**
	 * Sets an option if it doesn't exist.
	 */
	function add_option($option, $value)
	{
		$this->_populate_option_cache();
		if (!array_key_exists($option, $this->_optioncache))
		{
			$this->_optioncache[$option] = $value;
			add_option('lifestream_options', serialize($this->_optioncache));
		}
	}
	
	function __($text, $params=null)
	{
		if (!is_array($params))
		{
			$params = func_get_args();
			$params = array_slice($params, 1);
		}
		return vsprintf(__($text, 'lifestream'), $params);
	}
	
	function _e($text, $params=null)
	{
		if (!is_array($params))
		{
			$params = func_get_args();
			$params = array_slice($params, 1);
		}
		echo vsprintf(__($text, 'lifestream'), $params);
	}
	
	function init()
	{
		global $wpdb;

		$offset = $this->get_option('timezone');
		define(LIFESTREAM_DATE_OFFSET, $offset);

		load_plugin_textdomain('lifestream', 'wp-content/plugins/lifestream/locales');

		if (is_admin() && str_startswith($_GET['page'], 'lifestream'))
		{
			wp_enqueue_script('jquery');
			wp_enqueue_script('admin-forms');
		}
		add_feed('lifestream-feed', 'lifestream_rss_feed');

		// If this is an update we need to force reactivation
		if (LIFESTREAM_VERSION != $this->get_option('_version'))
		{
			$this->get_option('_version');
			$this->deactivate();
			$this->activate();
		}
	}
	
	function log_error($message, $feed_id=null)
	{
		global $wpdb;

		if ($feed_id)
		{
			$result = $wpdb->query($wpdb->prepare("INSERT INTO `".$wpdb->prefix."lifestream_error_log` (`feed_id`, `message`, `timestamp`) VALUES (%s, %s, %d)", $wpdb->escape($feed_id), $wpdb->escape($message), time()));
		}
		else
		{
			$result = $wpdb->query($wpdb->prepare("INSERT INTO `".$wpdb->prefix."lifestream_error_log` (`feed_id`, `message`, `timestamp`) VALUES (NULL, %s, %d)", $wpdb->escape($message), time()));
		}
	}
	
	function get_digest_interval()
	{
		$interval = $this->get_option('digest_interval');
		switch ($interval)
		{
			case 'weekly':
				return 3600*24*7;
			case 'daily':
				return 3600*24;
			case 'hourly':
				return 3600;
		}
	}
	
	function get_cron_schedules()
	{
		$cron['lifestream'] = array(
			'interval' => $this->get_option('update_interval') * 60,
			'display' => $this->__('On LifeStream update')
		);

		$cron['lifestream_digest'] = array(
			'interval' => $this->get_digest_interval(),
			'display' => $this->__('On LifeStream daily digest update')
		);
		return $cron;
	}
	
	function get_single_event($feed_type)
	{
		$events = $this->get_events(array('feed_types'=>array($feed_type), 'limit'=>1, 'break_groups'=>true));
		$event = $events[0];

		return $event;
	}
	
	function digest_update()
	{
		global $wpdb;

		if ($this->get_option('daily_digest') != '1') return;

		$hour_format = $this->get_option('hour_format');
		$day_format = $this->get_option('day_format');

		$interval = $this->get_digest_interval();

		$now = time();
		// If there was a previous digest, we show only events since it
		$from = $this->get_option('_last_digest');
		// Otherwise we show events within the interval period
		if (!$from) $from = $now - $interval;

		// make sure the post doesn't exist
		$results = $wpdb->get_results($wpdb->prepare("SELECT `post_id` FROM `".$wpdb->prefix."postmeta` WHERE `meta_key` = '_lifestream_digest_date' AND `meta_value` = %d LIMIT 0, 1", $now));
		if ($results) continue;

		$sql = $wpdb->prepare("SELECT t1.*, t2.`options` FROM `".$wpdb->prefix."lifestream_event_group` as `t1` INNER JOIN `".$wpdb->prefix."lifestream_feeds` as t2 ON t1.`feed_id` = t2.`id` WHERE t1.`timestamp` > %s AND t1.`timestamp` < %s ORDER BY t1.`timestamp` ASC", $from, $now);

		$results =& $wpdb->get_results($sql);
		$events = array();
		foreach ($results as &$result)
		{
			$events[] = new LifeStream_EventGroup($this, $result);
		}

		if (count($events))
		{
			ob_start();
			if (!include(dirname(__FILE__) . '/pages/daily-digest.inc.php')) return;
			$content = sprintf($this->get_option('digest_body'), ob_get_clean(), date($this->get_option('day_format'), $now), count($events));

			$data = array(
				'post_content' => $wpdb->escape($content),
				'post_title' => $wpdb->escape(sprintf($this->get_option('digest_title'), date($day_format, $now), date($hour_format, $now))),
				'post_date' => date('Y-m-d H:i:s', $now),
				'post_category' => array($this->get_option('digest_category')),
				'post_status' => 'publish',
				'post_author' => $wpdb->escape($this->get_option('digest_author')),
			);
			$post_id = wp_insert_post($data);
			add_post_meta($post_id, '_lifestream_digest_date', $now, true);
		}
		$this->update_option('_last_digest', $now);
	}
	
	// page output
	
	function options_menu()
	{
		global $wpdb;

		if (function_exists('add_menu_page'))
		{
			$basename = basename(LIFESTREAM_PLUGIN_FILE);

			$results =& $wpdb->get_results("SELECT COUNT(*) as `count` FROM `".$wpdb->prefix."lifestream_error_log` WHERE has_viewed = 0");
			$errors = $results[0]->count;

			add_menu_page('LifeStream', 'LifeStream', 'edit_posts', $basename, array(&$this, 'options_page'));
			add_submenu_page($basename, $this->__('LifeStream Feeds'), $this->__('Feeds'), 'edit_posts', $basename, array(&$this, 'options_page'));
			add_submenu_page($basename, $this->__('LifeStream Events'), $this->__('Events'), 'edit_posts', 'lifestream-events.php', array(&$this, 'options_page'));
			add_submenu_page($basename, $this->__('LifeStream Settings'), $this->__('Settings'), 'manage_options', 'lifestream-settings.php', array(&$this, 'options_page'));
			add_submenu_page($basename, $this->__('LifeStream Change Log'), $this->__('Change Log'), 'manage_options', 'lifestream-changelog.php', array(&$this, 'options_page'));
			add_submenu_page($basename, $this->__('LifeStream Errors'), $this->__('Errors (%d)', $errors), 'manage_options', 'lifestream-errors.php', array(&$this, 'options_page'));
			add_submenu_page($basename, $this->__('LifeStream Support Forums'), $this->__('Support Forums'), 'manage_options', 'lifestream-forums.php', array(&$this, 'options_page'));
		}
	}
	
	function header()
	{
		echo '<link rel="stylesheet" type="text/css" media="screen" href="'.$this->path.'/lifestream.css"/>';
		echo '<script type="text/javascript" src="'.$this->path.'/lifestream.js"></script>';
	}
	
	function options_page()
	{
		global $wpdb, $userdata;

		$wpdb->show_errors();

		$this->install();

		get_currentuserinfo();

		$date_format = sprintf('%s @ %s', $this->get_option('day_format'), $this->get_option('hour_format'));
		$basename = basename(LIFESTREAM_PLUGIN_FILE);

		$errors = array();
		$message = null;

		switch ($_GET['page'])
		{
			case 'lifestream-events.php':
				switch (strtolower($_REQUEST['op']))
				{
					case 'delete':
						if (!($ids = $_REQUEST['id'])) break;
						if (!is_array($ids)) $ids = array($ids);
						foreach ($ids as $id)
						{
							$result =& $wpdb->get_results($wpdb->prepare("SELECT `id`, `feed_id`, `timestamp`, `owner_id` FROM `".$wpdb->prefix."lifestream_event` WHERE `id` = %d", $id));
							if (!$result)
							{
								$errors[] = $this->__('The selected feed was not found.');
							}
							elseif (!current_user_can('manage_options') && $result[0]->owner_id != $userdata->ID)
							{
								$errors[] = $this->__('You do not have permission to do that.');
							}
							else
							{
								$result =& $result[0];
								$wpdb->query($wpdb->prepare("UPDATE `".$wpdb->prefix."lifestream_event` SET `visible` = 0 WHERE `id` = %d", $result->id));
								$wpdb->query($wpdb->prepare("UPDATE `".$wpdb->prefix."lifestream_event_group` SET `visible` = 0 WHERE `event_id` = %d", $result->id));

								// Now we have to update the batch if it exists.
								$group =& $wpdb->get_results($wpdb->prepare("SELECT `id` FROM `".$wpdb->prefix."lifestream_event_group` WHERE `event_id` IS NULL AND DATE(FROM_UNIXTIME(`timestamp`)) = DATE(FROM_UNIXTIME(%d)) AND `feed_id` = %d LIMIT 0, 1", $result->timestamp, $result->feed_id));
								if (count($group) == 1)
								{
									$group =& $group[0];
									$results =& $wpdb->get_results($wpdb->prepare("SELECT `data`, `link` FROM `".$wpdb->prefix."lifestream_event` WHERE `feed_id` = %d AND `visible` = 1 AND DATE(FROM_UNIXTIME(`timestamp`)) = DATE(FROM_UNIXTIME(%d))", $result->feed_id, $result->timestamp));
									if (count($results))
									{
										$events = array();
										foreach ($results as &$result)
										{
											$result->data = unserialize($result->data);
											$result->data['link'] = $result->link;
											$events[] = $result->data;
										}
										$wpdb->query($wpdb->prepare("UPDATE `".$wpdb->prefix."lifestream_event_group` SET `data` = %s, `total` = %d, `updated` = 1 WHERE `id` = %d", $wpdb->escape(serialize($events)), count($events), $group->id));
									}
									else
									{
										$wpdb->query($wpdb->prepare("DELETE FROM `".$wpdb->prefix."lifestream_event_group` WHERE `id` = %d", $group->id));
									}
								}
								else
								{
									$wpdb->query($wpdb->prepare("DELETE FROM `".$wpdb->prefix."lifestream_event_group` WHERE `event_id` = %d", $result->id));
								}
							}
							$message = $this->__('The selected events were hidden.');
						}
					break;
				}
			break;
			case 'lifestream-settings.php':
				if ($_POST['save'])
				{
					foreach (array_keys($this->_options) as $value)
					{
						$this->update_option($value, stripslashes($_POST['lifestream_'.$value]));
					}
					// We need to make sure the cron runs now
					$this->reschedule_cron();
				}
			break;
			default:
				$feedmsgs = array();
				switch (strtolower($_REQUEST['op']))
				{
					case 'refreshall':
						$results = $this->update_all($userdata->ID);
						foreach ($results as $id=>$result)
						{
							if (is_int($result)) $feedmsgs[$id] = $result;
							else $errors[] = $this->__('There was an error refreshing the selected feed: ID %s', $id);
						}
						$message = $this->__('All of your feeds have been refreshed.');
						break;
					case 'refresh':
						if (!$_REQUEST['id']) break;
						foreach ($_REQUEST['id'] as $id)
						{
							$result =& $wpdb->get_results($wpdb->prepare("SELECT * FROM `".$wpdb->prefix."lifestream_feeds` WHERE `id` = %d LIMIT 0, 1", $id));
							if (!$result)
							{
								$errors[] = $this->__('The selected feed was not found.');
							}
							elseif (!current_user_can('manage_options') && $result[0]->owner_id != $userdata->ID)
							{
								$errors[] = $this->__('You do not have permission to do that.');
							}
							else
							{
								$instance = LifeStream_Feed::construct_from_query_result($this, $result[0]);
								$msg_arr = $instance->refresh();
								if ($msg_arr[0] !== false)
								{
									$message = $this->__('The selected feeds and their events have been refreshed.');
									$feedmsgs[$instance->id] = $msg_arr[1];
								}
								else
								{
									$errors[] = $this->__('There was an error refreshing the selected feed: ID %s', $instance->id);
								}
							}
						}
					break;
					case 'delete':
						if (!$_REQUEST['id']) break;
						foreach ($_REQUEST['id'] as $id)
						{
							$result =& $wpdb->get_results($wpdb->prepare("SELECT * FROM `".$wpdb->prefix."lifestream_feeds` WHERE `id` = %d LIMIT 0, 1", $id));
							if (!$result)
							{
								$errors[] = $this->__('The selected feed was not found.');
							}
							elseif (!current_user_can('manage_options') && $result[0]->owner_id != $userdata->ID)
							{
								$errors[] = $this->__('You do not have permission to do that.');
							}
							else
							{
								$instance = LifeStream_Feed::construct_from_query_result($this, $result[0]);
								$instance->delete();
								$message = $this->__('The selected feeds and all related events has been removed.');
							}
						}
					break;
					case 'edit':
						$result =& $wpdb->get_results($wpdb->prepare("SELECT * FROM `".$wpdb->prefix."lifestream_feeds` WHERE `id` = %d LIMIT 0, 1", $_GET['id']));
						if (!$result)
						{
							$errors[] = $this->__('The selected feed was not found.');
						}
						elseif (!current_user_can('manage_options') && $result[0]->owner_id != $userdata->ID)
						{
							$errors[] = $this->__('You do not have permission to do that.');
						}
						else
						{
							$instance = LifeStream_Feed::construct_from_query_result($this, $result[0]);

							$options = $instance->get_options();

							if ($_POST['save'])
							{
								$values = array();
								foreach ($options as $option=>$option_meta)
								{
									if ($option_meta[1] && !$_POST[$option])
									{
										$errors[] = $option_meta[0].' is required.';
									}
									else
									{
										$values[$option] = stripslashes($_POST[$option]);
									}
								}
								if ($instance->get_constant('MUST_GROUP'))
								{
									$values['grouped'] = 1;
								}
								elseif ($instance->get_constant('CAN_GROUP'))
								{
									$values['grouped'] = $_POST['grouped'];
								}
								$values['feed_label'] = $_POST['feed_label'];
								$values['icon_url'] = $_POST['icon_url'];
								$values['auto_icon'] = $_POST['auto_icon'];
								if ($_POST['owner'] != $instance->owner_id && current_user_can('manage_options'))
								{
									$instance->owner_id = $_POST['owner'];
									$usero = new WP_User($author->user_id);
									$owner = $usero->data;
									$instance->owner = $owner->display_name;
								}
								if (!count($errors))
								{
									$instance->options = $values;
									$instance->save();
									unset($_POST);
								}
							}
						}
					break;
					case 'add':
						if ($_POST)
						{
							$class_name = $this->get_feed($_GET['feed']);
							if (!$class_name) break;
							$feed = new $class_name($this);
							$values = array();
							$options = $feed->get_options();
							foreach ($options as $option=>$option_meta)
							{
								if ($option_meta[1] && !$_POST[$option])
								{
									$errors[] = $option_meta[0].' is required.';
								}
								else
								{
									$values[$option] = stripslashes($_POST[$option]);
								}
							}
							if ($feed->get_constant('MUST_GROUP'))
							{
								$values['grouped'] = 1;
							}
							elseif ($feed->get_constant('CAN_GROUP'))
							{
								$values['grouped'] = $_POST['grouped'];
							}
							$values['feed_label'] = $_POST['feed_label'];
							$values['icon_url'] = $_POST['icon_url'];
							$values['auto_icon'] = $_POST['auto_icon'];
							if (current_user_can('manage_options'))
							{
								$feed->owner_id = $_POST['owner'];
								$usero = new WP_User($feed->owner_id);
								$owner = $usero->data;
								$feed->owner = $owner->display_name;
							}
							else
							{
								$feed->owner_id = $userdata->ID;
								$feed->owner = $userdata->display_name;
							}
							$feed->options = $values;
							if (!count($errors))
							{
								if (!($error = $feed->test()))
								{
									$result = $feed->save();
									if ($result !== false)
									{
										unset($_POST);
										unset($_REQUEST['op']);
										$msg_arr = $feed->refresh(null, true);
										if ($msg_arr[0] !== false)
										{
											$message = $this->__('A new %s feed was added to your LifeStream.', $feed->get_constant('NAME'));
											$feedmsgs[$feed->id] = $msg_arr[1];
											unset($_POST);
										}
									}
								}
								else
								{
									$errors[] = $error;
								}
							}
						}
					break;
				}
			break;
		}
		$lifestream = &$this;
		ob_start();
		?>
		<style type="text/css">
		.feedlist { margin: 0; padding: 0; }
		.feedlist li { list-style: none; display: inline; }
		.feedlist li a { float: left; display: block; padding: 2px; margin: 1px; width: 23%; text-decoration: none; }
		.feedlist li a:hover { background-color: #e9e9e9; }
		.success { color: #397D33; background-color: #D1FBCA; }
		.error { border-color: #E25F53; color: #E25F53; }
		td.icon { padding: 7px 0 9px 10px; }
		</style>
		<br />
		<?php
		if (count($errors)) { ?>
		<div id="message" class="error"><p><strong><?php $this->_e('There were errors with your request:') ?></strong></p><ul>
			<?php foreach ($errors as $error) { ?>
				<li><?php echo nl2br(LifeStream_Feed::parse_urls(htmlspecialchars($error))); ?></li>
			<?php } ?>
		</ul></div>
		<?php } elseif ($message) { ?>
		<div id="message" class="updated fade"><p><strong><?php echo $message; ?></strong></p></div>
		<?php } ?>
		<div class="wrap">
			<?php
			switch ($_GET['page'])
			{
				case 'lifestream-errors.php':
					$page = $_GET['paged'] ? $_GET['paged'] : 1;
					switch ($_REQUEST['op'])
					{
						case 'clear':
							$wpdb->query("DELETE FROM `".$wpdb->prefix."lifestream_error_log`");
						break;
					}
					$start = ($page-1)*LIFESTREAM_ERRORS_PER_PAGE;
					$end = $page*LIFESTREAM_ERRORS_PER_PAGE;

					$wpdb->query("UPDATE `".$wpdb->prefix."lifestream_error_log` SET has_viewed = 1");

					$results =& $wpdb->get_results("SELECT COUNT(*) as `count` FROM `".$wpdb->prefix."lifestream_error_log`");
					$number_of_pages = ceil($results[0]->count/LIFESTREAM_EVENTS_PER_PAGE);
					$results =& $wpdb->get_results($wpdb->prepare("SELECT t1.*, t2.`feed`, t2.`options` FROM `".$wpdb->prefix."lifestream_error_log` as t1 LEFT JOIN `".$wpdb->prefix."lifestream_feeds` as t2 ON t1.`feed_id` = t2.`id` ORDER BY t1.`timestamp` DESC LIMIT %d, %d", $start, $end));

					include(dirname(__FILE__) . '/pages/errors.inc.php');
				break;
				case 'lifestream-changelog.php':
					include(dirname(__FILE__) . '/pages/changelog.inc.php');
				break;
				case 'lifestream-forums.php':
					include(dirname(__FILE__) . '/pages/forums.inc.php');
				break;
				case 'lifestream-settings.php':
					$lifestream_digest_intervals = array(
						'weekly'	=> $this->__('Weekly'),
						'daily'		=> $this->__('Daily'),
						'hourly'	=> $this->__('Hourly'),
					);
					include(dirname(__FILE__) . '/pages/settings.inc.php');
				break;
				case 'lifestream-events.php':
					$page = $_GET['paged'] ? $_GET['paged'] : 1;
					$start = ($page-1)*LIFESTREAM_EVENTS_PER_PAGE;
					$end = $page*LIFESTREAM_EVENTS_PER_PAGE;

					if (!current_user_can('manage_options'))
					{
						$rows =& $wpdb->get_row($wpdb->prepare("SELECT COUNT(*) as `count` FROM `".$wpdb->prefix."lifestream_event` WHERE `owner_id` = %d", $userdata->ID));
						$number_of_pages = ceil($rows->count/LIFESTREAM_EVENTS_PER_PAGE);
						$rows =& $wpdb->get_results($wpdb->prepare("SELECT t1.*, t2.`feed`, t2.`options` FROM `".$wpdb->prefix."lifestream_event` as t1 JOIN `".$wpdb->prefix."lifestream_feeds` as t2 ON t1.`feed_id` = t2.`id` WHERE t1.`owner_id` = %d ORDER BY t1.`timestamp` DESC LIMIT %d, %d", $userdata->ID, $start, $end));
					}
					else
					{
						$rows =& $wpdb->get_row("SELECT COUNT(*) as `count` FROM `".$wpdb->prefix."lifestream_event`");
						$number_of_pages = ceil($rows->count/LIFESTREAM_EVENTS_PER_PAGE);
						$rows =& $wpdb->get_results($wpdb->prepare("SELECT t1.*, t2.`feed`, t2.`options` FROM `".$wpdb->prefix."lifestream_event` as t1 JOIN `".$wpdb->prefix."lifestream_feeds` as t2 ON t1.`feed_id` = t2.`id` ORDER BY t1.`timestamp` DESC LIMIT %d, %d", $start, $end));
					}
					$results = array();
					foreach ($rows as $result)
					{
						$results[] = new LifeStream_Event($lifestream, $result);
					}
					unset($rows);
					
					include(dirname(__FILE__) . '/pages/events.inc.php');
				break;
				default:
					switch ($_REQUEST['op'])
					{
						case 'edit':
							include(dirname(__FILE__) . '/pages/edit-feed.inc.php');
						break;
						case 'add':
							$identifier = $_GET['feed'];
							$class_name = $this->get_feed($identifier);
							if (!$class_name) break;
							$feed = new $class_name($this);
							$options = $feed->get_options();
							include(dirname(__FILE__) . '/pages/add-feed.inc.php');
						break;
						default:
							$page = $_GET['paged'] ? $_GET['paged'] : 1;
							$start = ($page-1)*LIFESTREAM_FEEDS_PER_PAGE;
							$end = $page*LIFESTREAM_FEEDS_PER_PAGE;
							if (!current_user_can('manage_options'))
							{
								$rows =& $wpdb->get_row($wpdb->prepare("SELECT COUNT(*) as `count` FROM `".$wpdb->prefix."lifestream_feeds` WHERE `owner_id` = %d", $userdata->ID));
								$number_of_pages = ceil($rows->count/LIFESTREAM_FEEDS_PER_PAGE);
								$rows =& $wpdb->get_results($wpdb->prepare("SELECT t1.*, (SELECT COUNT(1) FROM `".$wpdb->prefix."lifestream_event` WHERE `feed_id` = t1.`id`) as `events` FROM `".$wpdb->prefix."lifestream_feeds` as t1 WHERE t1.`owner_id` = %d ORDER BY `id` LIMIT %d, %d", $userdata->ID, $start, $end));
							}
							else
							{
								$rows =& $wpdb->get_row("SELECT COUNT(*) as `count` FROM `".$wpdb->prefix."lifestream_feeds`");
								$number_of_pages = ceil($rows->count/LIFESTREAM_FEEDS_PER_PAGE);
								$rows =& $wpdb->get_results($wpdb->prepare("SELECT t1.*, (SELECT COUNT(1) FROM `".$wpdb->prefix."lifestream_event` WHERE `feed_id` = t1.`id`) as `events` FROM `".$wpdb->prefix."lifestream_feeds` as t1 ORDER BY `id` LIMIT %d, %d", $start, $end));
							}
							$results = array();
							foreach ($rows as $result)
							{
								$results[] = LifeStream_Feed::construct_from_query_result($this, $result);
							}
							if ($results !== false)
							{
								include(dirname(__FILE__) . '/pages/feeds.inc.php');
							}
						break;
					}
				break;
			}
			?>
		</div>
		<?php
		ob_end_flush();
	}
	
	/**
	 * Attempts to update all feeds
	 */
	function update()
	{
		$event_arr = $this->update_all();
		$events = 0;
		foreach ($event_arr as $instance=>$result)
		{
			if (is_int($result)) $events += $result;
		}
		return $events;
	}
	
	function update_all()
	{
		global $wpdb;
		$this->update_option('_last_update', time());
		$events = array();
		$results =& $wpdb->get_results("SELECT * FROM `".$wpdb->prefix."lifestream_feeds`");
		foreach ($results as $result)
		{
			$instance = LifeStream_Feed::construct_from_query_result($this, $result);
			try
			{
				$feed_msg = $instance->refresh();
				$events[$instance->id] = $feed_msg[1];
			}
			catch (LifeStream_FeedFetchError $ex)
			{
				$this->log_error($ex, $instance->id);
				$events[$instance->id] = $ex;
			}
		}
		return $events;	
	}
	/**
	 * Registers a feed class with LifeStream.
	 */
	function register_feed($class_name)
	{
		$this->feeds[get_class_constant($class_name, 'ID')] = $class_name;

		ksort($this->feeds);
	}
	
	function get_feed($class_name)
	{
		return $this->feeds[$class_name];
	}

	/**
	 * Similar to file_get_contents but will use curl by default.
	 */
	function file_get_contents($url)
	{
		$handler = $this->get_option('url_handler');

		$use_fsock = true;
		if (($handler == 'auto' && function_exists('curl_init')) || $handler == 'curl')
		{
			$use_fsock = false;
		}

		$file = new SimplePie_File($url, 10, 5, null, SIMPLEPIE_USERAGENT, $use_fsock);
		if (!$file->success)
		{
			throw new LifeStream_FeedFetchError('Failed to open url: '.$url .' ('.$file->error.')');
		}
		return $file->body;
	}

	/*
	 * This is a wrapper function which initiates the callback for the custom tag embedding.
	 */
	function embed_callback($content)
	{
		return preg_replace_callback("|\[lifestream(?:\s+([^\]]+))?\]|i", array(&$this, 'embed_handler'), $content);
		return preg_replace_callback("|<\[]lifestream(?:\s+([^>\]+]))?/?[>\]]|i", array(&$this, 'embed_handler'), $content);
	}

	/*
	 * This function handles the real meat by handing off the work to helper functions.
	 */
	function embed_handler($matches)
	{
		$args = array();
		if (count($matches) > 1)
		{
			preg_match_all("|(?:([a-z_]+)=[\"']?([a-z0-9_-\s]+)[\"']?)\s*|i", $matches[1], $options);
			for ($i=0; $i<count($options[1]); $i++)
			{
				if ($options[$i]) $args[$options[1][$i]] = $options[2][$i];
			}
		}
		ob_start();
		if ($args['feed_ids']) $args['feed_ids'] = explode(',', $args['feed_ids']);
		if ($args['user_ids']) $args['user_ids'] = explode(',', $args['user_ids']);
		if ($args['feed_types']) $args['feed_types'] = explode(',', $args['feed_types']);
		lifestream($args);
		return ob_get_clean();
	}

	function reschedule_cron()
	{
		wp_clear_scheduled_hook('lifestream_cron');
		wp_clear_scheduled_hook('lifestream_digest_cron');
		// First lifestream cron should not happen instantly, incase we need to reschedule
		wp_schedule_event(time()+60, 'lifestream', 'lifestream_cron');
		// We have to calculate the time for the first digest
		$digest_time = $this->get_option('digest_time');
		$digest_interval = $this->get_option('digest_interval');
		$time = time();
		if ($digest_interval == 'hourly')
		{
			// Start at the next hour
			$time = strtotime(date('Y-m-d H:00:00', strtotime('+1 hour', $time)));
		}
		else
		{
			// If time has already passed for today, set it for tomorrow
			if (date('H') > $digest_time) $time = strtotime('+1 day', $time);
			$time = strtotime(date('Y-m-d '.$digest_time.':00:00', $time));
		}
		wp_schedule_event($time, 'lifestream_digest', 'lifestream_digest_cron');
	}

	function deactivate()
	{
		wp_clear_scheduled_hook('lifestream_cron');
		wp_clear_scheduled_hook('lifestream_digest_cron');
	}

	/**
	 * Initializes the plug-in upon activation.
	 */
	function activate()
	{
		if (version_compare(PHP_VERSION, '5.0', '<'))
		{
			deactivate_plugins(LIFESTREAM_PLUGIN_FILE);
			return;
		}
		
		global $wpdb;

		// Options/database install
		$this->install();
		
		// Cron job for the update
		$this->reschedule_cron();

		// Add a feed for this blog
		$results =& $wpdb->get_results("SELECT COUNT(*) as `count` FROM `".$wpdb->prefix."lifestream_feeds`");
		if (!$results[0]->count)
		{
			$rss_url = get_bloginfo('rss2_url');
			$options = array('url' => $rss_url);

			$feed = new LifeStream_BlogFeed($this, $options);
			$feed->owner = 'admin';
			$feed->owner_id = 1;
			$feed->save();
			$feed->refresh(null, true);
		}
	}

	function credits()
	{
		return 'Powered by <a href="http://www.ibegin.com/labs/wp-lifestream/">LifeStream</a> from <a href="http://www.ibegin.com/">iBegin</a>.';
	}

	/**
	 * Adds/updates the options on plug-in activation.
	 */
	function install($allow_database_install=true)
	{
		$version = $this->get_option('_version');

		if (!$version) $version = 0;

		if ($allow_database_install) $this->install_database($version);

		if ($version < 0.95)
		{
			foreach ($this->_options as $key=>$value)
			{
				$ovalue = get_option('lifestream_' . $key);
				if (!$ovalue)
				{
					$value = $value;
				}
				else
				{
					delete_option('lifestream_' . $key);
				}
				$this->add_option($key, $value);
			}
		}

		if ($version == LIFESTREAM_VERSION) return;

		// default options and their values
		foreach ($this->_options as $key=>$value)
		{
			$this->add_option($key, $value);
		}
		
		$this->update_option('_version', LIFESTREAM_VERSION);
	}

	/**
	 * Executes a MySQL query with exception handling.
	 */
	function safe_query($sql)
	{
		global $wpdb;

		$result = $wpdb->query($sql);
		if ($result === false)
		{
			if ($wpdb->error)
			{
				$reason = $wpdb->error->get_error_message();
			}
			else
			{
				$reason = $this->__('Unknown SQL Error');
			}
			$this->log_error($reason);
			throw new LifeStream_Error($reason);
		}
		return $result;
	}

	/**
	 * Initializes the database if it's not already present.
	 */
	function install_database($version)
	{
		global $wpdb, $userdata;

		get_currentuserinfo();

		$this->safe_query("CREATE TABLE IF NOT EXISTS `".$wpdb->prefix."lifestream_event` (
		  `id` int(11) NOT NULL auto_increment,
		  `feed_id` int(11) NOT NULL,
		  `feed` varchar(32) NOT NULL,
		  `link` varchar(200) NOT NULL,
		  `data` blob NOT NULL,
		  `visible` tinyint(1) default 1 NOT NULL,
		  `timestamp` int(11) NOT NULL,
		  `version` int(11) default 0 NOT NULL,
		  `key` char(16) NOT NULL,
		  `owner` varchar(128) NOT NULL,
		  `owner_id` int(11) NOT NULL,
		  PRIMARY KEY  (`id`),
		  INDEX `feed` (`feed`),
		  UNIQUE `feed_id` (`feed_id`, `key`, `owner_id`, `link`)
		) ENGINE=MyISAM;");

		$this->safe_query("CREATE TABLE IF NOT EXISTS `".$wpdb->prefix."lifestream_event_group` (
		  `id` int(11) NOT NULL auto_increment,
		  `feed_id` int(11) NOT NULL,
		  `event_id` int(11) NULL,
		  `feed` varchar(32) NOT NULL,
		  `data` blob NOT NULL,
		  `total` int(11) default 1 NOT NULL,
		  `updated` tinyint(1) default 0 NOT NULL,
		  `visible` tinyint(1) default 1 NOT NULL,
		  `timestamp` int(11) NOT NULL,
		  `version` int(11) default 0 NOT NULL,
		  `key` char(16) NOT NULL,
		  `owner` varchar(128) NOT NULL,
		  `owner_id` int(11) NOT NULL,
		  PRIMARY KEY  (`id`),
		  INDEX `feed` (`feed`),
		  INDEX `feed_id` (`feed_id`, `key`, `owner_id`, `timestamp`)
		) ENGINE=MyISAM;");

		$this->safe_query("CREATE TABLE IF NOT EXISTS `".$wpdb->prefix."lifestream_feeds` (
		  `id` int(11) NOT NULL auto_increment,
		  `feed` varchar(32) NOT NULL,
		  `options` text default NULL,
		  `timestamp` int(11) NOT NULL,
		  `owner` varchar(128) NOT NULL,
		  `owner_id` int(11) NOT NULL,
		  `version` int(11) default 0 NOT NULL,
		  INDEX `owner_id` (`owner_id`),
		  PRIMARY KEY  (`id`)
		) ENGINE=MyISAM;");

		$this->safe_query("CREATE TABLE IF NOT EXISTS `".$wpdb->prefix."lifestream_error_log` (
		  `id` int(11) NOT NULL auto_increment,
		  `message` varchar(255) NOT NULL,
		  `trace` text NULL,
		  `feed_id` int(11) NULL,
		  `timestamp` int(11) NOT NULL,
		  `has_viewed` tinyint(1) default 0 NOT NULL,
		  INDEX `feed_id` (`feed_id`, `has_viewed`),
		  INDEX `has_viewed` (`has_viewed`),
		  PRIMARY KEY  (`id`)
		) ENGINE=MyISAM;");

		if (!$version) return;

		// URGENT TODO: we need to solve alters when the column already exists due to WP issues

		if ($version < 0.5)
		{
			// Old wp-cron built-in stuff
			wp_clear_scheduled_hook('LifeStream_Hourly');

			// Upgrade them to version 0.5
			$wpdb->query("ALTER IGNORE TABLE `".$wpdb->prefix."lifestream_event_group` ADD `version` INT(11) NOT NULL DEFAULT '0' AFTER `timestamp`, ADD `key` CHAR( 16 ) NOT NULL AFTER `version`;");
			$wpdb->query("ALTER IGNORE TABLE `".$wpdb->prefix."lifestream_event` ADD `version` INT(11) NOT NULL DEFAULT '0' AFTER `timestamp`, ADD `key` CHAR( 16 ) NOT NULL AFTER `version`;");
		}
		if ($version < 0.6)
		{
			$wpdb->query("ALTER IGNORE TABLE `".$wpdb->prefix."lifestream_event_group` ADD `owner` VARCHAR(128) NOT NULL AFTER `key`, ADD `owner_id` INT(11) NOT NULL AFTER `owner`;");
			$wpdb->query("ALTER IGNORE TABLE `".$wpdb->prefix."lifestream_event` ADD `owner` VARCHAR(128) NOT NULL AFTER `key`, ADD `owner_id` INT(11) NOT NULL AFTER `owner`;");
			$wpdb->query("ALTER IGNORE TABLE `".$wpdb->prefix."lifestream_feeds` ADD `owner` VARCHAR(128) NOT NULL AFTER `timestamp`, ADD `owner_id` INT(11) NOT NULL AFTER `owner`;");
			$wpdb->query("ALTER IGNORE TABLE `".$wpdb->prefix."lifestream_event` DROP INDEX `feed_id`, ADD UNIQUE `feed_id` (`feed_id` , `key` , `owner_id` , `link` );");
			$wpdb->query("ALTER IGNORE TABLE `".$wpdb->prefix."lifestream_event_group` DROP INDEX `feed_id`, ADD INDEX `feed_id` (`feed_id` , `key` , `timestamp` , `owner_id`);");
			$wpdb->query("ALTER IGNORE TABLE `".$wpdb->prefix."lifestream_feeds` ADD INDEX `owner_id` (`owner_id`);");
			$this->safe_query($wpdb->prepare("UPDATE `".$wpdb->prefix."lifestream_feeds` SET `owner` = %s, `owner_id` = %d", $userdata->display_name, $userdata->ID));
			$this->safe_query($wpdb->prepare("UPDATE `".$wpdb->prefix."lifestream_event` SET `owner` = %s, `owner_id` = %d", $userdata->display_name, $userdata->ID));
			$this->safe_query($wpdb->prepare("UPDATE `".$wpdb->prefix."lifestream_event_group` SET `owner` = %s, `owner_id` = %d", $userdata->display_name, $userdata->ID));
		}
		if ($version < 0.81)
		{
			$wpdb->query("ALTER IGNORE TABLE `".$wpdb->prefix."lifestream_event` ADD `feed` VARCHAR(32) NOT NULL AFTER `feed_id`");
			$this->safe_query("UPDATE IGNORE `".$wpdb->prefix."lifestream_event` as t1 set t1.`feed` = (SELECT t2.`feed` FROM `".$wpdb->prefix."lifestream_feeds` as t2 WHERE t1.`feed_id` = t2.`id`)");
		}
		if ($version < 0.84)
		{
			$wpdb->query("ALTER IGNORE TABLE `".$wpdb->prefix."lifestream_event` ADD INDEX ( `feed` )");
			$wpdb->query("ALTER IGNORE TABLE `".$wpdb->prefix."lifestream_event_group` ADD INDEX ( `feed` )");
		}
		if ($version < 0.90)
		{
			$wpdb->query("ALTER IGNORE TABLE `".$wpdb->prefix."lifestream_feeds` ADD `version` int(11) default 0 NOT NULL AFTER `owner_id`");
		}
	}
	
	
	/**
	 * Gets recent events from the lifestream.
	 * @param {Array} $_ An array of keyword args.
	 */
	function get_events($_=array())
	{
		global $wpdb;

		setlocale(LC_ALL, WPLANG);

		$defaults = array(
			 // number of events
			'limit'				=> $this->get_option('number_of_items'),
			// offset of events (e.g. pagination)
			'offset'			=> 0,
			// array of feed ids
			'feed_ids'			=> array(),
			// array of user ids
			'user_ids'			=> array(),
			// array of feed type identifiers
			'feed_types'		=> array(),
			// interval for date cutoff (see mysql INTERVAL)
			'date_interval'		=> $this->get_option('date_interval'),
			// start date of events
			'start_date'		=> -1,
			// end date
			'end_date'			=> -1,
			// minimum number of events in group
			'event_total_min'	=> -1,
			// maximum
			'event_total_max'	=> -1,
			// break groups into single events
			'break_groups'		=> false,
		);

		$_ = array_merge($defaults, $_);
		
		# If any arguments are invalid we bail out

		// Old-style
		if ($_['number_of_results']) $_['limit'] = $_['number_of_results'];

		if (!((int)$_['limit'] > 0)) return;
		if (!((int)$_['offset'] >= 0)) return;

		if (!preg_match('/[\d]+ (month|day|year|hour|second|microsecond|week|quarter)s?/', $_['date_interval'])) $_['date_interval'] = -1;
		else $_['date_interval'] = rtrim($_['date_interval'], 's');

		$_['feed_ids'] = (array)$_['feed_ids'];
		$_['user_ids'] = (array)$_['user_ids'];
		$_['feed_types'] = (array)$_['feed_types'];

		$where = array('t1.`visible` = 1');
		if (count($_['feed_ids']))
		{
			foreach ($_['feed_ids'] as $key=>$value)
			{
				$_['feed_ids'][$key] = $wpdb->escape($value);
			}
			$where[] = 't1.`feed_id` IN ('.implode(', ', $_['feed_ids']).')';
		}
		elseif (count($_['feed_types']))
		{
			foreach ($_['feed_types'] as $key=>$value)
			{
				$_['feed_types'][$key] = $wpdb->escape($value);
			}
			$where[] = 't1.`feed` IN ("'.implode('", "', $_['feed_types']).'")';
		}
		if (count($_['user_ids']))
		{
			foreach ($_['user_ids'] as $key=>$value)
			{
				$_['user_ids'][$key] = $wpdb->escape($value);
			}
			$where[] = 't1.`owner_id` IN ('.implode(', ', $_['user_ids']).')';
		}
		if ($_['event_total_max'] > -1)
		{
			$where[] = sprintf('t1.`total` <= %d', $_['event_total_max']);
		}
		if ($_['event_total_min'] > -1)
		{
			$where[] = sprintf('t1.`total` >= %d', $_['event_total_min']);
		}
		if ($_['date_interval'] !== -1)
		{
			$where[] = sprintf('t1.`timestamp` > UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL %s))', $_['date_interval']);
		}
		if ($_['start_date'] !== -1)
		{
			if (!is_int($_['start_date'])) $_['start_date'] = strtotime($_['start_date']);
			$where[] = sprintf('t1.`timestamp` >= %s', $_['start_date']);
		}
		if ($_['end_date'] !== -1)
		{
			if (!is_int($_['end_date'])) $_['end_date'] = strtotime($_['end_date']);
			$where[] = sprintf('t1.`timestamp` <= %s', $_['end_date']);
		}

		if ($_['break_groups'])
		{
			// we select from lifestream_event vs grouped
			$table = 'lifestream_event';
			$cls = 'LifeStream_Event';
		}
		else
		{
			$table = 'lifestream_event_group';
			$cls = 'LifeStream_EventGroup';
		}
		$sql = sprintf("SELECT t1.*, t2.`options` FROM `".$wpdb->prefix.$table."` as `t1` INNER JOIN `".$wpdb->prefix."lifestream_feeds` as t2 ON t1.`feed_id` = t2.`id` WHERE (%s) ORDER BY t1.`timestamp` DESC LIMIT %d, %d", implode(') AND (', $where), $_['offset'], $_['limit']);

		$results =& $wpdb->get_results($sql);
		$events = array();
		foreach ($results as &$result)
		{
			if (array_key_exists($result->feed, $this->feeds))
			{
				$events[] = new $cls($this, $result);
			}
		}
		return $events;
	}
}

$lifestream = new Lifestream();

function lifestream_get_single_event($feed_type)
{
	global $lifestream;
	
	return $lifestream->get_single_event($feed_type);
}

require_once(dirname(__FILE__) . '/inc/labels.php');

abstract class LifeStream_Extension
{
	/**
	 * Represents a feed object in the database.
	 */
	
	public $options;
	
	// The ID must be a-z, 0-9, _, and - characters. It also must be unique.
	const ID			= 'generic';
	const NAME			= 'Generic';
	const AUTHOR		= 'David Cramer';
	const URL			= '';
	const DESCRIPTION	= '';
	// Can this feed be grouped?
	const CAN_GROUP		= true;
	// Can this feed have a label?
	const MUST_GROUP	= false;
	// Labels used in rendering each event
	// params: feed name, event descriptor
	const LABEL			= 'LifeStream_Label';
	// The version is so you can manage data in the database for old versions.
	const VERSION		= 2;
	const MEDIA			= 'automatic';

	/**
	 * Instantiates this object through a feed database object.
	 */
	public static function construct_from_query_result(&$lifestream, $row)
	{
		$class = $lifestream->get_feed($row->feed);
		if (!$class) return false;
		
		if (!empty($row->options)) $options = unserialize($row->options);
		else $options = null;
		
		$instance = new $class($lifestream, $options, $row->id, $row);
		$instance->date = $row->timestamp;
		if ($row->feed != $instance->get_constant('ID'))
		{
			throw new Exception('This shouldnt be happening...');
		}
		return $instance;
	}

	function __construct(&$lifestream, $options=array(), $id=null, $row=null)
	{
		$this->lifestream = $lifestream;
		$this->options = $options;
		$this->id = $id;
		if ($row)
		{
			$this->owner = $row->owner;
			$this->owner_id = $row->owner_id;
			$this->_owner_id = $row->owner_id;
			$this->version = $row->version;
		}
		else
		{
			$this->version = $this->get_constant('VERSION');
		}
	}
	
	function __toInt()
	{
		return $this->id;
	}
	
	function __toString()
	{
		return $this->get_url();
	}
	
	function get_event_display($event, $bit)
	{
		return $bit['title'];
	}
	
	function get_feed_display()
	{
		return $this->__toString();
	}
	
	function get_icon_url()
	{
		if (!empty($this->options['icon_url']))
		{
			return $this->options['icon_url'];
		}
		return $this->lifestream->path . '/images/' . $this->get_constant('ID') . '.png';
	}

	function get_public_url()
	{
		return $this->get_constant('URL');
	}
	
	function get_image_url($row, $item)
	{
		return is_array($item['image']) ? $item['image']['url'] : $item['image'];
	}

	function get_thumbnail_url($row, $item)
	{
		// Array checks are for backwards compatbility
		return is_array($item['thumbnail']) ? $item['thumbnail']['url'] : $item['thumbnail'];
	}

	function get_public_name()
	{
		if (!empty($this->options['feed_label']))
		{
			return $this->options['feed_label'];
		}
		return $this->get_constant('NAME');
	}
	
	/**
	 * Returns a constant attached to this class.
	 * @param {string} $constant
	 * @return {string | integer} $value
	 */
	function get_constant($constant)
	{
		return constant(sprintf('%s::%s', get_class($this), $constant));
	}
	
	/**
	 * Returns an array of available options.
	 * @return {array} Available options.
	 */
	function get_options()
	{		
		return array(
			// key => array(label, required, default value, choices)
			'url' => array($this->lifestream->__('Feed URL:'), true, '', ''),
		);
	}
	
	function save()
	{
		global $wpdb;

		$this->save_options();
		// If it has an ID it means it already exists.
		if ($this->id)
		{
			$result = $wpdb->query($wpdb->prepare("UPDATE `".$wpdb->prefix."lifestream_feeds` set `options` = %s, `owner` = %s, `owner_id` = %d WHERE `id` = %d", serialize($this->options), $this->owner, $this->owner_id, $this->id));
			if ($this->_owner_id && $this->_owner_id != $this->owner_id)
			{
				$wpdb->query($wpdb->prepare("UPDATE `".$wpdb->prefix."lifestream_event` SET `owner` = %s, `owner_id` = %d WHERE `feed_id` = %d", $this->owner, $this->owner_id, $this->id));
				$wpdb->query($wpdb->prepare("UPDATE `".$wpdb->prefix."lifestream_event_group` SET `owner` = %s, `owner_id` = %d WHERE `feed_id` = %d", $this->owner, $this->owner_id, $this->id));
			}
		}
		else
		{
			$result = $wpdb->query($wpdb->prepare("INSERT INTO `".$wpdb->prefix."lifestream_feeds` (`feed`, `options`, `timestamp`, `owner`, `owner_id`, `version`) VALUES (%s, %s, %d, %s, %d, %d)", $this->get_constant('ID'), serialize($this->options), time(), $this->owner, $this->owner_id, $this->get_constant('VERSION')));
			$this->id = $wpdb->insert_id;
		}
		return $result;
	}
	
	function delete()
	{
		global $wpdb;

		$this->lifestream->safe_query($wpdb->prepare("DELETE FROM `".$wpdb->prefix."lifestream_feeds` WHERE `id` = %d", $this->id));
		$this->lifestream->safe_query($wpdb->prepare("DELETE FROM `".$wpdb->prefix."lifestream_event` WHERE `feed_id` = %d", $this->id));
		$this->lifestream->safe_query($wpdb->prepare("DELETE FROM `".$wpdb->prefix."lifestream_event_group` WHERE `feed_id` = %d", $this->id));
		$this->lifestream->safe_query($wpdb->prepare("DELETE FROM `".$wpdb->prefix."lifestream_error_log` WHERE `feed_id` = %d", $this->id));
		$this->id = null;
	}
	
	/**
	 * Called upon saving options to handle additional data management.
	 */
	function save_options() { }
	
	/**
	 * Validates the feed. A success has no return value.
	 */
	function test()
	{
		try
		{
			$this->save_options();
			$this->fetch();
		}
		catch (LifeStream_Error $ex)
		{
			return $ex->getMessage();
		}
	}
	
	function refresh($urls=null, $initial=false)
	{
		global $wpdb;
		
		date_default_timezone_set('UTC');

		if (!$this->id) return array(false, $this->lifestream->__('Feed has not yet been saved.'));

		$inserted = array();
		$total = 0;
		try
		{
			$items = $this->fetch($urls, $initial);
		}
		catch (LifeStream_Error $ex)
		{
			$this->lifestream->log_error($ex, $this->id);
			return array(false, $ex);
		}
		if (!$items) return array(false, $this->lifestream->__('Feed result was empty.'));
		foreach ($items as $item_key=>$item)
		{
			$link = array_key_pop($item, 'link');
			$date = array_key_pop($item, 'date');
			$key = array_key_pop($item, 'key');
			
			if ($this->version == 2)
			{
				if ($item['guid']) $link_key = md5(array_key_pop($item, 'guid'));
				$link_key = md5($item['link'] . $item['title']);
			}
			elseif ($this->version == 1)
			{
				$link_key = md5($item['link'] . $item['title']);
			}
			else
			{
				$link_key = $item['link'];
			}
			
			$affected = $wpdb->query($wpdb->prepare("INSERT IGNORE INTO `".$wpdb->prefix."lifestream_event` (`feed_id`, `feed`, `link`, `data`, `timestamp`, `version`, `key`, `owner`, `owner_id`) VALUES (%d, %s, %s, %s, %d, %d, %s, %s, %d)", $this->id, $this->get_constant('ID'), $link_key, serialize($item), $date, $this->get_constant('VERSION'), $key, $this->owner, $this->owner_id));
			if ($affected)
			{
				$item['id'] = $wpdb->insert_id;
				$items[$item_key] = $item;
				if (!array_key_exists($key, $inserted)) $inserted[$key] = array();
				$total += 1;
				$inserted[$key][date('m d Y', $date)] = $date;
			}
			else
			{
				unset($items[$item_key]);
			}
		}
		if (count($inserted))
		{
			// Rows were inserted so we need to handle the grouped events
			
			if ($this->options['grouped'] && $this->get_constant('CAN_GROUP'))
			{
				// Grouping them by key
				foreach ($inserted as $key=>$dates)
				{
					// Grouping them by date
					foreach ($dates as $date_key=>$date)
					{
						// Get all of the current events for this date
						// (including the one we affected just now)
						$results =& $wpdb->get_results($wpdb->prepare("SELECT `data`, `link` FROM `".$wpdb->prefix."lifestream_event` WHERE `feed_id` = %d AND `visible` = 1 AND DATE(FROM_UNIXTIME(`timestamp`)) = DATE(FROM_UNIXTIME(%d)) AND `key` = %s", $this->id, $date, $key));
						$events = array();
						foreach ($results as &$result)
						{
							$result->data = unserialize($result->data);
							if (!$result->data['link']) $result->data['link'] = $result->link;
							$events[] = $result->data;
						}

						// First let's see if the group already exists in the database
						$group =& $wpdb->get_results($wpdb->prepare("SELECT `id` FROM `".$wpdb->prefix."lifestream_event_group` WHERE `feed_id` = %d AND DATE(FROM_UNIXTIME(`timestamp`)) = DATE(FROM_UNIXTIME(%d)) AND `key` = %s LIMIT 0, 1", $this->id, $date, $key));
						if (count($group) == 1)
						{
							$group =& $group[0];
							$wpdb->query($wpdb->prepare("UPDATE `".$wpdb->prefix."lifestream_event_group` SET `data` = %s, `total` = %d, `updated` = 1, `timestamp` = %d WHERE `id` = %d", serialize($events), count($events), $date, $group->id));
						}
						else
						{
							$wpdb->query($wpdb->prepare("INSERT INTO `".$wpdb->prefix."lifestream_event_group` (`feed_id`, `feed`, `data`, `total`, `timestamp`, `version`, `key`, `owner`, `owner_id`) VALUES(%d, %s, %s, %d, %d, %d, %s, %s, %d)", $this->id, $this->get_constant('ID'), serialize($events), count($events), $date, $this->get_constant('VERSION'), $key, $this->owner, $this->owner_id));
						}
					}
				}
			}
			else
			{
				foreach ($items as $item)
				{
					$date = array_key_pop($item, 'date');
					$key = array_key_pop($item, 'key');
					$wpdb->query($wpdb->prepare("INSERT INTO `".$wpdb->prefix."lifestream_event_group` (`feed_id`, `feed`, `event_id`, `data`, `timestamp`, `total`, `version`, `key`, `owner`, `owner_id`) VALUES(%d, %s, %d, %s, %d, 1, %d, %s, %s, %d)", $this->id, $this->get_constant('ID'), $item['id'], serialize(array($item)), $date, $this->get_constant('VERSION'), $key, $this->owner, $this->owner_id));
				}
			}
		}
		$wpdb->query($wpdb->prepare("UPDATE `".$wpdb->prefix."lifestream_feeds` SET `timestamp` = UNIX_TIMESTAMP() WHERE `id` = %d", $this->id));
		return array(true, $total);
	}
	
	abstract function fetch();
	
	function render_item($row, $item)
	{
		$thumbnail = $this->get_thumbnail_url($row, $item);
		
		if (!empty($thumbnail) && $this->get_constant('MEDIA') == 'automatic')
		{
			$image = $this->get_image_url($row, $item);
			
			if ($this->lifestream->get_option('use_ibox') == '1' && !empty($image))
			{
				// change it to be large size images
				$ibox = ' rel="ibox&target=\''.htmlspecialchars($image).'\'"';
			}
			else $ibox = '';
			
			return sprintf('<a href="%s"'.$ibox.' class="photo" title="%s"><img src="%s" width="50" alt="%s"/></a>', htmlspecialchars($item['link']), htmlspecialchars($item['title']), htmlspecialchars($thumbnail), htmlspecialchars($item['title']));
		}
		return sprintf('<a href="%s">%s</a>', htmlspecialchars($item['link']), htmlspecialchars($item['title']));
	}
	
	function get_label($event, $options=array())
	{
		$cls = $this->get_constant('LABEL');
		return new $cls($this, $event, $options);
	}
	
	function render($event, $options)
	{
		$lifestream = $this->lifestream;
		$id = uniqid('ls_', true);
		$options['id'] = $id;

		$label_inst = $this->get_label($event, $options);
		
		if (count($event->data) > 1)
		{
			if ($this->lifestream->get_option('show_owners'))
			{
				$label = $label_inst->get_label_plural_user();
			}
			else
			{
				$label = $label_inst->get_label_plural();
			}
		}
		else
		{
			if ($this->lifestream->get_option('show_owners'))
			{
				$label = $label_inst->get_label_single_user();
			}
			else
			{
				$label = $label_inst->get_label_single();
			}
		}
		
		$feed_label = $label_inst->get_feed_label();
		
		$hour_format = $this->lifestream->get_option('hour_format');
		if (count($event->data) == 1 && $this->get_constant('MUST_GROUP')) $visible = true;
		else $visible = $options['show_details'];
		if ($visible === null) $visible = !$this->lifestream->get_option('hide_details_default');

		if ($options['hide_metadata']) $show_metadata = false;
		else $show_metadata = true;
		
		include('templates/'.$label_inst->get_template().'.inc.php');
	}
	
	function get_events($limit=50, $offset=0)
	{
		global $wpdb;

		if (!$this->id) return false;
		
		if (!($limit > 0) || !($offset >= 0)) return false;

		$results =& $wpdb->get_results($wpdb->prepare("SELECT t1.*, t2.`feed`, t2.`options` FROM `".$wpdb->prefix."lifestream_event` as t1 JOIN `".$wpdb->prefix."lifestream_feeds` as t2 ON t1.`feed_id` = t2.`id` WHERE t1.`feed_id` = %d ORDER BY t1.`timestamp` DESC LIMIT %d, %d", $this->id, $offset, $limit));
		$events = array();
		foreach ($results as &$result)
		{
			$events[] = new LifeStream_Event($this->lifestream, $result);
		}
		return $events;
	}
}
/**
 * Generic RSS/Atom feed extension.
 */
class LifeStream_Feed extends LifeStream_Extension
{
	function save_options()
	{
		$urls = $this->get_url();
		if (!is_array($urls)) $urls = array($urls);
		
		$url = $urls[0];
		
		if (is_array($url)) $url = $url[0];
		
		$feed = new SimplePie();
		$feed->enable_cache(false);
		$data = $this->lifestream->file_get_contents($url);
		$feed->set_raw_data($data);
		$feed->enable_order_by_date(false);
		$feed->force_feed(true); 
		$success = $feed->init();
		
		if ($this->options['auto_icon'] && ($url = $feed->get_favicon()))
		{
			if ($this->lifestream->validate_image($url))
			{
				$this->options['icon_url'] = $url;
			}
			else
			{
				$this->options['icon_url'] = '';
			}
		}
		elseif ($this->options['icon_url'])
		{
			if (!$this->lifestream->validate_image($this->options['icon_url']))
			{
				throw new LifeStream_Error($this->lifestream->__('The icon url is not a valid image.'));
			}
		}
		
		parent::save_options();
	}
	
	function fetch($urls=null, $initial=false)
	{
		// kind of an ugly hack for now so we can extend twitter
		if (!$urls) $urls = $this->get_url();
		if (!is_array($urls)) $urls = array($urls);
		$items = array();
		foreach ($urls as $url_data)
		{
			if (is_array($url_data))
			{
				// url, key
				list($url, $key) = $url_data;
			}
			else
			{
				$url = $url_data;
				$key = '';
			}
			$feed = new SimplePie();
			$feed->enable_cache(false);
			$data = $this->lifestream->file_get_contents($url);
			$feed->set_raw_data($data);
			$feed->enable_order_by_date(false);
			$feed->force_feed(true); 
			
			$success = $feed->init();
			if (!$success)
			{
				$sample = substr($data, 0, 150);
				throw new LifeStream_FeedFetchError("Error fetching feed from {$url} ({$feed->error()})....\n\n{$sample}");
			}
			// We need to set the default timestamp if no dates are set
			if (!$initial) $default_timestamp = time();
			else $default_timestmap = 0;
			
			$feed->handle_content_type();
			foreach ($feed->get_items() as $row)
			{
				$row =& $this->yield($row, $url, $key);
				if (!$row) continue;
				if (!$row['key']) $row['key'] = $key;
				if (!($row['date'] > 0)) $row['date'] = $default_timestamp;
				if (count($row)) $items[] = $row;
			}
			$feed->__destruct();
			unset($feed);
		}
		return $items;
	}

	function yield($row, $url, $key)
	{
		// date and link are required
		// the rest of the data will be serialized into a `data` field
		// and is pulled out and used on the render($row) method

		$title = $row->get_title();
		if (!$title) return false;
		$data = array(
			'date'	=> $row->get_date('U'),
			'link'	=> $this->lifestream->html_entity_decode($row->get_link()),
			'title'	=> $this->lifestream->html_entity_decode($title),
			'description'	=> $this->lifestream->html_entity_decode($row->get_description()),
			'key'	=> $key,
			'guid'	=> $row->get_id(),
		);
		
		if ($enclosure = $row->get_enclosure())
		{
			if ($thumbnail = $enclosure->get_thumbnail())
			{
				$data['thumbnail'] = $thumbnail;
			}
			if ($image = $enclosure->get_medium())
			{
				$data['image'] = $image;
			}
			elseif ($image = $enclosure->get_link())
			{
				$data['image'] = $image;
			}
			if (!$data['key']) $data['key'] = 'photo';
		}
		return $data;
	}

	function get_url()
	{
		return $this->options['url'];
	}
	
	function parse_urls($text)
	{
		# match http(s):// urls
		$text = preg_replace('@(https?://([-\w\.]+)+(:\d+)?(/([\w\=/\~_\.]*(\?\S+)?)?)?)@', '<a href="$1">$1</a>', $text);
		# match www urls
		$text = preg_replace('@((?<!http://)www\.([-\w\.]+)+(:\d+)?(/([\w/\=\~_\.]*(\?\S+)?)?)?)@', '<a href="http://$1">$1</a>', $text);
		# match email@address
		$text = preg_replace('/\b([A-Z0-9._%+-]+@(?:[A-Z0-9-]+\.)+[A-Z]{2,4})\b/i', '<a href="mailto:$1">$1</a>', $text);
		return $text;
	}
}

/**
 * You need to pass a thumbnail item in yield() for PhotoFeed item's
 */
class LifeStream_PhotoFeed extends LifeStream_Feed
{
	const LABEL = 'LifeStream_PhotoLabel';
	const MUST_GROUP = true;
}

class LifeStream_GenericFeed extends LifeStream_Feed {
	const DESCRIPTION = 'The generic feed can handle both feeds with images (in enclosures), as well as your standard text based RSS and Atom feeds.';
	
	function get_options()
	{		
		return array(
			'url' => array($this->lifestream->__('Feed URL:'), true, '', ''),
		);
	}

	function get_public_url()
	{
		return $this->options['url'];
	}
	
	function get_label($event, $options)
	{
		if ($event->key == 'photo')	$cls = LifeStream_PhotoFeed::LABEL;
		else $cls = $this->get_constant('LABEL');
		return new $cls($this, $event, $options);
	}
}
$lifestream->register_feed('LifeStream_GenericFeed');

/**
 * Outputs the recent lifestream events.
 * @param {Array} $args An array of keyword args.
 */
function lifestream($args=array())
{
	global $lifestream;

	setlocale(LC_ALL, WPLANG);

	$_ = func_get_args();

	$defaults = array(
	);

	if (!is_array($_[0]))
	{
		// old style
		$_ = array(
			'limit'			=> $_[0],
			'feed_ids'		=> $_[1],
			'date_interval'	=> $_[2],
			'user_ids'		=> $_[4],
		);
		foreach ($_ as $key=>$value)
		{
			if ($value == null) unset($_[$key]);
		}
	}
	else
	{
		$_ = $args;
	}
	
	$_ = array_merge($defaults, $_);
	
	// TODO: offset
	//$offset = $lifestream->get_option('lifestream_timezone');
	$hour_format = $lifestream->get_option('hour_format');
	$day_format = $lifestream->get_option('day_format');
	
	$events = call_user_func(array(&$lifestream, 'get_events'), $_);
	
	include(dirname(__FILE__) . '/pages/lifestream-table.inc.php');

	echo '<!-- Powered by iBegin LifeStream '.LIFESTREAM_VERSION.' -->';

	if ($lifestream->get_option('show_credits') == '1')
	{
		echo '<p class="lifestream_credits"><small>'.$lifestream->credits().'</small></p>';
	}
}

function lifestream_sidebar_widget($_=array())
{
	global $lifestream;
	
	setlocale(LC_ALL, WPLANG);
	
	$defaults = array(
		'limit'			=> 10,
		'break_groups'	=> true,
		'show_details'	=> false,
	);
	
	$_ = array_merge($defaults, $_);
	
	// TODO: offset
	//$offset = $lifestream->get_option('lifestream_timezone');
	$hour_format = $lifestream->get_option('hour_format');
	$day_format = $lifestream->get_option('day_format');
	
	$events = call_user_func(array(&$lifestream, 'get_events'), $_);
	
	include(dirname(__FILE__) . '/pages/lifestream-list.inc.php');
}

function lifestream_register_feed($class_name)
{
	global $lifestream;
	
	$lifestream->register_feed($class_name);
}

include(dirname(__FILE__) . '/feeds.inc.php');
@include(dirname(__FILE__). '/local_feeds.inc.php');

// Require more of the codebase
require_once(dirname(__FILE__) . '/inc/widget.php');
require_once(dirname(__FILE__) . '/inc/syndicate.php');
?>
