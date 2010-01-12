<?php
class Lifestream_XboxLiveFeed extends Lifestream_Feed
{
	const ID	= 'xboxlive';
	const NAME	= 'Xbox Live';
	const URL	= 'http://www.xbox.com/';
	const LABEL	= 'Lifestream_PlayGameLabel';
	
	function __toString()
	{
		return $this->get_option('username');
	}

	function get_options()
	{
		return array(
			'username' => array($this->lifestream->__('Xbox Live ID:'), true, '', ''),
		);
	}
	
	function get_public_url()
	{
		return 'http://live.xbox.com/member/'.urlencode($this->get_option('username'));
	}
	
	function get_url()
	{
		return 'http://duncanmackenzie.net/services/GetXboxInfo.aspx?GamerTag='.urlencode($this->get_option('username'));
	}
	
	function get_event_display(&$event, &$bit)
	{
		return $bit['title'] ? $bit['title'] : $bit['name'];
	}
	
	function yield($row)
	{
		return array(
			'guid'	  =>  $this->lifestream->html_entity_decode($row->DetailsURL),
			'date'	  =>  strtotime($row->LastPlayed),
			'link'	  =>  $this->lifestream->html_entity_decode($row->DetailsURL),
			'title'	  =>  $this->lifestream->html_entity_decode($row->Game->Name),
		);
	}
	
	function fetch()
	{
		$url = $this->get_url();
		$response = $this->lifestream->file_get_contents($url);

		if ($response)
		{
			$xml = new SimpleXMLElement($response);
			
			if ($xml[0] == 'Service Unavailable') return;
			
			$items = array();
			foreach ($xml->RecentGames->XboxUserGameInfo as $row)
			{
				$items[] = $this->yield($row);
			}
			return $items;
		}
	}
	
	function render_item($row, $item)
	{
		return sprintf('%s', htmlspecialchars($item['link']), htmlspecialchars($item['name']));
	}
}
$lifestream->register_feed('Lifestream_XboxLiveFeed');
?>