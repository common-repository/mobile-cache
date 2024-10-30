<?php
// For debugging/testing

if (!class_exists("MobileCacheBase")) {

  require_once 'EzOptions.php';
  require_once ABSPATH . 'wp-admin/includes/file.php';

  /**
   * @property string $siteUrl Cached siteurl() output (with trailing /).
   * @property string $absPath Cached $wp_filesystem->abspath() output (with trailing /).
   * @property string $wpRoot If WP is installed in a folder, that folder name(with trailing /).
   * @property string $cacheUrl URL to the cache of the current device type. Usually http::/yourblog.com/wp-content/easy-cache/mobile/ (with trailing /).
   * @property stromg $cacheDir Full pathname of the cache folder for the current device type (with trailing /).
   * @property string $cacheTarget If cache folder is a sym link (for RAM disk), the real target (with trailing /).
   * @property string $cacheRoot Cache folder relative to ABSPATH (with trailing /, but no leading /).
   */
  class MobileCacheBase extends EzBasePlugin {

    var $unCachables = array('/wp-admin/', '/wp-includes/', '/feed/');
    var $request, $date;
    var $nLiteOptions;
    static $types = array('mobile' => 'Mobile Devices', 'others' => 'Other Browsers');
    static $userAgents = array('mobile' => 'iPhone', 'others' => '');
    static $siteUrl, $absPath, $wpRoot, $indexFile;
    static $cacheUrl, $cacheDir, $cacheTarget, $cacheRoot;
    static $wp_filesystem = false, $noCache = false;
    static $_options; // to use options in static functions
    static $credentials, $credUrl;

    const COOKIE_EXPIRY = 86400; // = 24 * 60 * 60;

    function __construct($file) { //constructor
      parent::__construct("easy-cache", "Easy Cache", $file);
      $this->prefix = 'mobileCache';
      $defaultOptions = $this->mkDefaultOptions();
      $this->optionName = $this->prefix;
      $this->options = get_option($this->optionName);
      if (empty($this->options)) {
        $this->options = $defaultOptions;
      }
      else {
        $this->options = array_merge($defaultOptions, $this->options);
      }
      if (!empty($this->options['pause_cache'])) {
        return;
      }
      self::$siteUrl = trailingslashit(site_url());
      self::$wpRoot = parse_url(self::$siteUrl, PHP_URL_PATH);
      if (empty(self::$wpRoot) || self::$wpRoot == DIRECTORY_SEPARATOR) {
        self::$wpRoot = "";
      }
      else {
        self::$wpRoot = trailingslashit(self::$wpRoot);
      }
      $this->date = date("F j, Y, g:i a");
      if (!empty($this->options['uncachables'])) {
        $this->unCachables = array_map('trim', explode(",", $this->options['uncachables']));
      }
      if (empty($this->options['minify_theme'])) {
        $this->unCachables[] = '/wp-content/themes/';
      }
      self::$indexFile = $this->options['index_file'];
      self::$_options = $this->options;

      // Hooks and such
      if (is_admin()) {
        add_action('edit_post', array($this, 'rmPostCache'));
        add_action('comment_closed', array($this, 'rmPostCache'));
        add_action('deleted_post', array($this, 'rmPostCache'));
        add_action('trashed_post', array($this, 'rmPostCache'));
        add_action('transition_post_status', array($this, 'transitionPostStatus'), 10, 3);
        add_action('deleted_comment', array($this, 'rmCommentCache'));
        add_action('wp_set_comment_status', array($this, 'rmCommentCache'));
        add_action('delete_category', array($this, 'deleteCategory'));
        add_action('switch_theme', array($this, 'switchTheme'));
        add_action('update_option_permalink_structure', array($this, 'updatePermaLinks'), 10, 2);

        add_filter('post_row_actions', array($this, 'postRowActions'), 10, 2);
        add_filter('page_row_actions', array($this, 'postRowActions'), 10, 2);

        add_action('init', array($this, 'handleFilters'));
        register_activation_hook($file, array($this, 'install'));
      }
      else {
        add_action('parse_request', array($this, 'parseRequest'), 2);
      }
    }

    /**
     * Creates EzOption objects for admin page rendering
     */
    function mkEzOptions() {
      if (!empty($this->ezOptions)) {
        return;
      }
      $o = new EzCheckBox('pause_cache');
      $o->title = __('Pause Easy Cache temporarily. The cached files will not be deleted, and you can resume caching later.', 'easy-cache');
      $o->desc = "<strong>" . __('Pause Easy Cache?', 'easy-cache') . "</strong>";
      $o->between = "&nbsp;";
      $o->after = "<br><br>";
      $o->tipWidth = "400";
      $this->ezOptions['pause_cache'] = clone $o;

      $o = new EzCheckBox('enable_others');
      $o->title = __('Use Easy Cache to handle caching for other users as well (recommended). Although originally designed for mobile devices, Easy Cache can do an excellent job using the same proven caching techniques for desktop and other browsing. If this option is set, Easy Cache will serve other (non-mobile) users from an independent cache. In the Pro version, you can set different image compression options depending on the target device type.', 'easy-cache');
      $o->desc = __('Enable Caching for All?', 'easy-cache');
      $o->between = "&nbsp;";
      $o->after = "<br><br>";
      $o->tipWidth = "400";
      $this->ezOptions['enable_others'] = clone $o;

      $o = new EzCheckBox('serve_logged_in');
      $o->title = __('If checked (not recommended), logged in users also see cached files rather than dynamically generated files.', 'easy-cache');
      $o->desc = __('Serve cached files to logged in users?', 'easy-cache');
      $o->between = "&nbsp;";
      $o->after = "<br><br>";
      $o->tipWidth = "400";
      $this->ezOptions['serve_logged_in'] = clone $o;

      $o = new EzCheckBox('minify_theme');
      $o->title = __('If checked (recommended), Easy Cache will minify and compress theme assets. If you see issues related to missing style files for child themes, you may want to disable this option.', 'easy-cache');
      $o->desc = __('Compress theme assets?', 'easy-cache');
      $o->between = "&nbsp;";
      $o->after = "<br><br>";
      $o->tipWidth = "400";
      $this->ezOptions['minify_theme'] = clone $o;

      $o = new EzText('uncachables');
      $o->title = __('Give a comma-separated list of folders which will not be cached. By default, <code>/wp-admin/, /wp-includes/ and /feed/</code> folders are excluded from caching. In the Pro version you can control specific files and file types to be ignored while caching.', 'easy-cache');
      $o->desc = __('Uncachable Folders:', 'easy-cache');
      $o->style = "width:65%;float:right";
      $o->after = "<br /><br />";
      $o->tipWidth = "400";
      $this->ezOptions['uncachables'] = clone $o;

      $o = new EzCheckBox('use_gzip');
      $o->title = __('If checked, Easy Cache will attempt to compress the html output using gzip, if possible. It is much better to enable gzip compression at the webserver level, or using <code>.htaccess</code>. Try this option if you must, and disable it if you have problems viewing your blog pages.', 'easy-cache');
      $o->desc = __('Use gzip compression?', 'easy-cache');
      $o->between = "&nbsp;";
      $o->after = "<br><br>";
      $o->tipWidth = "400";
      $this->ezOptions['use_gzip'] = clone $o;

      $o = new EzCheckBox('minify_html');
      $o->title = __('If checked (recommended), Easy Cache will minify the dynamic source generated by WordPress by removing comments and white spaces. This minification is done carefully not to strip off directives masquerading as comments (such as Internet Explorer directives).', 'easy-cache');
      $o->desc = __('Minify HTML source?', 'easy-cache');
      $o->between = "&nbsp;";
      $o->after = "<br><br>";
      $o->tipWidth = "400";
      $this->ezOptions['minify_html'] = clone $o;

      $o = new EzCheckBox('minify_css');
      $o->title = __('If checked (recommended), Easy Cache will minify style files (CSS) by removing comments and white spaces. Easy Cache will locate and minify imported CSS files as well. But if your blog looks misformatted, please disable this option and inform the plugin author.', 'easy-cache');
      $o->desc = __('Minify CSS files?', 'easy-cache');
      $o->between = "&nbsp;";
      $o->after = "<br><br>";
      $o->tipWidth = "400";
      $this->ezOptions['minify_css'] = clone $o;

      $o = new EzCheckBox('minify_js');
      $o->title = __('If checked (recommended), Easy Cache will minify JavaScript files (js) by removing comments and white spaces. It is safe to minify JavaScript files, but if you notice any problems with the dynamic behavior of the blog (like buttons not responding etc.), please disable this option and contact the plugin author.', 'easy-cache');
      $o->desc = __('Minify JavaScript?', 'easy-cache');
      $o->between = "&nbsp;";
      $o->after = "<br><br>";
      $o->tipWidth = "400";
      $this->ezOptions['minify_js'] = clone $o;

      $o = new EzText('cache_expiry');
      $o->title = __('Specify how long (in seconds) a cached file will live. Easy Cache is designed to run unattended, and you can set this number very large, e.g., 31536000 (one year). Easy Cache is intelligent enough to detect source changes and invalidate the corresponding pages.', 'easy-cache');
      $o->desc = __('Cache Expiry:', 'easy-cache');
      $o->style = "width:65%;float:right";
      $o->after = "<br /><br />";
      $o->tipWidth = "400";
      $this->ezOptions['cache_expiry'] = clone $o;

      $o = new EzText('jpeg_quality');
      $o->title = __('Specify the quality of JPEG compression to use while resizing your images. 50 (recommended) is adequate for most web usage, unless your images consist mostly of smooth gradients.', 'easy-cache');
      $o->desc = __('JPEG Quality:', 'easy-cache');
      $o->style = "width:65%;float:right";
      $o->after = "<br /><br />";
      $o->tipWidth = "400";
      $this->ezOptions['jpeg_quality'] = clone $o;

      $o = new EzText('max_dimension');
      $o->title = __('Specify the maximum image dimension (width or height) that you would like to resize to. Smaller images are never resized up, and if the resized image file is bigger than the original, it will not be used.', 'easy-cache');
      $o->desc = __('Max image dimension:', 'easy-cache');
      $o->style = "width:65%;float:right";
      $o->after = "<br /><br />";
      $o->tipWidth = "400";
      $this->ezOptions['max_dimension'] = clone $o;

      $o = new EzText('index_file');
      $o->title = __('Specify the index file that will be generated if your permalink structure points to folders. For instance, if you specify <code>index.htm</code> in this option, your post <code>http://example.com/my-last-post/</code> will be cached as <br><code>http://example.com/mobile/my-last-post/index.htm</code>.<br>You can specify <code>index.html</code> or anything else that your webserver is configured to use. But avoid using <code>index.php</code> because it may conflict with the default WordPress behavior.', 'easy-cache');
      $o->desc = __('Index file:', 'easy-cache');
      $o->style = "width:65%;float:right";
      $o->after = "<br /><br />";
      $o->tipWidth = "400";
      $this->ezOptions['index_file'] = clone $o;

      parent::mkEzOptions();
      $this->nLiteOptions = count($this->ezOptions);
    }

    /**
     * Makes the default values of options (for frontend as well as admin)
     * @return [string]
     */
    function mkDefaultOptions() {
      $defaultOptions = array(
          'pause_cache' => false,
          'enable_others' => true,
          'serve_logged_in' => false,
          'minify_theme' => true,
          'uncachables' => '/wp-admin/, /wp-includes/, /feed/',
          'use_gzip' => false,
          'minify_html' => true,
          'minify_css' => true,
          'minify_js' => true,
          'cache_expiry' => 31536000,
          'jpeg_quality' => 50,
          'max_dimension' => 640,
          'index_file' => 'index.htm') +
              parent::mkDefaultOptions();
      return $defaultOptions;
    }

    /**
     * Handles submits from admin page
     * @return bool Returns empty if $_POST is empty
     */
    function handleSubmits() {
      if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        return;
      }
      parent::handleSubmits();
      self::$_options = $this->options;
      echo $this->adminMsg;
    }

    /**
     * Prints out divs that are shown in a neat box on the admin page.
     */
    function printInfoDivs() {
      require_once 'info-divs.php';
    }

    /**
     * Prints out the links that will display info divs
     */
    function printInfoLinks() {
      ?>
      <div style="background-color:#ffd;padding:10px;border:solid 1px #cca">
        <p style='margin-top:-8px;margin-bottom:-6px;float:left;text-align:center;font-size:1.1em'>
          <span style='background-color:#ffd'>
            &nbsp;<strong> Info Box</strong>&nbsp;
          </span>
        </p>
        <ul style="padding-left:80px;list-style-type:circle; list-style-position:inside;">
          <li><a href='#' class="what">What does <strong>Easy Cache</strong> do?</a></li>
          <li><a href='#' class="why">Why another caching plugin?</a></li>
          <li><a href='#' class="pro-version">Why get the Pro version?</a></li>
        </ul>
      </div>
      <?php
    }

    /**
     * Quasi abstract.
     */
    function checkCache() {
      $this->install();
    }

    /**
     * Prints out the admin page
     */
    function printAdminPage() {
      $ez = parent::printAdminPage();
      if (empty($ez)) {
        return;
      }
      $this->handleSubmits();
      $this->mkEzOptions();
      $this->checkCache();
      $this->setOptionValues();
      ?>
      <div class="wrap" style="width:1000px;">
        <?php
        echo <<<EOF1
    <h2>{$this->name}{$this->strPro}<a href="http://validator.w3.org/" target="_blank"><img src="http://www.w3.org/Icons/valid-xhtml10" alt="Valid HTML5" title="{$this->name}{$this->strPro} Admin Page is certified Valid HTML5" height="31" onmouseover="Tip('{$this->name}{$this->strPro} Admin Page is certified Valid HTML5, with no errors in the HTML code generated by the plugin.')" onmouseout="UnTip()" width="88" class="alignright"/></a>
</h2>
EOF1;
        $permaStructure = get_option('permalink_structure');
        if (empty($permaStructure)) {
          $permalink = admin_url('options-permalink.php');
          ?>
          <div class='error' style='padding:10px;margin:10px;color:#a00;font-weight:500;background-color:#fee;' id="permalinks">
            <p><strong>Permalinks</strong> are not enabled on your blog, which this plugin needs. Please <a href='<?php echo $permalink; ?>'>enable a permalink structure</a> for your blog from <strong><a href='<?php echo $permalink; ?>'>Settings &rarr; Permalinks</a></strong>.<br> Any structure (other than the ugly default structure using <code><?php echo site_url(); ?>/?p=123</code>) will do.</p>
          </div>
          <?php
        }
        ?>
        <h3>
          <?php
          _e('Introduction', 'easy-cache');
          ?>
        </h3>
        <div style="width:45%;float:left;display:inline-block;padding:10px">
          <p><strong>Easy Cache</strong> is an aggressive caching plugin designed specifically for mobile devices. It serves minified static files and compressed images to your mobile readers.</p>
          <p>In the Pro version of this plugin, you can have use the same wickedly optimized caching algorithm to desktop browsers as well. The Pro version also adds a whole host of features.</p>
          <p><input type='button' value='Show Detailed Info' class='button-secondary details'/></p>
        </div>
        <div style="width:45%;float:left;display:inline-block;padding:10px">
          <table class="form-table">
            <tr style="vertical-align:middle">
              <?php
              $ez->renderHeadText($this->isPro);
              ?>
            </tr>
          </table>
        </div>
        <div style='clear:both'></div>
        <?php
        $this->printInfoDivs();
        ?>
        <form method='post' action='#'>
          <?php
          $this->renderNonce();
          ?>
          <h3>
            <?php
            printf(__('Easy Cache', 'easy-cache'));
            ?>
          </h3>
          <div style="width:40%;float:left;display:inline-block;padding:10px">
            <?php
            $firstCol = array('pause_cache',
                'enable_others',
                'serve_logged_in',
                'minify_theme',
                'use_gzip',
                'minify_html',
                'minify_css',
                'minify_js');
            foreach ($firstCol as $k) {
              if (!empty($this->ezOptions[$k])) {
                $this->ezOptions[$k]->render();
              }
            }
            ?>
          </div>
          <div style="width:55%;float:right;display:inline-block;">
            <?php
            $this->printInfoLinks();
            ?>
            <br>
            <?php
            $secondCol = array('cache_expiry',
                'jpeg_quality',
                'max_dimension',
                'index_file',
                'uncachables');
            foreach ($secondCol as $k) {
              if (!empty($this->ezOptions[$k])) {
                $this->ezOptions[$k]->render();
              }
            }
            ?>
          </div>
          <div style="clear:both"></div>
          <div style='padding-bottom:20px;text-align:center'>
            <?php
            $this->ezOptions['kill_author']->render();
            ?>
          </div>
          <?php
          $this->printProSection()
          ?>
          <div class="submit">
            <?php
            $this->renderSubmitButtons();
            ?>
          </div>
        </form>

        <?php
        $ez->renderWhyPro();
        $ez->renderSupport();
        $ez->renderTailText();
        ?>
      </div>
      <?php
    }

    /**
     * Generate credentials to construct WP_Filesystem object
     *
     * @param type $method WP_Filesystem method.
     * @return boolean/WP_Credentials True if using RAM disk (=> direct access)
     */
    static function getCredentials($method = '') {
      $access_type = get_filesystem_method();
      if (empty(self::$credUrl)) {
        if ($access_type === 'direct') {
          self::$credUrl = self::$siteUrl;
          self::$credentials = request_filesystem_credentials(self::$credUrl, $method, false, false, null);
        }
        else {
          self::$credUrl = wp_nonce_url(admin_url('options.php?page=mobile-cache.php'), 'easy-cache');
          self::$credentials = request_filesystem_credentials(self::$credUrl, '', false, false, null);
        }
      }
      return self::$credentials;
    }

    /**
     * Returns the global $wp_filesystem after basic checks.<p>
     * Also sets static variables
     *  $noCache to be true if caching is not possible.
     *  $cacheDir, $cacheUrl etc.
     *
     * @global WP_Filesystem $wp_filesystem
     * @param string $cacheType (Optional) Cache directory
     * @param string $install (Optional) Flag to indicate if cached FS can be returned
     * @return WP_Filesystem Returns the global var $wp_filesystem or empty on error
     */
    static function getFileSystem($cacheType = 'mobile/', $install = false) {
      if (!$install && !empty(self::$wp_filesystem)) {
        return self::$wp_filesystem;
      }
      $creds = static::getCredentials();
      if ($creds !== false) {
        WP_Filesystem($creds);
      }
      else {
        self::$noCache = true;
        return;
      }
      global $wp_filesystem;
      self::$wp_filesystem = $wp_filesystem;
      if (empty($wp_filesystem)) {
        self::$noCache = true;
        return;
      }
      $cacheRoot = trailingslashit($wp_filesystem->wp_content_dir()) . "easy-cache/";
      $cacheDir = $cacheRoot . $cacheType;
      if (!$wp_filesystem->is_dir($cacheDir)) {
        if (!$wp_filesystem->is_dir($cacheRoot)) {
          $wp_filesystem->mkdir($cacheRoot, 0777);
          $wp_filesystem->put_contents($cacheRoot . ".htaccess", "Options +FollowSymLinks -SymLinksIfOwnerMatch\n"
                  . "DirectoryIndex " . self::$indexFile . " index.php");
        }
        $wp_filesystem->mkdir($cacheDir, 0777);
        $indexPhp = $cacheDir . "/index.php";
        $wp_filesystem->put_contents($indexPhp, "<?php header('location: "
                . self::$siteUrl . "index.php');");
        $wp_filesystem->touch($indexPhp, 1, 1);
      }
      if (!$wp_filesystem->is_writable($cacheDir)) {
        self::$noCache = true;
      }
      self::$cacheDir = $cacheDir;
      self::$cacheTarget = $cacheDir;
      self::$cacheUrl = content_url("easy-cache/$cacheType");
      self::$cacheRoot = self::trimPath(self::$cacheUrl, self::$siteUrl);
      self::$absPath = trailingslashit($wp_filesystem->abspath());
      return $wp_filesystem;
    }

    /**
     * Creates the cache folder.<p>
     * Dies if it cannot be created. This is designed to be <br>
     * called from the plugin install hook so that the plugin<br>
     * doesn't activate if the cache cannot be created.
     *
     * @param string $cacheType (Optional) Cache directory
     * @param string $install (Optional) Flag to indicate if cached FS can be returned
     */
    static function createCache($cacheType = 'mobile/', $install = false) { // To be called from install hook
      $fs = static::getFileSystem($cacheType, $install);
      if (self::$noCache) {
        die("<strong>Easy Cache</strong>: Cache directory could not be created. Please create <code>"
                . self::$cacheDir . "</code>, set it writable, and try again.");
      }
    }

    /**
     * Fetches a the blog index so that its cache is built.
     */
    static function fetchIndex() {
      if (function_exists('curl_init')) {
        foreach (static::$userAgents as $ua) {
          $curl = curl_init();
          curl_setopt($curl, CURLOPT_URL, self::$siteUrl);
          curl_setopt($curl, CURLOPT_FOLLOWLOCATION, false);
          curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
          curl_setopt($curl, CURLOPT_USERAGENT, $ua);
          $content = curl_exec($curl);
          curl_close($curl);
        }
      }
    }

    /**
     * Creates a folder using WP_Filesystem
     * @param string $dir Folder to be created
     * @param string $cacheType (Optional) Cache directory
     */
    static function mkdir($dir, $cacheType = 'mobile/') {
      $fs = static::getFileSystem($cacheType);
      if (empty(self::$noCache)) {
        if (strpos($dir, self::$cacheTarget) === 0) {
          $dir = substr($dir, strlen(self::$cacheTarget));
        }
        $fs->chdir(self::$cacheTarget);
        $path = explode(DIRECTORY_SEPARATOR, $dir);
        foreach ($path as $d) {
          if (!$fs->is_dir($d)) {
            $fs->mkdir($d, 0777);
          }
          $fs->chdir($d);
        }
      }
    }

    /**
     * Creates a cached file with the data specified
     * @param string $file File name
     * @param string $data Data to be written
     * @param string $cacheType (Optional) Cache directory
     */
    static function cacheFile($file, $data, $cacheType = 'mobile/') {
      $fs = static::getFileSystem($cacheType);
      if (empty(self::$noCache)) {
        $dir = dirname($file);
        self::mkdir($dir, $cacheType);
        $fs->put_contents(self::$cacheDir . $file, $data);
      }
    }

    /**
     * Creates a cached file with the data specified
     * @param string $url URL to be cached
     * @param string $data Data to be written
     * @param string $cacheType (Optional) Cache directory
     */
    static function cacheUrl($url, $data, $cacheType = 'mobile/') {
      return static::cacheFile(trailingslashit($url) . self::$indexFile, $data, $cacheType);
    }

    /**
     *
     * Copies a file to the cacheDir after possible minification
     *
     * @global WP_Filesystem $wp_filesystem
     * @param string $source source file
     * @param string $target target file
     * @param string $cacheType (Optional) Cache directory
     */
    static function copyFile($source, $target, $cacheType = 'mobile/') {
      $fs = static::getFileSystem($cacheType);
      if (empty(self::$noCache)) {
        $dir = dirname($target);
        self::mkdir($dir, $cacheType);
        $type = strtolower(pathinfo($source, PATHINFO_EXTENSION));
        switch ($type) {
          case 'htm':
            $type = 'html';
          case 'html':
          case 'js':
          case 'css':
            $input = $fs->get_contents($source);
            $data = self::minify($input, $type);
            $fs->put_contents($target, $data);
            break;
          case 'png':
          case 'jpg':
          case 'jpeg':
          case 'gif':
            self::compress($source, $target, $cacheType);
            if ($fs->size($target) > $fs->size($source)) {
              $fs->copy($source, $target);
            }
            break;
          default:
            $fs->copy($source, $target);
        }
      }
    }

    /**
     * Flexible, type aware minification interface
     * <p>Uses EzMinify class
     * @param string $input String to be minified
     * @param string $type Minification type (html, js or css)
     * @return string Minified string
     */
    static function minify($input, $type = 'html') { // placeholder to minification
      if (empty(self::$_options["minify_$type"])) {
        return $input;
      }
      require_once 'EzMinify.php';
      return EzMinify::$type($input);
    }

    /**
     * Retrieves the real path of a file from its WP_Filesystem location
     * @param string $source File name
     * @return string The real path of the file
     */
    static function getRealPath($source) {
      if (get_filesystem_method() == 'direct') { // Using direct method in WP_Filesystem
        return $source;
      }
      $path = self::trimPath($source);
      return trailingslashit(ABSPATH) . $path;
    }

    /**
     * Generates a temporary image file name for saving images.<p>
     * The temp file is in the same location as the original image file,<br>
     * (or in the system temp folder) but with a modified base name.<br>
     *  Assumes that the original image file location is writable, if used.
     *
     * @param string $sourceImg Image file name
     * @return string Temp image file name
     */
    static function mkTmpImgName($sourceImg) {
      $parts = pathinfo($sourceImg);
      if (get_filesystem_method() == 'direct') {
        $tmpDir = trailingslashit(sys_get_temp_dir());
        $tmpName = $tmpDir . $parts['filename'] . "-easy-cache-temp."
                . $parts['extension'];
      }
      else {
        $tmpName = $parts['dirname'] . DIRECTORY_SEPARATOR . $parts['filename']
                . "-easy-cache-temp." . $parts['extension'];
      }
      return $tmpName;
    }

    /**
     * Compresses a given image.<p>
     * Uses the options max_dimension and jpeg_quality.
     * @param string $source
     * @param string $target
     * @param string $cacheType (Optional) Cache directory
     */
    static function compress($source, $target, $cacheType = 'mobile/') {
      $fs = static::getFileSystem($cacheType);
      if (!empty(self::$noCache)) {
        return;
      }
      $sourceImg = self::getRealPath($source);
      $editor = wp_get_image_editor($sourceImg);
      if (is_wp_error($editor)) {
        $fs->copy($source, $target);
        return;
      }
      $editor->set_quality(self::$_options['jpeg_quality']);
      $max = self::$_options['max_dimension'];
      $resized = $editor->resize($max, $max);
      if (is_wp_error($resized)) {
        $fs->copy($source, $target);
        return;
      }
      // WP_Filesystem and WP_Image_Editor don't play nice with each other.
      // Hence all this jumping through the hoops...
      $tmpImgName = self::mkTmpImgName($sourceImg);
      $saved = $editor->save($tmpImgName);
      if (is_wp_error($saved)) {
        $fs->copy($source, $target);
        return;
      }
      $sourceTmp = self::mkTmpImgName($source);
      $fs->move($sourceTmp, $target);
    }

    /**
     * Returns the device type (to be used as the cache folder name)
     * @return string Cache type
     */
    static function getCacheType() {
      if (!empty($_COOKIE["ezMobileDetect"])) {
        return $_COOKIE["ezMobileDetect"];
      }
      if (!class_exists("Ez_Mobile_Detect")) {
        require "lib/Ez_Mobile_Detect.php";
      }
      if (class_exists("Ez_Mobile_Detect")) {
        $detect = new Ez_Mobile_Detect;
        if ($detect->isMobile()) {
          setcookie("ezMobileDetect", "mobile/", time() + self::COOKIE_EXPIRY);
          return "mobile/";
        }
        else if (!empty(self::$_options['enable_others'])) {
          setcookie("ezMobileDetect", "others/", time() + self::COOKIE_EXPIRY);
          return "others/";
        }
      }
    }

    /**
     * Checks if the given request is cacheable.<p>
     * PHP files are not cacheable. And, by default, wp-admin and wp-includes<br>
     * and feed files are not cached. Optionally, you can specify that<br>
     * theme assets need to be cached or not.
     * @param string $request
     * @return boolean True if cacheable.
     */
    function notCachable($request) {
      foreach ($this->unCachables as $ex) {
        if (strpos($request, $ex) !== false) {
          return true;
        }
      }
      $ext = pathinfo($request, PATHINFO_EXTENSION);
      if ($ext == 'php' || $ext == 'ezp') {
        return true;
      }
      return false;
    }

    /**
     * Inserts the cache folder in the given URL.<p>
     * This function is used as the callback target for preg_replace.
     * @param string $url The URL to be modified
     * @return string URL with the cache folder inserted.
     */
    function addCacheType($url) {
      $regExp = '/\?.*?([\'"])/';
      $url[2] = preg_replace($regExp, '\\1', $url[2]);
      $request = "/" . rtrim($url[2], "'\"");
      if ($this->notCachable($request)) {
        $urlEx = $url[1] . $url[2];
      }
      else {
        $urlEx = $url[1] . self::$cacheRoot . $url[2];
      }
      return $urlEx;
    }

    /**
     * Buffer the output to be passed to the endBuffer function.
     */
    function startBuffer() {
      ob_start(array($this, 'endBuffer'));
    }

    /**
     * Caches the given HTML string.<p>
     * Optionally minifies and gzips the HTML string.
     * @param string $html HTML string to be cached.
     * @return string Minified, cacheable HTML string
     */
    function endBuffer($html) {
      $regex = "#(['\"]" . self::$siteUrl . ")(.*?['\"])#";
      $html = preg_replace_callback($regex, array($this, 'addCacheType'), $html);
      $output = false;
      if ($this->options['use_gzip']) {
        $string = str_ireplace("</html>", "<!-- gzipped by ob_gzhandler at $this->date -->\n</html>", $html);
        $output = ob_gzhandler($string);
      }
      if (!$output && $this->options['minify_html']) {
        $output = self::minify($html);
      }
      if (!$output) {
        $output = $html;
      }
      $cacheType = static::getCacheType();
      self::cacheUrl($this->request, $output, $cacheType);
      return $output;
    }

    /**
     * Removes a cached URL (which would be a folder in the cache)
     * @param string $url The URL to be removed
     * @param string $cacheType (Optional) Cache directory
     */
    function rmCachedUrl($url, $cacheType = 'mobile/') {
      $fs = static::getFileSystem();
      if (!empty(self::$noCache)) {
        return;
      }
      $dir = str_replace(self::$siteUrl, self::$cacheDir, $url);
      $fs->delete($dir, true);
    }

    /**
     * Removes all the cached entities corresponding to a post/page.<p>
     * All the category and tag pages that the post/page belongs to<br>
     * will be purged as well. So will the cached blog index page.
     * @param type $post
     * @param string $cacheType (Optional) Cache directory
     */
    function rmPostCache($post, $cacheType = 'mobile/') {
      $post = get_post($post);
      if (empty($post)) {
        die("Empty post in rmPostCache!");
      }

      $this->rmCachedUrl(self::$siteUrl . self::$indexFile);

      $url = get_permalink($post);
      $this->rmCachedUrl($url);

      $cats = wp_get_post_categories($post->ID, array('fields' => 'ids'));
      foreach ($cats as $catId) {
        $catUrl = get_category_link($catId);
        $this->rmCachedUrl($catUrl);
      }

      $tags = wp_get_post_tags($post->ID, array('fields' => 'ids'));
      foreach ($tags as $tagId) {
        $tagUrl = get_tag_link($tagId);
        $this->rmCachedUrl($tagUrl);
      }
    }

    /**
     * Removes the cache recursively, and recreates an empty one.
     *
     * @param string $cacheType mobile/others etc.
     * @param string $createNew (Optional) Whether to create an empty cache
     * @return bool Empty if not caching
     */
    static function rmSingleCache($cacheType = 'mobile/', $createNew = true) {
      $fs = static::getFileSystem($cacheType, true);
      if (!empty(self::$noCache)) {
        return;
      }
      $fs->delete(self::$cacheDir, true);
      if ($createNew) {
        self::createCache($cacheType, true);
      }
    }

    /**
     * Removes all caches recursively, and recreates empty ones.
     *
     * @param string $cacheType mobile/desktop etc.
     * @param string $createNew (Optional) Whether to create an empty cache
     * @return bool Empty if not caching
     */
    static function rmCache($cacheType = '', $createNew = true) {
      if (empty($cacheType)) {
        $types = array_keys(static::$types);
        foreach ($types as $type) {
          self::rmSingleCache($type, $createNew);
        }
      }
      else {
        self::rmSingleCache($cacheType, $createNew);
      }
    }

    /**
     * Serve a URL from the cache.
     * @param string $requestCache URL to be served
     */
    static function serveCached($requestCache, $targetCache) {
      $cacheType = static::getCacheType();
      $fs = static::getFileSystem($cacheType);
      if (!$fs->exists($targetCache)) {
        ini_set('user_agent', static::$userAgents[$cacheType]);
        $headers = get_headers($requestCache); // triggers caching
      }
      $headers = get_headers($requestCache);
      foreach ($headers as $h) {
        header($h);
      }
      readfile(self::getRealPath($targetCache));
      exit();
    }

    /**
     * Create the blog index file in the cache, just in case the reader reaches
     * it by clicking a cache url (http://www.example.com/blog/mobile/)
     * This function is not used because its work is done in the install hoook.
     * Archived here, just in case...
     */
    static function mkBlogIndex($cacheType = 'mobile/') {
      $fs = static::getFileSystem($cacheType);
      if (!empty(self::$noCache)) {
        return;
      }
      $indexFile = self::$cacheDir . self::$indexFile;
      $indexPhp = self::$cacheDir . "index.php";
      if (!$fs->exists($indexFile) && !$fs->exists($indexPhp)) {
        $fs->put_contents($indexPhp, "<?php header('location: " . self::$siteUrl
                . "index.php');");
        $fs->touch($indexPhp, 1, 1);
      }
      if ($fs->exists($indexFile) && $fs->exists($indexPhp)) {
        $fs->delete($indexPhp);
      }
    }

    /**
     * Removes the WP root directory part from the given target path.
     * Also removes the leading slash or backslash.
     *
     * @param type $target Path to be modified
     * @param type $root (Optional) The leading string to be removed
     * @return string trimmed path.
     */
    static function trimPath($target, $root = '') {
      if (empty($root)) {
        $root = self::$wpRoot;
      }
      if (empty($root) || $root == DIRECTORY_SEPARATOR) {
        return ltrim($target, '/' . DIRECTORY_SEPARATOR);
      }
      if (strpos($target, $root) === 0) {
        $s = substr($target, strlen($root));
        if (empty($s)) {
          $s = "";
        }
        return $s;
      }
      if (strpos($target, "/" . $root) === 0) {
        $s = substr($target, strlen($root) + 1);
        if (empty($s)) {
          $s = "";
        }
        return $s;
      }
      return ltrim($target, '/' . DIRECTORY_SEPARATOR);
    }

    // WordPress Actions and Filters

    /**
     * Action call-back to parse_request.<p>
     * Cache a URL, serve and die if possible.
     * If not, return empty
     *
     * @return Bool Empty if REQUEST_URI is not a cached or cacheable.
     */
    function parseRequest() {
      $cacheType = static::getCacheType();
      if (empty($cacheType)) {
        return;
      }
      if (is_user_logged_in() && empty($this->options['serve_logged_in'])) {
        return;
      }
      $request = $_SERVER['REQUEST_URI'];
      if ($this->notCachable($request)) {
        return;
      }
      $fs = static::getFileSystem($cacheType);
      if (!empty(self::$noCache)) {
        return;
      }
      $request = self::trimPath($request);
      $seekingCache = false;
      $requestTrimmed = self::trimPath($request, self::$cacheRoot);
      if ($requestTrimmed != $request) {
        $request = $requestTrimmed;
        $seekingCache = true;
      }
      $pos = strpos($request, self::$indexFile);
      $seekingIndex = false;
      if ($pos !== false) {
        $substr = substr($request, $pos);
        if (strpos($substr, self::$indexFile) !== false) {
          $seekingIndex = true;
        }
      }
      $indexFile = self::$indexFile;
      $request = preg_replace(array("/$indexFile.?$/", '/\?.*/'), '', $request);
      $requestReal = self::$siteUrl . $request;
      $requestCache = self::$cacheUrl . $request;
      $targetReal = rtrim(self::$absPath . $request, DIRECTORY_SEPARATOR);
      $targetCache = self::$cacheTarget . $request;
      if ($fs->is_dir($targetCache)) {
        $targetCache = trailingslashit($targetCache) . $indexFile;
        $requestCache = trailingslashit($requestCache) . $indexFile;
      }
      $this->request = $request;
      if ($fs->exists($targetCache)) {
        if ($fs->exists($targetReal) &&
                $fs->mtime($targetCache) < $fs->mtime($targetReal)) {
          // cached file older than the source. update it.
          static::copyFile($targetReal, $targetCache, $cacheType);
        }
        if (!$fs->exists($targetReal) &&
                $fs->mtime($targetCache) < time() - $this->options['cache_expiry']) {
          // cache expired and source not a physical file. generate and cache it.
          $fs->delete($targetCache);
          $this->startBuffer();
        }
        else {
          // serve the cached version
          static::serveCached($requestCache, $targetCache);
        }
      }
      else if (!empty($targetReal) && $fs->exists($targetReal) && !$fs->is_dir($targetReal)) {
        // copy a minified/resized version to cache and serve it
        static::copyFile($targetReal, $targetCache, $cacheType);
        // serve the cached version
        static::serveCached($requestCache, $targetCache);
        exit();
      }
      else if ($seekingCache || $seekingIndex) {
        // serve the real version and prime the cached one
        header("location: $requestReal");
        exit();
      }
      else if (empty($_REQUEST)) { // not a physical file. generate and cache it.
        $this->startBuffer();
      }
    }

    /**
     * Call-back to transition_post_status
     *
     * @param type $new_status
     * @param type $old_status
     * @param type $post
     */
    function transitionPostStatus($new_status, $old_status, $post) {
      if ($new_status != $old_status) {
        $this->rmPostCache($post);
      }
    }

    /**
     * Call-back to  deleted_comment and wp_set_comment_status
     * @param type $comment_id
     */
    function rmCommentCache($comment_id) {
      $comment = get_comment($comment_id);
      $ID = $comment->comment_post_ID;
      $this->rmPostCache($ID);
    }

    /**
     * Call-back to delete_category
     *
     * @param type $catId
     */
    function deleteCategory($catId) {
      $catUrl = get_category_link($catId);
      $this->rmCachedUrl($catUrl);
    }

    /**
     * Filter call-back to post_row_actions and page_row_actions
     * Adds a link to remove cache.
     *
     * @param type $actions
     * @param type $post
     * @return type
     */
    function postRowActions($actions, $post) {
      if (current_user_can('manage_options')) {
        $qs = "";
        if (!empty($_GET['paged'])) {
          $qs .= "paged={$_GET['paged']}&";
        }
        if (!empty($_GET['post_type'])) {
          $qs .= "post_type={$_GET['post_type']}&";
        }
        $url = sprintf("edit.php?{$qs}mobile_cache_purge_post_id=%s", $post->ID);
        $actions['purge'] = sprintf('<a href="%s">Refresh Cache</a>', wp_nonce_url(admin_url($url), 'purge', 'mc_nonce'));
      }
      return $actions;
    }

    /**
     * Call back to switch_theme
     */
    function switchTheme() {
      static::rmCache();
    }

    /**
     * Call back to update_permalinks
     */
    function updatePermaLinks($old, $new) {
      static::rmCache();
    }

    /**
     * Overridden. Runs only on the admin page of this plugin.
     */
    function load() {
      parent::load();
      static::getCredentials();
      if (empty(self::$credentials)) {
        add_action('admin_notices', function() {
          echo "<div class='error'><p>Looks like <strong>Easy Cache</strong> cannot create caches. Please include your FTP details (<code>FTP_HOST</code>, <code>FTP_USER</code> and <code>FTP_PASS</code>) in your <code>wp-config.php</code>.<br>See <a href='http://codex.wordpress.org/Editing_wp-config.php#WordPress_Upgrade_Constants' class='popup' data-height='1024' data-width='1200'>documentation</a> from WordPress.org.</p></div>";
        });
      }
    }

    /**
     * Call back to print admin footer scripts
     */
    function adminPrintFooterScripts() {
      parent::adminPrintFooterScripts();
      ?>
      <script>
        jQuery(document).ready(function () {
          if (!Array.prototype.last) {
            Array.prototype.last = function () {
              return this[this.length - 1];
            };
          }
          var divClasses = '.what, .details, .why, .pro-version';
          jQuery('body').on('click', divClasses, function () {
            var id = '#' + this.className.split(" ").last();
            jQuery(".info-div").not(id).hide('show');
            jQuery(id).toggle('show');
          });
        });
      </script>
      <?php
    }

    /**
     * Hooked to init action<p>
     * Handles delete requests from post/page listing.
     *
     * @param string $cacheType desktop/tablet/phone
     * @param boolean $showNotice Whether or not to display a message
     */
    function handleFilters($cacheType = 'mobile/', $showNotice = true) {
      if (!empty($_GET['mobile_cache_purge_post_id'])) {
        if (isset($_GET['mc_nonce']) && wp_verify_nonce($_GET['mc_nonce'], 'purge')) {
          $post = get_post($_GET['mobile_cache_purge_post_id']);
          $GLOBALS['mobile_cache_purge_post_title'] = $post->post_title;
          if ($showNotice) {
            add_action('admin_notices', function() {
              echo "<div class='updated'><p>Deleted the cached version of the article \"<b>{$GLOBALS['mobile_cache_purge_post_title']}</b>\" from your caches. It will be recreated next time it is loaded.</p></div>";
            });
          }
          $this->rmPostCache($post, $cacheType);
        }
      }
    }

    function install() {
      $types = array_keys(static::$types);
      foreach ($types as $type) {
        static::createCache("$type/", true);
      }
    }

  }

}
