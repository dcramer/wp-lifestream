<?php
class LifeStream_PlurkFeed extends LifeStream_Feed
{
    const ID            = 'plurk';
    const NAME          = 'Plurk';
    const URL           = 'http://www.plurk.com/';
        
    public static function get_options()
    {        
        return array(
            'username' => array('Username', true, '', ''),
        );
    }
    
    public function __toString()
    {
        return $this->options['username'];
    }

    function get_feed_url()
    {
        return 'http://www.plurk.com/user/'.$this->options['username'].'.xml';
    }

    function yield($row)
    {
        return array(
            'date'      =>  strtotime($row->get_date()),
            'link'      =>  html_entity_decode($row->get_link()),
            'text'      =>  $row->get_title(),
        );
    }
}
// Need to test this
//register_lifestream_feed('LifeStream_PlurkFeed');

class LifeStream_TwitterFeed extends LifeStream_Feed
{
    const ID            = 'twitter';
    const NAME          = 'Twitter';
    const URL           = 'http://www.twitter.com/';
        
    public static function get_options()
    {        
        return array(
            'username' => array('Username', true, '', ''),
            'link_urls' => array('Convert URLs to Links', false, true, true),
            'link_users' => array('Covert Usersnames to Links', false, true, true),
        );
    }

    public function __toString()
    {
        return $this->options['username'];
    }

    function parse_users($text)
    {
           $text = preg_replace('/([\.|\,|\:|\¡|\¿|\>|\{|\(]?)@{1}(\w*)([\.|\,|\:|\!|\?|\>|\}|\)]?)\s/i', "$1<a href=\"http://twitter.com/$2\" class=\"twitter-user\">@$2</a>$3 ", $text);
           return $text;
    }

    function get_feed_url()
    {
        return 'http://twitter.com/statuses/user_timeline/'.$this->options['username'].'.rss';
    }

    function yield($row)
    {
        $message = substr(strstr($row->get_description(),': '), 2, strlen($row->get_description()));

        if ($this->options['link_urls']) $message = $this->parse_hyperlinks($message);
        if ($this->options['users']) $message = $this->parse_users($message);

        return array(
            'date'      =>  strtotime($row->get_date()),
            'link'      =>  html_entity_decode($row->get_link()),
            'text'      =>  $message,
        );
    }
}
register_lifestream_feed('LifeStream_TwitterFeed');

class LifeStream_JaikuFeed extends LifeStream_TwitterFeed
{
    const ID            = 'jaiku';
    const NAME          = 'Jaiku';
    const URL           = 'http://www.jaiku.com/';
    
    function get_feed_url()
    {
        return 'http://'.$this->options['username'].'.jaiku.com/feed/rss';
    }

    function yield($row)
    {
        $message = $row->get_title();

        if ($this->options['link_urls']) $message = $this->parse_hyperlinks($message);
        if ($this->options['twitter_users']) $message = $this->parse_users($message);

        return array(
            'date'      =>  strtotime($row->get_date()),
            'link'      =>  html_entity_decode($row->get_link()),
            'text'      =>  $message,
        );
    }
}
register_lifestream_feed('LifeStream_JaikuFeed');

class LifeStream_DeliciousFeed extends LifeStream_Feed
{
    const NAMESPACE     = '';
    const ID            = 'delicious';
    const NAME          = 'Del.icio.us';
    const URL           = 'http://www.del.icio.us/';
        
    public static function get_options()
    {        
        return array(
            'username' => array('Username', true, '', ''),
            'filter_tag' => array('Limit items to tag', false, '', ''),
            'show_tags' => array('Show Tags', false, false, true),
            'display_description' => array('Display Descriptions', false, false, true),
        );
    }

    public function __toString()
    {
        return $this->options['username'];
    }

    function get_feed_url()
    {
        $url = 'http://del.icio.us/rss/'.$this->options['username'];
        if (!empty($this->options['filter_tag'])) $url .= '/'.$this->options['filter_tag'];
        return $url;
    }

