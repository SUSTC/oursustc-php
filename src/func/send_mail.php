<?php
	/**
	 * send_notification_mail
	 * PHP version 5
	 * @category print
	 * @author Frank <68852874@qq.com>, tengattack
	 * @copyright SUSTC
	 * @version [1.1] 2015-11-11
	 */
	// if (!defined('IN_SUSTC')) {
	//		exit;
	// }

	require_once SC_ROOT."/src/lib/phpmailer/PHPMailerAutoload.php";
	require_once SC_ROOT.'/src/config.php';

	function send_notification_mail($templ, $subject, $data = array()) {
		$body = file_get_contents(SC_ROOT.'template/mail/'.$templ.'.htm');

		foreach ($data as $key => $value) {
			$body = str_replace('{' . $key . '}', $value, $body);
		}

		return send_mail(EMAIL_ADDRESS, EMAIL_NAME, '[SUSTC.US] ' . $subject, $body);
	}

	function send_mail($address, $name = 'SUSTC', $subject, $body) {
		$mail= new PHPMailer();
		$mail->CharSet = 'utf-8';
		$mail->isSMTP();
		$mail->Host = MAILER_HOST;  // Specify main and backup SMTP servers
		$mail->SMTPAuth = true;                               // Enable SMTP authentication
		$mail->Username = MAILER_USERNAME;                 // SMTP username
		$mail->Password = MAILER_PASSWORD;                           // SMTP password
		//$mail->SMTPSecure = 'tls';                            // Enable TLS encryption, `ssl` also accepted
		$mail->Port = 25;                                    // TCP port to connect to
		$mail->setFrom(MAILER_USERNAME, 'SUSTC.US');

		$mail->addAddress($address, $name);     // Add a recipient
		//$mail->addReplyTo('info@example.com', 'Information');
		//$mail->addCC('cc@example.com');
		//$mail->addBCC('bcc@example.com');
		//$mail->addAttachment('/var/tmp/file.tar.gz');         // Add attachments
		//$mail->addAttachment('/tmp/image.jpg', 'new.jpg');    // Optional name
		$mail->isHTML(true);                                  // Set email format to HTML
		$mail->Subject = $subject;
		$mail->Body = $body;

		if (!$mail->send()) {
			return false;
		} else {
			return true;
		}
	}
?>
