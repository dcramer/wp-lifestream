<?php
class Lifestream_DailyBoothFeed extends Lifestream_Feed
{
	const ID			= 'dailybooth';
	const NAME			= 'DailyBooth';
	const URL			= 'http://www.dailybooth.com/';
	const DESCRIPTION	= '';
	const LABEL			= 'Lifestream_MessageLabel';
	const AUTHOR		='BandonRandon';
	
	function __toString()
	{
		return $this->get_option('username');
	}
	
	function get_options()
	{
		return array(
			'username' => array($this->lifestream->__('Username:'), true, '', ''),
		);
	}
	
	function get_public_url()
	{
		return 'http://www.dailybooth.com/'.$this->get_option('username');
	}
	
	function get_url()
	{
		return 'http://dailybooth.com/rss/'.$this->get_option('username').'.rss';
	}
}
$lifestream->register_feed('Lifestream_DailyBoothFeed');
?>