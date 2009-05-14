<?php

class LifeStream_Label
{
	function __construct(&$feed, &$event, &$options)
	{
		$this->lifestream = $feed->lifestream;
		$this->feed = $feed;
		$this->event = $event;
		$this->options = $options;
	}
	
	function _get_show_details_link()
	{
		return sprintf('<a href="javascript:void(0);" onclick="lifestream_toggle(this, \'%s\', \'%2$s\', \'%2$s\');return false;">%2$s</a>', $this->options['id'], count($this->event->data));
	}
	
	// backwards compatibility
	function _get_user_label() { return $this->get_user_label(); }

	function can_group()
	{
		return true;
	}
	
	function get_feed_label()
	{
		return sprintf('<a href="%s">%s</a>', $this->feed->get_public_url(), $this->feed->get_public_name());
	}
	
	function get_user_label()
	{
		return $this->event->owner;
	}
	
	function get_template()
	{
		return 'basic';
	}
	
	function get_label_single()
	{
		$post = sprintf('<a href="%s">%s</a>', htmlspecialchars($this->event->data[0]['link']), htmlspecialchars($this->event->get_event_display($this->event->data[0])));
		return $this->lifestream->__('Posted %2$s', $this->get_feed_label(), $post);
	}
	
	function get_label_plural()
	{
		return $this->lifestream->__('Posted %s items.', $this->_get_show_details_link(), $this->get_feed_label());
	}
	
	function get_label_single_user()
	{
		$post = sprintf('<a href="%s">%s</a>', htmlspecialchars($this->event->data[0]['link']), htmlspecialchars($this->event->get_event_display($this->event->data[0])));
		return $this->lifestream->__('%1$s posted %3$s', $this->get_user_label(), $this->get_feed_label(), $post);

	}
	
	function get_label_plural_user()
	{
		return $this->lifestream->__('%s posted %s items.', $this->get_user_label(), $this->_get_show_details_link(), $this->get_feed_label());
	}
}

class LifeStream_BlogLabel extends LifeStream_Label
{
	function get_label_single()
	{
		$post = sprintf('<a href="%s">%s</a>', htmlspecialchars($this->event->data[0]['link']), htmlspecialchars($this->event->get_event_display($this->event->data[0])));
		return $this->lifestream->__('Published %2$s', $this->get_feed_label(), $post);
	}
	
	function get_label_plural()
	{
		return $this->lifestream->__('Published %s posts.', $this->_get_show_details_link(), $this->get_feed_label());
	}
	
	function get_label_single_user()
	{
		$post = sprintf('<a href="%s">%s</a>', htmlspecialchars($this->event->data[0]['link']), htmlspecialchars($this->event->get_event_display($this->event->data[0])));
		return $this->lifestream->__('%1$s published %3$s', $this->get_user_label(), $this->get_feed_label(), $post);
	}
	
	function get_label_plural_user()
	{
		return $this->lifestream->__('%s published %s.', $this->get_user_label(), $this->_get_show_details_link(), $this->get_feed_label());
	}
	
	function get_template()
	{
		return 'post';
	}
}

class LifeStream_PhotoLabel extends LifeStream_Label
{
	function get_template()
	{
		return 'photo';
	}
	
	function get_label_single()
	{
		$post = sprintf('<a href="%s">%s</a>', htmlspecialchars($this->event->data[0]['link']), htmlspecialchars($this->event->get_event_display($this->event->data[0])));
		return $this->lifestream->__('Shared %2$s', $this->get_feed_label(), $post);
	}
	
	function get_label_plural()
	{
		return $this->lifestream->__('Shared %s photos.', $this->_get_show_details_link(), $this->get_feed_label());
	}
	
	function get_label_single_user()
	{
		$post = sprintf('<a href="%s">%s</a>', htmlspecialchars($this->event->data[0]['link']), htmlspecialchars($this->event->get_event_display($this->event->data[0])));
		return $this->lifestream->__('%1$s shared %3$s', $this->get_user_label(), $this->get_feed_label(), $post);
	}
	
	function get_label_plural_user()
	{
		return $this->lifestream->__('%s shared %s photos.', $this->get_user_label(), $this->_get_show_details_link(), $this->get_feed_label());
	}
}

class LifeStream_BookmarkLabel extends LifeStream_Label
{
	function get_template()
	{
		return 'bookmark';
	}
	
	function get_label_single()
	{
		$post = sprintf('<a href="%s">%s</a>', htmlspecialchars($this->event->data[0]['link']), htmlspecialchars($this->event->get_event_display($this->event->data[0])));
		return $this->lifestream->__('Shared %2$s', $this->get_feed_label(), $post);
	}
	
	function get_label_plural()
	{
		return $this->lifestream->__('Shared %s links.', $this->_get_show_details_link(), $this->get_feed_label());
	}
	
