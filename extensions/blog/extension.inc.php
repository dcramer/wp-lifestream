<?php
class Lifestream_BlogFeed extends Lifestream_GenericFeed
{
	const ID			= 'blog';
	const NAME			= 'Blog';
	const LABEL			= 'Lifestream_BlogLabel';
	const DESCRIPTION	= '';
	const HAS_EXCERPTS	= true;
	
	function get_options()
	{		
		return array(
			'url' => array($this->lifestream->__('Feed URL:'), true, '', ''),
			'permalink_url' => array($this->lifestream->__('Website URL:'), false, '', ''),
		);
	}
	
	function _get_domain()
	{
		if (!empty($this->options['permalink_url'])) $url = $this->options['permalink_url'];
		else $url = $this->options['url'];
		preg_match('#^(http://)?([a-z0-9\-\.]*\.)?([a-z0-9\-]+\.[a-z0-9\-]+)/?#i', $url, $matches);
		return $matches[3];
	}
	
	function get_public_name()
	{
		if (!empty($this->options['feed_label']))
		{
			return $this->options['feed_label'];
		}
		return $this->_get_domain();
	}
	
	function get_public_url()
	{
		if ($this->options['permalink_url']) return $this->options['permalink_url'];
		
		return 'http://'.$this->_get_domain();
	}

	function yield($row, $url, $key)
	{
		$data = parent::yield($row, $url, $key);
		$author =& $row->get_item_tags(SIMPLEPIE_NAMESPACE_DC_11, 'creator');
		$data['author'] = $this->lifestream->html_entity_decode($author[0]['data']);
		return $data;
	}
}
$lifestream->register_feed('Lifestream_BlogFeed');
?>