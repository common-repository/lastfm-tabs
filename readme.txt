=== Last.fm Tabs ===
Contributors: Felix Moche
Tags: Last.fm, Tabs, jQuery, widget
Requires at least: 2.9.2
Tested up to: 3.3.1
Stable tag: 2.0.2

Displays Last.fm cover art, friends, charts, shouts, loved tracks and user info.

== Description ==

This plugin allows you to easily display your last.fm info on your blog. There are currently 7 different things to display: cover art of your latest tracks, your friends, shouts on your profile page, coverart of your loved tracks, your user info, your most listened to albums and most listened to artists. All of these can independently be disabled or enabled.

Furthermore the plugin includes support of Sean Catchpole's idTabs jQuery plugin which displays all the information in some nice looking jQuery tabs and some stylish hover effect for descriptions.
See http://www.sunsean.com/idTabs/ and http://buildinternet.com/project/mosaic/ for further information.

Please let me know if there are any problems or feature requests.

== Installation ==

1. Unpack downloaded zip-file
2. Upload the folder 'lastfm-tabs' to your '/wp-content/plugins/' directory
3. Activate the plugin through the 'Plugins' menu in WordPress
4. Configure your options in 'Settings -> Last.fm options'
5. Place the widget in your sidebar, use the shortcode or modify your template.

== Frequently Asked Questions ==

= How does the shortcode work? =

[lastfm tab="albums, friends, charts, artists, info, recent, loved, shouts" count="3" titles="true"]

You can use the following functions: albums, friends, charts, artists, info, recent, loved und shouts.
You may combine two or more of these functions with commas.
The title-parameter enables/disables output of the corresponding title for each function. Title and count are optional. If not provided, the plugin options will be used.

= Should I use the provided css-file? =

The css file is just very simple and probably not optimal for your blog.
Also the file will set a fixed image with and height so the image size options in the config menu will be obsolete.
Use the file for 'large' images and if you want to use the hover-effect, otherwise better build your own.
In that case create your own CSS file with the name lastfm.css in your template directory to overwrite the default one
or deactivate the option and put your CSS rules in your theme's style.css.

= Why is the cover size always the same? =

You probably use the provided css file. It will set with and height to 126px.

= Can I use the hover effect with bigger images? =

Sure you can - but you'll have to build your own css file.

= Why are my recently played tracks always the same? =

All the information on your Last.fm-Sidebar is cached for the time you set in the plugin options.
When you clear the plugin's cache, the last track you listened to should be displayed.

= Why does my blog load so slow after installing the plugin? =

Try to lower the count of displayed entries. Especially the charts will cause a quite big request to Last.fm because it will query all the albums you listened to and not just the amount you configured (blame Last.fm).
All the other options will just query the amount of entries you specified.
You could also increase the cache expiration.

== Screenshots ==

1. Options (Wordpress 3.2.1)
2. In action (@Twenty Eleven)

== Translations ==

A german translation is included. If you want to translate this plugin in your own language, feel free to use the provided .po-file.

== Changelog ==

= 2.0.2 =
* added French translation, thanks to MerMouY

= 2.0.1 =
* added Romanian translation, thanks to Web Geek Science (http://webhostinggeeks.com)
* increased default cache expiration

= 2.0 =
* new version of mosaic js plugin
* error handling
* new options and new options menu
* minor fixes
* template tag
* shortcode
* re-organised code and files

= 1.3 =
* added top albums, top artists and weekly charts display
* fixed jquery including

= 1.2 =
* fixed Javascript
* full WP3 compatibility
* added settings link in plugins directory

= 1.1.2 =
* added limit for artist images, so it should load a lot faster

= 1.1.1 =
* Small syntax fix
* check if artist image exists before displaying it

= 1.1 =
* Added option to show artist image instead of default cover for not found album art
* Added preview for default cover

= 1.0 =
* Initial release