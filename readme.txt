=== Plugin Name ===
Contributors: wpuser0815
Tags: search engine, widget, seo, piwik
Requires at least: 2.9.2
Tested up to: 3.3.2
Stable tag: 0.4.0


Displays incoming queries from search engines like Google. The queries data come from a Piwik-database.


== Description ==

This Plugin displays last incoming queries from search engines in the sidebar or an area in the post.
The queries data come from a Piwik-database. You need a Piwik installation for this plugin.

= Important Links: =
<a href="http://piwik.org/" title="piwik page">Get Piwik</a>


== Changelog ==

= 0.4.0 =
* New: Piwik request via Authentication-Code (your Piwik-page -> API -> &token_auth=CODE)

= 0.3.4 =
* Fixed: code injection problem

= 0.3.3 =
# Changed: Database connection (please edit the piwik path)

= 0.3.1 = 
* Fixed: problems with UTF-8 (new option in settings area)

= 0.3.0 =
* New: english language (ready for other languages)
* New: option for set a keyword separator
* Fixed: not correct path in settings

= 0.2.6 =
* New: show keywords as links

= 0.2.1 =
* first public version

== Installation ==

1. Upload piwik-search-engine-keywords directory to the /wp-content/plugins/ directory.
2. Activate the plugin in WordPress.
3. Edit the path to Piwik. Example: "www.your-webspace.com/piwik/".
4. Set the token_auth from your Piwik-Page (your Piwik-page -> API -> &token_auth=...).
5. Set the ID of your Website. (Mostly '1').
6. Activate widget or output in the post.


== Frequently Asked Questions ==

= How I can change the output layout? =

You can edit style.css file in the piwik-search-engine-keyword directory.

= I have problems with character endcoding =

Try the option for UTF-8 database request.


== Screenshots ==

1. Administration panel
