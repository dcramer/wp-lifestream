<?php
class Lifestream_DribbbleFeed extends Lifestream_Feed
{
	const ID			= 'dribbble';
	const NAME			= 'Dribbble';
	const URL			= 'http://www.dribbble.com/';
	const DESCRIPTION	= 'Pulls in your shots from Dribbble.com';
	const LABEL 		= 'Lifestream_DribbbleLabel';
	const MUST_GROUP 	= true;

	function get_options()
	{		
		return array(
			'user_id' => array($this->lifestream->__('User Name:'), true, '', ''),
		);
	}
	
	function get_public_url()
	{
		return 'http://dribbble.com/players/'.$this->get_option('user_id');
	}

	function get_url()
	{
			return 'http://dribbble.com/players/'.$this->get_option('user_id').'/shots.rss';
	}
	
	function render_item($row, $item)
	{	
		preg_match("/src=\"(http.*(jpg|jpeg|gif|png))/", $item['description'], $image_url);
		$image = $image_url[1];
		$image = preg_replace('/.(jpg|jpeg|gif|png)/', '_teaser.$1',$image); #comment this out if you want to use the big 400x300 image
		$output = '<a href="'.$item['link'].'"><img src="'.$image.'" alt="'.$item['title'].'"/></a>';
		return $output;
	}

}

$lifestream->register_feed('Lifestream_DribbbleFeed');

class Lifestream_DribbbleLabel extends Lifestream_Label
{
	const TEMPLATE = 'photo';
}