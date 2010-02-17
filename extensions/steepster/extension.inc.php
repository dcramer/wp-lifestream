<?php
class Lifestream_SteepsterFeed extends Lifestream_Feed
{
	const ID	= 'steepster';
	const NAME	= 'Steepster';
	const URL	= 'http://www.steepster.com/';
	const LABEL	= 'Lifestream_DrankLabel';
	
	function get_options()
	{
		return array(
			'username' => array($this->lifestream->__('Username:'), true, '', ''),
		);
	}

	function get_url()
	{
		return 'http://steepster.com/'.urlencode($this->get_option('username')).'/feed.rss';
	}
	
}
$lifestream->register_feed('Lifestream_SteepsterFeed');
?>