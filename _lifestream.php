<?php
define(LIFESTREAM_VERSION, 0.52);
define(LIFESTREAM_PLUGIN_FILE, dirname(__FILE__) . '/lifestream.php');
define(LIFESTREAM_TABLE_PREFIX, $wpdb->prefix.'lifestream_');

if (!class_exists('SimplePie'))
{
    require_once('simplepie.inc');
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
    return preg_replace_callback("|<lifestream(?:\s+([a-z_]+)=[\"']?([a-z0-9_-\s]+)[\"']?)*\s*/>|i", 'lifestream_embed_handler', $content);
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
    lifestream($args['number_of_items'], $args['feed_ids'] ? explode(',', $args['feed_ids']) : null, $args['date_interval'], $args['output']);
    return ob_get_clean();
}

/**
 * Adds/updates the options on plug-in activation.
 */
function lifestream_install($allow_database_install=true)
{
    if (!get_option('lifestream_day_format')) update_option('lifestream_day_format', 'F jS');
    if (!get_option('lifestream_hour_format')) update_option('lifestream_hour_format', 'g:ia');   
    if (!get_option('lifestream_timezone')) update_option('lifestream_timezone', date('O')/100);
    if (!get_option('lifestream_number_of_items')) update_option('lifestream_number_of_items', 50);
    if (!get_option('lifestream_date_interval')) update_option('lifestream_date_interval', '1 month');
    if (!get_option('lifestream_digest_title')) update_option('lifestream_digest_title', 'Daily Digest for %s');
    if (!get_option('lifestream_digest_body')) update_option('lifestream_digest_body', '%1$s');
    if (!get_option('lifestream_digest_category')) update_option('lifestream_digest_category', '1');
    if (!get_option('lifestream_digest_author')) update_option('lifestream_digest_author', '1');
    if (!get_option('lifestream_update_interval')) update_option('lifestream_update_interval', '15');
    if (!get_option('lifestream__in_digest')) update_option('lifestream__in_digest', '0');
    
    if ($allow_database_install && get_option('lifestream__version') != LIFESTREAM_VERSION) lifestream_install_database();
}

function lifestream_activate()
{
    global $wpdb;
    // Add a feed for this blog
    
    lifestream_install();

    // Get rid of old cron job
    wp_clear_scheduled_hook('LifeStream_Hourly');

    $results =& $wpdb->get_results("SELECT COUNT(*) as `count` FROM `".LIFESTREAM_TABLE_PREFIX."feeds`");
    if (!$results[0]->count)
    {
        $rss_url = trailingslashit(get_settings('siteurl')) . 'wp-rss2.php';
        $options = array('url' => $rss_url);

        $feed = new LifeStream_BlogFeed($options);
        $feed->save();
        $feed->refresh();
    }
    else
    {
        lifestream_update();
    }
}

/**
 * Initializes the database if it's not already present.
 */
