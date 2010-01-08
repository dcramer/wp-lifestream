<?php
class Lifestream_WordPressCodexFeed extends Lifestream_PhotoFeed
{
	const ID	= 'wpcodex';
	const NAME	= 'WordPress Codex';
	const URL	= 'http://codex.wordpress.org/';
	const LABEL	= 'Lifestream_ContributionLabel';
	
	function get_options()
	{
		return array(
			'username' => array($this->lifestream->__('Username:'), true, '', ''),
		);
	}

	function get_public_url()
	{
		return 'http://codex.wordpress.org/index.php?title=User:'.urlencode($this->get_option('username'));
	}

	function get_url()
	{
		return 'http://codex.wordpress.org/index.php?title=Special:Contributions&feed=rss&target='.urlencode($this->get_option('username'));
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
$lifestream->register_feed('Lifestream_WordPressCodexFeed');
?>