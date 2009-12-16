<?php
class Lifestream_RaptrFeed extends Lifestream_Feed
{
	const ID	= 'raptr';
	const NAME	= 'Raptr';
	const URL	= 'http://www.ratpr.com/';

	private $achievement_regexp = '#unlocked the (.*) achievement in <a[^>]+href="([^"]+)"[^>]*>([^<]+)</a>#i';
	private $played_regexp = '#(?:played some|managed to fit in a quick game of|played a game of|acquainted himself with the main menu of|just came up for air from a crazy session of|spent a chunk of time playing)\s<a[^>]+href="([^"]+)"[^>]*>([^<]+)</a>#i';
	private $played_alt_regexp = '#(?:Another day, another game of|Nothing like a short game of|That was quite a marathon of)\s<a[^>]+href="([^"]+)"[^>]*>([^<]+)</a> (?:that|to calm|for) (.*) (?:played.|\'s frayed nerves.|.)#i';
	private $status_regexp = '#changed (?:his|her|their) status message to: <div><div><blockquote><span> (.*) </span></blockquote></div></div>#i';

	function __toString()
	{
		return $this->get_option('username');
	}

	function get_options()
	{
		return array(
			'username' => array($this->lifestream->__('Username:'), true, '', ''),
		);
	}
	
	function get_public_url()
	{
		return 'http://raptr.com/'.urlencode($this->get_option('username'));
	}

	function get_url()
	{
		return 'http://raptr.com/'.urlencode($this->get_option('username')).'/rss';
	}

	function render_item($row, $item)
	{
		return $this->parse_urls(htmlspecialchars($item['title'])) . ' ['.$this->lifestream->get_anchor_html(htmlspecialchars($this->get_option('username')), $item['link']).']';
	}
	
	function yield_many($row, $url, $key)
	{
		$events = array();
		
		$string = '<a href="'.$this->get_public_url().'">'.$this->get_option('username').'</a> ';
		$description = $this->lifestream->html_entity_decode($row->get_description());
		if (lifestream_str_startswith(strtolower($description), strtolower($string)))
		{
			$description = substr($description, strlen($string));
		}
		if (lifestream_str_startswith(strtolower($description), 'unlocked'))
		{
			preg_match_all($this->achievement_regexp, str_replace('</li>', "</li>\n", $description), $matches, PREG_SET_ORDER);
			foreach ($matches as $match)
			{
				$data = parent::yield($row, $url, $key);
				$data['title'] = $match[1].' in '.$match[3];
				$data['link'] = $match[2];
				$data['key'] = 'achievement';
				unset($data['description']);
				$events[] = $data;
			}
		}
		elseif (preg_match($this->status_regexp, $description, $match))
		{
			$data = parent::yield($row, $url, $key);
			$data['title'] = $match[1];
			$data['link'] = $this->get_public_url();
			$data['key'] = 'status';
			unset($data['description']);
			$events[] = $data;
		}
		elseif (preg_match($this->played_regexp, $description, $match) || preg_match($this->played_alt_regexp, $description, $match))
		{
			$data = parent::yield($row, $url, $key);
			$data['title'] = $match[2];
			$data['link'] = $match[1];
			$data['key'] = 'played';
			unset($data['description']);
			$events[] = $data;
		}
		return $events;
	}
	
	function get_label_class($key)
	{
		if ($key == 'achievement') $cls = 'Lifestream_UnlockAchievementLabel';
		elseif ($key == 'status') $cls = 'Lifestream_MessageLabel';
		else $cls = 'Lifestream_PlayGameLabel';
		return $cls;
	}
}
$lifestream->register_feed('Lifestream_RaptrFeed');
?>