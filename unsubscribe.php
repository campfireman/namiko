<?php 
session_start();
require_once("inc/config.inc.php");
require_once("inc/functions.inc.php");
//ini_set('display_errors', 1);

include("templates/header.inc.php");

require('util/phpmailer/Exception.php');
require('util/phpmailer/PHPMailer.php');
require('util/phpmailer/SMTP.php');
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// create error if error = true
if ($_SESSION['error']) {
	echo $_SESSION['errortxt'] . $_SESSION['errormsg'];
}

// create SESSION variable for displaying error messages
$_SESSION['error'] = false;
$_SESSION['agreed'] = false;
$_SESSION['errortxt'] = '<script>document.body.className += "noscroll"</script>
						 <div class="error notification">
			 			 <div><a href="javascript:void(0)" title="Close" class="closebtn" onclick="close_error()">&times;</a></div>
			 			 <div class="center-vertical">';

if (isset($_GET['created_at'])) {
	$created_at = urldecode($_GET['created_at']);

	if (isset($_GET['rid'])) {
		$rid = $_GET['rid'];

		$statement = $pdo->prepare("DELETE newsletter_proof.*, newsletter_recipients.* FROM newsletter_proof LEFT JOIN newsletter_recipients ON newsletter_recipients.rid = newsletter_recipients.rid WHERE newsletter_proof.rid = '$rid' AND newsletter_recipients.created_at = '$created_at'");
		$result = $statement->execute();
	}

	if (isset($_GET['uid'])) {
		$uid = $_GET['uid'];

		$statement = $pdo->prepare("UPDATE users SET newsletter = 0 WHERE uid = '$uid' AND created_at = '$created_at'");
		$result = $statement->execute();
	}

	if ($result) {
		$_SESSION['error'] = true;
		$_SESSION['errormsg'] = 'Newsletter erfolgreich deabonniert.</div></div>';
		header('location:'. htmlspecialchars($_SERVER['PHP_SELF']));
	} else {
		$_SESSION['error'] = true;
		$_SESSION['errormsg'] = 'Newsletter ist bereits deabonniert.</div></div>';
		header('location:'. htmlspecialchars($_SERVER['PHP_SELF']));
	}
}

if (isset($_POST['unsubscribe'])) {
	$email = trim($_POST['email']);

	if (empty($email)) {
		$_SESSION['error'] = true;
		$_SESSION['errormsg'] = 'Du musst alle Pflichtfelder ausfüllen.</div></div>';
		header('location:'. htmlspecialchars($_SERVER['PHP_SELF']));
	}

	if(!filter_var($_SESSION['email'], FILTER_VALIDATE_EMAIL)) {
		$_SESSION['errormsg'] = 'Bitte eine gültige E-Mail-Adresse eingeben</div></div>';
		$_SESSION['error'] = true;
		header('location:'. htmlspecialchars($_SERVER['PHP_SELF']));
	} 

	$mail = new PHPMailer(true);                              // Passing `true` enables exceptions
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

	    $statement = $pdo->prepare("SELECT * FROM newsletter_recipients WHERE email = :email");
		$result = $statement->execute(array('email' => $email));
		$user = $statement->fetch();
		
		if($user !== false) {
			$rid = $user['rid'];
			$created_at = $user['created_at'];
			$first_name = $user['first_name'];
			$params = '?rid='. $rid .'&created_at='.urlencode($created_at);
			
		} else {
			$statement = $pdo->prepare("SELECT * FROM users WHERE email = :email");
			$result = $statement->execute(array('email' => $email));
			$user = $statement->fetch();

			if ($user) {

			$uid = $user['uid'];
			$created_at = $user['created_at'];
			$first_name = $user['first_name'];
			$params = '?uid='. $uid .'&created_at='.urlencode($created_at);

			} else {
				$_SESSION['errormsg'] = 'Falls du den Newsletter abonniert hast, oder Mitglied bist, hast du nun eine Email erhalten.
				</div></div>';
				$_SESSION['error'] = true;
				header('location:'. htmlspecialchars($_SERVER['PHP_SELF']));
			}

		}

		$mail->setFrom('noreply@namiko.org', 'namiko e.V. Hannover');
	    $mail->addAddress($email, $first_name);
	    $mail->addReplyTo('kontakt@namiko.org');

	    //Content
	    $mail->isHTML(true);
	    $mail->Subject = 'Newsletter Deabonnieren';
	    $mail->Body    = '<h1>Moin '. $first_name .'!</h1>
	    					<p>Um deine Abmeldung vom Newsletter abzuschliessen, besuche folgende Adresse:<br><br><span style="font-weight: 600; font-size: 30px;"><a href="'. getSiteUrl() .'unsubscribe.php'. $params .'">Deabonnieren</a></span><br><br>
	    					Wir freuen uns sehr mit Dir zusammenzuarbeiten.<br><br><span style="font-style: italic">Dein namiko Hannover e.V. Team</span><br><br><br><br><br><br>
	    					Bei Rückfragen einfach an kontakt@namiko.org schreiben.</p>';
	    $mail->AltBody = 'Moin '. $first_name .'!
	    					Um deine Abmeldung vom Newsletter abzuschliessen, besuche folgende Adresse: '. getSiteUrl() .'unsubscribe.php'. $params .'
	    					Wir freuen uns sehr mit Dir zusammenzuarbeiten. Dein namiko Hannover e.V. Team
	    					Bei Rückfragen einfach an kontakt@namiko.org schreiben.';

	    $mail->send();
	    
	    $_SESSION['errormsg'] = 'Falls du den Newsletter abonniert hast, oder Mitglied bist, hast du nun eine Email erhalten.</div></div>';
		$_SESSION['error'] = true;
		header('location:'. htmlspecialchars($_SERVER['PHP_SELF']));
	} catch (Exception $e) {
	    $_SESSION['errormsg'] = 'Die Bestätigungsmail konnte nicht verschickt werden. Versuche es noch einmal oder wende Dich an kontakt@namiko.org</div></div>';
		$_SESSION['error'] = true;
		header('location:'. htmlspecialchars($_SERVER['PHP_SELF']));
	}
			
}
?>

<div class="login-background">
</div>

<div id="reg" class="center-vertical" style="height: 100vh">
	<div class="login form-container">
		<span class="subtitle2">Newsletter abbestellen</span><br><br>

			<span>
			<i class="fa fa-info-circle" aria-hidden="true"></i> Um den Newsletter abzubestellen, einfach deine Email eingeben. Du erhälst dann eine Email mit dem Deaktivierungslink.
			<br>
			</span><br>
		<form action="<?php htmlspecialchars($_SERVER['PHP_SELF']) ?>" method="post">
			<input type="email" name="email" placeholder="E-Mail" required><br><br>
			<button type="submit" name="unsubscribe" class="login-btn">Abschicken <i class="fa fa-paper-plane" aria-hidden="true"></i></button>
		</form>
	</div>
</div>


<script type="text/javascript">
function close_error () {
	var x = document.getElementsByClassName('error');
	document.body.classList.remove('noscroll');

	for (var i = 0; i <= x.item.length; i++) {
		if (x.item(i) != null) {
		x.item(i).style.height = '0';
		}
	}
}
</script>

<?php 
include("templates/footer.inc.php");
?>