<?php
define(LIFESTREAM_PLUGIN_FILE, dirname(__FILE__) . '/lifestream.php');
define(LIFESTREAM_FEEDS_PER_PAGE, 20);
define(LIFESTREAM_EVENTS_PER_PAGE, 50);

if (!class_exists('SimplePie'))
{
    require_once('lib/simplepie.inc');
}

$lifestream_path = trailingslashit(get_settings('siteurl')) . 'wp-content/plugins/lifestream';

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

$lifestream_feeds = array();
/**
 * Registers a feed class with LifeStream.
 */
function register_lifestream_feed($class_name)
{
    global $lifestream_feeds;

    $lifestream_feeds[get_class_constant($class_name, 'ID')] = $class_name;
}

function lifestream_file_get_contents($url)
{
    if (function_exists('curl_init'))
    {
        $ch = curl_init();
        $timeout = 5;
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
        $file_contents = curl_exec($ch);
        curl_close($ch);
        return $file_contents;
    }
    else
    {
        return file_get_contents($url);
    }
}

/*
 * This is a wrapper function which initiates the callback for the custom tag embedding.
 */
function lifestream_embed_callback($content)
{
    return preg_replace_callback("|[<\[]lifestream(?:\s+([a-z_]+)=[\"']?([a-z0-9_-\s]+)[\"']?)*\s*/?[>\]]|i", 'lifestream_embed_handler', $content);
}

/*
 * This function handles the real meat by handing off the work to helper functions.
 */
function lifestream_embed_handler($matches)
{
    // max_number
    // var_dump($matches);
    $args = array();
    for ($i=1; $i<=count($matches); $i+=2)
    {
        if ($matches[$i]) $args[$matches[$i]] = $matches[$i+1];
    }
    ob_start();
    if ($args['feed_ids']) $args['feed_ids'] = explode(',', $args['feed_ids']);
    if ($args['user_ids']) $args['user_ids'] = explode(',', $args['user_ids']);
    lifestream($args);
    return ob_get_clean();
}

/**
 * Initializes the plug-in upon first activation.
 */
function lifestream_activate()
{
    global $wpdb;
    // Add a feed for this blog
    
    lifestream_install();

    $results =& $wpdb->get_results("SELECT COUNT(*) as `count` FROM `".$wpdb->prefix."lifestream_feeds`");
    if (!$results[0]->count)
    {
        $rss_url = trailingslashit(get_settings('siteurl')) . 'wp-rss2.php';
        $options = array('url' => $rss_url);

        $feed = new LifeStream_BlogFeed($options);
        $feed->owner = 'admin';
        $feed->owner_id = 1;
        $feed->save();
        $feed->refresh();
    }
    else
    {
        lifestream_update();
    }
}

function lifestream_credits()
{
    return 'Powered by <a href="http://www.davidcramer.net/my-projects/lifestream">LifeStream</a> from <a href="http://www.ibegin.com/">iBegin</a>.';
}

$lifestream__options = array(
    'lifestream_day_format'     => 'F jS',
    'lifestream_hour_format'    => 'g:ia',
    'lifestream_timezone'       => (string)(date('O')/100),
    'lifestream_number_of_items'=> '50',
    'lifestream_date_interval'  => '1 month',
    'lifestream_digest_title'   => 'Daily Digest for %s',
    'lifestream_digest_body'    => '%1$s',
    'lifestream_digest_category'=> '1',
    'lifestream_digest_author'  => '1',
    'lifestream_update_interval'=> '15',
    'lifestream__in_digest'     => '0',
    'lifestream_show_owners'    => '0',
    'lifestream_use_ibox'       => '1',
    'lifestream_show_credits'   => '1',
);

/**
 * Adds/updates the options on plug-in activation.
 */
function lifestream_install($allow_database_install=true)
{
    global $lifestream__options;
    
    $version = get_option('lifestream__version');
    
    if (!$version) $version = 0;
    
    if ($version == LIFESTREAM_VERSION) return;
    
    // default options and their values
    
    foreach ($lifestream__options as $key=>$value)
    {
        if (!get_option($key)) update_option($key, $value);
    }

    if ($allow_database_install) lifestream_install_database($version);
    
    update_option('lifestream__version', LIFESTREAM_VERSION);
}

/**
 * Initializes the database if it's not already present.
 */
