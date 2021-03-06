<?php
session_start();
require_once "inc/config.inc.php";
require_once "inc/functions.inc.php";

include "templates/header.inc.php";

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

$first_name = isset($_SESSION['first_name']) ? $_SESSION['first_name'] : "";
$last_name = isset($_SESSION['last_name']) ? $_SESSION['last_name'] : "";
$email = isset($_SESSION['email']) ? $_SESSION['email'] : "";
$postal_code = isset($_SESSION['postal_code']) ? $_SESSION['postal_code'] : "";
$region = isset($_SESSION['region']) ? $_SESSION['region'] : "";
$street = isset($_SESSION['street']) ? $_SESSION['street'] : "";
$street_number = isset($_SESSION['street_number']) ? $_SESSION['street_number'] : "";
$account_holder = isset($_SESSION['account_holder']) ? $_SESSION['account_holder'] : "";
$IBAN = isset($_SESSION['iban']) ? $_SESSION['iban'] : "";
$BIC = isset($_SESSION['bic']) ? $_SESSION['bic'] : "";

?>

<div id="reg-con" class="login-background">
</div>

<div id="reg" class="reg-container center-vertical">
	<div class="form-container register">
		<form action="member.php" method="post">
			<div><span>Bereits registriert? </span><a href="login.php">zum Login</a></div>
			<div class="spacer3">
				<input id="inputFirstName" placeholder="Vorname" value="<?php echo htmlspecialchars($first_name) ?>" type="text" size="40" maxlength="150" name="first_name" required autofocus>
				<input id="inputLastName" placeholder="Nachname" value="<?php echo htmlspecialchars($last_name) ?>" type="text" size="40" maxlength="150" name="last_name" required>
			</div>

			<div class="">
				<input placeholder="E-mail" value="<?php echo htmlspecialchars($email) ?>" type="email" size="40" maxlength="250" name="email" required>
			</div>

			<div class="">
				<input id="inputPostalCode" placeholder="PLZ" value="<?php echo htmlspecialchars($postal_code) ?>" type="number" size="40" maxlength="5" name="postal_code" required>
				<input placeholder="Ort" value="<?php echo htmlspecialchars($region) ?>" type="text" id="inputRegion" size="40" maxlength="40" name="region" required>
			</div>

			<div class="">
				<input id="inputStreet" placeholder="Straße" value="<?php echo htmlspecialchars($street) ?>" type="text" size="40" maxlength="80" name="street" required>
				<input id="inputNumber" placeholder="Nr." value="<?php echo htmlspecialchars($street_number) ?>" type="text" size="40" maxlength="3" name="street_number" required>
			</div>

			<div class="">
				<input id="inputAccountHolder" placeholder="Kontoinhaber" value="<?php echo htmlspecialchars($account_holder) ?>" type="text" size="40" maxlength="150" name="account_holder" required>
			</div>

			<div class="">
				<span><i class="fa fa-info-circle" aria-hidden="true"></i> Keine Leerzeichen erlaubt</span>
				<input placeholder="IBAN"  value="<?php echo htmlspecialchars($IBAN) ?>"type="text" id="inputIBAN" size="40" maxlength="34" name="iban" required>
			</div>

			<div class="">
				<input placeholder="BIC" value="<?php echo htmlspecialchars($BIC) ?>" type="text" size="40" maxlength="15" name="bic" required>
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
include "templates/footer.inc.php"
?>