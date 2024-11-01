=== Risal Download ===
Contributors: Risal Affendie, Lars Kettermen
Donate link: http://linuxuserjp.wordpress.com/donate
Tags: abuse, download, session, browser, encrypt, security, secure, apache, linuxuserjp, risal, token, anti leech
Requires at least: 3.0.0
Tested up to: 3.3.1
Stable Tag: 1.6

risal-download is a simple scripts to avoid download abuse(either by human or robot).

== Description ==

= THE PURPOSE =

This plugin created to avoid unethical download activity by user. It prohibit user to use download manager, changing between browsers or sessions and such act consider as abuse. Never try on windows server though but working well on ubuntu. Let me know if it's work on other OS.

= WHY USE IT? =

It is a bad idea to locate any download-able files inside Apache's root directory where it is directly and freely accessible by Internet users. What I worried about is apache user can't read outside of it's root directory (mostly outside /var/www). I don't know, it's depend on OS distribution. My suggestion, you may change chmod of target directory. By the way don't forget to notice visitors to disable pop-up blocker for your site.

Please feel free to visit below's url for more information and leave some comment to help me improve the script.

`http://linuxuserjp.wordpress.com`

Salam. May God Guide and Bless you.

== Installation == 
1. Upload the 'wp-risal-download' folder to the '/wp-content/plugins/' directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Go to Plugins -> Risal Configuration and start registering your download-able files. 
(Better to use files locate at outside Apache's root directory as records, but 
accessible/readable by apache's user)

== Frequently Asked Questions ==
= I have already registering the files, so how it start working? =
Use shortcode [risal id=x file=xxxx] at post or page (using visual or html editor) in order to use it.

= What is value for 'id' and 'file'? = 
id refers id's number in the download-able record, and file is whatever name you decide to use. 
It's not depend on filename in database's record.

= Why nothing happened when I clicked the provided link? =
Provided link (by shortcode) will using pop-up, therefore visitor must disable pop-up blocker for your website.

= What browsers this script support? =
So far this scripts wrote for Internet Explorer, Firefox, Arora, Google Chrome and Opera. You may suggest others for me to rewrite the scripts and make a new realease/version.

= Need any additional requirement other than wordpress? =
1. Your PHP's server must support build-in **mime_content_type** function.
2. Your PHP's server must support **GD** function, in case you want to use 'Captcha'.


== Screenshots ==
1. Admin area where files been recorded.
2. Admin area where download transaction been listed.
3. Using shortcode in Page/Post Editor.
4. Link in page/post that use shortcode.

== Changelog ==

= 1.0 =
* Work under linux filesystem. Windows? Not yet tested.

= 1.1 =
* Fix html syntax in admin page. File's name and directory's name that containing SPACE are now able to be recorded/updated/deleted from database.

= 1.2 =
* Replace input syntax in Record's Form with "text box" rather than "browse file". More practical for user who remote their server. Sorry, you got to type manually filename and it's path.

= 1.3 =
* Add button for file browsing.

= 1.4 =
* Put anti-leech ability. Avoid downlink been used twice. In other hand, provide unique download link for pop-up.
* Be carefull if you update from previous version. Script will drop all tables for this plugins.

= 1.5 =
* Fix error on 1.4. Where unable to delete expired tokens and some produce error when clicked button. Version 1.4 totally trash.

= 1.6 =
* Attached with anti-leech timing adjust.
* Attached with anti-spam ability (captcha).
* Additional character for token.
* Attached with non-popup ability (but less secure).
* Fix error on Chromium's browser.
* New css and admin page's view.

