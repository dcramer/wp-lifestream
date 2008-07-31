<?php
/*
Plugin Name: LifeStream
Plugin URI: http://www.davidcramer.net/my-projects/lifestream
Description: Displays feeds in a lifestream.
Author: David Cramer
Version: 0.1
Author URI: http://www.davidcramer.net
*/

define(LIFESTREAM_TABLE_PREFIX, $wpdb->prefix.'lifestream_');

require('simplepie.inc');

$lifestream_path = trailingslashit(get_settings('siteurl')) . 'wp-content/plugins/lifestream';

// TODO: confirm htmlspecialchars isnt needed
// TODO: group events e.g. flickr photos
// TODO: convert dates to timestamps and not text
// TODO: fix parse_urls

define('MAGPIE_INPUT_ENCODING', 'UTF-8');

include_once(ABSPATH . WPINC . '/rss.php');

function get_class_const($class, $const)
{
    return constant(sprintf('%s::%s', $class, $const));
}

$lifestream_feeds = array();
/**
 * Registers a feed class with LifeStream.
 */
function register_lifestream_feed($class_name)
{
    global $lifestream_feeds;

    $lifestream_feeds[get_class_const($class_name, 'ID')] = $class_name;
}

/**
 * Adds/updates the options on plug-in activation.
 */
function LifeStream_Install()
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

function LifeStream_Activate()
{
    global $wpdb;
    // Add a feed for this blog
    
    $results = $wpdb->get_results("SELECT COUNT(*) as `count` FROM `".LIFESTREAM_TABLE_PREFIX."feeds`");
    if (!$results[0]->count)
    {
        $rss_url = trailingslashit(get_settings('siteurl')) . '/wp-rss.php';
        $options = array('url' => $rss_url);

        $feed = new LifeStream_BlogFeed($options);
        $feed->save();
        $feed->refresh();
    }
    
    LifeStream_Install();
    LifeStream_InstallDatabase();
}

/**
 * Attempts to update all feeds
 */
function LifeStream_Update()
{
    global $wpdb;
    $results = $wpdb->get_results("SELECT * FROM `".LIFESTREAM_TABLE_PREFIX."feeds`");
    foreach ($results as $result)
    {
        $instance = LifeStream_Feed::ConstructFromQueryResult($result);
        $instance->refresh();
    }
}

/**
 * Initializes the database if it's not already present.
 */
function LifeStream_InstallDatabase()
{
    global $wpdb;
    
    $wpdb->query("CREATE TABLE IF NOT EXISTS `".LIFESTREAM_TABLE_PREFIX."data` (
      `id` int(11) NOT NULL auto_increment,
      `feed_id` int(11) NOT NULL,
      `feed` varchar(32) NOT NULL,
      `link` varchar(200) NOT NULL,
      `text` text NOT NULL,
      `visible` tinyint(1) default 1 NOT NULL,
      `timestamp` int(11) NOT NULL,
      PRIMARY KEY  (`id`),
      UNIQUE (`feed`, `link`)
    ) ENGINE=MyISAM  DEFAULT CHARSET=utf8;");
    
    $wpdb->query("CREATE TABLE IF NOT EXISTS `".LIFESTREAM_TABLE_PREFIX."feeds` (
      `id` int(11) NOT NULL auto_increment,
      `feed` varchar(32) NOT NULL,
      `options` text default NULL,
      `timestamp` int(11) NOT NULL,
      PRIMARY KEY  (`id`)
    ) ENGINE=MyISAM  DEFAULT CHARSET=utf8;");
}

class LifeStream_Feed
{
    public $options;
    
    const ID            = 'generic';
    const NAME          = 'Generic';
    const AUTHOR        = 'David Cramer';
    const URL           = '';
    const DESCRIPTION   = '';
    
    public function get_constant($constant)
    {
        return constant(sprintf('%s::%s', get_class($this), $constant));
    }
    
    public static function get_options()
    {        
        return array(
            // key => array(label, required, default value, choices)
            'url' => array('Feed URL', true, '', ''),
        );
    }
    
    public static function ConstructFromQueryResult($row)
    {
        global $lifestream_feeds;
        
        $class = $lifestream_feeds[$row->feed];
        
        if (!empty($row->options)) $options = unserialize($row->options);
        else $options = null;

        $instance = new $class($options, $row->id);
        # $instance->options = unserialize($row['options']);
        return $instance;
    }

