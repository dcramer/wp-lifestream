<?php
class Lifestream_PandoraFeed extends Lifestream_Feed
{
	const ID			= 'pandora';
	const NAME			= 'Pandora';
	const URL			= 'http://www.pandora.com/';
	const NS_PANDORA	= 'http://musicbrainz.org/mm/mm-2.1#';
	const DESCRIPTION	= 'Your username is available from your profile page. For example, if your profile page has a url of http://www.pandora.com/people/foobar32 then your username is foobar32.';
	
	function __toString()
	{
		return $this->get_option('username');
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
		return 'http://feeds.pandora.com/feeds/people/'.$this->get_option('username').'/stations.xml';
	}
	
	function get_artists_url()
	{
			return 'http://feeds.pandora.com/feeds/people/'.$this->get_option('username').'/favoriteartists.xml';
	}
	
	function get_songs_url()
	{
		return 'http://feeds.pandora.com/feeds/people/'.$this->get_option('username').'/favorites.xml';
	}

	function get_public_url()
	{
		return 'http://www.pandora.com/people/'.$this->get_option('username');
	}

	function get_url()
	{
		$urls = array();
		if ($this->get_option('show_stations'))
		{
			$urls[] = array($this->get_stations_url(), 'station');
		}
		if ($this->get_option('show_bookmarked_artists'))
		{
			$urls[] = array($this->get_artists_url(), 'bookmarkartist');
		}
		if ($this->get_option('show_bookmarked_songs'))
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
?>