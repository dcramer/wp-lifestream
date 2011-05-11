<?php
if (!defined('SIMPLEPIE_NAMESPACE_YOUTUBE'))
{
	define('SIMPLEPIE_NAMESPACE_YOUTUBE', 'http://search.yahoo.com/mrss/');
}

class Lifestream_YouTubeFeed extends Lifestream_PhotoFeed
{
	const ID			= 'youtube';
	const NAME			= 'YouTube';
	const URL			= 'http://www.youtube.com/';
	const DESCRIPTION	= '';
	const TEMPLATE		= 'video';
	
	function __toString()
	{
		return $this->get_option('username');
	}
	
	function get_options()
	{
		return array(
			'username' => array($this->lifestream->__('Username:'), true, '', ''),
			'show_favorites' => array($this->lifestream->__('Include favorites in this feed.'), false, true, false),
		);
	}
	
	function get_public_url()
	{
		return 'http://gdata.youtube.com/feeds/base/users/'.$this->get_option('username').'/favorites';
	}
	
	function get_label_class($key)
	{
		if ($key == 'favorite') $cls = 'Lifestream_LikeVideoLabel';
		else $cls = 'Lifestream_VideoLabel';
		return $cls;
	}

	function get_posted_url()
	{
		return 'http://gdata.youtube.com/feeds/base/users/'.$this->get_option('username').'/uploads';
	}

	function get_favorited_url()
	{
		return 'http://gdata.youtube.com/feeds/api/users/'.$this->get_option('username').'/favorites?v=2';
	}

	function get_url()
	{
		$urls = array();
		$urls[] = array($this->get_posted_url(), 'video');
		if ($this->get_option('show_favorites')) $urls[] = array($this->get_favorited_url(), 'favorite');
		return $urls;
	}
	
	function yield($row, $url, $key)
	{
		$data = parent::yield($row, $url, $key);
		
		$data['image'] = str_replace('_m', '', $data['image']);
		
		/*$enclosure = $row->get_enclosure();
		$data['player_url'] = $enclosure->get_link();*/
		return $data;
	}
	
	function render_item($row, $item)
	{
		if (count($row->data) > 1 || !$item['player_url'] || !ls_is_event())
		{
			$attrs = array(
				'class' => 'photo',
				'title' => htmlspecialchars($item['title'])
			);
			if ($this->lifestream->get_option('use_ibox') == '1')
			{
				$attrs['rel'] = 'ibox';
			}
			return $this->lifestream->get_anchor_html('<img src="'.$item['thumbnail'].'" alt="" width="50"/>', $item['link'], $attrs);
		}
		else
		{
			return '<object width="425" height="344">
			<param name="movie" value="'.$item['player_url'].'"</param>
			<embed src="'.$item['player_url'].'"
			  type="application/x-shockwave-flash"
			  allowfullscreen="true"
			  width="425" height="344">
			</embed>
			</object>';
		}
	}
}
$lifestream->register_feed('Lifestream_YouTubeFeed');
?>