    function __construct($options=array(), $id=null)
    {
        $this->options = $options;
        $this->id = $id;
    }
    
    function __toString()
    {
        return (string)$this->options['url'];
    }
    
    function save()
    {
        global $wpdb;
        
        // If it has an ID it means it already exists.
        if ($this->id)
        {
            $wpdb->query(sprintf("UPDATE `".LIFESTREAM_TABLE_PREFIX."feeds` set `options` = '%s' WHERE `id` = '%s'", serialize($this->options), $this->id));
        }
        else
        {
            $wpdb->query(sprintf("INSERT INTO `".LIFESTREAM_TABLE_PREFIX."feeds` (`feed`, `options`, `timestamp`) VALUES ('%s', '%s', '%s')", $wpdb->escape(self::ID), $wpdb->escape(serialize($this->options)), time()));
            $feed->id = (string)$wpdb->insert_id;
        }
    }
    
    function delete()
    {
        global $wpdb;

        $wpdb->query(sprintf("DELETE FROM `".LIFESTREAM_TABLE_PREFIX."feeds` WHERE `id` = '%s'", $wpdb->escape($this->id)));
        $wpdb->query(sprintf("DELETE FROM `".LIFESTREAM_TABLE_PREFIX."data` WHERE `feed_id` = '%s'", $wpdb->escape($this->id)));
        
        $this->id = null;
    }
    
    function refresh()
    {
        global $wpdb;

        if (!$this->id) return false;

        $items = $this->fetch();
        foreach ($items as $item)
        {
            $wpdb->query(sprintf("INSERT IGNORE INTO `".LIFESTREAM_TABLE_PREFIX."data` (`feed`, `feed_id`, `link`, `text`, `timestamp`) VALUES ('%s', '%s', '%s', '%s', '%s')", $wpdb->escape(self::ID), $wpdb->escape($this->id), $wpdb->escape($item['link']), $wpdb->escape($item['text']), $wpdb->escape($item['date'])));
        }
        return count($items);
        
    }
    
    function fetch()
    {
        $feed = new SimplePie();
        $feed->enable_cache(false);
        $feed->set_feed_url($this->get_feed_url());
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
        return array(
            'date'      =>  strtotime($row->get_date()),
            'link'      =>  $row->get_link(),
            'text'   =>  '<a href="'.htmlspecialchars($row->get_link()).'" class="generic-link">'.$row->get_title().'</a>',
        );
    }
    
    function get_feed_url()
    {
        return $this->options['url'];
    }
    
    function parse_urls($text)
    {
        // match protocol://address/path/file.extension?some=variable&another=asf%
        $text = preg_replace("/\s([a-zA-Z]+:\/\/[a-z][a-z0-9\_\.\-]*[a-z]{2,6}[a-zA-Z0-9\/\*\-\?\&\%]*)([\s|\.|\,])/i"," <a href=\"$1\" class=\"twitter-link\">$1</a>$2", $text);
        // match www.something.domain/path/file.extension?some=variable&another=asf%
        $text = preg_replace("/\s(www\.[a-z][a-z0-9\_\.\-]*[a-z]{2,6}[a-zA-Z0-9\/\*\-\?\&\%]*)([\s|\.|\,])/i"," <a href=\"http://$1\" class=\"twitter-link\">$1</a>$2", $text);      
        // match name@address
        $text = preg_replace("/\s([a-zA-Z][a-zA-Z0-9\_\.\-]*[a-zA-Z]*\@[a-zA-Z][a-zA-Z0-9\_\.\-]*[a-zA-Z]{2,6})([\s|\.|\,])/i"," <a href=\"mailto://$1\" class=\"twitter-link\">$1</a>$2", $text);    
        return $text;
    }
}
register_lifestream_feed('LifeStream_Feed');

function LifeStream()
{
    global $lifestream_path, $wpdb;
    
    setlocale(LC_TIME, get_locale());
    
    $offset = get_option('lifestream_timezone');
    $hour_format = get_option('lifestream_hour_format');
    $day_format = get_option('lifestream_day_format');

    $results = $wpdb->get_results("SELECT * FROM `".LIFESTREAM_TABLE_PREFIX."data` WHERE `timestamp` > UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL 1 MONTH)) AND `visible` = 1 ORDER BY `timestamp` DESC LIMIT 0, 50");
    
    include('pages/lifestream.inc');
}

