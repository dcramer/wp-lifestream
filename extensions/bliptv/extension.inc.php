<?php
class Lifestream_BlipTVFeed extends Lifestream_Feed
{
	const ID	= 'bliptv';
	const NAME	= 'Blip.tv';
	const URL	= 'http://www.blip.tv/';
	const LABEL	= 'Lifestream_WatchEpisodeLabel';
	
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
		return 'http://'.$this->options['username'].'.blip.tv/';
	}
	
	function get_url()
	{
		return $this->get_public_url().'rss';
	}
}
$lifestream->register_feed('Lifestream_BlipTVFeed');
?>