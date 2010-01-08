<?php
class Lifestream_YelpFeed extends Lifestream_Feed
{
	const ID			= 'yelp';
	const NAME			= 'Yelp';
	const URL			= 'http://www.yelp.com/';
	const DESCRIPTION	= 'You can obtain your Yelp RSS feed url from your profile page. It should look something like this: http://www.yelp.com/syndicate/user/ctwwsl5_DSCzwPxtjzdl2A/rss.xml';
	const LABEL			= 'Lifestream_BusinessReviewLabel';
	
	function yield($row, $url, $key)
	{
		$data = parent::yield($row, $url, $key);

		$title = $row->get_title();
		
		$on_part = ' on Yelp.com';
		if (substr($title, strlen($title)-strlen($on_part)) == $on_part)
			$title = substr($title, 0, strlen($title)-strlen($on_part));
		
		$data['title'] = $title;
		return $data;
	}
}
$lifestream->register_feed('Lifestream_YelpFeed');
?>