<?php

if (!defined('IN_SUSTC')) {
  exit;
}

define('CLOUDPRINT_NODE_STATUS_OFFLINE', 0);
define('CLOUDPRINT_NODE_STATUS_ONLINE', 1);
define('CLOUDPRINT_NODE_STATUS_PROBLEM', 2);
define('CLOUDPRINT_NODE_STATUS_DISABLE', 3);

define('CLOUDPRINT_QUEUE_STATUS_WAITING', 0);
define('CLOUDPRINT_QUEUE_STATUS_INQUEUE', 1);
define('CLOUDPRINT_QUEUE_STATUS_PRINTING', 2);
define('CLOUDPRINT_QUEUE_STATUS_FINISH', 3);
define('CLOUDPRINT_QUEUE_STATUS_FAIL', 4);
define('CLOUDPRINT_QUEUE_STATUS_ERROR', 5);
define('CLOUDPRINT_QUEUE_STATUS_NOBALANCE', 6);
define('CLOUDPRINT_QUEUE_STATUS_CANCEL', 7);
define('CLOUDPRINT_QUEUE_STATUS_REFUND', 8);

define('CLOUDPRINT_NODE_NOT_REPAIRING', 0);
define('CLOUDPRINT_NODE_DURING_REPAIRING', 1);

define('COST_PER_PAGE', 20);
define('COST_PER_PAGE_COLORFUL', 130);

define('WAIT_BUYER_CONFIRM', 3);

class cloudprint
{
  var $core;

  var $nodes;
  var $disabled_nodes;
  var $nodestatus;
  var $usersummary;

  function cloudprint($core) {
    $this->core = $core;
    $this->init_nodestatus();
    $this->init_usersummary();
  }

  function init_nodestatus() {
    $_nodes = DB::fetch_all('SELECT * FROM '.DB::table('print_node');
      //.' WHERE `status` <> '.CLOUDPRINT_NODE_STATUS_DISABLE);
    $onlinecount = 0;
    $onlinetime = (TIMESTAMP - (3 * 60));
    
	$errns = [];
	$this->nodes = [];
	$this->disabled_nodes = [];
	
    foreach ($_nodes as &$node) {
	  if ($node['status'] == CLOUDPRINT_NODE_STATUS_DISABLE) {
		// for disabled nodes
		$this->disabled_nodes[] = $node;
		continue;
	  }
	  
	  //in 3 minutes it online
      if ($node['lasttime'] >= $onlinetime) {
        $onlinecount++;
      } else {
        $node['status'] = CLOUDPRINT_NODE_STATUS_OFFLINE;
      }
      if ($node['status'] == CLOUDPRINT_NODE_STATUS_PROBLEM) {
        $errns[] = $node['name'];
      }
	  
	  $this->nodes[] = $node;
    }
    $this->nodestatus['err'] = $errns;
    $this->nodestatus['online'] = $onlinecount;
    $this->nodestatus['inqueue'] = DB::result_first(
      'SELECT COUNT(*) FROM '.DB::table('print_queue').' WHERE `status` = '.CLOUDPRINT_QUEUE_STATUS_INQUEUE.";");
    $this->nodestatus['printing'] = DB::result_first(
      'SELECT COUNT(*) FROM '.DB::table('print_queue').' WHERE `status` = '.CLOUDPRINT_QUEUE_STATUS_PRINTING.";");
  }

  function init_usersummary() {
    if ($this->core->user->islogin()) {

      $unconfirm = $this->core->db->result_first(
          'SELECT SUM('.DB::quote_field('price').') FROM '.DB::table('user_deposit')
            .' WHERE '.DB::implode(array('status' => WAIT_BUYER_CONFIRM, 'uid' => $this->core->user->uid), ' AND '));

      $this->usersummary['balance'] = round($this->core->user->status['balance'] / 100, 2);
      if ($unconfirm) {
        $this->usersummary['balance_pending'] = strval(round($unconfirm / 100, 2));
      }

      $this->usersummary['inqueue'] =
        $this->core->db->result_first(
          "SELECT COUNT(*) FROM `sustc_print_queue` WHERE `status` = "
            .CLOUDPRINT_QUEUE_STATUS_INQUEUE." AND `uid` = ".$this->core->user->uid.";");
      $this->usersummary['printing'] =
        $this->core->db->result_first(
          "SELECT COUNT(*) FROM `sustc_print_queue` WHERE `status` = "
            .CLOUDPRINT_QUEUE_STATUS_PRINTING." AND `uid` = ".$this->core->user->uid.";");
    }
  }

  function find_node($node_name) {
    foreach ($this->nodes as &$node) {
      if ($node['name'] == $node_name) {
        return $node;
      }
    }
    return NULL;
  }
  
  function is_disabled_node($node_name) {
    foreach ($this->disabled_nodes as &$node) {
      if ($node['name'] == $node_name) {
        return true;
      }
    }
    return false;
  }

  function add_task($node_id, $document_id, $copies, $duplex) {
    if ($this->core->db->query('INSERT INTO `sustc_print_queue` SET '
        .'`uid` = "'.$this->core->user->uid.'", '
        .'`node_id` = "'.$node_id.'", '
        .'`duplex` = "'.($duplex ? 1 : 0).'", '
        .'`copies` = "'.intval($copies).'", '
        .'`starttime` = "'.TIMESTAMP.'";')) {
      return $this->core->db->insert_id();
    } else {
      return 0;
    }
  }

  function get_stats() {
    $stats = array();

    $stats['total'] = DB::result_first(
        'SELECT SUM('.DB::quote_field('page').' * '.DB::quote_field('copies').')'.
        ' FROM '.DB::table('print_queue').' WHERE '
        .DB::field('status', CLOUDPRINT_QUEUE_STATUS_FINISH));
    $stats['24hours'] = DB::result_first(
        'SELECT SUM('.DB::quote_field('page').' * '.DB::quote_field('copies').')'.
        ' FROM '.DB::table('print_queue').' WHERE '
        .DB::field('status', CLOUDPRINT_QUEUE_STATUS_FINISH).' AND '
        .DB::field('starttime', TIMESTAMP - (24 * 60 * 60), '>='));

    if (!$stats['total']) {
      $stats['total'] = 0;
    }
    if (!$stats['24hours']) {
      $stats['24hours'] = 0;
    }
    return $stats;
  }
}

?>
