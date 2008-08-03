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
    return preg_replace_callback("|<lifestream(?:\s+([a-zA-Z_]+)=[\"']?([a-zA-Z0-9_-\s]+)[\"']?)*\s*/>|i", 'lifestream_embed_handler', $content);
}

/*
 * This function handles the real meat by handing off the work to helper functions.
 */
function lifestream_embed_handler($matches)
{
    // max_number
    $args = array();
    $matches = array_slice($matches, 1);
    for ($i=0; $i<=count($matches); $i+=2)
    {
        if ($matches[$i]) $args[$matches[$i]] = $matches[$i+1];
    }
    ob_start();
    lifestream($args['number_of_items'], $args['feed_ids'], $args['date_interval'], $args['output']);
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
    
    $results = $wpdb->get_results("SELECT COUNT(*) as `count` FROM `".LIFESTREAM_TABLE_PREFIX."feeds`");
    if (!$results[0]->count)
    {
        $rss_url = trailingslashit(get_settings('siteurl')) . 'wp-rss.php';
        $options = array('url' => $rss_url);

        $feed = new LifeStream_BlogFeed($options);
        $feed->save();
        $feed->refresh();
    }
    
    lifestream_install();
    lifestream_install_database();
}

/**
 * Initializes the database if it's not already present.
 */
