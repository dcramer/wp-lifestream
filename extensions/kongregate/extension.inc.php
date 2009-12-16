<?php
class Lifestream_KongregateFeed extends Lifestream_Feed
{
	const ID			= 'kongregate';
	const NAME			= 'Kongregate';
	const URL			= 'http://www.kongregate.com/';
	const DESCRIPTION	= '';
	const LABEL			= 'Lifestream_ReceiveBadgeLabel';
	
	function __toString()
	{
		return $this->options['username'];
	}

	function get_options()
	{
		return array(
			'username' => array($this->lifestream->__('Username:'), true, '', ''),
		);
	}
	
	function get_url()
	{
		return 'http://www.kongregate.com/accounts/'.$this->options['username'].'/badges.rss';
	}
	
	function get_public_url()
	{
		return 'http://www.kongregate.com/accounts/'.$this->options['username'];
	}
}
$lifestream->register_feed('Lifestream_KongregateFeed');
?>