<?php
class Lifestream_PhotoBucketFeed extends Lifestream_PhotoFeed
{
	const ID	= 'photobucket';
	const NAME	= 'Photobucket';
	const URL	= 'http://www.photobucket.com/';
}
$lifestream->register_feed('Lifestream_PhotoBucketFeed');
?>