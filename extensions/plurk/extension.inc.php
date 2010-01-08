<?php
class Lifestream_PlurkFeed extends Lifestream_Feed
{
	const ID	= 'plurk';
	const NAME	= 'Plurk';
	const URL	= 'http://www.plurk.com/';

	private $image_match_regexp = '/img src="(http\:\/\/images\.plurk\.com\/[^"]+)" alt="http\:\/\/images\.plurk\.com\/[^"]+"/i';

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
		return 'http://www.plurk.com/'.$this->get_option('username');
	}

	function get_url()
	{
		return 'http://www.plurk.com/'.$this->get_option('username').'.xml';
	}

	function get_label_class($key)
	{
		if ($key == 'photo') $cls = 'Lifestream_PhotoLabel';
		else $cls = 'Lifestream_MessageLabel';
		return $cls;
	}
	
	function yield($row, $url, $key)
	{
		$data = parent::yield($row, $url, $key);
		$string = $this->get_option('username'). ' ';
		$title = $data['title'];
		if (lifestream_str_startswith(strtolower($title), strtolower($string)))
		{
			$title = substr($title, strlen($string));
		}
		$bits = explode(' ', $title);
		if ($bits[0] == 'shares')
		{
			if (preg_match($this->image_match_regexp, $data['description'], $match))
			{
				$data['thumbnail'] = $match[1];
				$data['key'] = 'photo';
			}
		}
		else
		{
			$data['key'] = 'message';
		}
		$data['title'] = implode(' ', array_slice($bits, 1));
		return $data;
	}
	
	function render_item($row, $item)
	{
		if ($row->key == 'message')
		{
			return $this->parse_urls(htmlspecialchars($item['title'])) . ' ['.$this->lifestream->get_anchor_html(htmlspecialchars($this->get_option('username')), $item['link']).']';
		}
		else
		{
			return parent::render_item($row, $item);
		}
	}
	
}
$lifestream->register_feed('Lifestream_PlurkFeed');
?>