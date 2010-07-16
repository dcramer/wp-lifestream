<?php
class Lifestream_TwitterSearchFeed extends Lifestream_Feed
{
	const ID	= 'twittersearch';
	const NAME	= 'Twitter Search';
	const URL	= 'http://search.twitter.com/';
	const LABEL	= 'Lifestream_MessageLabel';
    const DESCRIPTION = 'Search for a specific hashtag in Twitter';
    const AUTHOR = 'Julen Ruiz Aizpuru';

	function __toString()
	{
		return '#'.$this->options['hashtag'];
	}

	function get_options()
	{		
		return array(
			'hashtag' => array($this->lifestream->__('Hashtag:'), true, '', ''),
			'hide_hashtag' => array($this->lifestream->__('Hide hashtag in output.'), false, true, false),
		);
	}

	function get_url()
	{
        return 'http://search.twitter.com/search.atom?q=%23'.urlencode($this->options['hashtag']);
	}

	function get_user_url($user)
	{
		return 'http://twitter.com/'.urlencode($user);
	}

	function get_user_link($user)
	{
		return $this->lifestream->get_anchor_html('@'.htmlspecialchars($user), $this->get_user_url($user), array('class'=>'user'));
    }

	function _get_user_link($match)
	{
		return $match[1].$this->get_user_link($match[2]);
	}
	
	function _get_search_term_link($match)
	{
		return $match[1].$this->lifestream->get_anchor_html(htmlspecialchars($match[2]), 'https://search.twitter.com/search?q='.urlencode($match[2]), array('class'=>'searchterm'));
	}

	function parse_users($text)
	{
		return preg_replace_callback('/([^\w]*)@([a-z0-9_\-\/]+)\b/i', array($this, '_get_user_link'), $text);
	}

	function parse_search_term($text)
	{
		return preg_replace_callback('/([^\w]*)(#[a-z0-9_\-\/]+)\b/i', array($this, '_get_search_term_link'), $text);
	}

	function clear_search_term($text)
	{
		return preg_replace('/([^\w]*)(#'.$this->options['hashtag'].')\b/i', '$1', $text);
	}

    function clear_name($text)
    {
        $parts = explode(' ', $text);
        return $parts[0];
    }

	function render_item($row, $item)
	{
        $str = $this->lifestream->get_anchor_html('@'.$item['author'], $item['link']).'<br />';
        if ($this->options['hide_hashtag'])
        {
            return $str.$this->parse_search_term($this->clear_search_term($this->parse_users($this->parse_urls(htmlspecialchars($item['title'])))));
        }
        else
        {
            return $str.$this->parse_search_term($this->parse_users($this->parse_urls(htmlspecialchars($item['title']))));
        }
	}

	function yield($row, $url, $key)
	{
		$data = parent::yield($row, $url, $key);
        $author = $row->get_author();
        $data['author'] = $this->clear_name($author->get_name());
		return $data;
	}

}
$lifestream->register_feed('Lifestream_TwitterSearchFeed');
?>
