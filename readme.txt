=== Lifestream ===
Tags: lifestream, rss, social, miniblogging, twitter, flickr, friendfeed, delicious, last.fm, facebook, digg, atom
Requires at least: 2.5
Tested up to: 2.7.1
Stable tag: 0.99.4

Lifestream displays your social feeds and photos much like you would see it on many of the social networking sites.

== Description ==

Lifestream displays your social feeds and photos much like you would see it on many of the social networking sites.

For more information please visit the homepage: http://www.ibegin.com/labs/wp-lifestream/

Follow us on Twitter: http://www.twitter.com/wplifestream

Requirements:

* PHP 5.x
* WordPress 2.5+
* MySQL 5.x

== Installation ==

Upload the plugin (unzipped) into `/wp-content/plugins/`. You should end up with `/wp-content/plugins/lifestream/lifestream.php`.

Activate the plugin under the "Plugins" menu.

Visit "LifeStream" -> "Settings" to configure the basic options, and add feeds.

There are severals methods in which you can use LifeStream in a WordPress page.

The easiest way is to use the normal context handler (works in pages and posts):

`[lifestream]`

Another method requires a [custom WordPress page](http://codex.wordpress.org/Pages#Page_Templates), or the use of a template, and is a simple function call:

`<?php lifestream(); ?>`

You may also specify several parameters in the `lifestream` method (useful for sidebar display). These should be in the standard key=>value format for PHP calls.

* `(int) offset`: defaults to 0
* `(int) limit`: defaults to '50'
* `(string) date_interval`: defaults to '1 month'
* `(boolean) break_events`: defaults to false - forces grouped events to be single
* `(array) user_ids`: defaults to all users -- specify the ids of users to show
* `(array) feed_types`: defaults to all feeds -- specify the feed keys (e.g. twitter) to show
* `(array) feed_ids`: defaults to all feeds -- specify the ids of feeds to show, also overrides `feed_types` setting

For example:

`[lifestream limit="10"]`

For more advanced uses, you may directly use `get_events()` which will return an array of `Event` instances. This is the same syntax as the `lifestream()` method.

Example:

`<ul>
<?php
$events = $lifestream->get_events(array('limit' => 50));

foreach ($events as $event)
{
	echo '<li>'.$event->render().'</li>';
}
?>
</ul>`

Another popular example, would to be show your current Twitter, or Facebook status somewhere in your templates:

`$events = $lifestream->get_events(array('feed_types'=>array('twitter'), 'number_of_results'=>1, 'break_groups'=>true);
$event = $events[0];

// to render it with links
echo $event->feed->render_item($event, $event->data);

// or render just the text
echo $event->data['title'];`

Or, use our handy shortcuts:

`<?php lifestream_twitter_status(); ?>`

And

`<?php lifestream_facebook_status(); ?>`

== Requirements ==

* PHP 5
* WordPress 2.5 or newer

== Features ==

* Personalizable CSS classes.
* Detailed configuration options.
* Supports nearly every major social networking website
* Unlimited number of feeds in your Lifestream.
* Supports grouping of events.
* Localization ready!
* Digest available to summarize your activities.

== Built-in Feeds ==

The plugin includes most of the major social networking feeds available. You may add your own, as well as send me ideas for new feeds at dcramer@gmail.com. If you wish to add custom extensions, you may do so by creating a local_feeds.inc.php file in the lifestream directory.

* Facebook
* Digg
* Blog
* Twitter
* Reddit
* De.licio.us
* Jaiku
* Last.fm (Revamped plugin in 0.38)
* Flickr (Improved display in 0.39)
* Photobucket
* Pownce
* YouTube
* Google Reader
* Yelp
* MySpace Blog
* Skitch
* Identi.ca
* Pandora
* Hulu
* TwitPic
* Vimeo
* StumbleUpon
* Tumblr
* Amazon Wishlist
* Ma.gnolia
* Zooomr
* Blip.fm
* Brightkite
* Picasa (Web)
* Kongregate
* Viddler
* coComment
* FoodFeed
* MyEpisodes
* Mixx
* SlideShare
* Blip.tv
* Steam
* Xbox Live
* iTunes

== Localization ==

Currently the plugin is localized in the default language of English, as well as the following languages. If you wish to submit a localization please send it to dcramer@gmail.com.

* Chinese (Simplified)
* Japanese
* Polish
* Dutch
* Italian
* Bulgarian
* French
* German
* Danish
* Spanish

== Credits ==

An [iBegin Labs](http://www.ibegin.com/labs/) project.

Created and mainted by David Cramer ([mail](mailto:dcramer@gmail.com), [website](http://www.davidcramer.net)).

Core concept inspired by [RSS Stream](http://rick.jinlabs.com/code/rss-stream).