    function yield($row)
    {
        $message = '<a href="'.$row->get_link().'" class="delicious-link">'.$row->get_title().'</a>';
        if ($this->options['display_description'] && $row->get_description())
        {
            $message .= '<br/><span class="delicious-desc">'.$row->get_description().'</span>';
        }

        if ($this->options['show_tags'])
        {
            $message .= '<br/><div class="delicious-tags">';

            $subject =& $row->get_item_tags(SIMPLEPIE_NAMESPACE_DC_11, 'subject');
            $tags = explode(' ', $row->sanitize($subject[0]['data'], SIMPLEPIE_CONSTRUCT_TEXT));
            
            foreach ($tags as $tag)
            {
                $message .= '<a href="http://del.icio.us/tag/'.$tag.'" class="delicious-link-tag">'.$tag.'</a> '; // Puts a link to the tag.
            }
            $message .= '</div>';
        }

        return array(
            // TODO: can we just use get_date()?
            'date'      =>  strtotime($row->get_date()),
            'link'      =>  html_entity_decode($row->get_link()),
            'text'      =>  $message,
        );
    }
}
register_lifestream_feed('LifeStream_DeliciousFeed');

class LifeStream_LastFMFeed extends LifeStream_Feed
{
    const ID            = 'lastfm';
    const NAME          = 'Last.fm';
    const URL           = 'http://www.last.fm/';
        
    public static function get_options()
    {        
        return array(
            'username' => array('Username', true, '', ''),
        );
    }

    public function __toString()
    {
        return $this->options['username'];
    }

    function get_feed_url()
    {
        return 'http://ws.audioscrobbler.com/1.0/user/'.$this->options['username'].'/recenttracks.rss';
    }

    function yield($row)
    {
        return array(
            'date'      =>  strtotime($row->get_date()),
            'link'      =>  html_entity_decode($row->get_link()),
            'text'      =>  '<a href="'.$row->get_link().'" class="lastfm-link">'.$row->get_title().'</a>',
        );
    }
}
register_lifestream_feed('LifeStream_LastFMFeed');

class LifeStream_BlogFeed extends LifeStream_Feed
{
    const ID            = 'blog';
    const NAME          = 'Blog';
        
    public static function get_options()
    {        
        return array(
            'url' => array('Feed URL', true, '', ''),
            'show_author' => array('Show Author', false, false, true),
        );
    }

    function yield($row)
    {
        $message = '<a href="'.$row->get_link().'" class="blog-link">'.$row->get_title().'</a>';
        if ($this->options['show_author'])
        {
            if ($author =& $row->get_item_tags(SIMPLEPIE_NAMESPACE_DC_11, 'creator'))
            {
                $message .= ' by '.$author[0]['data'];
            }
        }

        return array(
            'date'      =>  strtotime($row->get_date()),
            'link'      =>  html_entity_decode($row->get_link()),
            'text'      =>  $message,
        );
    }
}
register_lifestream_feed('LifeStream_BlogFeed');

class LifeStream_FlickrFeed extends LifeStream_Feed
{
    const ID            = 'flickr';
    const NAME          = 'Flickr';
    const URL           = 'http://www.flickr.com/';
    const DESCRIPTION   = 'You can find your User ID by using <a href="http://idgettr.com/">idGettr</a>.';
    const NAMESPACE     = 'http://search.yahoo.com/mrss/';
    
    public static function get_options()
    {        
        return array(
            'user_id' => array('User ID', true, '', ''),
        );
    }
    
    public function __toString()
    {
        return $this->get_feed_url();
    }

    function get_feed_url()
    {
        return 'http://api.flickr.com/services/feeds/photos_public.gne?id='.$this->options['user_id'].'&format=rss_200';
    }

    function yield($row)
    {
        $thumbnail = $event_name =& $row->get_item_tags(self::NAMESPACE, 'thumbnail');
        $thumbnail = $thumbnail[0]['attribs'][''];
        return array(
            'date'      =>  strtotime($row->get_date()),
            'link'      =>  html_entity_decode($row->get_link()),
            'text'      =>  sprintf('<a href="%s">%s</a>', $row->get_link(), $row->get_title()),
        );
    }
}
register_lifestream_feed('LifeStream_FlickrFeed');

class LifeStream_PhotoBucketFeed extends LifeStream_Feed
{
    const ID            = 'photobucket';
    const NAME          = 'Photobucket';
    const URL           = 'http://www.photobucket.com/';
    
    function yield($row)
    {
        return array(
            'date'      =>  strtotime($row->get_date()),
            'link'      =>  html_entity_decode($row->get_link()),
            'text'      =>  '<a href="'.htmlspecialchars($row->get_link()).'" class="photobucket-link">'.$row->get_title().'</a>',
        );
    }
}
register_lifestream_feed('LifeStream_PhotoBucketFeed');

