<?php
class Lifestream_MyEpisodesFeed extends Lifestream_Feed
{
	const ID			= 'myepisodes';
	const NAME			= 'MyEpisodes';
	const URL			= 'http://www.myepisodes.com/';
	const DESCRIPTION	= 'You can obtain your MyList feed\'s URL by visiting your <a href="http://www.myepisodes.com/rsshelp.php#mylist">RSS Feeds</a> page, and copying the <strong>[Link]</strong> under <strong>MyList Feed</strong>.';
	const LABEL			= 'Lifestream_WatchEpisodeLabel';
	
	function __toString()
	{
		return $this->get_option('username');
	}

	function get_options()
	{
		return array(
			'username' => array($this->lifestream->__('Username:'), true, '', ''),
			'url' => array($this->lifestream->__('Feed URL:'), true, '', ''),
		);
	}
}
$lifestream->register_feed('Lifestream_MyEpisodesFeed');
?>