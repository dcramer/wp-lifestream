<?php
class Lifestream_MagnoliaFeed extends Lifestream_PhotoFeed
{
	const ID	= 'magnolia';
	const NAME	= 'Ma.gnolia';
	const URL	= 'http://www.ma.gnolia.com/';
	const LABEL	= 'Lifestream_BookmarkLabel';

	private $image_match_regexp = '/src="(http:\/\/scst\.srv\.girafa\.com\/[^"]+)"/i';
	
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
		return 'http://ma.gnolia.com/rss/full/people/'.$this->get_option('username');
	}
	
	function get_public_url()
	{
		return 'http://ma.gnolia.com/people/'.$this->get_option('username');
	}
	
	function yield($row, $url, $key)
	{
		$data = parent::yield($row, $url, $key);
		preg_match($this->image_match_regexp, $row->get_description(), $match);
		$data['thumbnail'] = $match[1];
		return $data;
	}
}
$lifestream->register_feed('Lifestream_MagnoliaFeed');
?>