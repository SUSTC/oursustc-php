<?php
	/**
	 * email reminder for cloudprinter
	 *
	 * PHP version 5
	 * @category print
	 * @author Frank <68852874@qq.com>
	 * @copyright SUSTC
	 * @version [1.0] 2015-11
	 */
	define('IN_SUSTC', true);
	define('CURSCRIPT', 'print');
	define('SC_ROOT', substr(__FILE__, 0, -25));

	define('CLOUDPRINT_NODE_NOT_REPAIRING', 0);
	define('CLOUDPRINT_NODE_DURING_REPAIRING', 1);

	require_once SC_ROOT.'src/service/cloudprint.php';
	require_once SC_ROOT.'src/core.php';
	require_once SC_ROOT."src/func/send_mail.php"; 
	require_once SC_ROOT.'src/config.php';

	$sustc = new core();
	$cloudprint = new cloudprint($sustc);

	//fetch the nodes_name offline or with errors but not been reminded in email
	$error_printers=array();
	foreach ($cloudprint->nodes as $nodes) {
		if ($nodes['status'] == CLOUDPRINT_NODE_STATUS_OFFLINE ) {
			if ($nodes['under_repairing']==0) {
			$error_printers[]= array('id'=>$nodes['id'],'name'=>$nodes['name'], 'err'=>'Offline');
			write_status_to_repairing($nodes['id'],1);
			}
		}elseif ($nodes['status'] == CLOUDPRINT_NODE_STATUS_PROBLEM) {
			if ($nodes['under_repairing']==0) {
			$error_printers[]= array('id'=>$nodes['id'],'name'=>$nodes['name'], 'err'=>'Problem');
			write_status_to_repairing($nodes['id'],1);
			}
		}else{
			if ($nodes['under_repairing']!=0) {
			write_status_to_repairing($nodes['id'],0);
			}
		}
	}
	unset($nodes);


	//send email
	if (count($error_printers)>0) {
			$template= file_get_contents(SC_ROOT.'\template\default\print/reminder_email.htm');

			$title='云打印节点出现故障';
			$contenthead    = <<<Bodyhead
<p>您好！</p>
<p>很不幸得告诉你，以下云打印节点出现故障。</p>
<ul style="background-color: #EEE;border: 1px solid #DDD;padding: 20px;margin: 15px 0;">
Bodyhead;
			$contentfoot='';
			foreach ($error_printers as $value) {
				$contentfoot.='<li>'.$value['name'].' | ErrorType: '.$value['err'].'</li>';
			}
			$contentfoot.='</ul>';
			$content= $contenthead.$contentfoot;
			$template=str_replace('{title}',$title,$template);
			$Body=str_replace('{content}',$content,$template);
			echo $Body;
			if(send_mail(EMAIL_ADDRESS,'SUSTC','[Cloudprinter_SUSTC]Error Alarm',$Body)){
				echo 'Something Wrong with printers.An email has been sent.';
			}else{
				echo 'Something Wrong with printers.But fail to send an email.';
			}
	}else{
		echo 'No printer is down Or an email has been sent.';
	}

	//write database
	function write_status_to_repairing ($node_id,$isunder_repairing){
		DB::update('print_node',array('under_repairing'=>$isunder_repairing),array('id' =>$node_id));
	}
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