function LifeStream_Options()
{
    global $lifestream_feeds, $wpdb;

    load_plugin_textdomain('lifestream', 'wp-content/plugins/lifestream/locales');
    
    setlocale(LC_TIME, get_locale());
    
    LifeStream_Install();
    
    $errors = array();
    $message = null;
    switch ($_GET['action'])
    {
        case 'feeds':        
            switch ($_GET['op'])
            {
                case 'refresh':
                    $result = $wpdb->get_results(sprintf("SELECT * FROM `".LIFESTREAM_TABLE_PREFIX."feeds` WHERE `id` = '%s'", $wpdb->escape($_GET['id'])));
                    $instance = LifeStream_Feed::ConstructFromQueryResult($result[0]);
                    $instance->refresh();
                    $message = 'The selected feed\'s events has been refreshed.';
                break;
                case 'delete':
                    $result = $wpdb->get_results(sprintf("SELECT * FROM `".LIFESTREAM_TABLE_PREFIX."feeds` WHERE `id` = '%s'", $wpdb->escape($_GET['id'])));
                    $instance = LifeStream_Feed::ConstructFromQueryResult($result[0]);
                    $instance->delete();
                    $message = 'The selected feed and all events has been removed.';                
                break;
                case 'edit':
                    $result = $wpdb->get_results(sprintf("SELECT * FROM `".LIFESTREAM_TABLE_PREFIX."feeds` WHERE `id` = '%s'", $wpdb->escape($_GET['id'])));

                    $instance = LifeStream_Feed::ConstructFromQueryResult($result[0]);

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
                        if (!count($errors))
                        {
                            $instance->options = $values;
                            $instance->save();
                        }
                    }
                break;
                default:
                    if ($_POST['save'])
                    {
                        $class_name = $lifestream_feeds[$_POST['feed_type']];
                        if (!$class_name) break;
                        $values = array();
                        $options = call_user_func(array($class_name, 'get_options'));
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
                        if (!count($errors))
                        {
                            $feed = new $class_name($values);
                            $feed->save();
                            $events = $feed->refresh();
                            unset($_POST);
                            $message = 'Selected feed was added to your LifeStream with '.$events.' event(s).';
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
    
    $basename = basename(__FILE__);

    ob_start();
    if (count($errors)) { ?>
    <div id="message" class="error"><p><strong><?php _e('Please correct the following errors:', 'lifestream') ?></strong></p><ul>
        <?php foreach ($errors as $error) { ?>
            <li><?php echo htmlspecialchars($error); ?></li>
        <?php } ?>
    </ul></div>
    <?php } elseif ($message) { ?>
    <div id="message" class="updated fade"><p><strong><?php _e($message, 'lifestream') ?></strong></p></div>
    <?php } ?>
    <style type="text/css">
    table.options th { text-align: left; }
    table.options th { vertical-align: top; line-height: 30px; }
    </style>
    <div class="wrap">
        <?php
        switch ($_GET['action'])
        {
            case 'feeds':
                switch ($_GET['op'])
                {
                    case 'edit':
                        include('pages/edit-feed.inc');
                    break;
                    default:
                        include('pages/feeds.inc');
                    break;
                }
            break;
            default:
                include('pages/settings.inc');
            break;
        }
        ?>
    </div>
    <?php
    ob_end_flush();
}

function LifeStream_OptionsMenu() {
   if (function_exists('add_options_page'))
   {
        add_options_page('LifeStream Options', 'LifeStream', 8, basename(__FILE__), 'LifeStream_Options');
    }
}

function LifeStream_Header() {
    global $lifestream_path;
    
    echo '<link rel="stylesheet" type="text/css" media="screen" href="'.$lifestream_path.'/lifestream.css"/>';
}

add_action('admin_menu', 'LifeStream_OptionsMenu');
add_action('LifeStream_Hourly', 'LifeStream_Update');
add_action('wp_head', 'LifeStream_Header');

include('feeds.inc');

if (isset($_GET['activate']) && $_GET['activate'] == 'true') {
    LifeStream_Activate();
}

if (!wp_get_schedule('LifeStream_Hourly')) wp_schedule_event(time(), 'hourly', 'LifeStream_Hourly');