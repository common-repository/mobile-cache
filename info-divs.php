<div id='details' style='clear:both;display:none;padding:10px;background-color:#ddf;font-size:1.1em;border:solid 1px #aac' class='info-div'>
  <p>Most caching plugins focus on techniques appropriate for the desktop era. But the that era is behind us now, with mobile devices accounting for more than half of the web traffic. While the legacy caching plugins do handle mobile devices as an afterthought, <strong>Easy Cache</strong> is the only plugin designed specifically for mobile devices, whose requirements are mainly smaller web page payloads.</p>
  <p><strong>Easy Cache</strong> is designed to be simple. It has a single-page admin screen (even in the Pro version) with a few simple, easy-to-understand options, with sensible default values. The moment you activate the plugin, it starts serving minified static files and compressed images to your mobile readers. The default options are good for a vast majority of blogs out there. If you would like to change an option, please hover over its label or input field to see a comprehensive description of what it does. Designed to run unattended, <strong>Easy Cache</strong> invalidates the cached entity whenever there is a source change, and warms up slowly without overloading your server as your readers visit various pages. When it cannot detect the type of device, it fails gracefully and serves a dynamic page.</p>
  <p>In the <a href="http://buy.thulasidas.com/easy-cache" title="Buy the Pro version of Easy Cache for $13.95. Instant download link." class="popup">Pro version</a> of this plugin, you can have use the same wickedly optimized caching algorithm to desktop browsers as well. Moreover, you can have two separate caches for phones and tablets, customized to their sizes. The Pro version also adds a whole host of features: Ability to use TinyPNG to optimize your images,  CDN (Content Delivery Networks) support, ability to pre-warm the whole cache in the background, cache statistics and so on.</p>
  <p><input type='button' value='Hide this Box' class='button-secondary details'/></p>
</div>
<div id='what' style='clear:both;display:none;padding:10px;background-color:#ddf;font-size:1.1em;border:solid 1px #aac' class='info-div'>
  <p>Here is what <strong>Easy Cache</strong> does when a reader visits one of your blog posts or pages from a mobile device for the first time:</p>
  <ol>
    <li>It generates the page and removes all the extra white spaces and comments that can be safely removed (minifies the HTML).</li>
    <li>It rewrites all the local links in the page (that can be safely rewritten) so that they point to their cached versions.</li>
    <li>It copies a static copy of the minified HTML to the cache.</li>
    <li>It also minifies all the JavaScript and CSS files that are requested in the page visit and copies them to the cache.</li>
    <li>It creates smaller versions of the images in the page and copies them to the cache as well.</li>
    <li>The next visit to the same page from a mobile device becomes lightning fast because it hits the cached version.</li>
  </ol>
  <p>
    This is the traditional cold cache approach where the cache slowly warms and becomes fully populated over time. The extra server load is minimal because what is cached is essentially a version of what is being served to the first mobile reader of the post or page.
  </p>
  <p>
    One crucial performance metric of a cache is how it can invalidate itself. How does it know when to update a cached file? <strong>Easy Cache</strong>, in fact, does it extremely intelligently.
  </p>
  <ol>
    <li>When anything changes in a blog post or page (edit, comments, status etc.), <strong>Easy Cache</strong> deletes the cached versions of all the associated archive pages (the category and tag pages that the post figures in) and the main blog index page, in addition to the post/page itself.</li>
    <li>Before serving a minified asset (<code>js</code> or <code>css</code> file) or a compressed image from the cache, <strong>Easy Cache</strong> checks its source. If the source has changed, it re-caches it (after minification and compression, of course) while serving the original.</li>
    <li>If you change your theme or permalink structure, <strong>Easy Cache</strong> flushes the cache in order to discard stale entities and to ensure that it will be recreated afresh.</li>
  </ol>
  <p>
    This is why you will never have to clear the cache ever again. <strong>Easy Cache</strong> is smart enough to do it on its own. But if you do want to clear the cached version of a post or a page, <strong>Easy Cache</strong> makes it a cinch -- just visit your <em>All Posts</em> or <em>All Pages</em> page from WordPress admin area, hover over the title, and you will see a link below it to Refresh Cache. Clicking on it will clear the cached version along with the associated caches of categories tags.
  </p>
  <p><input type='button' value='Hide this Box' class='button-secondary what'/></p>
</div>

