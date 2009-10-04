<?php
class Lifestream_PlurkFeed extends Lifestream_Feed
{
	const ID	= 'plurk';
	const NAME	= 'Plurk';
	const URL	= 'http://www.plurk.com/';

	private $image_match_regexp = '/img src="(http\:\/\/images\.plurk\.com\/[^"]+)" alt="http\:\/\/images\.plurk\.com\/[^"]+"/i';

	function __toString()
	{
		return $this->options['username'];
	}

	function get_options()
	{
		return array(
			'username' => array($this->lifestream->__('Username:'), true, '', ''),
		);
	}
	
	function get_public_url()
	{
		return 'http://www.plurk.com/'.$this->options['username'];
	}

	function get_url()
	{
		return 'http://www.plurk.com/'.$this->options['username'].'.xml';
	}

	function get_label_class($key)
	{
		if ($key == 'photo') $cls = 'Lifestream_PhotoLabel';
		else $cls = 'Lifestream_MessageLabel';
		return $cls;
	}
	
	function yield($row, $url, $key)
	{
		$data = parent::yield($row, $url, $key);
		$string = $this->options['username'] . ' ';
		$title = $data['title'];
		if (lifestream_str_startswith(strtolower($title), strtolower($string)))
		{
			$title = substr($title, strlen($string));
		}
		$bits = explode(' ', $title);
		if ($bits[0] == 'shares')
		{
			if (preg_match($this->image_match_regexp, $data['description'], $match))
			{
				$data['thumbnail'] = $match[1];
				$data['key'] = 'photo';
			}
		}
		else
		{
			$data['key'] = 'message';
		}
		$data['title'] = implode(' ', array_slice($bits, 1));
		return $data;
	}
	
	function render_item($row, $item)
	{
		if ($row->key == 'message')
		{
			return $this->parse_urls(htmlspecialchars($item['title'])) . ' ['.$this->lifestream->get_anchor_html(htmlspecialchars($this->options['username']), $item['link']).']';
		}
		else
		{
			return parent::render_item($row, $item);
		}
	}
	
}
$lifestream->register_feed('Lifestream_PlurkFeed');

class Lifestream_TwitterFeed extends Lifestream_Feed
{
	const ID		= 'twitter';
	const NAME		= 'Twitter';
	const URL		= 'http://www.twitter.com/';
	const LABEL		= 'Lifestream_MessageLabel';
	const CAN_GROUP	= false;
	const DESCRIPTION = 'Specifying your password will allow Lifestream to pull in protected updates from your profile. Your password is stored in plaintext in the database, so only do this is you have no other option.';
	
	function __toString()
	{
		return $this->options['username'];
	}

	function get_options()
	{		
		return array(
			'username' => array($this->lifestream->__('Username:'), true, '', ''),
			'password' => array($this->lifestream->__('Password:'), false, '', ''),
			'hide_replies' => array($this->lifestream->__('Hide Replies'), false, true, false),
		);
	}
	
	function _get_user_link($match)
	{
		return $match[1].$this->get_user_link($match[2]);
	}
	
	function _get_search_term_link($match)
	{
		return $match[1].$this->lifestream->get_anchor_html(htmlspecialchars($match[2]), 'https://search.twitter.com/search?q='.urlencode($match[2]), array('class'=>'searchterm'));
	}

	function get_user_link($user)
	{
		return $this->lifestream->get_anchor_html('@'.htmlspecialchars($user), $this->get_user_url($user), array('class'=>'user'));
	}
	
	function get_user_url($user)
	{
		return 'http://www.twitter.com/'.urlencode($user);
	}
	
	function get_public_url()
	{
		return $this->get_user_url($this->options['username']);
	}

	function parse_users($text)
	{
		return preg_replace_callback('/([^\w]*)@([a-z0-9_-]+)\b/i', array($this, '_get_user_link'), $text);
	}

	function parse_search_term($text)
	{
		return preg_replace_callback('/([^\w]*)(#[a-z0-9_-]+)\b/i', array($this, '_get_search_term_link'), $text);
	}

	function get_url($page=1, $count=20)
	{
		if ($this->options['password'])
		{
			$url_base = 'http://'.$this->options['username'].':'.urlencode($this->options['password']).'@twitter.com';
		}
		else
		{
			$url_base = 'http://twitter.com';
		}
		return $url_base . '/statuses/user_timeline/'.$this->options['username'].'.rss?page='.$page.'&count='.$count;
	}
	
	function save()
	{
		$is_new = (bool)!$this->id;
		parent::save();
		if ($is_new)
		{
			// new feed -- attempt to import all statuses up to 2k
			$feed_msg = array(true, '');
			$page = 0;
			while ($feed_msg[0] !== false && $page < 10)
			{
				$page += 1;
				$feed_msg = $this->refresh($this->get_url($page, 200));
			}
		}
	}
	
	function render_item($row, $item)
	{
		return $this->parse_search_term($this->parse_users($this->parse_urls(htmlspecialchars($item['description'])))) . ' ['.$this->lifestream->get_anchor_html(htmlspecialchars($this->options['username']), $item['link']).']';
	}
	
	function yield($row, $url, $key)
	{
		$data = parent::yield($row, $url, $key);
		$string = $this->options['username'] . ': ';
		$description = $this->lifestream->html_entity_decode($row->get_description());
		if (lifestream_str_startswith(strtolower($description), strtolower($string)))
		{
			$description = substr($description, strlen($string));
		}
		if ($this->options['hide_replies'] && lifestream_str_startswith($description, '@'))
		{
			return false;
		}
		$data['description'] = $description;
		return $data;
	}
}
$lifestream->register_feed('Lifestream_TwitterFeed');

class Lifestream_JaikuFeed extends Lifestream_TwitterFeed
{
	const ID			= 'jaiku';
	const NAME		  = 'Jaiku';
	const URL		   = 'http://www.jaiku.com/';
	const NS_JAIKU	  = 'http://jaiku.com/ns';
	
	function get_url()
	{
		return 'http://'.$this->options['username'].'.jaiku.com/feed/rss';
	}
	
	function get_user_url($user)
	{
		return 'http://'.$user.'.jaiku.com';
	}

	function render_item($row, $item)
	{
		return $this->parse_users($this->parse_urls(htmlspecialchars($item['title'])));
	}

	function yield($row, $url, $key)
	{
		if (!lifestream_str_startswith($row->get_link(), 'http://'.$this->options['username'].'.jaiku.com/presence/')) return;
		
		$data = parent::yield($row, $url, $key);
		preg_match('|<p>([^<]+)</p>|i', $row->get_description(), $matches);
		$data['title'] = $matches[1];
		return $data;
	}
}
$lifestream->register_feed('Lifestream_JaikuFeed');

class Lifestream_DeliciousFeed extends Lifestream_Feed
{
	const ID	= 'delicious';
	const NAME	= 'Delicious';
	const URL	= 'http://www.delicious.com/';
	const LABEL = 'Lifestream_BookmarkLabel';
	const HAS_EXCERPTS	= true;

	function __toString()
	{
		return $this->options['username'];
	}
	
