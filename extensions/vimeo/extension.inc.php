<?php
class Lifestream_VimeoFeed extends Lifestream_PhotoFeed
{
	const ID			= 'vimeo';
	const NAME			= 'Vimeo';
	const URL			= 'http://www.vimeo.com/';
	const DESCRIPTION	= 'Your user ID is the digits at the end of your profile URL. For example, if your profile is <strong>http://www.vimeo.com/user406516</strong> then your user ID is <strong>406516</strong>.';
	
	private $image_match_regexp = '/src="(http\:\/\/[a-z0-9]+\.vimeo\.com\/[^"]+)"/i';
	
	function __toString()
	{
		return $this->get_option('user_id');
	}
	
	function get_options()
	{
		return array(
			'user_id' => array($this->lifestream->__('User ID:'), true, '', ''),
			'show_videos' => array($this->lifestream->__('Include videos posted in this feed.'), false, true, true),
			'show_likes' => array($this->lifestream->__('Include liked videos in this feed.'), false, true, true),
		);
	}
	
	function get_label_class($key)
	{
		if ($key == 'like') $cls = 'Lifestream_LikeVideoLabel';
		else $cls = 'Lifestream_VideoLabel';
		return $cls;
	}
	
	function get_videos_url()
	{
		return 'http://www.vimeo.com/'.$this->get_option('user_id').'/videos/rss';
	}
	
	function get_likes_url()
	{
		return 'http://www.vimeo.com/'.$this->get_option('user_id').'/likes/rss';
	}

	function get_public_url()
	{
		return 'http://www.vimeo.com/'.$this->get_option('user_id');
	}

	function get_url()
	{
		$urls = array();
		if ($this->get_option('show_videos'))
		{
			$urls[] = array($this->get_videos_url(), 'video');
		}
		if ($this->get_option('show_likes'))
		{
			$urls[] = array($this->get_likes_url(), 'like');
		}
		return $urls;
	}
	
	function yield($row, $url, $key)
	{
		$data = parent::yield($row, $url, $key);
		preg_match($this->image_match_regexp, $row->get_description(), $match);
		$data['thumbnail'] = $match[1];
		return $data;
	}
}
$lifestream->register_feed('Lifestream_VimeoFeed');
?>