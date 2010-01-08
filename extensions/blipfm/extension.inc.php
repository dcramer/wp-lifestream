<?php
class Lifestream_BlipFMFeed extends Lifestream_Feed
{
	const ID		= 'blipfm';
	const NAME		= 'Blip.fm';
	const URL		= 'http://blip.fm/';
	const LABEL		= 'Lifestream_MessageLabel';
	const CAN_GROUP	= false;

	
	function get_options()
	{		
		return array(
			'username' => array($this->lifestream->__('Username:'), true, '', ''),
		);
	}
		
	function get_user_url($user)
	{
		return 'http://blip.fm/'.$user;
	}
	
	function get_url()
	{
		return 'http://blip.fm/feed/'.$this->get_option('username');
	}
	
	function get_event_display(&$event, &$bit)
	{
		return $bit['title'] ? $bit['title'] : $bit['track'];
	}

	function get_user_link($user)
	{
		return $this->lifestream->get_anchor_html('@'.htmlspecialchars($user), $this->get_user_url($user), array('class'=>'user'));
	}
	
	function parse_users($text)
	{
		return preg_replace_callback('/([^\w]*)@([a-z0-9_\-\/]+)\b/i', array($this, 'get_user_link'), $text);
	}

	function render_item($row, $item)
	{
		return $this->parse_users($this->parse_urls(htmlspecialchars($this->get_event_display($row, $item)))).' ['.$this->lifestream->get_anchor_html("#", $item['link']).']';
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
?>