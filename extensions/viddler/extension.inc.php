<?php
class Lifestream_ViddlerFeed extends Lifestream_PhotoFeed
{
	const ID			= 'viddler';
	const NAME			= 'Viddler';
	const URL			= 'http://www.viddler.com/';
	const DESCRIPTION	= '';
	const LABEL			= 'Lifestream_VideoLabel';
	
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
		return 'http://www.viddler.com/explore/'.$this->get_option('username').'/';
	}
	
	function get_url()
	{
		return 'http://www.viddler.com/explore/'.$this->get_option('username').'/videos/feed/';
	}
	
	function yield($row, $url, $key)
	{
		$data = parent::yield($row, $url, $key);
		$data['image'] = str_replace('_m', '', $data['image']);
		return $data;
	}
	
	function render_item($row, $item)
	{
		$attrs = array(
			'class' => 'photo',
			'title' => htmlspecialchars($item['title'])
		);
		if ($this->lifestream->get_option('use_ibox') == '1')
		{
			$attrs['rel'] = 'ibox';
		}
		return $this->lifestream->get_anchor_html('<img src="'.$item['thumbnail'].'" alt="" width="50"/>', $item['link'], $attrs);
	}
	
}
$lifestream->register_feed('Lifestream_ViddlerFeed');
?>