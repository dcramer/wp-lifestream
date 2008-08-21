<?php
class LifeStream_PlurkFeed extends LifeStream_Feed
{
    const ID            = 'plurk';
    const NAME          = 'Plurk';
    const URL           = 'http://www.plurk.com/';

    function __toString()
    {
        return $this->options['username'];
    }

    function get_options()
    {
        return array(
            'username' => array('Username:', true, '', ''),
        );
    }

    function get_url()
    {
        return 'http://www.plurk.com/user/'.$this->options['username'].'.xml';
    }
}
// Need to test this
//register_lifestream_feed('LifeStream_PlurkFeed');

class LifeStream_TwitterFeed extends LifeStream_Feed
{
    const ID            = 'twitter';
    const NAME          = 'Twitter';
    const URL           = 'http://www.twitter.com/';
    const LABEL_SINGLE  = 'Posted a tweet on <a href="%s">%s</a>.';
    const LABEL_PLURAL  = 'Posted %d tweets on <a href="%s">%s</a>.';
    
    function __toString()
    {
        return $this->options['username'];
    }

    function get_options()
    {        
        return array(
            'username' => array('Username:', true, '', ''),
            'link_urls' => array('Convert URLs to Links', false, true, true),
            'link_users' => array('Convert Usersnames to Links', false, true, true),
        );
    }
    
    function _get_user_link($match)
    {
        return $this->get_user_link($match[1]);
    }
    
    function get_user_link($user)
    {
        return '<a href="http://www.twitter.com/'.$user.'" class="user">@'.$user.'</a>';
    }

    function parse_users($text)
    {
        return preg_replace_callback('/(?:^@([a-z0-9_-]+):[^\b]@([a-z0-9_-]+))\b/i', array($this, '_get_user_link'), $text);
    }

    function get_url()
    {
        return 'http://twitter.com/statuses/user_timeline/'.$this->options['username'].'.rss';
    }
    
    function render_item($row, $item)
    {
        return $this->parse_users($this->parse_urls($item['title']));
    }
    
    function yield($row)
    {
        return array(
            'date'      =>  $row->get_date('U'),
            'link'      =>  html_entity_decode($row->get_link()),
            'title'     =>  html_entity_decode($row->get_description()),
        );
    }
}
register_lifestream_feed('LifeStream_TwitterFeed');

class LifeStream_JaikuFeed extends LifeStream_TwitterFeed
{
    const ID            = 'jaiku';
    const NAME          = 'Jaiku';
    const URL           = 'http://www.jaiku.com/';
    const LABEL_SINGLE  = 'Posted a Jaiku on <a href="%s">%s</a>.';
    const LABEL_PLURAL  = 'Posted %d Jaikus on <a href="%s">%s</a>.';
    const NAMESPACE     = 'http://jaiku.com/ns';
    
    function get_url()
    {
        return 'http://'.$this->options['username'].'.jaiku.com/feed/rss';
    }
    
    function yield($row)
    {
        if (!str_startswith($row->get_link(), 'http://'.$this->options['username'].'.jaiku.com/presence/')) return;
        
        preg_match('|<p>([^<]+)</p>|i', $row->get_description(), $matches);
        $title = $matches[1];
        
        return array(
            'date'      =>  $row->get_date('U'),
            'link'      =>  html_entity_decode($row->get_link()),
            'title'     =>  html_entity_decode($title),
        );
    }
}
register_lifestream_feed('LifeStream_JaikuFeed');

class LifeStream_DeliciousFeed extends LifeStream_Feed
{
    const NAMESPACE     = '';
    const ID            = 'delicious';
    const NAME          = 'Delicious';
    const URL           = 'http://www.delicious.us/';
    const LABEL_SINGLE  = 'Bookmarked a link on <a href="%s">%s</a>.';
    const LABEL_PLURAL  = 'Bookmarked %d links on <a href="%s">%s</a>.';

    function __toString()
    {
        return $this->options['username'];
    }
        
