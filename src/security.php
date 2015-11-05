<?php

!defined('IN_SUSTC') && exit('Access Denied');

require_once SC_ROOT.'src/config.php';
require_once SC_ROOT.'src/lib/session.php';

class security {

  var $formhash;

  function security() {
    $this->init_var();
  }

  function init_var() {
    global $_G;

    $sid = session_id();
    $this->formhash = substr(md5($sid . SC_PRIVATEKEY), 3, 8);

    $_G['formhash'] = $this->formhash;
  }

  function check_formhash($formhash) {
    return $this->formhash == $formhash;
  }

}

?>