=== Options Definitely ===

Plugin Name:       Options Definitely
Plugin URI:        http://wordpress.org/plugins/options-definitely/
Author URI:        http://leaves-and-love.net
Author:            Felix Arntz
Donate link:       http://leaves-and-love.net/wordpress-plugins/
Contributors:      flixos90
Requires at least: 4.0 
Tested up to:      4.2
Stable tag:        0.5.0
Version:           0.5.0
License:           GPL v2 
License URI:       http://www.gnu.org/licenses/gpl-2.0.html
Tags:              wordpress, plugin, framework, library, developer, options, admin, backend, ui

This framework plugin makes adding options to the WordPress admin area very simple, yet flexible. It all works using a single action and an array.

== Description ==

Options Definitely is a framework for developers that allows them to easily add options and their input fields to the WordPress admin so that a user can manage them. You can add new menus and options pages, add fields to those pages and organize them in multiple tabs and settings sections. Furthermore the fields have a validation mechanism, so you can specify what the user is allowed to enter and print out custom error messages.
The plugin comes with several common field types and validation functions included, including repeatable fields, where you can group a few fields together and allow the user to add more and more of them. If you need another field type or validation function, you can create your own callback and handle it there.
Another feature of Options Definitely is that you can easily display your settings sections as meta boxes, making them flexible for the user to move or hide.
In a future version, the plugin will also work with the WordPress Customizer.

= Usage =

Options Definitely is very easy to use. You have two choices of how you would like to add your options:
* either you add everything in a multidimensional associative array, hooking into the filter `wpod`
* or, if you prefer the object-oriented method, you can hook into the action 'wpod_oo' to access the framework functions directly

Both ways are fully compatible with each other, meaning you can choose whatever you prefer without conflicting with other plugins/themes using this framework. The basic difference between the two methods is that, when using the filter and array, you have a lot less code to write, on the other hand, the object-oriented approach also gives you the possibility to adjust or even delete components (in Options Definitely, when we speak about a component, we mean either a menu, a page, a tab, a section or a field) that someone else has previously added.

For a detailed guide and reference on how to use this framework, please read the [Wiki on Github](https://github.com/felixarntz/options-definitely/wiki). Once you get familiar with the options you have, you will be able to create complex options interfaces in just a few minutes.

> <strong>This plugin is a framework.</strong><br>
> When you activate the plugin, it will not change anything visible in your WordPress site. The plugin is a framework to make things easier for developers.
> In order to benefit by this framework, you or your developer should use its functionality to do what the framework is supposed to help with.

== Installation ==

1. Upload the entire `options-definitely` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Add all the options you like, for example in your plugin or theme.

== Frequently Asked Questions ==

= How do I use the plugin? =

You can use the framework anywhere you like, for example in your theme's functions.php or somewhere in your own plugin or must-use plugin. For a detailed guide and reference on how to use this framework, please read the [Wiki on Github](https://github.com/felixarntz/options-definitely/wiki).

= Why don't I see any change after having activated the plugin? =

Options Definitely is a framework plugin which means it does nothing on its own, it just helps other developers getting things done more quickly.

= Where should I submit my support request? =

I preferably take support requests as [issues on Github](https://github.com/felixarntz/options-definitely/issues), so I would appreciate if you created an issue for your request there. However, if you don't have an account there and do not want to sign up, you can of course use the [wordpress.org support forums](https://wordpress.org/support/plugin/options-definitely) as well.

= How can I contribute to the plugin? =

If you're a developer and you have some ideas to improve the plugin or to solve a bug, feel free to raise an issue or submit a pull request in the [Github repository for the plugin](https://github.com/felixarntz/options-definitely).

== Screenshots ==

1. an options page created with the plugin
2. PHP code to create the options page above using the array filter method
3. PHP code to create the options page above using the object-oriented action method

== Changelog ==

= 0.5.0 =
* First stable version

== Upgrade Notice ==

The current version of Options Definitely requires WordPress 4.0 or higher.

== Future ==

In a future version (before the 1.0.0 release), the plugin will also work with the WordPress Customizer (which will be especially useful for themes), in the same way it works now. You will be able to add your sections and fields in there, tabs can be added as panels.

If you want to be prepared before it comes out, you can already add `'customizer' => true` to each tab, section or field that you would like to show up in Customizer.
