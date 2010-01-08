<?php
class Lifestream_DeliciousFeed extends Lifestream_Feed
{
	const ID	= 'delicious';
	const NAME	= 'Delicious';
	const URL	= 'http://www.delicious.com/';
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
		$url = 'http://del.icio.us/rss/'.$this->get_option('username');
		if ($this->get_option('filter_tag')) $url .= '/'.$this->get_option('filter_tag');
		return $url;
	}
	
	function get_public_url()
	{
		return 'http://del.icio.us/'.$this->get_option('username');
	}

	function yield($row, $url, $key)
	{
		$data = parent::yield($row, $url, $key);
		$subject =& $row->get_item_tags(SIMPLEPIE_NAMESPACE_DC_11, 'subject');
		$tags = explode(' ', $row->sanitize($subject[0]['data'], SIMPLEPIE_CONSTRUCT_TEXT));
		$data['tags'] = $tags;
		return $data;
	}
}
$lifestream->register_feed('Lifestream_DeliciousFeed');
?>