<?php
class Lifestream_AmazonFeed extends Lifestream_PhotoFeed
{
	const ID	= 'amazon';
	const NAME	= 'Amazon';
	const URL	= 'http://www.amazon.com/';
	const LABEL	= 'Lifestream_WantItemLabel';

	private $image_match_regexp = '/src="(http\:\/\/ecx\.images-amazon\.com\/[^"]+\.jpg)"/i';
	
	function yield($row, $url, $key)
	{
		$data = parent::yield($row, $url, $key);
		preg_match($this->image_match_regexp, $row->get_description(), $match);
		$data['thumbnail'] = $match[1];
		return $data;
	}
}
$lifestream->register_feed('Lifestream_AmazonFeed');
?>