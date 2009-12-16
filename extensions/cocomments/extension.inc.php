<?php
class Lifestream_CoCommentsFeed extends Lifestream_Feed
{
	const ID			= 'cocomment';
	const NAME			= 'coComment';
	const URL			= 'http://www.cocomment.com/';
	const LABEL			= 'Lifestream_CommentLabel';
	const HAS_EXCERPTS	= true;
	
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
	
	function get_url()
	{
		return 'http://www.cocomment.com/myWebRss/'.$this->options['username'].'.rss';
	}
	
	function get_public_url()
	{
		return 'http://www.cocomment.com/comments/'.$this->options['username'];
	}

}
$lifestream->register_feed('Lifestream_CoCommentsFeed');
?>