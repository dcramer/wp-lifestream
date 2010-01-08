<?php
class Lifestream_SkitchFeed extends Lifestream_PhotoFeed
{
	const ID			= 'skitch';
	const NAME			= 'Skitch';
	const URL			= 'http://www.skitch.com/';
	const DESCRIPTION	= '';
	
	private $image_match_regexp = '/src="(http\:\/\/img+\.skitch\.com\/[^"]+\.jpg)"/i';
	
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
	
	function get_public_url()
	{
		return 'http://www.skitch.com/'.$this->get_option('username').'/';
	}
	
	function get_url()
	{
		return 'http://www.skitch.com/feeds/'.$this->get_option('username').'/atom.xml';
	}
	
	function yield($row, $url, $key)
	{
		$data = parent::yield($row, $url, $key);
		preg_match($this->image_match_regexp, $row->get_description(), $match);
		$data['thumbnail'] = $match[1];
		$data['image'] = str_replace('.preview.', '', $match[1]);
		return $data;
	}
}
$lifestream->register_feed('Lifestream_SkitchFeed');
?>