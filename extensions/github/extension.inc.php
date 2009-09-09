<?php
class Lifestream_GitHubFeed extends Lifestream_Feed
{
	const ID			= 'github';
	const NAME			= 'GitHub';
	const URL			= 'http://www.github.com/';
	const DESCRIPTION	= 'You can obtain your GitHub feed URL from the <a href="https://github.com/dashboard/yours">Your Dashboard</a> page. You will find the feed link in orange feed icon next to "News Feed".';
	const LABEL			= 'Lifestream_CommitLabel';

	function parse_message($text)
	{
		preg_match('/blockquote title=\"(.+)\"/', $text, $match);
		return $match[1];
	}

	function yield($row, $url, $key)
	{
		if (strpos($row->get_id(), "PushEvent") === false) {
			return null;
		} else {
			$data = parent::yield($row, $url, $key);
			$description = $this->lifestream->html_entity_decode($row->get_description());
			$message = $this->parse_message($description);
			$data['title'] = $message;
			return $data;
		}
	}
}
$lifestream->register_feed('Lifestream_GitHubFeed');
?>