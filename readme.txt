=== OM4 Custom CSS ===
Tags: custom css, css
Requires at least: 3.7
Tested up to: 3.8.1
Stable tag: 1.0.5
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Write custom CSS rules using and easy to use interface in the WordPress dashboard.

== Description ==

Custom CSS rules can be written and edited using an easy to use WordPress dashboard interface. No file editing required!

Also features a one-click CSS validation button.

The Custom CSS rules are usually output the last thing output before </head>, making it easy to override default CSS rules from themes and plugins.

== Installation ==

1. Activate the plugin.
1. Go to Appearance, Custom CSS and write some CSS rules!

== Changelog ==

= 1.0.5 =
* Automatically redirect requests for old/previous custom CSS files. Helps prevent issues with cached pages referring to a previous Custom CSS file that no longer exists. For example, on WPengine or a site using another page caching setup.
* Automatically purge WP Engine's cache when Custom CSS rules are saved.
* Add readme.

= 1.0.4 =
* Use https:// for the custom css file if the current page is being loaded via https.

= 1.0.3 =
* Automatically flush W3 Total Cache's page cache when Custom CSS rules are saved.

= 1.0.2 =
* Code improvements to more closely match the WordPress coding standards.

= 1.0.1 =
* Only output <link> tag if some custom css rules have been added.

= 1.0.0 =
* Initial release.