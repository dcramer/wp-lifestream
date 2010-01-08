<?php
class Lifestream_FoodFeedFeed extends Lifestream_Feed
{
	const ID	= 'foodfeed';
	const NAME	= 'FoodFeed';
	const URL	= 'http://www.foodfeed.us/';
	const LABEL	= 'Lifestream_EatLabel';
	
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
		return 'http://'.$this->get_option('username').'.foodfeed.us/rss';
	}
	
	function get_public_url()
	{
		return 'http://'.$this->get_option('username').'.foodfeed.us/';
	}

	function render_item($row, $item)
	{
		return htmlspecialchars($item['title']);
	}
}
$lifestream->register_feed('Lifestream_FoodFeedFeed');
?>