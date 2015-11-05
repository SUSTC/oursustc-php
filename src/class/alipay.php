<?php

/**
 *      (C)2001-2099 SUSTC-IT
 *      This is NOT a freeware, use is subject to license terms
 *
 *      $Id: class/alipay.php 0 2014-04-25 tengattack $
 */

if(!defined('IN_SUSTC')) {
  exit('Access Denied');
}

require_once libfile('lib/deposit');
require_once libfile('alipay_submit.class', 'lib/alipay/lib');
require_once libfile('alipay_notify.class', 'lib/alipay/lib');

define('SELLER_EMAIL', '651611473@qq.com');
define('SITE_URL', 'https://sustc.us');

define('WAIT_BUYER_PAY', 1);
define('WAIT_BUYER_CONFIRM', 3);
define('TRADE_UNSUPPORTED', 4);
define('TRADE_FINISHED', 5);
define('TRADE_CLOSED', 6);

class Alipay extends Deposit {

  var $alipay_config;

  function Alipay() {
    include libfile('alipay.config', 'lib/alipay');
    $this->alipay_config = $alipay_config;
  }

  function log($str) {
    $fh = fopen(SC_ROOT.'.\data\deposit.log', "a");
    if ($fh) {
      $strdate = date('[Y-m-d H:i:s]', TIMESTAMP);
      fwrite($fh, $strdate.' '.$str."\n");
      fclose($fh);
    }
  }

  function callback() {
    //计算得出通知验证结果
    $alipayNotify = new AlipayNotify($this->alipay_config);
    $verify_result = $alipayNotify->verifyReturn();

    $this->log('deposit/return '
      .'verify:'.($verify_result ? 'true' : 'false').' '
      .json_encode($_GET, JSON_UNESCAPED_UNICODE));

    $result = array('err' => -1);

    if($verify_result) {//验证成功
      //商户订单号
      $out_trade_no = $_GET['out_trade_no'];

      //支付宝交易号
      $trade_no = $_GET['trade_no'];

      //交易状态
      $trade_status = $_GET['trade_status'];

      $deposit_id = intval($out_trade_no);

      if (!$deposit_id) {
        $result['err'] = -2;
        return $result;
      }

      $record = DB::fetch_first('SELECT * FROM '.DB::table('user_deposit')
        .' WHERE '.DB::implode(array('id' => $deposit_id)));

      if (!$record) {
        $result['err'] = -3;
        return $result;
      }

      $result['err'] = 0;
      $result['record'] = $record;
      $result['status'] = $trade_status;

      switch ($trade_status) {
      case 'WAIT_SELLER_SEND_GOODS':
        break;
      case 'TRADE_FINISHED':
        break;
      }
    }
    return $result;
  }