function lifestream_install_database()
{
    global $wpdb;
    
    $version = get_option('lifestream__version');
    
    $wpdb->query("CREATE TABLE IF NOT EXISTS `".LIFESTREAM_TABLE_PREFIX."event` (
      `id` int(11) NOT NULL auto_increment,
      `feed_id` int(11) NOT NULL,
      `link` varchar(200) NOT NULL,
      `data` blob NOT NULL,
      `visible` tinyint(1) default 1 NOT NULL,
      `timestamp` int(11) NOT NULL,
      `version` int(11) default 0 NOT NULL,
      `key` char(16) NOT NULL,
      PRIMARY KEY (`id`),
      UNIQUE `feed_id` (`feed_id`, `key`, `link`)
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8;");

    $wpdb->query("CREATE TABLE IF NOT EXISTS `".LIFESTREAM_TABLE_PREFIX."event_group` (
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
      PRIMARY KEY (`id`),
      INDEX `feed_id` (`feed_id`, `key`, `timestamp`)
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8;");
    
    $wpdb->query("CREATE TABLE IF NOT EXISTS `".LIFESTREAM_TABLE_PREFIX."feeds` (
      `id` int(11) NOT NULL auto_increment,
      `feed` varchar(32) NOT NULL,
      `options` text default NULL,
      `timestamp` int(11) NOT NULL,
      PRIMARY KEY (`id`)
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8;");
    
    if ($version < 0.5)
    {
        // Upgrade them to version 0.5
        $wpdb->query("ALTER IGNORE TABLE `".LIFESTREAM_TABLE_PREFIX."event_group` ADD `version` INT(11) NOT NULL DEFAULT '0' AFTER `timestamp`, ADD `key` CHAR( 16 ) NOT NULL AFTER `version`;");
        $wpdb->query("ALTER IGNORE TABLE `".LIFESTREAM_TABLE_PREFIX."event` ADD `version` INT(11) NOT NULL DEFAULT '0' AFTER `timestamp`, ADD `key` CHAR( 16 ) NOT NULL AFTER `version`;");
        $wpdb->query("ALTER IGNORE TABLE `".LIFESTREAM_TABLE_PREFIX."event_group` DROP INDEX `feed_id`, ADD INDEX `feed_id` (`feed_id` , `key` , `timestamp` );");
    }
    if ($version < 0.52)
    {
        $wpdb->query("ALTER IGNORE TABLE `".LIFESTREAM_TABLE_PREFIX."event` DROP INDEX `feed_id`, ADD UNIQUE `feed_id` (`feed_id` , `key` , `link` );");
    }
    update_option('lifestream__version', LIFESTREAM_VERSION);
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
        if ($this->total > 1) return $this->feed->render_group($this);
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
    // params: number of items, feed name, url
    const LABEL_PLURAL  = 'Posted %d items on <a href="%s">%s</a>.';
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

        $instance = new $class($options, $row->id);
        if ($row->feed != $instance->get_constant('ID')) throw new Exception('This shouldnt be happening...');
        # $instance->options = unserialize($row['options']);
        return $instance;
    }
    
    // End of Static Methods

    function __construct($options=array(), $id=null)
    {
        $this->options = $options;
        $this->id = $id;
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
            $result = $wpdb->query(sprintf("UPDATE `".LIFESTREAM_TABLE_PREFIX."feeds` set `options` = '%s' WHERE `id` = %d", $wpdb->escape(serialize($this->options)), $this->id));
        }
        else
        {
            $result = $wpdb->query(sprintf("INSERT INTO `".LIFESTREAM_TABLE_PREFIX."feeds` (`feed`, `options`, `timestamp`) VALUES ('%s', '%s', %d)", $wpdb->escape($this->get_constant('ID')), $wpdb->escape(serialize($this->options)), time()));
            $this->id = $wpdb->insert_id;
        }
        return $result;
    }
    
    function delete()
    {
        global $wpdb;

        $wpdb->query(sprintf("DELETE FROM `".LIFESTREAM_TABLE_PREFIX."feeds` WHERE `id` = %d", $this->id));
        $wpdb->query(sprintf("DELETE FROM `".LIFESTREAM_TABLE_PREFIX."event` WHERE `feed_id` = %d", $this->id));
        $wpdb->query(sprintf("DELETE FROM `".LIFESTREAM_TABLE_PREFIX."event_group` WHERE `feed_id` = %d", $this->id));
        
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
        foreach ($items as $item)
        {
            $link = array_key_pop($item, 'link');
            $date = array_key_pop($item, 'date');
            $key = array_key_pop($item, 'key');
            if (!$date) $date = time();
            
            $affected =& $wpdb->query(sprintf("INSERT IGNORE INTO `".LIFESTREAM_TABLE_PREFIX."event` (`feed_id`, `link`, `data`, `timestamp`, `version`, `key`) VALUES (%d, '%s', '%s', %d, %d, '%s')", $this->id, $wpdb->escape($link), $wpdb->escape(serialize($item)), $date, $this->get_constant('VERSION'), $wpdb->escape($key)));
            if ($affected)
            {
                $fdate = date('m d Y', $date);
                if (!in_array($key, $inserted)) $inserted[$key] = array();
                $total += 1;
                if (in_array($fdate, $inserted[$key])) continue;

                $inserted[$key][$fdate] = $date;
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
                        $results =& $wpdb->get_results(sprintf("SELECT `data`, `link` FROM `".LIFESTREAM_TABLE_PREFIX."event` WHERE `feed_id` = %d AND `visible` = 1 AND DATE(FROM_UNIXTIME(`timestamp`)) = DATE(FROM_UNIXTIME(%d)) AND `key` = '%s'", $this->id, $date, $wpdb->escape($key)));
                        $events = array();
                        foreach ($results as &$result)
                        {
                            $result->data = unserialize($result->data);
                            $result->data['link'] = $result->link;
                            $events[] = $result->data;
                        }

                        // First let's see if the group already exists in the database
                        $group =& $wpdb->get_results(sprintf("SELECT `id` FROM `".LIFESTREAM_TABLE_PREFIX."event_group` WHERE `feed_id` = %d AND DATE(FROM_UNIXTIME(`timestamp`)) = DATE(FROM_UNIXTIME(%d)) AND `key` = '%s' LIMIT 0, 1", $this->id, $date, $wpdb->escape($key)));
                        if (count($group) == 1)
                        {
                            $group =& $group[0];
                            $wpdb->query(sprintf("UPDATE `".LIFESTREAM_TABLE_PREFIX."event_group` SET `data` = '%s', `total` = %d, `updated` = 1, `timestamp` = %d WHERE `id` = %d", $wpdb->escape(serialize($events)), count($events), $date, $group->id));
                        }
                        else
                        {
                            $wpdb->query(sprintf("INSERT INTO `".LIFESTREAM_TABLE_PREFIX."event_group` (`feed_id`, `feed`, `data`, `total`, `timestamp`, `version`, `key`) VALUES(%d, '%s', '%s', %d, %d, %d, '%s')", $this->id, $wpdb->escape($this->get_constant('ID')), $wpdb->escape(serialize($events)), count($events), $date, $this->get_constant('VERSION'), $key));
                        }
                    }
                }
            }
            else
            {
                foreach ($inserted as &$item)
                {
                    $date = array_key_pop($item, 'date');

                    $wpdb->query(sprintf("INSERT INTO `".LIFESTREAM_TABLE_PREFIX."event_group` (`feed_id`, `feed`, `data`, `timestamp`, `total`, `version`, `key`) VALUES(%d, '%s', '%s', %d, 1)", $this->id, $wpdb->escape($this->get_constant('ID')), $wpdb->escape(serialize(array($item))), $date, $this->get_constant('VERSION'), $item['key']));
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

        $results =& $wpdb->get_results(sprintf("SELECT t1.*, t2.`feed`, t2.`options` FROM `".LIFESTREAM_TABLE_PREFIX."event` as t1 JOIN `".LIFESTREAM_TABLE_PREFIX."feeds` as t2 ON t1.`feed_id` = t2.`id` WHERE t1.`feed_id` = %d ORDER BY t1.`timestamp` DESC LIMIT %d, %d", $this->id, $offset, $limit));
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
                $url = $url_data[0];
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
                    $result =& $this->yield($row, $url);
                    $result['key'] = $key;
                    if (count($result)) $items[] = $result;
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
        return array(
            'date'      =>  $row->get_date('U'),
            'link'      =>  html_entity_decode($row->get_link()),
            'title'     =>  html_entity_decode($row->get_title()),
        );
    }
    
    function render_item($row, $item)
    {
        return sprintf('<a href="%s">%s</a>', $item['link'], $item['title']);
    }
    
    function render($row)
    {
        // $row->date, $row->link, $row->data['field']
        if (!$this->options['show_label'])
        {
            return $this->render_item($row, $row->data[0]);
        }
        return sprintf(__($this->get_label_single($row->key), 'lifestream'), $this->get_public_url(), $this->get_public_name()) . '<br />' . $this->render_item($row, $row->data[0]);
    }
    
    function render_group($row)
    {
        $output = array();
        foreach ($row->data as $chunk)
        {
            $output[] = $this->render_item($row, $chunk);
        }
        $id = sprintf('lf_%s', round(microtime(true)*rand(10000,1000000)));
        return sprintf(__($this->get_label_plural($row->key), 'lifestream'), $row->total, $this->get_public_url(), $this->get_public_name()) . ' <small class="lifestream_more">(<a href="#" onclick="lifestream_toggle(this, \'' . $id . '\', \'' . __('Show Details', 'lifestream') . '\', \''. __('Hide Details', 'lifestream') .'\');return false;">' . __('Show Details', 'lifestream') . '</a>)</small><br /><ul id="' . $id . '" style="display:none;"><li>' . implode('</li><li>', $output) . '</li></ul>';
    }
    
    function get_url()
    {
        return $this->options['url'];
    }
    
    function parse_urls($text)
    {
        # match http(s):// urls
        $text = preg_replace('@(https?://([-\w\.]+)+(:\d+)?(/([\w/_\.]*(\?\S+)?)?)?)@', '<a href="$1">$1</a>', $text);
        # match www urls
        $text = preg_replace('@((?<!http://)www\.([-\w\.]+)+(:\d+)?(/([\w/_\.]*(\?\S+)?)?)?)@', '<a href="http://$1">$1</a>', $text);
        # match email@address
        $text = preg_replace('/\b([A-Z0-9._%+-]+@(?:[A-Z0-9-]+\.)+[A-Z]{2,4})\b/i', '<a href="mailto:$1">$1</a>', $text);
        return $text;
    }
}
class LifeStream_GenericFeed extends LifeStream_Feed {
    const LABEL_SINGLE  = 'Posted an item';
    const LABEL_PLURAL  = 'Posted %d items';
}
register_lifestream_feed('LifeStream_GenericFeed');

/**
 * Outputs the recent lifestream events.
 * @param {Int} $number_of_results The maximum number of results.
 * @param {Array} $feed_ids An array of feed IDs to include.
 * @param {String} $date_interval The cutoff date for events, using MySQL's date interval expressions.
 * @param {String} $output The lifestream output template name.
 */
function lifestream($number_of_results=null, $feed_ids=null, $date_interval=null, $output=null)
{
    global $lifestream_path;
    
    if ($output == null) $output = 'table';

    if (!in_array($output, array('table', 'list'))) return;
    
    // TODO: offset
    //$offset = get_option('lifestream_timezone');
    $hour_format = get_option('lifestream_hour_format');
    $day_format = get_option('lifestream_day_format');
    
    $args = func_get_args();
    $events = call_user_func_array('lifestream_get_events', $args);
    
    include(sprintf('pages/lifestream-%s.inc.php', $output));
}

/**
 * Gets recent events from the lifestream.
 * @param {Int} $number_of_results The maximum number of results.
 * @param {Array} $feed_ids An array of feed IDs to include.
 * @param {String} $date_interval The cutoff date for events, using MySQL's date interval expressions.
 * @return {Array} Events
 */
function lifestream_get_events($number_of_results=null, $feed_ids=null, $date_interval=null)
{
    global $wpdb;
    
    if ($number_of_results == null) $number_of_results = get_option('lifestream_number_of_items');
    if ($feed_ids == null) $feed_ids = array();
    if ($date_interval == null) $date_interval = get_option('lifestream_date_interval');

    # If any arguments are invalid we bail out

    if (!((int)$number_of_results > 0)) return;

    if (!preg_match('/[\d]+ (month|day|year|hour|second|microsecond|week|quarter)s?/', $date_interval)) return;
    $date_interval = rtrim($date_interval, 's');

    if (!is_array($feed_ids)) return;
    
    $where = array('t1.`visible` = 1');
    if (count($feed_ids))
    {
        foreach ($feed_ids as $key=>$value)
        {
            $feed_ids[$key] = $wpdb->escape($value);
        }
        $where[] = 't1.`feed_id` IN ('.implode(', ', $feed_ids).')';
    }

    $sql = sprintf("SELECT t1.*, t2.`options` FROM `".LIFESTREAM_TABLE_PREFIX."event_group` as `t1` INNER JOIN `".LIFESTREAM_TABLE_PREFIX."feeds` as t2 ON t1.`feed_id` = t2.`id` WHERE t1.`timestamp` > UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL %s)) AND (%s) ORDER BY t1.`timestamp` DESC LIMIT 0, %d", $date_interval, implode(') AND (', $where), $number_of_results);

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
    global $lifestream_feeds, $wpdb;

    $wpdb->show_errors();
    
    ksort($lifestream_feeds);
    
    lifestream_install();
    
    $date_format = sprintf('%s @ %s', get_option('lifestream_day_format'), get_option('lifestream_hour_format'));
    $basename = basename(LIFESTREAM_PLUGIN_FILE);
    
    $errors = array();
    $message = null;
   
    switch ($_GET['page'])
    {
        case 'lifestream-events.php':
            switch ($_GET['op'])
            {
                case 'delete':
                    $result =& $wpdb->get_results(sprintf("SELECT `id`, `feed_id`, `timestamp` FROM `".LIFESTREAM_TABLE_PREFIX."event` WHERE `id` = %d", $_GET['id']));
                    if (count($result) == 1)
                    {
                        $result =& $result[0];
                        $wpdb->query(sprintf("UPDATE `".LIFESTREAM_TABLE_PREFIX."event` SET `visible` = 0 WHERE `id` = %d", $result->id));
                        $wpdb->query(sprintf("UPDATE `".LIFESTREAM_TABLE_PREFIX."event_group` SET `visible` = 0 WHERE `event_id` = %d", $result->id));
                        
                        // Now we have to update the batch if it exists.
                        $group =& $wpdb->get_results(sprintf("SELECT `id` FROM `".LIFESTREAM_TABLE_PREFIX."event_group` WHERE `event_id` IS NULL AND DATE(FROM_UNIXTIME(`timestamp`)) = DATE(FROM_UNIXTIME(%d)) LIMIT 0, 1", $result->timestamp));
                        if (count($group) == 1)
                        {
                            $group =& $group[0];
                            $results =& $wpdb->get_results(sprintf("SELECT `data`, `link` FROM `".LIFESTREAM_TABLE_PREFIX."event` WHERE `feed_id` = %d AND `visible` = 1 AND DATE(FROM_UNIXTIME(`timestamp`)) = DATE(FROM_UNIXTIME(%d))", $result->feed_id, $result->timestamp));
                            if (count($results))
                            {
                                $events = array();
                                foreach ($results as &$result)
                                {
                                    $result->data = unserialize($result->data);
                                    $result->data['link'] = $result->link;
                                    $events[] = $result->data;
                                }
                                $wpdb->query(sprintf("UPDATE `".LIFESTREAM_TABLE_PREFIX."event_group` SET `data` = '%s', `total` = %d, `updated` = 1 WHERE `id` = %d", $wpdb->escape(serialize($events)), count($events), $group->id));
                            }
                            else
                            {
                                $wpdb->query(sprintf("DELETE FROM `".LIFESTREAM_TABLE_PREFIX."event_group` WHERE `id` = %d", $group->id));
                            }
                        }
                        $message = __('The selected event was hidden.', 'lifestream');
                    }
                    else
                    {
                        $errors[] = __('The selected event was not found.', 'lifestream');
                    }
                break;
            }
        break;
        case 'lifestream-feeds.php':
            switch ($_GET['op'])
            {
                case 'refreshall':
                    $events = lifestream_update();
                    $message = __('All of your feeds have been refreshed.', 'lifestream');
                    break;
                case 'refresh':
                    $result =& $wpdb->get_results(sprintf("SELECT * FROM `".LIFESTREAM_TABLE_PREFIX."feeds` WHERE `id` = %d LIMIT 0, 1", $_GET['id']));
                    if (count($result) == 1)
                    {
                        $instance = LifeStream_Feed::construct_from_query_result($result[0]);
                        $instance->refresh();
                        $message = __('The selected feed\'s events has been refreshed.', 'lifestream');
                    }
                    else
                    {
                        $errors[] = __('The selected feed was not found.', 'lifestream');
                    }
                break;
                case 'delete':
                    $result =& $wpdb->get_results(sprintf("SELECT * FROM `".LIFESTREAM_TABLE_PREFIX."feeds` WHERE `id` = %d LIMIT 0, 1", $_GET['id']));
                    if (count($result) == 1)
                    {
                        $instance = LifeStream_Feed::construct_from_query_result($result[0]);
                        $instance->delete();
                        $message = __('The selected feed and all events has been removed.', 'lifestream');
                    }
                    else
                    {
                        $errors[] = __('The selected feed was not found.', 'lifestream');
                    }
                break;
                case 'edit':
                    $result =& $wpdb->get_results(sprintf("SELECT * FROM `".LIFESTREAM_TABLE_PREFIX."feeds` WHERE `id` = %d LIMIT 0, 1", $_GET['id']));
                    if (count($result) == 1)
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
                                    $values[$option] = $_POST[$option];
                                }
                            }
                            if ($instance->get_constant('CAN_GROUP'))
                            {
                                $values['grouped'] = $_POST['grouped'];
                            }
                            $values['show_label'] = $_POST['show_label'];
                            if (!count($errors))
                            {
                                $instance->options = $values;
                                $instance->save();
                            }
                        }
                    }
                    else
                    {
                        $errors[] = __('The selected feed was not found.', 'lifestream');
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
                                $values[$option] = $_POST[$option];
                            }
                        }
                        if ($feed->get_constant('CAN_GROUP'))
                        {
                            $values['grouped'] = $_POST['grouped'];
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
        default:
            if ($_POST['save'])
            {
                $options = array('lifestream_timezone', 'lifestream_day_format', 'lifestream_hour_format', 'lifestream_update_interval', 'lifestream_daily_digest', 'lifestream_digest_title', 'lifestream_digest_body', 'lifestream_digest_author', 'lifestream_digest_category', 'lifestream_number_of_items', 'lifestream_date_interval');
                foreach ($options as $value)
                {
                    update_option($value, $_POST[$value]);
                }
            }
        break;
    }
    
    ob_start();
    ?>
    <style type="text/css">
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
            case 'lifestream-feeds.php':
                switch ($_GET['op'])
                {
                    case 'edit':
                        include('pages/edit-feed.inc.php');
                    break;
                    default:
                        $results =& $wpdb->get_results("SELECT t1.*, (SELECT COUNT(1) FROM `".LIFESTREAM_TABLE_PREFIX."event` WHERE `feed_id` = t1.`id`) as `events` FROM `".LIFESTREAM_TABLE_PREFIX."feeds` as t1 ORDER BY `id`");
                        if ($results !== false)
                        {
                            include('pages/feeds.inc.php');
                        }
                    break;
                }
            break;
            case 'lifestream-events.php':
                $page = $_GET['p'];
                if (!($page > 0)) $page = 1;

                $page -= 1;
                
                $results =& $wpdb->get_results(sprintf("SELECT t1.*, t2.`feed`, t2.`options` FROM `".LIFESTREAM_TABLE_PREFIX."event` as t1 JOIN `".LIFESTREAM_TABLE_PREFIX."feeds` as t2 ON t1.`feed_id` = t2.`id` ORDER BY t1.`timestamp` DESC LIMIT %d, 50", $page*50));

                include('pages/events.inc.php');
            break;
            default:
                include('pages/settings.inc.php');
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
        add_menu_page('LifeStream', 'LifeStream', 8, basename(LIFESTREAM_PLUGIN_FILE), 'lifestream_options');
        add_submenu_page(basename(LIFESTREAM_PLUGIN_FILE), __('LifeStream Settings', 'lifestream'), __('Settings', 'lifestream'), 8, 'lifestream.php', 'lifestream_options');
        add_submenu_page(basename(LIFESTREAM_PLUGIN_FILE), __('LifeStream Feeds', 'lifestream'), __('Feeds', 'lifestream'), 8, 'lifestream-feeds.php', 'lifestream_options');
        add_submenu_page(basename(LIFESTREAM_PLUGIN_FILE), __('LifeStream Events', 'lifestream'), __('Events', 'lifestream'), 8, 'lifestream-events.php', 'lifestream_options');
        
        //add_options_page('LifeStream Options', 'LifeStream', 8, basename(LIFESTREAM_PLUGIN_FILE), 'lifestream_options');
    }
}

