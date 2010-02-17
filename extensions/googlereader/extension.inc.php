<?php
class Lifestream_GoogleReaderFeed extends Lifestream_Feed
{
	const ID			= 'googlereader';
	const NAME			= 'Google Reader';
	const URL			= 'http://www.google.com/reader/';
	const DESCRIPTION	= 'Your Google Reader feed URL is available by going to "Share items" under "Your stuff". From there follow the link "See your shared items page in a new window.". It should look something like this: http://www.google.com/reader/shared/username_or_132412341234';
	const LABEL			= 'Lifestream_BookmarkLabel';
	const NS			= 'http://www.google.com/schemas/reader/atom/';
	const HAS_EXCERPTS	= true;
	const AUTHOR		= 'David Cramer, Kyle McNally';
	
	function __toString()
	{
		return $this->get_option('user_id') ? $this->get_option('user_id') : $this->get_option('url');
	}
	
	function get_event_description(&$event, &$bit)
	{
		return $bit['comment'];
	}
	
	function get_options()
	{		
		return array(
			'url' => array($this->lifestream->__('Website URL:'), true, '', ''),
			'user_id' => array($this->lifestream->__('User ID:'), null, '', ''),
			'broadcast' => array($this->lifestream->__('Include Shared'), false, true, false),
			'starred' => array($this->lifestream->__('Include Starred'), false, true, false)
		);
	}
	
	function get_url()
	{
		if (!$this->get_option('user_id')) return $this->get_option('url');
		if ( $this->get_option('broadcast') && $this->get_option('starred') )
			return array(
				'http://www.google.com/reader/public/atom/user%2F'.$this->get_option('user_id').'%2Fstate%2Fcom.google%2Fbroadcast',
				'http://www.google.com/reader/public/atom/user%2F'.$this->get_option('user_id').'%2Fstate%2Fcom.google%2Fstarred'
			);
		elseif ( $this->get_option('starred') )
			return 'http://www.google.com/reader/public/atom/user%2F'.$this->get_option('user_id').'%2Fstate%2Fcom.google%2Fstarred';
		else
			return 'http://www.google.com/reader/public/atom/user%2F'.$this->get_option('user_id').'%2Fstate%2Fcom.google%2Fbroadcast';
		
	}
	
	function save_options()
	{
		if (preg_match('/\/reader\/shared\/([A-Za-z0-9_\.\-]+)\/?/i', $this->get_option('url'), $match))
		{
			$this->update_option('user_id', $match[1]);
		}
		else
		{
			throw new Lifestream_Error("Invalid feed URL.");
		}
		parent::save_options();
	}
	
	function yield($row, $url, $key)
	{
		//<gr:annotation><content type="html">Just testing some stuff in Lifestream</content>
		$data = parent::yield($row, $url, $key);
		$annotation =& $row->get_item_tags(self::NS, 'annotation');
		$data['comment'] = $this->lifestream->html_entity_decode($annotation[0]['child']['http://www.w3.org/2005/Atom']['content'][0]['data']);
		$data['date'] = $row->data['attribs']['http://www.google.com/schemas/reader/atom/']['crawl-timestamp-msec']/1000;
		return $data;
	}
}
$lifestream->register_feed('Lifestream_GoogleReaderFeed');
?>