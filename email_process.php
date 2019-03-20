<?php
session_start(); //start session
//ini_set('display_errors', 1);
require_once("inc/config.inc.php"); //include config file
require('util/phpmailer/Exception.php');
require('util/phpmailer/PHPMailer.php');
require('util/phpmailer/SMTP.php');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if(isset($_POST["subject"])) {
	$subject = $_POST['subject'];
	$text = $_POST['text'];
	$query = "";

	if (isset($_POST['others'])) {
		$query .= 'SELECT email, first_name FROM newsletter_recipients WHERE verified = 1';

		if (isset($_POST['members'])) {
			$query .= ' UNION SELECT email, first_name FROM users WHERE newsletter = 1';
		}
	}

	if (empty($_POST['others']) && isset($_POST['members'])) {
		$query = ' SELECT email, first_name AS first_name FROM users';
	}

	if (empty($_POST['members']) && empty($_POST['others'])) {
		die(json_encode('Kein Rezipient ausgewählt.'));
	}

	$statement = $pdo->prepare($query);
	$result = $statement->execute();

	while ($row = $statement->fetch()) {
		$email = $row['email'];
		$first_name = $row['first_name'];

		$mail = new PHPMailer(true);
		try {
		    //Server settings
		    $mail->SMTPDebug = 0;
		    $mail->isSMTP();
		    $mail->Host = $smtp_host;
		    $mail->SMTPAuth = true;
		    $mail->Username = $smtp_username;
		    $mail->Password = $smtp_password;
		    $mail->SMTPSecure = 'tls';
		    $mail->Port = 587;
		    $mail->CharSet = 'UTF-8';
		    $mail->Encoding = 'base64';

		    //Recipients
		    $mail->setFrom('noreply@namiko.org', 'namiko e.V. Hannover');
		    $mail->addAddress($email, $first_name);
		    $mail->addReplyTo('noreply@namiko.org', 'NoReply');

		    //Content
		    $mail->isHTML(true);
		    $mail->Subject = $subject;
		    $mail->Body    = '<h3>Moin, '. htmlspecialchars($first_name) .'!</h3>'. $text;
		    $mail->AltBody = 'Moin, '. htmlspecialchars($first_name) .'!'. $text;

		    $mail->send();
		    $result2 = true;
		} catch (Exception $e) {
		    $result2 = false;
		}
	}

	if ($result && $result2) {
		die(json_encode('Mails erfolgreich verschickt.')); //output json 
	} 
	if (!$result) {
		die(json_encode($statement->errorInfo())); //output json 
	}

	if (!$result2) {
		die(json_encode('Eine, mehrere oder alle Mail/s konnte/n nicht verschickt werden.')); //output json 
	}



}

if(isset($_POST["temp_id"])) {
	$temp_id = $_POST['temp_id'];
	
	$statement = $pdo->prepare("SELECT template FROM mail_templates WHERE temp_id = '$temp_id'");
	$result = $statement->execute();
	$row = $statement->fetch();

	if ($result) {
		die(json_encode($row['template']));
	} else {
		die(json_encode('Fehler.'));
	}
}

?>