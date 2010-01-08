<?php
class Lifestream_LibraryThingFeed extends Lifestream_PhotoFeed
{
	const ID	= 'librarything';
	const NAME	= 'LibraryThing';
	const URL	= 'http://www.librarything.com/';
	const LABEL	= 'Lifestream_BookLabel';

	function __toString()
	{
		return $this->get_option('member_name');
	}

	function get_options()
	{
		return array(
			'member_name' => array($this->lifestream->__('Username:'), true, '', ''),
		);
	}

	function get_public_url()
	{
		return 'http://www.librarything.com/catalog/'.$this->get_option('member_name');
	}

	function get_url()
	{
		return 'http://www.librarything.com/rss/recent/'.$this->get_option('member_name');
	}

	private $image_match_regexp = '/img\s+src="([^"]+\.jpg)"/i';

	function yield($row, $url, $key)
	{
		$data = parent::yield($row, $url, $key);
		preg_match($this->image_match_regexp, $row->get_description(), $match);
		$data['thumbnail'] = $match[1];
		return $data;
	}
}
$lifestream->register_feed('Lifestream_LibraryThingFeed');
?>