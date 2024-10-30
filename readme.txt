=== Easy Cache ===
Contributors: manojtd
Donate link: http://buy.thulasidas.com/easy-cache
Tags:  cache, caching, fast cache, static pages, speed, mobile, performance
Requires at least: 4.2
Tested up to: 4.8
Stable tag: 1.60
License: GPL2 or later

Easy Cache (formerly Mobile Cache) is a high-performance, yet simple-to-use caching plugin. It serves static pages, optimized for the target device.

== Description ==

Most caching plugins focus on techniques appropriate for the desktop era, trying to minimize the server load and thereby increasing performance. But the that era is behind us now, with mobile devices accounting for more than half of the web traffic. While the legacy caching plugins do handle mobile devices as an afterthought, *Easy Cache* is the only plugin designed specifically for mobile devices. These mobile devices have bandwidth and data cost/usage requirements that can have significant readership impact, especially in emerging markets. Optimizing your server to handle traffic spikes is not the same as serving highly mobile-aware blog pages. Given that mobile browsing is now well past the tipping point of 50%, we can no longer ignore the specific requirements of mobile devices. *Easy Cache* is an attempt to address this issue.

*Easy Cache* is designed to be simple. It has a single-page admin screen (even in the Pro version) with a few simple, easy-to-understand options, with sensible default values. The moment you activate the plugin, it starts serving minified static files and compressed images to your mobile readers. The default options are good for a vast majority of blogs out there. If you would like to change an option, hovering over it will give you a comprehensive description of what it does. Designed to run unattended, *Easy Cache* invalidates the cached entity whenever there is a source change, and warms up slowly without overloading your server as your readers visit various pages. When it cannot detect the type of device, it fails gracefully and serves a dynamic page.

Although originally designed for mobile devices (as Mobile Cache), *Easy Cache* can now serve minified compressed static resources to desktop browsers as well, from an independent cache location. *Easy Cache* now is a complete caching solution with impressive performance and remarkably simple configuration.

= Features =

1. Mobile Specificity: Designed for smart phones and tablets, serving from an independent, optimized cache when browsed from a mobile device.
2. Desktop Option: If you would like to it to serve to desktop browsers, *Easy Cache* can do it as well, caching minified compressed static resources to an independent location specifically for desktop clients.
3. Simplicity: Just activate the plugin and forget about it. The default settings will work with most WordPress blogs out there, and are hyper-optimized to work with no intervention from you. If you want to change any default, you will find a friendly, single-page admin page with clear, detailed help and background information.
4. User Friendliness: No more admin messages about editing your `.htaccess`, `wp-config.php`, setting permissions, executing commands via ftp, update warnings etc. *Easy Cache* just works, quietly and efficiently without getting in your way.
5. Unified Functionality: What this plugin does (image compression, resource minification, static cache) can be accomplished by using a few other plugins in tandem. *Easy Cache* just brings them in a unified plugin, with the added guarantee that your source files are never modified.
6. Source Sanctity: *Easy Cache* never deletes or overwrites your existing content like your images, JavaScript and style resources. And it doesn't require you to modify your `.htaccess`, `wp-config.php` or add extra php files in anywhere. All the optimization is done in a separate cache folder outside WordPress workflow so that if you deactivate or temporarily pause the plugin, your source files and blog processing are left in their original, pristine condition.
7. Graceful Failures: In the unlikely event that the plugin cannot create cached resources, it fails quickly and gracefully to let WordPress (and other caching plugins) serve your blog pages.
8. Intelligent Invalidations: *Easy Cache* excels in ensuring that your readers get the latest content. It removes the stale content using a variety of intelligent hooks.

= Pro Version =

