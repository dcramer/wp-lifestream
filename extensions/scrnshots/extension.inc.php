<?php
class Lifestream_ScrnShotsFeed extends Lifestream_PhotoFeed
{
	const ID			= 'scrnshots';
	const NAME			= 'Scrnshots';
	const URL			= 'http://www.scrnshots.com/';
	const DESCRIPTION	= 'ScrnShots is the best way to take and share screenshots of web and screen based design. Upload as many screenshots as you want, embed them in your blog, discuss them with your contacts and become a better designer!';

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
		return 'http://scrnshots.com/users/'.$this->get_option('username').'/screenshots.rss';
	}

	function get_public_url()
	{
		return 'http://scrnshots.com/users/'.$this->get_option('username');
	}

	function yield($row, $url, $key)
	{
		$data = parent::yield($row, $url, $key);

		$description = $data['description'];
		$title = strip_tags($description);
		$img = strip_tags($description,'<img>');
		$src = str_replace($title,'',$img);
		$large = preg_replace('/.*src=([\'"])((?:(?!\1).)*)\1.*/si','$2',$src);
		$small = str_replace('large','med_rect',$large);

		$data['thumbnail'] = $small;
		$data['image'] = $large;
		return $data;
	}
}
$lifestream->register_feed('Lifestream_ScrnShotsFeed');
?>