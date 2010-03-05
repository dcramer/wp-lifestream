<?php
class Lifestream_eBayFeed extends Lifestream_Feed
{
	const ID			= 'ebay';
	const NAME			= 'eBay';
	const URL			= 'http://www.ebay.com/';
	const DESCRIPTION	= 'Shows what items you put up for sale.';
	const AUTHOR		= 'Kyle McNally';
	
	function get_options()
	{		
		return array(
			'username' => array($this->lifestream->__('Username:'), true, '', ''),
		);
	}
	
	function get_url()
	{
		return 'http://shop.ebay.com/'.$this->get_option('username').'/m.html?_dmd=1&_ipg=50&_rss=1&_sop=10';
	}

}
$lifestream->register_feed('Lifestream_eBayFeed');
?>