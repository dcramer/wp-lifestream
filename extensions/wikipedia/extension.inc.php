<?php
class Lifestream_WikipediaFeed extends Lifestream_PhotoFeed
{
	const ID	= 'wikipedia';
	const NAME	= 'Wikipedia';
	const URL	= 'http://www.wikipedia.org/';
	const LABEL	= 'Lifestream_ContributionLabel';

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
		return 'http://en.wikipedia.org/wiki/User:'.urlencode($this->get_option('username'));
	}

	function get_url()
	{
		return 'http://en.wikipedia.org/w/index.php?title=Special:Contributions&feed=rss&target='.urlencode($this->get_option('username'));
	}

	function yield($row, $url, $key)
	{
		$data = parent::yield($row, $url, $key);
		if (lifestream_str_startswith(strtolower($data['title']), 'talk:')) return;
		// we dont need huge descriptions stored in the db, its bloat
		unset($data['description']);
		return $data;
	}
}
$lifestream->register_feed('Lifestream_WikipediaFeed');
?>