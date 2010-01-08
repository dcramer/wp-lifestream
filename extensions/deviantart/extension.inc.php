<?php
class Lifestream_DeviantArtFeed extends Lifestream_PhotoFeed
{
	const ID	= 'deviantart';
	const NAME	= 'deviantART';
	const URL	= 'http://www.deviantart.com/';

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
		return 'http://'.urlencode($this->get_option('username')).'.deviantart.com/';
	}

	function get_url()
	{
		return 'http://backend.deviantart.com/rss.xml?q=gallery%3A'.urlencode($this->get_option('username'));
	}
}
$lifestream->register_feed('Lifestream_DeviantArtFeed');
?>