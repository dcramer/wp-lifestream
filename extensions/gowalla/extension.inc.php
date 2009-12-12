<?php
class Lifestream_GowallaFeed extends Lifestream_Feed
{
	const ID	= 'gowalla';
	const NAME	= 'Gowalla';
	const URL	= 'http://www.gowalla.com/';
	const LABEL	= 'Lifestream_LocationLabel';

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
		return 'http://gowalla.com/users/'.urlencode($this->get_option('username'));
	}

	function get_url()
	{
		return 'http://gowalla.com/users/'.urlencode($this->get_option('username')).'/visits.atom';
	}
}
$lifestream->register_feed('Lifestream_GowallaFeed');
?>