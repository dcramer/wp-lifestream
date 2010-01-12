<?php
class Lifestream_UstreamFeed extends Lifestream_PhotoFeed
{
	const ID			= 'ustream';
	const NAME			= 'Ustream';
	const URL			= 'http://www.ustream.tv/';
	const DESCRIPTION	= '';
	const API_KEY		= 'AD221D3F494A24A27363B0A8C521065F';
	
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
		return 'http://www.ustream.tv/'.$this->get_option('username');
	}
	
	function get_label_class($key)
	{
		$cls = 'Lifestream_StreamLabel';
		return $cls;
	}

	function get_url() {
		return 'http://api.ustream.tv/xml/channel/recent/search/username:eq:'.$this->get_option('username').'?key='.$this->get_constant('API_KEY');
	}
	
	function yield($item, $url)
	{
		$ts = strtotime($item->lastStreamedAt);
		return array(
			'guid'		=>  $item->id.':'.$ts,
			'date'		=>  $ts,
			'link'		=>  $this->lifestream->html_entity_decode($item->url),
			'title'		=>  $this->lifestream->html_entity_decode($item->title),
			'description'	=>  $this->lifestream->html_entity_decode($item->description),
			'image'		=>  $this->lifestream->html_entity_decode($item->imageUrl->medium),
			'thumbnail' =>  $this->lifestream->html_entity_decode($item->imageUrl->small),
		);
	}
	
	function fetch()
	{
		$url = $this->get_url();
		$response = $this->lifestream->file_get_contents($url);
		
		if ($response)
		{
			$xml = new SimpleXMLElement($response);
			
			$items = array();
			foreach ($xml->results->array as $item)
			{
				$items[] = $this->yield($item, $url);
			}
			return $items;
		}
	}
}
$lifestream->register_feed('Lifestream_UstreamFeed');
?>