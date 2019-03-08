<?php
session_start(); //start session
ini_set('display_errors', 1);
require_once("inc/config.inc.php"); //include config file
require('util/phpmailer/Exception.php');
require('util/phpmailer/PHPMailer.php');
require('util/phpmailer/SMTP.php');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if(isset($_POST["subject"])) {
	$name = trim($_POST['name']);
	$email = trim($_POST['email']);
	$subject = trim($_POST['subject']);
	$text = trim($_POST['text']);
	$error = false;

	if (empty($name) || empty($email) || empty($subject) || empty($text)) {
		$error = true;
	}

	if (!preg_match("/^[öÖüÜäÄßa-zA-Z ]*$/",$name)) {
		$error = true;
	}

	if(!filter_var($_SESSION['email'], FILTER_VALIDATE_EMAIL)) {
		$error = true;
	}

	if (!preg_match("/^[öÖüÜäÄßa-zA-Z ]*$/",$subject)) {
		$error = true;
	}

	if (!preg_match("/^[öÖüÜäÄßa-zA-Z ]*$/",$text)) {
		$error = true;
	}

	if (!$error) {
		$mail = new PHPMailer(true);
		try {
		    //Server settings
		    $mail->SMTPDebug = 0;                                 // Enable verbose debug output
		    $mail->isSMTP();                                      // Set mailer to use SMTP
		    $mail->Host = $smtp_host;  // Specify main and backup SMTP servers
		    $mail->SMTPAuth = true;                               // Enable SMTP authentication
		    $mail->Username = $smtp_username;                 // SMTP username
		    $mail->Password = $smtp_password;                           // SMTP password
		    $mail->SMTPSecure = 'tls';                            // Enable TLS encryption, `ssl` also accepted
		    $mail->Port = 587;                                    // TCP port to connect to

		    //Recipients
		    $mail->setFrom('noreply@namiko.org', 'namiko e.V. Hannover');
		    $mail->addAddress('kontakt@namiko.org');     // Add a recipient
		    $mail->addReplyTo($email, $name);

		    //Content
		    $mail->isHTML(true);                                  // Set email format to HTML
		    $mail->Subject = '[FORMULAR] ' . $subject;
		    $mail->Body    = 'Absender: ' . $name . '<br>Email: ' . $email .'<br>Betreff: '. $subject .'<br><br>'. $text;
		    $mail->AltBody = 'Absender: ' . $name . 'Email: ' . $email .'Betreff: '. $subject .''. $text;

		    $mail->send();
		} catch (Exception $e) {
		    $error = true;
		}
	}
	}

	if (!$error) {
		die(json_encode('Mail erfolgreich verschickt.')); //output json 
	} 
	if ($error) {
		die(json_encode('Die Mail konnte nicht verschickt werden.')); //output json 
	}
