<?php 
session_start();
require_once("inc/config.inc.php");
require_once("inc/functions.inc.php");

include("templates/header.inc.php");

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
			 			 <div class="center-vertical">
			 			 <div><h1><span class="emph">Fehler</span></h1></div>';

?>

<div id="reg-con" class="login-background">
</div>

<div id="reg" class="reg-container center-vertical">
	<div class="form-container register">
		<form action="member.php" method="post">

			<div class="">
				<input id="inputFirstName" placeholder="Vorname" value="<?php echo htmlspecialchars($_SESSION['first_name']) ?>" type="text" size="40" maxlength="150" name="first_name" required autofocus>
				<input id="inputLastName" placeholder="Nachname" value="<?php echo htmlspecialchars($_SESSION['last_name']) ?>" type="text" size="40" maxlength="150" name="last_name" required>
			</div>

			<div class="">
				<input placeholder="E-mail" value="<?php echo htmlspecialchars($_SESSION['email']) ?>" type="email" size="40" maxlength="250" name="email" required>
			</div>

			<div class="">
				<input id="inputPostalCode" placeholder="PLZ" value="<?php echo htmlspecialchars($_SESSION['postal_code']) ?>" type="number" size="40" maxlength="5" name="postal_code" required>
				<input placeholder="Ort" value="<?php echo htmlspecialchars($_SESSION['region']) ?>" type="text" id="inputRegion" size="40" maxlength="40" name="region" required>
			</div>

			<div class="">
				<input id="inputStreet" placeholder="Straße" value="<?php echo htmlspecialchars($_SESSION['street']) ?>" type="text" size="40" maxlength="80" name="street" required>
				<input id="inputNumber" placeholder="Nr." value="<?php echo htmlspecialchars($_SESSION['street_number']) ?>" type="number" size="40" maxlength="3" name="street_number" required>
			</div>

			<div class="">
				<input id="inputAccountHolder" placeholder="Kontoinhaber" value="<?php echo htmlspecialchars($_SESSION['account_holder']) ?>" type="text" size="40" maxlength="150" name="account_holder" required>
			</div>

			<div class="">
				<span><i class="fa fa-info-circle" aria-hidden="true"></i> Keine Leerzeichen erlaubt</span>
				<input placeholder="IBAN"  value="<?php echo htmlspecialchars($_SESSION['iban']) ?>"type="text" id="inputIBAN" size="40" maxlength="34" name="iban" required>
			</div>

			<div class="">
				<input placeholder="BIC" value="<?php echo htmlspecialchars($_SESSION['bic']) ?>" type="text" size="40" maxlength="15" name="bic" required>
			</div>

			<div class="">
				<input placeholder="Passwort" type="password" size="40"  maxlength="250" name="password" required>
			</div> 

			<div class="">
				<input placeholder="Passwort wiederholen" type="password" size="40" maxlength="250" name="password2" required>
			</div> 

			<button class="register-btn" name="submit" type="submit">Weiter <i class="fa fa-arrow-circle-o-right" aria-hidden="true"></i></button>
		</form>
	</div>
</div>



<script>

// function for closing errors 
function close_error () {
	var x = document.getElementsByClassName('error');
	document.body.classList.remove('noscroll');
	
	// closing all errors 
	for (var i = 0; i <= x.item.length; i++) {
		if (x.item(i) != null) {
		x.item(i).style.height = '0';
		}
	}
}
</script>
<?php 
include("templates/footer.inc.php")
?>