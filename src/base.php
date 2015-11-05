<?php

/*
	[RasCenter] (C)2009-2011 TengAttack.

	$Id: base.php 2011-07-31 09:00 GMT+8 $
*/

!defined('IN_SUSTC') && exit('Access Denied');


class base {

	var $time;
	var $onlineip;
	var $db;
	
	function base() {
		$this->init_var();
		$this->init_db();
	}
	
	function init_var() {
		$this->time = time();
		define('TIMESTAMP', $this->time);
		
		$cip = getenv('HTTP_CLIENT_IP');
		$xip = getenv('HTTP_X_FORWARDED_FOR');
		$rip = getenv('REMOTE_ADDR');
		$srip = $_SERVER['REMOTE_ADDR'];
		if($cip && strcasecmp($cip, 'unknown')) {
			$this->onlineip = $cip;
		} elseif($xip && strcasecmp($xip, 'unknown')) {
			$this->onlineip = $xip;
		} elseif($rip && strcasecmp($rip, 'unknown')) {
			$this->onlineip = $rip;
		} elseif($srip && strcasecmp($srip, 'unknown')) {
			$this->onlineip = $srip;
		}
		preg_match("/[\d\.]{7,15}/", $this->onlineip, $match);
		$this->onlineip = $match[0] ? $match[0] : 'unknown';
	}
	
	function init_db() {
		/*
		require_once SC_ROOT.'src/lib/db.class.php';
		$this->db = new db();
		$this->db->connect(SC_DBHOST, SC_DBUSER, SC_DBPW, SC_DBNAME, SC_DBCHARSET, SC_DBCONNECT, SC_DBTABLEPRE);
		*/
		$db_config = array();
		$db_config[1] = array(
			'dbhost' => SC_DBHOST,
			'dbuser' => SC_DBUSER,
			'dbpw' => SC_DBPW,
			'dbcharset' => SC_DBCHARSET,
			'pconnect' => SC_DBCONNECT,
			'dbname' => SC_DBNAME,
			'tablepre' => SC_DBTABLEPRE,
		);

		require_once SC_ROOT.'src/lib/db_driver_mysqli.php';
		$this->db = new db_driver_mysqli();
		$this->db->set_config($db_config);
		$this->db->connect();
	}
	
	function implode($arr) {
		return "'".implode("','", (array)$arr)."'";
	}
}

?>