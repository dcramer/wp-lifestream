<?php
// class Lifestream_HuluFeed extends Lifestream_PhotoFeed
// {
//	 const ID			= 'hulu';
//	 const NAME		  = 'Hulu';
//	 const URL		   = 'http://www.hulu.com/';
//	 const LABEL_SINGLE  = 'Watched a video on %s.';
//	 const LABEL_PLURAL  = 'Watched %d videos on %s.';
//	 const LABEL_SINGLE_USER = '%s watched a video on %s.';
//	 const LABEL_PLURAL_USER = '%s watched %d videos on %s.';
//	 const DESCRIPTION   = 'You may first need to change your privacy settings on Hulu for the feed to be viewable.';
//	 
//	 private $link_match_regexp = '/href="(http\:\/\/www\.hulu\.com\/watch\/[^"]+)"/i';
//	 private $image_match_regexp = '/src="(http\:\/\/thumbnails\.hulu\.com\/[^"]+\.jpg)"/i';
//	 
//	 function get_options()
//	 {		
//		 return array(
//			 'username' => array($this->lifestream->__('Username:'), true, '', ''),
//		 );
//	 }
// 
//	 function get_url()
//	 {
//		 // Support old-style url for feed
//		 if ($this->get_option('url')) return $this->get_option('url');
//		 return 'http://www.hulu.com/feed/activity/'.$this->get_option('username');
//	 }
// 
//	 
//	 function yield($row, $url, $key)
//	 {
//		 $data = parent::yield($row, $url, $key);
//		 if (!$data['thumbnail'])
//		 {
//			 preg_match($this->link_match_regexp, $row->get_description(), $link_match);
//			 preg_match($this->image_match_regexp, $row->get_description(), $image_match);
//			 $data['thumbnail'] = $image_match[1];
//			 $data['link'] = $link_match[1];
//		 }
//		 return $data;
//	 }
// }
class Lifestream_HuluFeed extends Lifestream_Feed
{
	const ID			= 'hulu';
	const NAME			= 'Hulu';
	const URL			= 'http://www.hulu.com/';
	const DESCRIPTION	= 'You can obtain your history feed by visiting <a href="http://www.hulu.com/users/history">here</a> and clicking the RSS icon at the top of the page. You may first need to change your privacy settings for the feed to be viewable.';
	const LABEL			= 'Lifestream_WatchVideoLabel';
}
$lifestream->register_feed('Lifestream_HuluFeed');
?>