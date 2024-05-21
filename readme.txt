=== OM4 Custom CSS ===
Tags: custom css, css, css editor
Requires at least: 5.3
Tested up to: 6.5
Stable tag: 1.7
Contributors: jamescollins
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Write custom CSS rules using and easy to use interface in the WordPress dashboard. No theme editing or child theme required!

== Description ==

Write custom CSS rules using and easy to use interface in the WordPress dashboard. No theme editing or child theme required!

SCSS/SASS syntax (such as variables) can also be used.

Also features a one-click CSS validation button.

The Custom CSS rules are usually output the last thing output before </head>, making it easy to override default CSS rules from themes and plugins.

Note: requires PHP v7.4 or newer.

== Installation ==

1. Activate the plugin.
1. Go to Appearance, Custom CSS and write some CSS rules!
1. Save your CSS rules using the one of the convenient keyboard shortcuts Mac: Cmd+Enter or Cmd+Shift+S, or PC: Ctrl+Enter or Ctrl+Shift+S
1. Validate your CSS rules using the one-click CSS validator button.

== Changelog ==

= 1.7 =
* Change CSS editor to Darcula theme.
* Add support for PHP 7.4.
* Update CodeMirror library and SCSSPHP libraries to their latest versions.
* Add tests.

= 1.6 =
* Add support for PHP 7.2.
* Update CodeMirror library and SCSSPHP libraries to their latest versions.

= 1.5.2 =
* Improved visual feedback when saving Custom CSS rules. The editor's background colour now turns grey while saving.

= 1.5.1 =
* Save the CSS rules using the Cmd+Shift+S (or Ctrl+Shift+S) keyboard shortcut. The Cmd+Enter (or Ctrl+Enter) shortcut still works too.
* CSS Editor: update before saving via a keyboard shortcut.

= 1.5 =
* Add support for SCSS/SASS.
* Save rules via AJAX, which speeds up development.
* Save the CSS rules using the Cmd+Enter (or Ctrl+Enter) keyboard shortcut.
* Add translation support.
* Note: requires PHP v5.4 or newer.

= 1.4 =
* If using the Beaver Builder WordPress theme, ensure OM4 CSS rules load after the theme's Custom CSS rules.

= 1.3 =
* CSS Editor: update editor to latest version.

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
