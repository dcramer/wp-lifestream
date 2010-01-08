<?php
class Lifestream_ReadernautFeed extends Lifestream_Feed
{
	const ID			= 'readernaut';
	const NAME			= 'Readernaut';
	const URL			= 'http://www.readernaut.com/';
	const DESCRIPTION	= 'Readernaut is my library, my notebook, my book club.';
	const LABEL			= 'Lifestream_BookLabel';
	
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

	function get_url()
	{
		return 'http://readernaut.com/rss/'.$this->get_option('username').'/books/';
	}

	function get_public_url()
	{
		return 'http://readernaut.com/'.$this->get_option('username');
	}
}
$lifestream->register_feed('Lifestream_ReadernautFeed');
?>