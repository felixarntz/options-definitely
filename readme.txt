=== Options Definitely ===

Plugin Name:       Options Definitely
Plugin URI:        https://wordpress.org/plugins/options-definitely/
Author:            Felix Arntz
Author URI:        https://leaves-and-love.net
Contributors:      flixos90
Donate link:       https://leaves-and-love.net/wordpress-plugins/
Requires at least: 4.0
Tested up to:      4.7
Stable tag:        0.6.7
Version:           0.6.7
License:           GNU General Public License v3
License URI:       http://www.gnu.org/licenses/gpl-3.0.html
Tags:              definitely, framework, admin, options, settings, settings screen, tabs, sections, metaboxes

This framework plugin makes adding options screens with sections and fields to WordPress very simple, yet flexible.

== Description ==

_Options Definitely_ is a framework for developers that allows them to easily add options and their input fields to the WordPress admin so that a user can manage them. You can add new menus and options pages, add fields to those pages and organize them in multiple tabs and settings sections.

The plugin belongs to the group of _Definitely_ plugins which aim at making adding backend components in WordPress easier and more standardized for developers. All _Definitely_ plugins bundle a custom library that handles functionality which is shared across all these plugins, for example handling the field types and their controls.

The library comes with several common field types and validation functions included, including repeatable fields, where you can group a few fields together and allow the user to add more and more of them. All the fields have a validation mechanism, so you can specify what the user is allowed to enter and print out custom error messages.

For an extensive list of features, please visit the [Features page in the _Options Definitely_ Wiki](https://github.com/felixarntz/options-definitely/wiki/Features).

> <strong>This plugin is a framework.</strong><br>
> When you activate the plugin, it will not change anything visible in your WordPress site. The plugin is a framework to make things easier for developers.<br>
> In order to benefit by this framework, you or your developer should use its functionality to do what the framework is supposed to help with.

= Usage =

_Options Definitely_ is very easy to use. Although you need to be able to write some PHP code to use the library, setting up settings pages with tabs, sections and fields should be quite straightforward. All you need to know is:

* how to hook into a WordPress action
* how to call a single class function
* how to handle an array

For a detailed guide and reference on how to use this framework, please read the [Wiki on Github](https://github.com/felixarntz/options-definitely/wiki). Once you get familiar with the options you have, you will be able to create complex options interfaces in just a few minutes.

**Note:** This plugin requires PHP 5.3.0 at least.

> _Options Definitely_ is just one among a group of _Definitely_ plugins which allow developers to build their admin interfaces more quickly. You might also wanna check out:<br>
> - [Post Types Definitely](https://wordpress.org/plugins/post-types-definitely/)

== Installation ==

1. Upload the entire `options-definitely` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Add all the options you like, for example in your plugin or theme.

== Frequently Asked Questions ==

= How do I use the plugin? =

You can use the framework anywhere you like, for example in your theme's functions.php or somewhere in your own plugin or must-use plugin. For a detailed guide and reference on how to use this framework, please read the [Wiki on Github](https://github.com/felixarntz/options-definitely/wiki).

= Why don't I see any change after having activated the plugin? =

Options Definitely is a framework plugin which means it does nothing on its own, it just helps other developers getting things done way more quickly.

= Where should I submit my support request? =

I preferably take support requests as [issues on Github](https://github.com/felixarntz/options-definitely/issues), so I would appreciate if you created an issue for your request there. However, if you don't have an account there and do not want to sign up, you can of course use the [wordpress.org support forums](https://wordpress.org/support/plugin/options-definitely) as well.

= How can I contribute to the plugin? =

If you're a developer and you have some ideas to improve the plugin or to solve a bug, feel free to raise an issue or submit a pull request in the [Github repository for the plugin](https://github.com/felixarntz/options-definitely).

You can also contribute to the plugin by translating it. Simply visit [translate.wordpress.org](https://translate.wordpress.org/projects/wp-plugins/options-definitely) to get started. Note that you can help not only translating the plugin, but also the underlying library [_WPDLib_](https://github.com/felixarntz/wpdlib).

== Screenshots ==

1. an options page created with the plugin
2. PHP code to create the options page above

== Changelog ==

= 0.6.7 =
* Enhanced: on WordPress 4.7 settings are now registered globally using the enhanced `register_meta()` implementation
* Fixed: datetime and time fields no longer switch the time back by an hour automatically
* Fixed: in datetime and date fields it is no longer possible to accidentally change the month by scrolling
* Fixed: datetime and date fields better support locale special characters

= 0.6.6 =
* Fixed: select fields no longer show twice when a placeholder is specified

= 0.6.5 =
* Tweaked: updated the plugin initialization library
* Tweaked: updated the Select2 and Datetimepicker JavaScript plugins
* Fixed: dropdowns no longer close immediately after opening them

= 0.6.4 =
* Fixed: WPDLib now has type "wordpress-muplugin" so that it is not loaded outside of a project

= 0.6.3 =
* Fixed: uncaught JavaScript error with datetimepicker

= 0.6.2 =
* Enhanced: WYSIWYG field experience is now more similar to the default post editor, including media and link buttons
* Tweaked: updated the Select2 and Datetimepicker JavaScript plugins
* Fixed: the selected value of a multiselect field is now properly displayed
* Fixed: the `rows` attribute now works correctly for textareas and WYSIWYG
* Fixed: updated the media picker JavaScript plugin for more flexibility and some bug fixes
* Fixed: updated the map picker JavaScript plugin for more flexibility and some bug fixes

= 0.6.1 =
* Tweaked: plugin now uses wordpress.org language packs
* Tweaked: updated plugin initialization library
* Fixed: "Settings saved" message does not show twice anymore on an options screen
* Fixed: admin notice no longer shows on each site when the plugin is activated network-wide
* Fixed: numeric validation of floating point numbers
* Fixed: formatting floating point numbers more precise than 2 digits

= 0.6.0 =
* Added: a new action is available to easily enqueue scripts on specific settings pages only
* Added: new field type map (can store either address or latitude and longitude)
* Enhanced: the plugin can now easily be used as a must-use plugin or as a library in any plugin or theme
* Enhanced: media field type can now alternatively store URL instead of attachment ID
* Enhanced: options keys 'terms' and 'users' (for related objects) now accept 'any' as value (similar to 'posts')
* Fixed: critical validation bug in WYSIWYG
* Fixed: step validation for decimal numbers
* Fixed: images now display correctly in media preview

= 0.5.1 =
* Fixed: critical issue in `wpod_get_options()`
* Fixed: on PHP 5.2 the plugin now terminates appropriately

= 0.5.0 =
* First stable version
