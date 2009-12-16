<?php
class Lifestream_MobypictureFeed extends Lifestream_PhotoFeed
{
	const ID	= 'mobypicture';
	const NAME	= 'Mobypicture';
	const URL	= 'http://www.mobypicture.com/';

	function __toString()
	{
		return $this->options['username'];
	}

	function get_options()
	{
		return array(
			'username' => array($this->lifestream->__('Username:'), true, '', ''),
		);
	}

	function get_public_url()
	{
		return 'http://www.mobypicture.com/user/'.$this->options['username'];
	}

	function get_url()
	{
		return 'http://www.mobypicture.com/rss/'.$this->options['username'].'/user.rss';
	}
}
$lifestream->register_feed('Lifestream_MobypictureFeed');
?>