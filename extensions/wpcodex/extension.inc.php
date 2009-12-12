<?php
class Lifestream_WordPressCodexFeed extends Lifestream_WikipediaFeed
{
	const ID	= 'wpcodex';
	const NAME	= 'WordPress Codex';
	const URL	= 'http://codex.wordpress.org/';
	const LABEL	= 'Lifestream_ContributionLabel';

	function get_public_url()
	{
		return 'http://codex.wordpress.org/index.php?title=User:'.urlencode($this->get_option('username'));
	}

	function get_url()
	{
		return 'http://codex.wordpress.org/index.php?title=Special:Contributions&feed=rss&target='.urlencode($this->get_option('username'));
	}
}
$lifestream->register_feed('Lifestream_WordPressCodexFeed');
?>