In addition to the fully functional Lite version, *Easy Cache*  also has a [Pro version](http://buy.thulasidas.com/easy-cache "Easy Cache -- smart caching for mobile devices, $13.95"), which brings the same wickedly optimized caching techniques to desktop browsers as well. Moreover, you can have two separate caches for phones and tablets (in addition to the desktop cache), customized to their sizes. The Pro version also adds a whole host of other features:

1. Independent caching for tablets, smart phones and desktop readers so that the image resizing is optimized to the target browsers.
2. Use of RAM (where available) for blazingly fast cache. You just need to specify the RAM disk location, and Easy Cache will take care of the rest with an absolute minimum of fuss.
3. In-memory caching option where RAM disk is not available.
4. Full support for Content Delivery Networks (CDN). Supports load-balancing over multiple CDN servers.
5. Test mode to serve cached versions to the admin user to verify and benchmark caching.
6. Ability to specify individual file types to be excluded from caching.
7. Optionally cache RSS feeds.
8. Ability to delete cached entities per device type (smart phones, tables or desktop browsers) or en masse.
9. Ability to pre-populate the cache so that *Easy Cache* behaves like a warm cache. The pre-cache process happens in the background without affecting your workflow, or overloading your server.
10. Comprehensive help to guide you through in migrating from other popular caching plugins, and on apache/nginx or hybrid servers.
11. Integrated CDN cache busting options for easily refreshing off-site content.
12. Ability to use TinyPNG for compression for state-of-the-art image compression. [Work in progress]

The Pro version purchased now will have free updates until all these features are fully functional.

== Upgrade Notice ==

Compatibility with WP4.8. Sunset edition.

== Screenshots ==

1. *Easy Cache* admin page, showing the simple, single-page, easy-to-understand settings.
2. *Easy Cache* Pro features, showing independent caching by target type (smart phones, tablets, desktop browsers) and CDN and RAM disk support.
3. My blog performance with no caching.
4. My blog with a fully-configured *Easy Cache* set up.

== Installation ==

To install Easy Cache, please use the plugin installation interface.

1. Search for the plugin *Easy Cache* from your admin menu Plugins -> Add New.
2. Click on install.

It can also be installed from a downloaded zip archive.

1. Go to your admin menu Plugins -> Add New, and click on "Upload Plugin" near the top.
2. Browse for the zip file and click on upload.

Once uploaded and activated, the plugin immediately starts caching pages for visitors from mobile browsers. Visit the *Easy Cache* plugin admin page to check and modify any options, if you need to.

== Frequently Asked Questions ==

= What does this plugin do? =

Here is what *Easy Cache* does when a reader visits one of your blog posts or pages from a mobile device for the first time:

1. It generates the page and removes all the extra white spaces and comments that can be safely removed (minifies the HTML).
2. It rewrites all the local links in the page (that can be safely rewritten) so that they point to their cached versions.
3. It copies a static copy of the minified HTML to the cache.
4. It also minifies all the JavaScript and CSS files that are requested in the page visit and copies them to the cache.
5. It creates smaller versions of the images in the page and copies them to the cache as well.

The next visit to the same page from a mobile device becomes lightning fast because it hits the cached version of the page and all assets (which are minified and compressed).

Now in V1.10+, the same caching techniques can be used for desktop browsers as well. *Easy Cache* can now serve minified compressed static resources to desktop browsers from an independent cache location.

= How does *Easy Cache* know when to invalidate a cached entity? =

*Easy Cache* invalidates (deletes) stale entries using extremely intelligent algorithms.

1. When anything changes in a blog post or page (edit, comments, status etc.), *Easy Cache* deletes the cached versions of all the associated archive pages (the category and tag pages that the post figures in) and the main blog index page, in addition to the post/page itself.
2. Before serving a minified asset (js or css file) or a compressed image from the cache, *Easy Cache* checks its source. If the source has changed, it re-caches it (after minification and compression, of course) while serving the original.
3. If you switch your theme, *Easy Cache* will clear all cached resources.
4. If you change your permalink structure, *Easy Cache* will clear all cached resources.

This is why you will never have to clear the cache ever again. *Easy Cache* is smart enough to do it on its own.

= I activated the plugin, and visited my blog. I don't see any difference. Is it working? =

By default, *Easy Cache* doesn't serve cached files to logged in users. If you want to see the effect of caching, please log out and try again. After that, visit any speed test website such [Pingdom](http://tools.pingdom.com/fpt/ "Pingdom Website Speed Test") with and without the plugin active.

= I haven't given the plugin permission to write stuff on my server. How does create a cache? =

To create the compressed and minified cache files, *Easy Cache* uses the same class that WordPress uses to update itself and your plugins. So if WordPress can run updates, *Easy Cache* can create files -- securely and with no exposed attack vectors.

== Change Log ==

* V1.60: Compatibility with WP4.8. Sunset edition. [Aug 1, 2017]
* V1.51: Minor interface and documentation changes. [Feb 25, 2016]
* V1.50: Deprecating translation interface in favor of Google translation. [Feb 23, 2016]
* V1.41: More screenshots. [Dec 11, 2015]
* V1.40: Compatibility with WordPress 4.4. Numerous fixes and improvements. [Dec 5, 2015]
* V1.31: RAM disk feature released. [Nov 26, 2015]
* V1.30: Numerous fixes and enhancements. [Nov 22, 2015]
* V1.21: Bug fixes. Minor feature enhancements. [Nov 17, 2015]
* V1.20: Renaming the plugin to Easy Cache. Adding more features. [Nov 14, 2015]
* V1.10: Adding separate caching for desktop browsers. Improved HTML minification. [Nov 10, 2015]
* V1.02: Temporarily turning of HTML minification (too aggressive with white spaces) [Nov 8, 2015]
* V1.01: Refactoring changes. [Nov 7, 2015]
* V1.00: Initial release. [Nov 7, 2015]