    function get_options()
    {        
        return array(
            'username' => array('Username:', true, '', ''),
            'filter_tag' => array('Limit items to tag:', false, '', ''),
            'show_tags' => array('Show Tags', false, false, true),
            'display_description' => array('Display Descriptions', false, false, true),
        );
    }

    function get_url()
    {
        $url = 'http://del.icio.us/rss/'.$this->options['username'];
        if (!empty($this->options['filter_tag'])) $url .= '/'.$this->options['filter_tag'];
        return $url;
    }

    function yield($row)
    {
        $subject =& $row->get_item_tags(SIMPLEPIE_NAMESPACE_DC_11, 'subject');
        $tags = explode(' ', $row->sanitize($subject[0]['data'], SIMPLEPIE_CONSTRUCT_TEXT));

        return array(
            // TODO: can we just use get_date()?
            'date'      =>  $row->get_date('U'),
            'link'      =>  html_entity_decode($row->get_link()),
            'title'     =>  html_entity_decode($row->get_title()),
            'description'   =>  html_entity_decode($row->get_description()),
            'tags'      =>  $tags,
        );
    }
}
register_lifestream_feed('LifeStream_DeliciousFeed');

class LifeStream_LastFMFeed extends LifeStream_Feed
{
    const ID            = 'lastfm';
    const NAME          = 'Last.fm';
    const URL           = 'http://www.last.fm/';
    const LABEL_SINGLE  = 'Scrobbled a song on <a href="%s">%s</a>.';
    const LABEL_PLURAL  = 'Scrobbled %d songs on <a href="%s">%s</a>.';
    
    function __toString()
    {
        return $this->options['username'];
    }
        
    function get_options()
    {        
        return array(
            'username' => array('Username:', true, '', ''),
            'loved' => array('Only show loved tracks.', false, true, true),
        );
    }

    function get_url()
    {
        if ($this->options['loved'])
        {
            $feed_name = 'recentlovedtracks';
        }
        else
        {
            $feed_name = 'recenttracks';
        }
        
        return 'http://ws.audioscrobbler.com/1.0/user/'.$this->options['username'].'/'.$feed_name.'.xml';
    }
    
    function yield($track)
    {
        return array(
            'date'      =>  strtotime($track->date),
            'link'      =>  html_entity_decode($track->url),
            'name'     =>  html_entity_decode($track->name),
            'artist'    =>  html_entity_decode($track->artist),
        );
    }
    
    function fetch()
    {
        // Look it's our first non-feed parser!
        $response = lifestream_file_get_contents($this->get_url());

        if ($response)
        {
            $xml = new SimpleXMLElement($response);
            
            $feed = $xml->track;
            $items = array();
            foreach ($feed as $track)
            {
                $items[] = $this->yield($track);
            }
            return $items;
        }
    }
    
    function render_item($row, $item)
    {
        return sprintf('<a href="%s">%s - %s</a>', $item['link'], $item['artist'], $item['name']);
    }
    
}
register_lifestream_feed('LifeStream_LastFMFeed');

class LifeStream_BlogFeed extends LifeStream_Feed
{
    const ID            = 'blog';
    const NAME          = 'Blog';
    const LABEL_SINGLE  = 'Published a blog post.';
    const LABEL_PLURAL  = 'Published %d blog posts.';

    function get_options()
    {        
        return array(
            'url' => array('Feed URL:', true, '', ''),
            'show_author' => array('Show Author', false, false, true),
        );
    }

