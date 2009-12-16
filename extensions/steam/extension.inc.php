<?php
class Lifestream_SteamFeed extends Lifestream_Feed
{
	const ID	= 'steam';
	const NAME	= 'Steam';
	const URL	= 'http://www.steampowered.com/';
	const LABEL	= 'Lifestream_UnlockAchievementLabel';
	const MEDIA	= 'text';
	
	function __toString()
	{
		return $this->options['username'];
	}

	function get_options()
	{
		return array(
			'username' => array($this->lifestream->__('Steam ID:'), true, '', ''),
		);
	}
	
	function get_public_url()
	{
		return 'http://steamcommunity.com/id/'.$this->options['username'];
	}
	
	function get_url()
	{
		return 'http://pipes.yahoo.com/pipes/pipe.run?_id=IH0KF8OZ3RGJPl7dBR50VA&_render=rss&steamid='.$this->options['username'];
	}
}
$lifestream->register_feed('Lifestream_SteamFeed');
?>