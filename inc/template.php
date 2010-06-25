<?php
// Template tags and related functions
class LifestreamTemplate
{
	var $current_event = -1;
	var $event_count = 0;
	var $has_next_page = false;
	var $has_prev_page = false;
	var $show_metadata = true;
	var $events = array();
	var $posts = array();
	var $in_event = false;
	
	function __construct($lifestream)
	{
		$this->lifestream = $lifestream;
		$this->id = $lifestream->generate_unique_id();
		$this->limit = $lifestream->get_option('number_of_items');
	}
	
	function set_in_event($true_or_false=true)
	{
		$this->in_event = $true_or_false;
	}
	
	function set_event($event)
	{
		$this->events = array($event);
	}
	
	function is_single()
	{
		return is_single();
	}
	
	function is_event()
	{
		return $this->is_single() && $this->in_event;
	}

	function get_events()
	{
		global $posts, $wp_query;
		
		if (is_single())
		{
			$this->page = 1;
			$this->offset = 0;
			
			$options = $this->get_options();
			$options['post_ids'] = $posts[0]->ID;
			$events = call_user_func(array(&$this->lifestream, 'get_events'), $options);
		}
		else
		{
			$this->page = $this->lifestream->get_page_from_request();
			$this->offset = ($this->page-1) * $this->limit;
			$events = call_user_func(array(&$this->lifestream, 'get_events'), $this->get_options());
			$post_ids = array();
			foreach ($events as &$event)
			{
				$post_ids[] = $event->post_id;
			}

			$posts = get_posts(array(
				'include' => implode(',', $post_ids),
				'numberposts' => count($post_ids),
				'post_type' => 'lsevent',
			));
		}

		// foreach ($posts as &$post)
		// {
		// 	$this->posts[$post->ID] = $post;
		// }

		$this->events = $events;
		$this->rewind_events();
		
		$this->has_next_page = (count($this->events) > $this->limit);
		if ($this->has_next_page)
		{
			$this->events = array_slice($this->events, 0, $this->limit);
		}
		$this->has_prev_page = ($this->page > 1);

		$this->event_count = count($this->events);
	}
	
	function get_options()
	{
		return array(
			'offset'=>$this->offset,
			'limit'=>$this->limit+1,
			'id'=>$this->id,
			'is_single' => $this->is_single(),
		);
	}
		
	function rewind_events()
	{
		$this->current_event = -1;
		if ($this->event_count > 0)
		{
			$this->event = $this->events[0];
		}
	}
	
	function have_activity()
	{
		if ($this->current_event + 1 < $this->event_count)
		{
			$this->set_in_event(true);
			return true;
		}
		elseif ($this->current_event + 1 == $this->event_count && $this->event_count > 0)
		{
			// Do some cleaning up after the loop
			$this->rewind_events();
		}
		$this->set_in_event(false);
		$this->in_the_loop = false;
		return false;
	}
	
	function the_event()
	{
		global $event, $post;
		$this->in_the_loop = true;

		$event = $this->next_event();
		$post = get_post($event->post_id);
		// setup_postdata($post);
		//$post =& $this->posts[$event->post_id];
	}
	
	function next_event()
	{
		$this->current_event++;
		
		$this->event = $this->events[$this->current_event];

		return $this->event;
	}
	
	function do_credits()
	{
		if ($this->lifestream->get_option('show_credits') == '1')
		{
			echo '<p class="lifestream_credits"><small>'.$this->lifestream->credits().'</small></p>';
		}
	}
	
	function event_class($event=null)
	{
		if (!$event) $event = $this->event;
		
		echo 'lifestream_feedid_'.$event->feed->id.' lifestream_feed_'.$event->feed->get_constant('ID');
	}
	
	function event_link($event=null)
	{
		if (!$event) $event = $this->event;
		
		echo htmlspecialchars($event->get_url());
	}
	
	function event_permalink($event=null)
	{
		if (!$event) $event = $this->event;
		
		echo htmlspecialchars(the_permalink());
	}
	
	function event_icon($event=null)
	{
		if (!$event) $event = $this->event;
		
		echo $event->feed->get_icon_url();
	}
	
	function event_label($event=null)
	{
		if (!$event) $event = $this->event;
		
		echo $event->get_label($this->get_options());
	}
	
	function event_content($event=null)
	{
		if (!$event) $event = $this->event;
		
		echo $event->render($this->get_options());
	}
	function event_feed_label($event=null)
	{
		if (!$event) $event = $this->event;
		
		echo $event->get_feed_label($this->get_options());
	}
	function event_date($event=null)
	{
		if (!$event) $event = $this->event;

		echo date($this->lifestream->get_option('hour_format'), $event->timestamp);
	}
	
	function get_link()
	{
		return 'blah';
	}
	
	function previous_stream_link($format='&laquo; %link', $link='%title')
	{
		if (!$this->has_prev_page) return;
		
		$title = $this->lifestream->__('Previous Page');

		$rel = 'prev';

		$string = '<a href="'.$this->lifestream->get_previous_page_url().'" rel="'.$rel.'">';
		$link = str_replace('%title', $title, $link);
		$link = $string . $link . '</a>';

		$format = str_replace('%link', $link, $format);
		
		echo $format;
	}
	
	function next_stream_link($format='%link &raquo;', $link='%title')
	{
		if (!$this->has_next_page) return;

		$title = $this->lifestream->__('Next Page');

		$rel = 'next';

		$string = '<a href="'.$this->lifestream->get_next_page_url().'" rel="'.$rel.'">';
		$link = str_replace('%title', $title, $link);
		$link = $string . $link . '</a>';

		$format = str_replace('%link', $link, $format);
		
		echo $format;
	}
}

$ls_template = new LifestreamTemplate($lifestream);

function ls_have_activity()
{
	global $ls_template;
	
	return $ls_template->have_activity();
}

function ls_is_event()
{
	global $ls_template;
	
	return $ls_template->is_event();
}

function ls_is_single()
{
	global $ls_template;
	
	return $ls_template->is_single();
}

function ls_the_event()
{
	global $ls_template;
	
	return $ls_template->the_event();
}
function ls_do_credits()
{
	global $ls_template;
	
	return $ls_template->do_credits();
}
function ls_event_class()
{
	global $ls_template;
	
	return $ls_template->event_class();
}
function ls_event_link()
{
	global $ls_template;
	
	return $ls_template->event_link();
}
function ls_event_permalink()
{
	global $ls_template;
	
	return $ls_template->event_permalink();
}
function ls_event_icon()
{
	global $ls_template;
	
	return $ls_template->event_icon();
}
function ls_event_label()
{
	global $ls_template;
	
	return $ls_template->event_label();
}
function ls_get_option($key, $default=null)
{
	global $lifestream;
	
	return $lifestream->get_option($key, $default);
}
function ls_event_content()
{
	global $ls_template;
	
	return $ls_template->event_content();
}
function ls_event_feed_label()
{
	global $ls_template;
	
	return $ls_template->event_feed_label();
}
function ls_event_date()
{
	global $ls_template;
	
	return $ls_template->event_date();
}
function ls_next_page($format='%link &raquo;', $link='%title')
{
	global $ls_template;
	
	return $ls_template->next_stream_link();
}
function ls_prev_page($format='&laquo; %link', $link='%title')
{
	global $ls_template;
	
	return $ls_template->previous_stream_link();
}

?>