function lifestream_header()
{
    global $lifestream_path;
    
    echo '<link rel="stylesheet" type="text/css" media="screen" href="'.$lifestream_path.'/lifestream.css"/>';
    echo '<script type="text/javascript" src="'.$lifestream_path.'/lifestream.js"></script>';
}

function widget_lifestream($args)
{
    extract($args);
?>
        <?php echo $before_widget; ?>
            <?php echo $before_title
                . 'LifeStream'
                . $after_title; ?>
            <?php lifestream(10); ?>
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
    $results =& $wpdb->get_results("SELECT * FROM `".LIFESTREAM_TABLE_PREFIX."feeds`");
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
        $digest_day = strtotime('-'.$days - $i.' days', $now);
        $digest_day = strtotime(date('Y-m-d 23:59:59', $digest_day));

        $results = $wpdb->get_results(sprintf("SELECT `post_id` FROM `{$wpdb->prefix}postmeta` WHERE `meta_key = '_lifestream_digest_date' AND `meta_value` = %d LIMIT 1", $digest_day));
        if ($results) continue;

        $sql = sprintf("SELECT t1.*, t2.`options` FROM `".LIFESTREAM_TABLE_PREFIX."event_group` as `t1` INNER JOIN `".LIFESTREAM_TABLE_PREFIX."feeds` as t2 ON t1.`feed_id` = t2.`id` WHERE t1.`timestamp` > '%s' AND t1.`timestamp` < '%s' ORDER BY t1.`timestamp` ASC", strtotime(date('Y-m-d 00:00:00', $digest_day)), strtotime(date('Y-m-d 23:59:59', $digest_day)));
        
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
}

if (function_exists('wp_register_sidebar_widget'))
{
    wp_register_sidebar_widget('lifestream', 'LifeStream', 'widget_lifestream', array('classname' => 'widget_lifestream', 'description' => 'Share your LifeStream on your blog.'));
}
elseif (function_exists('register_sidebar_widget'))
{
    register_sidebar_widget('LifeStream', 'widget_lifestream');
}

if ((isset($_GET['activate']) && $_GET['activate'] == 'true') || (isset($_GET['activate-multi']) && $_GET['activate-multi'] == 'true'))
{
    lifestream_activate();
}

add_action('admin_menu', 'lifestream_options_menu');
add_action('LifeStream_Hourly', 'lifestream_update');
add_action('wp_head', 'lifestream_header');
add_filter('the_content', 'lifestream_embed_callback');
add_action('init', 'lifestream_init');

$offset = get_option('lifestream_timezone');
define(LIFESTREAM_DATE_OFFSET, $offset);
