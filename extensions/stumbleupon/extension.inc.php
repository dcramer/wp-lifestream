<?php
class Lifestream_StumbleUponFeed extends Lifestream_PhotoFeed
{
	const ID	= 'stumbleupon';
	const NAME	= 'StumbleUpon';
	const URL	= 'http://www.stumbleupon.com/';
	
	function __toString()
	{
		return $this->get_option('username');
	}
	
	function get_options()
	{
		return array(
			'username' => array($this->lifestream->__('Username:'), true, '', ''),
			'show_reviews' => array($this->lifestream->__('Include reviews in this feed.'), false, true, true),
			'show_favorites' => array($this->lifestream->__('Include favorites in this feed.'), false, true, false),
		);
	}
	
	function get_label_class($key)
	{
		if ($key == 'review') $cls = 'Lifestream_ReviewWebsiteLabel';
		else $cls = 'Lifestream_LikeWebsiteLabel';
		return $cls;
	}
	
	function get_favorites_url()
	{
		return 'http://rss.stumbleupon.com/user/'.$this->get_option('username').'/favorites';
	}
	
	function get_reviews_url()
	{
		return 'http://rss.stumbleupon.com/user/'.$this->get_option('username').'/reviews';
	}

	function get_public_url()
	{
		return 'http://'.$this->get_option('username').'.stumbleupon.com';
	}

	function get_url()
	{
		$urls = array();
		if ($this->get_option('show_reviews'))
		{
			$urls[] = array($this->get_reviews_url(), 'review');
		}
		if ($this->get_option('show_favorites'))
		{
			$urls[] = array($this->get_favorites_url(), 'favorite');
		}
		return $urls;
	}
}
$lifestream->register_feed('Lifestream_StumbleUponFeed');
?>