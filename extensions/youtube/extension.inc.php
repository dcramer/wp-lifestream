<?php
class Lifestream_YouTubeFeed extends Lifestream_PhotoFeed
{
	const ID			= 'youtube';
	const NAME			= 'YouTube';
	const URL			= 'http://www.youtube.com/';
	const DESCRIPTION	= '';
	
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
		return 'http://www.youtube.com/user/'.$this->get_option('username');
	}
	
	function get_label_class($key)
	{
		if ($key == 'favorite') $cls = 'Lifestream_LikeVideoLabel';
		else $cls = 'Lifestream_VideoLabel';
		return $cls;
	}

	function get_posted_url() {
		return 'http://gdata.youtube.com/feeds/api/users/'.$this->get_option('username')).'/uploads?v=2';
		}

	function get_favorited_url() {
		return 'http://gdata.youtube.com/feeds/api/users/'.$this->get_option('username')).'/favorites?v=2';
		}

	function get_url() {
		$urls = array();
		$urls[] = array($this->get_posted_url(), 'video');
		if ($this->get_option('show_favorites')) $urls[] = array($this->get_favorited_url(), 'favorite');
		return $urls;
	}
	
	function yield($row, $url, $key)
	{
		$data = parent::yield($row, $url, $key);
		$data['image'] = str_replace('_m', '', $data['image']);
		return $data;
	}
	
	function render_item($row, $item)
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
}
$lifestream->register_feed('Lifestream_YouTubeFeed');
?>