<?php
class Lifestream_MixxFeed extends Lifestream_Feed
{
	const ID	= 'mixx';
	const NAME	= 'Mixx';
	const URL	= 'http://www.mixx.com/';
	
	function __toString()
	{
		return $this->get_option('username');
	}
	
	function get_options()
	{		
		return array(
			'username' => array($this->lifestream->__('Username:'), true, '', ''),
			'show_comments' => array($this->lifestream->__('Show Comments.'), false, true, false),
			'show_favorites' => array($this->lifestream->__('Show Favorites.'), false, true, true),
			'show_submissions' => array($this->lifestream->__('Show Submissions.'), false, true, true),
		);
	}
	
	function get_public_url()
	{
		return 'http://www.mixx.com/users/'.$this->get_option('username');
	}
	
	function get_url()
	{
		return 'http://www.mixx.com/feeds/users/'.$this->get_option('username');
	}
	
	function yield($row, $url, $key)
	{
		$data = parent::yield($row, $url, $key);
		$title = $this->lifestream->html_entity_decode($row->get_title());
		if (lifestream_str_startswith($title, 'Comment on: '))
		{
			if (!$this->get_option('show_comments')) return;
			$key = 'comment';
			$title = substr($title, 12);
		}
		elseif (lifestream_str_startswith($title, 'Submitted: '))
		{
			if (!$this->get_option('show_submissions')) return;
			$key = 'submit';
			$title = substr($title, 11);
		}
		elseif (lifestream_str_startswith($title, 'Favorite: '))
		{
			if (!$this->get_option('show_favorites')) return;
			$key = 'favorite';
			$title = substr($title, 10);
		}
		else
		{
			return;
		}
		
		$data['title'] = $title;
		$data['key'] = $key;
		return $data;
	}
	
	function get_label_class($key)
	{
		if ($key == 'favorite') $cls = 'Lifestream_LikeStoryLabel';
		elseif ($key == 'comment') $cls = 'Lifestream_CommentLabel';
		elseif ($key == 'submit') $cls = 'Lifestream_ShareStoryLabel';
		return $cls;
	}
}
$lifestream->register_feed('Lifestream_MixxFeed');
?>