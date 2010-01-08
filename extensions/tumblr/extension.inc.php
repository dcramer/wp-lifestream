<?php
class Lifestream_TumblrFeed extends Lifestream_Feed
{
	const ID	= 'tumblr';
	const NAME	= 'Tumblr';
	const URL	= 'http://www.tumblr.com/';
	const HAS_EXCERPTS	= true;
	
	// http://media.tumblr.com/ck3ATKEVYd6ay62wLAzqtEkX_500.jpg
	private $image_match_regexp = '/src="(http:\/\/(?:[a-z0-9\.]+\.)?media\.tumblr\.com\/[a-zA-Z0-9_-]+\.jpg)"/i';
	
	function get_options()
	{		
		return array(
			'username' => array($this->lifestream->__('Username:'), true, '', ''),
		);
	}
	
	# TODO: initialization import
	# http://twitter.com/statuses/user_timeline/zeeg.xml
	function get_url()
	{
		return 'http://'.$this->get_option('username').'.tumblr.com/rss';
	}
	
	function get_user_url($user)
	{
		return 'http://'.$this->get_option('username').'.tumblr.com/';
	}
	
	function yield($row, $url, $key)
	{
		$data = parent::yield($row, $url, $key);
		preg_match($this->image_match_regexp, $row->get_description(), $match);
		if (strip_tags($data['title']) == strip_tags($data['description']))
		{
			$data['key'] = 'note';
		}
		if ($match)
		{
			$data['thumbnail'] = $match[1];
			$data['image'] = $match[1];
			$data['key'] = 'image';
		}
		return $data;
	}
	
	function parse_users($text)
	{
		return preg_replace_callback('/([^\w]*)@([a-z0-9_-]+)\b/i', array($this, '_get_user_link'), $text);
	}
	
	function _get_user_link($match)
	{
		return $match[1].$this->get_user_link($match[2]);
	}

	function render_item($event, $item)
	{
		if ($event->key == 'image')
		{
			return Lifestream_PhotoFeed::render_item($event, $item);
		}
		elseif ($event->key == 'note')
		{
			return Lifestream_TwitterFeed::parse_users($this->parse_urls(htmlspecialchars($item['title']))) . ' ['.$this->lifestream->get_anchor_html($this->get_option('username'). htmlspecialchars($item['link'])).']';
		}
		else
		{
			return parent::render_item($event, $item);
		}
	}
	
	function get_label_class($key)
	{
		if ($key == 'image') $cls = Lifestream_PhotoFeed::LABEL;
		elseif ($key == 'note') $cls = Lifestream_TwitterFeed::LABEL;
		else $cls = Lifestream_BlogFeed::LABEL;
		return $cls;
	}
}
$lifestream->register_feed('Lifestream_TumblrFeed');
?>