<?php
class Lifestream_FacebookFeed extends Lifestream_Feed
{
	const ID			= 'facebook';
	const NAME			= 'Facebook';
	const URL			= 'http://www.facebook.com/';
	const DESCRIPTION	= 'To obtain your Facebook feed URL visit the very hard to find <a href="http://www.facebook.com/notifications.php" target="_blank">Notifications</a> page. On the right hand side look in the sidebar for the <strong>Subscribe to Notifications</strong> item, and click the <strong>Your Notifications</strong> link.';
	const LABEL			= 'Lifestream_MessageLabel';
	const CAN_GROUP		= false;
	
	function render_item($row, $item)
	{
		return htmlspecialchars($item['title']);
	}
}

/**
 * Displays your latest Facebook status.
 * @param {Boolean} $links Parse user links.
 */
function lifestream_facebook_status($links=true)
{
	global $lifestream;

	$event = $lifestream->get_single_event('facebook');
	if (!$event) return;
	if ($links)
	{
		// to render it with links
		echo $event->feed->render_item($event, $event->data[0]);
	}
	else
	{
		// or render just the text
		echo $event->data[0]['title'];
	}
}

$lifestream->register_feed('Lifestream_FacebookFeed');
?>