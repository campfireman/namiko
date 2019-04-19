<?php
require('./util/phpmailer/Exception.php');
require('./util/phpmailer/PHPMailer.php');
require('./util/phpmailer/SMTP.php');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/** 
 * create instance for sending one or batches of emails
 */
class Mail {
	private $from;
	private $fromEntity;
	private $mailErrors;

	/**
	 * builds mailer with user information
	 * @param string $smtp_host
	 * @param string $smtp_username
	 * @param string $smtp_password
	 * @param string $from
	 * @param string $fromEntity
	 */
	function __construct($smtp_host, $smtp_username, $smtp_password, $from, $fromEntity) {
		$this->smtp_host = $smtp_host;
		$this->smtp_username = $smtp_username;
		$this->smtp_password = $smtp_password;
		$this->from = $from;
		$this->fromEntity = $fromEntity;
	}

	/**
	 * send mail to given recipient
	 * @param  string $email
	 * @param  string $entity  [description]
	 * @param  string $subject [description]
	 * @param  string $text    [description]
	 * @param  bool   $convert strip HTML tags from given text?
	 * @return void
	 * @throws [<Exception>] when error with sending occurrs
	 */
	public function send($email, $recipient, $subject, $text, $convert) {
		global $mail_footer;
		$stripped_text = ($convert ? strip_tags($text) : $text);

		$mail = new PHPMailer(true);

		//Server settings
		$mail->SMTPDebug = 0;
		$mail->isSMTP();
		$mail->Host = $this->smtp_host;
		$mail->SMTPAuth = true;
		$mail->Username = $this->smtp_username;
		$mail->Password = $this->smtp_password;
		$mail->SMTPSecure = 'tls';
		$mail->Port = 587;
		$mail->CharSet = 'UTF-8';
		$mail->Encoding = 'base64';

		//Recipients
		$mail->setFrom($this->from, $this->fromEntity);
		$mail->addAddress($email, $recipient);
		$mail->addReplyTo($this->from, $this->fromEntity);

		//Content
		$mail->isHTML(true);
		$mail->Subject = $subject;
		$mail->Body    = $text . $mail_footer;
		$mail->AltBody = $stripped_text;

		$mail->send();
	}

	/**
	 * sends mail to all recipients in given array, errors can be retrieved via getMailErrors()
	 * @param  Array $batch keys have to match argument names of send()
	 * @return bool         returns false when an error occurs
	 */
	public function sendBatch($batch) {
		$error = false;
		foreach ($batch as $uid => $data) {
			try {
				$this->send($data['email'], $data['recipient'], $data['subject'], $data['text'], true);
			} catch (Exception $e) {
				$this->mailErrors[] = $e->getMessage();
				$error = true;
			}
		}
		return $error;
	}

	/**
	 * retrieve array of all mail errors from send batch
	 * @return Array error messages from php mailer
	 */
	public function getMailErrors() {
		return json_encode($this->mailErrors);
	}
}
?>