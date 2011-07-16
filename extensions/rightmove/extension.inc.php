<?php
class Lifestream_RightMoveFeed extends Lifestream_Feed
{
	const ID			= 'rightmove';
	const NAME			= 'RightMove';
	const URL			= 'http://www.rightmove.co.uk/';
	const DESCRIPTION	= 'Enter your Rightmove Search RSS URL Here.';
	const LABEL			= 'Lifestream_MessageLabel';
	const AUTHOR		= 'Robert McGhee';
	
	function __toString()
	{
		return $this->get_option('url');
	}

	function get_options()
	{
		return array(
			'url' => array($this->lifestream->__('Search RSS URL:'), true, '', ''),
		);
	}
	
	function get_url()
	{
		return $this->options['url'];
	}
	
	function render_item($row, $item)
    	{
        	return  $this->lifestream->get_anchor_html(htmlspecialchars($item['title']), $item['link']) . " added today";
    	}

}
$lifestream->register_feed('Lifestream_RightMoveFeed');
?>