function lifestream_install_database()
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
    
    /**
     * Returns a constant attached to this class.
     * @param {string} $constant
     * @return {string | integer} $value
     */
    public function get_constant($constant)
    {
        return constant(sprintf('%s::%s', get_class($this), $constant));
    }
    
    /**
     * Returns an array of available options.
     * @return {array} Available options.
     */
    public static function get_options()
    {        
        return array(
            // key => array(label, required, default value, choices)
            'url' => array('Feed URL', true, '', ''),
        );
    }
    
    /**
     * Instantiates this object through a feed instance
     */
    public static function construct_from_query_result($row)
    {
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
            $wpdb->query(sprintf("INSERT INTO `".LIFESTREAM_TABLE_PREFIX."feeds` (`feed`, `options`, `timestamp`) VALUES ('%s', '%s', '%s')", $wpdb->escape($this->get_constant('ID')), $wpdb->escape(serialize($this->options)), time()));
            $this->id = (string)$wpdb->insert_id;
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
            $wpdb->query(sprintf("INSERT IGNORE INTO `".LIFESTREAM_TABLE_PREFIX."data` (`feed`, `feed_id`, `link`, `text`, `timestamp`) VALUES ('%s', '%s', '%s', '%s', '%s')", $wpdb->escape($this->get_constant('ID')), $wpdb->escape($this->id), $wpdb->escape($item['link']), $wpdb->escape($item['text']), $wpdb->escape($item['date'])));
        }
        return count($items);
    }
    
    function get_events($limit=50, $offset=0)
    {
        global $wpdb;

        if (!$this->id) return false;
        
        if (!($limit > 0) || !($offset >= 0)) return false;

        return $wpdb->get_results(sprintf("SELECT * FROM `".LIFESTREAM_TABLE_PREFIX."data` WHERE `visible` = 1 AND `feed_id` = '%d' ORDER BY `timestamp` DESC LIMIT %s, %s", $this->id, $offset, $limit));
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
        // date and link are required
        // the rest of the data will be serialized into a `data` field
        // and is pulled out and used on the display($row) method
        return array(
            'date'      =>  strtotime($row->get_date()),
            'link'      =>  html_entity_decode($row->get_link()),
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
    
    $where = array('`visible` = 1');
    if (count($feed_ids))
    {
        foreach ($feed_ids as $key=>$value)
        {
            $feed_ids[$key] = $wpdb->escape($value);
        }
        $where[] = '`id` IN ('.implode(', ', $feed_ids).')';
    }

    $sql = sprintf("SELECT * FROM `".LIFESTREAM_TABLE_PREFIX."data` WHERE `timestamp` > UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL %s)) AND (%s) ORDER BY `timestamp` DESC LIMIT 0, %d", $date_interval, implode(') AND (', $where), $number_of_results);


    $results = $wpdb->get_results($sql);
    
    include(sprintf('pages/lifestream-%s.inc', $output));
}

function lifestream_options()
{
    global $lifestream_feeds, $wpdb;
    
    ksort($lifestream_feeds);

    #load_plugin_textdomain('lifestream', 'wp-content/plugins/lifestream/locales');
    
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
                    $result = $wpdb->get_results(sprintf("SELECT * FROM `".LIFESTREAM_TABLE_PREFIX."data` WHERE `id` = '%s'", $wpdb->escape($_GET['id'])));
                    if ($result)
                    {
                        $wpdb->query(sprintf("UPDATE `".LIFESTREAM_TABLE_PREFIX."data` SET `visible` = 0 WHERE `id` = '%s'", $wpdb->escape($_GET['id'])));
                        $message = 'The selected event was hidden.';
                    }
                    else
                    {
                        $errors[] = 'The selected event was not found.';
                    }
                break;
            }
        break;
        case 'feeds':
            switch ($_GET['op'])
            {
                case 'refresh':
                    $result = $wpdb->get_results(sprintf("SELECT * FROM `".LIFESTREAM_TABLE_PREFIX."feeds` WHERE `id` = '%s'", $wpdb->escape($_GET['id'])));
                    if ($result)
                    {
                        $instance = LifeStream_Feed::construct_from_query_result($result[0]);
                        $instance->refresh();
                        $message = 'The selected feed\'s events has been refreshed.';
                    }
                    else
                    {
                        $errors[] = 'The selected feed was not found.';
                    }
                break;
                case 'delete':
                    $result = $wpdb->get_results(sprintf("SELECT * FROM `".LIFESTREAM_TABLE_PREFIX."feeds` WHERE `id` = '%s'", $wpdb->escape($_GET['id'])));
                    if ($result)
                    {
                        $instance = LifeStream_Feed::construct_from_query_result($result[0]);
                        $instance->delete();
                        $message = 'The selected feed and all events has been removed.';                                        
                    }
                    else
                    {
                        $errors[] = 'The selected feed was not found.';
                    }
                break;
                case 'edit':
                    $result = $wpdb->get_results(sprintf("SELECT * FROM `".LIFESTREAM_TABLE_PREFIX."feeds` WHERE `id` = '%s'", $wpdb->escape($_GET['id'])));
                    if ($result)
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
                            if (!count($errors))
                            {
                                $instance->options = $values;
                                $instance->save();
                            }
                        }
                    }
                    else
                    {
                        $errors[] = 'The selected feed was not found.';
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
                        $results = $wpdb->get_results("SELECT t1.*, (SELECT COUNT(1) FROM `".LIFESTREAM_TABLE_PREFIX."data` WHERE `feed_id` = t1.`id`) as `events` FROM `".LIFESTREAM_TABLE_PREFIX."feeds` as t1");
                    
                        include('pages/feeds.inc');
                    break;
                }
            break;
            case 'events':
                $page = $_GET['p'];
                if (!($page > 0)) $page = 1;

                $page -= 1;
                
                $results = $wpdb->get_results(sprintf("SELECT * FROM `".LIFESTREAM_TABLE_PREFIX."data` WHERE `visible` = 1 ORDER BY `timestamp` DESC LIMIT %d, 50", $page*50));

                include('pages/events.inc');
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

function lifestream_options_menu() {
   if (function_exists('add_options_page'))
   {
        add_options_page('LifeStream Options', 'LifeStream', 8, basename(__FILE__), 'lifestream_options');
    }
}

function lifestream_header() {
    global $lifestream_path;
    
    echo '<link rel="stylesheet" type="text/css" media="screen" href="'.$lifestream_path.'/lifestream.css"/>';
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

include('feeds.inc');

/**
 * Attempts to update all feeds
 */
function lifestream_update()
{
    global $wpdb;
    $results = $wpdb->get_results("SELECT * FROM `".LIFESTREAM_TABLE_PREFIX."feeds`");
    foreach ($results as $result)
    {
        $instance = LifeStream_Feed::construct_from_query_result($result);
        $instance->refresh();
    }
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

if (isset($_GET['activate']) && $_GET['activate'] == 'true') {
    lifestream_activate();
}

if (!wp_get_schedule('LifeStream_Hourly')) wp_schedule_event(time(), 'hourly', 'LifeStream_Hourly');