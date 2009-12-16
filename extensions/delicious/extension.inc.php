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
		return $this->options['username'];
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
		$url = 'http://del.icio.us/rss/'.$this->options['username'];
		if (!empty($this->options['filter_tag'])) $url .= '/'.$this->options['filter_tag'];
		return $url;
	}
	
	function get_public_url()
	{
		return 'http://del.icio.us/'.$this->options['username'];
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