	function get_label_single_user()
	{
		$post = sprintf('<a href="%s">%s</a>', htmlspecialchars($this->event->data[0]['link']), htmlspecialchars($this->event->get_event_display($this->event->data[0])));
		return $this->lifestream->__('%1$s shared %3$s', $this->get_user_label(), $this->get_feed_label(), $post);
	}
	
	function get_label_plural_user()
	{
		return $this->lifestream->__('%s shared %s links.', $this->get_user_label(), $this->_get_show_details_link(), $this->get_feed_label());
	}
}

class LifeStream_MessageLabel extends LifeStream_Label
{
	function get_template()
	{
		return 'message';
	}
	
	function can_group()
	{
		return false;
	}
	
	function get_label_single()
	{
		return $this->lifestream->__('Posted a message.', $this->get_feed_label());
	}
	
	function get_label_plural()
	{
		return $this->lifestream->__('Posted %s messages.', $this->_get_show_details_link(), $this->get_feed_label());
	}
	
	function get_label_single_user()
	{
		return $this->lifestream->__('%s posted a message.', $this->get_user_label(), $this->get_feed_label());
	}
	
	function get_label_plural_user()
	{
		return $this->lifestream->__('%s posted %s messages.', $this->get_user_label(), $this->_get_show_details_link(), $this->get_feed_label());
	}
}

class LifeStream_ReviewLabel extends LifeStream_Label
{
	function get_template()
	{
		return 'basic';
	}
	
	function get_label_single()
	{
		$post = sprintf('<a href="%s">%s</a>', htmlspecialchars($this->event->data[0]['link']), htmlspecialchars($this->event->get_event_display($this->event->data[0])));
		return $this->lifestream->__('Reviewed %2$s', $this->get_feed_label(), $post);
	}
	
	function get_label_plural()
	{
		return $this->lifestream->__('Reviewed %s items.', $this->_get_show_details_link(), $this->get_feed_label());
	}
	
	function get_label_single_user()
	{
		$post = sprintf('<a href="%s">%s</a>', htmlspecialchars($this->event->data[0]['link']), htmlspecialchars($this->event->get_event_display($this->event->data[0])));
		return $this->lifestream->__('%1$s reviewed %3$s', $this->get_user_label(), $this->get_feed_label(), $post);
	}
	
	function get_label_plural_user()
	{
		return $this->lifestream->__('%s reviewed %s items.', $this->get_user_label(), $this->_get_show_details_link(), $this->get_feed_label());
	}
}

class LifeStream_PurchaseLabel extends LifeStream_Label
{
	function get_template()
	{
		return 'basic';
	}
	
	function get_label_single()
	{
		$post = sprintf('<a href="%s">%s</a>', htmlspecialchars($this->event->data[0]['link']), htmlspecialchars($this->event->get_event_display($this->event->data[0])));
		return $this->lifestream->__('Purchased %2$s', $this->get_feed_label(), $post);
	}
	
	function get_label_plural()
	{
		return $this->lifestream->__('Purchased %s items.', $this->_get_show_details_link(), $this->get_feed_label());
	}
	
	function get_label_single_user()
	{
		$post = sprintf('<a href="%s">%s</a>', htmlspecialchars($this->event->data[0]['link']), htmlspecialchars($this->event->get_event_display($this->event->data[0])));
		return $this->lifestream->__('%1$s purchased %3$s', $this->get_user_label(), $this->get_feed_label(), $post);
	}
	
	function get_label_plural_user()
	{
		return $this->lifestream->__('%s purchased %s items.', $this->get_user_label(), $this->_get_show_details_link(), $this->get_feed_label());
	}
}

class LifeStream_CommitLabel extends LifeStream_Label
{
	function get_label_single()
	{
		return $this->lifestream->__('Commited code.', $this->get_feed_label());
	}
	
	function get_label_plural()
	{
		return $this->lifestream->__('Made %s commits.', $this->_get_show_details_link(), $this->get_feed_label());
	}
	
	function get_label_single_user()
	{
		return $this->lifestream->__('%s commited code.', $this->get_user_label(), $this->get_feed_label());
	}
	
	function get_label_plural_user()
	{
		return $this->lifestream->__('%s made %s commits.', $this->get_user_label(), $this->_get_show_details_link(), $this->get_feed_label());
	}
}

class LifeStream_BookLabel extends LifeStream_Label
{
	function get_template()
	{
		return 'basic';
	}
	
	function get_label_single()
	{
		$post = sprintf('<a href="%s">%s</a>', htmlspecialchars($this->event->data[0]['link']), htmlspecialchars($this->event->get_event_display($this->event->data[0])));
		return $this->lifestream->__('Shared %2$s', $this->get_feed_label(), $post);
	}
	
	function get_label_plural()
	{
		return $this->lifestream->__('Shared %s books.', $this->_get_show_details_link(), $this->get_feed_label());
	}
	
	function get_label_single_user()
	{
		$post = sprintf('<a href="%s">%s</a>', htmlspecialchars($this->event->data[0]['link']), htmlspecialchars($this->event->get_event_display($this->event->data[0])));
		return $this->lifestream->__('%1$s shared %3$s', $this->get_user_label(), $this->get_feed_label(), $post);
	}
	
