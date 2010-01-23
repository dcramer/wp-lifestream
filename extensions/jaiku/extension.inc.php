<?php
class Lifestream_JaikuFeed extends Lifestream_Feed
{
	const ID          = 'jaiku';
	const NAME        = 'Jaiku';
	const URL         = 'http://www.jaiku.com/';
	const NS_JAIKU	  = 'http://jaiku.com/ns';
	const LABEL       = 'Lifestream_MessageLabel';
	const DESCRIPTION = 'Hiding or showing comments only affects new comments and not the ones already in your lifestream.';
        const AUTHOR      = 'Unknown, Jonas Nockert';
	const CAN_GROUP   = false;

	function get_options()
	{		
		return array(
			'username' => array($this->lifestream->__('Username:'), true, '', ''),
			'hide_comments' => array($this->lifestream->__('Hide Comments'), false, true, false),
		);
	}

	function get_url()
	{
		return 'http://'.$this->get_option('username').'.jaiku.com/feed/rss';
	}

	function get_user_url($user)
	{
		return 'http://'.$user.'.jaiku.com';
	}

	function get_user_link($user)
	{
		return $this->lifestream->get_anchor_html('@'.htmlspecialchars($user[1]), $this->get_user_url($user[1]), array('class'=>'user'));
	}

	function parse_users($text)
	{
		return preg_replace_callback('/(?<=^|\s)@([a-zA-Z0-9_]+)\b/i', array($this, 'get_user_link'), $text);
	}

	function render_item($row, $item)
	{
		// Posts do not have URL fragment identifiers
		if (strpos($item['guid'], '#') === false)
		{
			// Render a post using only the feed item title
			return $this->parse_users($this->parse_urls(htmlspecialchars($item['title']))).' ['.$this->lifestream->get_anchor_html(htmlspecialchars($this->get_option('username')), $item['link']).']';
		}

		/* Render a comment using the feed item description. We're rendering the comment
		   html directly here but given the below it should be safe enough:
		   1) Jaiku is trying hard to avoid rendering anything malicious anywhere.
		   2) It can be assumed that the source of the html will be text the user wrote
		      herself. */

                // Remove avatar image
		$desc_without_image = preg_replace('/<a.+?$\s+/Amu', '', $item['description']);

		// Remove comment author as it's always the stream owner.
		$desc_without_author = preg_replace('!^(<p.*?>\s+)(Comment).+(on)$!um', '\\1<i>\\2 \\3', $desc_without_image);

		// Remove relative time and location
		$desc_without_time = preg_replace('!\s+[^\r\n]+\s+[^\r\n]+\s+</p>$!u', '</i>', $desc_without_author);

		/* Line breaks instead of paragraphs. Nested paragraphs and
		   multiple empty paragraphs in a row should not give additional
		   line breaks */
		$desc_without_p = preg_replace('/<p.*?>/u', '', $desc_without_time);
		$desc_without_p = preg_replace('!(\s*</p>\s*){1,}!u', '<br/><br/>', $desc_without_p);

		return $desc_without_p . ' ['.$this->lifestream->get_anchor_html(htmlspecialchars($this->get_option('username')), $item['link']).']';
	}

	function yield($row, $url, $key)
	{
		$data = parent::yield($row, $url, $key);
		// Comments have URL fragment identifiers while posts do not
		if ($this->get_option('hide_comments') && strpos($data['guid'], '#') !== false)
		{
			return false;
		}
		$data['title'] = $this->lifestream->html_entity_decode($row->get_title());
		$data['description'] = $row->get_description();
		return $data;
	}
}
$lifestream->register_feed('Lifestream_JaikuFeed');
?>
