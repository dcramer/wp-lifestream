<?php
class Lifestream_FlickrFeed extends Lifestream_PhotoFeed
{
	const ID			= 'flickr';
	const NAME			= 'Flickr';
	const URL			= 'http://www.flickr.com/';
	const DESCRIPTION	= 'You can find your User ID by using <a href="http://idgettr.com/" target="_blank">idGettr</a>.';
	 
	function get_options()
	{		
		return array(
			'user_id' => array($this->lifestream->__('User ID:'), true, '', ''),
		);
	}
	
	function get_public_url()
	{
		return 'http://www.flickr.com/photos/'.$this->get_option('user_id').'/';
	}

	function get_url()
	{
		return 'http://api.flickr.com/services/feeds/photos_public.gne?id='.$this->get_option('user_id');
	}

	function yield($row, $url, $key)
	{
		$data = parent::yield($row, $url, $key);
		$data['thumbnail'] = str_replace('_m', '_t', $data['image']); 		
		$data['image'] = str_replace('_m', '', $data['image']);
		return $data;
	}
}
$lifestream->register_feed('Lifestream_FlickrFeed');
?>