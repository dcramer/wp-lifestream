<?php
class Lifestream_AppBrainFeed extends Lifestream_Feed
{
	const ID			= 'appbrain';
	const NAME			= 'AppBrain';
	const URL			= 'http://www.appbrain.com/';
	const LABEL			= 'Lifestream_ApplicationLabel';
	const DESCRIPTION	= 'Recently installed applications on all your devices.';
	const AUTHOR		= 'Alan Isherwood';
	 
	function get_options()
	{		
		return array(
			'username' => array($this->lifestream->__('Username:'), true, '', ''),
		);
	}
	
	function get_public_url()
	{
		return 'http://www.appbrain.com/user/'.$this->get_option('username').'/';
	}

	function get_url()
	{
		return 'http://www.appbrain.com/rss/user/'.$this->get_option('username').'/';
	}

	function yield($row)
	{
		return array(
			'date'	=>	$row->get_date('U'),
			'link'	=>	html_entity_decode($row->get_link()),
			'title'	=>	html_entity_decode($row->get_title()),
		);
	}
}
$lifestream->register_feed('Lifestream_AppBrainFeed');
?>