function lifestream_install_database($version)
{
    global $wpdb, $userdata;
    
    get_currentuserinfo();
    
    $wpdb->query("CREATE TABLE IF NOT EXISTS `".$wpdb->prefix."lifestream_event` (
      `id` int(11) NOT NULL auto_increment,
      `feed_id` int(11) NOT NULL,
      `link` varchar(200) NOT NULL,
      `data` blob NOT NULL,
      `visible` tinyint(1) default 1 NOT NULL,
      `timestamp` int(11) NOT NULL,
      `version` int(11) default 0 NOT NULL,
      `key` char(16) NOT NULL,
      `owner` varchar(128) NOT NULL,
      `owner_id` int(11) NOT NULL,
      PRIMARY KEY  (`id`),
      UNIQUE `feed_id` (`feed_id`, `key`, `owner_id`, `link`)
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8;");

    $wpdb->query("CREATE TABLE IF NOT EXISTS `".$wpdb->prefix."lifestream_event_group` (
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
      INDEX `feed_id` (`feed_id`, `key`, `owner_id`, `timestamp`)
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8;");
    
    $wpdb->query("CREATE TABLE IF NOT EXISTS `".$wpdb->prefix."lifestream_feeds` (
      `id` int(11) NOT NULL auto_increment,
      `feed` varchar(32) NOT NULL,
      `options` text default NULL,
      `timestamp` int(11) NOT NULL,
      `owner` varchar(128) NOT NULL,
      `owner_id` int(11) NOT NULL,
      INDEX `owner_id` (`owner_id`),
      PRIMARY KEY  (`id`)
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8;");
    
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
        $wpdb->query("ALTER TABLE `".$wpdb->prefix."lifestream_feeds` ADD INDEX `owner_id` (`owner_id`);");
        $wpdb->query(sprintf("UPDATE `".$wpdb->prefix."lifestream_feeds` SET `owner` = '%s', `owner_id` = %d", $userdata->user_nicename, $userdata->ID));
        $wpdb->query(sprintf("UPDATE `".$wpdb->prefix."lifestream_event` SET `owner` = '%s', `owner_id` = %d", $userdata->user_nicename, $userdata->ID));
        $wpdb->query(sprintf("UPDATE `".$wpdb->prefix."lifestream_event_group` SET `owner` = '%s', `owner_id` = %d", $userdata->user_nicename, $userdata->ID));
    }
}

class LifeStream_Event
{
    /**
     * Represents a grouped event in the database.
     */
     
     function __construct($row)
     {
         global $lifestream_feeds;
         
         $this->date = $row->timestamp;
         $this->data = unserialize($row->data);
         $this->id = $row->id;
         $this->timestamp = $row->timestamp;
         $this->total = $row->total;
         $this->key = $row->key;
         $this->version = $row->version;
         $this->owner = $row->owner;
         $this->owner_id = $row->owner_id;
         $this->visible = $row->visible;
         $this->link = ($this->data['link'] ? $this->data['link'] : $row->link);
         $this->feed = new $lifestream_feeds[$row->feed](unserialize($row->options), $row->feed_id);
     }
     
     function get_date()
     {
         return $this->date + LIFESTREAM_DATE_OFFSET*60*60;
     }
     
     function render()
     {
        /**
         * Returns an HTML-ready string.
         */
        return $this->feed->render($this);
     }
    
}
class LifeStream_Feed
{
    /**
     * Represents a feed object in the database.
     */
    
    public $options;
    
    // The ID must be a-z, 0-9, _, and - characters. It also must be unique.
    const ID            = 'generic';
    const NAME          = 'Generic';
    const AUTHOR        = 'David Cramer';
    const URL           = '';
    const DESCRIPTION   = '';
    // Can this feed be grouped?
    const CAN_GROUP     = true;
    // Labels used in rendering each event
    // params: feed url, feed name
    const LABEL_SINGLE  = 'Posted an item on <a href="%s">%s</a>.';
    // params: number of items, feed url, feed name
    const LABEL_PLURAL  = 'Posted %d items on <a href="%s">%s</a>.';
    // params: author url, author name, feed url, feed name
    const LABEL_SINGLE_USER = '<a href="%s">%s</a> posted an item on <a href="%s">%s</a>.';
    // params: author url, author name, number of items, feed url, feed name
    const LABEL_PLURAL_USER = '<a href="%s">%s</a> posted %d items on <a href="%s">%s</a>.';
    // The version is so you can manage data in the database for old versions.
    const VERSION       = 0;
    
    public static function construct_from_query_result($row)
    {
        /**
         * Instantiates this object through a feed database object.
         */

        global $lifestream_feeds;
        
        $class = $lifestream_feeds[$row->feed];
        if (!$class) return false;
        
        if (!empty($row->options)) $options = unserialize($row->options);
        else $options = null;
        
        $instance = new $class($options, $row->id, $row);
        if ($row->feed != $instance->get_constant('ID')) throw new Exception('This shouldnt be happening...');
        # $instance->options = unserialize($row['options']);
        return $instance;
    }
    
    // End of Static Methods

    function __construct($options=array(), $id=null, $row=null)
    {
        $this->options = $options;
        $this->id = $id;
        if ($row)
        {
            $this->owner = $row->owner;
            $this->owner_id = $row->owner_id;
            $this->_owner_id = $row->owner_id;
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

    function get_public_url()
    {
        return $this->get_constant('URL');
    }

    function get_public_name()
    {
        return $this->get_constant('NAME');
    }
    
    function get_label_single($key)
    {
        return $this->get_constant('LABEL_SINGLE');
    }
    
    function get_label_plural($key)
    {
        return $this->get_constant('LABEL_PLURAL');
    }
    
    function get_label_single_user($key)
    {
        return $this->get_constant('LABEL_SINGLE_USER');
    }
    
    function get_label_plural_user($key)
    {
        return $this->get_constant('LABEL_PLURAL_USER');
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
            'url' => array('Feed URL:', true, '', ''),
        );
    }
    
    function save()
    {
        global $wpdb;

        // If it has an ID it means it already exists.
        if ($this->id)
        {
            $result = $wpdb->query(sprintf("UPDATE `".$wpdb->prefix."lifestream_feeds` set `options` = '%s', `owner` = '%s', `owner_id` = %d WHERE `id` = %d", $wpdb->escape(serialize($this->options)), $wpdb->escape($this->owner), $this->owner_id, $this->id));
            if ($this->_owner_id && $this->_owner_id != $this->owner_id)
            {
                $wpdb->query(sprintf("UPDATE `".$wpdb->prefix."lifestream_event` SET `owner` = '%s', `owner_id` = %d WHERE `feed_id` = %d", $wpdb->escape($this->owner), $this->owner_id, $this->id));
                $wpdb->query(sprintf("UPDATE `".$wpdb->prefix."lifestream_event_group` SET `owner` = '%s', `owner_id` = %d WHERE `feed_id` = %d", $wpdb->escape($this->owner), $this->owner_id, $this->id));
            }
        }
        else
        {
            $result = $wpdb->query(sprintf("INSERT INTO `".$wpdb->prefix."lifestream_feeds` (`feed`, `options`, `timestamp`, `owner`, `owner_id`) VALUES ('%s', '%s', %d, '%s', %d)", $wpdb->escape($this->get_constant('ID')), $wpdb->escape(serialize($this->options)), time(), $wpdb->escape($this->owner), $this->owner_id));
            $this->id = $wpdb->insert_id;
        }
        return $result;
    }
    
    function delete()
    {
        global $wpdb;

        $wpdb->query(sprintf("DELETE FROM `".$wpdb->prefix."lifestream_feeds` WHERE `id` = %d", $this->id));
        $wpdb->query(sprintf("DELETE FROM `".$wpdb->prefix."lifestream_event` WHERE `feed_id` = %d", $this->id));
        $wpdb->query(sprintf("DELETE FROM `".$wpdb->prefix."lifestream_event_group` WHERE `feed_id` = %d", $this->id));
        
        $this->id = null;
    }
    
    function refresh()
    {
        global $wpdb;
        
        date_default_timezone_set('UTC');

        if (!$this->id) return false;

        $inserted = array();
        $total = 0;
        $items = $this->fetch();
        if (!$items) return false;
        foreach ($items as $item_key=>$item)
        {
            $link = array_key_pop($item, 'link');
            $date = array_key_pop($item, 'date');
            $key = array_key_pop($item, 'key');
            
            $affected = $wpdb->query(sprintf("INSERT IGNORE INTO `".$wpdb->prefix."lifestream_event` (`feed_id`, `link`, `data`, `timestamp`, `version`, `key`, `owner`, `owner_id`) VALUES (%d, '%s', '%s', %d, %d, '%s', '%s', %d)", $this->id, $wpdb->escape($link), $wpdb->escape(serialize($item)), $date, $this->get_constant('VERSION'), $wpdb->escape($key), $wpdb->escape($this->owner), $this->owner_id));
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
                        $results =& $wpdb->get_results(sprintf("SELECT `data`, `link` FROM `".$wpdb->prefix."lifestream_event` WHERE `feed_id` = %d AND `visible` = 1 AND DATE(FROM_UNIXTIME(`timestamp`)) = DATE(FROM_UNIXTIME(%d)) AND `key` = '%s'", $this->id, $date, $wpdb->escape($key)));
                        $events = array();
                        foreach ($results as &$result)
                        {
                            $result->data = unserialize($result->data);
                            $result->data['link'] = $result->link;
                            $events[] = $result->data;
                        }

                        // First let's see if the group already exists in the database
                        $group =& $wpdb->get_results(sprintf("SELECT `id` FROM `".$wpdb->prefix."lifestream_event_group` WHERE `feed_id` = %d AND DATE(FROM_UNIXTIME(`timestamp`)) = DATE(FROM_UNIXTIME(%d)) AND `key` = '%s' LIMIT 0, 1", $this->id, $date, $wpdb->escape($key)));
                        if (count($group) == 1)
                        {
                            $group =& $group[0];
                            $wpdb->query(sprintf("UPDATE `".$wpdb->prefix."lifestream_event_group` SET `data` = '%s', `total` = %d, `updated` = 1, `timestamp` = %d WHERE `id` = %d", $wpdb->escape(serialize($events)), count($events), $date, $group->id));
                        }
                        else
                        {
                            $wpdb->query(sprintf("INSERT INTO `".$wpdb->prefix."lifestream_event_group` (`feed_id`, `feed`, `data`, `total`, `timestamp`, `version`, `key`, `owner`, `owner_id`) VALUES(%d, '%s', '%s', %d, %d, %d, '%s', '%s', %d)", $this->id, $wpdb->escape($this->get_constant('ID')), $wpdb->escape(serialize($events)), count($events), $date, $this->get_constant('VERSION'), $wpdb->escape($key), $wpdb->escape($this->owner), $this->owner_id));
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
                    $wpdb->query(sprintf("INSERT INTO `".$wpdb->prefix."lifestream_event_group` (`feed_id`, `feed`, `event_id`, `data`, `timestamp`, `total`, `version`, `key`, `owner`, `owner_id`) VALUES(%d, '%s', %d, '%s', %d, 1, %d, '%s', '%s', %d)", $this->id, $wpdb->escape($this->get_constant('ID')), $item['id'], $wpdb->escape(serialize(array($item))), $date, $this->get_constant('VERSION'), $wpdb->escape($key), $wpdb->escape($this->owner), $this->owner_id));
                }
            }
        }
        return $total;
    }
    
    function get_events($limit=50, $offset=0)
    {
        global $wpdb;

        if (!$this->id) return false;
        
        if (!($limit > 0) || !($offset >= 0)) return false;

        $results =& $wpdb->get_results(sprintf("SELECT t1.*, t2.`feed`, t2.`options` FROM `".$wpdb->prefix."lifestream_event` as t1 JOIN `".$wpdb->prefix."lifestream_feeds` as t2 ON t1.`feed_id` = t2.`id` WHERE t1.`feed_id` = %d ORDER BY t1.`timestamp` DESC LIMIT %d, %d", $this->id, $offset, $limit));
        $events = array();
        foreach ($results as &$result)
        {
            $events[] = new LifeStream_Event($result);
        }
        return $events;
    }
    function fetch()
    {
        $urls = $this->get_url();
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
            $feed->set_feed_url($url);
            $feed->init();
            $feed->handle_content_type();

            $response =& $feed->get_items();
            if ($response)
            {
                foreach ($response as $row)
                {
                    $row =& $this->yield($row, $url);
                    if (!$row) continue;
                    if (!$row['key']) $row['key'] = $key;
                    if (!($row['date'] > 0)) $row['date'] = time();
                    if (count($row)) $items[] = $row;
                }
            }
        }
        return $items;
    }

    function yield($row)
    {
        // date and link are required
        // the rest of the data will be serialized into a `data` field
        // and is pulled out and used on the render($row) method
        $title = $row->get_title();
        if (!$title) return false;
        return array(
            'date'      =>  $row->get_date('U'),
            'link'      =>  html_entity_decode($row->get_link()),
            'title'     =>  html_entity_decode($title),
        );
    }
    
    function render_item($event, $item)
    {
        if (get_option('lifestream_use_ibox') == '1') $ibox = ' rel="ibox"';
        else $ibox = '';
        return sprintf('<a href="%s"'.$ibox.'>%s</a>', $item['link'], $item['title']);
    }
    
    function render_group_items($id, $output, $event)
    {
        return sprintf('<ul id="%s" style="display:none;"><li>%s</li></ul>', $id, implode('</li><li>', $output));
    }
    
    function get_render_output($event)
    {
        $label = '';
        $rows = array();

        foreach ($event->data as $row)
        {
            $rows[] = $this->render_item($event, $row);
        }
        if (count($rows) > 1)
        {
            if (get_option('lifestream_show_owners'))
            {
                $label = sprintf(__($this->get_label_plural_user($event->key), 'lifestream'), '#', $event->owner, $event->total, $this->get_public_url(), $this->get_public_name());
            }
            else
            {
                $label = sprintf(__($this->get_label_plural($event->key), 'lifestream'), $event->total, $this->get_public_url(), $this->get_public_name());
            }
        }
        else
        {
            if (get_option('lifestream_show_owners'))
            {
                $label = sprintf(__($this->get_label_single_user($event->key), 'lifestream'), '#', $event->owner, $this->get_public_url(), $this->get_public_name());
            }
            else
            {
                $label = sprintf(__($this->get_label_single($event->key), 'lifestream'), $this->get_public_url(), $this->get_public_name());
            }
        }
        return array($label, $rows);
    }
    
    function render($event)
    {
        list($label, $rows) = $this->get_render_output($event);
        if (count($rows) > 1)
        {
            return sprintf('%1$s <small class="lifestream_more">(<span onclick="lifestream_toggle(this, \'lwg_%2$d\', \'%3$s\', \'%4$s\');return false;">%3$s</span>)</small><div class="lifestream_events">%5$s</div>', $label, $event->id, __('Show Details', 'lifestream'), __('Hide Details', 'lifestream'), $this->render_group_items('lwg_'.$event->id, $rows, $event));
        }
        elseif ($this->options['show_label'])
        {
            return sprintf('%s<div class="lifestream_events">%s', $label, $rows[0]);
        }
        else
        {
            return $rows[0];
        }
    }
    
    function get_url()
    {
        return $this->options['url'];
    }
    
    function parse_urls($text)
    {
        if (get_option('lifestream_use_ibox') == '1') $ibox = ' rel="ibox"';
        else $ibox = '';
    
        # match http(s):// urls
        $text = preg_replace('@(https?://([-\w\.]+)+(:\d+)?(/([\w/_\.]*(\?\S+)?)?)?)@', '<a href="$1"'.$ibox.'>$1</a>', $text);
        # match www urls
        $text = preg_replace('@((?<!http://)www\.([-\w\.]+)+(:\d+)?(/([\w/_\.]*(\?\S+)?)?)?)@', '<a href="http://$1">$1</a>', $text);
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
    const LABEL_SINGLE  = 'Posted a photo on <a href="%s">%s</a>.';
    const LABEL_PLURAL  = 'Posted %d photos on <a href="%s">%s</a>.';
    const LABEL_SINGLE_USER = '<a href="%s">%s</a> posted a photo on <a href="%s">%s</a>.';
    const LABEL_PLURAL_USER = '<a href="%s">%s</a> posted %d photos on <a href="%s">%s</a>.';
    
    function render_item($row, $item)
    {
        if (get_option('lifestream_use_ibox') == '1') $ibox = ' rel="ibox"';
        else $ibox = '';

        return sprintf('<a href="%s" '.$ibox.'class="photo" title="%s""><img src="%s" width="50"/></a>', htmlspecialchars($item['link']), $item['title'], $item['thumbnail']);
    }
    
    
    function render_group_items($id, $output, $event)
    {
        return sprintf('<div id="%s" style="display:none;">%s</div>', $id, implode(' ', $output));
    }
}

class LifeStream_GenericFeed extends LifeStream_Feed {
    const LABEL_SINGLE  = 'Posted an item';
    const LABEL_PLURAL  = 'Posted %d items';
    
    function get_options()
    {        
        return array(
            'url' => array('Feed URL:', true, '', ''),
            'name' => array('Feed Name:', false, '', ''),
        );
    }

    function get_public_name()
    {
        return $this->options['name'];
    }

    function get_public_url()
    {
        return $this->options['url'];
    }
    
    function get_label_single($key)
    {
        if ($this->options['name']) return parent::LABEL_SINGLE;
        return $this->get_constant('LABEL_SINGLE');
    }
    
    function get_label_plural($key)
    {
        if ($this->options['name']) return parent::LABEL_PLURAL;
        return $this->get_constant('LABEL_PLURAL');
    }
    
    function get_label_single_user($key)
    {
        if ($this->options['name']) return parent::LABEL_SINGLE_USER;
        return $this->get_constant('LABEL_SINGLE_USER');
    }
    
    function get_label_plural_user($key)
    {
        if ($this->options['name']) return parent::LABEL_PLURAL_USER;
        return $this->get_constant('LABEL_PLURAL_USER');
    }
}
register_lifestream_feed('LifeStream_GenericFeed');

/**
 * Outputs the recent lifestream events.
 * @param {Array} $args An array of keyword args.
 */
function lifestream($args=array())
{
    global $lifestream_path;

    $_ = func_get_args();

    if (!is_array($_[0]))
    {
        // old style
        $_ = array(
            'number_of_results' => $_[0],
            'feed_ids'          => $_[1],
            'date_interval'     => $_[2],
            'user_ids'          => $_[4],
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
    
    // TODO: offset
    //$offset = get_option('lifestream_timezone');
    $hour_format = get_option('lifestream_hour_format');
    $day_format = get_option('lifestream_day_format');
    
    $events = call_user_func('lifestream_get_events', $_);
    
    include('pages/lifestream-table.inc.php');

    echo '<!-- Powered by iBegin LifeStream '.LIFESTREAM_VERSION.' -->';

    if (get_option('lifestream_show_credits') == '1')
    {
        echo '<p class="lifestream_credits"><small>'.lifestream_credits().'</small></p>';
    }
}

function lifestream_sidebar_widget($_=array())
{
    global $lifestream_path;
    
    $defaults = array(
        'number_of_results' => 10,
        'event_total_max'   => 1,
    );
    
    $_ = array_merge($defaults, $_);
    
    // TODO: offset
    //$offset = get_option('lifestream_timezone');
    $hour_format = get_option('lifestream_hour_format');
    $day_format = get_option('lifestream_day_format');
    
    $events = call_user_func('lifestream_get_events', $_);
    
    include('pages/lifestream-list.inc.php');
}

/**
 * Gets recent events from the lifestream.
 * @param {Array} $_ An array of keyword args.
 */
function lifestream_get_events($_=array())
{
    global $wpdb;
    
    $defaults = array(
        'number_of_results' => get_option('lifestream_number_of_items'),
        'offset'            => 0,
        'feed_ids'          => array(),
        'user_ids'          => array(),
        'date_interval'     => get_option('lifestream_date_interval'),
        'event_total_min'   => -1,
        'event_total_max'   => -1,
    );
    
    $_ = array_merge($defaults, $_);

    # If any arguments are invalid we bail out

    if (!((int)$_['number_of_results'] > 0)) return;
    if (!((int)$_['offset'] >= 0)) return;

    if (!preg_match('/[\d]+ (month|day|year|hour|second|microsecond|week|quarter)s?/', $_['date_interval'])) return;
    $_['date_interval'] = rtrim($_['date_interval'], 's');

    if (!is_array($_['feed_ids'])) return;
    if (!is_array($_['user_ids'])) return;
    
    $where = array('t1.`visible` = 1');
    if (count($_['feed_ids']))
    {
        foreach ($_['feed_ids'] as $key=>$value)
        {
            $_['feed_ids'][$key] = $wpdb->escape($value);
        }
        $where[] = 't1.`feed_id` IN ('.implode(', ', $_['feed_ids']).')';
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

    $sql = sprintf("SELECT t1.*, t2.`options` FROM `".$wpdb->prefix."lifestream_event_group` as `t1` INNER JOIN `".$wpdb->prefix."lifestream_feeds` as t2 ON t1.`feed_id` = t2.`id` WHERE t1.`timestamp` > UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL %s)) AND (%s) ORDER BY t1.`timestamp` DESC LIMIT %d, %d", $_['date_interval'], implode(') AND (', $where), $_['offset'], $_['number_of_results']);

    $results =& $wpdb->get_results($sql);
    $events = array();
    foreach ($results as &$result)
    {
        $events[] = new LifeStream_Event($result);
    }
    return $events;
}

function lifestream_options()
{
    global $lifestream_feeds, $lifestream__options, $wpdb, $userdata;

    $wpdb->show_errors();
    
    ksort($lifestream_feeds);
    
    lifestream_install();
    
    get_currentuserinfo();
    
    $date_format = sprintf('%s @ %s', get_option('lifestream_day_format'), get_option('lifestream_hour_format'));
    $basename = basename(LIFESTREAM_PLUGIN_FILE);
    
    $errors = array();
    $message = null;
   
    switch ($_GET['page'])
    {
        case 'lifestream-events.php':
            switch (strtolower($_REQUEST['op']))
            {
                case 'delete':
                    if ($_REQUEST['id']) break;
                    foreach ($_REQUEST['id'] as $id)
                    {
                        $result =& $wpdb->get_results(sprintf("SELECT `id`, `feed_id`, `timestamp`, `owner_id` FROM `".$wpdb->prefix."lifestream_event` WHERE `id` = %d", $id));
                        if (!$result)
                        {
                            $errors[] = __('The selected feed was not found.', 'lifestream');
                        }
                        elseif (!current_user_can('manage_options') && $result[0]->owner_id != $userdata->ID)
                        {
                            $errors[] = __('You do not have permission to do that.', 'lifestream');
                        }
                        else
                        {
                            $result =& $result[0];
                            $wpdb->query(sprintf("UPDATE `".$wpdb->prefix."lifestream_event` SET `visible` = 0 WHERE `id` = %d", $result->id));
                            $wpdb->query(sprintf("UPDATE `".$wpdb->prefix."lifestream_event_group` SET `visible` = 0 WHERE `event_id` = %d", $result->id));
                        
                            // Now we have to update the batch if it exists.
                            $group =& $wpdb->get_results(sprintf("SELECT `id` FROM `".$wpdb->prefix."lifestream_event_group` WHERE `event_id` IS NULL AND DATE(FROM_UNIXTIME(`timestamp`)) = DATE(FROM_UNIXTIME(%d)) AND `feed_id` = %d LIMIT 0, 1", $result->timestamp, $result->feed_id));
                            if (count($group) == 1)
                            {
                                $group =& $group[0];
                                $results =& $wpdb->get_results(sprintf("SELECT `data`, `link` FROM `".$wpdb->prefix."lifestream_event` WHERE `feed_id` = %d AND `visible` = 1 AND DATE(FROM_UNIXTIME(`timestamp`)) = DATE(FROM_UNIXTIME(%d))", $result->feed_id, $result->timestamp));
                                if (count($results))
                                {
                                    $events = array();
                                    foreach ($results as &$result)
                                    {
                                        $result->data = unserialize($result->data);
                                        $result->data['link'] = $result->link;
                                        $events[] = $result->data;
                                    }
                                    $wpdb->query(sprintf("UPDATE `".$wpdb->prefix."lifestream_event_group` SET `data` = '%s', `total` = %d, `updated` = 1 WHERE `id` = %d", $wpdb->escape(serialize($events)), count($events), $group->id));
                                }
                                else
                                {
                                    $wpdb->query(sprintf("DELETE FROM `".$wpdb->prefix."lifestream_event_group` WHERE `id` = %d", $group->id));
                                }
                            }
                            else
                            {
                                $wpdb->query(sprintf("DELETE FROM `".$wpdb->prefix."lifestream_event_group` WHERE `event_id` = %d LIMIT 0, 1", $result->id));
                            }
                        }
                        $message = __('The selected events were hidden.', 'lifestream');
                    }
                break;
            }
        break;
        case 'lifestream-settings.php':
            if ($_POST['save'])
            {
                foreach (array_keys($lifestream__options) as $value)
                {
                    update_option($value, $_POST[$value]);
                }
            }
        break;
        default:
            switch (strtolower($_REQUEST['op']))
            {
                case 'refreshall':
                    $events = lifestream_update($userdata->ID);
                    $message = __('All of your feeds have been refreshed.', 'lifestream');
                    break;
                case 'refresh':
                    if ($_REQUEST['id']) break;
                    foreach ($_REQUEST['id'] as $id)
                    {
                        $result =& $wpdb->get_results(sprintf("SELECT * FROM `".$wpdb->prefix."lifestream_feeds` WHERE `id` = %d LIMIT 0, 1", $id));
                        if (!$result)
                        {
                            $errors[] = __('The selected feed was not found.', 'lifestream');
                        }
                        elseif (!current_user_can('manage_options') && $result[0]->owner_id != $userdata->ID)
                        {
                            $errors[] = __('You do not have permission to do that.', 'lifestream');
                        }
                        else
                        {
                            $instance = LifeStream_Feed::construct_from_query_result($result[0]);
                            $instance->refresh();
                            $message = __('The selected feeds and their events have been refreshed.', 'lifestream');
                        }
                    }
                break;
                case 'delete':
                    if ($_REQUEST['id']) break;
                    foreach ($_REQUEST['id'] as $id)
                    {
                        $result =& $wpdb->get_results(sprintf("SELECT * FROM `".$wpdb->prefix."lifestream_feeds` WHERE `id` = %d LIMIT 0, 1", $id));
                        if (!$result)
                        {
                            $errors[] = __('The selected feed was not found.', 'lifestream');
                        }
                        elseif (!current_user_can('manage_options') && $result[0]->owner_id != $userdata->ID)
                        {
                            $errors[] = __('You do not have permission to do that.', 'lifestream');
                        }
                        else
                        {
                            $instance = LifeStream_Feed::construct_from_query_result($result[0]);
                            $instance->delete();
                            $message = __('The selected feeds and all related events has been removed.', 'lifestream');
                        }
                    }
                break;
                case 'edit':
                    $result =& $wpdb->get_results(sprintf("SELECT * FROM `".$wpdb->prefix."lifestream_feeds` WHERE `id` = %d LIMIT 0, 1", $_GET['id']));
                    if (!$result)
                    {
                        $errors[] = __('The selected feed was not found.', 'lifestream');
                    }
                    elseif (!current_user_can('manage_options') && $result[0]->owner_id != $userdata->ID)
                    {
                        $errors[] = __('You do not have permission to do that.', 'lifestream');
                    }
                    else
                    {
                        $instance = LifeStream_Feed::construct_from_query_result($result[0]);

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
                            if ($instance->get_constant('CAN_GROUP'))
                            {
                                $values['grouped'] = $_POST['grouped'];
                            }
                            if ($_POST['owner'] != $instance->owner_id && current_user_can('manage_options'))
                            {
                                $instance->owner_id = $_POST['owner'];
                                $usero = new WP_User($author->user_id);
                                $owner = $usero->data;
                                $instance->owner = $owner->user_nicename;
                            }
                            $values['show_label'] = $_POST['show_label'];
                            if (!count($errors))
                            {
                                $instance->options = $values;
                                $instance->save();
                            }
                        }
                    }
                break;
                default:
                    if ($_POST['save'])
                    {
                        $class_name = $lifestream_feeds[$_POST['feed_type']];
                        if (!$class_name) break;
                        $feed = new $class_name();
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
                        if ($feed->get_constant('CAN_GROUP'))
                        {
                            $values['grouped'] = $_POST['grouped'];
                        }
                        if (current_user_can('manage_options'))
                        {
                            $feed->owner_id = $_POST['owner'];
                            $usero = new WP_User($feed->owner_id);
                            $owner = $usero->data;
                            $feed->owner = $owner->user_nicename;
                        }
                        else
                        {
                            $feed->owner_id = $userdata->ID;
                            $feed->owner = $userdata->user_nicename;
                        }
                        $values['show_label'] = $_POST['show_label'];
                        if (!count($errors))
                        {
                            $feed->options = $values;
                            $result = $feed->save();
                            if ($result !== false)
                            {
                                unset($_POST);
                                $events = $feed->refresh();
                                if ($events !== false)
                                {
                                    $message = sprintf(__('Selected feed was added to your LifeStream with %d event(s).', 'lifestream'), $events);                                                                    
                                }
                            }
                        }
                    }
                break;
            }
        break;
    }
    
    ob_start();
    ?>
    <style type="text/css">
    table.options th, table.options td { padding: 3px 0; }
    table.options th { text-align: left; }
    table.options th { vertical-align: top; line-height: 30px; }
    table.options td .helptext { color: #999; margin-top: 3px; }
    </style>
    <br />
    <?php
    if (count($errors)) { ?>
    <div id="message" class="error"><p><strong><?php _e('Please correct the following errors:', 'lifestream') ?></strong></p><ul>
        <?php foreach ($errors as $error) { ?>
            <li><?php echo htmlspecialchars($error); ?></li>
        <?php } ?>
    </ul></div>
    <?php } elseif ($message) { ?>
    <div id="message" class="updated fade"><p><strong><?php echo $message; ?></strong></p></div>
    <?php } ?>
    <div class="wrap">
        <?php
        switch ($_GET['page'])
        {
            case 'lifestream-forums.php':
                include('pages/forums.inc.php');
            break;
            case 'lifestream-settings.php':
                include('pages/settings.inc.php');
            break;
            case 'lifestream-events.php':
                $page = $_GET['paged'] ? $_GET['paged'] : 1;
                $start = ($page-1)*LIFESTREAM_EVENTS_PER_PAGE;
                $end = $page*LIFESTREAM_EVENTS_PER_PAGE;
                
                if (!current_user_can('manage_options'))
                {
                    $results =& $wpdb->get_results(sprintf("SELECT COUNT(*) as `count` FROM `".$wpdb->prefix."lifestream_event` WHERE `owner_id` = %d", $userdata->ID));
                    $number_of_pages = ceil($results[0]->count/LIFESTREAM_EVENTS_PER_PAGE);
                    $results =& $wpdb->get_results(sprintf("SELECT t1.*, t2.`feed`, t2.`options` FROM `".$wpdb->prefix."lifestream_event` as t1 JOIN `".$wpdb->prefix."lifestream_feeds` as t2 ON t1.`feed_id` = t2.`id` WHERE t1.`owner_id` = %d ORDER BY t1.`timestamp` DESC LIMIT %d, %d", $userdata->ID, $start, $end));
                }
                else
                {
                    $results =& $wpdb->get_results("SELECT COUNT(*) as `count` FROM `".$wpdb->prefix."lifestream_event`");
                    $number_of_pages = ceil($results[0]->count/LIFESTREAM_EVENTS_PER_PAGE);
                    $results =& $wpdb->get_results(sprintf("SELECT t1.*, t2.`feed`, t2.`options` FROM `".$wpdb->prefix."lifestream_event` as t1 JOIN `".$wpdb->prefix."lifestream_feeds` as t2 ON t1.`feed_id` = t2.`id` ORDER BY t1.`timestamp` DESC LIMIT %d, %d", $start, $end));
                }
                include('pages/events.inc.php');
            break;
            default:
                switch ($_GET['op'])
                {
                    case 'edit':
                        include('pages/edit-feed.inc.php');
                    break;
                    default:
                        $page = $_GET['paged'] ? $_GET['paged'] : 1;
                        $start = ($page-1)*LIFESTREAM_FEEDS_PER_PAGE;
                        $end = $page*LIFESTREAM_FEEDS_PER_PAGE;
                        if (!current_user_can('manage_options'))
                        {
                            $results =& $wpdb->get_results(sprintf("SELECT COUNT(*) as `count` FROM `".$wpdb->prefix."lifestream_feeds` WHERE `owner_id` = %d", $userdata->ID));
                            $number_of_pages = ceil($results[0]->count/LIFESTREAM_FEEDS_PER_PAGE);
                            $results =& $wpdb->get_results(sprintf("SELECT t1.*, (SELECT COUNT(1) FROM `".$wpdb->prefix."lifestream_event` WHERE `feed_id` = t1.`id`) as `events` FROM `".$wpdb->prefix."lifestream_feeds` as t1 WHERE t1.`owner_id` = %d ORDER BY `id` LIMIT %d, %d", $userdata->ID, $start, $end));
                        }
                        else
                        {
                            $results =& $wpdb->get_results("SELECT COUNT(*) as `count` FROM `".$wpdb->prefix."lifestream_feeds`");
                            $number_of_pages = ceil($results[0]->count/LIFESTREAM_FEEDS_PER_PAGE);
                            $results =& $wpdb->get_results(sprintf("SELECT t1.*, (SELECT COUNT(1) FROM `".$wpdb->prefix."lifestream_event` WHERE `feed_id` = t1.`id`) as `events` FROM `".$wpdb->prefix."lifestream_feeds` as t1 ORDER BY `id` LIMIT %d, %d", $start, $end));
                        }
                        if ($results !== false)
                        {
                            include('pages/feeds.inc.php');
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

function lifestream_options_menu()
{
    if (function_exists('add_menu_page'))
    {
        $basename = basename(LIFESTREAM_PLUGIN_FILE);

        add_menu_page('LifeStream', 'LifeStream', 'edit_posts', $basename, 'lifestream_options');
        add_submenu_page($basename, __('LifeStream Feeds', 'lifestream'), __('Feeds', 'lifestream'), 'edit_posts', $basename, 'lifestream_options');
        add_submenu_page($basename, __('LifeStream Events', 'lifestream'), __('Events', 'lifestream'), 'edit_posts', 'lifestream-events.php', 'lifestream_options');
        add_submenu_page($basename, __('LifeStream Settings', 'lifestream'), __('Settings', 'lifestream'), 'manage_options', 'lifestream-settings.php', 'lifestream_options');
        add_submenu_page($basename, __('LifeStream Support Forums', 'lifestream'), __('Support Forums', 'lifestream'), 'manage_options', 'lifestream-forums.php', 'lifestream_options');
        
        //add_options_page('LifeStream Options', 'LifeStream', 8, basename(LIFESTREAM_PLUGIN_FILE), 'lifestream_options');
    }
}

function lifestream_header()
{
    global $lifestream_path;
    
    echo '<link rel="stylesheet" type="text/css" media="screen" href="'.$lifestream_path.'/lifestream.css"/>';
    echo '<script type="text/javascript" src="'.$lifestream_path.'/lifestream.js"></script>';
}

function widget_lifestream_config()
{
    ?>
    <p><label for="lifestream_title">Title: <input class="widefat" id="lifestream_title" name="lifestream_title" value="" type="text"></label></p>
    <p>
        <label for="lifestream_show_grouped"><input class="checkbox" id="lifestream_show_grouped" name="lifestream_show_grouped" type="checkbox"> Show events ungrouped.</label>
    </p>
    <?php
}

function widget_lifestream($args)
{
    extract($args);
?>
    <?php echo $before_widget; ?>
        <?php echo $before_title
            . 'LifeStream'
            . $after_title; ?>
        <?php lifestream_sidebar_widget(array('number_of_items'=>10)); ?>
    <?php echo $after_widget; ?>
<?php
}

include('feeds.inc.php');

/**
 * Attempts to update all feeds
 */
function lifestream_update()
{
    global $wpdb;
    update_option('lifestream__last_update', time());
    $events = 0;
    $results =& $wpdb->get_results("SELECT * FROM `".$wpdb->prefix."lifestream_feeds`");
    foreach ($results as $result)
    {
        $instance = LifeStream_Feed::construct_from_query_result($result);
        $events += $instance->refresh();
    }
    return $events;
}

// digest code based on Twitter Tools by Alex King
function lifestream_do_digest()
{
    global $wpdb, $lifestream_path;
    
    $hour_format = get_option('lifestream_hour_format');
    $day_format = get_option('lifestream_day_format');
    
    // thread locking
    if (get_option('lifestream__in_digest') == '1') return;
    update_option('lifestream__in_digest', '1');

    $now = time();
    $yesterday = strtotime('-1 day', $now);
    $last_post = get_option('lifestream__last_digest');
    
    if ($last_post && date('Y-m-d 00:00:00', $last_post) != date('Y-m-d 00:00:00', $yesterday))
    {
        $days = ceil((strtotime(date('Y-m-d 00:00:00', $yesterday)) - $last_post) / (3600 * 24));
    }
    else
    {
        $days = 1;
    }
    
    for ($i=0; $i<$days; $i++)
    {
        // make sure the post doesn't exist
        $digest_day = strtotime('-'.($days - $i).' days', $now);
        $digest_day = strtotime(date('Y-m-d 23:59:59', $digest_day));

        $results = $wpdb->get_results(sprintf("SELECT `post_id` FROM `".$wpdb->prefix."postmeta` WHERE `meta_key = '_lifestream_digest_date' AND `meta_value` = %d LIMIT 1", $digest_day));
        if ($results) continue;

        $sql = sprintf("SELECT t1.*, t2.`options` FROM `".$wpdb->prefix."lifestream_event_group` as `t1` INNER JOIN `".$wpdb->prefix."lifestream_feeds` as t2 ON t1.`feed_id` = t2.`id` WHERE t1.`timestamp` > '%s' AND t1.`timestamp` < '%s' ORDER BY t1.`timestamp` ASC", strtotime(date('Y-m-d 00:00:00', $digest_day)), strtotime(date('Y-m-d 23:59:59', $digest_day)));
        
        $results =& $wpdb->get_results($sql);
        $events = array();
        foreach ($results as &$result)
        {
            $events[] = new LifeStream_Event($result);
        }

        if (count($events))
        {
            ob_start();
            include('pages/lifestream-table.inc.php');
            $content = sprintf(get_option('lifestream_digest_body'), ob_get_clean(), date(get_option('lifestream_day_format'), $digest_day), count($events));

            $data = array(
                'post_content' => $wpdb->escape($content),
                'post_title' => $wpdb->escape(sprintf(get_option('lifestream_digest_title'), date('Y-m-d', $digest_day))),
                'post_date' => date('Y-m-d 23:59:59', $digest_day),
                'post_category' => array(get_option('lifestream_digest_category')),
                'post_status' => 'publish',
                'post_author' => $wpdb->escape(get_option('lifestream_digest_author')),
            );
            $post_id = wp_insert_post($data);
            add_post_meta($post_id, '_lifestream_digest_date', $digest_day, true);
        }
    }
    update_option('lifestream__last_digest', $now);
    update_option('lifestream__in_digest', '0');
}

function lifestream_init()
{
    global $wpdb;
    
    if (isset($_GET['activate']) || isset($_GET['activate-multi']))
    {
        lifestream_activate();
    }
    
    $offset = get_option('lifestream_timezone');
    define(LIFESTREAM_DATE_OFFSET, $offset);
    
    // wp cron is too limited, make our own
    $time = get_option('lifestream__last_update');
    if (!$time || ($time + (get_option('lifestream_update_interval') * 60) < time()))
    {
        add_action('shutdown', 'lifestream_update');
    }
    if (get_option('lifestream_daily_digest') == '1')
    {
        $time = get_option('lifestream__last_digest');
        if ($time < strtotime(date('Y-m-d 00:00:00', time())))
        {
            add_action('shutdown', 'lifestream_do_digest');
        }
    }
    load_plugin_textdomain('lifestream', 'wp-content/plugins/lifestream/locales');
    
    if (function_exists('register_sidebar_widget'))
    {
        //         if ( !$id ) {
        //             wp_register_sidebar_widget( 'rss-1', $name, 'wp_widget_rss', $widget_ops, array( 'number' => -1 ) );
        //             wp_register_widget_control( 'rss-1', $name, 'wp_widget_rss_control', $control_ops, array( 'number' => -1 ) );
        // }
        // wp_register_widget_control('lifestream-1', 'LifeStream', 'widget_lifestream', array('id_base'=>'lifestream')) {
        //         register_sidebar_widget('LifeStream', 'widget_lifestream');
    }
    
    if (is_admin() && str_startswith($_GET['page'], 'lifestream'))
    {
        wp_enqueue_script('jquery');
        wp_enqueue_script('admin-forms');
    }
}

add_action('admin_menu', 'lifestream_options_menu');
add_action('wp_head', 'lifestream_header');
add_filter('the_content', 'lifestream_embed_callback');
add_action('init', 'lifestream_init');

?>