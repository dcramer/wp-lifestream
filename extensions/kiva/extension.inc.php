<?php
class Lifestream_MicroloanLabel extends Lifestream_PhotoLabel
{
	const CAN_GROUP = false;
	
	function get_label_single()
	{
		return $this->lifestream->__('Gave a microloan to %2$s.', $this->get_feed_label(), $this->get_single_link());
	}
	
	function get_label_plural()
	{
		return $this->lifestream->__('Gave microloans to %s people.', $this->_get_show_details_link(), $this->get_feed_label());
	}
}

class Lifestream_KivaFeed extends Lifestream_PhotoFeed
{
	const ID	= 'kiva';
	const NAME	= 'Kiva';
	const URL	= 'http://www.kiva.org/';
	const LABEL	= 'Lifestream_MicroloanLabel';

	function __toString()
	{
		return $this->get_option('username');
	}

	function get_options()
	{		
		return array(
			'username' => array($this->lifestream->__('Lender ID:'), true, '', ''),
		);
	}

	function get_public_url()
	{
		return 'http://www.kiva.org/lender/'.urlencode($this->get_option('username'));
	}

	function get_url()
	{
		return 'http://www.kiva.org/rss/lender/'.urlencode($this->get_option('username'));
	}
}

$lifestream->register_feed('Lifestream_KivaFeed');
?>