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
		return $this->get_option('username');
	}

	function get_options()
	{
		return array(
			'username' => array($this->lifestream->__('Steam ID:'), true, '', ''),
		);
	}
	
	function get_public_url()
	{
		return 'http://steamcommunity.com/id/'.$this->get_option('username');
	}
	
	function get_url()
	{
		return 'http://pipes.yahoo.com/pipes/pipe.run?_id=0bc042425b3f744977252cd205b57e66&_render=rss&steamid='.$this->get_option('username');
	}
}
$lifestream->register_feed('Lifestream_SteamFeed');
?>
