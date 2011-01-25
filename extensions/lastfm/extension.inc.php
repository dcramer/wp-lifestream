<?php
class Lifestream_LastFMFeed extends Lifestream_Feed
{
	const ID		= 'lastfm';
	const NAME		= 'Last.fm';
	const URL		= 'http://www.last.fm/';
	const LABEL		= 'Lifestream_ListenSongLabel';
	const AUTHOR	= 'Anonymous, Alan Isherwood';
	
	function __toString()
	{
		return $this->get_option('username');
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
		return 'http://www.last.fm/user/'.$this->get_option('username');
	}

	function get_url()
	{
		if ($this->get_option('loved'))
		{
			$feed_name = 'recentlovedtracks';
		}
		else
		{
			$feed_name = 'recenttracks';
		}
		
		return 'http://ws.audioscrobbler.com/1.0/user/'.$this->get_option('username').'/'.$feed_name.'.xml';
	}
	
	function get_label_class($key)
	{
		if ($this->get_option('loved')) $cls = 'Lifestream_LikeLabel';
		else $cls = 'Lifestream_ListenSongLabel';
		return $cls;
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
		$url = $this->get_url();
		$response = $this->lifestream->file_get_contents($url);
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
		return $this->lifestream->get_anchor_html(htmlspecialchars($item['artist']).' &#8211; '.htmlspecialchars($item['name']), $item['link']);
	}
}
$lifestream->register_feed('Lifestream_LastFMFeed');
?>