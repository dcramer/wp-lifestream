<?php
class Lifestream_InstapaperFeed extends Lifestream_Feed
{
	const ID			= 'instapaper';
	const NAME			= 'Instapaper';
	const URL			= 'http://www.instapaper.com/';
	const DESCRIPTION	= 'You can obtain your Instapaper feed\'s URL by visiting your <a href="http://www.instapaper.com/u">Account</a> page, and copying the <strong>"RSS feed for this folder"</strong> URL under <strong>FOLDER TOOLS</strong>.';
	const LABEL			= 'Lifestream_MessageLabel';
	const AUTHOR			= 'Robert McGhee';
	
	function __toString()
	{
		return $this->get_option('url');
	}

	function get_options()
	{
		return array(
			'url' => array($this->lifestream->__('Feed URL:'), true, '', ''),
		);
	}

	function get_url()
	{
		return $this->options['url'];
	}

	function render_item($row, $item)
    	{
        	return "Bookmarked " . $this->lifestream->get_anchor_html(htmlspecialchars($item['title']), $item['link']);
    	}
}
$lifestream->register_feed('Lifestream_InstapaperFeed');
?>