<?php
class Lifestream_GoodReadsFeed extends Lifestream_PhotoFeed
{
	const ID	= 'goodreads';
	const NAME	= 'GoodReads';
	const URL	= 'http://www.goodreads.com/';
	const LABEL	= 'Lifestream_BookLabel';

	function __toString()
	{
		return $this->get_option('user_id');
	}

	function get_options()
	{		
		return array(
			'url' => array($this->lifestream->__('Profile URL:'), true, '', ''),
			'user_id' => array($this->lifestream->__('User ID:'), null, '', ''),
		);
	}
	
	function save_options()
	{
		# We need to get their user id from the URL
		if (preg_match('/\/([0-9]+)(?:-.+)?$/i', $this->get_option('url'), $match))
		{
			$this->update_option('user_id', $match[1]);
		}
		else
		{
			throw new Lifestream_Error("Invalid profile URL.");
		}
		
		parent::save_options();
	}

	function get_public_url()
	{
		return $this->get_option('url');
	}

	function get_url()
	{
		return 'http://www.goodreads.com/review/list_rss/'.$this->get_option('user_id');
	}
	
	function yield($item, $url)
	{
		return array(
			'guid'	  =>  $this->lifestream->html_entity_decode($item->guid),
			'date'	  =>  strtotime($item->pubDate),
			'link'	  =>  $this->lifestream->html_entity_decode($item->link),
			'title'	 =>  $this->lifestream->html_entity_decode($item->title),
			'author'	=>  $this->lifestream->html_entity_decode($item->author_name),
			'description'	=>  $this->lifestream->html_entity_decode($item->book_description),
			'image'	 =>  $this->lifestream->html_entity_decode($item->book_large_image_url),
			'thumbnail' =>  $this->lifestream->html_entity_decode($item->book_small_image_url),
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
			foreach ($xml->channel->item as $item)
			{
				$items[] = $this->yield($item, $url);
			}
			return $items;
		}
	}
}
$lifestream->register_feed('Lifestream_GoodReadsFeed');
?>