	function get_label_plural_user()
	{
		return $this->lifestream->__('%s shared %s books.', $this->get_user_label(), $this->_get_show_details_link(), $this->get_feed_label());
	}
}

class LifeStream_CommentLabel extends LifeStream_Label
{
	function get_template()
	{
		return 'comment';
	}
	
	function can_group()
	{
		return false;
	}

	function get_label_single()
	{
		return $this->lifestream->__('Posted a comment.', $this->get_feed_label());
	}
	
	function get_label_plural()
	{
		return $this->lifestream->__('Posted %s comments.', $this->_get_show_details_link(), $this->get_feed_label());
	}
	
	function get_label_single_user()
	{
		return $this->lifestream->__('%s posted a comment.', $this->get_user_label(), $this->get_feed_label());
	}
	
	function get_label_plural_user()
	{
		return $this->lifestream->__('%s posted %s comments.', $this->get_user_label(), $this->_get_show_details_link(), $this->get_feed_label());
	}
}

class LifeStream_LikeStoryLabel extends LifeStream_Label
{
	function get_template()
	{
		return 'basic';
	}
	
	function get_label_single()
	{
		$post = sprintf('<a href="%s">%s</a>', htmlspecialchars($this->event->data[0]['link']), htmlspecialchars($this->event->get_event_display($this->event->data[0])));
		return $this->lifestream->__('Liked %2$s', $this->get_feed_label(), $post);
	}
	
	function get_label_plural()
	{
		return $this->lifestream->__('Liked %s stories.', $this->_get_show_details_link(), $this->get_feed_label());
	}
	
	function get_label_single_user()
	{
		$post = sprintf('<a href="%s">%s</a>', htmlspecialchars($this->event->data[0]['link']), htmlspecialchars($this->event->get_event_display($this->event->data[0])));
		return $this->lifestream->__('%1$s liked %3$s', $this->get_user_label(), $this->get_feed_label(), $post);
	}
	
	function get_label_plural_user()
	{
		return $this->lifestream->__('%s liked %s stories.', $this->get_user_label(), $this->_get_show_details_link(), $this->get_feed_label());
	}
}

class LifeStream_BusinessReviewLabel extends LifeStream_Label
{
	function get_template()
	{
		return 'basic';
	}
	
	function get_label_single()
	{
		$post = sprintf('<a href="%s">%s</a>', htmlspecialchars($this->event->data[0]['link']), htmlspecialchars($this->event->get_event_display($this->event->data[0])));
		return $this->lifestream->__('Reviewed %2$s', $this->get_feed_label(), $post);
	}
	
	function get_label_plural()
	{
		return $this->lifestream->__('Reviewed %s businesses.', $this->_get_show_details_link(), $this->get_feed_label());
	}
	
	function get_label_single_user()
	{
		$post = sprintf('<a href="%s">%s</a>', htmlspecialchars($this->event->data[0]['link']), htmlspecialchars($this->event->get_event_display($this->event->data[0])));
		return $this->lifestream->__('%1$s reviewed %3$s', $this->get_user_label(), $this->get_feed_label(), $post);
	}
	
	function get_label_plural_user()
	{
		return $this->lifestream->__('%s reviewed %s businesses.', $this->get_user_label(), $this->_get_show_details_link(), $this->get_feed_label());
	}
}

class LifeStream_ListenLabel extends LifeStream_Label
{
	function get_template()
	{
		return 'basic';
	}
	
	function get_label_single()
	{
		$post = sprintf('<a href="%s">%s</a>', htmlspecialchars($this->event->data[0]['link']), htmlspecialchars($this->event->get_event_display($this->event->data[0])));
		return $this->lifestream->__('Listened to %2$s', $this->get_feed_label(), $post);
	}
	
	function get_label_plural()
	{
		return $this->lifestream->__('Listened to %s songs.', $this->_get_show_details_link(), $this->get_feed_label());
	}
	
	function get_label_single_user()
	{
		$post = sprintf('<a href="%s">%s</a>', htmlspecialchars($this->event->data[0]['link']), htmlspecialchars($this->event->get_event_display($this->event->data[0])));
		return $this->lifestream->__('%1$s listened to %3$s', $this->get_user_label(), $this->get_feed_label(), $post);
	}
	
	function get_label_plural_user()
	{
		return $this->lifestream->__('%s listened to %s songs.', $this->get_user_label(), $this->_get_show_details_link(), $this->get_feed_label());
	}
}

class LifeStream_VideoLabel extends LifeStream_Label
{
	function get_template()
	{
		return 'photo';
	}
	
	function get_label_single()
	{
		$post = sprintf('<a href="%s">%s</a>', htmlspecialchars($this->event->data[0]['link']), htmlspecialchars($this->event->get_event_display($this->event->data[0])));
		return $this->lifestream->__('Shared %2$s', $this->get_feed_label(), $post);
	}
	
