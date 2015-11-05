<?php

if (!defined('IN_SUSTC')) {
  exit;
}

define('SC_DBHOST', 'localhost');
define('SC_DBUSER', 'sustc');
define('SC_DBPW', 'DBPASSWORD');
define('SC_DBCHARSET', 'utf8');
define('SC_DBCONNECT', 1);
define('SC_DBTABLEPRE', 'sustc_');
define('SC_DBNAME', 'sustc');

define('SC_PRIVATEKEY', 'SCPKEY');

define('USER_API', 'http://localhost:3005/api/user');
define('USER_API_KEY', 'UAKEYhere');
define('USER_COOKIE_KEY', 'sc_user');

?>
