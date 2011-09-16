<?php
class Lifestream_PictMobiFeed extends Lifestream_PhotoFeed
{
	const ID	= 'pictmobi';
	const NAME	= 'Pict.Mobi';
	const URL	= 'http://pict.mobi/';

	function get_options()
	{
		return array(
			'username' => array($this->lifestream->__('Username:'), true, '', ''),
		);
	}

	function get_public_url()
	{
		return 'http://pict.mobi/images/'.$this->get_option('username');
	}

	function get_url()
	{
		return 'http://pict.mobi/feed/'.$this->get_option('username');
	}

	function get_thumbnail_url($row, $item)
	{
		preg_match('#\/([^\/]+)$#i', $item['link'], $matches);
		return 'http://pict.mobi/show/thumb/'.$matches[1];
	}
}
$lifestream->register_feed('Lifestream_PictMobiFeed');
?>

