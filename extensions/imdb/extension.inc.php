<?php
class Lifestream_IMDBFeed extends Lifestream_Feed
{
	const ID			= 'imdb';
	const NAME			= 'IMDB (My Movies)';
	const URL			= 'http://www.imdb.com/';
	const LABEL			= 'Lifestream_LikeMovieLabel';
	const DESCRIPTION   = 'You can obtain your IMDB feed\'s URL by visiting your <a href="http://www.imdb.com/mymovies/list">My Movies</a> page, and copying the url for the RSS feed from your address bar. You will need to check the "Public" box on the Pending page.';
}
$lifestream->register_feed('Lifestream_IMDBFeed');
?>