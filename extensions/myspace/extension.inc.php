<?php
class Lifestream_MySpaceFeed extends Lifestream_BlogFeed
{
	const ID			= 'myspace';
	const NAME			= 'MySpace';
	const URL			= 'http://www.myspace.com/';
	const DESCRIPTION	= 'To retrieve your MySpace blog URL, visit your profile and click "View all entries" under your blog. From there, you will see an "rss" link on the top right of the page.';
	
}
$lifestream->register_feed('Lifestream_MySpaceFeed');
?>