	function get_label_plural()
	{
		return $this->lifestream->__('Shared %s videos.', $this->_get_show_details_link(), $this->get_feed_label());
	}
	
	function get_label_single_user()
	{
		$post = sprintf('<a href="%s">%s</a>', htmlspecialchars($this->event->data[0]['link']), htmlspecialchars($this->event->get_event_display($this->event->data[0])));
		return $this->lifestream->__('%1$s shared %3$s', $this->get_user_label(), $this->get_feed_label(), $post);
	}
	
	function get_label_plural_user()
	{
		return $this->lifestream->__('%s shared %s videos.', $this->get_user_label(), $this->_get_show_details_link(), $this->get_feed_label());
	}
}
class LifeStream_LikeVideoLabel extends LifeStream_VideoLabel
{
	function get_template()
	{
		return 'photo';
	}
	
	function get_label_single()
	{
		$post = sprintf('<a href="%s">%s</a>', htmlspecialchars($this->event->data[0]['link']), htmlspecialchars($this->event->get_event_display($this->event->data[0])));
		return $this->lifestream->__('Liked %2$s', $this->get_feed_label(), $post);
	}
	
	function get_label_plural()
	{
		return $this->lifestream->__('Liked %s videos.', $this->_get_show_details_link(), $this->get_feed_label());
	}
	
	function get_label_single_user()
	{
		$post = sprintf('<a href="%s">%s</a>', htmlspecialchars($this->event->data[0]['link']), htmlspecialchars($this->event->get_event_display($this->event->data[0])));
		return $this->lifestream->__('%1$s liked %3$s', $this->get_user_label(), $this->get_feed_label(), $post);
	}
	
	function get_label_plural_user()
	{
		return $this->lifestream->__('%s liked %s videos.', $this->get_user_label(), $this->_get_show_details_link(), $this->get_feed_label());
	}
}

class LifeStream_LikeSongLabel extends LifeStream_Label
{
	function get_template()
	{
		return 'like';
	}
	
	function get_label_single()
	{
		$post = sprintf('<a href="%s">%s</a>', htmlspecialchars($this->event->data[0]['link']), htmlspecialchars($this->event->get_event_display($this->event->data[0])));
		return $this->lifestream->__('Liked %2$s', $this->get_feed_label(), $post);
	}
	
	function get_label_plural()
	{
		return $this->lifestream->__('Liked %s songs.', $this->_get_show_details_link(), $this->get_feed_label());
	}
	
	function get_label_single_user()
	{
		$post = sprintf('<a href="%s">%s</a>', htmlspecialchars($this->event->data[0]['link']), htmlspecialchars($this->event->get_event_display($this->event->data[0])));
		return $this->lifestream->__('%1$s liked %3$s', $this->get_user_label(), $this->get_feed_label(), $post);
	}
	
	function get_label_plural_user()
	{
		return $this->lifestream->__('%s liked %s songs.', $this->get_user_label(), $this->_get_show_details_link(), $this->get_feed_label());
	}
}

class LifeStream_LikeArtistLabel extends LifeStream_Label
{
	function get_template()
	{
		return 'basic';
	}
	
	function get_label_single()
	{
		$post = sprintf('<a href="%s">%s</a>', htmlspecialchars($this->event->data[0]['link']), htmlspecialchars($this->event->get_event_display($this->event->data[0])));
		return $this->lifestream->__('Liked %2$s', $this->get_feed_label(), $post);
	}
	
	function get_label_plural()
	{
		return $this->lifestream->__('Liked %s artists.', $this->_get_show_details_link(), $this->get_feed_label());
	}
	
	function get_label_single_user()
	{
		$post = sprintf('<a href="%s">%s</a>', htmlspecialchars($this->event->data[0]['link']), htmlspecialchars($this->event->get_event_display($this->event->data[0])));
		return $this->lifestream->__('%1$s liked %3$s', $this->get_user_label(), $this->get_feed_label(), $post);
	}
	
	function get_label_plural_user()
	{
		return $this->lifestream->__('%s liked %s artists.', $this->get_user_label(), $this->_get_show_details_link(), $this->get_feed_label());
	}
}

class LifeStream_CreateStationLabel extends LifeStream_Label
{
	function get_template()
	{
		return 'basic';
	}
	
	function get_label_single()
	{
		$post = sprintf('<a href="%s">%s</a>', htmlspecialchars($this->event->data[0]['link']), htmlspecialchars($this->event->get_event_display($this->event->data[0])));
		return $this->lifestream->__('Created %2$s', $this->get_feed_label(), $post);
	}
	
	function get_label_plural()
	{
		return $this->lifestream->__('Created %s stations.', $this->_get_show_details_link(), $this->get_feed_label());
	}
	
