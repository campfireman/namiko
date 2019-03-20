<?php
session_start();
require_once("inc/config.inc.php");
require_once("inc/functions.inc.php");

//Überprüfe, dass der User eingeloggt ist
//Der Aufruf von check_user() muss in alle internen Seiten eingebaut sein
$user = check_user();

include("templates/header.inc.php");
include("templates/nav.inc.php");

if(isset($_GET['save'])) {
	$save = $_GET['save'];
	
	if($save == 'personal_data') {
		$first_name = trim($_POST['first_name']);
		$last_name = trim($_POST['last_name']);
		
		if($first_name == "" || $last_name == "") {
			$error_msg = "Bitte Vor- und last_name ausfüllen.";
		} else {
			$statement = $pdo->prepare("UPDATE users SET first_name = :first_name, last_name = :last_name, updated_at=NOW() WHERE uid = :uid");
			$result = $statement->execute(array('first_name' => $first_name, 'last_name'=> $last_name, 'uid' => $user['uid'] ));
			
			$success_msg = "Daten erfolgreich gespeichert.";
		}
	} else if($save == 'email') {
		$password = $_POST['password'];
		$email = trim($_POST['email']);
		$email2 = trim($_POST['email2']);
		
		if($email != $email2) {
			$error_msg = "Die eingegebenen E-Mail-Adressen stimmten nicht überein.";
		} else if(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
			$error_msg = "Bitte eine gültige E-Mail-Adresse eingeben.";
		} else if(!password_verify($password, $user['password'])) {
			$error_msg = "Bitte korrektes Passwort eingeben.";
		} else {
			$statement = $pdo->prepare("UPDATE users SET email = :email WHERE uid = :uid");
			$result = $statement->execute(array('email' => $email, 'uid' => $user['uid'] ));
				
			$success_msg = "E-Mail-Adresse erfolgreich gespeichert.";
		}
		
	} else if($save == 'password') {
		$passwordOld = $_POST['passwordOld'];
		$passwordNew = trim($_POST['passwordNew']);
		$passwordNew2 = trim($_POST['passwordNew2']);
		
		if($passwordNew != $passwordNew2) {
			$error_msg = "Die eingegebenen Passwörter stimmten nicht überein.";
		} else if($passwordNew == "") {
			$error_msg = "Das Passwort darf nicht leer sein.";
		} else if(!password_verify($passwordOld, $user['password'])) {
			$error_msg = "Bitte korrektes Passwort eingeben.";
		} else {
			$password_hash = password_hash($passwordNew, PASSWORD_DEFAULT);
				
			$statement = $pdo->prepare("UPDATE users SET password = :password WHERE uid = :uid");
			$result = $statement->execute(array('password' => $password_hash, 'uid' => $user['uid'] ));
				
			$success_msg = "Passwort erfolgreich gespeichert.";
		}
		
	}
}

?>

<h3 class="header">Einstellungen</h3>
<div class="sizer spacer">



<?php 
if(isset($success_msg) && !empty($success_msg)):
?>
	<div class="alert alert-success">
		<a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
	  	<?php echo $success_msg; ?>
	</div>
<?php 
endif;
?>

<?php 
if(isset($error_msg) && !empty($error_msg)):
?>
	<div class="alert alert-danger">
		<a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
	  	<?php echo $error_msg; ?>
	</div>
<?php 
endif;
?>

