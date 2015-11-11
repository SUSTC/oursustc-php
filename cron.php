<?php

define('IN_SUSTC', true);
define('SC_ROOT', substr(__FILE__, 0, -8)); //strlen('cron.php')

define('CURSCRIPT', 'print');

include SC_ROOT.'./print/remind_maintain.php';

?>
