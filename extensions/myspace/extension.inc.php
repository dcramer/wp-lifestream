<?php
class Lifestream_MySpaceFeed extends Lifestream_GenericFeed
{
	const ID			= 'myspace';
	const NAME			= 'MySpace';
	const URL			= 'http://www.myspace.com/';
	const DESCRIPTION	= 'To retrieve your MySpace blog URL, visit your profile and click "View all entries" under your blog. From there, you will see an "rss" link on the top right of the page.';
	const LABEL			= 'Lifestream_BlogLabel';
	const HAS_EXCERPTS	= true;

	function get_options()
	{		
		return array(
			'url' => array($this->lifestream->__('Feed URL:'), true, '', ''),
			'permalink_url' => array($this->lifestream->__('Website URL:'), false, '', ''),
		);
	}
	
	function get_public_name()
	{
		if ($this->get_option('feed_label'))
		{
			return $this->get_option('feed_label');
		}
		return 'MySpace Blog';
	}
	
	function get_public_url()
	{
		if ($this->get_option('permalink_url')) return $this->get_option('permalink_url');
		
		return 'http://'.$this->_get_domain();
	}

	function yield($row, $url, $key)
	{
		$data = parent::yield($row, $url, $key);
		$author =& $row->get_item_tags(SIMPLEPIE_NAMESPACE_DC_11, 'creator');
		$data['author'] = $this->lifestream->html_entity_decode($author[0]['data']);
		return $data;
	}	
}
$lifestream->register_feed('Lifestream_MySpaceFeed');
?>