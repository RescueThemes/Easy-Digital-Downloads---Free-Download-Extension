=== Easy Digital Downloads - Free Download ===
Contributors: vafpress
Donate link: http://vafpress.com
Tags: easy digital downloads extension, digital free download, freebies download, vafpress
Requires at least: 3.3
Tested up to: 3.5.1
Stable tag: 0.2.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Easy Digital Downloads extension for easier free download or freebies sharing.

== Description ==

This is extension tested up to Easy Digital Downloads 1.5. EDD - Free Download will override the original purchase button for every download product that has price of 0 (free), to a direct download link, bypassing normal shopping cart mechanism, but still maintaining awesome features of EDD, such as download logs report, and requiring user to register before download.

Upon activation, the extension will create 3 pages:

* File list page
* User login and registration page
* Download gateway page

When a user click on download url, the extension will first check, if the download has more than 1 files, then user will be first redirected to file list page, if there is only 1 file, user will be brought directly to download gateway page, in this page the download will be started after some certain time that you can specify via settings, the benefit is you can show any message or promoting your premium product at this page.

All pages are hidden in the front end, but fully editable via wordpress admin.

If you check in must login option, user will be checked whether they are login or not in every step, and will be redirected to login and registration page if they aren't logged in, and they will be redirected back exactly to their previous url before loggin on or registering.

And this plugin is compatible with easy digital downloads mail chimp extension.

== Installation ==

1. Upload plugin package file to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Configure plugin under Easy Digital Downloads settings page -> Misc

== Frequently Asked Questions ==

= Why is this page blank? =

No one asked anything yet :p

== Screenshots ==

1. All the things that you can customize via settings page.

== Changelog ==

= 0.2.0
* Fix download limit being ignored

= 0.1.9
* Move everything to a subfolder to prevent weird issue
* Added %name% wildcard to add download name to button

= 0.1.8
* Update 1 line of outdated code in download process function.

= 0.1.7 =
* Fix download issue with EDD 1.5.2.2

= 0.1.6 =
* Forgot to cleanup a test code

= 0.1.5 =
* Fix silly error when renaming function name

= 0.1.4 =
* Changed and added 2 action 'vp_edd_fd_before_member' and 'vp_edd_fd_after_member'

= 0.1.3 =
* Fix stupid way of getting page id

= 0.1.2 =
* Fix weird bug in WP multisite, when using settings.php as a filename
* Fix php error notice when a download doesn't has any file

= 0.1.1 =
* Fix compatibility issue with EDD 1.5

= 0.1 =
* Initial version

== Upgrade Notice ==

Not yet