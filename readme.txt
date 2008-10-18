=== Lifestream ===
Tags: lifestream, rss, social, miniblogging, twitter, flickr, pownce, delicious, last.fm, facebook, digg, atom
Requires at least: 2.5
Tested up to: 2.6
Stable tag: trunk

Lifestream displays your social feeds and photos much like you would see it on many of the social networking sites.

== Description ==

Lifestream displays your social feeds and photos much like you would see it on many of the social networking sites.

For more information please visit the homepage: http://www.davidcramer.net/my-projects/lifestream

Update Notes:

* LifeStream v0.3 extensions are NOT compatible with v0.2 or v0.1. You also will need to reactivate the plugin when upgrading from v0.1, as the database has changed.
* When updating to 0.38 you will need to remove your Last.fm feed and re-add it as the data structure has been completely changed. (First non-rss lifestream feed!)
* LifeStream v0.6 extensions have newly added support for multi-user which introduces two new LABEL constants that you must add to your custom code.

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

* Note: arguments passed must be as an array with key/value pairs.

You may also specify several parameters in the `lifestream` method (useful for sidebar display):

* `(int) number_of_items`: defaults to '50'
* `(array) feed_ids`: defaults to all feeds
* `(string) date_interval`: defaults to '1 month'
* `(string) output`: defaults to table; options are table and list

For example:

`[lifestream number_of_items="10" output="list"]`

For more advanced uses, you may directly use `lifestream_get_events()` which will return an array of `Event` instances.

Example:

`<ul>
<?php
$events = lifestream_get_events(array('number_of_results' => 50));

foreach ($events as $event)
{
	echo '<li>'.$event->render().'</li>';
}
?>
</ul>`

== Requirements ==

* PHP 5
* WordPress 2.5 or newer

== Features ==

* Personalizable CSS classes.
* Detailed configuration options.
* Supports nearly every major social networking website (see)
* Unlimited number of feeds in your Lifestream.
* Supports grouping of events.
* Localization ready!
* Daily digest available to summarize your activities.

== Built-in Feeds ==

The plugin includes most of the major social networking feeds available. You may add your own, as well as send me ideas for new feeds at dcramer@gmail.com.

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

== Credits ==

Created and mainted by David Cramer ([mail](mailto:dcramer@gmail.com), [website](http://www.davidcramer.net)).

Core concept inspired by [RSS Stream](http://rick.jinlabs.com/code/rss-stream).