class LifeStream_FacebookFeed extends LifeStream_Feed
{
    const ID            = 'facebook';
    const NAME          = 'Facebook';
    const URL           = 'http://www.facebook.com/';
    const DESCRIPTION   = 'To obtain your Facebook feed URL you will need to go your profile and click "See All" under your mini-feed. Once there, click "Status Stories" on the right hand side. On the right hand side of the next page you will the "My Status" RSS feed link.';

    function yield($row)
    {
        return array(
            'date'      =>  strtotime($row->get_date()),
            'link'      =>  html_entity_decode($row->get_link()),
            'text'      =>  '<a href="'.htmlspecialchars($row->get_link()).'" class="facebook-link">'.$row->get_title().'</a>',
        );
    }
}
register_lifestream_feed('LifeStream_FacebookFeed');

class LifeStream_PownceFeed extends LifeStream_Feed
{
    const NAMESPACE     = 'http://pownce.com/Atom';
    const ID            = 'pownce';
    const NAME          = 'Pownce';
    const URL           = 'http://www.pownce.com/';
        
    public static function get_options()
    {        
        return array(
            'username' => array('Username', true, '', ''),
            'link_urls' => array('Convert URLs to Links', false, true, true),
        );
    }
    
    public function __toString()
    {
        return $this->options['username'];
    }
    
    function get_feed_url()
    {
        return 'http://www.pownce.com/feeds/public/'.$this->options['username'].'/';
    }

    function yield($row)
    {
        $message = '<a href="'.$row->get_link().'">'.$row->get_description().'</a>';
        if ($this->options['link_urls'])
        {
            $message = $this->parse_urls($message);
        }
        
        if ($event_name =& $row->get_item_tags(self::NAMESPACE, 'event_name'))
        {
            $message .= ' <span class="pownce-event">(Event: '.$event_name[0]['data'];
            if ($event_location =& $row->get_item_tags(self::NAMESPACE, 'event_location')) $message .= ' at '.$event_location[0]['data'];
            if ($event_date =& $row->get_item_tags('pownce', 'event_date')) $message .= ' on '.strtotime($event_date[0]['data']);
            $message .= ')</span>';
        }
        return array(
            'date'      =>  strtotime($row->get_date()),
            'link'      =>  html_entity_decode($row->get_link()),
            'text'      =>  $message,
        );
    }
}
register_lifestream_feed('LifeStream_PownceFeed');

class LifeStream_DiggFeed extends LifeStream_Feed
{
    const ID            = 'digg';
    const NAME          = 'Digg';
    const URL           = 'http://www.digg.com/';
        
    public static function get_options()
    {        
        return array(
            'username' => array('Username', true, '', ''),
        );
    }
    
    public function __toString()
    {
        return $this->options['username'];
    }
    
    function get_feed_url()
    {
        return 'http://www.digg.com/users/'.$this->options['username'].'/history.rss';
    }

    function yield($row)
    {
        if (!$row->get_title()) return;
        return array(
            'date'      =>  strtotime($row->get_date()),
            'link'      =>  html_entity_decode($row->get_link()),
            'text'      =>  '<a href="'.$row->get_link().'">'.$row->get_title().'</a>',
        );
    }
}
register_lifestream_feed('LifeStream_DiggFeed');

class LifeStream_YouTubeFeed
{
    const NAMESPACE     = 'http://search.yahoo.com/mrss/';
    const ID            = 'youtube';
    const NAME          = 'YouTube';
    const URL           = 'http://www.youtube.com/';
        
    public static function get_options()
    {        
        return array(
            'username' => array('Username', true, '', ''),
        );
    }
    
    public function __toString()
    {
        return $this->options['username'];
    }
    
    function get_feed_url()
    {
        return 'http://www.youtube.com/ut_rss?type=username&arg='.$this->options['username'];
    }

    function yield($row)
    {
        if (!$row->get_title()) return;
        // $row->get_item_tags(NAMESPACE, 'thumbnail')
        // <media:thumbnail url="http://s1.ytimg.com/vi/tOJoE0HS_yo/default.jpg" width="120" height="90" />
        
        return array(
            'date'      =>  strtotime($row->get_date()),
            'link'      =>  html_entity_decode($row->get_link()),
            'text'      =>  '<a href="'.$row->get_link().'">'.$row->get_title().'</a>',
        );
    }
}
register_lifestream_feed('LifeStream_YouTubeFeed');
?>