	function get_label_single_user()
	{
		$post = sprintf('<a href="%s">%s</a>', htmlspecialchars($this->event->data[0]['link']), htmlspecialchars($this->event->get_event_display($this->event->data[0])));
		return $this->lifestream->__('%1$s created %3$s', $this->get_user_label(), $this->get_feed_label(), $post);
	}
	
	function get_label_plural_user()
	{
		return $this->lifestream->__('%s created %s stations.', $this->get_user_label(), $this->_get_show_details_link(), $this->get_feed_label());
	}
}

class LifeStream_WatchVideoLabel extends LifeStream_Label
{
	function get_template()
	{
		return 'basic';
	}
	
	function get_label_single()
	{
		$post = sprintf('<a href="%s">%s</a>', htmlspecialchars($this->event->data[0]['link']), htmlspecialchars($this->event->get_event_display($this->event->data[0])));
		return $this->lifestream->__('Watched %2$s', $this->get_feed_label(), $post);
	}
	
	function get_label_plural()
	{
		return $this->lifestream->__('Watched %s videos.', $this->_get_show_details_link(), $this->get_feed_label());
	}
	
	function get_label_single_user()
	{
		$post = sprintf('<a href="%s">%s</a>', htmlspecialchars($this->event->data[0]['link']), htmlspecialchars($this->event->get_event_display($this->event->data[0])));
		return $this->lifestream->__('%1$s watched %3$s', $this->get_user_label(), $this->get_feed_label(), $post);
	}
	
	function get_label_plural_user()
	{
		return $this->lifestream->__('%s watched %s videos.', $this->get_user_label(), $this->_get_show_details_link(), $this->get_feed_label());
	}
}

class LifeStream_ReviewWebsiteLabel extends LifeStream_Label
{
	function get_template()
	{
		return 'basic';
	}
	
	function get_label_single()
	{
		$post = sprintf('<a href="%s">%s</a>', htmlspecialchars($this->event->data[0]['link']), htmlspecialchars($this->event->get_event_display($this->event->data[0])));
		return $this->lifestream->__('Reviewed %2$s', $this->get_feed_label(), $post);
	}
	
	function get_label_plural()
	{
		return $this->lifestream->__('Reviewed %s websites.', $this->_get_show_details_link(), $this->get_feed_label());
	}
	
	function get_label_single_user()
	{
		$post = sprintf('<a href="%s">%s</a>', htmlspecialchars($this->event->data[0]['link']), htmlspecialchars($this->event->get_event_display($this->event->data[0])));
		return $this->lifestream->__('%1$s reviewed %3$s', $this->get_user_label(), $this->get_feed_label(), $post);
	}
	
	function get_label_plural_user()
	{
		return $this->lifestream->__('%s reviewed %s websites.', $this->get_user_label(), $this->_get_show_details_link(), $this->get_feed_label());
	}
}

class LifeStream_LikeWebsiteLabel extends LifeStream_Label
{
	function get_template()
	{
		return 'basic';
	}
	
	function get_label_single()
	{
		$post = sprintf('<a href="%s">%s</a>', htmlspecialchars($this->event->data[0]['link']), htmlspecialchars($this->event->get_event_display($this->event->data[0])));
		return $this->lifestream->__('Liked %2$s', $this->get_feed_label(), $post);
	}
	
	function get_label_plural()
	{
		return $this->lifestream->__('Liked %s websites.', $this->_get_show_details_link(), $this->get_feed_label());
	}
	
	function get_label_single_user()
	{
		$post = sprintf('<a href="%s">%s</a>', htmlspecialchars($this->event->data[0]['link']), htmlspecialchars($this->event->get_event_display($this->event->data[0])));
		return $this->lifestream->__('%1$s liked %3$s', $this->get_user_label(), $this->get_feed_label(), $post);
	}
	
	function get_label_plural_user()
	{
		return $this->lifestream->__('%s liked %s websites.', $this->get_user_label(), $this->_get_show_details_link(), $this->get_feed_label());
	}
}

class LifeStream_WantLabel extends LifeStream_Label
{
	function get_template()
	{
		return 'basic';
	}
	
	function get_label_single()
	{
		$post = sprintf('<a href="%s">%s</a>', htmlspecialchars($this->event->data[0]['link']), htmlspecialchars($this->event->get_event_display($this->event->data[0])));
		return $this->lifestream->__('Wants %2$s', $this->get_feed_label(), $post);
	}
	
	function get_label_plural()
	{
		return $this->lifestream->__('Wants %s items.', $this->_get_show_details_link(), $this->get_feed_label());
	}
	
	function get_label_single_user()
	{
		$post = sprintf('<a href="%s">%s</a>', htmlspecialchars($this->event->data[0]['link']), htmlspecialchars($this->event->get_event_display($this->event->data[0])));
		return $this->lifestream->__('%1$s wants %3$s', $this->get_user_label(), $this->get_feed_label(), $post);
	}
	
	function get_label_plural_user()
	{
		return $this->lifestream->__('%s wants %s items.', $this->get_user_label(), $this->_get_show_details_link(), $this->get_feed_label());
	}
}

