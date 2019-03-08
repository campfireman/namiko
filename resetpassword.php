<?php 
session_start();
require_once("inc/config.inc.php");
require_once("inc/functions.inc.php");

include("templates/header.inc.php");
?>

<div id="reg-con" class="login-background">
</div>

 <div class="center-vertical" style="height: 100vh">
 <div class="login form-container">

<?php



if(!isset($_GET['uid']) || !isset($_GET['code'])) {
	error("Leider wurde beim Aufruf dieser Website kein Code zum Zurücksetzen deines Passworts übermittelt");
}



$showForm = true; 
$uid = $_GET['uid'];
$code = $_GET['code'];
 
//Abfrage des Nutzers
$statement = $pdo->prepare("SELECT * FROM users WHERE uid = :uid");
$result = $statement->execute(array('uid' => $uid));
$user = $statement->fetch();
 
//Überprüfe dass ein Nutzer gefunden wurde und dieser auch ein Passwortcode hat
if($user === null || $user['passwordcode'] === null) {
	error("Der Benutzer wurde nicht gefunden oder hat kein neues Passwort angefordert.");
}
 
if($user['passwordcode_time'] === null || strtotime($user['passwordcode_time']) < (time()-24*3600) ) {
	error("Dein Code ist leider abgelaufen. Bitte benutze die Passwort vergessen Funktion erneut.");
}
 
 
//Überprüfe den Passwortcode
if(sha1($code) != $user['passwordcode']) {
	error("Der übergebene Code war ungültig. Stell sicher, dass du den genauen Link in der URL aufgerufen hast. Solltest du mehrmals die Passwort-vergessen Funktion genutzt haben, so ruf den Link in der neuesten E-Mail auf.");
}
 
//Der Code war korrekt, der Nutzer darf ein neues Passwort eingeben
 
if(isset($_GET['send'])) {
	$password = $_POST['password'];
	$password2 = $_POST['password2'];
	$uid = $_GET['uid'];
	
	if($password != $password2) {
		$msg =  "Bitte identische Passwörter eingeben";
	} else { //Speichere neues Passwort und lösche den Code
		$passwordhash = password_hash($password, PASSWORD_DEFAULT);
		$statement = $pdo->prepare("UPDATE users SET password = :passwordhash, passwordcode = NULL, passwordcode_time = NULL WHERE uid = :uid");
		$result = $statement->execute(array('passwordhash' => $passwordhash, 'uid'=> $uid ));
		
		if($result) {
			$msg = "Dein Passwort wurde erfolgreich geändert. <a href='login.php'>Zum Login</a>";
			$showForm = false;
		}
	}
}


?>

 
<span class="subtitle2">Neues Passwort vergeben</span><br><br><br>
<?php 
if(isset($msg)) {
	echo $msg;
}

if($showForm):
?>

<form class="form" action="?send=1&amp;uid=<?php echo htmlentities($uid); ?>&amp;code=<?php echo htmlentities($code); ?>" method="post">
<label for="password">Bitte gib ein neues Passwort ein:</label><br>
<input type="password" id="password" name="password" required><br>
 
<label for="password2">Passwort erneut eingeben:</label><br>
<input type="password" id="password2" name="password2" required><br>
 
<input type="submit" value="Passwort speichern" class="login-btn">
</form>
<?php 
endif;
?>

</div> 
</div>
</div><!-- /container -->
 

<?php 
include("templates/footer.inc.php")
?>