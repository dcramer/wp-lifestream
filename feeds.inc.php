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
    const LABEL_SINGLE_USER = '<a href="%s">%s</a> posted a tweet on <a href="%s">%s</a>.';
    const LABEL_PLURAL_USER = '<a href="%s">%s</a> posted %d tweets on <a href="%s">%s</a>.';
    
    function __toString()
    {
        return $this->options['username'];
    }

    function get_options()
    {        
        return array(
            'username' => array('Username:', true, '', ''),
            'link_urls' => array('Convert URLs to links.', false, true, true),
            'link_users' => array('Convert @username to links.', false, true, true),
        );
    }
    
    function _get_user_link($match)
    {
        return $this->get_user_link($match[1]);
    }
    
    function get_user_link($user)
    {
        return '<a href="'.$this->get_user_url($user).'" class="user">@'.$user.'</a>';
    }
    
    function get_user_url($user)
    {
        return 'http://www.twitter.com/'.$user;
    }
    
    function get_public_url()
    {
        return $this->get_user_url($this->options['username']);
    }

    function parse_users($text)
    {
        return preg_replace_callback('/[^\w]*@([a-z0-9_-]+)\b/i', array($this, '_get_user_link'), $text);
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
    const LABEL_SINGLE_USER = '<a href="%s">%s</a> posted a Jaiku on <a href="%s">%s</a>.';
    const LABEL_PLURAL_USER = '<a href="%s">%s</a> posted %d Jaikus on <a href="%s">%s</a>.';
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
    const LABEL_SINGLE_USER = '<a href="%s">%s</a> bookmarked a link on <a href="%s">%s</a>.';
    const LABEL_PLURAL_USER = '<a href="%s">%s</a> bookmarked %d links on <a href="%s">%s</a>.';

    function __toString()
    {
        return $this->options['username'];
    }
        
    function get_options()
    {        
        return array(
            'username' => array('Username:', true, '', ''),
            'filter_tag' => array('Limit items to tag:', false, '', ''),
            'show_tags' => array('Show tags with links.', false, false, true),
            'display_description' => array('Display descriptions of links.', false, false, true),
        );
    }

    function get_url()
    {
        $url = 'http://del.icio.us/rss/'.$this->options['username'];
        if (!empty($this->options['filter_tag'])) $url .= '/'.$this->options['filter_tag'];
        return $url;
    }
    
    function get_public_url()
    {
        return 'http://del.icio.us/'.$this->options['username'];
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
    const LABEL_SINGLE_USER = '<a href="%s">%s</a> scrobbled a song on <a href="%s">%s</a>.';
    const LABEL_PLURAL_USER = '<a href="%s">%s</a> scrobbled %d songs on <a href="%s">%s</a>.';
    
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
    
    function get_public_url()
    {
        return 'http://www.last.fm/user/'.$this->options['username'];
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
            'name'      =>  html_entity_decode($track->name),
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
    const LABEL_SINGLE_USER = '<a href="%s">%s</a> published a blog post.';
    const LABEL_PLURAL_USER = '<a href="%s">%s</a> published %d blog posts.';

    function get_options()
    {        
        return array(
            'url' => array('Feed URL:', true, '', ''),
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

class LifeStream_FlickrFeed extends LifeStream_PhotoFeed
{
    const ID            = 'flickr';
    const NAME          = 'Flickr';
    const URL           = 'http://www.flickr.com/';
    const DESCRIPTION   = 'You can find your User ID by using <a href="http://idgettr.com/">idGettr</a>.';
    const NAMESPACE     = 'http://search.yahoo.com/mrss/';
     
    function get_options()
    {        
        return array(
            'user_id' => array('User ID:', true, '', ''),
        );
    }
    
    function get_public_url()
    {
        return 'http://www.flickr.com/photos/'.$this->options['username'].'/';
    }

    function get_url()
    {
        return 'http://api.flickr.com/services/feeds/photos_public.gne?id='.$this->options['user_id'].'&format=rss_200';
    }

    function yield($row)
    {
        $thumbnail = $row->get_item_tags(self::NAMESPACE, 'thumbnail');
        $thumbnail = $thumbnail[0]['attribs'][''];
        $image = $row->get_item_tags(self::NAMESPACE, 'content');
        $image = $image[0]['attribs'][''];
        return array(
            'date'      =>  $row->get_date('U'),
            'link'      =>  html_entity_decode($row->get_link()),
            'title'     =>  html_entity_decode($row->get_title()),
            'thumbnail' =>  $thumbnail['url'],
            'image'     =>  str_replace('_m', '', $image['url']),
        );
    }
    
    function render_item($row, $item)
    {
        // Maintain backwards compatibility.
        $thumbnail = is_array($item['thumbnail']) ? $item['thumbnail']['url'] : $item['thumbnail'];
        if (isset($item['image']))
        {
            $image = is_array($item['image']) ? str_replace('_m', '', $item['image']['url']) : $item['image'];
        }
        else
        {
            $image = null;
        }
        
        if (get_option('lifestream_use_ibox') == '1' && $item['image'])
        {
            // change it to be large size images
            $ibox = ' rel="ibox&target=\''.$image.'\'"';
        }
        else $ibox = '';
        
        return sprintf('<a href="%s" class="photo" title="%s"'.$lightbox.'><img src="%s" width="50"/></a>', htmlspecialchars($item['link']), $item['title'], $thumbnail);
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
    const LABEL_SINGLE_USER = '<a href="%s">%s</a> posted a photo on <a href="%s">%s</a>.';
    const LABEL_PLURAL_USER = '<a href="%s">%s</a> posted %d photos on <a href="%s">%s</a>.';
}
register_lifestream_feed('LifeStream_PhotoBucketFeed');

class LifeStream_FacebookFeed extends LifeStream_Feed
{
    const ID            = 'facebook';
    const NAME          = 'Facebook';
    const URL           = 'http://www.facebook.com/';
    const DESCRIPTION   = 'To obtain your Facebook feed URL you will need to go your profile and click "See All" under your mini-feed. Once there, click "Status Stories" on the right hand side. On the right hand side of the next page you will the "My Status" RSS feed link.';
    const CAN_GROUP     = false;
    // Plurals aren't used since can_group is false, but might as well.
    const LABEL_SINGLE  = 'Updated status on <a href="%s">%s</a>.';
    const LABEL_PLURAL  = 'Updated status %d times on <a href="%s">%s</a>.';
    const LABEL_SINGLE_USER = '<a href="%s">%s</a> updated their status on <a href="%s">%s</a>.';
    const LABEL_PLURAL_USER = '<a href="%s">%s</a> updated their status %d times on <a href="%s">%s</a>.';
    
    function render_item($row, $item)
    {
        return $item['title'];
    }
}
register_lifestream_feed('LifeStream_FacebookFeed');

class LifeStream_PownceFeed extends LifeStream_TwitterFeed
{
    // TODO: change labels based on type (event, file, url, note)
    const NAMESPACE     = 'http://pownce.com/Atom';
    const ID            = 'pownce';
    const NAME          = 'Pownce';
    const URL           = 'http://www.pownce.com/';
    const LABEL_SINGLE  = 'Posted a note on <a href="%s">%s</a>.';
    const LABEL_PLURAL  = 'Posted %d notes on <a href="%s">%s</a>.';
    const LABEL_SINGLE_USER = '<a href="%s">%s</a> posted a note on <a href="%s">%s</a>.';
    const LABEL_PLURAL_USER = '<a href="%s">%s</a> posted %d notes on <a href="%s">%s</a>.';
    
    function get_url()
    {
        return 'http://www.pownce.com/feeds/public/'.$this->options['username'].'/';
    }
    
    function get_user_url($user)
    {
        return 'http://www.pownce.com/'.$user.'/';
    }
    
    function yield($row)
    {
        $category = $row->get_category();

        if ($category) $key = $category->get_label();
        else $key = 'note';

        $data = array(
            'date'      =>  $row->get_date('U'),
            'link'      =>  html_entity_decode($row->get_link()),
            'description'   =>  html_entity_decode($row->get_description()),
            'key'       =>  $key,
        );
        
        if ($key == 'link')
        {
            $data['relurl'] = $row->get_link(0, 'related');
        }
        elseif ($key == 'event')
        {
            if ($event_name =& $row->get_item_tags(self::NAMESPACE, 'event_name'))
            {
                $data['event'] = array();
                $data['event']['name'] = html_entity_decode($event_name[0]['data']);

                if ($event_location =& $row->get_item_tags(self::NAMESPACE, 'event_location'))
                    $data['event']['location'] = html_entity_decode($event_location[0]['data']);

                if ($event_date =& $row->get_item_tags('pownce', 'event_date'))
                    $data['event']['date'] = strototime($event_date[0]['data']);
            }
        }
        return $data;
    }
    
    function render_item($event, $item)
    {
        if ($event->key == 'event')
        {
            return sprintf('<a href="%s">%s</a>', $item['link'], $item['description']);
        }
        elseif ($event->key == 'link')
        {
            return sprintf('<a href="%s">%s</a>', $item['relurl'], $item['description']);
        }
        else
        {
            return $this->parse_users($this->parse_urls($item['description']));
        }
    }
    
    function get_label_single($key)
    {
        if ($key == 'event')
        {
            $label = 'Posted an event on <a href="%s">%s</a>.';
        }
        elseif ($key == 'link')
        {
            $label = 'Posted a link on <a href="%s">%s</a>.';
        }
        else
        {
            $label = 'Posted a note on <a href="%s">%s</a>.';
        }
        return $label;
    }

    function get_label_plural($key)
    {
        if ($key == 'event')
        {
            $label = 'Posted %d events on <a href="%s">%s</a>.';
        }
        elseif ($key == 'link')
        {
            $label = 'Posted %d links on <a href="%s">%s</a>.';
        }
        else
        {
            $label = 'Posted %d notes on <a href="%s">%s</a>.';
        }
        return $label;
    }
    
    function get_label_single_user($key)
    {
        if ($key == 'event')
        {
            $label = '<a href="%s">%s</a> posted an event on <a href="%s">%s</a>.';
        }
        elseif ($key == 'link')
        {
            $label = '<a href="%s">%s</a> posted a link on <a href="%s">%s</a>.';
        }
        else
        {
            $label = '<a href="%s">%s</a> posted a note on <a href="%s">%s</a>.';
        }
        return $label;
    }

    function get_label_plural_user($key)
    {
        if ($key == 'event')
        {
            $label = '<a href="%s">%s</a> posted %d events on <a href="%s">%s</a>.';
        }
        elseif ($key == 'link')
        {
            $label = '<a href="%s">%s</a> posted %d links on <a href="%s">%s</a>.';
        }
        else
        {
            $label = '<a href="%s">%s</a> posted %d notes on <a href="%s">%s</a>.';
        }
        return $label;
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
    const LABEL_SINGLE_USER = '<a href="%s">%s</a> dugg a link on <a href="%s">%s</a>.';
    const LABEL_PLURAL_USER = '<a href="%s">%s</a> dugg %d links on <a href="%s">%s</a>.';
    
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
    
    function get_public_url()
    {
        return 'http://www.digg.com/users/'.$this->options['username'];
    }
    
    function get_url()
    {
        return 'http://www.digg.com/users/'.$this->options['username'].'/history.rss';
    }
}
register_lifestream_feed('LifeStream_DiggFeed');

class LifeStream_YouTubeFeed extends LifeStream_FlickrFeed
{
    const ID            = 'youtube';
    const NAME          = 'YouTube';
    const URL           = 'http://www.youtube.com/';
    const LABEL_SINGLE  = 'Posted a video on <a href="%s">%s</a>.';
    const LABEL_PLURAL  = 'Posted %d videos on <a href="%s">%s</a>.';
    const LABEL_SINGLE_USER = '<a href="%s">%s</a> posted a video on <a href="%s">%s</a>.';
    const LABEL_PLURAL_USER = '<a href="%s">%s</a> posted %d videos on <a href="%s">%s</a>.';
    const DESCRIPTION   = '';
    
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
    
    function get_public_url()
    {
        return 'http://www.youtube.com/user/'.$this->options['username'];
    }
    
    function get_url()
    {
        return 'http://www.youtube.com/rss/user/'.$this->options['username'].'/videos.rss';
    }
    
    function yield($row)
    {
        $thumbnail = $row->get_item_tags(self::NAMESPACE, 'thumbnail');
        $thumbnail = $thumbnail[0]['attribs'][''];
        return array(
            'date'      =>  $row->get_date('U'),
            'link'      =>  html_entity_decode($row->get_link()),
            'title'     =>  html_entity_decode($row->get_title()),
            'thumbnail' =>  $thumbnail['url'],
        );
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
    const LABEL_SINGLE_USER = '<a href="%s">%s</a> found an interesting link on <a href="%s">%s</a>.';
    const LABEL_PLURAL_USER = '<a href="%s">%s</a> found %d interesting links on <a href="%s">%s</a>.';
    
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
    
    function get_public_url()
    {
        return 'http://www.reddit.com/user/'.$this->options['username'].'/';
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
    const LABEL_SINGLE_USER = '<a href="%s">%s</a> shared a link on <a href="%s">%s</a>.';
    const LABEL_PLURAL_USER = '<a href="%s">%s</a> shared %d links on <a href="%s">%s</a>.';
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
    const LABEL_SINGLE_USER = '<a href="%s">%s</a> reviewed a business on <a href="%s">%s</a>.';
    const LABEL_PLURAL_USER = '<a href="%s">%s</a> reviewed %d businesses on <a href="%s">%s</a>.';
    
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
    const LABEL_SINGLE_USER = '<a href="%s">%s</a> published a blog post on <a href="%s">%s</a>.';
    const LABEL_PLURAL_USER = '<a href="%s">%s</a> published %d blog posts on <a href="%s">%s</a>.';
    
}
register_lifestream_feed('LifeStream_MySpaceFeed');

class LifeStream_SkitchFeed extends LifeStream_FlickrFeed
{
    const ID            = 'skitch';
    const NAME          = 'Skitch';
    const URL           = 'http://www.skitch.com/';
    const LABEL_SINGLE  = 'Shared an image on <a href="%s">%s</a>.';
    const LABEL_PLURAL  = 'Shared %d images on <a href="%s">%s</a>.';
    const LABEL_SINGLE_USER = '<a href="%s">%s</a> shared an image on <a href="%s">%s</a>.';
    const LABEL_PLURAL_USER = '<a href="%s">%s</a> shared %d images on <a href="%s">%s</a>.';
    const DESCRIPTION   = '';
    
    private $image_match_regexp = '/src="(http\:\/\/img+\.skitch\.com\/[^"]+\.jpg)"/i';
    
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
    
    function get_public_url()
    {
        return 'http://www.skitch.com/'.$this->options['username'].'/';
    }
    
    function get_url()
    {
        return 'http://www.skitch.com/feeds/'.$this->options['username'].'/atom.xml';
    }
    
    function yield($item)
    {
        preg_match($this->image_match_regexp, $item->get_description(), $match);
        return array(
            'date'      =>  $item->get_date('U'),
            'link'      =>  html_entity_decode($item->get_link()),
            'title'     =>  html_entity_decode($item->get_title()),
            'thumbnail' =>  $match[1],
            'image'     =>  str_replace('.preview.', '', $match[1]),
        );
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
    const LABEL_SINGLE_USER = '<a href="%s">%s</a> posted a dent on <a href="%s">%s</a>.';
    const LABEL_PLURAL_USER = '<a href="%s">%s</a> posted %d dents on <a href="%s">%s</a>.';

    function get_user_url($user)
    {
        return 'http://www.identi.ca/'.$user;
    }

    function get_url()
    {
        return 'http://identi.ca/'.$this->options['username'].'/rss';
    }
}
register_lifestream_feed('LifeStream_IdenticaFeed');


class LifeStream_PandoraFeed extends LifeStream_Feed
{
    const ID            = 'pandora';
    const NAME          = 'Pandora';
    const URL           = 'http://www.pandora.com/';
    const NAMESPACE     = 'http://musicbrainz.org/mm/mm-2.1#';
    const DESCRIPTION   = 'Your username is available from your profile page. For example, if your profile page has a url of http://www.pandora.com/people/foobar32 then your username is foobar32.';
    
    function __toString()
    {
        return $this->options['username'];
    }
    
    function get_options()
    {
        return array(
            'username' => array('Username:', true, '', ''),
            'show_stations' => array('Include stations in this feed.', false, true, true),
            'show_bookmarked_artists' => array('Include bookmarked artists in this feed.', false, true, true),
            'show_bookmarked_songs' => array('Include bookmarked songs in this feed.', false, true, true),
        );
    }
    
    function get_label_single($key)
    {
        if ($key == 'bookmarksong')
        {
            $label = 'Bookmarked a song on <a href="%s">%s</a>.';
        }
        elseif ($key == 'bookmarkartist')
        {
            $label = 'Bookmarked an artist on <a href="%s">%s</a>.';
        }
        elseif ($key == 'station')
        {
            $label = 'Added a station on <a href="%s">%s</a>.';
        }
        return $label;
    }

    function get_label_plural($key)
    {
        if ($key == 'bookmarksong')
        {
            $label = 'Bookmarked %d songs on <a href="%s">%s</a>.';
        }
        elseif ($key == 'bookmarkartist')
        {
            $label = 'Bookmarked %d artists on <a href="%s">%s</a>.';
        }
        elseif ($key == 'station')
        {
            $label = 'Added %d stations on <a href="%s">%s</a>.';
        }
        return $label;
    }
    
    function get_label_single_user($key)
    {
        if ($key == 'bookmarksong')
        {
            $label = '<a href="%s">%s</a> bookmarked a song on <a href="%s">%s</a>.';
        }
        elseif ($key == 'bookmarkartist')
        {
            $label = '<a href="%s">%s</a> bookmarked an artist on <a href="%s">%s</a>.';
        }
        elseif ($key == 'station')
        {
            $label = '<a href="%s">%s</a> added a station on <a href="%s">%s</a>.';
        }
        return $label;
    }

    function get_label_plural_user($key)
    {
        if ($key == 'bookmarksong')
        {
            $label = '<a href="%s">%s</a> bookmarked %d songs on <a href="%s">%s</a>.';
        }
        elseif ($key == 'bookmarkartist')
        {
            $label = '<a href="%s">%s</a> bookmarked %d artists on <a href="%s">%s</a>.';
        }
        elseif ($key == 'station')
        {
            $label = '<a href="%s">%s</a> added %d stations on <a href="%s">%s</a>.';
        }
        return $label;
    }
    
    function get_stations_url()
    {
        return 'http://feeds.pandora.com/feeds/people/'.$this->options['username'].'/stations.xml';
    }
    
    function get_artists_url()
    {
            return 'http://feeds.pandora.com/feeds/people/'.$this->options['username'].'/favoriteartists.xml';
    }
    
    function get_songs_url()
    {
        return 'http://feeds.pandora.com/feeds/people/'.$this->options['username'].'/favorites.xml';
    }

    function get_public_url()
    {
        return 'http://www.pandora.com/people/'.$this->options['username'];
    }

    function get_url()
    {
        $urls = array();
        if ($this->options['show_stations'])
        {
            $urls[] = array($this->get_stations_url(), 'station');
        }
        if ($this->options['show_bookmarked_artists'])
        {
            $urls[] = array($this->get_artists_url(), 'bookmarkartist');
        }
        if ($this->options['show_bookmarked_songs'])
        {
            $urls[] = array($this->get_songs_url(), 'bookmarksong');
        }
        return $urls;
    }
}
register_lifestream_feed('LifeStream_PandoraFeed');

class LifeStream_HuluFeed extends LifeStream_Feed
{
    const ID            = 'hulu';
    const NAME          = 'Hulu';
    const URL           = 'http://www.hulu.com/';
    const LABEL_SINGLE  = 'Watched a video on <a href="%s">%s</a>.';
    const LABEL_PLURAL  = 'Watched %d videos on <a href="%s">%s</a>.';
    const LABEL_SINGLE_USER = '<a href="%s">%s</a> watched a video on <a href="%s">%s</a>.';
    const LABEL_PLURAL_USER = '<a href="%s">%s</a> watched %d videos on <a href="%s">%s</a>.';
    const DESCRIPTION   = 'You can obtain your history feed by visiting <a href="http://www.hulu.com/users/history">here</a> and clicking the RSS icon at the top of the page. You may first need to change your privacy settings for the feed to be viewable.';
}
register_lifestream_feed('LifeStream_HuluFeed');

class LifeStream_FireEagleFeed extends LifeStream_Feed
{
    const ID            = 'fireeagle';
    const NAME          = 'Fire Eagle';
    const URL           = 'http://fireeagle.yahoo.net/';
    const LABEL_SINGLE  = 'Updated location on <a href="%s">%s</a>.';
    const LABEL_PLURAL  = 'Updated location %d times on <a href="%s">%s</a>.';
    const LABEL_SINGLE_USER = '<a href="%s">%s</a> updated their location on <a href="%s">%s</a>.';
    const LABEL_PLURAL_USER = '<a href="%s">%s</a> updated their location %d times on <a href="%s">%s</a>.';

    protected $fe_key = 'cb91fb5dQsGd';
    protected $fe_secret = 'uZyxnMZS2UsgAIwnf3BuzZKWhGjMqWqt';

    function main()
    {
        ob_start();

        if (@$_GET['f'] == 'start') {
            // get a request token + secret from FE and redirect to the authorization page
            $fe = new FireEagle($this->fe_key, $this->fe_secret);
            $tok = $fe->getRequestToken();
            if (!isset($tok['oauth_token'])
                || !is_string($tok['oauth_token'])
                || !isset($tok['oauth_token_secret'])
                || !is_string($tok['oauth_token_secret'])) {
             echo "ERROR! FireEagle::getRequestToken() returned an invalid response. Giving up.";
             exit;
            }
            $_SESSION['auth_state'] = "start";
            $_SESSION['request_token'] = $token = $tok['oauth_token'];
            $_SESSION['request_secret'] = $tok['oauth_token_secret'];
            header("Location: ".$fe->getAuthorizeURL($token));
        }
        else if (@$_GET['f'] == 'callback')
        {
            // the user has authorized us at FE, so now we can pick up our access token + secret
            if (@$_SESSION['auth_state'] != "start")
            {
                echo "Out of sequence.";
                exit;
            }
            if ($_GET['oauth_token'] != $_SESSION['request_token'])
            {
                echo "Token mismatch.";
                exit;
            }

            $fe = new FireEagle($this->fe_key, $this->fe_secret, $_SESSION['request_token'], $_SESSION['request_secret']);
            $tok = $fe->getAccessToken();
            if (!isset($tok['oauth_token']) || !is_string($tok['oauth_token'])
                || !isset($tok['oauth_token_secret'])
                || !is_string($tok['oauth_token_secret']))
            {
                    error_log("Bad token from FireEagle::getAccessToken(): ".var_export($tok, TRUE));
                    echo "ERROR! FireEagle::getAccessToken() returned an invalid response. Giving up.";
                    exit;
            }

            $_SESSION['access_token'] = $tok['oauth_token'];
            $_SESSION['access_secret'] = $tok['oauth_token_secret'];
            $_SESSION['auth_state'] = "done";
            header("Location: ".$_SERVER['SCRIPT_NAME']);
        }
        else if (@$_SESSION['auth_state'] == 'done')
        {
            // we have our access token + secret, so now we can actually *use* the api
            $fe = new FireEagle($this->fe_key, $this->fe_secret, $_SESSION['access_token'], $_SESSION['access_secret']);
            $location = $fe->user();
            if ($location->user->best_guess)
            {
                $location->user->best_guess->name;
            }
        }
    }
}
//register_lifestream_feed('LifeStream_FireEagleFeed');

class LifeStream_TwitPicFeed extends LifeStream_PhotoFeed
{
    const ID            = 'twitpic';
    const NAME          = 'TwitPic';
    const URL           = 'http://www.twitpic.com/';
    
    function get_options()
    {        
        return array(
            'username' => array('Username:', true, '', ''),
        );
    }
    
    function get_public_url()
    {
        return 'http://www.twitpic.com/photos/'.$this->options['username'];
    }

    function get_url()
    {
        return 'http://www.twitpic.com/photos/'.$this->options['username'].'/feed.rss';
    }

    function yield($row)
    {
        return array(
            'date'      =>  $row->get_date('U'),
            'link'      =>  html_entity_decode($row->get_link()),
            'title'     =>  html_entity_decode($row->get_title()),
            'thumbnail' =>  html_entity_decode($row->get_link()).'-thumb.jpg',
        );
    }
}
register_lifestream_feed('LifeStream_TwitPicFeed');

class LifeStream_VimeoFeed extends LifeStream_PhotoFeed
{
    const ID            = 'vimeo';
    const NAME          = 'Vimeo';
    const URL           = 'http://www.vimeo.com/';
    const DESCRIPTION   = 'Your user ID is the digits at the end of your profile URL. For example, if your profile is <strong>http://www.vimeo.com/user406516</strong> then your user ID is <strong>406516</strong>.';
    
    private $image_match_regexp = '/src="(http\:\/\/[a-z0-9]+\.vimeo\.com\/[^"]+)"/i';
    
    function __toString()
    {
        return $this->options['user_id'];
    }
    
    function get_options()
    {
        return array(
            'user_id' => array('User ID:', true, '', ''),
            'show_videos' => array('Include videos posted in this feed.', false, true, true),
            'show_likes' => array('Include liked videos in this feed.', false, true, true),
        );
    }
    
    function get_label_single($key)
    {
        if ($key == 'video')
        {
            $label = 'Posted a video on <a href="%s">%s</a>.';
        }
        elseif ($key == 'like')
        {
            $label = 'Liked a video on <a href="%s">%s</a>.';
        }
        return $label;
    }

    function get_label_plural($key)
    {
        if ($key == 'video')
        {
            $label = 'Posted %d videos on <a href="%s">%s</a>.';
        }
        elseif ($key == 'like')
        {
            $label = 'Liked %d videos on <a href="%s">%s</a>.';
        }
        return $label;
    }
    
    function get_label_single_user($key)
    {
        if ($key == 'video')
        {
            $label = '<a href="%s">%s</a> posted a video on <a href="%s">%s</a>.';
        }
        elseif ($key == 'like')
        {
            $label = '<a href="%s">%s</a> liked a video on <a href="%s">%s</a>.';
        }
        return $label;
    }

    function get_label_plural_user($key)
    {
        if ($key == 'video')
        {
            $label = '<a href="%s">%s</a> posted %d videos on <a href="%s">%s</a>.';
        }
        elseif ($key == 'like')
        {
            $label = '<a href="%s">%s</a> liked %d videos on <a href="%s">%s</a>.';
        }
        return $label;
    }
    
    function get_videos_url()
    {
        return 'http://www.vimeo.com/user'.$this->options['user_id'].'/videos/rss';
    }
    
    function get_likes_url()
    {
        return 'http://www.vimeo.com/user'.$this->options['user_id'].'/likes/rss';
    }

    function get_public_url()
    {
        return 'http://www.vimeo.com/user'.$this->options['user_id'];
    }

    function get_url()
    {
        $urls = array();
        if ($this->options['show_videos'])
        {
            $urls[] = array($this->get_videos_url(), 'video');
        }
        if ($this->options['show_likes'])
        {
            $urls[] = array($this->get_likes_url(), 'like');
        }
        return $urls;
    }
    
    function yield($item)
    {
        preg_match($this->image_match_regexp, $item->get_description(), $match);
        return array(
            'date'      =>  $item->get_date('U'),
            'link'      =>  html_entity_decode($item->get_link()),
            'title'     =>  html_entity_decode($item->get_title()),
            'thumbnail' =>  $match[1],
        );
    }
}
register_lifestream_feed('LifeStream_VimeoFeed');

class LifeStream_StumbleUponFeed extends LifeStream_PhotoFeed
{
    const ID            = 'stumbleupon';
    const NAME          = 'StumbleUpon';
    const URL           = 'http://www.stumbleupon.com/';
    
    function __toString()
    {
        return $this->options['username'];
    }
    
    function get_options()
    {
        return array(
            'username' => array('Username:', true, '', ''),
            'show_reviews' => array('Include reviews in this feed.', false, true, true),
            'show_favorites' => array('Include favorites in this feed.', false, true, false),
        );
    }
    
    function get_label_single($key)
    {
        if ($key == 'review')
        {
            $label = 'Reviewed a website on <a href="%s">%s</a>.';
        }
        elseif ($key == 'favorite')
        {
            $label = 'Favorited a website on <a href="%s">%s</a>.';
        }
        return $label;
    }

    function get_label_plural($key)
    {
        if ($key == 'review')
        {
            $label = 'Reviewed %d websites on <a href="%s">%s</a>.';
        }
        elseif ($key == 'favorite')
        {
            $label = 'Favorited %d websites on <a href="%s">%s</a>.';
        }
        return $label;
    }
    
    function get_label_single_user($key)
    {
        if ($key == 'review')
        {
            $label = '<a href="%s">%s</a> reviewed a website on <a href="%s">%s</a>.';
        }
        elseif ($key == 'favorite')
        {
            $label = '<a href="%s">%s</a> favorited a website on <a href="%s">%s</a>.';
        }
        return $label;
    }

    function get_label_plural_user($key)
    {
        if ($key == 'review')
        {
            $label = '<a href="%s">%s</a> reviewed %d websites on <a href="%s">%s</a>.';
        }
        elseif ($key == 'favorite')
        {
            $label = '<a href="%s">%s</a> favorited %d websites on <a href="%s">%s</a>.';
        }
        return $label;
    }
    
    function get_favorites_url()
    {
        return 'http://rss.stumbleupon.com/user/'.$this->options['username'].'/favorites';
    }
    
    function get_reviews_url()
    {
        return 'http://rss.stumbleupon.com/user/'.$this->options['username'].'/reviews';
    }

    function get_public_url()
    {
        return 'http://'.$this->options['username'].'.stumbleupon.com';
    }

    function get_url()
    {
        $urls = array();
        if ($this->options['show_reviews'])
        {
            $urls[] = array($this->get_reviews_url(), 'review');
        }
        if ($this->options['show_favorites'])
        {
            $urls[] = array($this->get_favorites_url(), 'favorite');
        }
        return $urls;
    }
    
    function yield($item)
    {
        $enclosure = $item->get_enclosure();
        return array(
            'date'      =>  $item->get_date('U'),
            'link'      =>  html_entity_decode($item->get_link()),
            'title'     =>  html_entity_decode($item->get_title()),
            'thumbnail' =>  $enclosure->link,
        );
    }
}
register_lifestream_feed('LifeStream_StumbleUponFeed');

class LifeStream_TumblrFeed extends LifeStream_TwitterFeed
{
    const ID            = 'tumblr';
    const NAME          = 'Tumblr';
    const URL           = 'http://www.tumblr.com/';
    const LABEL_SINGLE  = 'Posted a note on <a href="%s">%s</a>.';
    const LABEL_PLURAL  = 'Posted %d notes on <a href="%s">%s</a>.';
    const LABEL_SINGLE_USER = '<a href="%s">%s</a> posted a note on <a href="%s">%s</a>.';
    const LABEL_PLURAL_USER = '<a href="%s">%s</a> posted %d notes on <a href="%s">%s</a>.';
    const NAMESPACE     = 'http://jaiku.com/ns';
    
    // http://media.tumblr.com/ck3ATKEVYd6ay62wLAzqtEkX_500.jpg
    private $image_match_regexp = '/src="(http:\/\/media\.tumblr\.com\/[a-zA-Z0-9_-]+\.jpg)"/i';
    
    function get_url()
    {
        return 'http://'.$this->options['username'].'.tumblr.com/rss';
    }
    
    function get_user_url($user)
    {
        return 'http://'.$this->options['username'].'.tumblr.com/';
    }
    
    function yield($item)
    {
        preg_match($this->image_match_regexp, $item->get_description(), $match);
        $data = array(
            'date'      =>  $item->get_date('U'),
            'link'      =>  html_entity_decode($item->get_link()),
            'title'     =>  html_entity_decode($item->get_title()),
            'key'       =>  'note',
        );
        if ($match)
        {
            $data['thumbnail'] = $match[1];
            $data['image'] = $match[1];
            $data['key'] = 'image';
        }
        return $data;
    }
    
    function render_group_items($id, $output, $event)
    {
        if ($event->key == 'image')
        {
            return LifeStream_PhotoFeed::render_group_items($id, $output, $event);
        }
        else
        {
            return parent::render_group_items($id, $output, $event);
        }
    }
    
    function render_item($event, $item)
    {
        if ($event->key == 'image')
        {
            return LifeStream_PhotoFeed::render_item($event, $item);
        }
        else
        {
            return $this->parse_users($this->parse_urls($item['title']));
        }
    }
    
    function get_label_single($key)
    {
        if ($key == 'image') return LifeStream_PhotoFeed::LABEL_SINGLE;
        return $this->get_constant('LABEL_SINGLE');
    }
    
    function get_label_plural($key)
    {
        if ($key == 'image') return LifeStream_PhotoFeed::LABEL_PLURAL;
        return $this->get_constant('LABEL_PLURAL');
    }
    
    function get_label_single_user($key)
    {
        if ($key == 'image') return LifeStream_PhotoFeed::LABEL_SINGLE_USER;
        return $this->get_constant('LABEL_SINGLE_USER');
    }
    
    function get_label_plural_user($key)
    {
        if ($key == 'image') return LifeStream_PhotoFeed::LABEL_PLURAL_USER;
        return $this->get_constant('LABEL_PLURAL_USER');
    }
}
register_lifestream_feed('LifeStream_TumblrFeed');

?>