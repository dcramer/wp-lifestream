<?php
class Lifestream_RaptrFeed extends Lifestream_Feed
{
	const ID	= 'raptr';
	const NAME	= 'Raptr';
	const URL	= 'http://www.ratpr.com/';

	private $achievement_regexp = '#unlocked the (.*) achievement in <a[^>]+href="([^"]+)"[^>]*>([^<]+)</a>#i';
	private $played_regexp = '#(?:played some|managed to fit in a quick game of|played a game of|acquainted himself with the main menu of)\s<a[^>]+href="([^"]+)"[^>]*>([^<]+)</a>#i';

	function __toString()
	{
		return $this->options['username'];
	}

	function get_options()
	{
		return array(
			'username' => array($this->lifestream->__('Username:'), true, '', ''),
		);
	}
	
	function get_public_url()
	{
		return 'http://raptr.com/'.urlencode($this->options['username']);
	}

	function get_url()
	{
		return 'http://raptr.com/'.urlencode($this->options['username']).'/rss';
	}
	
	function yield_many($row, $url, $key)
	{
		$events = array();
		
		$string = '<a href="'.$this->get_public_url().'">'.$this->options['username'].'</a> ';
		$description = $this->lifestream->html_entity_decode($row->get_description());
		if (str_startswith(strtolower($description), strtolower($string)))
		{
			$description = substr($description, strlen($string));
		}
		if (str_startswith(strtolower($description), 'unlocked'))
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
		elseif (preg_match($this->played_regexp, $description, $match))
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
		else $cls = 'Lifestream_PlayGameLabel';
		return $cls;
	}
}
$lifestream->register_feed('Lifestream_RaptrFeed');
?>