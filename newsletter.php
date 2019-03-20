<?php 
session_start();
require_once("inc/config.inc.php");
require_once("inc/functions.inc.php");
ini_set('display_errors', 1);

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

if (isset($_POST['newsletter'])) {
	$first_name = trim($_POST['first_name']);
	$last_name = trim($_POST['last_name']);
	$email = trim($_POST['email']);
	$test = true;

	if (empty($first_name) || empty($email)) {
		$_SESSION['error'] = true;
		$_SESSION['errormsg'] = 'Du musst alle Pflichtfelder ausfüllen.</div></div>';
		header('location:'. htmlspecialchars($_SERVER['PHP_SELF']));
	}

	// allow only legitimate characters for the first name
	if (!preg_match("/^[a-zA-Z\x7f-\xff]*$/", $first_name)) {
		$_SESSION['errormsg'] = 'Bitte einen gültigen Vornamen eingeben.</div></div>';
		$_SESSION['error'] = true;
		header('location:'. htmlspecialchars($_SERVER['PHP_SELF']));
	}

	// allow only legitimate characters for the last name
	if (!preg_match("/^[a-zA-Z\x7f-\xff]*$/", $last_name)) {
		$_SESSION['errormsg'] = 'Bitte einen gültigen Nachnamen eingeben.</div></div>';
		$_SESSION['error'] = true;
		header('location:'. htmlspecialchars($_SERVER['PHP_SELF']));
	}

	if(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
		$_SESSION['errormsg'] = 'Bitte eine gültige E-Mail-Adresse eingeben</div></div>';
		$_SESSION['error'] = true;
		header('location:'. htmlspecialchars($_SERVER['PHP_SELF']));
	} 

	$statement = $pdo->prepare("SELECT * FROM newsletter_recipients WHERE email = :email");
	$result = $statement->execute(array('email' => $email));
	$user = $statement->fetch();
	
	if($user !== false) {
		$_SESSION['errormsg'] = 'Diese E-Mail-Adresse ist bereits vergeben</div></div>';
		$_SESSION['error'] = true;
		header("location:". htmlspecialchars($_SERVER['PHP_SELF']));
	}

	$statement = $pdo->prepare("SELECT * FROM users WHERE email = :email");
	$result = $statement->execute(array('email' => $email));
	$user = $statement->fetch();
	
	if($user !== false) {
		$_SESSION['errormsg'] = 'Diese E-Mail-Adresse ist bereits vergeben</div></div>';
		$_SESSION['error'] = true;
		header("location:". htmlspecialchars($_SERVER['PHP_SELF']));
	}	

	if (!$_SESSION['error']) {
		$statement = $pdo->prepare("INSERT INTO newsletter_recipients (first_name,last_name, email, created_by) VALUES (:first_name, :last_name, :email, :created_by)");
		$result = $statement->execute(array('first_name' => $first_name, 'last_name' => $last_name, 'email' => $email, 'created_by' => 1));
		print_r($statement->errorInfo());

		if ($result) {
			$statement = $pdo->prepare("SELECT rid, created_at FROM newsletter_recipients WHERE email = '$email'");
			$result = $statement->execute();

			if ($result) {

				while ($row = $statement->fetch()) {

					$id = '?rid='. $row['rid'] .'&created_at='. urlencode($row['created_at']);

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
					    $mail->addReplyTo('kontakt@namiko.org');

					    //Content
					    $mail->isHTML(true);
					    $mail->Subject = utf8_decode('Newsletter Bestätigung');
					    $mail->Body    = '<h1>Moin '. $first_name .'!</h1>
					    					<p>Um deine Anmeldung für den Newsletter abzuschliessen, besuche folgende Adresse:<br><br><span style="font-weight: 600; font-size: 30px;"><a href="'. getSiteUrl() .'verify_newsletter.php'. $id .'">Bestätigen</a></span><br><br>
					    					Wir freuen uns sehr mit Dir zusammenzuarbeiten.<br><br><span style="font-style: italic">Dein namiko Hannover e.V. Team</span><br><br><br><br><br><br>
					    					Bei Rückfragen einfach an kontakt@namiko.org schreiben.</p>';
					    $mail->AltBody = 'Moin '. $first_name .'!
					    					Um deine Anmeldung für den Newsletter abzuschliessen, besuche folgende Adresse: '. getSiteUrl() .'verify_newsletter.php'. $id .'
					    					Wir freuen uns sehr mit Dir zusammenzuarbeiten. Dein namiko Hannover e.V. Team
					    					Bei Rückfragen einfach an kontakt@namiko.org schreiben.';

					    $mail->send();
					    
					    $_SESSION['errormsg'] = 'Die Bestätigungsmail wurde erfolgreich verschickt. Überprüfe dein Postfach.</div></div>';
						$_SESSION['error'] = true;
						header('location:'. htmlspecialchars($_SERVER['PHP_SELF']));
					} catch (Exception $e) {
					    $_SESSION['errormsg'] = 'Die Bestätigungsmail konnte nicht verschickt werden. Versuche es noch einmal oder wende Dich an kontakt@namiko.org</div></div>';
						$_SESSION['error'] = true;
						header('location:'. htmlspecialchars($_SERVER['PHP_SELF']));
					}
				}
			}
		}
	}
}
?>

<div class="login-background">
</div>

<div id="reg" class="center-vertical" style="height: 100vh">
	<div class="login form-container">
		<span class="subtitle2">Newsletter erhalten</span><br><br>

			<span>
			<i class="fa fa-info-circle" aria-hidden="true"></i> Damit Du den Newsletter erhalten kannst, musst Du nach Absenden des Formulars auf den Bestätigungslink in Deinem Postfach clicken.
			<br>
			</span><br>
		<form action="<?php htmlspecialchars($_SERVER['PHP_SELF']) ?>" method="post">
			<input type="text" name="first_name" placeholder="Vorname" required>
			<input type="text" name="last_name" placeholder="Nachname (optional)">
			<input type="email" name="email" placeholder="E-Mail" required><br><br>
			<div class="checkbox">
				<label>
					<input type="checkbox" name="agree">
					A) Ich bin damit einverstanden, dass entsprechend der <a href="<?php echo getSiteUrl().'data.php'; ?>" target="_blank">Datenschutzerklärung</a> personenbezogene Daten (in diesem Falle Name und Email) erhoben und verarbeitet werden.<br>
					B) Ich bin ausdrücklich damit einverstanden, den Newsletter zu erhalten und weiß, dass ich diesen jederzeit problemlos abmelden kann.
				</label>
			</div>
			<button type="submit" name="newsletter" class="login-btn">Abschicken <i class="fa fa-paper-plane" aria-hidden="true"></i></button>
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