<div id='why' style='clear:both;display:none;padding:10px;background-color:#ddf;font-size:1.1em;border:solid 1px #aac' class='info-div'>
  <p>Why another caching plugin? Because many of the popular caching plugins are not designed with smart phones and tablets in mind. These mobile devices have bandwidth and data cost/usage requirements that can have significant readership impact, especially in emerging markets. Optimizing your server to handle traffic spikes is not the same as serving highly mobile-aware blog pages. <strong>Easy Cache</strong> is the only plugin designed from ground up with mobile devices in mind.</p>
  <ol>
    <li>Mobile specificity: Designed for smart phones and tablets, and active only when browsed from a mobile device (in the lite version).</li>
    <li>Simplicity: Just activate the plugin and forget about it. The default settings will work with most WordPress blogs out there, and are hyper-optimized to work with no intervention from you.</li>
    <li>User Friendliness: No more admin messages about editing your <code>.htaccess</code>, <code>wp-config.php</code>, setting permissions, executing commands via ftp, update warnings etc. <strong>Easy Cache</strong> just works, quietly and efficiently without getting in your way.</li>
    <li>Unified Functionality: What this plugin does (image compression, resource minification, static cache) can be accomplished by using a few other plugins in tandem. <strong>Easy Cache</strong> just brings them in a unified plugin, with the added guarantee that your source files are never modified.</li>
    <li>Source Sanctity: <strong>Easy Cache</strong> <em>never</em> deletes or overwrites your existing content like your images, JavaScript and style resources. And it doesn't require you to modify your <code>.htaccess</code>, <code>wp-config.php</code> or add extra php files in anywhere. All the optimization is done in a separate cache folder outside WordPress workflow so that if you deactivate or temporarily pause the plugin, your source files and blog processing are left in their original, pristine condition.</li>
    <li>Graceful Failures: In the unlikely event that the plugin cannot create cached resources, it fails quickly and gracefully to let WordPress (and other caching plugins) serve your blog pages.</li>
    <li>Intelligent Invalidations: <strong>Easy Cache</strong> excels in ensuring that your readers get the latest content. It removes the stale content using a variety of intelligent hooks.</li>
  </ol>
  <p>If you like the way this plugin works, you might want to get the <a href="http://buy.thulasidas.com/easy-cache" title="Buy the Pro version of Easy Cache for $13.95. Instant download link." class="popup">Pro version</a>, so that you can bring the same benefits to your desktop readers, and have specialized and independent caching for smart phones and tablets.</p>
  <p><input type='button' value='Hide this Box' class='button-secondary why'/></p>
</div>

<div id='pro-version' style='clear:both;display:none;padding:10px;background-color:#ddf;font-size:1.1em' class='info-div'>
  <p>The <a href="http://buy.thulasidas.com/easy-cache" title="Buy the Pro version of Easy Cache for $13.95. Instant download link." class="popup">Pro version</a> of <strong>Easy Cache</strong> builds on the power of this plugin and gives you the following additional features:</p>
  <ol>
    <li>Independent caching for tablets, smart phones and desktop readers so that the image resizing is optimized to the target browsers.</li>
    <li>Use of RAM (where available) for blazingly fast cache. You just need to specify the RAM disk location, and Easy Cache will take care of the rest with an absolute minimum of fuss.</li>
    <li>Full support for Content Delivery Networks (CDN). Supports load-balancing over multiple CDN servers.</li>
    <li>Test mode to serve cached versions to the admin user to verify and benchmark caching.</li>
    <li>Ability to specify individual file types to be excluded from caching.</li>
    <li>Optionally cache RSS feeds.</li>
    <li>Ability to delete cached entities per device type (smart phones, tables or desktop browsers) or en masse.</li>
    <li>Ability to pre-populate the cache so that <em>Easy Cache</em> behaves like a warm cache. The pre-cache process happens in the background without affecting your workflow, or overloading your server.</li>
    <li>Integrated CDN cache busting options for easily refreshing off-site content.</li>
    <li>Ability to use TinyPNG for compression for state-of-the-art image compression. [Work in progress]</li>
    <li>Comprehensive help to guide you through in migrating from other popular caching plugins, and on apache/nginx or hybrid servers. [Work in progress]</li>
  </ol>
  <p>If you like <strong>Easy Cache</strong>, you will love its <a href="http://buy.thulasidas.com/easy-cache" title="Buy the Pro version of Easy Cache for $13.95. Instant download link." class="popup">Pro version</a>!</p>
  <p><input type='button' value='Hide this Box' class='button-secondary pro-version'/></p>
</div>
