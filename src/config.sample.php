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

// mail receiver
define('EMAIL_ADDRESS', 'receiver@domain.com'); //警告被发送到的邮件地址
define('EMAIL_NAME', 'Test');				//收件人称谓,随意

// mail sender
define('MAILER_HOST', 'smtp.exmail.qq.com');//发信SMTP服务器（默认端口25）
define('MAILER_USERNAME', 'notifications@sustc.us');
define('MAILER_PASSWORD', 'password');

?>
