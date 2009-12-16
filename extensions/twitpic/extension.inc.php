<?php
class Lifestream_TwitPicFeed extends Lifestream_PhotoFeed
{
	const ID	= 'twitpic';
	const NAME	= 'TwitPic';
	const URL	= 'http://www.twitpic.com/';
	
	function get_options()
	{		
		return array(
			'username' => array($this->lifestream->__('Username:'), true, '', ''),
		);
	}
	
	function get_public_url()
	{
		return 'http://www.twitpic.com/photos/'.$this->options['username'];
	}

	function get_url()
	{
		return 'http://www.twitpic.com/photos/'.$this->options['username'].'/feed.rss';
	}

	function get_thumbnail_url($row, $item)
	{
		preg_match('#\/([^\/]+)$#i', $item['link'], $matches);
		return 'http://www.twitpic.com/show/thumb/'.$matches[1].'.jpg';
	}
}
$lifestream->register_feed('Lifestream_TwitPicFeed');
?>