<?php
class Lifestream_SlideShareFeed extends Lifestream_Feed
{
	const ID	= 'slideshare';
	const NAME	= 'SlideShare';
	const URL	= 'http://www.slideshare.net/';
	const LABEL	= 'Lifestream_ShareSlideLabel';
	
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
		return 'http://www.slideshare.net/'.$this->get_option('username');
	}
	
	function get_url()
	{
		return 'http://www.slideshare.net/rss/user/'.$this->get_option('username');
	}
}
$lifestream->register_feed('Lifestream_SlideShareFeed');
?>