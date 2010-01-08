<?php
class Lifestream_MobypictureFeed extends Lifestream_PhotoFeed
{
	const ID	= 'mobypicture';
	const NAME	= 'Mobypicture';
	const URL	= 'http://www.mobypicture.com/';

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
		return 'http://www.mobypicture.com/user/'.$this->get_option('username');
	}

	function get_url()
	{
		return 'http://www.mobypicture.com/rss/'.$this->get_option('username').'/user.rss';
	}
}
$lifestream->register_feed('Lifestream_MobypictureFeed');
?>