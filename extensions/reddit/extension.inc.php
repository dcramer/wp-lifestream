<?php
class Lifestream_RedditFeed extends Lifestream_Feed
{
	const ID	= 'reddit';
	const NAME	= 'Reddit';
	const URL	= 'http://www.reddit.com/';
	const HAS_EXCERPTS	= true;
	
	function __toString()
	{
		return $this->get_option('username');
	}

	function get_options()
	{
		return array(
			'username' => array($this->lifestream->__('Username:'), true, '', ''),
			'show_submitted' => array($this->lifestream->__('Include submitted stories in this feed.'), false, true, true),
			'show_comments' => array($this->lifestream->__('Include posted comments in this feed.'), false, true, true),
			'show_liked' => array($this->lifestream->__('Include liked stories in this feed.'), false, true, true),
		);
	}

	function get_label_class($key)
	{
		if ($key == 'submitted')	$cls = 'Lifestream_ShareStoryLabel';
		elseif ($key == 'comment')	$cls = 'Lifestream_CommentLabel';
		elseif ($key == 'liked')	$cls = 'Lifestream_LikeStoryLabel';
		else $cls = 'Lifestream_Label';
		return $cls;
	}
	
	function get_submitted_url()
	{
		return 'http://www.reddit.com/user/'.$this->get_option('username').'/submitted.rss';
	}
	
	function get_comments_url()
	{
		return 'http://www.reddit.com/user/'.$this->get_option('username').'/comments.rss';
	}
	
	function get_liked_url()
	{
		return 'http://www.reddit.com/user/'.$this->get_option('username').'/liked.rss';
	}
	
	function parse_post_author($text)
	{
		preg_match('/submitted by <a href="http:\/\/www\.reddit\.com\/user\/([^\/"]+).*?"/', $text, $match);
		return $match[1];
	}

	function get_public_url()
	{
		return 'http://www.reddit.com/user/'.$this->get_option('username').'/';
	}

	function get_url()
	{
		$urls = array();
		if ($this->get_option('show_submitted'))$urls[] = array($this->get_submitted_url(), 'submitted');
		if ($this->get_option('show_comments'))	$urls[] = array($this->get_comments_url(), 'comment');
		if ($this->get_option('show_liked'))		$urls[] = array($this->get_liked_url(), 'liked');
		return $urls;
	}

	function yield($row, $url, $key)
	{
		$data = parent::yield($row, $url, $key);

		$title = $row->get_title();
		
		$chunk = sprintf('%s on', $this->get_option('username'));
		if (lifestream_str_startswith($title, $chunk))
		{
			$title = substr($title, strlen($chunk));
		}

		$data['title'] = $this->lifestream->html_entity_decode($title);
		$data['description'] = $this->lifestream->html_entity_decode($row->get_description());
		
		// Submissions are automatically liked, so we'll omit the redundant "liked" entry for posts submitted by the owner
		if ($key == 'liked' && $this->parse_post_author($data['description']) == $this->get_option('username'))
		{
			return null;
		}
		else
		{
			return $data;
		}
	}
}
$lifestream->register_feed('Lifestream_RedditFeed');
?>