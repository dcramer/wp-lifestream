<?php
class Lifestream_ZooomrFeed extends Lifestream_PhotoFeed
{
	const ID			= 'zooomr';
	const NAME			= 'Zooomr';
	const URL			= 'http://www.zooomr.com/';
	const DESCRIPTION	= '';
	
	function __toString()
	{
		return $this->get_option('username');
	}
	
	function get_url()
	{
		return $this->get_option('url');
	}

	function get_options()
	{
		return array(
			'url' => array($this->lifestream->__('Feed URL:'), true, '', ''),
			'username' => array($this->lifestream->__('Username:'), true, '', ''),
		);
	}
	
	function get_public_url()
	{
		return 'http://www.zooomr.com/photos/'.$this->get_option('username').'/';
	}
	
	function yield($row, $url, $key)
	{
		$data = parent::yield($row, $url, $key);
		$data['image'] = str_replace('_m', '', $data['image']);
		return $data;
	}
	
}
$lifestream->register_feed('Lifestream_ZooomrFeed');
?>