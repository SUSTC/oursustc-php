<?php

if (!defined('IN_SUSTC')) {
  exit;
}

require_once SC_ROOT.'src/inc/utf.php';
require_once SC_ROOT.'src/lib/session.php';
require_once SC_ROOT.'src/func/user.php';

class user
{
  var $core;

  var $uid;
  var $username;
  var $email;

  var $status;

  function user($core) {
    $this->core = $core;
    $this->init_user();
  }

  function call_checklogin() {

    if (isset($_COOKIE[USER_COOKIE_KEY])) {
      $user_cookie = $_COOKIE[USER_COOKIE_KEY];
      if ($user_cookie) {
        $ch = curl_init();
        $scookie = USER_COOKIE_KEY.'='.urlencode($_COOKIE[USER_COOKIE_KEY]);

        curl_setopt($ch, CURLOPT_URL, USER_API.'/checklogin?cookies='.urlencode($scookie));
        //curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        $data = curl_exec($ch);
        curl_close($ch);

        if ($data) {
          $r = json_decode($data, TRUE);
          if ($r['islogin']) {
            $r['user']['csrf'] = $r['csrf'];
            return $r['user'];
          }
        }
      }
    }

    return false;
  }

  function init_user() {
    global $_G;

    $user = $this->call_checklogin();

    /*if (isset($_SESSION['user']) && $_SESSION['user']['uid'] > 0) {
      $this->uid = $_SESSION['user']['uid'];
      $this->username = $_SESSION['user']['username'];
      $this->email = $_SESSION['user']['email'];*/

    if ($user) {
      $selfUser = DB::fetch_first(
        "SELECT * FROM ".DB::table('user_profile')." WHERE `studentid` = ".intval($user['studentid']).";");
      if (!$selfUser) {
        $uid = $this->add($user['studentid'], '', $user['email'], $user['realname'], $user['studentid']);
        $selfUser = array('uid' => $uid);
      }

      $this->csrf = $user['csrf'];
      $_G['usercsrf'] = $user['csrf'];

      $this->uid = $selfUser['uid'];
      $this->realname = $user['realname'];
      $this->username = $user['showname'];
      $this->email = $user['email'];
      $this->new_notification = $user['new_notification'];

      $this->status = DB::fetch_first("SELECT * FROM ".DB::table('user_status')." WHERE `uid` = ".$this->uid.";");
    } else {
      $this->uid = 0;
    }

    $_G['uid'] = $this->uid;
  }

  function islogin() {
    return $this->uid > 0;
  }

  function setlogin($u) {
    if (isset($_SESSION['user'])) {
      unset($_SESSION['user']);
    }
    if (isset($u) && $u['uid'] > 0) {

      $fields = array('profile', 'setting');
      $fd = array();

      foreach ($fields as $value) {
        $fd[$value] = $this->core->db->fetch_first("SELECT * FROM `sustc_user_".$value."` WHERE `uid` = ".$u['uid'].";");
      }
      
      $this->uid = $u['uid'];
      $this->username = $u['username'];
      $this->email = $u['email'];

      $_SESSION['user'] = array(
        'uid' => $u['uid'],
        'username' => $u['username'],
        'email' => $u['email'],
        'profile' => $fd['profile'],
        'setting' => $fd['setting']
      );

      $this->status = DB::fetch_first("SELECT * FROM ".DB::table('user_status')." WHERE `uid` = ".$u['uid'].";");

      unset($fd);
      unset($u);
    }
  }

  function login($username, $password) {
    $username_clean = utf8_clean_string($username);
    $u = $this->core->db->fetch_first("SELECT * FROM `sustc_user` WHERE `username_clean` = \"$username_clean\";");

    $errcode = 1;
    if ($u) {
      if (phpbb_check_hash($password, $u['password'])) {
        $this->setlogin($u);
        $errcode = 0;
      } else {
        $errcode = 2;
      }
      unset($u);
    }
    return $errcode;
  }

  function add($username, $password, $email, $realname, $studentid, $balance = 0) {
    $username_clean = utf8_clean_string($username);
    $password_hash = '';
    if ($password) {
      $password_hash = phpbb_hash($password);
    }
    unset($password);

    $email = strtolower(trim($email));
    
    $u = array(
      'username' => $username,
      'username_clean' => $username_clean,
      'password' => $password_hash,
      'email' => $email
    );

    $uid = DB::insert('user', $u, true);
    if ($uid > 0) {
      $profile = array('uid' => $uid, 'realname' => $realname, 'studentid' => $studentid);
      $setting = array('uid' => $uid);
      $status = array('uid' => $uid, 'group_id' => 1, 'status' => 1, 'balance' => $balance);
      DB::insert('user_profile', $profile);
      DB::insert('user_setting', $setting);
      DB::insert('user_status', $status);
    }
    return $uid;
  }

  function logout() {
    $this->setlogin();
  }

  function update($field, $data) {
    if (!in_array($field, array('', 'profile', 'setting', 'status'))) {
      return;
    }
  }
}

?>