class LifeStream_LocationLabel extends LifeStream_Label
{
	function get_template()
	{
		return 'basic';
	}
	
	function get_label_single()
	{
		$post = sprintf('<a href="%s">%s</a>', htmlspecialchars($this->event->data[0]['link']), htmlspecialchars($this->event->get_event_display($this->event->data[0])));
		return $this->lifestream->__('Checked in at %2$s', $this->get_feed_label(), $post);
	}
	
	function get_label_plural()
	{
		return $this->lifestream->__('Checked in %s times.', $this->_get_show_details_link(), $this->get_feed_label());
	}
	
	function get_label_single_user()
	{
		$post = sprintf('<a href="%s">%s</a>', htmlspecialchars($this->event->data[0]['link']), htmlspecialchars($this->event->get_event_display($this->event->data[0])));
		return $this->lifestream->__('%1$s checked in at %3$s', $this->get_user_label(), $this->get_feed_label(), $post);
	}
	
	function get_label_plural_user()
	{
		return $this->lifestream->__('%s checked in %s times.', $this->get_user_label(), $this->_get_show_details_link(), $this->get_feed_label());
	}
}

class LifeStream_ReceiveBadgeLabel extends LifeStream_Label
{
	function get_template()
	{
		return 'basic';
	}
	
	function get_label_single()
	{
		$post = sprintf('<a href="%s">%s</a>', htmlspecialchars($this->event->data[0]['link']), htmlspecialchars($this->event->get_event_display($this->event->data[0])));
		return $this->lifestream->__('Received %2$s', $this->get_feed_label(), $post);
	}
	
	function get_label_plural()
	{
		return $this->lifestream->__('Received %s badges.', $this->_get_show_details_link(), $this->get_feed_label());
	}
	
	function get_label_single_user()
	{
		$post = sprintf('<a href="%s">%s</a>', htmlspecialchars($this->event->data[0]['link']), htmlspecialchars($this->event->get_event_display($this->event->data[0])));
		return $this->lifestream->__('%1$s received %3$s', $this->get_user_label(), $this->get_feed_label(), $post);
	}
	
	function get_label_plural_user()
	{
		return $this->lifestream->__('%s received %s badges.', $this->get_user_label(), $this->_get_show_details_link(), $this->get_feed_label());
	}
}

class LifeStream_EatLabel extends LifeStream_Label
{
	function get_template()
	{
		return 'basic';
	}
	
	function get_label_single()
	{
		$post = sprintf('<a href="%s">%s</a>', htmlspecialchars($this->event->data[0]['link']), htmlspecialchars($this->event->get_event_display($this->event->data[0])));
		return $this->lifestream->__('Ate %2$s.', $this->get_feed_label(), $post);
	}
	
	function get_label_plural()
	{
		return $this->lifestream->__('Ate %s meals.', $this->_get_show_details_link(), $this->get_feed_label());
	}
	
	function get_label_single_user()
	{
		$post = sprintf('<a href="%s">%s</a>', htmlspecialchars($this->event->data[0]['link']), htmlspecialchars($this->event->get_event_display($this->event->data[0])));
		return $this->lifestream->__('%1$s ate %3$s.', $this->get_user_label(), $this->get_feed_label(), $post);
	}
	
	function get_label_plural_user()
	{
		return $this->lifestream->__('%s ate %s.', $this->get_user_label(), $this->_get_show_details_link(), $this->get_feed_label());
	}
}

class LifeStream_WatchEpisodeLabel extends LifeStream_Label
{
	function get_template()
	{
		return 'basic';
	}
	
	function get_label_single()
	{
		$post = sprintf('<a href="%s">%s</a>', htmlspecialchars($this->event->data[0]['link']), htmlspecialchars($this->event->get_event_display($this->event->data[0])));
		return $this->lifestream->__('Watched %2$s.', $this->get_feed_label(), $post);
	}
	
	function get_label_plural()
	{
		return $this->lifestream->__('Watched %s episodes.', $this->_get_show_details_link(), $this->get_feed_label());
	}
	
	function get_label_single_user()
	{
		$post = sprintf('<a href="%s">%s</a>', htmlspecialchars($this->event->data[0]['link']), htmlspecialchars($this->event->get_event_display($this->event->data[0])));
		return $this->lifestream->__('%1$s watched %3$s.', $this->get_user_label(), $this->get_feed_label(), $post);
	}
	
	function get_label_plural_user()
	{
		return $this->lifestream->__('%s watched %s episodes.', $this->get_user_label(), $this->_get_show_details_link(), $this->get_feed_label());
	}
}

class LifeStream_ShareStoryLabel extends LifeStream_Label
{
	function get_template()
	{
		return 'basic';
	}
	
	function get_label_single()
	{
		$post = sprintf('<a href="%s">%s</a>', htmlspecialchars($this->event->data[0]['link']), htmlspecialchars($this->event->get_event_display($this->event->data[0])));
		return $this->lifestream->__('Shared %2$s', $this->get_feed_label(), $post);
	}
	
