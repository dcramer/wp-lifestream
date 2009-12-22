<?php
class Lifestream_FoursquareFeed extends Lifestream_Feed
{
	const ID			= 'foursquare';
	const NAME			= 'Foursquare';
	const URL			= 'http://www.foursquare.com/';
	const DESCRIPTION	= 'To obtain your Foursquare feed URL, visit the <a href="http://foursquare.com/feeds/" target="_blank">feeds.foursquare</a> page while logged in to Foursquare. If you log in from that page, you\'ll need to go back to the feeds page manually since it is not linked anywhere on the normal Foursquare site.';
	const LABEL 		= 'Lifestream_LocationLabel';

	function __toString()
	{
		return $this->options['username'];
	}
	
	function get_options()
	{
		return array(
			'username' => array($this->lifestream->__('Username:'), true, '', ''),
			'rssfeedurl' => array($this->lifestream->__('RSS Feed URL:'), false, '', ''),
		);
	}

	function get_url()
	{
		return $this->options['rssfeedurl'];
	}
	
	function get_public_url()
	{
		return 'http://www.foursquare.com/user/'.$this->options['username'];
	}
}
$lifestream->register_feed('Lifestream_FoursquareFeed');
?>