  function notify() {
    //计算得出通知验证结果
    $alipayNotify = new AlipayNotify($this->alipay_config);
    $verify_result = $alipayNotify->verifyNotify();

    $this->log('deposit/notify '
      .'verify:'.($verify_result ? 'true' : 'false').' '
      .json_encode($_POST, JSON_UNESCAPED_UNICODE));

    if($verify_result) {//验证成功
      //商户订单号
      $out_trade_no = $_POST['out_trade_no'];

      //支付宝交易号
      $trade_no = $_POST['trade_no'];

      //交易状态
      $trade_status = $_POST['trade_status'];

      $deposit_id = intval($out_trade_no);

      if (!$deposit_id) {
        echo "fail";
        return FALSE;
      }

      $record = DB::fetch_first('SELECT * FROM '.DB::table('user_deposit')
        .' WHERE '.DB::implode(array('id' => $deposit_id)));

      if (!$record) {
        echo "fail";
        return FALSE;
      }

      if ($record['status'] == TRADE_FINISHED) {
        echo "success";
        return TRUE;
      }

      switch ($trade_status) {
      case 'WAIT_BUYER_PAY':
        DB::update('user_deposit',
          array('trade_no' => $trade_no, 'status' => WAIT_BUYER_PAY, 'updatetime' => TIMESTAMP),
          array('id' => $deposit_id));
        break;
      case 'TRADE_CLOSED':
        DB::update('user_deposit',
          array('status' => TRADE_CLOSED, 'updatetime' => TIMESTAMP),
          array('id' => $deposit_id));
        break;
      case 'WAIT_SELLER_SEND_GOODS':
        $this->send_goods($record, $trade_no);
        DB::update('user_deposit',
          array('status' => WAIT_BUYER_CONFIRM, 'updatetime' => TIMESTAMP),
          array('id' => $deposit_id));
        break;
      case 'WAIT_BUYER_CONFIRM_GOODS':
        DB::update('user_deposit',
          array('status' => WAIT_BUYER_CONFIRM, 'updatetime' => TIMESTAMP),
          array('id' => $deposit_id));
        break;
      case 'TRADE_SUCCESS':
        DB::update('user_deposit',
          array('status' => TRADE_UNSUPPORTED, 'updatetime' => TIMESTAMP),
          array('id' => $deposit_id));
        break;
      case 'TRADE_FINISHED':
        DB::update('user_deposit',
          array('status' => TRADE_FINISHED, 'updatetime' => TIMESTAMP),
          array('id' => $deposit_id));
        if ($record && $record['uid']) {
          DB::query('UPDATE '.DB::table('user_status')
            .' SET '.DB::quote_field('balance').' = '.DB::quote_field('balance').' + '.$record['price']
            .' WHERE '.DB::implode(array('uid' => $record['uid'])));
        }
        break;
      }
      $this->log('deposit/notify id:'.$deposit_id.' '.$trade_status);
      echo "success";
      return TRUE;
    } else {
      echo "fail";
      return FALSE;
    }
  }

  function send_goods($record, $trade_no) {
    global $_G;

    $alipay_config = $this->alipay_config;
    $parameter = array(
      "service" => "send_goods_confirm_by_platform",
      "partner" => trim($alipay_config['partner']),
      "trade_no"  => $trade_no,
      "logistics_name"  => 'AUTO',
      "invoice_no"  => '',
      "transport_type"  => 'EXPRESS',
      "_input_charset"  => trim(strtolower($alipay_config['input_charset']))
    );

    //建立请求
    $alipaySubmit = new AlipaySubmit($alipay_config);
    $html_text = $alipaySubmit->buildRequestHttp($parameter);

    $doc = new DOMDocument();
    $doc->loadXML($html_text);
    if(!empty($doc->getElementsByTagName("alipay")->item(0)->nodeValue)) {
      $alipay = $doc->getElementsByTagName("alipay")->item(0)->nodeValue;
      $this->log('deposit/send id:'.$record['id'].' '.$alipay);
      //echo $alipay;
    }
  }

  function create_trade($price) {
    global $_G;

    $alipay_config = $this->alipay_config;

    $out_trade_no = DB::insert('user_deposit', array(
      'uid' => $_G['uid'],
      'status' => 0,
      'price' => $price,
      'createtime' => TIMESTAMP,
    ), true);

    $strprice = round($price / 100, 2);
    $subject = 'SUSTC.US '.lang('user', 'deposit').' '.$strprice.' '.lang('user', 'yuan');

    $body = '';

    $parameter = array(
      "service" => "trade_create_by_buyer",
      "partner" => trim($alipay_config['partner']),
      "payment_type"  => '1',
      "notify_url"  => SITE_URL.'/user/deposit/notify',
      "return_url"  => SITE_URL.'/user/deposit/return',
      "seller_email"  => SELLER_EMAIL,
      "out_trade_no"  => $out_trade_no,
      "subject" => $subject,
      "price" => $strprice,
      "quantity"  => '1',
      "logistics_fee" => '0.00',
      "logistics_type"  => 'EXPRESS',
      "logistics_payment" => 'SELLER_PAY',
      "body"  => $body,
      "show_url"  => SITE_URL.'/user/deposit?price='.$price,
      "receive_name"  => '',
      "receive_address" => '',
      "receive_zip" => '',
      "receive_phone" => '',
      "receive_mobile"  => '',
      "_input_charset"  => trim(strtolower($alipay_config['input_charset']))  //utf-8
    );

    //建立请求
    $alipaySubmit = new AlipaySubmit($alipay_config);
    $html_text = $alipaySubmit->buildRequestForm($parameter, "get", lang('template', 'confirm'));
    return $html_text;
  }
}

?>