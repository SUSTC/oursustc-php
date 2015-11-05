<?php

define('IN_SUSTC', true);
define('SC_ROOT', substr(__FILE__, 0, -9)); //strlen('index.php')

require_once SC_ROOT.'src/config.php';
require_once SC_ROOT.'src/core.php';

$sustc = new core();

if (!$sustc->user->islogin()) {
  dredirect('/user/signin');
}

include template('index/news');

?>