	function get_label_plural()
	{
		return $this->lifestream->__('Shared %s stories.', $this->_get_show_details_link(), $this->get_feed_label());
	}
	
	function get_label_single_user()
	{
		$post = sprintf('<a href="%s">%s</a>', htmlspecialchars($this->event->data[0]['link']), htmlspecialchars($this->event->get_event_display($this->event->data[0])));
		return $this->lifestream->__('%1$s shared %3$s', $this->get_user_label(), $this->get_feed_label(), $post);
	}
	
	function get_label_plural_user()
	{
		return $this->lifestream->__('%s shared %s stories.', $this->get_user_label(), $this->_get_show_details_link(), $this->get_feed_label());
	}
}

class LifeStream_LikeMovieLabel extends LifeStream_VideoLabel
{
	function get_template()
	{
		return 'photo';
	}
	
	function get_label_single()
	{
		$post = sprintf('<a href="%s">%s</a>', htmlspecialchars($this->event->data[0]['link']), htmlspecialchars($this->event->get_event_display($this->event->data[0])));
		return $this->lifestream->__('Liked %2$s', $this->get_feed_label(), $post);
	}
	
	function get_label_plural()
	{
		return $this->lifestream->__('Liked %s movies.', $this->_get_show_details_link(), $this->get_feed_label());
	}
	
	function get_label_single_user()
	{
		$post = sprintf('<a href="%s">%s</a>', htmlspecialchars($this->event->data[0]['link']), htmlspecialchars($this->event->get_event_display($this->event->data[0])));
		return $this->lifestream->__('%1$s liked %3$s', $this->get_user_label(), $this->get_feed_label(), $post);
	}
	
	function get_label_plural_user()
	{
		return $this->lifestream->__('%s liked %s movies.', $this->get_user_label(), $this->_get_show_details_link(), $this->get_feed_label());
	}
}

class LifeStream_ShareSlideLabel extends LifeStream_Label
{
	function get_label_single()
	{
		$post = sprintf('<a href="%s">%s</a>', htmlspecialchars($this->event->data[0]['link']), htmlspecialchars($this->event->get_event_display($this->event->data[0])));
		return $this->lifestream->__('Shared %2$s', $this->get_feed_label(), $post);
	}
	
	function get_label_plural()
	{
		return $this->lifestream->__('Shared %s presentations.', $this->_get_show_details_link(), $this->get_feed_label());
	}
	
	function get_label_single_user()
	{
		$post = sprintf('<a href="%s">%s</a>', htmlspecialchars($this->event->data[0]['link']), htmlspecialchars($this->event->get_event_display($this->event->data[0])));
		return $this->lifestream->__('%1$s shared %3$s', $this->get_user_label(), $this->get_feed_label(), $post);
	}
	
	function get_label_plural_user()
	{
		return $this->lifestream->__('%s shared %s presentations.', $this->get_user_label(), $this->_get_show_details_link(), $this->get_feed_label());
	}
}

class LifeStream_UnlockAchievementLabel extends LifeStream_Label
{
	function get_template()
	{
		return 'basic';
	}
	
	function get_label_single()
	{
		$post = sprintf('<a href="%s">%s</a>', htmlspecialchars($this->event->data[0]['link']), htmlspecialchars($this->event->get_event_display($this->event->data[0])));
		return $this->lifestream->__('Unlocked %2$s', $this->get_feed_label(), $post);
	}
	
	function get_label_plural()
	{
		return $this->lifestream->__('Unlocked %s achievements.', $this->_get_show_details_link(), $this->get_feed_label());
	}
	
	function get_label_single_user()
	{
		$post = sprintf('<a href="%s">%s</a>', htmlspecialchars($this->event->data[0]['link']), htmlspecialchars($this->event->get_event_display($this->event->data[0])));
		return $this->lifestream->__('%1$s unlocked %3$s', $this->get_user_label(), $this->get_feed_label(), $post);
	}
	
	function get_label_plural_user()
	{
		return $this->lifestream->__('%s unlocked %s achievements.', $this->get_user_label(), $this->_get_show_details_link(), $this->get_feed_label());
	}
}

class LifeStream_PlayGameLabel extends LifeStream_Label
{
	function get_template()
	{
		return 'basic';
	}
	
	function get_label_single()
	{
		$post = sprintf('<a href="%s">%s</a>', htmlspecialchars($this->event->data[0]['link']), htmlspecialchars($this->event->get_event_display($this->event->data[0])));
		return $this->lifestream->__('Played %2$s', $this->get_feed_label(), $post);
	}
	
	function get_label_plural()
	{
		return $this->lifestream->__('Played %s games.', $this->_get_show_details_link(), $this->get_feed_label());
	}
	
