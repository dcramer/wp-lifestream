<?php
class LifeStream_WordPressCodexFeed extends LifeStream_WikipediaFeed
{
	const ID	= 'wpcodex';
	const NAME	= 'WordPress Codex';
	const URL	= 'http://codex.wordpress.org/';
	const LABEL	= 'LifeStream_ContributionLabel';

	function get_public_url()
	{
		return 'http://codex.wordpress.org/index.php?title=User:'.urlencode($this->options['username']);
	}

	function get_url()
	{
		return 'http://codex.wordpress.org/index.php?title=Special:Contributions&feed=rss&target='.urlencode($this->options['username']);
	}
}
$lifestream->register_feed('LifeStream_WordPressCodexFeed');
?>