<?php
class Lifestream_BackTypeFeed extends Lifestream_Feed
{
	const ID		= 'backtype';
	const NAME		= 'BackType';
	const URL		= 'http://www.backtype.com/';
	const LABEL		= 'Lifestream_CommentLabel';
	# grouping doesnt support what we'd need for backtype
	const CAN_GROUP	= false;
	const HAS_EXCERPTS	= true;

	function get_options()
	{		
		return array(
			'username' => array($this->lifestream->__('Username:'), true, '', ''),
			'filter' => array($this->lifestream->__('Sites to filter out:'), false, '', '' , $this->lifestream->__('Sites as named by BackType, usually the title of the RSS Feed, separate with comma\'s.')),
		);
	}
	
	function get_user_link($user)
	{
		return $this->lifestream->get_anchor_html(htmlspecialchars($user), $this->get_user_url($user), array('class'=>'user'));
	}
	
	function get_user_url($user)
	{
		return 'http://www.backtype.com/'.urlencode($user);
	}
	
	function get_public_url()
	{
		return $this->get_user_url($this->get_option('username'));
	}

	function get_url()
	{
		return 'http://feeds.backtype.com/'.$this->get_option('username');
	}
	
	function render_item($row, $item)
	{
		$output = "Posted a comment on ".htmlspecialchars($item['title'])."<br/>";
		$output .= str_replace("</p>", "<br/><br/>", str_replace("<p>","",$item['description'])) . ' ['.$this->lifestream->get_anchor_html(htmlspecialchars($this->get_option('username')), $item['link']).']';
		return $output;
	}
	
	function yield($row, $url, $key)
	{
		$data = parent::yield($row, $url, $key);

		$filters = explode(",",$this->get_option('filter'));
		foreach ($filters as $filter) {
			if (strtolower($filter) == strtolower(strip_tags($row->get_title()))) {
				return false;
				exit;
			}
		}
		$description = strip_tags(str_replace('<p><a href="http://www.backtype.com/'.strtolower($this->get_option('username')).'">Read more comments by '.strtolower($this->get_option('username')).'</a></p>', '' , $this->lifestream->html_entity_decode($row->get_description())));
		
		$data['description'] = $description;
		return $data;
	}
}
$lifestream->register_feed('Lifestream_BackTypeFeed');
?>