	function get_label_single_user()
	{
		$post = sprintf('<a href="%s">%s</a>', htmlspecialchars($this->event->data[0]['link']), htmlspecialchars($this->event->get_event_display($this->event->data[0])));
		return $this->lifestream->__('%1$s played %3$s', $this->get_user_label(), $this->get_feed_label(), $post);
	}
	
	function get_label_plural_user()
	{
		return $this->lifestream->__('%s played %s games.', $this->get_user_label(), $this->_get_show_details_link(), $this->get_feed_label());
	}
}
class LifeStream_QueueVideoLabel extends LifeStream_Label
{
	function get_template()
	{
		return 'basic';
	}
  
	function get_label_single()
	{
		$post = sprintf('<a href="%s">%s</a>', htmlspecialchars($this->event->data[0]['link']), htmlspecialchars($this->event->get_event_display($this->event->data[0])));
		return $this->lifestream->__('Queued %2$s', $this->get_feed_label(), $post);
	}

	function get_label_plural()
	{
		return $this->lifestream->__('Queued %s videos.', $this->_get_show_details_link(), $this->get_feed_label());
	}
	
	function get_label_single_user()
	{
		$post = sprintf('<a href="%s">%s</a>', htmlspecialchars($this->event->data[0]['link']), htmlspecialchars($this->event->get_event_display($this->event->data[0])));
		return $this->lifestream->__('%1$s queued %3$s', $this->get_user_label(), $this->get_feed_label(), $post);
	}
	
	function get_label_plural_user()
	{
		return $this->lifestream->__('%s queued %s videos.', $this->get_user_label(), $this->_get_show_details_link(), $this->get_feed_label());
	}
}

class LifeStream_ReviewVideoLabel extends LifeStream_Label
{
	function get_template()
	{
		return 'basic';
	}
  
	function get_label_single()
	{
		$post = sprintf('<a href="%s">%s</a>', htmlspecialchars($this->event->data[0]['link']), htmlspecialchars($this->event->get_event_display($this->event->data[0])));
		return $this->lifestream->__('Reviewed %2$s', $this->get_feed_label(), $post);
	}

	function get_label_plural()
	{
		return $this->lifestream->__('Reviewed %s videos.', $this->_get_show_details_link(), $this->get_feed_label());
	}
	
	function get_label_single_user()
	{
		$post = sprintf('<a href="%s">%s</a>', htmlspecialchars($this->event->data[0]['link']), htmlspecialchars($this->event->get_event_display($this->event->data[0])));
		return $this->lifestream->__('%1$s reviewed %3$s', $this->get_user_label(), $this->get_feed_label(), $post);
	}
	
	function get_label_plural_user()
	{
		return $this->lifestream->__('%s reviewed %s videos.', $this->get_user_label(), $this->_get_show_details_link(), $this->get_feed_label());
	}
}

class LifeStream_AttendEventLabel extends LifeStream_Label
{
	function get_template()
	{
		return 'basic';
	}
  
	function get_label_single()
	{
		$post = sprintf('<a href="%s">%s</a>', htmlspecialchars($this->event->data[0]['link']), htmlspecialchars($this->event->get_event_display($this->event->data[0])));
		return $this->lifestream->__('Attending %2$s', $this->get_feed_label(), $post);
	}

	function get_label_plural()
	{
		return $this->lifestream->__('Attending %s events.', $this->_get_show_details_link(), $this->get_feed_label());
	}
	
	function get_label_single_user()
	{
		$post = sprintf('<a href="%s">%s</a>', htmlspecialchars($this->event->data[0]['link']), htmlspecialchars($this->event->get_event_display($this->event->data[0])));
		return $this->lifestream->__('%1$s is attending %3$s', $this->get_user_label(), $this->get_feed_label(), $post);
	}
	
	function get_label_plural_user()
	{
		return $this->lifestream->__('%s is attending %s events.', $this->get_user_label(), $this->_get_show_details_link(), $this->get_feed_label());
	}
}

class LifeStream_ContributionLabel extends LifeStream_Label
{
	function get_template()
	{
		return 'basic';
	}
  
	function get_label_single()
	{
		$post = sprintf('<a href="%s">%s</a>', htmlspecialchars($this->event->data[0]['link']), htmlspecialchars($this->event->get_event_display($this->event->data[0])));
		return $this->lifestream->__('Contributed to %2$s', $this->get_feed_label(), $post);
	}

	function get_label_plural()
	{
		return $this->lifestream->__('Contributed to %s pages.', $this->_get_show_details_link(), $this->get_feed_label());
	}
	
	function get_label_single_user()
	{
		$post = sprintf('<a href="%s">%s</a>', htmlspecialchars($this->event->data[0]['link']), htmlspecialchars($this->event->get_event_display($this->event->data[0])));
		return $this->lifestream->__('%1$s contributed to %3$s', $this->get_user_label(), $this->get_feed_label(), $post);
	}
	
	function get_label_plural_user()
	{
		return $this->lifestream->__('%s contributed to %s pages.', $this->get_user_label(), $this->_get_show_details_link(), $this->get_feed_label());
	}
}
?>