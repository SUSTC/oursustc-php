<?php

define('IN_SUSTC', true);
define('SC_ROOT', substr(__FILE__, 0, -8)); //strlen('user.php')

require_once SC_ROOT.'src/config.php';
require_once SC_ROOT.'src/core.php';
require_once libfile('class/alipay');

$sustc = new core();

$action = isset($_GET['action']) ? $_GET['action'] : '';
$redirect = isset($_GET['redirect']) ? 
  $_GET['redirect'] : (isset($_POST['redirect']) ? $_POST['redirect'] : '');

$err = array('code' => 0);

if ($action == 'signin') {
  if ($sustc->user->islogin()) {
    if (!$redirect) {
      $redirect = '/';
    }
    dredirect($redirect);
  }
  if (is_post()) {
    $err['code'] = -1;
    if (isset($_POST['formhash'])
        && $sustc->security->check_formhash($_POST['formhash'])) {
      $userfield = $_POST['user'];
      if (isset($userfield['username']) && isset($userfield['password'])) {
        $err['code'] = $sustc->user->login($userfield['username'], $userfield['password']);
        if ($err['code'] == 0) {
          if (!$redirect) {
            $redirect = '/';
          }
          dredirect($redirect);
        }
      }
    }
  }
  if (isset($_GET['logout']) && $_GET['logout']) {
    $err['logout'] = true;
  }
  include template('user/signin');
} else if ($action == 'reset') {
  echo 'WIP!<br>Please contact admin.';
} else if ($action == 'security') {
  if (!$sustc->user->islogin()) {
    dredirect('/user/signin?redirect=/user/security');
  }
  if (is_post()) {
    $err['code'] = -1;
    $err['success'] = false;
    if (isset($_POST['formhash'])
        && $sustc->security->check_formhash($_POST['formhash'])) {
      $userfield = $_POST['user'];
      if (!isset($userfield['password']) || !$userfield['password']) {
        $err['code'] = 1;
      } else if ((isset($userfield['password']) && $userfield['password'])
          && ((isset($userfield['email']) && $userfield['email'])
            || (isset($userfield['new_password']) && $userfield['new_password']))) {
        if (isset($userfield['new_password'])
            && $userfield['new_password']
            && ($userfield['new_password'] != $userfield['new_password2'])) {
          $err['code'] = 2;
        } else {
          $u = DB::fetch_first(
            'SELECT * FROM '.DB::table('user')
            .' WHERE '.DB::implode(array('uid' => $sustc->user->uid)));
          if ($u) {
            if (phpbb_check_hash($userfield['password'], $u['password'])) {
              $updata = array();
              $err['changed'] = array(
                'password' => false,
                'email' => false,
              );
              if (isset($userfield['email']) && $userfield['email'] && $userfield['email'] != $u['email']) {
                $updata['email'] = strtolower(trim($userfield['email']));
                $_SESSION['user']['email'] = $updata['email'];
                $err['changed']['email'] = true;
              }
              if (isset($userfield['new_password']) && $userfield['new_password']) {
                global $_G;
                $_G['config'] = array(
                  'rand_seed' => rand(),
                  'rand_seed_last_update' => TIMESTAMP
                );
                $updata['password'] = phpbb_hash($userfield['new_password']);
                $err['changed']['password'] = true;
              }
              if ($updata) {
                DB::update('user', $updata, array('uid' => $sustc->user->uid));
                $err['code'] = 0;
                $err['success'] = true;
              } else {
                $err['code'] = 3;
              }
            } else {
              $err['code'] = 1;
            }
            unset($userfield);
            unset($u);
          }
        }
      } else {
        $err['code'] = 3;
      }
    }
  }
  include template('user/security');
} else if ($action == 'logout') {
  if ($sustc->user->islogin()
      && $sustc->security->check_formhash($_GET['formhash'])) {
    $sustc->user->logout();
    dredirect('/user/signin?logout=1'.($redirect ? '&redirect='.$redirect : ''));
  }
  if (!$redirect) {
    $redirect = '/';
  }
  dredirect($redirect);
} else if ($action == 'add') {
  if (!$sustc->user->islogin()) {
    dredirect('/user/signin?redirect=/user/add');
  }
  $err['success'] = false;
  $err['code'] = 2;
  $withpower = false;
  if ($sustc->user->islogin() && $sustc->user->status['group_id'] >= 5) {
    $withpower = true;
    unset($err['success']);
  }
  if ($withpower && is_post()) {
    $err['success'] = false;
    $err['code'] = -1;

    global $_G;
    $_G['config'] = array(
      'rand_seed' => rand(),
      'rand_seed_last_update' => TIMESTAMP
    );
    $userfield = $_POST['user'];
    if (isset($userfield['username'])
            && isset($userfield['balance'])) {
      $balance = intval($userfield['balance']);
      if ($balance >= 0) {
        $pwd = gen_key(12);
        $new_uid = $sustc->user->add($userfield['username'], $pwd, '', $userfield['realname'], $balance);
        if ($new_uid) {
          $err['added'] = array('password' => $pwd);
          $err['success'] = true;
          $err['code'] = 0;
        } else {
          $err['code'] = 1;
        }
      }
    }
  }
  include template('user/add');
} else if ($action == 'deposit') {
  $do = isset($_GET['do']) ? $_GET['do'] : false;
  if ($do) {
    switch ($do) {
    case 'notify':
      $trade = new Alipay();
      $trade->notify();
      break;
    case 'return':
      $trade = new Alipay();
      unset($_GET['action']);
      unset($_GET['do']);
      $result = $trade->callback();
      include template('user/deposit_return');
      break;
    }
    exit();
  }
  if (!$sustc->user->islogin()) {
    dredirect('/user/signin?redirect=/user/deposit');
  }
  if (is_post()) {
    $err['code'] = -1;
    $err['success'] = false;
    $depo = isset($_POST['deposit']) ? $_POST['deposit'] : false;
    if ($depo && isset($_POST['formhash'])
        && $sustc->security->check_formhash($_POST['formhash'])) {
      $price = isset($depo['price']) ? intval($depo['price']) : 0;
      if (isset($depo['method']) && $depo['method'] == 'alipay'
          && in_array($price, array(500, 1000, 2000, 5000))) {
        $trade = new Alipay();
        //echo '<!DOCTYPE html><html><head></head><body>';
        $trade_html = $trade->create_trade($price);
        //echo '</body></html>';
        include template('user/deposit_call');
        exit();
      }
    }
  }
  $err['disabled'] = isset($_GET['disabled']) ?
      (intval($_GET['disabled']) != 0 ? TRUE : FALSE) : FALSE;
  include template('user/deposit');
} else {
  echo 'WIP';
}


?>