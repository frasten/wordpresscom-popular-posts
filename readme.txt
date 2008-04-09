=== Plugin Name ===
Contributors: frasten
Donate link: http://polpoinodroidi.netsons.org/wordpress-plugins/wordpresscom-popular-posts/#donations
Tags: posts, widget, statistics, popular posts
Requires at least: 2.2.0
Tested up to: 2.5.0
Stable tag: 0.4.0

This plugin can show the most popular articles in your sidebar, using data collected by Wordpress.com Stats plugin.

== Description ==


Wordpress.com Popular Posts lists the most popular posts on a wordpress powered weblog.
This list can be used in the sidebar to show an indication of which are the most visited pages.

For further info and changelog visit [plugin homepage](http://polpoinodroidi.netsons.org/wordpress-plugins/wordpresscom-popular-posts/).

**Requires [Wordpress.com Stats](http://wordpress.org/extend/plugins/stats/) plugin, at least v1.2**

== Installation ==

Wordpress.com Popular Posts can be installed easily:

1. Install [Wordpress.com Stats](http://wordpress.org/extend/plugins/stats/) plugin.
1. Extract the files in the .zip archive, and upload them (including subfolders) to your /wp-content/plugins/ directory.
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Inside the WordPress admin, go to Design > Widgets, and add the 'Popular Posts' widget where you want, then save the changes.
1. If you want, you can customize some settings in the widget, in that page.

== Frequently Asked Questions ==

= I added the widget, but nothing shows up =

Check whether the Wordpress.com Stats plugin is installed and active.
You must have at least version 1.2 of WP Stats.

= How can I integrate this plugin in my non-widget-ready theme? =
Edit sidebar.php in your theme files, and place this code where you need:
If your theme supports widgets, you can place the widget named 'Popular Posts' where you want.

If it doesn't, put this code inside the file sidebar.php, in your theme files:

`<?php if (function_exists('WPPP_show_popular_posts')) WPPP_show_popular_posts(); ?>`

Optionally you can add these parameters to the function:
`WPPP_show_popular_posts(title,number,days);`

title: Title of the widget
number: number of links shown
days: length of the time frame of the stats
