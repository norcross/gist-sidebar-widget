=== GitHub Gists Sidebar Widget ===
Contributors: norcross
Donate link: http://andrewnorcross.com/donate
Tags: github, gist, widget
Requires at least: 3.0
Tested up to: 3.3
Stable tag: 1.1

A sidebar widget to display your public gists from GitHub.

== Description ==
A sidebar widget to display your public gists from GitHub. Allows for optional display of creation date and link back to your GitHub profile page. Uses v3 of the GitHub API.

== Installation ==

This section describes how to install the plugin and get it working.

1. Upload the 'gist-sidebar-widget' folder to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Drag the widget to your sidebar and input your username, number of gists, and options for displaying creation date and a link back to your GitHub profile.

== Frequently Asked Questions ==

= What does this do? =

It displays your public gists from GitHub on the sidebar, linking to each of them on GitHub.

= They look kind of bland. Can I style them? =

Sure can. There are four classes to look at to add to you CSS.

* Each list item `<li>` has the class `gist_item`
	
* Each link within the `<li>` has the class `gist_link`
	
* The optional creation date is wrapped in a `<span>` with the class `gist_date`

* The optional link to your GitHub profile is in a `<p>` with the class `github_link`


= Is that it? =

Pretty much, yeah.

== Screenshots ==

1. The plugin options displayed within the widget.


== Changelog ==

= 1.1 =
* Added username to transient variable to allow multiple widget use.

= 1.0 =
* Initial release


== Potential Enhancements ==
I plan on including the Transients API in an upcoming release...once I figure out exactly how they work. Otherwise, feel free to suggest.


