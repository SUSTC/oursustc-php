<?php

if (!defined('IN_SUSTC')) {
  exit;
}

require_once SC_ROOT.'src/inc/utf.php';
require_once SC_ROOT.'src/lib/session.php';

require_once SC_ROOT.'src/base.php';
require_once SC_ROOT.'src/security.php';
require_once SC_ROOT.'src/discuz_database.php';
require_once SC_ROOT.'src/user.php';

require_once SC_ROOT.'src/func/common.php';

class core {

  var $session;
  var $user;
  var $base;
  var $db;
  var $security;

  function core() {
    $this->init_env();
    $this->init_var();
    $this->init_input();
    $this->init_db();
    $this->init_user();
  }

  function init_env() {
    global $_G;

    $_G = array(
      'starttime' => microtime(true),
      'setting' => array(
        'dateformat' => 'Y-m-d',
        'timeformat' => 'H:i',
        'timeoffset' => 8,
        'dateconvert' => true
      ),
      'member' => array(
        'timeoffset' => 8,
      ),
    );

    if (PHP_VERSION < '5.3.0') {
      set_magic_quotes_runtime(0);
    }

    define('MAGIC_QUOTES_GPC', function_exists('get_magic_quotes_gpc') && get_magic_quotes_gpc());

    init_utf_tools();
  }

  function init_var() {
    global $_G;

    $this->session = new session();
    $this->base = new base();
    $this->security = new security();

    $_G['timestamp'] = TIMESTAMP;

    $needsetlocale = false;
    $locale = '';
    $cookieloc = '';
    if (isset($_COOKIE['locale'])) {
      $cookieloc = $_COOKIE['locale'];
    }
    if (isset($_GET['locale'])) {
      $locale = $_GET['locale'];
      if ($locale != $cookieloc) {
        $needsetlocale = true;
      }
    } else {
      $locale = $cookieloc;
    }
    if (!in_array($locale, array('en', 'zh_CN'))) {
      $locale = 'zh_CN';  //def use zh_CN
      $needsetlocale = true;
    }
    if ($needsetlocale) {
      setcookie('locale', $locale, TIMESTAMP + (30 * 24 * 60 * 60));
    }
    
    $_G['locale'] = $locale;

  }

  function init_input() {
    if (MAGIC_QUOTES_GPC) {
      $_GET = dstripslashes($_GET);
      $_POST = dstripslashes($_POST);
      $_COOKIE = dstripslashes($_COOKIE);
    }
  }

  function init_db() {
    $this->db = $this->base->db;
    DB::set($this->db);
  }


  function init_user() {
    $this->user = new user($this);
  }
}

class DB extends discuz_database {
  public static function set($db) {
    self::$db = $db;
  }
}

?>