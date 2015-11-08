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
	define('SC_ROOT', substr(__FILE__, 0, -31)); //strlen('print\remind_maintain/index.php')
	define('IN_SUSTC', true);
	define('CURSCRIPT', 'print');

	require_once SC_ROOT.'src/service/cloudprint.php';
	require_once SC_ROOT.'src/core.php';
	require_once SC_ROOT."src/class/PHPMailer-master/PHPMailerAutoload.php"; 
	require_once 'config.php';

	$sustc = new core();
	$cloudprint = new cloudprint($sustc);
	//fetch the nodes_name offline or with errors
	$error_printers=array();

	foreach ($cloudprint->nodes as $nodes) {
		if ($nodes['status'] == 0) {
			$error_printers[]= array('name'=>$nodes['name'] , 'err'=>CLOUDPRINT_NODE_STATUS_OFFLINE);
		}
	}
	unset($nodes);


	foreach ($cloudprint->nodestatus['err'] as $err_nodename) {
		$error_printers[]= array('name'=>$err_nodename, 'err'=>CLOUDPRINT_NODE_STATUS_PROBLEM);
	}
	unset($err);

	send_alerts($error_printers);

	//send_alerts
	function send_alerts($_error_printers){
		if (count($_error_printers)>0) {
			$mail= new PHPMailer;
			$mail->isSMTP();
			$mail->Host = 'smtp.qq.com';  // Specify main and backup SMTP servers
			$mail->SMTPAuth = true;                               // Enable SMTP authentication
			$mail->Username = 'notifications@sustc.us';                 // SMTP username
			$mail->Password = 'gPGDmNHrayanSC1G';                           // SMTP password
			//$mail->SMTPSecure = 'tls';                            // Enable TLS encryption, `ssl` also accepted
			$mail->Port = 25;                                    // TCP port to connect to
			$mail->setFrom('notifications@sustc.us', 'SUSTC_notifications');
			$mail->addAddress(EMIL_ADRESS, EMIL_NAME);     // Add a recipient
			//$mail->addReplyTo('info@example.com', 'Information');
			//$mail->addCC('cc@example.com');
			//$mail->addBCC('bcc@example.com');

			//$mail->addAttachment('/var/tmp/file.tar.gz');         // Add attachments
			//$mail->addAttachment('/tmp/image.jpg', 'new.jpg');    // Optional name
			$mail->isHTML(true);                                  // Set email format to HTML
			$mail->Subject = '[Cloudprinter_SUSTC]Error Alarm';
			$Bodyhead    = <<<Bodyhead
<div style="background-color:#fff; border:1px solid #666666; color:#111;-moz-border-radius:8px; -webkit-border-radius:8px; -khtml-border-radius:8px;border-radius:8px; font-size:12px; width:702px; margin:0 auto; margin-top:10px;font-family:微软雅黑, Arial;">
	<div style="background:#666666; width:100%; height:60px; color:white;-moz-border-radius:6px 6px 0 0; -webkit-border-radius:6px 6px 0 0;-khtml-border-radius:6px 6px 0 0; border-radius:6px 6px 0 0; ">
		<span style="height:60px; line-height:60px; margin-left:30px; font-size:20px;">云打印节点出现故障</span>
	</div>
	<div style="width:90%; margin:0 auto">
		<p>您好！</p>
		<p>很不幸得告诉你，以下云打印节点出现故障。</p>
		<ul style="background-color: #EEE;border: 1px solid #DDD;padding: 20px;margin: 15px 0;">
Bodyhead;
			$Bodyfoot='';
			foreach ($_error_printers as $value) {
				$Bodyfoot.='<li>'.$value['name'].' | ErrorType: '.$value['err'].'</li>';
			}
			$Bodyfoot.=<<<Bodyfoot
</ul>
<p>此邮件由系统自动发出，请勿回复！	</p>
<p>From sustcus_notifications</p>

	</div>
</div>
Bodyfoot;
			$mail->Body  = $Bodyhead.$Bodyfoot;
			//$mail->AltBody = 'This is the body in plain text for non-HTML mail clients';
			//echo $Bodyhead.$Bodyfoot;
			if(!$mail->send()) {
			    echo 'Something wrong with printer but Message could not be sent.';
			    echo 'Mailer Error: ' . $mail->ErrorInfo;
			} else {
			    echo 'Something wrong with printer and Message has been sent';
			}
		}else{
			echo 'No printer is down.';
		}
			
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