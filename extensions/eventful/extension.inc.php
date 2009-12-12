<?php
class Lifestream_EventfulFeed extends Lifestream_Feed
{
	const ID	= 'eventful';
	const NAME	= 'Eventful';
	const URL	= 'http://www.eventful.com/';
	const LABEL	= 'Lifestream_AttendEventLabel';

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
		return 'http://eventful.com/users/'.urlencode($this->get_option('username'));
	}

	function get_url()
	{
		return 'http://eventful.com/atom/users/'.urlencode($this->get_option('username'));
	}
}
$lifestream->register_feed('Lifestream_EventfulFeed');
?>