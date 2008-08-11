<?php
/*
Plugin Name: LifeStream
Plugin URI: http://www.davidcramer.net/my-projects/lifestream
Description: Displays feeds in a lifestream.
Author: David Cramer
Version: 0.32
Author URI: http://www.davidcramer.net
*/

define(LIFESTREAM_TABLE_PREFIX, $wpdb->prefix.'lifestream_');

require('simplepie.inc');

$lifestream_path = trailingslashit(get_settings('siteurl')) . 'wp-content/plugins/lifestream';

// TODO: group events e.g. flickr photos

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
function lifestream_install()
{
    // add_option("RSS_Stream_date", "%B %e");
    //     add_option("RSS_Stream_hour", "g:ia");
    //     add_option("RSS_Stream_blogfeed", bloginfo('rss2_url'));
    //     add_option("RSS_Stream_genericfeednumber", '0');
    //     add_option("RSS_Stream_timelapse", '10');
    
    // read in the sql database
    if (get_option('lifestream_day_format') == '') update_option('lifestream_day_format', 'F jS');
    if (get_option('lifestream_hour_format') == '') update_option('lifestream_hour_format', 'g:ia');   
    if (get_option('lifestream_timezone') == '') update_option('lifestream_timezone', date('O')/100);
}

function lifestream_activate()
{
    global $wpdb;
    // Add a feed for this blog
    
    lifestream_install();
    lifestream_install_database();
    
    $results =& $wpdb->get_results("SELECT COUNT(*) as `count` FROM `".LIFESTREAM_TABLE_PREFIX."feeds`");
    if (!$results[0]->count)
    {
        $rss_url = trailingslashit(get_settings('siteurl')) . 'wp-rss.php';
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
    
    $wpdb->query("CREATE TABLE IF NOT EXISTS `".LIFESTREAM_TABLE_PREFIX."event` (
      `id` int(11) NOT NULL auto_increment,
      `feed_id` int(11) NOT NULL,
      `link` varchar(200) NOT NULL,
      `data` blob NOT NULL,
      `visible` tinyint(1) default 1 NOT NULL,
      `timestamp` int(11) NOT NULL,
      PRIMARY KEY  (`id`),
      UNIQUE (`feed_id`, `link`)
    ) ENGINE=MyISAM  DEFAULT CHARSET=utf8;");

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
      PRIMARY KEY  (`id`),
      INDEX (`feed_id`, `timestamp`)
    ) ENGINE=MyISAM  DEFAULT CHARSET=utf8;");
    
    $wpdb->query("CREATE TABLE IF NOT EXISTS `".LIFESTREAM_TABLE_PREFIX."feeds` (
      `id` int(11) NOT NULL auto_increment,
      `feed` varchar(32) NOT NULL,
      `options` text default NULL,
      `timestamp` int(11) NOT NULL,
      PRIMARY KEY  (`id`)
    ) ENGINE=MyISAM  DEFAULT CHARSET=utf8;");
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
         $this->total = $row->total;
         $this->visible = $row->visible;
         $this->link = ($this->data['link'] ? $this->data['link'] : $row->link);
         $this->feed = new $lifestream_feeds[$row->feed](unserialize($row->options), $row->feed_id);
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
            'url' => array('Feed URL', true, '', ''),
        );
    }
    
    function save()
    {
        global $wpdb;
        
        // If it has an ID it means it already exists.
        if ($this->id)
        {
            $wpdb->query(sprintf("UPDATE `".LIFESTREAM_TABLE_PREFIX."feeds` set `options` = '%s' WHERE `id` = '%d'", $wpdb->escape(serialize($this->options)), $this->id));
        }
        else
        {
            $wpdb->query(sprintf("INSERT INTO `".LIFESTREAM_TABLE_PREFIX."feeds` (`feed`, `options`, `timestamp`) VALUES ('%s', '%s', '%d')", $wpdb->escape($this->get_constant('ID')), $wpdb->escape(serialize($this->options)), time()));
            $this->id = $wpdb->insert_id;
        }
    }
    
    function delete()
    {
        global $wpdb;

        $wpdb->query(sprintf("DELETE FROM `".LIFESTREAM_TABLE_PREFIX."feeds` WHERE `id` = '%d'", $this->id));
        $wpdb->query(sprintf("DELETE FROM `".LIFESTREAM_TABLE_PREFIX."event` WHERE `feed_id` = '%d'", $this->id));
        $wpdb->query(sprintf("DELETE FROM `".LIFESTREAM_TABLE_PREFIX."event_group` WHERE `feed_id` = '%d'", $this->id));
        
        $this->id = null;
    }
    
    function refresh()
    {
        global $wpdb;

        if (!$this->id) return false;

        $inserted = array();
        $items = $this->fetch();
        if (!$items) return false;
        foreach ($items as $item)
        {
            $link = array_key_pop($item, 'link');
            $date = array_key_pop($item, 'date');
            
            $affected =& $wpdb->query(sprintf("INSERT IGNORE INTO `".LIFESTREAM_TABLE_PREFIX."event` (`feed_id`, `link`, `data`, `timestamp`) VALUES ('%d', '%s', '%s', '%d')", $this->id, $wpdb->escape($link), $wpdb->escape(serialize($item)), $date));
            if ($affected)
            {
                $item['date'] = $date;
                $item['link'] = $link;
                $inserted[] = $item;
            }
        }
        if (count($inserted))
        {
            // Rows were inserted so we need to handle the grouped events
            
            if ($this->options['grouped'] && $this->get_constant('CAN_GROUP'))
            {
                $grouped = array();
                // Now let's fetch the dates we need to fix in the database
                foreach ($inserted as $item)
                {
                    $date = date('m d Y', $item['date']);
                    if (in_array($date, $grouped)) continue;

                    // Get all of the current events for this date
                    // (including the one we affected just now)
                    $results =& $wpdb->get_results(sprintf("SELECT `data`, `link` FROM `".LIFESTREAM_TABLE_PREFIX."event` WHERE `feed_id` = '%d' AND `visible` = 1 AND DATE(FROM_UNIXTIME(`timestamp`)) = DATE(FROM_UNIXTIME('%d'))", $this->id, $item['date']));
                    $events = array();
                    foreach ($results as &$result)
                    {
                        $result->data = unserialize($result->data);
                        $result->data['link'] = $result->link;
                        $events[] = $result->data;
                    }

                    // First let's see if the group already exists in the database
                    $group =& $wpdb->get_results(sprintf("SELECT `id` FROM `".LIFESTREAM_TABLE_PREFIX."event_group` WHERE `feed_id` = '%d' AND DATE(FROM_UNIXTIME(`timestamp`)) = DATE(FROM_UNIXTIME('%d')) LIMIT 0, 1", $this->id, $item['date']));
                    if (count($group) == 1)
                    {
                        $group =& $group[0];
                        $wpdb->query(sprintf("UPDATE `".LIFESTREAM_TABLE_PREFIX."event_group` SET `data` = '%s', `total` = '%d', `updated` = 1 WHERE `id` = '%d'", $wpdb->escape(serialize($events)), count($events), $group->id));
                    }
                    else
                    {
                        $wpdb->query(sprintf("INSERT INTO `".LIFESTREAM_TABLE_PREFIX."event_group` (`feed_id`, `feed`, `data`, `total`, `timestamp`) VALUES('%d', '%s', '%s', '%d', '%d')", $this->id, $wpdb->escape($this->get_constant('ID')), $wpdb->escape(serialize($events)), count($events), $item['date']));
                    }
                    
                    $grouped[] = $date;
                }
            }
            else
            {
                foreach ($inserted as &$item)
                {
                    $date = array_key_pop($item, 'date');

                    $wpdb->query(sprintf("INSERT INTO `".LIFESTREAM_TABLE_PREFIX."event_group` (`feed_id`, `feed`, `data`, `timestamp`, `total`) VALUES('%d', '%s', '%s', '%d', 0)", $this->id, $wpdb->escape($this->get_constant('ID')), $wpdb->escape(serialize(array($item))), $date));
                }
            }
        }
        return count($inserted);
    }
    
    function get_events($limit=50, $offset=0)
    {
        global $wpdb;

        if (!$this->id) return false;
        
        if (!($limit > 0) || !($offset >= 0)) return false;

        $results =& $wpdb->get_results(sprintf("SELECT t1.*, t2.`feed`, t2.`options` FROM `".LIFESTREAM_TABLE_PREFIX."event` as t1 JOIN `".LIFESTREAM_TABLE_PREFIX."feeds` as t2 ON t1.`feed_id` = t2.`id` WHERE t1.`feed_id` = '%d' ORDER BY t1.`timestamp` DESC LIMIT %d, %d", $this->id, $offset, $limit));
        $events = array();
        foreach ($results as &$result)
        {
            $events[] = new LifeStream_Event($result);
        }
        return $events;
    }
    function fetch()
    {
        $feed = new SimplePie();
        $feed->enable_cache(false);
        $feed->set_feed_url($this->get_url());
        $feed->init();
        $feed->handle_content_type();

        $response =& $feed->get_items();
        if ($response)
        {
            $items = array();
            foreach ($response as $row)
            {
                $result =& $this->yield($row);
                if (count($result)) $items[] = $result;
            }
            return $items;
        }
        return;
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
        return sprintf(__($this->get_constant('LABEL_SINGLE'), 'lifestream'), $this->get_public_url(), $this->get_public_name()) . '<br />' . $this->render_item($row, $row->data[0]);
    }
    
    function render_group($row)
    {
        $output = array();
        foreach ($row->data as $chunk)
        {
            $output[] = $this->render_item($row, $chunk);
        }
        $id = sprintf('lf_%s', round(microtime(true)*rand(10000,1000000)));
        return sprintf(__($this->get_constant('LABEL_PLURAL'), 'lifestream'), $row->total, $this->get_public_url(), $this->get_public_name()) . ' <small class="lifestream_more">(<a href="javascript:void(0);" onclick="lifestream_toggle(this, \'' . $id . '\', \'' . __('Show Details', 'lifestream') . '\', \''. __('Hide Details', 'lifestream') .'\')">' . __('Show Details', 'lifestream') . '</a>)</small><br /><ul id="' . $id . '" style="display:none;"><li>' . implode('</li><li>', $output) . '</li></ul>';
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

function lifestream($number_of_results=null, $feed_ids=null, $date_interval=null, $output=null)
{
    global $lifestream_path, $wpdb;
    
    if ($number_of_results == null) $number_of_results = 50;
    if ($feed_ids == null) $feed_ids = array();
    if ($date_interval == null) $date_interval = '1 month';
    if ($output == null) $output = 'table';

    # If any arguments are invalid we bail out

    if (!((int)$number_of_results > 0)) return;

    if (!preg_match('/[\d]+ (month|day|year)/', $date_interval)) return;
    
    if (!is_array($feed_ids)) return;
    
    if (!in_array($output, array('table', 'list'))) return;
    
    setlocale(LC_TIME, get_locale());
    
    $offset = get_option('lifestream_timezone');
    $hour_format = get_option('lifestream_hour_format');
    $day_format = get_option('lifestream_day_format');
    
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
    
    include(sprintf('pages/lifestream-%s.inc.php', $output));
}

function lifestream_options()
{
    global $lifestream_feeds, $wpdb;
    
    ksort($lifestream_feeds);
    
    setlocale(LC_TIME, get_locale());
    
    lifestream_install();
    
    $date_format = sprintf('%s @ %s', get_option('lifestream_day_format'), get_option('lifestream_hour_format'));
    $basename = basename(__FILE__);
    
    $errors = array();
    $message = null;
    switch ($_GET['action'])
    {
        case 'events':
            switch ($_GET['op'])
            {
                case 'delete':
                    $result =& $wpdb->get_results(sprintf("SELECT `id`, `feed_id`, `timestamp` FROM `".LIFESTREAM_TABLE_PREFIX."event` WHERE `id` = '%d'", $_GET['id']));
                    if (count($result) == 1)
                    {
                        $result =& $result[0];
                        $wpdb->query(sprintf("UPDATE `".LIFESTREAM_TABLE_PREFIX."event` SET `visible` = 0 WHERE `id` = '%d'", $result->id));
                        $wpdb->query(sprintf("UPDATE `".LIFESTREAM_TABLE_PREFIX."event_group` SET `visible` = 0 WHERE `event_id` = '%d'", $result->id));
                        
                        // Now we have to update the batch if it exists.
                        $group =& $wpdb->get_results(sprintf("SELECT `id` FROM `".LIFESTREAM_TABLE_PREFIX."event_group` WHERE `event_id` IS NULL AND DATE(FROM_UNIXTIME(`timestamp`)) = DATE(FROM_UNIXTIME(%d)) LIMIT 0, 1", $result->timestamp));
                        if (count($group) == 1)
                        {
                            $group =& $group[0];
                            $results =& $wpdb->get_results(sprintf("SELECT `data`, `link` FROM `".LIFESTREAM_TABLE_PREFIX."event` WHERE `feed_id` = '%d' AND `visible` = 1 AND DATE(FROM_UNIXTIME(`timestamp`)) = DATE(FROM_UNIXTIME('%d'))", $result->feed_id, $result->timestamp));
                            $events = array();
                            foreach ($results as &$result)
                            {
                                $result->data = unserialize($result->data);
                                $result->data['link'] = $result->link;
                                $events[] = $result->data;
                            }
                            $wpdb->query(sprintf("UPDATE `".LIFESTREAM_TABLE_PREFIX."event_group` SET `data` = '%s', `total` = '%d', `updated` = 1 WHERE `id` = '%d'", $wpdb->escape(serialize($events)), count($events), $group->id));
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
        case 'feeds':
            switch ($_GET['op'])
            {
                case 'refresh':
                    $result =& $wpdb->get_results(sprintf("SELECT * FROM `".LIFESTREAM_TABLE_PREFIX."feeds` WHERE `id` = '%d' LIMIT 0, 1", $_GET['id']));
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
                    $result =& $wpdb->get_results(sprintf("SELECT * FROM `".LIFESTREAM_TABLE_PREFIX."feeds` WHERE `id` = '%d' LIMIT 0, 1", $_GET['id']));
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
                    $result =& $wpdb->get_results(sprintf("SELECT * FROM `".LIFESTREAM_TABLE_PREFIX."feeds` WHERE `id` = '%d' LIMIT 0, 1", $_GET['id']));
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
                            $feed->save();
                            $events = $feed->refresh();
                            unset($_POST);
                            $message = sprintf(__('Selected feed was added to your LifeStream with %d event(s).', 'lifestream'), $events);
                        }
                    }
                break;
            }
        break;
        default:
            if ($_POST['save'])
            {
                $options = array('lifestream_timezone', 'lifestream_day_format', 'lifestream_hour_format');
                foreach ($options as $value)
                {
                    update_option($value, $_POST[$value]);
                }
            }
        break;
    }
    
    ob_start();
    if (count($errors)) { ?>
    <div id="message" class="error"><p><strong><?php _e('Please correct the following errors:', 'lifestream') ?></strong></p><ul>
        <?php foreach ($errors as $error) { ?>
            <li><?php echo htmlspecialchars($error); ?></li>
        <?php } ?>
    </ul></div>
    <?php } elseif ($message) { ?>
    <div id="message" class="updated fade"><p><strong><?php echo $message; ?></strong></p></div>
    <?php } ?>
    <style type="text/css">
    table.options th { text-align: left; }
    table.options th { vertical-align: top; line-height: 30px; }
    table.options td .helptext { color: #999; margin-top: 3px; }
    </style>
    <div class="wrap">
        <?php
        switch ($_GET['action'])
        {
            case 'feeds':
                switch ($_GET['op'])
                {
                    case 'edit':
                        include('pages/edit-feed.inc.php');
                    break;
                    default:
                        $results =& $wpdb->get_results("SELECT t1.*, (SELECT COUNT(1) FROM `".LIFESTREAM_TABLE_PREFIX."event` WHERE `feed_id` = t1.`id`) as `events` FROM `".LIFESTREAM_TABLE_PREFIX."feeds` as t1 ORDER BY `id`");
                    
                        include('pages/feeds.inc.php');
                    break;
                }
            break;
            case 'events':
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

function lifestream_options_menu() {
   if (function_exists('add_options_page'))
   {
        add_options_page('LifeStream Options', 'LifeStream', 8, basename(__FILE__), 'lifestream_options');
    }
}

function lifestream_header() {
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
    $results =& $wpdb->get_results("SELECT * FROM `".LIFESTREAM_TABLE_PREFIX."feeds`");
    foreach ($results as $result)
    {
        $instance = LifeStream_Feed::construct_from_query_result($result);
        $instance->refresh();
    }
}

function lifestream_init()
{
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


add_action('admin_menu', 'lifestream_options_menu');
add_action('LifeStream_Hourly', 'lifestream_update');
add_action('wp_head', 'lifestream_header');
add_filter('the_content', 'lifestream_embed_callback');
add_action('init', 'lifestream_init');

if (isset($_GET['activate']) && $_GET['activate'] == 'true') {
    lifestream_activate();
}

if (!wp_get_schedule('LifeStream_Hourly')) wp_schedule_event(time(), 'hourly', 'LifeStream_Hourly');