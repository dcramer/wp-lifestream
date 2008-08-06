=== Lifestream ===
Tags: lifestream, rss, social, miniblogging, twitter, flickr, pownce, delicious, last.fm, facebook, digg, atom
Requires at least: 2.5
Tested up to: 2.6
Stable tag: trunk

Lifestream displays your social feeds and photos much like you would see it on many of the social networking sites.

== Description ==

Lifestream displays your social feeds and photos much like you would see it on many of the social networking sites.

Lifestream v0.3 extensions are NOT compatible with v0.2 or v0.1. You also will need to reactivate the plugin when upgrading from v0.1, as the database has changed.

Requirements:

* PHP 5.x
* WordPress 2.5+

== Installation ==

Upload the plugin (unzipped) into `/wp-content/plugins/`. You should end up with `/wp-content/plugins/lifestream/lifestream.php`.

Activate the plugin under the "Plugins" menu.

Visit "Settings" -> "LifeStream" to configure the basic options, and add feeds.

There are severals methods in which you can use LifeStream in a WordPress page.

The easiest way is to use the normal context handler (works in pages and posts):

`<lifestream />`

Another method requires a [custom WordPress page](http://codex.wordpress.org/Pages#Page_Templates), or the use of a template, and is a simple function call:

`<?php lifestream(); ?>`

The second method requires you install the [wp-exec](http://wordpress.org/extend/plugins/wp-exec/) plugin:

`<exec type="function" name="LifeStream" />`

You may also specify several parameters in the lifestream method (useful for sidebar display):

* `(int) number_of_items`: defaults to '50'
* `(array) feed_ids`: defaults to all feeds
* `(string) date_interval`: defaults to '1 month'
* `(string) output`: defaults to table; options are table and list

For example:

`<lifestream number_of_items="10" output="list"/>`

== Requirements ==

* PHP 5
* WordPress 2.5 or newer

== Features ==

* Personalizable CSS classes.
* Detailed configuration options.
* Detects URLs, e-mail addresses and @username replies
* Supported services: twitter, pownce, facebook, last.fm, del.icio.us, flickr
* Unlimited number of feeds in your Lifestream.
* Supports grouping of events.

== Credits ==

Created and mainted by David Cramer ([mail](mailto:dcramer@gmail.com), [website](http://www.davidcramer.net)).

Core concept inspired by [RSS Stream](http://rick.jinlabs.com/code/rss-stream).