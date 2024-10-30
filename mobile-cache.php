<?php

/*
  Plugin Name: Easy Cache
  Plugin URI: http://www.thulasidas.com/plugins/easy-cache
  Description: Easy Cache (formerly Mobile Cache) is a high-performance, yet simple-to-use caching plugin. It serves static pages optimized to the target device (mobile or desktop browsers).
  Version: 1.60
  Author: Manoj Thulasidas
  Author URI: http://www.thulasidas.com
 */

/*
  Copyright (C) 2008 www.ads-ez.com

  This program is free software; you can redistribute it and/or modify
  it under the terms of the GNU General Public License as published by
  the Free Software Foundation; either version 3 of the License, or
  (at your option) any later version.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

if (!class_exists("MobileCache")) {

  require_once 'MobileCacheBase.php';

  class MobileCache extends MobileCacheBase {

    function __construct() {
      parent::__construct(__FILE__);
    }

  }

}

//End Class MobileCache

if (class_exists("MobileCache")) {
  $mobileCache = new MobileCache();
}