    function yield($row)
    {
        $author =& $row->get_item_tags(SIMPLEPIE_NAMESPACE_DC_11, 'creator');

        return array(
            'date'      =>  $row->get_date('U'),
            'link'      =>  html_entity_decode($row->get_link()),
            'title'     =>  html_entity_decode($row->get_title()),
            'author'    =>  $author[0]['data'],
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
    const LABEL_SINGLE  = 'Posted a photo on <a href="%s">%s</a>.';
    const LABEL_PLURAL  = 'Posted %d photos on <a href="%s">%s</a>.';
    
    function get_options()
    {        
        return array(
            'user_id' => array('User ID:', true, '', ''),
        );
    }

    function get_url()
    {
        return 'http://api.flickr.com/services/feeds/photos_public.gne?id='.$this->options['user_id'].'&format=rss_200';
    }

    function yield($row)
    {
        $thumbnail = $event_name =& $row->get_item_tags(self::NAMESPACE, 'thumbnail');
        $thumbnail = $thumbnail[0]['attribs'][''];
        return array(
            'date'      =>  $row->get_date('U'),
            'link'      =>  html_entity_decode($row->get_link()),
            'title'     =>  html_entity_decode($row->get_title()),
            'thumbnail' =>  $thumbnail,
        );
    }
    
    function render_item($row, $item)
    {
        return sprintf('<a href="%s" title="%s"><img src="%s" width="%d" height="%d"/></a>', $item['link'], $item['title'], $item['thumbnail']['url'], $item['thumbnail']['width'], $item['thumbnail']['height']);
    }
    
    function render_group($row)
    {
        $output = array();
        foreach ($row->data as $chunk)
        {
            $output[] = $this->render_item($row, $chunk);
        }
        $id = sprintf('lf_%s', round(microtime(true)*rand(10000,1000000)));
        return sprintf(__($this->get_constant('LABEL_PLURAL'), 'lifestream'), $row->total, $this->get_public_url(), $this->get_public_name()) . ' <small class="lifestream_more">(<a href="#" onclick="lifestream_toggle(this, \'' . $id . '\', \'' . __('Show Details', 'lifestream') . '\', \''. __('Hide Details', 'lifestream') .'\');return false;">' . __('Show Details', 'lifestream') . '</a>)</small><br /><div id="' . $id . '" style="display:none;">' . implode(' ', $output) . '</div>';
    }
}
register_lifestream_feed('LifeStream_FlickrFeed');

class LifeStream_PhotoBucketFeed extends LifeStream_Feed
{
    const ID            = 'photobucket';
    const NAME          = 'Photobucket';
    const URL           = 'http://www.photobucket.com/';
    const LABEL_SINGLE  = 'Posted a photo on <a href="%s">%s</a>.';
    const LABEL_PLURAL  = 'Posted %d photos on <a href="%s">%s</a>.';
}
register_lifestream_feed('LifeStream_PhotoBucketFeed');

class LifeStream_FacebookFeed extends LifeStream_Feed
{
    const ID            = 'facebook';
    const NAME          = 'Facebook';
    const URL           = 'http://www.facebook.com/';
    const DESCRIPTION   = 'To obtain your Facebook feed URL you will need to go your profile and click "See All" under your mini-feed. Once there, click "Status Stories" on the right hand side. On the right hand side of the next page you will the "My Status" RSS feed link.';
    const CAN_GROUP     = false;
    const LABEL_SINGLE  = 'Updated status on <a href="%s">%s</a>.';
    const LABEL_PLURAL  = 'Updated status %d times on <a href="%s">%s</a>.';
    
    function render_item($row, $item)
    {
        return $item['title'];
    }
}
register_lifestream_feed('LifeStream_FacebookFeed');

class LifeStream_PownceFeed extends LifeStream_TwitterFeed
{
    const NAMESPACE     = 'http://pownce.com/Atom';
    const ID            = 'pownce';
    const NAME          = 'Pownce';
    const URL           = 'http://www.pownce.com/';
    const LABEL_SINGLE  = 'Posted a note on <a href="%s">%s</a>.';
    const LABEL_PLURAL  = 'Posted %d notes on <a href="%s">%s</a>.';
    
    function get_url()
    {
        return 'http://www.pownce.com/feeds/public/'.$this->options['username'].'/';
    }
    
    function get_user_link($user)
    {
        return '<a href="http://www.pownce.com/'.$user.'" class="user">@'.$user.'</a>';
    }
    
    function render_item($row, $item)
    {
        return $this->parse_users($this->parse_urls($item['description']));
    }

    function yield($row)
    {
        $data = array(
            'date'      =>  $row->get_date('U'),
            'link'      =>  html_entity_decode($row->get_link()),
            'description'   =>  html_entity_decode($row->get_description()),
        );
        
        if ($event_name =& $row->get_item_tags(self::NAMESPACE, 'event_name'))
        {
            $data['event'] = array();
            $data['event']['name'] = html_entity_decode($event_name[0]['data']);
            
            if ($event_location =& $row->get_item_tags(self::NAMESPACE, 'event_location'))
                $data['event']['location'] = html_entity_decode($event_location[0]['data']);

            if ($event_date =& $row->get_item_tags('pownce', 'event_date'))
                $data['event']['date'] = html_entity_decode($event_date[0]['data']);
        }
        return $data;
    }
}
register_lifestream_feed('LifeStream_PownceFeed');

class LifeStream_DiggFeed extends LifeStream_Feed
{
    const ID            = 'digg';
    const NAME          = 'Digg';
    const URL           = 'http://www.digg.com/';
    const LABEL_SINGLE  = 'Dugg a link on <a href="%s">%s</a>.';
    const LABEL_PLURAL  = 'Dugg %d links on <a href="%s">%s</a>.';
    
    function __toString()
    {
        return $this->options['username'];
    }
    
    function get_options()
    {        
        return array(
            'username' => array('Username:', true, '', ''),
        );
    }
    
    function get_url()
    {
        return 'http://www.digg.com/users/'.$this->options['username'].'/history.rss';
    }
}
register_lifestream_feed('LifeStream_DiggFeed');

class LifeStream_YouTubeFeed extends LifeStream_Feed
{
    const NAMESPACE     = 'http://search.yahoo.com/mrss/';
    const ID            = 'youtube';
    const NAME          = 'YouTube';
    const URL           = 'http://www.youtube.com/';
    const LABEL_SINGLE  = 'Posted a video on <a href="%s">%s</a>.';
    const LABEL_PLURAL  = 'Posted %d videos on <a href="%s">%s</a>.';
    
    function __toString()
    {
        return $this->options['username'];
    }
    
    function get_options()
    {
        return array(
            'username' => array('Username:', true, '', ''),
        );
    }
    
    function get_url()
    {
        return 'http://www.youtube.com/ut_rss?type=username&arg='.$this->options['username'];
    }
}
register_lifestream_feed('LifeStream_YouTubeFeed');

class LifeStream_RedditFeed extends LifeStream_Feed
{
    const ID            = 'reddit';
    const NAME          = 'Reddit';
    const URL           = 'http://www.reddit.com/';
    const LABEL_SINGLE  = 'Found an interesting link on <a href="%s">%s</a>.';
    const LABEL_PLURAL  = 'Found %d interesting links on <a href="%s">%s</a>.';
    
    function __toString()
    {
        return $this->options['username'];
    }

    function get_options()
    {        
        return array(
            'username' => array('Username:', true, '', ''),
        );
    }
    
    function get_url()
    {
        return 'http://www.reddit.com/user/'.$this->options['username'].'/.rss';
    }

    function yield($row)
    {
        $title = $row->get_title();
        
        $chunk = sprintf('%s on', $this->options['username']);
        if (str_startswith($title, $chunk))
            $title = substr($title, strlen($chunk));
        
        return array(
            'date'      =>  $row->get_date('U'),
            'link'      =>  html_entity_decode($row->get_link()),
            'title'     =>  html_entity_decode($title),
        );
    }
}
register_lifestream_feed('LifeStream_RedditFeed');

class LifeStream_GoogleReaderFeed extends LifeStream_Feed
{
    const ID            = 'googlereader';
    const NAME          = 'Google Reader';
    const URL           = 'http://www.google.com/reader/';
    const DESCRIPTION   = 'Your Google Reader feed URL is available by going to "Share items" under "Your stuff". From there follow the link "See your shared items page in a new window.". On this page your feed URL will be available in any browser which shows you RSS feeds. It should look something like this: http://www.google.com/reader/public/atom/user/14317428968164573500/state/com.google/broadcast';
    const LABEL_SINGLE  = 'Shared a link on <a href="%s">%s</a>.';
    const LABEL_PLURAL  = 'Shared %d links on <a href="%s">%s</a>.';
}
register_lifestream_feed('LifeStream_GoogleReaderFeed');

class LifeStream_YelpFeed extends LifeStream_Feed
{
    const ID            = 'yelp';
    const NAME          = 'Yelp';
    const URL           = 'http://www.yelp.com/';
    const DESCRIPTION   = 'You can obtain your Yelp RSS feed url from your profile page. It should look something like this: http://www.yelp.com/syndicate/user/ctwwsl5_DSCzwPxtjzdl2A/rss.xml';
    const LABEL_SINGLE  = 'Reviewed a business on <a href="%s">%s</a>.';
    const LABEL_PLURAL  = 'Reviewed %d businesses on <a href="%s">%s</a>.';
    
    function yield($row)
    {
        $title = $row->get_title();
        
        $on_part = ' on Yelp.com';
        if (substr($title, strlen($title)-strlen($on_part)) == $on_part)
            $title = substr($title, 0, strlen($title)-strlen($on_part));
        
        return array(
            'date'      =>  $row->get_date('U'),
            'link'      =>  html_entity_decode($row->get_link()),
            'title'     =>  html_entity_decode($title),
        );
    }
}
register_lifestream_feed('LifeStream_YelpFeed');

class LifeStream_MySpaceFeed extends LifeStream_BlogFeed
{
    const ID            = 'myspace';
    const NAME          = 'MySpace';
    const URL           = 'http://www.myspace.com/';
    const DESCRIPTION   = 'To retrieve your MySpace blog URL, visit your profile and click "View all entries" under your blog. From there, you will see an "rss" link on the top right of the page.';
    const LABEL_SINGLE  = 'Published a blog post on <a href="%s">%s</a>.';
    const LABEL_PLURAL  = 'Published %d blog posts on <a href="%s">%s</a>.';
}
register_lifestream_feed('LifeStream_MySpaceFeed');

class LifeStream_SkitchFeed extends LifeStream_Feed
{
    const ID            = 'skitch';
    const NAME          = 'Skitch';
    const URL           = 'http://www.skitch.com/';
    const LABEL_SINGLE  = 'Shared an image on <a href="%s">%s</a>.';
    const LABEL_PLURAL  = 'Shared %d images on <ah ref="%s">%s</a>.';
    
    function __toString()
    {
        return $this->options['username'];
    }

    function get_options()
    {        
        return array(
            'username' => array('Username:', true, '', ''),
        );
    }
    
    function get_url()
    {
        return 'http://skitch.com/feeds/'.$this->options['username'].'/atom.xml';
    }
}
register_lifestream_feed('LifeStream_SkitchFeed');

class LifeStream_IdenticaFeed extends LifeStream_TwitterFeed
{
    const ID            = 'identica';
    const NAME          = 'Identi.ca';
    const URL           = 'http://www.identi.ca/';
    const LABEL_SINGLE  = 'Posted a dent on <a href="%s">%s</a>.';
    const LABEL_PLURAL  = 'Posted %d dents on <a href="%s">%s</a>.';

    function get_user_link($user)
    {
        return '<a href="http://www.identi.ca/'.$user.'" class="user">@'.$user.'</a>';
    }

    function get_url()
    {
        return 'http://identi.ca/'.$this->options['username'].'/rss';
    }
    
    function yield($row)
    {
        return array(
            'date'      =>  $row->get_date('U'),
            'link'      =>  html_entity_decode($row->get_link()),
            'title'     =>  html_entity_decode($row->get_title()),
        );
    }
}
register_lifestream_feed('LifeStream_IdenticaFeed');

?>