<?php

if (!class_exists('SimplePie'))
{
	require_once(ABSPATH . WPINC . '/class-simplepie.php');
}

global $wpdb, $userdata, $lifestream;

function lifestream_path_join()
{
	$bits = func_get_args();
	$sep = (in_array(PHP_OS, array("WIN32", "WINNT")) ? '\\' : '/');
	foreach ($bits as $key=>$value) {
		$bits[$key] = rtrim($value, $sep);
	}
	return implode($sep, $bits);
}

function lifestream_array_key_pop($array, $key, $default=null)
{
	$value = @$array[$key];
	unset($array[$key]);
	if (!$value) $value = $default;
	return $value;
}
// Returns the utf string corresponding to the unicode value (from php.net, courtesy - romans@void.lv)
function lifestream_code2utf($num)
{
	if ($num < 128) return chr($num);
	if ($num < 2048) return chr(($num >> 6) + 192) . chr(($num & 63) + 128);
	if ($num < 65536) return chr(($num >> 12) + 224) . chr((($num >> 6) & 63) + 128) . chr(($num & 63) + 128);
	if ($num < 2097152) return chr(($num >> 18) + 240) . chr((($num >> 12) & 63) + 128) . chr((($num >> 6) & 63) + 128) . chr(($num & 63) + 128);
	return '';
}
function lifestream_str_startswith($string, $chunk)
{
	return substr($string, 0, strlen($chunk)) == $chunk;
}
function lifestream_str_endswith($string, $chunk)
{
	return substr($string, strlen($chunk)*-1) == $chunk;
}
function lifestream_get_class_constant($class, $const)
{
	return constant(sprintf('%s::%s', $class, $const));
}

class Lifestream_Error extends Exception { }
class Lifestream_ValidationError extends Exception { }
class Lifestream_FeedFetchError extends Lifestream_Error { }

class Lifestream_Event
{
	/**
	 * Represents a single event in the database.
	 */
	function __construct(&$lifestream, $row)
	{
		$this->lifestream = $lifestream;
		$this->date = $row->timestamp;
		$this->data = array(unserialize($row->data));
		$this->id = $row->id;
		$this->timestamp = $row->timestamp;
		$this->total = 1;
		$this->is_grouped = false;
		$this->key = $row->key;
		$this->owner = $row->owner;
		$this->owner_id = $row->owner_id;
		$this->post_id = $row->post_id;
		$this->visible = $row->visible;
		$this->link = @(!empty($this->data['link']) ? $this->data['link'] : $row->link);
		$cls = $this->lifestream->get_feed($row->feed);
		$this->feed = new $cls($this->lifestream, unserialize($row->options), $row->feed_id);
	}
	
	function __toString()
	{
		return $this->data[0]['title'];
	}
	
	function get_event_display()
	{
		return $this->feed->get_event_display($this, $this->data[0]);
	}

	function get_event_link()
	{
		return $this->feed->get_event_link($this, $this->data[0]);
	}
	
	function get_timesince()
	{
		return $this->lifestream->timesince($this->timestamp);
	}
	
	function get_date()
	{
		return $this->date + LIFESTREAM_DATE_OFFSET;
	}
	
	/**
	 * Returns an HTML-ready string.
	 */
	function render($options=array())
	{
		return $this->feed->render($this, $options);
	}
	
	function get_label_instance($options=array())
	{
		if (!isset($this->_label_instance))
		{
			$this->_label_instance = $this->feed->get_label($this, $options);
		}
		return $this->_label_instance;
	}
	
	function get_label($options=array())
	{
		$label_inst = $this->get_label_instance($options);
		if (count($this->data) > 1)
		{
			if (@$options['show_owners'] || $this->lifestream->get_option('show_owners'))
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
			if (@$options['show_owners'] || $this->lifestream->get_option('show_owners'))
			{
				$label = $label_inst->get_label_single_user();
			}
			else
			{
				$label = $label_inst->get_label_single();
			}
		}
		return $label;
	}
	
	function get_feed_label($options=array())
	{
		$label_inst = $this->get_label_instance($options);
		return $label_inst->get_feed_label();
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

class Lifestream_EventGroup extends Lifestream_Event
{
	/**
	 * Represents a grouped event in the database.
	 */
	 
	function __construct(&$lifestream, $row)
	{
		parent::__construct($lifestream, $row);
		$this->total = $row->total ? $row->total : 1;
		$this->data = unserialize($row->data);
		$this->is_grouped = true;
	}
	
	function get_event_display($bit)
	{
		return $this->feed->get_event_display($this, $bit);
	}
	
	function get_event_link($bit)
	{
		return $this->feed->get_event_link($this, $bit);
	}
	
}

class Lifestream
{
	// stores all registered feeds
	public $feeds = array();

	// stores file locations to feed classes
	public $paths = array();
	
	// stores theme information
	public $themes = array();

	// stores icon folder names
	public $icons = array();
	
	// current theme
	public $theme = 'default';
	
	protected $paging_key = 'ls_p';

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
		static $trans_tbl;

		// replace numeric entities
		$string = preg_replace('~&#x([0-9a-f]+);~ei', 'lifestream_code2utf(hexdec("\\1"))', $string);
		$string = preg_replace('~&#([0-9]+);~e', 'lifestream_code2utf(\\1)', $string);

		// replace literal entities
		if (!isset($trans_tbl))
		{
			$trans_tbl = array();

			foreach (get_html_translation_table(HTML_ENTITIES) as $val=>$key)
				$trans_tbl[$key] = utf8_encode($val);
		}

		return strtr($string, $trans_tbl);
	}

	// function html_entity_decode($string)
	// {
	// 	$string = html_entity_decode($string, ENT_QUOTES, 'utf-8');
	// 	
	// 	$string = preg_replace('~&#x0*([0-9a-f]+);~ei', 'chr(hexdec("\\1"))', $string);
	// 	$string = preg_replace('~&#0*([0-9]+);~e', 'chr(\\1)', $string);
	// 
	//  		return $string;
	// }

	function parse_nfo_file($file)
	{
		$data = array();
		if (!is_file($file)) return $data;
		$fp = file($file);
		foreach ($fp as $line)
		{
			if (lifestream_str_startswith('#', $line)) continue;
			list($key, $value) = explode(':', $line, 2);
			$data[strtolower($key)] = trim($value);
		}
		return $data;
	}

	function get_icon_paths()
	{
		$directories = array(
			lifestream_path_join(LIFESTREAM_PATH, 'icons')
		);
		if ($this->get_option('icon_dir') && $this->get_option('icon_dir') != $directories[0]) {
			$directories[] = $this->get_option('icon_dir');
		}
		return $directories;
	}
	
	function get_rss_feed_url()
	{
		$permalink = get_option('permalink_structure');
		if (!empty($permalink))
		{
			$url = trailingslashit(get_bloginfo('rss2_url')) . 'lifestream-feed';
		}
		else {
			$url = trailingslashit(get_bloginfo('url')) . 'wp-rss2.php?feed=lifestream-feed';
		}
		return $url;
	}

	/**
	 * Find each icons/name/generic.png file.
	 */
	function detect_icons()
	{
		$directories = $this->get_icon_paths();
		foreach ($directories as $base_dir)
		{
			if (!is_dir($base_dir)) continue;
			$handler = opendir($base_dir);
			while ($file = readdir($handler))
			{
				// ignore hidden files
				if (lifestream_str_startswith($file, '.')) continue;
				// if its not a directory we dont care
				$path = lifestream_path_join($base_dir, $file);
				if (!is_dir($path)) continue;
				$ext_file = lifestream_path_join($path, 'generic.png');
				if (is_file($ext_file))
				{
					$data = $this->parse_nfo_file(lifestream_path_join($path, 'icons.txt'));
					if (!$data['name']) $data['name'] = $file;
					$data['__path'] = $path;
					$data['__url'] = 
					$this->icons[$file] = $data;
				}
			}
		}
	}
	
	function get_extension_paths()
	{
		$directories = array(
			lifestream_path_join(LIFESTREAM_PATH, 'extensions')
		);
		if ($this->get_option('extension_dir') && $this->get_option('extension_dir') != $directories[0]) {
			$directories[] = $this->get_option('extension_dir');
		}
		return $directories;
	}
	
	/**
	 * Find each extension/name/extension.inc.php file.
	 */
	function detect_extensions()
	{
		$lifestream =& $this;

		$directories = $this->get_extension_paths();
		foreach ($directories as $base_dir)
		{
			if (!is_dir($base_dir)) continue;
			$handler = opendir($base_dir);
			while ($file = readdir($handler))
			{
				// ignore hidden files
				if (lifestream_str_startswith($file, '.')) continue;
				$path = lifestream_path_join($base_dir, $file);
				// if its not a directory we dont care
				if (!is_dir($path)) continue;
				// check for extension.inc.php
				$ext_file = lifestream_path_join($path, 'extension.inc.php');
				if (is_file($ext_file))
				{
					include($ext_file);
				}
			}
		}
	}
	
	function get_theme_paths()
	{
		$directories = array(
			lifestream_path_join(LIFESTREAM_PATH, 'themes')
		);
		if ($this->get_option('theme_dir') && $this->get_option('theme_dir') != $directories[0]) {
			$directories[] = $this->get_option('theme_dir');
		}
		return $directories;
	}
	
	/**
	 * Find each themes/name/theme.txt file.
	 */
	function detect_themes()
	{
		$directories = $this->get_theme_paths();
		foreach ($directories as $base_dir)
		{
			if (!is_dir($base_dir)) continue;
			$handler = opendir($base_dir);
			while ($file = readdir($handler))
			{
				// ignore hidden files
				if (lifestream_str_startswith($file, '.')) continue;
				// if its not a directory we dont care
				$path = lifestream_path_join($base_dir, $file);
				if (!is_dir($path)) continue;
				// check for main.inc.php
				$ext_file = lifestream_path_join($path, 'theme.txt');
				if (is_file($ext_file))
				{
					$theme = array();
					$theme = $this->parse_nfo_file($ext_file);
					$theme['__path'] = $path;
					if (!array_key_exists('name', $theme)) continue;
					$this->themes[$file] = $theme;
				}
			}
		}
	}
	
	function get_media_url_for_icon($filename='generic.png', $iconpack='default')
	{
		$path = lifestream_path_join($this->icons[$iconpack]['__path'], $filename);
		if (!is_file($path))
		{
			$filename = 'generic.png';
			$path = lifestream_path_join(LIFESTREAM_PATH, 'icons', 'default', $filename);
		}
		return $this->get_absolute_media_url($path);
	}

	function get_icon_media_url($filename)
	{
		return $this->get_media_url_for_icon($filename, $this->get_option('icons', 'default'));
	}
	
	function get_theme_media_url($filename)
	{
		return $this->get_media_url_for_theme($filename, $this->get_option('theme', 'default'));
	}

	function get_media_url_for_theme($filename, $theme='default')
	{
		// base dir is now $theme['__path'] so we must abstract the web dir
		$path = lifestream_path_join($this->themes[$theme]['__path'], 'media', $filename);
		if (!is_file($path))
		{
			$path = lifestream_path_join(LIFESTREAM_PATH, 'themes', 'default', 'media', $filename);
		}
		return $this->get_absolute_media_url($path);
	}
	
	function get_absolute_media_url($path)
	{
		$path = str_replace(trailingslashit(WP_CONTENT_DIR), '', $path);
		$path = str_replace(trailingslashit(realpath(LIFESTREAM_PATH)), 'plugins/'.LIFESTREAM_PLUGIN_DIR.'/', $path);
		return str_replace('\\', '/', trailingslashit(WP_CONTENT_URL).$path);
	}

	function get_theme_filepath($filename)
	{
		$path = $this->get_filepath_for_theme($filename, $this->get_option('theme', 'default'));
		if (!is_file($path))
		{
			$path = $this->get_filepath_for_theme($filename, 'default');
		}
		return $path;
	}
	
	function get_filepath_for_theme($filename, $theme='default')
	{
		if (!array_key_exists($theme, $this->themes))
		{
			throw new Exception('Theme is not valid.');
		}
		return lifestream_path_join($this->themes[$theme]['__path'], $filename);
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
		'theme'				=> 'default',
		'icons'				=> 'default',
		'extension_dir'		=> '',
		'theme_dir'			=> '',
		'icon_dir'			=> '',
		'links_new_windows'	=> '0',
		'truncate_interval'	=> '0',
		'page_id'			=> '',
	);
	
	function __construct()
	{
		$this->path = WP_CONTENT_URL . '/plugins/lifestream';
		
		$this->_optioncache = null;

		add_action('admin_menu', array(&$this, 'options_menu'));
		add_action('wp_head', array(&$this, 'header'));
		add_filter('the_content', array(&$this, 'embed_callback'));
		add_action('init', array(&$this, 'init'));

		add_filter('cron_schedules', array(&$this, 'get_cron_schedules'));
		add_action('lifestream_digest_cron', array(&$this, 'digest_update'));
		add_action('lifestream_cron', array(&$this, 'update'));
		add_action('lifestream_cleanup', array(&$this, 'cleanup_history'));
		add_action('template_redirect', array($this, 'template_redirect'));
		
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
			$this->_optioncache = (array)get_option('lifestream_options');
			if (!$this->_optioncache) $this->_optioncache = (array)$this->_options;
		}
	}
	
