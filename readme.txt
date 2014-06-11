=== Nexternal ===
Contributors: Nathan Smallcomb
Author URI: http://alreadysetup.com/nexternal
Tags: ecommerce, shopping, store, nexternal, sell
Requires at least: 2.8
Tested up to: 3.9.1
Stable tag: trunk

Allows you to publish products from your Nexternal store to your WordPress website.

== Description ==

This plugin allows you to easily publish products from your Nexternal store to your pages and posts. It uses Nexternal's API to get the latest product information including price, images and more. 

The plugin can display a single product, a product grid or a scrolling (horizontal or vertical) carousel of products.

We have a few other custom Nexternal focused plugins not available in the plugin catalog. Contact us if you are interested.

* [Plugin Homepage](http://alreadysetup.com/nexternal)
* Please visit the homepage and register yourself for updates. We require your feedback to help this plugin evolve.

= Why Is This Plugin Free? =

Think of it as your introduction to us. Already Set Up can help you with your WordPress site. From custom [__WordPress Coding__](http://alreadysetup.com/wordpress-websites/) to skilled [__Internet marketing__](http://alreadysetup.com/internet-marketing/), our services can help you gain an edge online. Thanks for your interest and enjoy the plugin!

Features:
--------
 * Connects to Nexternal API
 * Easily search your product catalog for products to publish
 * Publish a single product
 * Publish a product grid with x rows and y columns
 * Publish a single row or single column product carousel with x products and y visible concurrently
 * Apply custom CSS from within the plugin
 * Publish to a page or post (or even a widget)
 * Supports multiple instances per page
 * Uses shortcodes

Security:
--------
 * Securely communicates with Nexternal API using a key
 * Credentials used to retrieve/create the key are not stored


Requirements/Restrictions:
-------------------------
 * Works with WordPress 2.8, possibly older versions.


== Installation ==

1. If you are upgrading please be sure to backup your .css files from the plugin directory as they may be overwritten if named the same.

2. Install automatically through the `Plugins`, `Add New` menu in WordPress, or upload the `nexternal` folder to the `/wp-content/plugins/` directory. 

3. Activate the plugin through the `Plugins` menu in WordPress. 

4. From the dashboard connect to the Nexternal API and configure the plugin's default options using the Nexternal menu at lower left.

5. When connecting a user account, a Nexternal user of the type 'XML Tools', with the 'ProductQuery' option enabled should be used.

6. Click the Nexternal button on the post/page visual editor to begin publishing products.


== Screenshots ==

1. screenshot-1.png show the WordPress dashboard and the Nexternal settings button.

2. screenshot-2.png shows the Nexternal settings window which enables you to set the Global Configuration and the Default Options.

3. screenshot-3.png shows how to access the plugin from the post/page editor

4. screenshot-4.png is the plugin's main window during content editing.


== Frequently Asked Questions ==

[See the official FAQ at AlreadySetUp.com](http://AlreadySetUp.com/nexternal)

= I just installed this and cannot get it to accept my credentials =

Be sure you have authorized your account to access the API within the Nexternal OMS.  

= Do you offer support for this plugin =

Absolutely! For more help... [contact Already Set Up](http://alreadysetup.com/home/contact-us)

= Is this plugin available in other languages? =

No, sorry.

For more help... [See the official FAQ at AlreadySetUp.com](http://alreadysetup.com/nexternal)


== Changelog ==

= 1.4.2 =
- Fix for carousel jquery product ID support and box-model: border-box

= 1.4.1 =
- Fix for shortcode representation in pages

= 1.4 =
- Nexternal authentication updates
- updated plugin access (settings page, uninstall)
- separated plugin settings to multiple pages
- added basic instructions for connecting Nexternal account
- changed product identification from SKU to productNumber, allowing non-SKU product support
- altered inline editor height to avoid overlapping content

= 1.3 =
- updated syntax to avoid depreciation warnings (multiple files)
- updated enqueue_scripts call to use appropriate hook (nexternal.php)
- changed authentication method to user/pass instead of activeKey (all files)

= 1.2 =
- changed jquery and jquery ui references to current cdn references (window.php)

= 1.1.7b =
- stop curl from getting hung up on SSL certs from nexternal (nexternal-api curl_post)
- fixed bug where tinymce window wasnt able to find javascript file (window.php jquery-1.7.2.min.js)

= 1.1.6 =
- fixed jquery UI inclusion bug (another bug)

= 1.1.5 =
- Modified jquery instantiation

= 1.1.4 =
- Adjusted the add products functionality as the search was not updating

= 1.1.3 =
- Added custom link attributes mainly to enable Google analytics cross-domain tracking with onclick event

= 1.1.2 =
- Minor cosmetic

= 1.1.1 =
- Minor UI changes
- JQuery multiple instance fixes
- Added short description to query options

= 1.1 =
- Added multiple instance support
- Added product search box
- Added single product to CSS and drop down options

= 1.0 =
- Initial release




