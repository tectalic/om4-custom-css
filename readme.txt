=== OM4 Custom CSS ===
Tags: custom css, css, css editor
Requires at least: 4.0
Tested up to: 4.6
Stable tag: 1.2
Contributors: jamescollins
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Write custom CSS rules using and easy to use interface in the WordPress dashboard.

== Description ==

Custom CSS rules can be written and edited using an easy to use WordPress dashboard interface. No file editing required!

Uses CSS syntax highlighting to help you write your CSS rules.

Also features a one-click CSS validation button.

The Custom CSS rules are usually output the last thing output before </head>, making it easy to override default CSS rules from themes and plugins.

== Installation ==

1. Activate the plugin.
1. Go to Appearance, Custom CSS and write some CSS rules!

== Changelog ==

= 1.2 =
* CSS Editor: wrap long lines so there is no need to scroll horizontally.
* CSS Editor: update editor to latest version.

= 1.1 =
* CSS syntax highlighting.
* Fix CSS validate buttons (they now open in a new window because the CSS validator can no longer be embedded in an iframe thickbox).

= 1.0.8 =
* Security enhancement for add_query_arg usage.

= 1.0.7 =
* Store previous CSS files for up to a day. This avoids issues where static caches (such as the ones used on WP Engine) refer to old CSS files that no longer exist.

= 1.0.6 =
* Allow other plugins to perform actions whenever the Custom CSS rules are saved.
* No longer flush the caches in this plugin. Instead, it will be done via the OM4 Service plugin.

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

== Upgrade Notice ==

= 1.2 =
* CSS Editor: wrap long lines so there is no need to scroll horizontally.