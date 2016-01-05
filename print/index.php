<?php

define('IN_SUSTC', true);
define('SC_ROOT', substr(__FILE__, 0, -15)); //strlen('print/index.php')

define('CURSCRIPT', 'print');

require_once SC_ROOT.'src/config.php';
require_once SC_ROOT.'src/core.php';
require_once SC_ROOT.'src/upload.php';
require_once SC_ROOT.'src/service/cloudprint.php';

$sustc = new core();
$cloudprint = new cloudprint($sustc);

$action = isset($_GET['action']) ? $_GET['action'] : '';

if ($action == 'add' || $action == 'status' || $action == 'cancel') {
  if (!$sustc->user->islogin()) {
    dredirect('/user/signin?redirect=/print/'.$action);
  }
}

if ($action == 'add') {
  $err = array(
    'code' => 0
  );
  if (is_post()) {
    $err['code'] = -1;
    if (isset($_POST['formhash'])
        && $sustc->security->check_formhash($_POST['formhash'])) {
      if (isset($_POST['print'])) {
        global $_G;
        $print = $_POST['print'];
        $upload = new upload();
        if (isset($_FILES['document'])
            && $upload->init($_FILES['document'], 'document')) {
          if ($upload->save(1)) {
            $node_id = intval($print['node']);
            //check node_id $cloudprint->nodes
            //if ($node_id != 1)
            $queue = array(
              'uid' => $_G['uid'],
              'node_id' => $node_id,
              'document_id' => $upload->attid,
              'duplex' => ($print['duplex'] ? true : false),
              'colorful' => (intval($print['colorful']) ? true : false),
              'copies' => intval($print['copies']),
              'status' => 0,
              'starttime' => TIMESTAMP,
            );
            if ($queue['copies'] > 0) {
              $queue_id = DB::insert('print_queue', $queue, true);
              if ($queue_id > 0) {
                dredirect('/print/status/'.$queue_id);
              }
            }
          }
        }
      }
    }
  }
  include template('print/add');
} else if ($action == 'status') {
  global $_G;
  $qid = isset($_GET['id']) ? intval($_GET['id']) : 0;
  $status = array();
  if ($qid) {
    $queue = DB::fetch_first('SELECT * FROM '.DB::table('print_queue').' WHERE '.DB::implode(array('id' => $qid)));
    if (!$queue || $queue['uid'] != $_G['uid']) {
      unset($queue);
    } else {
      $status[0] = $queue;
      foreach ($cloudprint->nodes as $node) {
        if ($queue['node_id'] == $node['id']) {
          $queue['node'] = $node;
          break;
        }
      }
      $doc_id = $queue['document_id'];
      if ($doc_id) {
        $doc = DB::fetch_first('SELECT * FROM '.DB::table('attachment').' WHERE '.DB::implode(array('id' => $doc_id)));
        $queue['document'] = $doc;
      }
    }
  } else {
    $status = DB::fetch_all(
      'SELECT * FROM '.DB::table('print_queue').' WHERE '
      .DB::implode(array('uid' => $_G['uid']))
      .' ORDER BY '.DB::order('id', 'DESC')
      .DB::limit(20));
  }
  include template('print/status');
} else if ($action == 'api') {
  global $_G;
  $api = array(
    'err' => array('code' => -1)
  );

  if (is_post() && $_POST['data']
      && $data = json_decode($_POST['data'], TRUE)) {
    $nodeName = $data['node'];
    $node = $cloudprint->find_node($nodeName);
    if ($node && $node['accesskey'] == $data['accesskey']) {
      //defind('IS_PRINTER', true);

      if ($data['command'] == 'query') {
        $api['err']['code'] = 0;
        DB::update('print_node',
          array('lasttime' => TIMESTAMP),
          array('id' => $node['id']));

        $queue = DB::fetch_all(
            'SELECT * FROM '.DB::table('print_queue')
            .' WHERE '.DB::implode(array('node_id' => $node['id'], 'status' => 0), ' AND '));

        $api['task'] = array(
          'queue' => $queue,
          'count' => count($queue)
        );
      } else if ($data['command'] == 'status') {
        $api['err']['code'] = 0;
        $status = intval($data['status']);
        //$status != 0: error
        $node_status = ($status != 0) ? CLOUDPRINT_NODE_STATUS_PROBLEM : CLOUDPRINT_NODE_STATUS_ONLINE;
        DB::update('print_node',
          array('status' => $node_status, 'lasttime' => TIMESTAMP),
          array('id' => $node['id']));
      } else if ($data['command'] == 'ready') {
        $api['err']['code'] = 0;

        foreach ($data['queue'] as $value) {
          $u = array(
            'page' => $value['page'],
            'endtime' => TIMESTAMP
          );
          if ($value['page'] > 0) {
            $u['status'] = CLOUDPRINT_QUEUE_STATUS_INQUEUE;
          } else {
            $u['status'] = CLOUDPRINT_QUEUE_STATUS_ERROR;
          }
          DB::update('print_queue', $u, array('id' => $value['id']));
        }
      } else if (in_array($data['command'], array('printing', 'finish', 'fail'))) {
        $api['err']['code'] = 0;
        $u = array(
          'endtime' => TIMESTAMP
        );
        switch ($data['command']) {
          case 'printing':
            $api['err']['code'] = 1;
            $u['status'] = CLOUDPRINT_QUEUE_STATUS_NOBALANCE;
            break;
          case 'finish':
            $u['status'] = CLOUDPRINT_QUEUE_STATUS_FINISH;
            break;
          case 'fail':
            $u['status'] = CLOUDPRINT_QUEUE_STATUS_FAIL;
            break;
        }

        $ids = array();
        foreach ($data['queue'] as $value) {
          $ids[] = $value['id'];
        }
        if ($data['command'] == 'printing') {
          //only one
          $queue = DB::fetch_all(
            'SELECT * FROM '.DB::table('print_queue')
            .' WHERE '.DB::field('id', $ids, 'in'));
          $cost = 0;
          $uid = 0;
          foreach ($queue as $q) {
            $cost_per = COST_PER_PAGE;
            foreach ($cloudprint->nodes as $node) {
              if ($q['node_id'] == $node['id']) {
                if ($node['colorful']) {
                  $cost_per = COST_PER_PAGE_COLORFUL;
                }
                break;
              }
            }
            $uid = $q['uid'];
            $cost += $q['page'] * $q['copies'] * $cost_per;
          }
          if ($uid) {
            $userstatus = DB::fetch_first('SELECT * FROM '.DB::table('user_status').' WHERE '.DB::implode(array('uid' => $uid)));
            if ($userstatus['balance'] >= $cost) {
              $api['err']['code'] = 0;
              $u['status'] = CLOUDPRINT_QUEUE_STATUS_PRINTING;

              //TODO use sql update
              $userstatus['balance'] -= $cost;
              DB::update('user_status', array('balance' => $userstatus['balance']), array('uid' => $uid));
            }
          }
        }

        DB::update('print_queue', $u, DB::field('id', $ids, 'in'));
      }
    } else {
      $api['err']['code'] = 1;
    }
  }
  echo json_encode($api, JSON_UNESCAPED_UNICODE);
} else if ($action == 'cancel') {
  global $_G;
  $qid = 0;
  if (is_post()) {
    //$err['code'] = -1;
    if (isset($_POST['formhash'])
        && $sustc->security->check_formhash($_POST['formhash'])) {
      if (isset($_POST['queue_id'])) {
        $qid = intval($_POST['queue_id']);
      }
    }
  }
  if ($qid) {
    $queue = DB::fetch_first('SELECT * FROM '.DB::table('print_queue').' WHERE '.DB::implode(array('id' => $qid)));
    if ($queue && $queue['uid'] == $_G['uid']) {
      if ($queue['status'] == CLOUDPRINT_QUEUE_STATUS_WAITING) {
        DB::update('print_queue',
          array('status' => CLOUDPRINT_QUEUE_STATUS_CANCEL, 'endtime' => TIMESTAMP),
          array('id' => $qid, 'status' => CLOUDPRINT_QUEUE_STATUS_WAITING));
      }
    }
    dredirect('/print/status/'.$qid);
  } else {
    dredirect('/print/status');
  }
} else if ($action == 'refund') {
  // refund for failed
  global $_G;
  $qid = 0;
  if (is_post()) {
    //$err['code'] = -1;
    if (isset($_POST['formhash'])
        && $sustc->security->check_formhash($_POST['formhash'])) {
      if (isset($_POST['queue_id'])) {
        $qid = intval($_POST['queue_id']);
      }
    }
  }
  if ($qid) {
    $queue = DB::fetch_first('SELECT * FROM '.DB::table('print_queue').' WHERE '.DB::implode(array('id' => $qid)));
    if ($queue && $queue['uid'] == $_G['uid'] && TIMESTAMP - $queue['endtime'] < 60 * 60 * 24) {
      // in one day
      if ($queue['status'] == CLOUDPRINT_QUEUE_STATUS_FAIL || $queue['status'] == CLOUDPRINT_QUEUE_STATUS_ERROR) {
        // calc costs
        $cost_per = COST_PER_PAGE;
        foreach ($cloudprint->nodes as $node) {
          if ($queue['node_id'] == $node['id']) {
            if ($node['colorful']) {
              $cost_per = COST_PER_PAGE_COLORFUL;
            }
            break;
          }
        }
        $cost = $queue['page'] * $queue['copies'] * $cost_per;
        DB::update('print_queue',
          array('status' => CLOUDPRINT_QUEUE_STATUS_REFUND, 'endtime' => TIMESTAMP),
          array('id' => $qid));
        DB::query('UPDATE '.DB::table('user_status').' SET `balance` = `balance` + '.$cost.' WHERE '.DB::implode(array('uid' => $_G['uid']), ' AND '));
      }
    }
    dredirect('/print/status/'.$qid);
  } else {
    dredirect('/print/status');
  }
} else {
  include template('print/index');
}

?>
