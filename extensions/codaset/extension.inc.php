<?php
class Lifestream_CodasetFeed extends Lifestream_Feed
{
	const ID			= 'codaset';
	const NAME			= 'Codaset';
	const URL			= 'http://www.codaset.com/';
	const DESCRIPTION	= 'You can obtain your Codaset feed URL from the <a href="hhttp://codaset.com/yours">My Account</a> page. You will find the feed link in orange feed icon next to "My Public and Private Activity... ".';
	const LABEL			= 'Lifestream_CommitLabel';

	function yield($row, $url, $key)
	{
		
		if ($row->data['child']['http://www.w3.org/2005/Atom']['title']['data']) {
			return null;
		} else {
			$data = parent::yield($row, $url, $key);
			// echo '<pre>'; print_r($row->data['child']['http://www.w3.org/2005/Atom']); die;
			$data['title'] = $row->data['child']['http://www.w3.org/2005/Atom']['title'][0]['data'];
			$data['description'] = $row->data['child']['http://www.w3.org/2005/Atom']['id'][0]['data'];
			// echo '<pre>'; print_r($data); die;
			return $data;
		}
	}
}
$lifestream->register_feed('Lifestream_CodasetFeed');
?>