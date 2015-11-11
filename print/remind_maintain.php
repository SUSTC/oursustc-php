<?php

!defined('IN_SUSTC') && exit('Access Denied');

	/**
	 * email reminder for cloudprinter
	 *
	 * PHP version 5
	 * @category print
	 * @author Frank <68852874@qq.com>, tengattack
	 * @copyright SUSTC
	 * @version [1.1] 2015-11-11
	 */

	require_once SC_ROOT.'src/config.php';

	require_once SC_ROOT.'src/core.php';

	require_once SC_ROOT."src/func/send_mail.php";
	require_once SC_ROOT.'src/service/cloudprint.php';

	$sustc = new core();
	$cloudprint = new cloudprint($sustc);

	//write database
	function write_status_to_repairing($node_id, $isunder_repairing) {
		DB::update('print_node', array('under_repairing' => $isunder_repairing), array('id' => $node_id));
	}

	//fetch the nodes_name offline or with errors but not been reminded in email
	$error_printers = array();

	foreach ($cloudprint->nodes as $nodes) {
		if ($nodes['status'] == CLOUDPRINT_NODE_STATUS_OFFLINE) {
			if ($nodes['under_repairing'] == CLOUDPRINT_NODE_NOT_REPAIRING) {
				$error_printers[] = array('id'=>$nodes['id'], 'name'=>$nodes['name'], 'err'=>'Offline');
				write_status_to_repairing($nodes['id'], CLOUDPRINT_NODE_DURING_REPAIRING);
			}
		} elseif ($nodes['status'] == CLOUDPRINT_NODE_STATUS_PROBLEM) {
			if ($nodes['under_repairing'] == CLOUDPRINT_NODE_NOT_REPAIRING) {
				$error_printers[] = array('id'=>$nodes['id'], 'name'=>$nodes['name'], 'err'=>'Problem');
				write_status_to_repairing($nodes['id'], CLOUDPRINT_NODE_DURING_REPAIRING);
			}
		} else {
			if ($nodes['under_repairing'] != CLOUDPRINT_NODE_NOT_REPAIRING) {
				write_status_to_repairing($nodes['id'], CLOUDPRINT_NODE_NOT_REPAIRING);
			}
		}
	}
	unset($nodes);

	//send email
	if (count($error_printers) > 0) {

			$title = 'cloudPrint 节点出现故障';
			$contenthead = <<<Bodyhead
<p>您好！</p>
<p>很不幸的告诉你，以下 cloudPrint 节点出现故障。</p>
<ul style="background-color: #EEE;border: 1px solid #DDD;padding: 20px;margin: 15px 0;">
Bodyhead;
			$contentfoot = '';
			foreach ($error_printers as $value) {
				$contentfoot .= '<li>'.$value['name'].' | ErrorType: '.$value['err'].'</li>';
			}
			$contentfoot .= '</ul>';
			$content = $contenthead . $contentfoot;

			echo "Something Wrong with printers.\n";

			if (send_notification_mail('cloudprint_reminder', $title,
					array('title' => $title, 'content' => $content))) {
				echo 'An email has been sent successfully.';
			} else {
				echo 'But fail to send an email.';
			}
			echo "\n";
	} /*else {
		echo "No printer is down.\n";
	}*/

	//array in the $cloudprint
	/*
	    [nodes] => Array
        (
            [0] => Array
                (
                    [id] => 1
                    [name] => 002
                    [status] => 0
                    [lasttime] => 0
                    [page] => 0
                    [description] =>
                    [duplex] => 0
                    [colorful] => 1
                    [accesskey] =>
                )

        )

    [nodestatus] => Array
        (
            [err] => Array
                (
                )

            [online] => 0
            [inqueue] => 1
            [printing] => 0
        )
	 */
?>
