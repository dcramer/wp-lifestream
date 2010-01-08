<?php
class Lifestream_PicasaFeed extends Lifestream_PhotoFeed
{
	const ID			= 'picasa';
	const NAME			= 'Picasa';
	const URL			= 'http://picasaweb.google.com/';
	const DESCRIPTION	= '';
	
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
		return 'http://picasaweb.google.com/data/feed/base/user/'.$this->get_option('username').'?alt=rss&kind=album&hl=en_US&access=public';
	}
	
	function get_public_url()
	{
		return 'http://picasaweb.google.com/'.$this->get_option('username');
	}
	
	function yield($row, $url, $key)
	{
		$data = parent::yield($row, $url, $key);
		$data['image'] = str_replace('_m', '', $data['image']);
		return $data;
	}
}
$lifestream->register_feed('Lifestream_PicasaFeed');
?>