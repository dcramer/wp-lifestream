<?php
class Lifestream_BrightkiteFeed extends Lifestream_Feed
{
	const ID			= 'brightkite';
	const NAME			= 'Brightkite';
	const URL			= 'http://www.brightkite.com/';
	const DESCRIPTION	= '';
	const NS_BRIGHTKITE	= 'http://brightkite.com/placeFeed';
	
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
		return 'http://www.brightkite.com/people/'.$this->get_option('username');
	}

	function get_url()
	{
		return 'http://www.brightkite.com/people/'.$this->get_option('username').'/objects.rss';
	}
	
	function render_group_items($id, $output, $event)
	{
		if ($event->key == 'photo')
		{
			return Lifestream_PhotoFeed::render_group_items($id, $output, $event);
		}
		else
		{
			return parent::render_group_items($id, $output, $event);
		}
	}
	
	function render_item($event, $item)
	{
		if ($event->key == 'photo')
		{
			return Lifestream_PhotoFeed::render_item($event, $item);
		}
		elseif ($event->key == 'checkin') return $this->lifestream->get_anchor_html(htmlspecialchars($item['placename']), $item['placelink']);
		else
		{
			return $this->parse_urls(htmlspecialchars($item['text']));
		}
	}
	
	function get_label_class($key)
	{
		if ($key == 'photo') $cls = Lifestream_PhotoFeed::LABEL;
		elseif ($key == 'checkin') $cls = 'Lifestream_LocationLabel';
		else $cls = $this->get_constant('LABEL');
		return $cls;
	}
	
	function yield($row, $url, $key)
	{
		$data = parent::yield($row, $url, $key);
		$type = $row->get_item_tags(self::NS_BRIGHTKITE, 'eventType');
		$type = $type[0]['data'];

		$placelink = $row->get_item_tags(self::NS_BRIGHTKITE, 'placeLink');
		$data['placelink'] = $placelink[0]['data'];

		$placename = $row->get_item_tags(self::NS_BRIGHTKITE, 'placeName');
		$data['placename'] = $placename[0]['data'];

		$placeaddress = $row->get_item_tags(self::NS_BRIGHTKITE, 'placeAddress');
		$data['placeaddress'] = $placeaddress[0]['data'];

		if ($enclosure = $row->get_enclosure())
		{
			$data['thumbnail'] = $enclosure->get_thumbnail();
			$data['image'] = $enclosure->get_medium();
		}
		return $data;
	}
}
$lifestream->register_feed('Lifestream_BrightkiteFeed');
?>