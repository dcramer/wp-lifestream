<?php
class Lifestream_GetGlueFeed extends Lifestream_Feed
{
	const ID			= 'getglue';
	const NAME			= 'GetGlue';
	const URL			= 'http://www.getglue.com/';
	const DESCRIPTION	= 'You can obtain your GetGlue feed\'s URL by visiting your <a href="http://www.getglue.com/">Profile page</a> and copying the <strong>"[Username] Check-ins Feed"</strong> URL under <strong>RSS</strong>.';
	const LABEL			= 'Lifestream_MessageLabel';
	const AUTHOR		= 'Robert McGhee';
	
	function __toString()
	{
		return $this->get_option('url');
	}

	function get_options()
	{
		return array(
			'username' => array($this->lifestream->__('Username:'), true, '', ''),
			'url' => array($this->lifestream->__('Feed URL:'), true, '', ''),
		);
	}	

	function get_url()
	{
		return $this->options['url'];
	}
	
	function get_public_url()
	{
		return 'http://getglue.com/'.urlencode($this->get_option('username'));
	}
	
	function render_item($row, $item)
    {
    	$on_part = $this->get_option('username') . ' is ';
        return $this->lifestream->get_anchor_html(ucfirst(substr(htmlspecialchars($item['title']),strlen($on_part))), $item['link']);
    }

}
$lifestream->register_feed('Lifestream_GetGlueFeed');
?>