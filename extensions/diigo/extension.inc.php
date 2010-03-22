<?php
/**
 * Diigo Feed parser
 * 
 * @author  oncletom
 * @version 1.0
 * @since   2010-03-22
 */
class Lifestream_DiigoFeed extends Lifestream_Feed
{
	const ID	= 'diigo';
	const NAME	= 'Diigo';
	const URL	= 'http://www.diigo.com/';
	const LABEL = 'Lifestream_BookmarkLabel';
	const HAS_EXCERPTS	= true;

	function __toString()
	{
		return $this->get_option('username');
	}

	function get_options()
	{
		return array(
			'username' => array($this->lifestream->__('Username:'), true, '', ''),
			'filter_tag' => array($this->lifestream->__('Limit items to tag:'), false, '', ''),
		);
	}

	function get_url()
	{
		$url = 'http://www.diigo.com/rss/user/'.$this->get_option('username');
		if ($this->get_option('filter_tag')) $url .= '/'.$this->get_option('filter_tag');
		return $url;
	}
	
	function get_public_url()
	{
		return 'http://www.diigo.com/user/'.$this->get_option('username');
	}

	function yield($row, $url, $key)
	{
		$data = parent::yield($row, $url, $key);
		
		/*
		 * Removing the "Posted by" mention
		 */
		$data['description'] = preg_replace('#<p><strong>Posted by.+</p>#sU', '', $data['description']);
		
		/*
		 * Extracting tags
		 */
		preg_match_all('#http://(www.)?diigo.com/user/[^\/]+/([^"\' ]+)#', $data['description'], $matches);
		if (isset($matches[2]) && !empty($matches[2]))
		{
		  $data['tags'] = array_map(array($row, 'sanitize'), $matches[2], array(SIMPLEPIE_CONSTRUCT_TEXT));
		  
		  $data['description'] = preg_replace('#<p><strong>Tags:</strong>.+$#sU', '', $data['description']);
		}

		return $data;
	}
}

$lifestream->register_feed('Lifestream_DiigoFeed');