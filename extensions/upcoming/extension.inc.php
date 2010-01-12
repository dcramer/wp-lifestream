<?php
class Lifestream_UpcomingFeed extends Lifestream_Feed
{
	const ID	= 'upcoming';
	const NAME	= 'Upcoming';
	const URL	= 'http://upcoming.yahoo.com/';
	const LABEL	= 'Lifestream_AttendEventLabel';
	const DESCRIPTION = 'You can get your API key <a href="http://upcoming.yahoo.com/services/api/keygen.php">here</a>. Please note, this feed will only show events you mark as attending.';

	function __toString()
	{
		return $this->get_option('user_id');
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
		if (preg_match('/\/user\/([0-9]+)\//i', $this->get_option('url'). $match))
		{
			$this->update_option('user_id', $match[1]);
		}
		else
		{
			throw new Lifestream_Error("Invalid feed URL.");
		}
		parent::save_options();
	}

	function get_public_url()
	{
		return 'http://upcoming.yahoo.com/user/'.$this->get_option('user_id').'/';
	}

	function get_url()
	{
		return 'http://upcoming.yahooapis.com/services/rest/?api_key='.$this->get_option('api_key').'&method=user.getWatchlist&user_id='.$this->get_option('user_id').'&show=all';
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
		$url = $this->get_url();
		$response = $this->lifestream->file_get_contents($url);
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
?>