<div>

  <!-- Nav tabs -->
  <ul class="nav nav-tabs" role="tablist">
    <li role="presentation" class="active"><a href="#data" aria-controls="home" role="tab" data-toggle="tab">Persönliche Daten</a></li>
    <li role="presentation"><a href="#email" aria-controls="profile" role="tab" data-toggle="tab">E-Mail</a></li>
    <li role="presentation"><a href="#passwort" aria-controls="messages" role="tab" data-toggle="tab">Passwort</a></li>
  </ul>

  <!-- Persönliche Daten-->
  <div class="tab-content">
    <div role="tabpanel" class="tab-pane active" id="data">
    	<br>
    	<form action="?save=personal_data" method="post" class="form">
    		<div class="row">
    			<label for="inputFirstName" class="col-sm-2">Vorname</label>
    			<div class="col-sm-10">
    				<input id="inputFirstName" name="first_name" type="text" value="<?php echo htmlentities($user['first_name']); ?>" required>
    			</div>
    		</div><br>
    		
    		<div class="row">
    			<label for="inputLastName" class="col-sm-2">Nachname</label>
    			<div class="col-sm-10">
    				<input id="inputLastName" name="last_name" type="text" value="<?php echo htmlentities($user['last_name']); ?>" required>
    			</div>
    		</div><br>
    		
    		<div class="row">
			    <div class="col-sm-offset-2 col-sm-10">
			      <button type="submit" class="clean-btn green">Speichern</button>
			    </div>
			</div>
    	</form>
    </div>
    
    <!-- Änderung der E-Mail-Adresse -->
    <div role="tabpanel" class="tab-pane" id="email">
    	<br>
    	<p><i class="fa fa-info-circle" aria-hidden="true"></i> Zum Änderen deiner E-Mail-Adresse gib bitte dein aktuelles Passwort sowie die neue E-Mail-Adresse ein.</p><br>
    	<form action="?save=email" method="post" class="form">
    		<div class="row">
    			<label for="inputPasswort" class="col-sm-2 control-label">Passwort</label>
    			<div class="col-sm-10">
    				<input id="inputPasswort" name="password" type="password" required>
    			</div>
    		</div><br>
    		
    		<div class="row">
    			<label for="inputEmail" class="col-sm-2 control-label">E-Mail</label>
    			<div class="col-sm-10">
    				<input id="inputEmail" name="email" type="email" value="<?php echo htmlentities($user['email']); ?>" required>
    			</div>
    		</div><br>
    		
    		
    		<div class="row">
    			<label for="inputEmail2" class="col-sm-2 control-label">E-Mail (wiederholen)</label>
    			<div class="col-sm-10">
    				<input id="inputEmail2" name="email2" type="email"  required>
    			</div>
    		</div><br>
    		
    		<div class="row">
			    <div class="col-sm-offset-2 col-sm-10">
			      <button type="submit" class="clean-btn green">Speichern</button>
			    </div>
			</div>
    	</form>
    </div>
    
    <!-- Änderung des Passworts -->
    <div role="tabpanel" class="tab-pane" id="passwort">
    	<br>
    	<p><i class="fa fa-info-circle" aria-hidden="true"></i> Zum Änderen deines Passworts gib bitte dein aktuelles Passwort sowie das neue Passwort ein.</p><br>
    	<form action="?save=password" method="post" class="form">
    		<div class="row">
    			<label for="inputPasswort" class="col-sm-2">Altes Passwort</label>
    			<div class="col-sm-10">
    				<input id="inputpasswordOld" name="passwordOld" type="password" required>
    			</div>
    		</div><br>
    		
    		<div class="row">
    			<label for="inputpasswordNew" class="col-sm-2">Neues Passwort</label>
    			<div class="col-sm-10">
    				<input id="inputpasswordNew" name="passwordNew" type="password" required>
    			</div>
    		</div><br>
    		
    		
    		<div class="row">
    			<label for="inputpasswordNew2" class="col-sm-2">Neues Passwort (wiederholen)</label>
    			<div class="col-sm-10">
    				<input id="inputpasswordNew2" name="passwordNew2" type="password"  required>
    			</div>
    		</div><br>
    		
    		<div class="row">
			    <div class="col-sm-offset-2 col-sm-10">
			      <button type="submit" class="clean-btn green">Speichern</button>
			    </div>
			</div>
    	</form>
    </div>
  </div>

</div>


</div>
<?php 
include("templates/footer.inc.php")
?>
