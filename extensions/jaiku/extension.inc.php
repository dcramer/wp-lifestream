<?php
class Lifestream_JaikuFeed extends Lifestream_Feed
{
	const ID			= 'jaiku';
	const NAME		  = 'Jaiku';
	const URL		   = 'http://www.jaiku.com/';
	const NS_JAIKU	  = 'http://jaiku.com/ns';
	const LABEL		= 'Lifestream_MessageLabel';
	const CAN_GROUP	= false;
	
	function get_options()
	{		
		return array(
			'username' => array($this->lifestream->__('Username:'), true, '', ''),
		);
	}

	function get_url()
	{
		return 'http://'.$this->get_option('username').'.jaiku.com/feed/rss';
	}
	
	function get_user_url($user)
	{
		return 'http://'.$user.'.jaiku.com';
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
		return $this->parse_users($this->parse_urls(htmlspecialchars($item['title']))).' ['.$this->lifestream->get_anchor_html(htmlspecialchars($this->get_option('username')), $item['link']).']';
	}

	function yield($row, $url, $key)
	{
		//I don't know what this is for, would not fetch when running
		//if (!lifestream_str_startswith($row->get_link(), 'http://'.$this->get_option('username').'.jaiku.com/presence/')) return;
		
		$data = parent::yield($row, $url, $key);
		//preg_match('|<p>([^<]+)</p>|i', $row->get_title(), $matches);
		//$data['title'] = $matches[1];
		$data['title'] = $row->get_title();
		return $data;
	}
}
$lifestream->register_feed('Lifestream_JaikuFeed');
?>