<?php

define('IN_SUSTC', true);
define('SC_ROOT', substr(__FILE__, 0, -13)); //strlen('clear/api.php')

require_once SC_ROOT.'src/config.php';
require_once SC_ROOT.'src/base.php';
require_once SC_ROOT.'src/lib/session.php';

$session = new session();
$action = isset($_GET['action']) ? $_GET['action'] : '';

if ($action == 'get') {

  if (isset($_SESSION['done']) && $_SESSION['done']) {
    exit('[]');
  }
  
  exit('[{"title":"已经结束","order":1,"items":[]}]');

  $b = new base();

  $clear_user = $b->db->fetch_all('SELECT * FROM `sustc_clear_user` WHERE `disabled` = 0;');
  $items = array();
  $classcount = 0;
  foreach ($clear_user as $key => $value) {
    $iclass = -1;
    for ($i = 0; $i < $classcount; $i++) {
      if ($items[$i] && $items[$i]['title'] == $value['class']) {
        $iclass = $i;
        break;
      }
    }
    if ($iclass == -1) {
      $iclass = $classcount;
      $classcount++;
      $items[$iclass] = array();
      $items[$iclass]['title'] = $value['class'];
      $items[$iclass]['order'] = $iclass + 1;
      $items[$iclass]['items'] = array();
    }
    array_push($items[$iclass]['items'], array(
      'title' => $value['realname'],
      'order' => count($items[$iclass]['items']),
      'value' => $value['studentid']
    ));
  }
  echo json_encode($items, JSON_UNESCAPED_UNICODE);
} else if ($action == 'done') {

  $r = array('errno' => 2, 'msg' => 'Value Error');
  $value = isset($_POST['value']) ? $_POST['value'] : FALSE;
  if (true) {
  //if (isset($_SESSION['done']) && $_SESSION['done']) {
    $r['errno'] = 3;
    $r['msg'] = 'Already finish';
  } else if ($value) {
    $v = json_decode($value, TRUE);
    if ($v) {
      $vcount = count($v);
      $rstudentids = array();
      for ($i = 0; $i < $vcount; $i++) {
        $studentid = intval($v[$i]);
        if ($studentid > 0) {
          array_push($rstudentids, $studentid);
        }
      }
      $studentids = array_unique($rstudentids);
      $knowcount = count($studentids);
      if ($knowcount > 0) {
        $b = new base();
        $clear_user = $b->db->query('UPDATE `sustc_clear_user` SET `famous` = `famous` + 1 WHERE `studentid` IN ('
          .$b->implode($studentids).');');
      }
      $_SESSION['done'] = TRUE;
      $_SESSION['knowcount'] = $knowcount;
      $r['errno'] = 0;
      $r['msg'] = 'Succeed';
    }
  }
  
  echo json_encode($r, JSON_UNESCAPED_UNICODE);
} else if ($action == 'top') {
  $b = new base();

  $top_user = $b->db->fetch_all('SELECT * FROM `sustc_clear_user` WHERE `disabled` = 0 ORDER BY `famous` DESC, `studentid` ASC LIMIT 10;');
  /*$count = count($top_user);
  for ($i = 0; $i < $count; $i++) {
    $top_user[$i]['rank'] = $i + 1;
  }*/
  echo json_encode($top_user, JSON_UNESCAPED_UNICODE);

} else if ($action == 'knowcount') {
  $r = array('errno' => 5);
  if (isset($_SESSION['knowcount'])) {
    $r['errno'] = 0;
    $r['knowcount'] = $_SESSION['knowcount'];
  }
  echo json_encode($r, JSON_UNESCAPED_UNICODE);
} else {
  $r = array('errno' => 1, 'msg' => 'Unknow action');
  echo json_encode($r, JSON_UNESCAPED_UNICODE);
}

?>