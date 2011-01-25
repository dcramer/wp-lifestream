<?php
class Lifestream_WakoopaFeed extends Lifestream_Feed
{
	const ID			= 'wakoopa';
	const NAME			= 'Wakoopa';
	const URL			= 'http://www.wakoopa.com/';
	const LABEL			= 'Lifestream_ApplicationLabel';
	const DESCRIPTION	= 'Displays your recently used applications.';
	const AUTHOR		= 'Alan Isherwood';

	function get_options()
	{		
		return array(
			'username' => array($this->lifestream->__('Username:'), true, '', ''),
		);
	}
	
	function get_public_url()
	{
		return 'http://wakoopa.com/'.$this->get_option('username').'/';
	}

	function get_url()
	{
		return 'http://wakoopa.com/'.$this->get_option('username').'/feed/recently_used';
	}	

    function yield($row)
    {
        return array(
            'date'		=>	$row->get_date('U'),
            'link'		=>	html_entity_decode($row->get_link()),
            'title'		=>	html_entity_decode($row->get_title()),
        );
    }
}
$lifestream->register_feed('Lifestream_WakoopaFeed');
?>