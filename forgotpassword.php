<?php 
session_start();
require_once("inc/config.inc.php");
require_once("inc/functions.inc.php");
require('util/phpmailer/Exception.php');
require('util/phpmailer/PHPMailer.php');
require('util/phpmailer/SMTP.php');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

include("templates/header.inc.php");
?>

<div id="reg-con" class="login-background">
</div>

<div class="center-vertical" style="height: 100vh">
 <div class="login form-container">


<?php 
$showForm = true;
 
if(isset($_GET['send']) ) {
	if(!isset($_POST['email']) || empty($_POST['email'])) {
		$error = "<b>Bitte eine E-Mail-Adresse eintragen</b>";
	} else {
		$statement = $pdo->prepare("SELECT * FROM users WHERE email = :email");
		$result = $statement->execute(array('email' => $_POST['email']));
		$user = $statement->fetch();		
 
		if($user === false) {
			$error = "<b>Kein Benutzer gefunden</b>";
		} else {
			
			$passwordcode = random_string();
			$statement = $pdo->prepare("UPDATE users SET passwordcode = :passwordcode, passwordcode_time = NOW() WHERE uid = :uid");
			$result = $statement->execute(array('passwordcode' => sha1($passwordcode), 'uid' => $user['uid']));
			
			$first_name = $user['first_name'];
			$email = $user['email'];

			$subject = utf8_decode("Passwort zurückzusetzen"); 
			$url_passwordcode = getSiteURL().'resetpassword.php?uid='.$user['uid'].'&code='.$passwordcode;
			$text = 'für deinen Account auf namiko.org wurde nach einem neuen Passwort gefragt. Um ein neues Passwort zu vergeben, rufe innerhalb der nächsten 24 Stunden die folgende Website auf:
				'.$url_passwordcode.'
 
				Sollte dir dein Passwort wieder eingefallen sein oder hast du dies nicht angefordert, so bitte ignoriere diese E-Mail.
 
				Viele Grüße,
				namiko-Team';

			 
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
				    $mail->addAddress($email, $first_name);     // Add a recipient
				    $mail->addReplyTo('kontakt@namiko.org', 'Kontakt');

				    //Content
				    $mail->isHTML(true);                                  // Set email format to HTML
				    $mail->Subject = $subject;
				    $mail->Body    = '<h3>Moin, '. htmlspecialchars($first_name) .'!</h3>'. $text;
				    $mail->AltBody = 'Moin, '. htmlspecialchars($first_name) .'!'. $text;

				    $mail->send();
				    $result = true;
				} catch (Exception $e) {
				    $result = false;
				}

			if ($result) {
				echo "Ein Link um dein Passwort zurückzusetzen wurde an deine E-Mail-Adresse gesendet.";	
				$showForm = false;
			} else {
				echo "Ein Fehler ist aufgertreten.";	
				$showForm = false;
			} 
		}
	}
}
 
if($showForm):
?> 
	Gib hier deine E-Mail-Adresse ein, um ein neues Passwort anzufordern.<br><br>
	 
	<?php
	if(isset($error) && !empty($error)) {
		echo $error;
	}
	
	?>
	<form action="?send=1" method="post">
		<input placeholder="E-Mail" name="email" type="email" value="<?php echo isset($_POST['email']) ? htmlentities($_POST['email']) : ''; ?>" required>
		<br>
		<input  class="login-btn" type="submit" value="Neues Passwort">
	</form> 
<?php
endif; //Endif von if($showForm)
?>

</div> 
</div><!-- /container -->
 

<?php 
include("templates/footer.inc.php")
?>