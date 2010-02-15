<?php
class Lifestream_PinboardFeed extends Lifestream_Feed
{
	const ID	= 'pinboard';
	const NAME	= 'Pinboard.in';
	const URL	= 'http://www.pinboard.in/';
	const LABEL = 'Lifestream_BookmarkLabel';
	const HAS_EXCERPTS	= true;

	function __toString()
	{
		return $this->get_option('username');
	}
	
	function get_options()
	{
		return array(
			'username' => array($this->lifestream->__('Username:'), true, '', ''),
			'filter_tag' => array($this->lifestream->__('Limit items to tag:'), false, '', ''),
		);
	}

	function get_url()
	{
		$url = 'http://feeds.pinboard.in/rss/u:'.urlencode($this->get_option('username'));
		if ($this->get_option('filter_tag')) $url .= '/t:'.urlencode($this->get_option('filter_tag'));
		return $url;
	}
	
	function get_public_url()
	{
		return 'http://pinboard.in/u:'.urlencode($this->get_option('username'));
	}

	function yield($row, $url, $key)
	{
		$TAXONOMY_NS = 'http://purl.org/rss/1.0/modules/taxonomy/';
		$RDF_NS = 'http://www.w3.org/1999/02/22-rdf-syntax-ns#';
		// <taxo:topics>
		//       <rdf:Bag>
		//       	<rdf:li rdf:resource="http://pinboard.in/u:zeeg/t:mysql"/>
		// 	<rdf:li rdf:resource="http://pinboard.in/u:zeeg/t:optimization"/>
		// 
		// 	<rdf:li rdf:resource="http://pinboard.in/u:zeeg/t:performance"/>
		//         </rdf:Bag>
		//       </taxo:topics>
		
		
		$data = parent::yield($row, $url, $key);
		$topics =& $row->get_item_tags($TAXONOMY_NS, 'topics');
		$topics = $topics[0]['child'][$RDF_NS]['Bag'][0]['child'][$RDF_NS]['li'];
		$tags = array();
		foreach ($topics as $t)
		{
			$t = $t['attribs'][$RDF_NS]['resource'];
			if (!empty($t)) $tags[] = $row->sanitize(substr(strstr($t, '/t:'), 3), SIMPLEPIE_CONSTRUCT_TEXT);
		}
		$data['tags'] = $tags;
		return $data;
	}
}
$lifestream->register_feed('Lifestream_PinboardFeed');
?>