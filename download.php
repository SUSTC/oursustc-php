<?php

define('IN_SUSTC', true);
define('SC_ROOT', substr(__FILE__, 0, -12)); //strlen('download.php')

require_once SC_ROOT.'src/config.php';
require_once SC_ROOT.'src/core.php';

$sustc = new core();

$fid = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($fid) {
  $attach = DB::fetch_first('SELECT * FROM '.DB::table('attachment').' WHERE '.DB::implode(array('id' => $fid)));
  dredirect('/data/attach/'.$attach['savepath'].$attach['id'].'.'.$attach['ext']);
}

?>