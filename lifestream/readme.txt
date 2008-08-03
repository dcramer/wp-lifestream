=== Lifestream ===
Tags: lifestream, rss, social, miniblogging, twitter, flickr, pownce, delicious, last.fm, facebook, digg, atom
Requires at least: 2.5
Tested up to: 2.6
Stable tag: trunk

Lifestream displays your social feeds and photos much like you would see it on many of the social networking sites.

== Description ==

Lifestream displays your social feeds and photos much like you would see it on many of the social networking sites.

Requirements:
* PHP 5.x
* WordPress 2.5+

== Installation ==

Upload the plugin (unzipped) into `/wp-content/plugins/`. You should end up with `/wp-content/plugins/lifestream/lifestream.php`.

Activate the plugin under the "Plugins" menu.

Visit "Settings" -> "LifeStream" to configure the basic options, and add feeds.

Please see the "Usage" section for more information.

== Usage ==

There are severals methods in which you can use LifeStream.

The easiest way is to use the normal context handler (works in pages and posts):

`&lt;lifestream/&gt;`

Another method requires a [custom WordPress page](http://codex.wordpress.org/Pages#Page_Templates), or the use of a template, and is a simple function call:

`&lt;?php lifestream(); ?&gt;`

The second method requires you install the [wp-exec](http://wordpress.org/extend/plugins/wp-exec/) plugin:

`&lt;exec type="function" name="LifeStream" /&gt;`

You may also specify several parameters in the lifestream method (useful for sidebar display):

* `(int) number_of_items`: defaults to '50'
* `(array) feed_ids`: defaults to all feeds
* `(string) date_interval` (defaults to '1 month')

== Requirements ==

* PHP 5
* WordPress 2.5 or newer

== Features ==

* Personalizable CSS classes.
* Detailed configuration options.
* Detects URLs, e-mail addresses and @username replies
* Supported services: twitter, pownce, facebook, last.fm, del.icio.us, flickr
* Unlimited number of feeds in your Lifestream.

== Credits ==

Created and mainted by David Cramer ([mail](mailto:dcramer@gmail.com), [website](http://www.davidcramer.net).

Core concept inspired by [RSS Stream](http://rick.jinlabs.com/code/rss-stream).