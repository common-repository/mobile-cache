<?php
/*
  Copyright (C) 2008 www.ads-ez.com

  This program is free software; you can redistribute it and/or modify
  it under the terms of the GNU General Public License as published by
  the Free Software Foundation; either version 3 of the License, or (at
  your option) any later version.

  This program is distributed in the hope that it will be useful, but
  WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
  General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

if (!class_exists("EzBaseOption")) {

  class EzBaseOption { // base EzOption class

    var $name, $desc, $title, $tipTitle, $tipWarning, $value, $type;
    var $tipWidth = 240, $tipX = 5, $tipY = 7;
    var $width, $labelWidth, $height, $before, $between, $after, $style;

    function EzBaseOption($type, $name) {
      $vars = get_object_vars($this);
      foreach ($vars as $k => $v) {
        $this->$k = '';
      }
      $this->type = $type;
      $this->name = $name;
      $this->tipWidth = 240;
      $this->tipX = 5;
      $this->tipY = 7;
    }

    function __clone() {
      foreach ($this as $key => $val) {
        if (is_object($val) || (is_array($val))) {
          $this->{$key} = unserialize(serialize($val));
        }
      }
    }

    function get() {
      return $this->value;
    }

    function set($properties, $desc = '') {
      if (!isset($properties)) {
        return;
      }
      if (is_array($properties)) {
        foreach ($properties as $k => $v) {
          $key = strtolower($k);
          if (floatval(phpversion()) > 5.3) {
            if (property_exists($this, $key)) {
              $this->$key = $v;
            }
          }
          else {
            if (array_key_exists($key, $this)) {
              $this->$key = $v;
            }
          }
        }
      }
      else {
        $this->value = $properties;
        if (!empty($desc)) {
          $this->desc = $desc;
        }
      }
    }

    function preRender() {
      $toolTip = $this->mkToolTip();
      if (!empty($this->labelWidth)) {
        $style = "style='display:inline-block;width:{$this->labelWidth}'";
      }
      else {
        $style = "";
      }
      echo "{$this->before}\n<label $style for='{$this->name}' $toolTip>\n";
    }

    function postRender() {
      echo "</label>\n{$this->after}\n";
    }

    function render() {
      $this->preRender();
      echo "<input type='{$this->type}' id='{$this->name}' name='{$this->name}'";
      if (!empty($this->style)) {
        echo " style='{$this->style}'";
      }
      echo " value='{$this->value}' />{$this->desc}\n";
      $this->postRender();
    }

    function updateValue() {
      if (isset($_POST[$this->name])) {
        $this->value = $_POST[$this->name];
      }
    }

    function mkToolTip() {
      if (!empty($this->title)) {
        if (!empty($this->tipWarning)) {
          if (empty($this->tipTitle)) {
            $this->tipTitle = "Warning!";
          }
          $warning = ", BGCOLOR, '#ffcccc', FONTCOLOR, '#800000'"
                  . ", BORDERCOLOR, '#c00000'";
        }
        else {
          $warning = '';
        }
        $toolTip = "onmouseover=\"Tip('" . htmlspecialchars($this->title)
                . "', WIDTH, $this->tipWidth, TITLE, '$this->tipTitle'"
                . $warning . ", FIX, [this, $this->tipX, $this->tipY])\" onmouseout=\"UnTip()\"";
      }
      else {
        $toolTip = "";
      }
      return $toolTip;
    }

    function mkTagToTip() {
      if (!empty($this->title)) {
        $toolTip = "<span style='text-decoration:underline;cursor:pointer;' "
                . "onmouseover=\"Tip('{$this->title}')\" "
                . "onclick=\"TagToTip('{$this->name}', WIDTH, 300, "
                . "TITLE, '{$this->tipTitle}', STICKY, 1, CLOSEBTN, true, "
                . "CLICKCLOSE, true, FIX, [this, 15, 5])\" "
                . "onmouseout=\"UnTip();\">{$this->desc}</span>";
      }
      else {
        $toolTip = "";
      }
      return $toolTip;
    }

    static function getValues($ezOptions) {
      $options = array();
      foreach ($ezOptions as $k => $o) {
        $options[$k] = $o->get();
      }
      return $options;
    }

    static function setValues($options, &$ezOptions) {
      $error = '';
      foreach ($options as $k => $v) {
        if (isset($ezOptions[$k])) {
          if (!empty($_POST)) {
            // Suppress errors because $_POST won't be set for checkboxes and flags
            @$value = $_POST[$k];
          }
          else {
            $value = $v;
          }
          $ezOptions[$k]->set($value);
        }
        else {
          $error .= "Warning: Cannot find <code>ezOptions[$k]</code><br />";
        }
      }
      return $error;
    }

  }

  class EzCheckBox extends EzBaseOption {

    function EzCheckBox($name) {
      parent::EzBaseOption('checkbox', $name);
    }

    function render() {
      $this->preRender();
      echo "<input type='{$this->type}' id='{$this->name}' name='{$this->name}' ";
      if (!empty($this->style)) {
        echo " style='{$this->style}' ";
      }
      if ($this->value) {
        echo "checked='checked' ";
      }
      echo "/>{$this->desc}\n";
      $this->postRender();
    }

    function updateValue() {
      $this->value = isset($_POST[$this->name]);
    }

  }

  class EzRadioBox extends EzBaseOption { // Radiobox

    var $choices;

    function EzRadioBox($name) {
      parent::EzBaseOption('radio', $name);
    }

    function &addChoice($name, $value, $desc) {
      $subname = $this->name . '_' . $name;
      $this->choices[$subname] = new EzBaseOption('radio', $subname);
      $this->choices[$subname]->value = $value;
      $this->choices[$subname]->desc = $desc;
      return $this->choices[$subname];
    }

    function preRender() {
      $toolTip = $this->mkToolTip();
      if (!empty($this->labelWidth)) {
        $style = "style='display:inline-block;width:{$this->labelWidth}'";
      }
      else {
        $style = "";
      }
      echo "{$this->before}\n";
      echo "<span $style id='{$this->name}' $toolTip>{$this->desc} {$this->between}";
      echo "\n{$this->after}\n";
    }

    function postRender() {
      echo "</span>";
    }

    function render() {
      $this->preRender();
      if (!empty($this->choices)) {
        foreach ($this->choices as $k => $v) {
          echo $v->before, "\n";
          echo "<label for='{$k}'>\n";
          echo "<input type='{$v->type}' id='{$k}' name='{$this->name}' ";
          if ($this->value == $v->value) {
            echo "checked='checked'";
          }
          echo " value='{$v->value}' /> {$v->desc}\n</label>\n{$v->after}\n";
        }
      }
      $this->postRender();
    }

  }

  class EzSelect extends EzBaseOption { // Drop-down menu.

    var $choices = array();

    function EzSelect($name) {
      parent::EzBaseOption('select', $name);
    }

    function &addChoice($name, $value = '', $desc = '') {
      $subname = $this->name . '_' . $name;
      if (is_array($this->choices) && array_key_exists($subname, $this->choices)) {
        die("Fatal Error [addChoice]: New Choice $subname already exists "
                . "in {$this->name}");
      }
      $this->choices[$subname] = new EzBaseOption('choice', $subname);
      $this->choices[$subname]->value = $value;
      $this->choices[$subname]->desc = $desc;
      return $this->choices[$subname];
    }

    function render() {
      $this->preRender();
      echo "{$this->desc} {$this->between}\n"
      . "<select id='{$this->name}' name='{$this->name}' ";
      if (!empty($this->style)) {
        echo " style='{$this->style}'";
      }

      echo '>';
      if (!empty($this->choices)) {
        foreach ($this->choices as $k => $v) {
          echo "{$v->before}<option value='{$v->value}' ";
          if ($this->value == $v->value) {
            echo "selected='selected'";
          }
          echo ">{$v->desc}</option>{$v->after}\n";
        }
      }
      echo "</select>\n";
      $this->postRender();
    }

  }

  class EzMessage extends EzBaseOption { // Not an option, but a Message in the admin panel

    function EzMessage($name) { // constructor
      parent::EzBaseOption('', $name);
    }

    function render() {
      $this->preRender();
      if (!empty($this->value)) {
        echo $this->value, "\n";
      }
      if (!empty($this->desc)) {
        echo $this->desc, "\n";
      }
      $this->postRender();
    }

  }

  class EzHelpTag extends EzBaseOption { // Not an option, but to render help text

    function EzHelpTag($name) { // constructor
      parent::EzBaseOption('', $name);
    }

    function render() {
      $toolTip = $this->mkTagToTip();
      echo "{$this->before}\n";
      echo "$toolTip\n";
      echo "{$this->after}\n";
    }

  }

  class EzHelpPopUp extends EzBaseOption { // Not an option, but to popup a url

    function EzHelpPopUp($name) { // constructor
      parent::EzBaseOption('', $name);
    }

    function render() {
      echo "{$this->before}\n";
      echo "<span style='text-decoration:underline;cursor:pointer;' "
      . "onmouseover=\"Tip('{$this->title}')\" "
      . "onclick=\"popupwindow('{$this->name}', 'DontCare', 1000, 1024);"
      . "return false;\" onmouseout=\"UnTip();\">"
      . "{$this->desc}</span>\n";
      echo "{$this->after}\n";
    }

  }

  class EzTextArea extends EzBaseOption {

    function EzTextArea($name) {
      parent::EzBaseOption('textarea', $name);
      $this->width = 50;
      $this->height = 5;
      $this->style = "width: 96%; height: 180px;";
    }

    function render() {
      $this->preRender();
      echo "{$this->desc}<textarea cols='{$this->width}' rows='{$this->height}'"
      . " name='{$this->name}' id='{$this->name}' style='{$this->style}'>",
      stripslashes(htmlspecialchars($this->value)),
      "</textarea>\n";
      $this->postRender();
    }

  }

  class EzText extends EzBaseOption {

    function EzText($name) {
      parent::EzBaseOption('text', $name);
    }

    function render() {
      $this->preRender();
      echo "{$this->desc}{$this->between}"
      . "<input type='{$this->type}' id='{$this->name}' name='{$this->name}' ";
      if (!empty($this->style)) {
        echo " style='{$this->style}'";
      }
      echo " value='{$this->value}' />\n";
      $this->postRender();
    }

  }

  class EzSubmit extends EzBaseOption {

    var $onclick = '', $buttonStyle = 'primary';

    function EzSubmit($name) {
      parent::EzBaseOption('submit', $name);
      $this->value = $this->desc;
      $this->tipY = 10;
      $this->buttonStyle = 'primary';
    }

    function render() {
      $this->preRender();
      echo "<input type='{$this->type}' class='button button-{$this->buttonStyle}' id='{$this->name}' name='{$this->name}' ";
      if (!empty($this->style)) {
        echo " style='{$this->style}'";
      }
      if (!empty($this->onclick)) {
        echo " onclick=\"{$this->onclick}\"";
      }
      echo " value='{$this->desc}' />\n";
      $this->postRender();
    }

  }

  class EzColorPicker extends EzBaseOption { // ColorPickers

    function EzColorPicker($name) {
      parent::EzBaseOption('text', $name);
      $this->style = "border:0px solid;";
    }

    function render() {
      $this->preRender();
      echo $this->desc;
      echo "$this->between\n";
      echo "&nbsp;<input type='{$this->type}' id='{$this->name}' name='{$this->name}' ";
      if (!empty($this->style)) {
        echo " style='{$this->style}'";
      }

      echo " class=\"color {hash:false,caps:true,pickerFaceColor:'transparent',pickerFace:3,pickerBorder:0,pickerInsetColor:'black'}\"";
      echo " value='{$this->value}' />\n";
      $this->postRender();
    }

  }

  class EzOneTab extends EzBaseOption { // a tab in the mini-tab container, miniTab

    var $mTabOptions;

    function EzOneTab($name) {
      parent::EzBaseOption('onetab', $name);
      $this->mTabOptions = array();
    }

    function &addTabOption($type, $key) {
      $subname = $this->name . '_' . $key;
      if (is_array($this->mTabOptions) && array_key_exists($subname, $this->mTabOptions)) {
        die("Fatal Error [addTabOption]: New Option $subname already exists in {$this->name}");
      }
      if (class_exists($type)) { // Specialized class for this type of input
        $this->mTabOptions[$key] = new $type($subname);
      }
      else {
        $this->mTabOptions[$key] = new EzBaseOption($type, $subname);
      }
      return $this->mTabOptions[$key];
    }

    function render() {
      $toolTip = $this->mkToolTip();
      echo "{$this->before}\n";
      if (!empty($this->mTabOptions)) {
        foreach ($this->mTabOptions as $k => $o) {
          if (!empty($o)) {
            $o->render();
          }
        }
      }
      echo "{$this->after}\n";
    }

    function updateValue() {
      foreach ($this->mTabOptions as $option) {
        $option->updateValue();
      }
    }

  }

  class EzMiniTab extends EzBaseOption { // a mini-tab container.

    var $tabs;

    function EzMiniTab($name) {
      parent::EzBaseOption('minitab', $name);
      $this->tabs = array();
    }

    function &addTab($name) {
      $subname = $this->name . '-' . $name;
      if (array_key_exists($subname, $this->tabs)) {
        die("Fatal Error [addTab]: New Tab $subname already exists in {$this->name}");
      }
      $this->tabs[$subname] = new EzOneTab($subname);
      return $this->tabs[$subname];
    }

    function render() {
      $toolTip = $this->mkToolTip();
      echo "{$this->before}\n";
      echo "<div><ul class='tabs' name='tabs' id='{$this->name}_MiniTabs'>\n";
      $class = "class='current'";
      foreach ($this->tabs as $tab) {
        echo "<li><a href='#' $class id='mtab_{$tab->name}_link'>{$tab->desc}</a></li>\n";
        $class = '';
      }
      echo "</ul>\n</div><!-- of ul tabs -->\n";

      $current = '_current';
      foreach ($this->tabs as $tab) {
        $name = $tab->name;
        echo "<div class='tab$current' id='mtab_$name'>\n";
        $tab->render();
        echo "</div><!-- End: $name --> \n";
        $current = '';
      }
      echo "{$this->after}\n";
    }

    function updateValue() {
      foreach ($this->tabs as $tab) {
        $tab->updateValue();
      }
    }

  }

}

if (!class_exists("EzBasePlugin")) {

  class EzBasePlugin {

    var $prefix, $slug, $name, $plgDir, $plgURL, $plgFile, $adminMsg = '';
    var $ezAdmin, $myPlugins;
    var $isPro, $strPro;
    var $optionName, $options = array(), $ezOptions = array();
    static $queuedPopup = false;
    var $lang;

    function __construct($slug, $name, $file) {
      $this->slug = $slug;
      $this->plgDir = dirname($file);
      $this->plgURL = plugin_dir_url($file);
      $this->plgFile = $file;
      $this->name = $name;
      $this->isPro = is_dir("{$this->plgDir}/pro") && file_exists("{$this->plgDir}/pro/pro.php");
      $this->strPro = ' Lite';
      if ($this->isPro) {
        $this->strPro = ' Pro';
      }
      $this->init();
    }

    function __destruct() {

    }

    function mkDefaultOptions() {
      $defaultOptions = array(
          'kill_invites' => false,
          'kill_rating' => false,
          'kill_author' => false);
      return $defaultOptions;
    }

    function mkEzOptions() {
      $this->ezOptions['kill_invites'] = new EzBaseOption('hidden', 'kill_invites');
      $this->ezOptions['kill_rating'] = new EzBaseOption('hidden', 'kill_rating');

      $o = new EzCheckBox('kill_author');
      $o->title = __('If you find the author links and ads on the plugin admin page distracting or annoying, you can suppress them by checking this box. Please remember to save your options after checking.', 'easy-common');
      $o->desc = __('Kill author links on the admin page?', 'easy-common');
      $o->before = "<br /><b>";
      $o->after = "</b><br />";
      $this->ezOptions['kill_author'] = clone $o;
    }

    function resetOptions() {
      $defaultOptions = $this->mkDefaultOptions();
      update_option($this->optionName, $defaultOptions);
      $this->options = $defaultOptions;
      unset($_POST);
    }

    function cleanDB() {
      if (!empty($this->prefix)) {
        global $wpdb;
        $wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE '$this->prefix%'");
      }
    }

    function renderNonce() {
      wp_nonce_field("$this->prefix-submit", "$this->prefix-nonce");
    }

    function renderSubmitButtons($uninstall = false) {
      $update = new EzSubmit('saveChanges');
      $update->desc = __('Save Changes', 'easy-common');
      $update->title = __('Save the changes as specified above', 'easy-common');
      $update->tipWidth = 130;
      $update->tipTitle = $update->desc;

      $reset = new EzSubmit('resetOptions');
      $reset->desc = __('Reset Options', 'easy-common');
      $reset->title = __('This <strong>Reset Options</strong> button discards all your changes and loads the default options. This is your only warning!', 'easy-common');
      $reset->tipWidth = 150;
      $reset->tipWarning = true;

      $cleanDB = new EzSubmit('cleanDB');
      $cleanDB->desc = __('Clean Database', 'easy-common');
      $cleanDB->title = __('The <strong>Database Cleanup</strong> button discards <em>all</em> your settings for this plugin that you have saved so far. Please be careful with all database operations -- keep a backup.', 'easy-common');
      $cleanDB->tipWarning = true;

      if ($uninstall) {
        $uninstall = new EzSubmit('uninstall');
        $uninstall->desc = __('Uninstall', 'easy-common');
        $uninstall->title = __('The <b>Uninstall</b> button really kills this plugin after cleaning up all the options it wrote in your database. This is your only warning! Please be careful with all database operations -- keep a backup.', 'easy-common');
        $uninstall->tipWidth = 160;
        $uninstall->tipWarning = true;
      }

      $update->render();
      $reset->render();
      $cleanDB->render();
      if ($uninstall) {
        $uninstall->render();
      }
    }

    /**
     * Handles submits from admin page
     * @return bool Returns empty if $_POST is empty
     */
    function handleSubmits() {
      if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        return;
      }
      if (empty($this->prefix)) {
        $this->adminMsg .= "<div class='error'><p><strong>" .
                __('Cannot handle submits without specifying plugin!', 'easy-common') .
                "</strong></p></div>";
        return;
      }
      if (isset($_POST['disableGoogleTran'])) {
        update_option("killGoogleTran-$this->prefix", true);
        return;
      }
      if (isset($_POST['enableGoogleTran'])) {
        update_option("killGoogleTran-$this->prefix", false);
        return;
      }
      if (!check_admin_referer("$this->prefix-submit", "$this->prefix-nonce")) {
        return;
      }

      if (isset($_POST['saveChanges'])) {
        $this->mkEzOptions();
        $this->setOptionValues();

        foreach ($this->ezOptions as $k => $o) {
          if (isset($this->options[$k])) {
            $this->options[$k] = $o->get();
          }
          else {
            if (defined("EZ_DEBUG")) {
              echo "<div class='error'>Warning: <code>option[$k]</code> is not defined, but <code>ezOption[$k]</code> exists!</div>";
            }
          }
        }

        update_option($this->optionName, $this->options);

        $this->adminMsg .= "<div class='updated'><p><strong>" .
                __('Settings Updated.', 'easy-common') .
                "</strong></p></div>";
      }
      if (isset($_POST['resetOptions'])) {
        $this->resetOptions();
        $this->adminMsg .= "<div class='updated'><p><strong>" .
                __('Ok, all your settings have been discarded!', 'easy-common') .
                "</strong></p></div>";
      }
      if (isset($_POST['cleanDB']) || isset($_POST['uninstall'])) {
        $this->resetOptions();
        $this->cleanDB($this->prefix);
        $this->adminMsg .= "<div class='updated'><p><strong>" .
                __('Database has been cleaned. All your options for this plugin (for all themes) have been removed.', 'easy-common') .
                "</strong></p> </div>";

        if (isset($_POST['uninstall'])) {
          remove_action('admin_menu', "{$this->prefix}_ap");
          $this->adminMsg .= "<div class='updated'><p><strong>" .
                  __('This plugin can be deactivated now. ', 'easy-common') .
                  "<a href='plugins.php'>" .
                  __('Go to Plugins', 'easy-common') .
                  "</a>.</strong></p></div>";
        }
      }
    }

    function printProSection() {

    }

    function setOptionValues() {
      $error = EzBaseOption::setValues($this->options, $this->ezOptions);
      if (defined("EZ_DEBUG") && !empty($error)) {
        echo "<div class='error'>$error</div>";
      }
    }

    function mkEzAdmin() {
      require($this->plgDir . '/myPlugins.php');
      $slug = $this->slug;
      $plg = $this->myPlugins[$slug];
      $plgURL = $this->plgURL;
      if ($this->isPro || file_exists($this->plgDir . '/EzAdminPro.php')) {
        require_once($this->plgDir . '/EzAdminPro.php');
        $this->ezAdmin = new EzAdminPro($plg, $slug, $plgURL);
      }
      else {
        require_once($this->plgDir . '/EzAdmin.php');
        $this->ezAdmin = new EzAdmin($plg, $slug, $plgURL);
      }
      if (!empty($this->options['kill_author']) || isset($_POST['kill_author'])) {
        $this->ezAdmin->killAuthor = true;
      }
      $this->ezAdmin->plgFile = $this->plgFile;
      return $this->ezAdmin;
    }

    function info($hide = true) {
      if (!function_exists('get_plugin_data')) {
        require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
      }
      $plugin_data = get_plugin_data($this->plgFile);
      $str = "{$plugin_data['Name']} V{$plugin_data['Version']}";
      if ($hide) {
        $str = "<!-- $str -->";
      }
      return $str;
    }

    function printAdminPage() {
      $this->mkEzAdmin();
      return $this->ezAdmin;
    }

    function adminMenu() {
      $mName = "$this->name $this->strPro";
      $adminPage = add_options_page($mName, $mName, 'activate_plugins', basename($this->plgFile), array($this, 'printAdminPage'));
      add_action('load-' . $adminPage, array($this, 'load'));
    }

    function pluginActionLinks($links, $file) {
      if ($file == plugin_basename($this->plgFile)) {
        $settingsLink = "<a href='options-general.php?page="
                . basename($this->plgFile)
                . "'>Settings</a>";
        array_unshift($links, $settingsLink);
        if (empty($this->isPro) && !empty($this->slug)) {
          $buyLink = "<a href='http://buy.thulasidas.com/$this->slug' class='popup' target='_top' title='Get the Pro version of this plugin for more features!'><b style='color:red'>Go <i>Pro</i>!</b></a>";
          array_unshift($links, $buyLink);
        }
      }
      return $links;
    }

    function init() { // Add admin page etc. Overrirde if needed.
      add_action('admin_menu', array($this, 'adminMenu'));
      add_filter('plugin_action_links', array($this, 'pluginActionLinks'), -10, 2);
      add_action('admin_enqueue_scripts', array($this, 'adminEnqueueScripts'));
    }

    function adminEnqueueScripts($hook) {
      if (empty(self::$queuedPopup) && $hook == 'plugins.php') {
        wp_enqueue_script('jquery-ui-droppable');
        add_action('admin_print_footer_scripts', array($this, 'printPopupScript'));
        self::$queuedPopup = true;
      }
    }

    function adminPrintFooterScripts() {
      $this->printPopupScript();
      $this->printGoogleTran();
    }

    function printPopupScript() {
      ?>
      <script>
        function popupwindow(url, title, w, h) {
          return ezPopUp(url, title, w, h);
        }
        function ezPopUp(url, title, w, h) {
          var wLeft = window.screenLeft ? window.screenLeft : window.screenX;
          var wTop = window.screenTop ? window.screenTop : window.screenY;
          var left = wLeft + (window.innerWidth / 2) - (w / 2);
          var top = wTop + (window.innerHeight / 2) - (h / 2);
          window.open(url, title, 'toolbar=no, location=no, directories=no, status=no, menubar=no, scrollbars=no, resizable=no, copyhistory=no, width=' + w + ', height=' + h + ', top=' + top + ', left=' + left);
          return true;
        }
        jQuery(document).ready(function () {
          jQuery('body').on('click', ".popup", function (e) {
            e.preventDefault();
            var url = jQuery(this).attr('href');
            var title = "Window";
            var w = 1024;
            var h = 728;
            if (jQuery(this).attr('data-height')) {
              h = jQuery(this).attr('data-height');
              w = 1000;
            }
            if (jQuery(this).attr('data-width')) {
              w = jQuery(this).attr('data-width');
            }
            return ezPopUp(url, title, w, h);
          });
        });
      </script>
      <?php
    }

    function adminPrintStyles() {
      ?>
      <style type="text/css">
        /* Google Translator top bar and tooltips */
        .goog-te-banner-frame.skiptranslate {display: none !important;}
        body { top: 0px !important; }
        .goog-tooltip {
          display: none !important;
        }
        .goog-tooltip:hover {
          display: none !important;
        }
        .goog-text-highlight {
          background-color: transparent !important;
          border: none !important;
          box-shadow: none !important;
        }
      </style>
      <?php
    }

    function setGoogleTranCookie() {
      if (!empty($_GET['lang'])) {
        $lang = $_GET['lang'];
      }
      else {
        $locale = get_locale();
        $lang = substr($locale, 0, 2);
      }
      $lang = strtolower($lang);
      if ($lang == 'en') {
        setcookie("googtrans", "", 1, '/');
      }
      else {
        setcookie("googtrans", "/en/$lang", time() + 300, '/');
      }
      $this->lang = $lang;
    }

    function printGoogleTran() {
      $killed = get_option("killGoogleTran-$this->prefix");
      if (empty($killed)) {
        if ($this->lang != 'en') {
          ?>
          <div class="updated" style="padding:10px;margin:10px">
            View this admin page in your language: &nbsp;
            <span id='GoogleTranslatorWidget' style='display:inline-block'>
              <span id='google_translate_element'></span>
            </span> &nbsp;
            Disable it if you have trouble saving options.
            <form method="POST" style="display:inline;float:right">
              <input type="submit" class="button-primary" style="display: inline;" name="disableGoogleTran" value="Disable Translator">
            </form>
          </div>
          <!-- Google translator -->
          <script type='text/javascript'>
            function googleTranslateElementInit() {
              new google.translate.TranslateElement({pageLanguage: 'en', layout: google.translate.TranslateElement.InlineLayout.SIMPLE}, 'google_translate_element');
            }
          </script>
          <script type='text/javascript' src='//translate.google.com/translate_a/element.js?cb=googleTranslateElementInit'></script>
          <?php
        }
      }
      else {
        ?>
        <div class="updated" style="padding:15px;margin:10px">
          To view this admin page in your language using Google Translate, please enable it. &nbsp;
          <form method="POST" style="display:inline;float:right">
            <input type="submit" class="button-primary" style="display: inline;" name="enableGoogleTran" value="Enable Translator">
          </form>
        </div>
        <?php
      }
    }

    function load() { // Runs inits specific to the admin page (JS/CSS etc.)
      wp_register_script('wzToolTip', "$this->plgURL/wz_tooltip.js", array(), '2.0', true);
      wp_enqueue_script('wzToolTip');
      add_action('admin_print_footer_scripts', array($this, 'adminPrintFooterScripts'));
      add_action('admin_print_styles', array($this, 'adminPrintStyles'));
      $this->setGoogleTranCookie();
    }

  }

}
