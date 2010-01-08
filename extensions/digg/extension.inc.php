<?php
class Lifestream_DiggFeed extends Lifestream_Feed
{
	const ID	= 'digg';
	const NAME	= 'Digg';
	const URL	= 'http://www.digg.com/';
	const LABEL	= 'Lifestream_LikeStoryLabel';
	
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
		return 'http://www.digg.com/users/'.$this->get_option('username');
	}
	
	function get_url()
	{
		return 'http://www.digg.com/users/'.$this->get_option('username').'/history.rss';
	}
}
$lifestream->register_feed('Lifestream_DiggFeed');
?>