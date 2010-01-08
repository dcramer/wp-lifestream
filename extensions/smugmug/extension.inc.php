<?php
class Lifestream_SmugMugFeed extends Lifestream_PhotoFeed
{
	const ID			= 'smugmug';
	const NAME			= 'SmugMug';
	const URL			= 'http://www.smugmug.com/';

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
		return 'http://'.$this->get_option('username').'.smugmug.com/';
	}

	function get_url()
	{
		return 'http://www.smugmug.com/hack/feed.mg?Type=nicknameRecentPhotos&Data='.$this->get_option('username').'&format=atom10';
	}
}
$lifestream->register_feed('Lifestream_SmugMugFeed');
?>