	function get_options()
	{
		return array(
			'username' => array($this->lifestream->__('Username:'), true, '', ''),
			'filter_tag' => array($this->lifestream->__('Limit items to tag:'), false, '', ''),
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

	function yield($row, $url, $key)
	{
		$data = parent::yield($row, $url, $key);
		$subject =& $row->get_item_tags(SIMPLEPIE_NAMESPACE_DC_11, 'subject');
		$tags = explode(' ', $row->sanitize($subject[0]['data'], SIMPLEPIE_CONSTRUCT_TEXT));
		$data['tags'] = $tags;
		return $data;
	}
}
$lifestream->register_feed('Lifestream_DeliciousFeed');

class Lifestream_LastFMFeed extends Lifestream_Feed
{
	const ID	= 'lastfm';
	const NAME	= 'Last.fm';
	const URL	= 'http://www.last.fm/';
	const LABEL	= 'Lifestream_ListenSongLabel';
	
	function __toString()
	{
		return $this->options['username'];
	}
	
	function get_event_display(&$event, &$bit)
	{
		return $bit['name'] . ' - ' . $bit['artist'];
	}
		
	function get_options()
	{		
		return array(
			'username' => array($this->lifestream->__('Username:'), true, '', ''),
			'loved' => array($this->lifestream->__('Only show loved tracks.'), false, true, true),
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
	
	function yield($track, $url)
	{
		return array(
			'guid'	  =>  $this->lifestream->html_entity_decode($track->url),
			'date'	  =>  strtotime($track->date),
			'link'	  =>  $this->lifestream->html_entity_decode($track->url),
			'name'	  =>  $this->lifestream->html_entity_decode($track->name),
			'artist'	=>  $this->lifestream->html_entity_decode($track->artist),
		);
	}
	
	function fetch()
	{
		$response = $this->lifestream->file_get_contents($this->get_url());
		if ($response)
		{
			$xml = new SimpleXMLElement($response);
			
			$feed = $xml->track;
			$items = array();
			foreach ($feed as $track)
			{
				$items[] = $this->yield($track, $url);
			}
			return $items;
		}
	}
	
	function render_item($row, $item)
	{
		return $this->lifestream->get_anchor_html(htmlspecialchars($item['artist']).' &ndash; '.htmlspecialchars($item['name']), $item['link']);
	}
}
$lifestream->register_feed('Lifestream_LastFMFeed');

class Lifestream_BlogFeed extends Lifestream_GenericFeed
{
	const ID			= 'blog';
	const NAME			= 'Blog';
	const LABEL			= 'Lifestream_BlogLabel';
	const DESCRIPTION	= '';
	const HAS_EXCERPTS	= true;
	
	function get_options()
	{		
		return array(
			'url' => array($this->lifestream->__('Feed URL:'), true, '', ''),
			'permalink_url' => array($this->lifestream->__('Website URL:'), false, '', ''),
		);
	}
	
	function _get_domain()
	{
		if (!empty($this->options['permalink_url'])) $url = $this->options['permalink_url'];
		else $url = $this->options['url'];
		preg_match('#^(http://)?([a-z0-9\-\.]*\.)?([a-z0-9\-]+\.[a-z0-9\-]+)/?#i', $url, $matches);
		return $matches[3];
	}
	
	function get_public_name()
	{
		if (!empty($this->options['feed_label']))
		{
			return $this->options['feed_label'];
		}
		return $this->_get_domain();
	}
	
	function get_public_url()
	{
		if ($this->options['permalink_url']) return $this->options['permalink_url'];
		
		return 'http://'.$this->_get_domain();
	}

	function yield($row, $url, $key)
	{
		$data = parent::yield($row, $url, $key);
		$author =& $row->get_item_tags(SIMPLEPIE_NAMESPACE_DC_11, 'creator');
		$data['author'] = $this->lifestream->html_entity_decode($author[0]['data']);
		return $data;
	}
}
$lifestream->register_feed('Lifestream_BlogFeed');

class Lifestream_FlickrFeed extends Lifestream_PhotoFeed
{
	const ID			= 'flickr';
	const NAME			= 'Flickr';
	const URL			= 'http://www.flickr.com/';
	const DESCRIPTION	= 'You can find your User ID by using <a href="http://idgettr.com/" target="_blank">idGettr</a>.';
	 
	function get_options()
	{		
		return array(
			'user_id' => array($this->lifestream->__('User ID:'), true, '', ''),
		);
	}
	
	function get_public_url()
	{
		return 'http://www.flickr.com/photos/'.$this->options['user_id'].'/';
	}

	function get_url()
	{
		return 'http://api.flickr.com/services/feeds/photos_public.gne?id='.$this->options['user_id'].'&format=rss_200';
	}

	function yield($row, $url, $key)
	{
		$data = parent::yield($row, $url, $key);
		$data['image'] = str_replace('_m', '', $data['image']);
		return $data;
	}
}
$lifestream->register_feed('Lifestream_FlickrFeed');

class Lifestream_PhotoBucketFeed extends Lifestream_PhotoFeed
{
	const ID	= 'photobucket';
	const NAME	= 'Photobucket';
	const URL	= 'http://www.photobucket.com/';
}
$lifestream->register_feed('Lifestream_PhotoBucketFeed');

class Lifestream_FacebookFeed extends Lifestream_Feed
{
	const ID			= 'facebook';
	const NAME			= 'Facebook';
	const URL			= 'http://www.facebook.com/';
	const DESCRIPTION	= 'To obtain your Facebook feed URL visit the very hard to find <a href="http://www.facebook.com/notifications.php" target="_blank">Notifications</a> page. On the right hand side look in the sidebar for the <strong>Subscribe to Notifications</strong> item, and click the <strong>Your Notifications</strong> link.';
	const LABEL			= 'Lifestream_MessageLabel';
	const CAN_GROUP		= false;
	
	function render_item($row, $item)
	{
		return htmlspecialchars($item['title']);
	}
}
$lifestream->register_feed('Lifestream_FacebookFeed');

class Lifestream_DiggFeed extends Lifestream_Feed
{
	const ID	= 'digg';
	const NAME	= 'Digg';
	const URL	= 'http://www.digg.com/';
	const LABEL	= 'Lifestream_LikeStoryLabel';
	
	function __toString()
	{
		return $this->options['username'];
	}
	
	function get_options()
	{		
		return array(
			'username' => array($this->lifestream->__('Username:'), true, '', ''),
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
$lifestream->register_feed('Lifestream_DiggFeed');

class Lifestream_YouTubeFeed extends Lifestream_FlickrFeed
{
	const ID			= 'youtube';
	const NAME			= 'YouTube';
	const URL			= 'http://www.youtube.com/';
	const DESCRIPTION	= '';
	
	function __toString()
	{
		return $this->options['username'];
	}
	
	function get_options()
	{
		return array(
			'username' => array($this->lifestream->__('Username:'), true, '', ''),
			'show_favorites' => array($this->lifestream->__('Include favorites in this feed.'), false, true, false),
		);
	}
	
	function get_public_url()
	{
		return 'http://www.youtube.com/user/'.$this->options['username'];
	}
	
	function get_label_class($key)
	{
		if ($key == 'favorite') $cls = 'Lifestream_LikeVideoLabel';
		else $cls = 'Lifestream_VideoLabel';
		return $cls;
	}

	function get_posted_url() {
		return 'http://gdata.youtube.com/feeds/api/users/'.$this->options['username'].'/uploads?v=2';
		}

	function get_favorited_url() {
		return 'http://gdata.youtube.com/feeds/api/users/'.$this->options['username'].'/favorites?v=2';
		}

	function get_url() {
		$urls = array();
		$urls[] = array($this->get_posted_url(), 'video');
		if ($this->options['show_favorites']) $urls[] = array($this->get_favorited_url(), 'favorite');
		return $urls;
	}
	
	function render_item($row, $item)
	{
		$attrs = array(
			'class' => 'photo',
			'title' => htmlspecialchars($item['title'])
		);
		if ($this->lifestream->get_option('use_ibox') == '1')
		{
			$attrs['rel'] = 'ibox';
		}
		return $this->lifestream->get_anchor_html('<img src="'.$item['thumbnail'].'" alt="" width="50"/>', $item['link'], $attrs);
	}
}
$lifestream->register_feed('Lifestream_YouTubeFeed');

class Lifestream_GoogleReaderFeed extends Lifestream_Feed
{
	const ID			= 'googlereader';
	const NAME			= 'Google Reader';
	const URL			= 'http://www.google.com/reader/';
	const DESCRIPTION	= 'Your Google Reader feed URL is available by going to "Share items" under "Your stuff". From there follow the link "See your shared items page in a new window.". It should look something like this: http://www.google.com/reader/shared/14285665327310657206';
	const LABEL			= 'Lifestream_BookmarkLabel';
	const NS			= 'http://www.google.com/schemas/reader/atom/';
	const HAS_EXCERPTS	= true;
	
	function __toString()
	{
		return $this->options['user_id'] ? $this->options['user_id'] : $this->options['url'];
	}
	
	function get_event_description(&$event, &$bit)
	{
		return $bit['comment'];
	}
	
	function get_options()
	{		
		return array(
			'url' => array($this->lifestream->__('Website URL:'), true, '', ''),
			'user_id' => array($this->lifestream->__('User ID:'), null, '', ''),
		);
	}
	
	function get_url()
	{
		if (!$this->options['user_id']) return $this->options['url'];
		return 'http://www.google.com/reader/public/atom/user%2F'.$this->options['user_id'].'%2Fstate%2Fcom.google%2Fbroadcast';
	}
	
	function save_options()
	{
		if (preg_match('/\/reader\/shared\/([0-9]+)\/?/i', $this->options['url'], $match))
		{
			$this->options['user_id'] = $match[1];
		}
		else
		{
			throw new Lifestream_Error("Invalid feed URL.");
		}
		parent::save_options();
	}
	
	function yield($row, $url, $key)
	{
		//<gr:annotation><content type="html">Just testing some stuff in Lifestream</content>
		$data = parent::yield($row, $url, $key);
		$annotation =& $row->get_item_tags(self::NS, 'annotation');
		$data['comment'] = $this->lifestream->html_entity_decode($annotation[0]['child']['http://www.w3.org/2005/Atom']['content'][0]['data']);
		return $data;
	}
}
$lifestream->register_feed('Lifestream_GoogleReaderFeed');

class Lifestream_YelpFeed extends Lifestream_Feed
{
	const ID			= 'yelp';
	const NAME			= 'Yelp';
	const URL			= 'http://www.yelp.com/';
	const DESCRIPTION	= 'You can obtain your Yelp RSS feed url from your profile page. It should look something like this: http://www.yelp.com/syndicate/user/ctwwsl5_DSCzwPxtjzdl2A/rss.xml';
	const LABEL			= 'Lifestream_BusinessReviewLabel';
	
	function yield($row, $url, $key)
	{
		$data = parent::yield($row, $url, $key);

		$title = $row->get_title();
		
		$on_part = ' on Yelp.com';
		if (substr($title, strlen($title)-strlen($on_part)) == $on_part)
			$title = substr($title, 0, strlen($title)-strlen($on_part));
		
		$data['title'] = $title;
		return $data;
	}
}
$lifestream->register_feed('Lifestream_YelpFeed');

class Lifestream_MySpaceFeed extends Lifestream_BlogFeed
{
	const ID			= 'myspace';
	const NAME			= 'MySpace';
	const URL			= 'http://www.myspace.com/';
	const DESCRIPTION	= 'To retrieve your MySpace blog URL, visit your profile and click "View all entries" under your blog. From there, you will see an "rss" link on the top right of the page.';
	
}
$lifestream->register_feed('Lifestream_MySpaceFeed');

class Lifestream_SkitchFeed extends Lifestream_FlickrFeed
{
	const ID			= 'skitch';
	const NAME			= 'Skitch';
	const URL			= 'http://www.skitch.com/';
	const DESCRIPTION	= '';
	
	private $image_match_regexp = '/src="(http\:\/\/img+\.skitch\.com\/[^"]+\.jpg)"/i';
	
	function __toString()
	{
		return $this->options['username'];
	}

	function get_options()
	{		
		return array(
			'username' => array($this->lifestream->__('Username:'), true, '', ''),
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
	
	function yield($row, $url, $key)
	{
		$data = parent::yield($row, $url, $key);
		preg_match($this->image_match_regexp, $row->get_description(), $match);
		$data['thumbnail'] = $match[1];
		$data['image'] = str_replace('.preview.', '', $match[1]);
		return $data;
	}
}
$lifestream->register_feed('Lifestream_SkitchFeed');

class Lifestream_IdenticaFeed extends Lifestream_TwitterFeed
{
	const ID	= 'identica';
	const NAME	= 'Identi.ca';
	const URL	= 'http://www.identi.ca/';

	function get_user_url($user)
	{
		return 'http://www.identi.ca/'.$user;
	}

	function render_item($row, $item)
	{
		return $this->parse_users($this->parse_urls(htmlspecialchars($item['title']))) . ' ['.$this->lifestream->get_anchor_html(htmlspecialchars($this->options['username']), $item['link']).']';
	}

	function get_url()
	{
		return 'http://identi.ca/'.$this->options['username'].'/rss';
	}
	
	function yield($row, $url, $key)
	{
		$string = $this->options['username'] . ': ';
		$title = $this->lifestream->html_entity_decode($row->get_title());
		if (lifestream_str_startswith($title, $string))
		{
			$title = substr($title, strlen($string));
		}
		return array(
			'guid'	  =>  $row->get_id(),
			'date'	  =>  $row->get_date('U'),
			'link'	  =>  $this->lifestream->html_entity_decode($row->get_link()),
			'title'	 =>  $title,
		);
	}
}
$lifestream->register_feed('Lifestream_IdenticaFeed');


class Lifestream_PandoraFeed extends Lifestream_Feed
{
	const ID			= 'pandora';
	const NAME			= 'Pandora';
	const URL			= 'http://www.pandora.com/';
	const NS_PANDORA	= 'http://musicbrainz.org/mm/mm-2.1#';
	const DESCRIPTION	= 'Your username is available from your profile page. For example, if your profile page has a url of http://www.pandora.com/people/foobar32 then your username is foobar32.';
	
	function __toString()
	{
		return $this->options['username'];
	}
	
	function get_options()
	{
		return array(
			'username' => array($this->lifestream->__('Username:'), true, '', ''),
			'show_stations' => array($this->lifestream->__('Include stations in this feed.'), false, true, true),
			'show_bookmarked_artists' => array($this->lifestream->__('Include bookmarked artists in this feed.'), false, true, true),
			'show_bookmarked_songs' => array($this->lifestream->__('Include bookmarked songs in this feed.'), false, true, true),
		);
	}

	function get_label_class($key)
	{
		if ($key == 'bookmarksong') $cls = 'Lifestream_LikeSongLabel';
		if ($key == 'bookmarkartist') $cls = 'Lifestream_LikeArtistLabel';
		else $cls = 'Lifestream_CreateStationLabel';
		return $cls;
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
	
	function yield($row, $url, $key)
	{
		if (lifestream_str_endswith($row->get_title(), 'QuickMix')) return false;
		return parent::yield($row, $url, $key);
	}
}
$lifestream->register_feed('Lifestream_PandoraFeed');

// class Lifestream_HuluFeed extends Lifestream_PhotoFeed
// {
//	 const ID			= 'hulu';
//	 const NAME		  = 'Hulu';
//	 const URL		   = 'http://www.hulu.com/';
//	 const LABEL_SINGLE  = 'Watched a video on %s.';
//	 const LABEL_PLURAL  = 'Watched %d videos on %s.';
//	 const LABEL_SINGLE_USER = '%s watched a video on %s.';
//	 const LABEL_PLURAL_USER = '%s watched %d videos on %s.';
//	 const DESCRIPTION   = 'You may first need to change your privacy settings on Hulu for the feed to be viewable.';
//	 
//	 private $link_match_regexp = '/href="(http\:\/\/www\.hulu\.com\/watch\/[^"]+)"/i';
//	 private $image_match_regexp = '/src="(http\:\/\/thumbnails\.hulu\.com\/[^"]+\.jpg)"/i';
//	 
//	 function get_options()
//	 {		
//		 return array(
//			 'username' => array($this->lifestream->__('Username:'), true, '', ''),
//		 );
//	 }
// 
//	 function get_url()
//	 {
//		 // Support old-style url for feed
//		 if ($this->options['url']) return $this->options['url'];
//		 return 'http://www.hulu.com/feed/activity/'.$this->options['username'];
//	 }
// 
//	 
//	 function yield($row, $url, $key)
//	 {
//		 $data = parent::yield($row, $url, $key);
//		 if (!$data['thumbnail'])
//		 {
//			 preg_match($this->link_match_regexp, $row->get_description(), $link_match);
//			 preg_match($this->image_match_regexp, $row->get_description(), $image_match);
//			 $data['thumbnail'] = $image_match[1];
//			 $data['link'] = $link_match[1];
//		 }
//		 return $data;
//	 }
// }
class Lifestream_HuluFeed extends Lifestream_Feed
{
	const ID			= 'hulu';
	const NAME			= 'Hulu';
	const URL			= 'http://www.hulu.com/';
	const DESCRIPTION	= 'You can obtain your history feed by visiting <a href="http://www.hulu.com/users/history">here</a> and clicking the RSS icon at the top of the page. You may first need to change your privacy settings for the feed to be viewable.';
	const LABEL			= 'Lifestream_WatchVideoLabel';
}
$lifestream->register_feed('Lifestream_HuluFeed');

class Lifestream_TwitPicFeed extends Lifestream_PhotoFeed
{
	const ID	= 'twitpic';
	const NAME	= 'TwitPic';
	const URL	= 'http://www.twitpic.com/';
	
	function get_options()
	{		
		return array(
			'username' => array($this->lifestream->__('Username:'), true, '', ''),
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

	function get_thumbnail_url($row, $item)
	{
		preg_match('#\/([^\/]+)$#i', $item['link'], $matches);
		return 'http://www.twitpic.com/show/thumb/'.$matches[1].'.jpg';
	}
}
$lifestream->register_feed('Lifestream_TwitPicFeed');

class Lifestream_VimeoFeed extends Lifestream_PhotoFeed
{
	const ID			= 'vimeo';
	const NAME			= 'Vimeo';
	const URL			= 'http://www.vimeo.com/';
	const DESCRIPTION	= 'Your user ID is the digits at the end of your profile URL. For example, if your profile is <strong>http://www.vimeo.com/user406516</strong> then your user ID is <strong>406516</strong>.';
	
	private $image_match_regexp = '/src="(http\:\/\/[a-z0-9]+\.vimeo\.com\/[^"]+)"/i';
	
	function __toString()
	{
		return $this->options['user_id'];
	}
	
	function get_options()
	{
		return array(
			'user_id' => array($this->lifestream->__('User ID:'), true, '', ''),
			'show_videos' => array($this->lifestream->__('Include videos posted in this feed.'), false, true, true),
			'show_likes' => array($this->lifestream->__('Include liked videos in this feed.'), false, true, true),
		);
	}
	
	function get_label_class($key)
	{
		if ($key == 'like') $cls = 'Lifestream_LikeVideoLabel';
		else $cls = 'Lifestream_VideoLabel';
		return $cls;
	}
	
	function get_videos_url()
	{
		return 'http://www.vimeo.com/'.$this->options['user_id'].'/videos/rss';
	}
	
	function get_likes_url()
	{
		return 'http://www.vimeo.com/'.$this->options['user_id'].'/likes/rss';
	}

	function get_public_url()
	{
		return 'http://www.vimeo.com/'.$this->options['user_id'];
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
	
	function yield($row, $url, $key)
	{
		$data = parent::yield($row, $url, $key);
		preg_match($this->image_match_regexp, $row->get_description(), $match);
		$data['thumbnail'] = $match[1];
		return $data;
	}
}
$lifestream->register_feed('Lifestream_VimeoFeed');

class Lifestream_StumbleUponFeed extends Lifestream_PhotoFeed
{
	const ID	= 'stumbleupon';
	const NAME	= 'StumbleUpon';
	const URL	= 'http://www.stumbleupon.com/';
	
	function __toString()
	{
		return $this->options['username'];
	}
	
	function get_options()
	{
		return array(
			'username' => array($this->lifestream->__('Username:'), true, '', ''),
			'show_reviews' => array($this->lifestream->__('Include reviews in this feed.'), false, true, true),
			'show_favorites' => array($this->lifestream->__('Include favorites in this feed.'), false, true, false),
		);
	}
	
	function get_label_class($key)
	{
		if ($key == 'review') $cls = 'Lifestream_ReviewWebsiteLabel';
		else $cls = 'Lifestream_LikeWebsiteLabel';
		return $cls;
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
}
$lifestream->register_feed('Lifestream_StumbleUponFeed');

class Lifestream_TumblrFeed extends Lifestream_Feed
{
	const ID	= 'tumblr';
	const NAME	= 'Tumblr';
	const URL	= 'http://www.tumblr.com/';
	const HAS_EXCERPTS	= true;
	
	// http://media.tumblr.com/ck3ATKEVYd6ay62wLAzqtEkX_500.jpg
	private $image_match_regexp = '/src="(http:\/\/(?:[a-z0-9\.]+\.)?media\.tumblr\.com\/[a-zA-Z0-9_-]+\.jpg)"/i';
	
	function get_options()
	{		
		return array(
			'username' => array($this->lifestream->__('Username:'), true, '', ''),
		);
	}
	
	# TODO: initialization import
	# http://twitter.com/statuses/user_timeline/zeeg.xml
	function get_url()
	{
		return 'http://'.$this->options['username'].'.tumblr.com/rss';
	}
	
	function get_user_url($user)
	{
		return 'http://'.$this->options['username'].'.tumblr.com/';
	}
	
	function yield($row, $url, $key)
	{
		$data = parent::yield($row, $url, $key);
		preg_match($this->image_match_regexp, $row->get_description(), $match);
		if (strip_tags($data['title']) == strip_tags($data['description']))
		{
			$data['key'] = 'note';
		}
		if ($match)
		{
			$data['thumbnail'] = $match[1];
			$data['image'] = $match[1];
			$data['key'] = 'image';
		}
		return $data;
	}
	
	function parse_users($text)
	{
		return preg_replace_callback('/([^\w]*)@([a-z0-9_-]+)\b/i', array($this, '_get_user_link'), $text);
	}
	
	function _get_user_link($match)
	{
		return $match[1].$this->get_user_link($match[2]);
	}

	function render_item($event, $item)
	{
		if ($event->key == 'image')
		{
			return Lifestream_PhotoFeed::render_item($event, $item);
		}
		elseif ($event->key == 'note')
		{
			return Lifestream_TwitterFeed::parse_users($this->parse_urls(htmlspecialchars($item['title']))) . ' ['.$this->lifestream->get_anchor_html($this->options['username'], htmlspecialchars($item['link'])).']';
		}
		else
		{
			return parent::render_item($event, $item);
		}
	}
	
	function get_label_class($key)
	{
		if ($key == 'image') $cls = Lifestream_PhotoFeed::LABEL;
		elseif ($key == 'note') $cls = Lifestream_TwitterFeed::LABEL;
		else $cls = Lifestream_BlogFeed::LABEL;
		return $cls;
	}
}
$lifestream->register_feed('Lifestream_TumblrFeed');

class Lifestream_AmazonFeed extends Lifestream_PhotoFeed
{
	const ID	= 'amazon';
	const NAME	= 'Amazon';
	const URL	= 'http://www.amazon.com/';
	const LABEL	= 'Lifestream_WantItemLabel';

	private $image_match_regexp = '/src="(http\:\/\/ecx\.images-amazon\.com\/[^"]+\.jpg)"/i';
	
	function yield($row, $url, $key)
	{
		$data = parent::yield($row, $url, $key);
		preg_match($this->image_match_regexp, $row->get_description(), $match);
		$data['thumbnail'] = $match[1];
		return $data;
	}
}
$lifestream->register_feed('Lifestream_AmazonFeed');

class Lifestream_MagnoliaFeed extends Lifestream_PhotoFeed
{
	const ID	= 'magnolia';
	const NAME	= 'Ma.gnolia';
	const URL	= 'http://www.ma.gnolia.com/';
	const LABEL	= 'Lifestream_BookmarkLabel';

	private $image_match_regexp = '/src="(http:\/\/scst\.srv\.girafa\.com\/[^"]+)"/i';
	
	function __toString()
	{
		return $this->options['username'];
	}
		
	function get_options()
	{		
		return array(
			'username' => array($this->lifestream->__('Username:'), true, '', ''),
		);
	}

	function get_url()
	{
		return 'http://ma.gnolia.com/rss/full/people/'.$this->options['username'];
	}
	
	function get_public_url()
	{
		return 'http://ma.gnolia.com/people/'.$this->options['username'];
	}
	
	function yield($row, $url, $key)
	{
		$data = parent::yield($row, $url, $key);
		preg_match($this->image_match_regexp, $row->get_description(), $match);
		$data['thumbnail'] = $match[1];
		return $data;
	}
}
$lifestream->register_feed('Lifestream_MagnoliaFeed');

class Lifestream_ZooomrFeed extends Lifestream_FlickrFeed
{
	const ID			= 'zooomr';
	const NAME			= 'Zooomr';
	const URL			= 'http://www.zooomr.com/';
	const DESCRIPTION	= '';
	
	function __toString()
	{
		return $this->options['username'];
	}
	
	function get_url()
	{
		return $this->options['url'];
	}

	function get_options()
	{
		return array(
			'url' => array($this->lifestream->__('Feed URL:'), true, '', ''),
			'username' => array($this->lifestream->__('Username:'), true, '', ''),
		);
	}
	
	function get_public_url()
	{
		return 'http://www.zooomr.com/photos/'.$this->options['username'].'/';
	}
}
$lifestream->register_feed('Lifestream_ZooomrFeed');

class Lifestream_BlipFMFeed extends Lifestream_TwitterFeed
{
	const ID	= 'blipfm';
	const NAME	= 'Blip.fm';
	const URL	= 'http://blip.fm/';
	const LABEL	= 'Lifestream_MessageLabel';
	
	function get_user_url($user)
	{
		return 'http://blip.fm/'.$user;
	}
	
	function get_url()
	{
		return 'http://blip.fm/feed/'.$this->options['username'];
	}
	
	function get_event_display(&$event, &$bit)
	{
		return $bit['title'] ? $bit['title'] : $bit['track'];
	}

	function render_item($row, $item)
	{
		return $this->parse_users($this->parse_urls(htmlspecialchars($this->get_event_display($row, $item))));
	}

	function yield($row, $url, $key)
	{
		$data = parent::yield($row, $url, $key);
		$data['track'] = $data['title'];
		$data['title'] = $data['description'];
		return $data;
	}
}

$lifestream->register_feed('Lifestream_BlipFMFeed');

class Lifestream_BrightkiteFeed extends Lifestream_Feed
{
	const ID			= 'brightkite';
	const NAME			= 'Brightkite';
	const URL			= 'http://www.brightkite.com/';
	const DESCRIPTION	= '';
	const NS_BRIGHTKITE	= 'http://brightkite.com/placeFeed';
	
	function __toString()
	{
		return $this->options['username'];
	}
	
	function get_options()
	{
		return array(
			'username' => array($this->lifestream->__('Username:'), true, '', ''),
		);
	}
	
	function get_public_url()
	{
		return 'http://www.brightkite.com/people/'.$this->options['username'];
	}

	function get_url()
	{
		return 'http://www.brightkite.com/people/'.$this->options['username'].'/objects.rss';
	}
	
	function render_group_items($id, $output, $event)
	{
		if ($event->key == 'photo')
		{
			return Lifestream_PhotoFeed::render_group_items($id, $output, $event);
		}
		else
		{
			return parent::render_group_items($id, $output, $event);
		}
	}
	
	function render_item($event, $item)
	{
		if ($event->key == 'photo')
		{
			return Lifestream_PhotoFeed::render_item($event, $item);
		}
		elseif ($event->key == 'checkin') return $this->lifestream->get_anchor_html(htmlspecialchars($item['placename']), $item['placelink']);
		else
		{
			return $this->parse_urls(htmlspecialchars($item['text']));
		}
	}
	
	function get_label_class($key)
	{
		if ($key == 'photo') $cls = Lifestream_PhotoFeed::LABEL;
		elseif ($key == 'checkin') $cls = 'Lifestream_LocationLabel';
		else $cls = $this->get_constant('LABEL');
		return $cls;
	}
	
	function yield($row, $url, $key)
	{
		$data = parent::yield($row, $url, $key);
		$type = $row->get_item_tags(self::NS_BRIGHTKITE, 'eventType');
		$type = $type[0]['data'];

		$placelink = $row->get_item_tags(self::NS_BRIGHTKITE, 'placeLink');
		$data['placelink'] = $placelink[0]['data'];

		$placename = $row->get_item_tags(self::NS_BRIGHTKITE, 'placeName');
		$data['placename'] = $placename[0]['data'];

		$placeaddress = $row->get_item_tags(self::NS_BRIGHTKITE, 'placeAddress');
		$data['placeaddress'] = $placeaddress[0]['data'];

		if ($enclosure = $row->get_enclosure())
		{
			$data['thumbnail'] = $enclosure->get_thumbnail();
			$data['image'] = $enclosure->get_medium();
		}
		return $data;
	}
}
$lifestream->register_feed('Lifestream_BrightkiteFeed');

class Lifestream_PicasaFeed extends Lifestream_FlickrFeed
{
	const ID			= 'picasa';
	const NAME			= 'Picasa';
	const URL			= 'http://picasaweb.google.com/';
	const DESCRIPTION	= '';
	
	function __toString()
	{
		return $this->options['username'];
	}

	function get_options()
	{
		return array(
			'username' => array($this->lifestream->__('Username:'), true, '', ''),
		);
	}
	
	function get_url()
	{
		return 'http://picasaweb.google.com/data/feed/base/user/'.$this->options['username'].'?alt=rss&kind=album&hl=en_US&access=public';
	}
	
	function get_public_url()
	{
		return 'http://picasaweb.google.com/'.$this->options['username'];
	}
}
$lifestream->register_feed('Lifestream_PicasaFeed');

class Lifestream_KongregateFeed extends Lifestream_Feed
{
	const ID			= 'kongregate';
	const NAME			= 'Kongregate';
	const URL			= 'http://www.kongregate.com/';
	const DESCRIPTION	= '';
	const LABEL			= 'Lifestream_ReceiveBadgeLabel';
	
	function __toString()
	{
		return $this->options['username'];
	}

	function get_options()
	{
		return array(
			'username' => array($this->lifestream->__('Username:'), true, '', ''),
		);
	}
	
	function get_url()
	{
		return 'http://www.kongregate.com/accounts/'.$this->options['username'].'/badges.rss';
	}
	
	function get_public_url()
	{
		return 'http://www.kongregate.com/accounts/'.$this->options['username'];
	}
}
$lifestream->register_feed('Lifestream_KongregateFeed');

class Lifestream_ViddlerFeed extends Lifestream_YouTubeFeed
{
	const ID			= 'viddler';
	const NAME			= 'Viddler';
	const URL			= 'http://www.viddler.com/';
	const DESCRIPTION	= '';
	
	function __toString()
	{
		return $this->options['username'];
	}
	
	function get_options()
	{
		return array(
			'username' => array($this->lifestream->__('Username:'), true, '', ''),
		);
	}
	
	function get_public_url()
	{
		return 'http://www.viddler.com/explore/'.$this->options['username'].'/';
	}
	
	function get_url()
	{
		return 'http://www.viddler.com/explore/'.$this->options['username'].'/videos/feed/';
	}
}
$lifestream->register_feed('Lifestream_ViddlerFeed');

class Lifestream_CoCommentsFeed extends Lifestream_Feed
{
	const ID			= 'cocomment';
	const NAME			= 'coComment';
	const URL			= 'http://www.cocomment.com/';
	const LABEL			= 'Lifestream_CommentLabel';
	const HAS_EXCERPTS	= true;
	
	function __toString()
	{
		return $this->options['username'];
	}

	function get_options()
	{
		return array(
			'username' => array($this->lifestream->__('Username:'), true, '', ''),
		);
	}
	
	function get_url()
	{
		return 'http://www.cocomment.com/myWebRss/'.$this->options['username'].'.rss';
	}
	
	function get_public_url()
	{
		return 'http://www.cocomment.com/comments/'.$this->options['username'];
	}

}
$lifestream->register_feed('Lifestream_CoCommentsFeed');

class Lifestream_FoodFeedFeed extends Lifestream_Feed
{
	const ID	= 'foodfeed';
	const NAME	= 'FoodFeed';
	const URL	= 'http://www.foodfeed.us/';
	const LABEL	= 'Lifestream_EatLabel';
	
	function __toString()
	{
		return $this->options['username'];
	}

	function get_options()
	{
		return array(
			'username' => array($this->lifestream->__('Username:'), true, '', ''),
		);
	}
	
	function get_url()
	{
		return 'http://'.$this->options['username'].'.foodfeed.us/rss';
	}
	
	function get_public_url()
	{
		return 'http://'.$this->options['username'].'.foodfeed.us/';
	}

	function render_item($row, $item)
	{
		return htmlspecialchars($item['title']);
	}
}
$lifestream->register_feed('Lifestream_FoodFeedFeed');

class Lifestream_MyEpisodesFeed extends Lifestream_Feed
{
	const ID			= 'myepisodes';
	const NAME			= 'MyEpisodes';
	const URL			= 'http://www.myepisodes.com/';
	const DESCRIPTION	= 'You can obtain your MyList feed\'s URL by visiting your <a href="http://www.myepisodes.com/rsshelp.php#mylist">RSS Feeds</a> page, and copying the <strong>[Link]</strong> under <strong>MyList Feed</strong>.';
	const LABEL			= 'Lifestream_WatchEpisodeLabel';
	
	function __toString()
	{
		return $this->options['username'];
	}

	function get_options()
	{
		return array(
			'username' => array($this->lifestream->__('Username:'), true, '', ''),
			'url' => array($this->lifestream->__('Feed URL:'), true, '', ''),
		);
	}
}
$lifestream->register_feed('Lifestream_MyEpisodesFeed');

class Lifestream_MixxFeed extends Lifestream_Feed
{
	const ID	= 'mixx';
	const NAME	= 'Mixx';
	const URL	= 'http://www.mixx.com/';
	
	function __toString()
	{
		return $this->options['username'];
	}
	
	function get_options()
	{		
		return array(
			'username' => array($this->lifestream->__('Username:'), true, '', ''),
			'show_comments' => array($this->lifestream->__('Show Comments.'), false, true, false),
			'show_favorites' => array($this->lifestream->__('Show Favorites.'), false, true, true),
			'show_submissions' => array($this->lifestream->__('Show Submissions.'), false, true, true),
		);
	}
	
	function get_public_url()
	{
		return 'http://www.mixx.com/users/'.$this->options['username'];
	}
	
	function get_url()
	{
		return 'http://www.mixx.com/feeds/users/'.$this->options['username'];
	}
	
	function yield($row, $url, $key)
	{
		$data = parent::yield($row, $url, $key);
		$title = $this->lifestream->html_entity_decode($row->get_title());
		if (lifestream_str_startswith($title, 'Comment on: '))
		{
			if (!$this->options['show_comments']) return;
			$key = 'comment';
			$title = substr($title, 12);
		}
		elseif (lifestream_str_startswith($title, 'Submitted: '))
		{
			if (!$this->options['show_submissions']) return;
			$key = 'submit';
			$title = substr($title, 11);
		}
		elseif (lifestream_str_startswith($title, 'Favorite: '))
		{
			if (!$this->options['show_favorites']) return;
			$key = 'favorite';
			$title = substr($title, 10);
		}
		else
		{
			return;
		}
		
		$data['title'] = $title;
		$data['key'] = $key;
		return $data;
	}
	
	function get_label_class($key)
	{
		if ($key == 'favorite') $cls = 'Lifestream_LikeStoryLabel';
		elseif ($key == 'comment') $cls = 'Lifestream_CommentLabel';
		elseif ($key == 'submit') $cls = 'Lifestream_ShareStoryLabel';
		return $cls;
	}
}
$lifestream->register_feed('Lifestream_MixxFeed');

class Lifestream_IMDBFeed extends Lifestream_Feed
{
	const ID			= 'imdb';
	const NAME			= 'IMDB (My Movies)';
	const URL			= 'http://www.imdb.com/';
	const LABEL			= 'Lifestream_LikeMovieLabel';
	const DESCRIPTION   = 'You can obtain your IMDB feed\'s URL by visiting your <a href="http://www.imdb.com/mymovies/list">My Movies</a> page, and copying the url for the RSS feed from your address bar. You will need to check the "Public" box on the Pending page.';
}
$lifestream->register_feed('Lifestream_IMDBFeed');

class Lifestream_SlideShareFeed extends Lifestream_Feed
{
	const ID	= 'slideshare';
	const NAME	= 'SlideShare';
	const URL	= 'http://www.slideshare.net/';
	const LABEL	= 'Lifestream_ShareSlideLabel';
	
	function __toString()
	{
		return $this->options['username'];
	}

	function get_options()
	{
		return array(
			'username' => array($this->lifestream->__('Username:'), true, '', ''),
		);
	}
	
	function get_public_url()
	{
		return 'http://www.slideshare.net/'.$this->options['username'];
	}
	
	function get_url()
	{
		return 'http://www.slideshare.net/rss/user/'.$this->options['username'];
	}
}
$lifestream->register_feed('Lifestream_SlideShareFeed');

class Lifestream_BlipTVFeed extends Lifestream_Feed
{
	const ID	= 'bliptv';
	const NAME	= 'Blip.tv';
	const URL	= 'http://www.blip.tv/';
	const LABEL	= 'Lifestream_WatchEpisodeLabel';
	
	function __toString()
	{
		return $this->options['username'];
	}

	function get_options()
	{
		return array(
			'username' => array($this->lifestream->__('Username:'), true, '', ''),
		);
	}
	
	function get_public_url()
	{
		return 'http://'.$this->options['username'].'.blip.tv/';
	}
	
	function get_url()
	{
		return $this->get_public_url().'rss';
	}
}
$lifestream->register_feed('Lifestream_BlipTVFeed');

class Lifestream_SteamFeed extends Lifestream_Feed
{
	const ID	= 'steam';
	const NAME	= 'Steam';
	const URL	= 'http://www.steampowered.com/';
	const LABEL	= 'Lifestream_UnlockAchievementLabel';
	const MEDIA	= 'text';
	
	function __toString()
	{
		return $this->options['username'];
	}

	function get_options()
	{
		return array(
			'username' => array($this->lifestream->__('Steam ID:'), true, '', ''),
		);
	}
	
	function get_public_url()
	{
		return 'http://steamcommunity.com/id/'.$this->options['username'];
	}
	
	function get_url()
	{
		return 'http://pipes.yahoo.com/pipes/pipe.run?_id=IH0KF8OZ3RGJPl7dBR50VA&_render=rss&steamid='.$this->options['username'];
	}
}
$lifestream->register_feed('Lifestream_SteamFeed');

class Lifestream_XboxLiveFeed extends Lifestream_Feed
{
	const ID	= 'xboxlive';
	const NAME	= 'Xbox Live';
	const URL	= 'http://www.xbox.com/';
	const LABEL	= 'Lifestream_PlayGameLabel';
	
	function __toString()
	{
		return $this->options['username'];
	}

	function get_options()
	{
		return array(
			'username' => array($this->lifestream->__('Xbox Live ID:'), true, '', ''),
		);
	}
	
	function get_public_url()
	{
		return 'http://live.xbox.com/member/'.urlencode($this->options['username']);
	}
	
	function get_url()
	{
		return 'http://duncanmackenzie.net/services/GetXboxInfo.aspx?GamerTag='.urlencode($this->options['username']);
	}
	
	function get_event_display(&$event, &$bit)
	{
		return $bit['title'] ? $bit['title'] : $bit['name'];
	}
	
	function yield($row)
	{
		return array(
			'guid'	  =>  $this->lifestream->html_entity_decode($row->DetailsURL),
			'date'	  =>  strtotime($row->LastPlayed),
			'link'	  =>  $this->lifestream->html_entity_decode($row->DetailsURL),
			'title'	  =>  $this->lifestream->html_entity_decode($row->Game->Name),
		);
	}
	
	function fetch()
	{
		$response = $this->lifestream->file_get_contents($this->get_url());

		if ($response)
		{
			$xml = new SimpleXMLElement($response);
			
			if ($xml[0] == 'Service Unavailable') return;
			
			$items = array();
			foreach ($xml->RecentGames->XboxUserGameInfo as $row)
			{
				$items[] = $this->yield($row);
			}
			return $items;
		}
	}
	
	function render_item($row, $item)
	{
		return sprintf('%s', htmlspecialchars($item['link']), htmlspecialchars($item['name']));
	}
}
$lifestream->register_feed('Lifestream_XboxLiveFeed');

class Lifestream_iTunesFeed extends Lifestream_Feed
{
	const ID			= 'itunes';
	const NAME			= 'iTunes';
	const URL			= '';
	const DESCRIPTION	= 'To obtain your iTunes feed URL you must first go to your account in the iTunes Store. Once there, follow the "Enable My iTunes" link at the bottom. Follow the instructions to enable any feeds you wish to use (it\'s easiest just to enable them all).

Once Enabled, you will need to click "Get HTML Code" on one of the feeds. On this page, click "Copy Feed URL", and you should now have the URL for your feed. Lifestream just needs one feed url, it doesn\'t matter which, to process any of the feeds.

<strong>Note:</strong> If HTML code link opened in Firefox, you may need to re-open it in Internet Explorer for the "Copy Feed URL" to work correctly.';
	
	function __toString()
	{
		return $this->options['user_id'];
	}
	
	function get_options()
	{		
		return array(
			'url' => array($this->lifestream->__('Feed URL:'), true, '', ''),
			'user_id' => array($this->lifestream->__('User ID:'), null, '', ''),
			'show_purchases' => array($this->lifestream->__('Show Purchases.'), false, true, true),
			'show_reviews' => array($this->lifestream->__('Show Reviews.'), false, true, true),
		);
	}
	
	function save_options()
	{
		if (preg_match('/\/userid=([0-9]+)\//i', $this->options['url'], $match))
		{
			$this->options['user_id'] = $match[1];
		}
		else
		{
			throw new Lifestream_Error("Invalid feed URL.");
		}
		parent::save_options();
	}

	function get_url()
	{
		$urls = array();
		if ($user_id = $this->options['user_id'])
		{
			if ($this->options['show_purchases'])
			{
				$urls[] = array('http://ax.itunes.apple.com/WebObjects/MZStoreServices.woa/ws/RSS/myrecentpurchases/sf=143441/userid='.$user_id.'/xml?v0=9987', 'purchase');
			}
			if ($this->options['show_reviews'])
			{
				$urls[] = array('http://ax.itunes.apple.com/WebObjects/MZStoreServices.woa/ws/RSS/myrecentreviews/sf=143441/toprated=true/userid='.$user_id.'/xml?v0=9987', 'review');
			}
		}
		return $urls;
	}
	
	# http://phobos.apple.com/rss
	# <im:contentType term="Music" label="Music"><im:contentType term="Track" label="Track"/></im:contentType>
	# <im:image height="170">http://a1.phobos.apple.com/us/r1000/022/Music/c4/ae/6e/mzi.qpurndic.170x170-75.jpg</im:image>
	function get_label_class($key)
	{
		if ($key == 'review') $cls = 'Lifestream_ReviewItemLabel';
		elseif ($key == 'purchase') $cls = 'Lifestream_PurchaseItemLabel';
		return $cls;
	}
}
$lifestream->register_feed('Lifestream_iTunesFeed');

class Lifestream_ReadernautFeed extends Lifestream_Feed
{
	const ID			= 'readernaut';
	const NAME			= 'Readernaut';
	const URL			= 'http://www.readernaut.com/';
	const DESCRIPTION	= 'Readernaut is my library, my notebook, my book club.';
	const LABEL			= 'Lifestream_BookLabel';
	
	function __toString()
	{
		return $this->options['username'];
	}

	function get_options()
	{		
		return array(
			'username' => array($this->lifestream->__('Username:'), true, '', ''),
		);
	}

	function get_url()
	{
		return 'http://readernaut.com/rss/'.$this->options['username'].'/books/';
	}

	function get_public_url()
	{
		return 'http://readernaut.com/'.$this->options['username'];
	}
}
$lifestream->register_feed('Lifestream_ReadernautFeed');

class Lifestream_ScrnShotsFeed extends Lifestream_PhotoFeed
{
	const ID			= 'scrnshots';
	const NAME			= 'Scrnshots';
	const URL			= 'http://www.scrnshots.com/';
	const DESCRIPTION	= 'ScrnShots is the best way to take and share screenshots of web and screen based design. Upload as many screenshots as you want, embed them in your blog, discuss them with your contacts and become a better designer!';

	function __toString()
	{
		return $this->options['username'];
	}

	function get_options()
	{		
		return array(
			'username' => array($this->lifestream->__('Username:'), true, '', ''),
		);
	}

	function get_url()
	{
		return 'http://scrnshots.com/users/'.$this->options['username'].'/screenshots.rss';
	}

	function get_public_url()
	{
		return 'http://scrnshots.com/users/'.$this->options['username'];
	}

	function yield($row, $url, $key)
	{
		$data = parent::yield($row, $url, $key);

		$description = $data['description'];
		$title = strip_tags($description);
		$img = strip_tags($description,'<img>');
		$src = str_replace($title,'',$img);
		$large = preg_replace('/.*src=([\'"])((?:(?!\1).)*)\1.*/si','$2',$src);
		$small = str_replace('large','med_rect',$large);

		$data['thumbnail'] = $small;
		$data['image'] = $large;
		return $data;
	}
}
$lifestream->register_feed('Lifestream_ScrnShotsFeed');

class Lifestream_MobypictureFeed extends Lifestream_PhotoFeed
{
	const ID	= 'mobypicture';
	const NAME	= 'Mobypicture';
	const URL	= 'http://www.mobypicture.com/';

	function __toString()
	{
		return $this->options['username'];
	}

	function get_options()
	{
		return array(
			'username' => array($this->lifestream->__('Username:'), true, '', ''),
		);
	}

	function get_public_url()
	{
		return 'http://www.mobypicture.com/user/'.$this->options['username'];
	}

	function get_url()
	{
		return 'http://www.mobypicture.com/rss/'.$this->options['username'].'/user.rss';
	}
}
$lifestream->register_feed('Lifestream_MobypictureFeed');

class Lifestream_SmugMugFeed extends Lifestream_PhotoFeed
{
	const ID			= 'smugmug';
	const NAME			= 'SmugMug';
	const URL			= 'http://www.smugmug.com/';

	function __toString()
	{
		return $this->options['username'];
	}

	function get_options()
	{
		return array(
			'username' => array($this->lifestream->__('Username:'), true, '', ''),
		);
	}

	function get_public_url()
	{
		return 'http://'.$this->options['username'].'.smugmug.com/';
	}

	function get_url()
	{
		return 'http://www.smugmug.com/hack/feed.mg?Type=nicknameRecentPhotos&Data='.$this->options['username'].'&format=atom10';
	}
}
$lifestream->register_feed('Lifestream_SmugMugFeed');

class Lifestream_GoodReadsFeed extends Lifestream_PhotoFeed
{
	const ID	= 'goodreads';
	const NAME	= 'GoodReads';
	const URL	= 'http://www.goodreads.com/';
	const LABEL	= 'Lifestream_BookLabel';

	function __toString()
	{
		return $this->options['user_id'];
	}

	function get_options()
	{		
		return array(
			'url' => array($this->lifestream->__('Profile URL:'), true, '', ''),
			'user_id' => array($this->lifestream->__('User ID:'), null, '', ''),
		);
	}
	
	function save_options()
	{
		# We need to get their user id from the URL
		if (preg_match('/\/([0-9]+)(?:-.+)?$/i', $this->options['url'], $match))
		{
			$this->options['user_id'] = $match[1];
		}
		else
		{
			throw new Lifestream_Error("Invalid profile URL.");
		}
		
		parent::save_options();
	}

	function get_public_url()
	{
		return $this->options['url'];
	}

	function get_url()
	{
		return 'http://www.goodreads.com/review/list_rss/'.$this->options['user_id'];
	}
	
	function yield($item, $url)
	{
		return array(
			'guid'	  =>  $this->lifestream->html_entity_decode($item->guid),
			'date'	  =>  strtotime($item->pubDate),
			'link'	  =>  $this->lifestream->html_entity_decode($item->link),
			'title'	 =>  $this->lifestream->html_entity_decode($item->title),
			'author'	=>  $this->lifestream->html_entity_decode($item->author_name),
			'description'	=>  $this->lifestream->html_entity_decode($item->book_description),
			'image'	 =>  $this->lifestream->html_entity_decode($item->book_large_image_url),
			'thumbnail' =>  $this->lifestream->html_entity_decode($item->book_small_image_url),
		);
	}
	
	function fetch()
	{
		$response = $this->lifestream->file_get_contents($this->get_url());
		
		if ($response)
		{
			$xml = new SimpleXMLElement($response);
			
			$items = array();
			foreach ($xml->channel->item as $item)
			{
				$items[] = $this->yield($item, $url);
			}
			return $items;
		}
	}}
$lifestream->register_feed('Lifestream_GoodReadsFeed');

class Lifestream_DeviantArtFeed extends Lifestream_PhotoFeed
{
	const ID	= 'deviantart';
	const NAME	= 'deviantART';
	const URL	= 'http://www.deviantart.com/';

	function __toString()
	{
		return $this->options['username'];
	}

	function get_options()
	{
		return array(
			'username' => array($this->lifestream->__('Username:'), true, '', ''),
		);
	}

	function get_public_url()
	{
		return 'http://'.urlencode($this->options['username']).'.deviantart.com/';
	}

	function get_url()
	{
		return 'http://backend.deviantart.com/rss.xml?q=gallery%3A'.urlencode($this->options['username']);
	}
}
$lifestream->register_feed('Lifestream_DeviantArtFeed');

class Lifestream_BackTypeFeed extends Lifestream_Feed
{
	const ID		= 'backtype';
	const NAME		= 'BackType';
	const URL		= 'http://www.backtype.com/';
	const LABEL		= 'Lifestream_CommentLabel';
	# grouping doesnt support what we'd need for backtype
	const CAN_GROUP	= false;
	const HAS_EXCERPTS	= true;

	function get_options()
	{		
		return array(
			'username' => array($this->lifestream->__('Username:'), true, '', ''),
			'filter' => array($this->lifestream->__('Sites to filter out:'), false, '', '' , $this->lifestream->__('Sites as named by BackType, usually the title of the RSS Feed, separate with comma\'s.')),
		);
	}
	
	function get_user_link($user)
	{
		return $this->lifestream->get_anchor_html(htmlspecialchars($user), $this->get_user_url($user), array('class'=>'user'));
	}
	
	function get_user_url($user)
	{
		return 'http://www.backtype.com/'.urlencode($user);
	}
	
	function get_public_url()
	{
		return $this->get_user_url($this->options['username']);
	}

	function get_url()
	{
		return 'http://feeds.backtype.com/'.$this->options['username'];
	}
	
	function render_item($row, $item)
	{
		$output = "Posted a comment on ".htmlspecialchars($item['title'])."<br/>";
		$output .= str_replace("</p>", "<br/><br/>", str_replace("<p>","",$item['description'])) . ' ['.$this->lifestream->get_anchor_html(htmlspecialchars($this->options['username']), $item['link']).']';
		return $output;
	}
	
	function yield($row, $url, $key)
	{
		$data = parent::yield($row, $url, $key);

		$filters = explode(",",$this->options['filter']);
		foreach ($filters as $filter) {
			if (strtolower($filter) == strtolower(strip_tags($row->get_title()))) {
				return false;
				exit;
			}
		}
		$description = strip_tags(str_replace('<p><a href="http://www.backtype.com/'.strtolower($this->options['username']).'">Read more comments by '.strtolower($this->options['username']).'</a></p>', '' , $this->lifestream->html_entity_decode($row->get_description())));
		
		$data['description'] = $description;
		return $data;
	}
}
$lifestream->register_feed('Lifestream_BackTypeFeed');

class Lifestream_LibraryThingFeed extends Lifestream_PhotoFeed
{
	const ID	= 'librarything';
	const NAME	= 'LibraryThing';
	const URL	= 'http://www.librarything.com/';
	const LABEL	= 'Lifestream_BookLabel';

	function __toString()
	{
		return $this->options['member_name'];
	}

	function get_options()
	{
		return array(
			'member_name' => array($this->lifestream->__('Username:'), true, '', ''),
		);
	}

	function get_public_url()
	{
		return 'http://www.librarything.com/catalog/'.$this->options['member_name'];
	}

	function get_url()
	{
		return 'http://www.librarything.com/rss/recent/'.$this->options['member_name'];
	}

	private $image_match_regexp = '/img\s+src="([^"]+\.jpg)"/i';

	function yield($row, $url, $key)
	{
		$data = parent::yield($row, $url, $key);
		preg_match($this->image_match_regexp, $row->get_description(), $match);
		$data['thumbnail'] = $match[1];
		return $data;
	}
}
$lifestream->register_feed('Lifestream_LibraryThingFeed');

/**
 * Displays your latest Twitter status.
 * @param {Boolean} $links Parse user links.
 */
function lifestream_twitter_status($links=true)
{
	global $lifestream;

	$event = $lifestream->get_single_event('twitter');
	if (!$event) return;
	if ($links)
	{
		// to render it with links
		echo $event->feed->render_item($event, $event->data[0]);
	}
	else
	{
		// or render just the text
		echo $event->data[0]['title'];
	}
}

/**
 * Displays your latest Facebook status.
 * @param {Boolean} $links Parse user links.
 */
function lifestream_facebook_status($links=true)
{
	global $lifestream;

	$event = $lifestream->get_single_event('facebook');
	if (!$event) return;
	if ($links)
	{
		// to render it with links
		echo $event->feed->render_item($event, $event->data[0]);
	}
	else
	{
		// or render just the text
		echo $event->data[0]['title'];
	}
}

class Lifestream_NetflixFeed extends Lifestream_Feed
{
	const ID			= 'netflix';
	const NAME			= 'Netflix';
	const URL			= 'http://www.netflix.com/';
	const DESCRIPTION	= 'You can find your feed URL by logging into your Netflix account and clicking on RSS at the very bottom of the page.';

	function __toString()
	{
		return $this->options['user_id'];
	}

	function get_options()
	{		
		return array(
			'url' => array($this->lifestream->__('Feed URL:'), true, '', ''),
			'user_id' => array($this->lifestream->__('User ID:'), null, '', ''),
			'show_queue' => array($this->lifestream->__('Include queued videos in this feed.'), true, true, false),
			'show_reviews' => array($this->lifestream->__('Include reviewed videos in this feed.'), true, true, false),
		);
	}
	
	function get_url() {
		$urls = array();
		if ($this->options['show_queue'])
		{
			$urls[] = array('http://rss.netflix.com/QueueRSS?id='.$this->options['user_id'], 'queue');
			$urls[] = array('http://rss.netflix.com/QueueEDRSS?id='.$this->options['user_id'], 'queue');
		}
		if ($this->options['show_reviews'])
		{
			$urls[] = array('http://rss.netflix.com/ReviewsRSS?id='.$this->options['user_id'], 'review');
		}
		return $urls;
	}
	
	function save_options()
	{
		if (preg_match('/id=([A-Z0-9]+)/i', $this->options['url'], $match))
		{
			$this->options['user_id'] = $match[1];
		}
		else
		{
			throw new Lifestream_Error("Invalid feed URL.");
		}
		parent::save_options();
	}
	
	function get_label_class($key)
	{
		if ($key == 'review') $cls = 'Lifestream_ReviewVideoLabel';
		elseif ($key == 'queue') $cls = 'Lifestream_QueueVideoLabel';
		return $cls;
	}
	
	function yield($row, $url, $key)
	{
		$data = parent::yield($row, $url, $key);
		if ($data['title'] == 'Your Queue is empty.') return;
		if ($key == 'queue')
		{
			$data['title'] = substr($data['title'], 5);
		}
		return $data;
	}
}
$lifestream->register_feed('Lifestream_NetflixFeed');

class Lifestream_UpcomingFeed extends Lifestream_Feed
{
	const ID	= 'upcoming';
	const NAME	= 'Upcoming';
	const URL	= 'http://upcoming.yahoo.com/';
	const LABEL	= 'Lifestream_AttendEventLabel';
	const DESCRIPTION = 'You can get your API key <a href="http://upcoming.yahoo.com/services/api/keygen.php">here</a>. Please note, this feed will only show events you mark as attending.';

	function __toString()
	{
		return $this->options['user_id'];
	}

	function get_options()
	{		
		return array(
			'url' => array($this->lifestream->__('Profile URL:'), true, '', ''),
			'user_id' => array($this->lifestream->__('User ID:'), null, '', ''),
			'api_key' => array($this->lifestream->__('API Key:'), true, '', ''),
		);
	}
	
	function save_options()
	{
		if (preg_match('/\/user\/([0-9]+)\//i', $this->options['url'], $match))
		{
			$this->options['user_id'] = $match[1];
		}
		else
		{
			throw new Lifestream_Error("Invalid feed URL.");
		}
		parent::save_options();
	}

	function get_public_url()
	{
		return 'http://upcoming.yahoo.com/user/'.$this->options['user_id'].'/';
	}

	function get_url()
	{
		return 'http://upcoming.yahooapis.com/services/rest/?api_key='.$this->options['api_key'].'&method=user.getWatchlist&user_id='.$this->options['user_id'].'&show=all';
	}
	
	function get_event_display(&$event, &$bit)
	{
		return $bit['name'];
	}
	
	function yield(&$event, &$url)
	{
		if (!$event->status != 'attend') return;
		return array(
			'guid'		=> $this->lifestream->html_entity_decode($event['id']),
			'link'		=> $this->lifestream->html_entity_decode($event['venue_url']),
			'name'		=> $this->lifestream->html_entity_decode($event['name']),
			'description'	=> $this->lifestream->html_entity_decode($event['description']),
			'venue_city'	=> $this->lifestream->html_entity_decode($event['venue_city']),
			'venue_state'	=> $this->lifestream->html_entity_decode($event['venue_state_name']),
		);
	}
	
	function fetch()
	{
		$response = $this->lifestream->file_get_contents($this->get_url());
		if ($response)
		{
			$xml = new SimpleXMLElement($response);
			
			$feed = $xml->event;
			$items = array();
			foreach ($feed as $event)
			{
				$items[] = $this->yield($event, $url);
			}
			return $items;
		}
	}
}
$lifestream->register_feed('Lifestream_UpcomingFeed');

class Lifestream_WikipediaFeed extends Lifestream_PhotoFeed
{
	const ID	= 'wikipedia';
	const NAME	= 'Wikipedia';
	const URL	= 'http://www.wikipedia.org/';
	const LABEL	= 'Lifestream_ContributionLabel';

	function __toString()
	{
		return $this->options['username'];
	}

	function get_options()
	{
		return array(
			'username' => array($this->lifestream->__('Username:'), true, '', ''),
		);
	}

	function get_public_url()
	{
		return 'http://en.wikipedia.org/wiki/User:'.urlencode($this->options['username']);
	}

	function get_url()
	{
		return 'http://en.wikipedia.org/w/index.php?title=Special:Contributions&feed=rss&target='.urlencode($this->options['username']);
	}

	function yield($row, $url, $key)
	{
		$data = parent::yield($row, $url, $key);
		if (lifestream_str_startswith(strtolower($data['title']), 'talk:')) return;
		// we dont need huge descriptions stored in the db, its bloat
		unset($data['description']);
		return $data;
	}
}
$lifestream->register_feed('Lifestream_WikipediaFeed');
?>