	/**
	 * Fetches the value of an option. Returns `null` if the option is not set.
	 */
	function get_option($option, $default=null)
	{
		$this->_populate_option_cache();
		if (!isset($this->_optioncache[$option])) $value = $default;
		else
		{
			$value = $this->_optioncache[$option];
		}
		if (empty($value)) $value = $default;
		return $value;
	}
	
	/**
	 * Removes an option.
	 */
	function delete_option($option)
	{
		$this->_populate_option_cache();
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
		if (!array_key_exists($option, $this->_optioncache) || $this->_optioncache[$option] === '')
		{
			$this->_optioncache[$option] = $value;
			update_option('lifestream_options', $this->_optioncache);
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

		$offset = get_option('gmt_offset') * 3600;
		define('LIFESTREAM_DATE_OFFSET', $offset);

		load_plugin_textdomain('lifestream', false, 'lifestream/locales');
		$page = (isset($_GET['page']) ? $_GET['page'] : null);
		
		if (is_admin() && lifestream_str_startswith($page, 'lifestream'))
		{
			wp_enqueue_script('jquery');
			wp_enqueue_script('admin-forms');
		}
		add_feed('lifestream-feed', 'lifestream_rss_feed');
		$this->is_buddypress = (function_exists('bp_is_blog_page') ? true : false);

		register_post_type('lsevent', array(
			'label' => $this->__('Lifestream Events'),
			'singular_label' => $this->__('Lifestream Event'),
			'show_ui' => false,
			'public' => true,
			'exclude_from_search' => true,
			'hierarchical' => false,
			'capability_type' => 'post',
			'rewrite' => array('slug', 'lifestream'),
			'query_var' => false,
			'can_export' => false,
			'show_in_nav_menus' => false,
			'supports' => array('title', 'comments')
		));

		// If this is an update we need to force reactivation
		if (LIFESTREAM_VERSION != $this->get_option('_version'))
		{
			$this->get_option('_version');
			$this->deactivate();
			$this->activate();
		}
	}
	
	function is_lifestream_event()
	{
		global $wpdb, $posts, $post, $wp_query;
		
		if (!$posts)
		{
			if ($wp_query->query_vars['p']) {
				$posts = array(get_post($wp_query->query_vars['p'], OBJECT));
			}
			elseif ($wp_query->query_vars['name']) {
				$posts = $wpdb->get_results($wpdb->prepare("SELECT `ID` FROM `".$wpdb->prefix."posts` WHERE `post_name` = %s AND `post_type` = 'lsevent' LIMIT 1", $wp_query->query_vars['name']));
				if (!$posts) return false;
				$posts = array(get_post($posts[0]->ID, OBJECT));
			}
			$wp_query->post = $posts[0];
			$post = $wp_query->post;
			$wp_query->is_404 = false;
			$wp_query->queried_object = $posts[0];
			$wp_query->queried_object_id = $posts[0]->ID;
			$wp_query->is_single = true;
		}
		return (is_single() && get_post_type() == 'lsevent');
	}

	function is_lifestream_home()
	{
		global $wp_query, $post;
		
		return (is_page() && $post->ID == $this->get_option('page_id'));
	}

	function template_redirect()
	{
		global $ls_template;
		
		$lifestream = $this;
		
		if ($this->is_lifestream_event())
		{
			$ls_template->get_events();
			include($this->get_template('event.php'));
			exit;
		}
		else if ($this->is_lifestream_home())
		{
			$ls_template->get_events();
			
			include($this->get_template('home.php'));
			exit;
		}
	}
	
	function get_template($template)
	{
		if (file_exists(TEMPLATEPATH.'/lifestream/'.$template))
		{
			return TEMPLATEPATH.'/lifestream/'.$template;
		}
		return LIFESTREAM_PATH . '/templates/'.$template;
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
	
	function get_anchor_html($label, $href, $attrs=array())
	{
		// TODO: this might need to be optimized as string management is typically slow
		if ($this->get_option('links_new_windows') && empty($attrs['target']))
		{
			$attrs['target'] = '_blank';
		}
		$attrs['href'] = $href;

		$html = '<a';
		foreach ($attrs as $key=>$value)
		{
			$html .= ' '.$key.'="'.$value.'"';
		}
		$html .= '>'.$label.'</a>';
		return $html;
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
	
	function get_cron_schedules($cron)
	{
		$interval = (int)$this->get_option('update_interval', 15);
		if (!($interval > 0)) $interval = 15;
		$cron['lifestream'] = array(
			'interval' => $interval * 60,
			'display' => $this->__('On Lifestream update')
		);
		$cron['lifestream_digest'] = array(
			'interval' => (int)$this->get_digest_interval(),
			'display' => $this->__('On Lifestream daily digest update')
		);
		return $cron;
	}
	
	function get_single_event($feed_type)
	{
		$events = $this->get_events(array('feed_types'=>array($feed_type), 'limit'=>1, 'break_groups'=>true));
		$event = $events[0];

		return $event;
	}
	
	function generate_unique_id()
	{
		return uniqid('ls_', true);
	}
	
	function digest_update()
	{
		global $wpdb;

		if ($this->get_option('daily_digest') != '1') return;

		$interval = $this->get_digest_interval();

		$options = array(
			'id' => $this->generate_unique_id(),
		);

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
			$events[] = new Lifestream_EventGroup($this, $result);
		}

		if (count($events))
		{
			ob_start();
			if (!include($this->get_theme_filepath('digest.inc.php'))) return;
			$content = sprintf($this->get_option('digest_body'), ob_get_clean(), date($this->get_option('day_format'), $now), count($events));

			$data = array(
				'post_content' => $wpdb->escape($content),
				'post_title' => $wpdb->escape(sprintf($this->get_option('digest_title'), date($this->get_option('day_format'), $now), date($this->get_option('hour_format'), $now))),
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

		wp_enqueue_script('postbox');

		if (function_exists('add_menu_page'))
		{
			$basename = basename(LIFESTREAM_PLUGIN_FILE);

			$results =& $wpdb->get_results("SELECT COUNT(*) as `count` FROM `".$wpdb->prefix."lifestream_error_log` WHERE has_viewed = 0");
			$errors = $results[0]->count;
			add_menu_page('Lifestream', 'Lifestream', 'edit_posts', $basename, array(&$this, 'options_page'));
			add_submenu_page($basename, $this->__('Lifestream Feeds'), $this->__('Feeds'), 'level_1', $basename, array(&$this, 'options_page'));
			add_submenu_page($basename, $this->__('Lifestream Events'), $this->__('Events'), 'edit_posts', 'lifestream-events.php', array(&$this, 'options_page'));
			add_submenu_page($basename, $this->__('Lifestream Settings'), $this->__('Settings'), 'manage_options', 'lifestream-settings.php', array(&$this, 'options_page'));
			add_submenu_page($basename, $this->__('Lifestream Change Log'), $this->__('Change Log'), 'edit_posts', 'lifestream-changelog.php', array(&$this, 'options_page'));
			add_submenu_page($basename, $this->__('Lifestream Errors'), $this->__('Errors (%d)', $errors), 'edit_posts', 'lifestream-errors.php', array(&$this, 'options_page'));
			add_submenu_page($basename, $this->__('Lifestream Maintenance'), $this->__('Maintenance / Debug', $errors), 'manage_options', 'lifestream-maintenance.php', array(&$this, 'options_page'));
			add_submenu_page($basename, $this->__('Lifestream Support Forums'), $this->__('Support Forums'), 'edit_posts', 'lifestream-forums.php', array(&$this, 'options_page'));
		}
	}
	
	function header()
	{
		echo '<script type="text/javascript" src="'.$this->path.'/lifestream.js"></script>';
		echo '<link rel="stylesheet" type="text/css" media="screen" href="'.$this->get_theme_media_url('lifestream.css').'"/>';
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
			case 'lifestream-maintenance.php':
				if (@$_POST['resetcron'])
				{
					$this->reschedule_cron();
					$message = $this->__('Cron timers have been reset.');
				}
				elseif (@$_POST['restore'])
				{
					$this->restore_options();
					$message = $this->__('Default options have been restored.');
				}
				elseif (@$_POST['restoredb'])
				{
					$this->restore_database();
					$message = $this->__('Default database has been restored.');
				}
				elseif (@$_POST['fixposts'])
				{
					$new_posts = $this->upgrade_posts_to_events();
					$message = $this->__('There were %d new posts which had to be created.', $new_posts);
				}
				elseif (@$_POST['cleanupposts'])
				{
					$affected = $this->safe_query($wpdb->prepare("DELETE FROM `".$wpdb->prefix."posts` WHERE `post_type` = 'lsevent' AND `ID` NOT IN (SELECT `post_id` FROM `".$wpdb->prefix."lifestream_event_group` WHERE `post_id` != 0)"));
					$message = $this->__('There were %d unused posts which have been removed.', $affected);
				}
				elseif (@$_POST['recreatepage'])
				{
					$this->create_page_template();
					$message = $this->__('A new page was created for Lifestream, with the ID of %s.', $this->get_option('page_id'));
				}
			break;
			case 'lifestream-events.php':
				switch ((isset($_REQUEST['op']) ? strtolower($_REQUEST['op']) : null))
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
										$this->safe_query($wpdb->prepare("DELETE FROM `".$wpdb->prefix."posts` WHERE `post_type` = 'lsevent' AND `ID` = %d", $group->post_id));
										$wpdb->query($wpdb->prepare("DELETE FROM `".$wpdb->prefix."lifestream_event_group` WHERE `id` = %d", $group->id));
									}
								}
								else
								{
									$this->safe_query($wpdb->prepare("DELETE FROM `".$wpdb->prefix."posts` WHERE `post_type` = 'lsevent' AND `ID` = %d", $result->post_id));
									$wpdb->query($wpdb->prepare("DELETE FROM `".$wpdb->prefix."lifestream_event_group` WHERE `event_id` = %d", $result->id));
								}
							}
							$message = $this->__('The selected events were hidden.');
						}
					break;
				}
			break;
			case 'lifestream-settings.php':
				if (!empty($_POST['save']))
				{
					foreach (array_keys($this->_options) as $value)
					{
						$this->update_option($value, (isset($_POST['lifestream_'.$value]) ? stripslashes($_POST['lifestream_'.$value]) : '0'));
					}
					// We need to make sure the cron runs now
					$this->reschedule_cron();
				}
			break;
			default:
				$feedmsgs = array();
				switch ((isset($_REQUEST['op']) ? strtolower($_REQUEST['op']) : null))
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
								$instance = Lifestream_Feed::construct_from_query_result($this, $result[0]);
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
					case 'pause':
						if (!$_REQUEST['id']) break;
						$ids = array();
						foreach ($_REQUEST['id'] as $id)
						{
							$ids[] = (int)$id;
						}
						if (!empty($ids))
						{
							if (current_user_can('manage_options'))
							{
								$wpdb->query($wpdb->prepare("UPDATE `".$wpdb->prefix."lifestream_feeds` SET `active` = 0 WHERE `id` IN ('%s')", implode("','", $ids)));
							}
							else 
							{
								$wpdb->query($wpdb->prepare("UPDATE `".$wpdb->prefix."lifestream_feeds` SET `active` = 1 WHERE `id` IN ('%s') AND `owner_id` = %s", implode("','", $ids), $userdata->ID));
								
							}
							$message = $this->__('The selected feeds have been paused, and events will not be refreshed.');
						}
					break;
					case 'unpause':
						if (!$_REQUEST['id']) break;
						$ids = array();
						foreach ($_REQUEST['id'] as $id)
						{
							$ids[] = (int)$id;
						}
						if (!empty($ids))
						{
							if (current_user_can('manage_options'))
							{
								$wpdb->query($wpdb->prepare("UPDATE `".$wpdb->prefix."lifestream_feeds` SET `active` = 1 WHERE `id` IN ('%s')", implode("','", $ids)));
							}
							else 
							{
								$wpdb->query($wpdb->prepare("UPDATE `".$wpdb->prefix."lifestream_feeds` SET `active` = 0 WHERE `id` IN ('%s') AND `owner_id` = %s", implode("','", $ids), $userdata->ID));
								
							}
							$message = $this->__('The selected feeds have been unpaused, and events will now be refreshed.');
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
								$instance = Lifestream_Feed::construct_from_query_result($this, $result[0]);
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
							$instance = Lifestream_Feed::construct_from_query_result($this, $result[0]);

							$options = $instance->get_options();

							if (@$_POST['save'])
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
									$values['grouped'] = @$_POST['grouped'];
								}
								if ($instance->get_constant('HAS_EXCERPTS'))
								{
									$values['excerpt'] = $_POST['excerpt'];
								}
								$values['feed_label'] = $_POST['feed_label'];
								$values['icon_url'] = $_POST['icon_type'] == 3 ? $_POST['icon_url'] : '';
								$values['auto_icon'] = $_POST['icon_type'] == 2;
								if ($_POST['owner'] != $instance->owner_id && current_user_can('manage_options') && $_POST['owner'])
								{
									$usero = new WP_User($_POST['owner']);
									$owner = $usero->data;
									$instance->owner_id = $_POST['owner'];
									$instance->owner = $owner->display_name;
								}
								if (!count($errors))
								{
									$instance->options = $values;
									$instance->save();
									unset($_POST);
								}
							}
							elseif (@$_POST['truncate'])
							{
								$instance->truncate();
								$instance->refresh();
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
								$values['grouped'] = @$_POST['grouped'];
							}
							if ($feed->get_constant('HAS_EXCERPTS'))
							{
								$values['excerpt'] = $_POST['excerpt'];
							}
							$values['feed_label'] = $_POST['feed_label'];
							$values['icon_url'] = $_POST['icon_type'] == 3 ? $_POST['icon_url'] : '';
							$values['auto_icon'] = $_POST['icon_type'] == 2;
							if (current_user_can('manage_options') && $_POST['owner'])
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
											$message = $this->__('A new %s feed was added to your Lifestream.', $feed->get_constant('NAME'));
											$feedmsgs[$feed->id] = $msg_arr[1];
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
		.feedlist li a { float: left; display: block; padding: 2px 2px 2px 20px; min-height: 16px; background-repeat: no-repeat; background-position: left center; margin: 1px; width: 150px; text-decoration: none; }
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
				<li><?php echo nl2br(Lifestream_Feed::parse_urls(htmlspecialchars($error))); ?></li>
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
					$page = (!empty($_GET['paged']) ? $_GET['paged'] : 1);
					switch ((isset($_REQUEST['op']) ? strtolower($_REQUEST['op']) : null))
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

					include(LIFESTREAM_PATH . '/pages/errors.inc.php');
				break;
				case 'lifestream-maintenance.php':
					include(LIFESTREAM_PATH . '/pages/maintenance.inc.php');
				break;
				case 'lifestream-changelog.php':
					include(LIFESTREAM_PATH . '/pages/changelog.inc.php');
				break;
				case 'lifestream-forums.php':
					include(LIFESTREAM_PATH . '/pages/forums.inc.php');
				break;
				case 'lifestream-settings.php':
					$lifestream_digest_intervals = array(
						'weekly'	=> $this->__('Weekly'),
						'daily'		=> $this->__('Daily'),
						'hourly'	=> $this->__('Hourly'),
					);
					include(LIFESTREAM_PATH . '/pages/settings.inc.php');
				break;
				case 'lifestream-events.php':
					$page = (!empty($_GET['paged']) ? $_GET['paged'] : 1);
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
						$results[] = new Lifestream_Event($lifestream, $result);
					}
					unset($rows);
					
					include(LIFESTREAM_PATH . '/pages/events.inc.php');
				break;
				default:
					switch ((isset($_REQUEST['op']) ? strtolower($_REQUEST['op']) : null))
					{
						case 'edit':
							include(LIFESTREAM_PATH . '/pages/edit-feed.inc.php');
						break;
						case 'add':
							$identifier = $_GET['feed'];
							$class_name = $this->get_feed($identifier);
							if (!$class_name) break;
							$feed = new $class_name($this);
							$options = $feed->get_options();
							include(LIFESTREAM_PATH . '/pages/add-feed.inc.php');
						break;
						default:
							$page = (!empty($_GET['paged']) ? $_GET['paged'] : 1);
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
								$results[] = Lifestream_Feed::construct_from_query_result($this, $result);
							}
							if ($results !== false)
							{
								include(LIFESTREAM_PATH . '/pages/feeds.inc.php');
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
	 * Cleans up old entries based on the `truncate_interval` setting.
	 */
	function cleanup_history()
	{
		$int = $this->get_option('truncate_interval');
		if (!(int)$int) return;
		// the value is in days
		$ts = time()-(int)$int*3600*24;
		$result = $wpdb->query($wpdb->prepare("DELETE FROM `".$wpdb->prefix."lifestream_event` WHERE `timestamp` < %s", $wpdb->escape($ts)));
		$this->safe_query($wpdb->prepare("DELETE FROM `".$wpdb->prefix."posts` WHERE `post_type` = 'lsevent' AND `ID` IN (SELECT `post_id` FROM `".$wpdb->prefix."lifestream_event_group` WHERE `timestamp` < %s)", $wpdb->escape($ts)));
		$result = $wpdb->query($wpdb->prepare("DELETE FROM `".$wpdb->prefix."lifestream_event_group` WHERE `timestamp` < %s", $wpdb->escape($ts)));
		$result = $wpdb->query($wpdb->prepare("DELETE FROM `".$wpdb->prefix."lifestream_error_log` WHERE `timestamp` < %s", $wpdb->escape($ts)));
	}
	
	/**
	 * Attempts to update all feeds
	 */
	function update($user_id=null)
	{
		$event_arr = $this->update_all($user_id);
		$events = 0;
		foreach ($event_arr as $instance=>$result)
		{
			if (is_int($result)) $events += $result;
		}
		return $events;
	}
	
	function update_all($user_id=null)
	{
		// $user_id is not implemented yet
		global $wpdb;
		$this->update_option('_last_update', time());
		$events = array();
		$results =& $wpdb->get_results("SELECT * FROM `".$wpdb->prefix."lifestream_feeds` WHERE `active` = 1");
		foreach ($results as $result)
		{
			$instance = Lifestream_Feed::construct_from_query_result($this, $result);
			try
			{
				$feed_msg = $instance->refresh();
				$events[$instance->id] = $feed_msg[1];
			}
			catch (Lifestream_FeedFetchError $ex)
			{
				$this->log_error($ex, $instance->id);
				$events[$instance->id] = $ex;
			}
		}
		return $events;	
	}
	/**
	 * Registers a feed class with Lifestream.
	 * @param $class_name {Class} Should extend Lifestream_Extension.
	 */
	function register_feed($class_name)
	{
		$this->feeds[lifestream_get_class_constant($class_name, 'ID')] = $class_name;
		// this may be the ugliest thing ever written in PHP, thank you developers!
		$rcl = new ReflectionClass($class_name);
		$this->paths[$class_name] = dirname($rcl->getFileName());
		unset($rcl);
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
			throw new Lifestream_FeedFetchError('Failed to open url: '.$url .' ('.$file->error.')');
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
			preg_match_all("|(?:([a-z_]+)=[\"']?([a-z0-9_-\s,]+)[\"']?)\s*|i", $matches[1], $options);
			for ($i=0; $i<count($options[1]); $i++)
			{
				if ($options[$i]) $args[$options[1][$i]] = $options[2][$i];
			}
		}
		ob_start();
		if (!empty($args['feed_ids'])) $args['feed_ids'] = explode(',', $args['feed_ids']);
		if (!empty($args['user_ids'])) $args['user_ids'] = explode(',', $args['user_ids']);
		if (!empty($args['feed_types'])) $args['feed_types'] = explode(',', $args['feed_types']);
		lifestream($args);
		return ob_get_clean();
	}
	
	/**
	 * Returns the duration from now until timestamp.
	 * @param $timestamp {Integer}
	 * @param $granularity {Integer}
	 * @param $format {String} Date format.
	 * @return {String}
	 */
	function timesince($timestamp, $granularity=1, $format='Y-m-d H:i:s')
	{
		$difference = time() - $timestamp;
		if ($difference < 0) return 'just now';
		elseif ($difference < 86400*2)
		{
			return $this->duration($difference, $granularity) . ' ago';
		}
		else
		{
			return date($this->get_option('day_format'), $timestamp);
		}
	}
	
	/**
	 * Returns the duration from a difference.
	 * @param $difference {Integer}
	 * @param $granularity {Integer}
	 * @return {String}
	 */
	function duration($difference, $granularity=2)
	{
		{ // if difference is over 10 days show normal time form
			$periods = array(
				$this->__('w') => 604800,
				$this->__('d') => 86400,
				$this->__('h') => 3600,
				$this->__('m') => 60,
				$this->__('s') => 1
			);
			$output = '';
			foreach ($periods as $key => $value)
			{
				if ($difference >= $value)
				{
					$time = round($difference / $value);
					$difference %= $value;
					$output .= ($output ? ' ' : '').$time.$key;
					//$output .= (($time > 1 && ($key == 'week' || $key == 'day')) ? $key.'s' : $key);
					$granularity--;
				}
				if ($granularity == 0) break;
			}
			return ($output ? $output : '0 seconds');
		}
	}
	
	function get_cron_task_description($name)
	{
		switch ($name)
		{
			case 'lifestream_cleanup':
				return 'Cleans up old events and error messages.';
			break;
			case 'lifestream_cron':
				return 'Updates all active feeds.';
			break;
			case 'lifestream_digest_cron':
				return 'Creates a daily digest post if enabled.';
			break;
		}
	}

	function restore_options()
	{
		// default options and their values
		foreach ($this->_options as $key=>$value)
		{
			$this->update_option($key, $value);
		}
		$this->update_option('extension_dir', WP_CONTENT_DIR.'/wp-lifestream/extensions/');
		$this->update_option('theme_dir', WP_CONTENT_DIR.'/wp-lifestream/themes/');
		$this->update_option('icon_dir', WP_CONTENT_DIR.'/wp-lifestream/icons/');
	}
	
	function restore_database()
	{
		global $wpdb;
		
		$this->safe_query("DROP TABLE `".$wpdb->prefix."lifestream_event`;");
		$this->safe_query("DROP TABLE `".$wpdb->prefix."lifestream_event_group`;");
		$this->safe_query("DROP TABLE `".$wpdb->prefix."lifestream_feeds`;");
		$this->safe_query("DROP TABLE `".$wpdb->prefix."lifestream_error_log`;");
		$this->install_database();
	}

	function reschedule_cron()
	{
		wp_clear_scheduled_hook('lifestream_cron');
		wp_clear_scheduled_hook('lifestream_cleanup');
		wp_clear_scheduled_hook('lifestream_digest_cron');

		wp_schedule_event(time()+60, 'daily', 'lifestream_cleanup');
		
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
			if (date('H') > $digest_time)
			{
				$time = strtotime('+1 day', $time);
			}
			else
			{
				$time = strtotime(date('Y-m-d '.$digest_time.':00:00', $time));
			}
		}
		wp_schedule_event($time, 'lifestream_digest', 'lifestream_digest_cron');
	}

	function deactivate()
	{
		wp_clear_scheduled_hook('lifestream_cron');
		wp_clear_scheduled_hook('lifestream_cleanup');
		wp_clear_scheduled_hook('lifestream_digest_cron');
	}

	/**
	 * Initializes the plug-in upon activation.
	 */
	function activate()
	{
		global $wpdb, $userdata;

		get_currentuserinfo();

		// Options/database install
		$this->install();
		
		// Add a feed for this blog
		$results =& $wpdb->get_results("SELECT COUNT(*) as `count` FROM `".$wpdb->prefix."lifestream_feeds`");
		if (!$results[0]->count)
		{
			$rss_url = get_bloginfo('rss2_url');
			$options = array('url' => $rss_url);

			$feed = new Lifestream_BlogFeed($this, $options);
			$feed->owner = $userdata->display_name;
			$feed->owner_id = $userdata->ID;
			$feed->save(false);
		}
		
		// Cron job for the update
		$this->reschedule_cron();
	}

	function credits()
	{
		return 'Powered by <a href="http://www.enthropia.com/labs/wp-lifestream/">Lifestream</a>.';
	}

	/**
	 * Adds/updates the options on plug-in activation.
	 */
	function install($allow_database_install=true)
	{
		global $wpdb, $userdata;

		$version = (string)$this->get_option('_version', 0);

		if ($allow_database_install)
		{
			$this->install_database($version);
		}

		if (version_compare($version, '0.95', '<'))
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
		
		// default options and their values
		foreach ($this->_options as $key=>$value)
		{
			$this->add_option($key, $value);
		}
		// because these are based off of the WP_CONTENT_DIR they cannot be included in $_options
		$this->add_option('extension_dir', WP_CONTENT_DIR.'/wp-lifestream/extensions/');
		$this->add_option('theme_dir', WP_CONTENT_DIR.'/wp-lifestream/themes/');
		$this->add_option('icon_dir', WP_CONTENT_DIR.'/wp-lifestream/icons/');
		
		if (!$this->get_option('page_id'))
		{
			get_currentuserinfo();
			
			// First let's see if they have a legacy page:
			
			$results = $wpdb->get_results($wpdb->prepare("SELECT `ID` FROM `".$wpdb->prefix."posts` WHERE `post_type` = 'page' AND (`post_content` LIKE '%%[lifestream]%%' OR `post_title` LIKE 'Lifestream') AND `post_author` = %d AND `post_status` != 'trash' LIMIT 2", $userdata->ID));
			if (count($results) == 1)
			{
				$this->update_option('page_id', $results[0]->ID);
			}
			elseif (!count($results))
			{
				$this->create_page_template();
			}
		}
		
		if (version_compare($version, LIFESTREAM_VERSION, '=')) return;

		$this->update_option('_version', LIFESTREAM_VERSION);
	}
	
	function create_page_template()
	{
		global $userdata;
		
		get_currentuserinfo();
		
		$post = array(
			'post_title' => 'Lifestream',
			'post_content' => 'A stream of my online social activity.',
			'post_status' => 'publish',
			'post_author' => $userdata->ID,
			'post_type' => 'page',
			// should we insert the feed types into the tags?
			// 'tags_input' => ''
		);
		$post_id = wp_insert_post($post);
		$this->update_option('page_id', $post_id);
		return $post_id;
	}
	
	function get_page()
	{
		$page = get_post($this->get_option('page_id'));
		return $page;
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
			throw new Lifestream_Error($reason);
		}
		return $result;
	}

	/**
	 * Initializes the database if it's not already present.
	 */
	function install_database($version=0)
	{
		global $wpdb, $userdata;

		get_currentuserinfo();

		$this->safe_query("CREATE TABLE IF NOT EXISTS `".$wpdb->prefix."lifestream_event` (
		  `id` int(10) unsigned NOT NULL auto_increment,
		  `feed_id` int(10) unsigned NOT NULL,
		  `post_id` int(10) unsigned default 0 NOT NULL,
		  `feed` varchar(32) NOT NULL,
		  `link` varchar(200) NOT NULL,
		  `data` blob NOT NULL,
		  `visible` tinyint(1) default 1 NOT NULL,
		  `timestamp` int(11) NOT NULL,
		  `version` int(11) default 0 NOT NULL,
		  `key` char(16) NOT NULL,
		  `group_key` char(32) NOT NULL,
		  `owner` varchar(128) NOT NULL,
		  `owner_id` int(10) unsigned NOT NULL,
		  PRIMARY KEY  (`id`),
		  INDEX `feed` (`feed`),
		  UNIQUE `feed_id` (`feed_id`, `group_key`, `owner_id`, `link`)
		);");

		$this->safe_query("CREATE TABLE IF NOT EXISTS `".$wpdb->prefix."lifestream_event_group` (
		  `id` int(10) unsigned NOT NULL auto_increment,
		  `feed_id` int(10) unsigned NOT NULL,
		  `event_id` int(10) unsigned NOT NULL,
		  `post_id` int(10) unsigned default 0 NOT NULL,
		  `feed` varchar(32) NOT NULL,
		  `data` blob NOT NULL,
		  `total` int(10) unsigned default 1 NOT NULL,
		  `updated` tinyint(1) default 0 NOT NULL,
		  `visible` tinyint(1) default 1 NOT NULL,
		  `timestamp` int(11) NOT NULL,
		  `version` int(11) default 0 NOT NULL,
		  `key` char(16) NOT NULL,
		  `group_key` char(32) NOT NULL,
		  `owner` varchar(128) NOT NULL,
		  `owner_id` int(10) unsigned NOT NULL,
		  PRIMARY KEY  (`id`),
		  INDEX `feed` (`feed`),
		  INDEX `feed_id` (`feed_id`, `group_key`, `owner_id`, `timestamp`)
		);");

		$this->safe_query("CREATE TABLE IF NOT EXISTS `".$wpdb->prefix."lifestream_feeds` (
		  `id` int(10) unsigned NOT NULL auto_increment,
		  `feed` varchar(32) NOT NULL,
		  `options` text default NULL,
		  `timestamp` int(11) NOT NULL,
		  `active` tinyint(1) default 1 NOT NULL,
		  `owner` varchar(128) NOT NULL,
		  `owner_id` int(10) unsigned NOT NULL,
		  `version` int(11) default 0 NOT NULL,
		  INDEX `owner_id` (`owner_id`),
		  PRIMARY KEY  (`id`)
		);");

		$this->safe_query("CREATE TABLE IF NOT EXISTS `".$wpdb->prefix."lifestream_error_log` (
		  `id` int(10) unsigned NOT NULL auto_increment,
		  `message` varchar(255) NOT NULL,
		  `trace` text NULL,
		  `feed_id` int(10) unsigned NULL,
		  `timestamp` int(11) NOT NULL,
		  `has_viewed` tinyint(1) default 0 NOT NULL,
		  INDEX `feed_id` (`feed_id`, `has_viewed`),
		  INDEX `has_viewed` (`has_viewed`),
		  PRIMARY KEY  (`id`)
		);");

		if (!$version) return;

		// URGENT TODO: we need to solve alters when the column already exists due to WP issues

		if (version_compare($version, '0.5', '<'))
		{
			// Old wp-cron built-in stuff
			wp_clear_scheduled_hook('Lifestream_Hourly');

			// Upgrade them to version 0.5
			$wpdb->query("ALTER IGNORE TABLE `".$wpdb->prefix."lifestream_event_group` ADD `version` INT(11) NOT NULL DEFAULT '0' AFTER `timestamp`, ADD `key` CHAR( 16 ) NOT NULL AFTER `version`;");
			$wpdb->query("ALTER IGNORE TABLE `".$wpdb->prefix."lifestream_event` ADD `version` INT(11) NOT NULL DEFAULT '0' AFTER `timestamp`, ADD `key` CHAR( 16 ) NOT NULL AFTER `version`;");
		}
		if (version_compare($version, '0.6', '<'))
		{
			$wpdb->query("ALTER IGNORE TABLE `".$wpdb->prefix."lifestream_event_group` ADD `owner` VARCHAR(128) NOT NULL AFTER `key`, ADD `owner_id` int(10) unsigned NOT NULL AFTER `owner`;");
			$wpdb->query("ALTER IGNORE TABLE `".$wpdb->prefix."lifestream_event` ADD `owner` VARCHAR(128) NOT NULL AFTER `key`, ADD `owner_id` int(10) unsigned NOT NULL AFTER `owner`;");
			$wpdb->query("ALTER IGNORE TABLE `".$wpdb->prefix."lifestream_feeds` ADD `owner` VARCHAR(128) NOT NULL AFTER `timestamp`, ADD `owner_id` int(10) unsigned NOT NULL AFTER `owner`;");
			$wpdb->query("ALTER IGNORE TABLE `".$wpdb->prefix."lifestream_event` DROP INDEX `feed_id`, ADD UNIQUE `feed_id` (`feed_id` , `key` , `owner_id` , `link` );");
			$wpdb->query("ALTER IGNORE TABLE `".$wpdb->prefix."lifestream_event_group` DROP INDEX `feed_id`, ADD INDEX `feed_id` (`feed_id` , `key` , `timestamp` , `owner_id`);");
			$wpdb->query("ALTER IGNORE TABLE `".$wpdb->prefix."lifestream_feeds` ADD INDEX `owner_id` (`owner_id`);");
			$this->safe_query($wpdb->prepare("UPDATE `".$wpdb->prefix."lifestream_feeds` SET `owner` = %s, `owner_id` = %d", $userdata->display_name, $userdata->ID));
			$this->safe_query($wpdb->prepare("UPDATE `".$wpdb->prefix."lifestream_event` SET `owner` = %s, `owner_id` = %d", $userdata->display_name, $userdata->ID));
			$this->safe_query($wpdb->prepare("UPDATE `".$wpdb->prefix."lifestream_event_group` SET `owner` = %s, `owner_id` = %d", $userdata->display_name, $userdata->ID));
		}
		if (version_compare($version, '0.81', '<'))
		{
			$wpdb->query("ALTER IGNORE TABLE `".$wpdb->prefix."lifestream_event` ADD `feed` VARCHAR(32) NOT NULL AFTER `feed_id`");
			$this->safe_query("UPDATE IGNORE `".$wpdb->prefix."lifestream_event` as t1 set t1.`feed` = (SELECT t2.`feed` FROM `".$wpdb->prefix."lifestream_feeds` as t2 WHERE t1.`feed_id` = t2.`id`)");
		}
		if (version_compare($version, '0.84', '<'))
		{
			$wpdb->query("ALTER IGNORE TABLE `".$wpdb->prefix."lifestream_event` ADD INDEX ( `feed` )");
			$wpdb->query("ALTER IGNORE TABLE `".$wpdb->prefix."lifestream_event_group` ADD INDEX ( `feed` )");
		}
		if (version_compare($version, '0.90', '<'))
		{
			$wpdb->query("ALTER IGNORE TABLE `".$wpdb->prefix."lifestream_feeds` ADD `version` int(11) default 0 NOT NULL AFTER `owner_id`");
		}
		if (version_compare($version, '0.99.6.0', '<'))
		{
			$wpdb->query("ALTER IGNORE TABLE `".$wpdb->prefix."lifestream_feeds` ADD `active` tinyint(1) default 1 NOT NULL AFTER `timestamp`");
		}
		if (version_compare($version, '0.99.9.4', '<'))
		{
			$wpdb->query("UPDATE `".$wpdb->prefix."lifestream_feeds` SET `active` = 1");
		}
		if (version_compare($version, '0.99.9.7', '<'))
		{
			$wpdb->query("ALTER IGNORE TABLE `".$wpdb->prefix."lifestream_event` ADD `group_key` char(32) NOT NULL AFTER `key`, DROP KEY `feed_id`, ADD UNIQUE `feed_id` (`feed_id`, `group_key`, `owner_id`, `link`)");
			$wpdb->query("ALTER IGNORE TABLE `".$wpdb->prefix."lifestream_event_group` ADD `group_key` char(32) NOT NULL AFTER `key`, DROP KEY `feed_id`, ADD INDEX `feed_id` (`feed_id`, `group_key`, `owner_id`, `timestamp`)");
			$wpdb->query("UPDATE `".$wpdb->prefix."lifestream_event` SET `group_key` = md5(`key`)");
			$wpdb->query("UPDATE `".$wpdb->prefix."lifestream_event_group` SET `group_key` = md5(`key`)");
		}
		if (version_compare($version, '0.99.9.7.1', '<'))
		{
			$wpdb->query("ALTER IGNORE TABLE `".$wpdb->prefix."lifestream_event` ADD `post_id` int(10) unsigned default 0 NOT NULL AFTER `feed_id`");
			$wpdb->query("ALTER IGNORE TABLE `".$wpdb->prefix."lifestream_event_group` ADD `post_id` int(10) unsigned default 0 NOT NULL AFTER `feed_id`");
			$this->upgrade_posts_to_events();
		}
	}
	
	/**
	 * Imports all events as custom posts in WordPress 2.9.
	 */
	function upgrade_posts_to_events()
	{
		$new_events = 0;
		$offset = 0;
		$events = $this->get_events(array(
			'offset' => $offset,
			'post_ids' => array(0),
		));
		while ($events)
		{
			foreach ($events as &$event)
			{
				$this->create_post_for_event($event);
				$new_events += 1;
			}
			$offset += 50;
			$events = $this->get_events(array(
				'offset' => $offset,
			));
		}
		return $new_events;
	}
	
	function create_post_for_event($event)
	{
		global $wpdb;
		
		// TODO: find a better title
		$post = array(
			'post_title' => 'Lifestream Event',
			'post_content' => '',
			'post_status' => 'publish',
			'post_author' => $event->owner_id,
			'post_type' => 'lsevent',
			// should we insert the feed types into the tags?
			// 'tags_input' => ''
			'post_date' => date('Y-m-d H:i:s', $event->timestamp),
		);
		$post_id = wp_insert_post($post);
		$event->post_id = $post_id;
		$event_list = array();
		if ($event->is_grouped)
		{
			$wpdb->query($wpdb->prepare("UPDATE `".$wpdb->prefix."lifestream_event_group` set `post_id` = %d WHERE `id` = %d", $event->post_id, $event->id));
			foreach ($event->data as $event)
			{
				// TODO: append to $event_list
			}
		}
		else
		{
			$event_list[] = $event;
		}
		// TODO: process event list and update post ids
	}
	
	function get_page_from_request()
	{
		return (!empty($_GET[$this->paging_key]) ? $_GET[$this->paging_key] : 1);
	}
	function get_next_page_url($page=null)
	{
		if (!$page) $page = $this->get_page_from_request();
		if (!empty($_SERVER['QUERY_STRING'])) {
			$url = str_replace('&'.$this->paging_key.'='.$page, '', $_SERVER['QUERY_STRING']);
			return '?'.$url.'&'.$this->paging_key.'='.($page+1);
		}
		return '?'.$this->paging_key.'='.($page+1);
	}
	function get_previous_page_url($page=null)
	{
		if (!$page) $page = $this->get_page_from_request();
		if (strpos($_SERVER['QUERY_STRING'], '?') !== false) {
			$url = str_replace('&'.$this->paging_key.'='.$page, '', $_SERVER['QUERY_STRING']);
			return $url.'&'.$this->paging_key.'='.($page-1);
		}
		return '?'.$this->paging_key.'='.($page-1);
	}
	
	/**
	 * Gets recent events from the lifestream.
	 * @param {Array} $_ An array of keyword args.
	 */
	function get_events($_=array())
	{
		global $wpdb;

		$defaults = array(
			 // number of events
			'event_ids'			=> array(),
			'post_ids'			=> array(),
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
		if (!empty($_['number_of_results'])) $_['limit'] = $_['number_of_results'];

		if (!((int)$_['limit'] > 0)) return false;
		if (!((int)$_['offset'] >= 0)) return false;

		if (!preg_match('/[\d]+ (month|day|year|hour|second|microsecond|week|quarter)s?/', $_['date_interval'])) $_['date_interval'] = -1;
		else $_['date_interval'] = rtrim($_['date_interval'], 's');

		$_['feed_ids'] = (array)$_['feed_ids'];
		$_['event_ids'] = (array)$_['event_ids'];
		$_['post_ids'] = (array)$_['post_ids'];
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
		if (count($_['event_ids']))
		{
			foreach ($_['event_ids'] as $key=>$value)
			{
				$_['event_ids'][$key] = $wpdb->escape($value);
			}
			$where[] = 't1.`id` IN ('.implode(', ', $_['event_ids']).')';
		}
		elseif (count($_['post_ids']))
		{
			foreach ($_['post_ids'] as $key=>$value)
			{
				$_['post_ids'][$key] = $wpdb->escape($value);
			}
			$where[] = 't1.`post_id` IN ('.implode(', ', $_['post_ids']).')';
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
			$cls = 'Lifestream_Event';
		}
		else
		{
			$table = 'lifestream_event_group';
			$cls = 'Lifestream_EventGroup';
		}
		$sql = sprintf("SELECT t1.*, t2.`options` FROM `".$wpdb->prefix.$table."` as `t1` INNER JOIN `".$wpdb->prefix."lifestream_feeds` as t2 ON t1.`feed_id` = t2.`id` WHERE t1.`visible` = 1 AND (%s) ORDER BY t1.`timestamp` DESC LIMIT %d, %d", implode(') AND (', $where), $_['offset'], $_['limit']);

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

require_once(LIFESTREAM_PATH . '/inc/labels.php');

abstract class Lifestream_Extension
{
	/**
	 * Represents a feed object in the database.
	 */
	
	public $options;
	public static $builtin = false;
	public static $absolute_path = __FILE__;
	
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
	const LABEL			= 'Lifestream_Label';
	// The version is so you can manage data in the database for old versions.
	const VERSION		= 2;
	const MEDIA			= 'automatic';
	const HAS_EXCERPTS	= false;

	/**
	 * Instantiates this object through a feed database object.
	 */
	public static function construct_from_query_result(&$lifestream, $row)
	{
		$class = $lifestream->get_feed($row->feed);
		if (!$class)
		{
			$class = 'Lifestream_InvalidExtension';
		}
		if (!empty($row->options)) $options = unserialize($row->options);
		else $options = null;
		
		$instance = new $class($lifestream, $options, $row->id, $row);
		$instance->date = $row->timestamp;
		return $instance;
	}

	function __construct(&$lifestream, $options=array(), $id=null, $row=null)
	{
		$this->lifestream = $lifestream;
		$this->options = $options;
		$this->id = $id;
		if ($row)
		{
			$this->active = $row->active;
			$this->owner = $row->owner;
			$this->owner_id = $row->owner_id;
			$this->_owner_id = $row->owner_id;
			$this->version = $row->version;
			$this->events = (int)@($row->events);
			$this->feed = $row->feed;
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
	
	function get_event_excerpt(&$event, &$bit)
	{
		if (!isset($this->option['excerpts']))
		{
			// default legacy value
			$this->update_option('excerpt', 1);
		}
		if ($this->get_option('excerpt') > 0)
		{
			$excerpt = $this->get_event_description($event, $bit);
		}
		if ($this->get_option('excerpt') == 1)
		{
			$excerpt = $this->lifestream->truncate($excerpt, $this->lifestream->get_option('truncate_length'));
		}
		return $excerpt;
	}

	function has_excerpt(&$event, &$bit)
	{
		if (!isset($this->option['excerpts']))
		{
			// default legacy value
			$this->update_option('excerpt', 1);
		}
		return ($this->get_option('excerpt') > 0 && $this->get_event_description($event, $bit));
	}
	
	/**
	 * Returns the description (also used in excerpts) for this
	 * event.
	 * @return {String} event description
	 */
	function get_event_description(&$event, &$bit)
	{
		return $bit['description'];
	}
	
	function get_event_display(&$event, &$bit)
	{
		return $bit['title'];
	}
	
	function get_event_link(&$event, &$bit)
	{
		return $bit['link'];
	}
	
	function get_feed_display()
	{
		return $this->__toString();
	}
	
	function get_icon_name()
	{
		return 'icon.png';
	}
	
	function get_icon_url()
	{
		// TODO: clean this up to use the new Lifestream::get_media methods
		if ($this->get_option('icon_url'))
		{
			return $this->get_option('icon_url');
		}
		$path = trailingslashit($this->lifestream->paths[get_class($this)]);
		$root = trailingslashit(dirname(__FILE__));
		if ($path == $root)
		{
			$path = $this->lifestream->icons[$this->lifestream->get_option('icons', 'default')]['__path'];
			$icon_path = lifestream_path_join($path, $this->get_constant('ID').'.png');
			if (!is_file($icon_path))
			{
				$icon_path = lifestream_path_join($path, 'generic.png');
			}
			$path = $icon_path; 
		}
		else
		{
			$path = lifestream_path_join($path, $this->get_icon_name());
		}
		return $this->lifestream->get_absolute_media_url($path);
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
		return is_array(@$item['thumbnail']) ? $item['thumbnail']['url'] : @$item['thumbnail'];
	}

	function get_public_name()
	{
		if ($this->get_option('feed_label'))
		{
			return $this->get_option('feed_label');
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
	
	/**
	 * Fetches the value of an option. Returns `null` if the option is not set.
	 */
	function get_option($option, $default=null)
	{
		if (!isset($this->options[$option]))
		{
			$value = $default;
		}
		else {
			$value = $this->options[$option];
			if (!$value) $value = $default;
		}
		if (empty($value)) $value = null;
		return $value;
	}
	
	/**
	 * Removes an option.
	 */
	function delete_option($option)
	{
		unset($this->options[$option]);
	}
	
	/**
	 * Updates the value of an option.
	 */
	function update_option($option, $value)
	{
		$this->options[$option] = $value;
	}
	
	/**
	 * Sets an option if it doesn't exist.
	 */
	function add_option($option, $value)
	{
		if (!array_key_exists($option, $this->options) || $this->options[$option] === '')
		{
			$this->options[$option] = $value;
		}
	}
	
	function truncate()
	{
		global $wpdb;
		if ($this->id)
		{
			$wpdb->query($wpdb->prepare("DELETE FROM `".$wpdb->prefix."lifestream_event` WHERE `feed_id` = %d", $this->id));
			$this->lifestream->safe_query($wpdb->prepare("DELETE FROM `".$wpdb->prefix."posts` WHERE `post_type` = 'lsevent' AND `ID` IN (SELECT `post_id` FROM `".$wpdb->prefix."lifestream_event_group` WHERE `feed_id` = %d)", $this->id));
			$wpdb->query($wpdb->prepare("DELETE FROM `".$wpdb->prefix."lifestream_event_group` WHERE `feed_id` = %d", $this->id));
		}
	}
	
	function save($validate=true)
	{
		global $wpdb;

		$this->save_options($validate);
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
		$this->lifestream->safe_query($wpdb->prepare("DELETE FROM `".$wpdb->prefix."posts` WHERE `post_type` = 'lsevent' AND `ID` IN (SELECT `post_id` FROM `".$wpdb->prefix."lifestream_event_group` WHERE `feed_id` = %d)", $this->id));
		$this->lifestream->safe_query($wpdb->prepare("DELETE FROM `".$wpdb->prefix."lifestream_event_group` WHERE `feed_id` = %d", $this->id));
		$this->lifestream->safe_query($wpdb->prepare("DELETE FROM `".$wpdb->prefix."lifestream_error_log` WHERE `feed_id` = %d", $this->id));
		$this->id = null;
	}
	
	/**
	 * Called upon saving options to handle additional data management.
	 */
	function save_options($validate=true) { }
	
	/**
	 * Validates the feed. A success has no return value.
	 */
	function test()
	{
		try
		{
			$this->save_options($validate=true);
			$this->fetch();
		}
		catch (Lifestream_Error $ex)
		{
			return $ex->getMessage();
		}
	}
	
	function refresh($urls=null, $initial=false)
	{
		global $wpdb;
		
		date_default_timezone_set('UTC');

		if (!$this->id) return array(false, $this->lifestream->__('Feed has not yet been saved.'));

		$grouped = array();
		$ungrouped = array();
		$total = 0;
		try
		{
			$items = $this->fetch($urls, $initial);
		}
		catch (Lifestream_Error $ex)
		{
			$this->lifestream->log_error($ex, $this->id);
			return array(false, $ex);
		}
		if (!$items) return array(false, $this->lifestream->__('Feed result was empty.'));

		if (!$initial) $default_timestamp = time();
		else $default_timestamp = 0;

		foreach ($items as $item_key=>&$item)
		{
			// We need to set the default timestamp if no dates are set
			$date = lifestream_array_key_pop($item, 'date');
			$key = lifestream_array_key_pop($item, 'key');
			$group_key = md5(lifestream_array_key_pop($item, 'group_key', $key));

			if (!($date > 0)) $date = $default_timestamp;
			
			if ($this->version == 2)
			{
				if ($item['guid']) $link_key = md5(lifestream_array_key_pop($item, 'guid'));
				else $link_key = md5($item['link'] . $item['title']);
			}
			elseif ($this->version == 1)
			{
				$link_key = md5($item['link'] . $item['title']);
			}
			else
			{
				$link_key = $item['link'];
			}
			
			$affected = $wpdb->query($wpdb->prepare("INSERT IGNORE INTO `".$wpdb->prefix."lifestream_event` (`feed_id`, `feed`, `link`, `data`, `timestamp`, `version`, `key`, `group_key`, `owner`, `owner_id`) VALUES (%d, %s, %s, %s, %d, %d, %s, %s, %s, %d)", $this->id, $this->get_constant('ID'), $link_key, serialize($item), $date, $this->get_constant('VERSION'), $key, $group_key, $this->owner, $this->owner_id));
			if ($affected)
			{
				$item['id'] = $wpdb->insert_id;
				$item['date'] = $date;
				$item['key'] = $key;
				$item['group_key'] = $group_key;
				$total += 1;

				$label = $this->get_label_class($key);
				if ($this->get_option('grouped') && $this->get_constant('CAN_GROUP') && constant(sprintf('%s::%s', $label, 'CAN_GROUP')))
				{
					if (!array_key_exists($group_key, $grouped)) $grouped[$group_key] = array();
					$grouped[$group_key][date('m d Y', $date)] = $date;
				}
				else
				{
					$ungrouped[] = $item;
				}
			}
			else
			{
				unset($items[$item_key]);
			}
		}
		// Grouping them by key
		foreach ($grouped as $group_key=>$dates)
		{
			// Grouping them by date
			foreach ($dates as $date_key=>$date)
			{
				// Get all of the current events for this date
				// (including the one we affected just now)
				$results =& $wpdb->get_results($wpdb->prepare("SELECT `data`, `link` FROM `".$wpdb->prefix."lifestream_event` WHERE `feed_id` = %d AND `visible` = 1 AND DATE(FROM_UNIXTIME(`timestamp`)) = DATE(FROM_UNIXTIME(%d)) AND `group_key` = %s", $this->id, $date, $group_key));
				$events = array();
				foreach ($results as &$result)
				{
					$result->data = unserialize($result->data);
					if (!$result->data['link']) $result->data['link'] = $result->link;
					$events[] = $result->data;
				}

				// First let's see if the group already exists in the database
				$group =& $wpdb->get_results($wpdb->prepare("SELECT `id` FROM `".$wpdb->prefix."lifestream_event_group` WHERE `feed_id` = %d AND DATE(FROM_UNIXTIME(`timestamp`)) = DATE(FROM_UNIXTIME(%d)) AND `group_key` = %s LIMIT 0, 1", $this->id, $date, $group_key));
				if (count($group) == 1)
				{
					$group =& $group[0];
					$wpdb->query($wpdb->prepare("UPDATE `".$wpdb->prefix."lifestream_event_group` SET `data` = %s, `total` = %d, `updated` = 1, `timestamp` = %d WHERE `id` = %d", serialize($events), count($events), $date, $group->id));
				}
				else
				{
					$wpdb->query($wpdb->prepare("INSERT INTO `".$wpdb->prefix."lifestream_event_group` (`feed_id`, `feed`, `data`, `total`, `timestamp`, `version`, `key`, `group_key`, `owner`, `owner_id`, `post_id`) VALUES(%d, %s, %s, %d, %d, %d, %s, %s, %s, %d, 0)", $this->id, $this->get_constant('ID'), serialize($events), count($events), $date, $this->get_constant('VERSION'), $key, $group_key, $this->owner, $this->owner_id));
				}
			}
		}
		foreach ($ungrouped as &$item)
		{
			$date = lifestream_array_key_pop($item, 'date');
			$key = lifestream_array_key_pop($item, 'key');
			$group_key = lifestream_array_key_pop($item, 'group_key');

			$wpdb->query($wpdb->prepare("INSERT INTO `".$wpdb->prefix."lifestream_event_group` (`feed_id`, `feed`, `event_id`, `data`, `timestamp`, `total`, `version`, `key`, `group_key`, `owner`, `owner_id`, `post_id`) VALUES(%d, %s, %d, %s, %d, 1, %d, %s, %s, %s, %d, 0)", $this->id, $this->get_constant('ID'), $item['id'], serialize(array($item)), $date, $this->get_constant('VERSION'), $key, $group_key, $this->owner, $this->owner_id));
		}
		$wpdb->query($wpdb->prepare("UPDATE `".$wpdb->prefix."lifestream_feeds` SET `timestamp` = UNIX_TIMESTAMP() WHERE `id` = %d", $this->id));
		unset($items, $ungrouped);
		
		$this->lifestream->upgrade_posts_to_events();
		return array(true, $total);
	}
	
	/**
	 * Processes a row and returns an array of data dictionaries.
	 * @return {Array} Array of data dictionaries.
	 */
	function yield_many()
	{
		$args = func_get_args();
		$data = call_user_func_array(array(&$this, 'yield'), $args);
		return array($data);
	}

	/**
	 * Processes a row and returns a data dictionary.
	 * Should at the very least return title and link keys.
	 * @abstract
	 * @return {Array} Data dictionary.
	 */
	//abstract function yield();

	abstract function fetch();
	
	function get_id($event, $uniq_id='')
	{
		return 'ls-'.$event->id.'-'.$uniq_id;
	}
	
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
	
	function get_label_class($key)
	{
		return $this->get_constant('LABEL');
	}
	
	function get_label($event, $options=array())
	{
		$cls = $this->get_label_class($event->key);
		return new $cls($this, $event, $options);
	}
	
	function render($event, $options=array())
	{
		$lifestream = $this->lifestream;

		$label_inst = $event->get_label_instance($options);
		
		if ($event->is_grouped && count($event->data) == 1 && $this->get_constant('MUST_GROUP')) $visible = true;
		else $visible = isset($options['show_details']) ? !empty($options['show_details']) : null;
		if ($visible === null) $visible = !$this->lifestream->get_option('hide_details_default');

		$filename = $label_inst->get_template();
		require($this->lifestream->get_theme_filepath('templates/'.$filename.'.inc.php'));
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
			$events[] = new Lifestream_Event($this->lifestream, $result);
		}
		return $events;
	}
}
class Lifestream_InvalidExtension extends Lifestream_Extension
{
	const NAME = '(The extension could not be found)';
	
	function get_url()
	{
		return $this->feed;
	}
	
	function fetch()
	{
		return;
	}
}

/**
 * Generic RSS/Atom feed extension.
 */
class Lifestream_Feed extends Lifestream_Extension
{
	function save_options($validate=true)
	{
		$urls = $this->get_url();
		if (!is_array($urls)) $urls = array($urls);
		
		$url = $urls[0];
		
		if (is_array($url)) $url = $url[0];
		
		$feed = new SimplePie();
		$feed->enable_cache(false);
		if ($validate)
		{
			$data = $this->lifestream->file_get_contents($url);
			$feed->set_raw_data($data);
			$feed->enable_order_by_date(false);
			$feed->force_feed(true); 
			$success = $feed->init();
		}
		if ($this->get_option('auto_icon') == 2 && ($url = $feed->get_favicon()))
		{
			if ($this->lifestream->validate_image($url))
			{
				$this->update_option('icon_url', $url);
			}
			else
			{
				$this->update_option('icon_url', '');
			}
		}
		// elseif ($this->get_option('icon_url'))
		// {
		//  if (!$this->lifestream->validate_image($this->get_option('icon_url')))
		//  {
		//	  throw new Lifestream_Error($this->lifestream->__('The icon url is not a valid image.'));
		//  }
		// }
		
		parent::save_options();
	}
	
	/**
	 * Fetches all current events from this extension.
	 * @return {Array} List of events.
	 */
	function fetch($urls=null, $initial=false)
	{
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
				throw new Lifestream_FeedFetchError("Error fetching feed from {$url} ({$feed->error()})....\n\n{$sample}");
			}
			
			$feed->handle_content_type();
			foreach ($feed->get_items() as $row)
			{
				$rows =& $this->yield_many($row, $url, $key);
				foreach ($rows as $row)
				{
					if (!$row) continue;
					if (!$row['key']) $row['key'] = $key;
					if (count($row)) $items[] = $row;
				}
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
			
			if (($player = $enclosure->get_player()))
			{
				$data['player_url'] = $player;
			}
			
			if (!$data['key']) $data['key'] = ($data['player_url'] ? 'video' : 'photo');
		}
		return $data;
	}

	function get_url()
	{
		return $this->get_option('url');
	}
	
	function parse_urls($text)
	{
		# match http(s):// urls
		$text = preg_replace('@(https?://([-\w\.]+)+(:\d+)?(/([\w\=/\~_\.\%\-]*(\?\S+)?)?)?)@', '<a href="$1">$1</a>', $text);
		# match www urls
		$text = preg_replace('@((?<!http://)www\.([-\w\.]+)+(:\d+)?(/([\w/\=\~_\.\%\-]*(\?\S+)?)?)?)@', '<a href="http://$1">$1</a>', $text);
		# match email@address
		$text = preg_replace('/\b([A-Z0-9._%+-]+@(?:[A-Z0-9-]+\.)+[A-Z]{2,4})\b/i', '<a href="mailto:$1">$1</a>', $text);
		return $text;
	}
}

/**
 * You need to pass a thumbnail item in yield() for PhotoFeed item's
 */
class Lifestream_PhotoFeed extends Lifestream_Feed
{
	const LABEL = 'Lifestream_PhotoLabel';
	const MUST_GROUP = true;
}

class Lifestream_GenericFeed extends Lifestream_Feed {
	const DESCRIPTION = 'The generic feed can handle both feeds with images (in enclosures), as well as your standard text based RSS and Atom feeds.';
	
	function get_options()
	{		
		return array(
			'url' => array($this->lifestream->__('Feed URL:'), true, '', ''),
		);
	}

	function get_public_url()
	{
		return $this->get_option('url');
	}
	
	function get_label($event, $options)
	{
		if ($event->key == 'photo')	$cls = Lifestream_PhotoFeed::LABEL;
		else $cls = $this->get_constant('LABEL');
		return new $cls($this, $event, $options);
	}
}
$lifestream->register_feed('Lifestream_GenericFeed');

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
		'id'	=> $lifestream->generate_unique_id(),
		'limit' => $lifestream->get_option('number_of_items'),
	);

	if (@$_[0] && !is_array($_[0]))
	{
		// old style
		$_ = array(
			'limit'			=> @$_[0],
			'feed_ids'		=> @$_[1],
			'date_interval'	=> @$_[2],
			'user_ids'		=> @$_[4],
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
	$page = $lifestream->get_page_from_request();
	$defaults['offset'] = ($page-1)*(!empty($_['limit']) ? $_['limit'] : $defaults['limit']);

	$_ = array_merge($defaults, $_);
	$limit = $_['limit'];
	$_['limit'] = $_['limit'] + 1;
	$options =& $_;
	
	// TODO: offset
	//$offset = $lifestream->get_option('lifestream_timezone');
	$events = call_user_func(array(&$lifestream, 'get_events'), $_);
	$has_next_page = (count($events) > $limit);
	if ($has_next_page) {
		$events = array_slice($events, 0, $limit);
	}
	$has_prev_page = ($page > 1);
	$has_paging = ($has_next_page || $has_prev_page);
	$show_metadata = empty($options['hide_metadata']);
	
	require($lifestream->get_theme_filepath('main.inc.php'));

	echo '<!-- Powered by Lifestream (version: '.LIFESTREAM_VERSION.'; theme: '.$lifestream->get_option('theme', 'default').'; iconset: '.$lifestream->get_option('icons', 'default').') -->';

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
	
	$_['id'] = $lifestream->generate_unique_id();
	
	$options =& $_;
	
	// TODO: offset
	//$offset = $lifestream->get_option('lifestream_timezone');
	$events = call_user_func(array(&$lifestream, 'get_events'), $_);
	$show_metadata = empty($options['hide_metadata']);
	
	require($lifestream->get_theme_filepath('sidebar.inc.php'));
}

function lifestream_register_feed($class_name)
{
	global $lifestream;
	
	$lifestream->register_feed($class_name);
}

// built-in feeds
//include(LIFESTREAM_PATH . '/inc/extensions.php');

// legacy local_feeds
// PLEASE READ extensions/README
@include(LIFESTREAM_PATH . '/local_feeds.inc.php');

// detect external extensions in extensions/
$lifestream->detect_extensions();
$lifestream->detect_themes();
$lifestream->detect_icons();

// sort once
ksort($lifestream->feeds);

// Require more of the codebase
require_once(LIFESTREAM_PATH . '/inc/widget.php');
require_once(LIFESTREAM_PATH . '/inc/syndicate.php');
require_once(LIFESTREAM_PATH . '/inc/template.php');

?>