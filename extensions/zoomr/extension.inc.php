<?php
class Lifestream_ZooomrFeed extends Lifestream_PhotoFeed
{
	const ID			= 'zooomr';
	const NAME			= 'Zooomr';
	const URL			= 'http://www.zooomr.com/';
	const DESCRIPTION	= '';
	
	function __toString()
	{
		return $this->options['username'];
	}
	
	function get_url()
	{
		return $this->options['url'];
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
		return 'http://www.zooomr.com/photos/'.$this->options['username'].'/';
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