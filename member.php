<?php 
session_start();
require_once("inc/config.inc.php");
require_once("inc/functions.inc.php");
require_once("util/php-iban/php-iban.php");

include("templates/header.inc.php");

if ($_SESSION['error']) {
	echo $_SESSION['errortxt'] . $_SESSION['errormsg'];
}


if(isset($_POST['submit'])) {

	// trim input and save to SESSION variables
	$_SESSION['first_name'] = trim($_POST['first_name']);
	$_SESSION['last_name'] = trim($_POST['last_name']);
	$_SESSION['email'] = trim($_POST['email']);
	$_SESSION['postal_code'] = trim($_POST['postal_code']);
	$_SESSION['region'] = trim($_POST['region']);
	$_SESSION['street'] = trim($_POST['street']);
	$_SESSION['street_number'] = trim($_POST['street_number']);
	$_SESSION['account_holder'] = trim($_POST['account_holder']);
	$_SESSION['iban'] = trim($_POST['iban']);
	$_SESSION['bic'] = trim($_POST['bic']);
	$_SESSION['password'] = $_POST['password'];
	$_SESSION['password2'] = $_POST['password2'];

	$_SESSION['contribution'] = '5€+ (wird bei der Registration ergänzt)';
	$_SESSION['memberDoc'];
	$_SESSION['sepaDoc'];
	$_SESSION['mid'] = 'XXXX (wird bei der Registration ergänzt)';
	$_SESSION['uid'];
	
	// double check (html required should usually do the job)
	if(empty($_SESSION['first_name']) || empty($_SESSION['last_name']) || empty($_SESSION['email']) || empty($_SESSION['postal_code']) || empty($_SESSION['region']) || empty($_SESSION['street']) || empty($_SESSION['street_number']) || empty($_SESSION['account_holder']) || empty($_SESSION['iban']) || empty($_SESSION['bic'])) {
		$_SESSION['errormsg'] = 'Bitte alle Felder ausfüllen</div></div>';
		$_SESSION['error'] = true;
		header("location: register.php");
	}

	// allow only legitimate characters for the first name
	if (!preg_match("/^[a-zA-Z-\x7f-\xff]*$/",$_SESSION['first_name'])) {
		$_SESSION['errormsg'] = 'Bitte einen gültigen Vornamen eingeben.</div></div>';
		$_SESSION['error'] = true;
		header("location: register.php");
	}

	// allow only legitimate characters for the last name
	if (!preg_match("/^[a-zA-Z-\x7f-\xff]*$/",$_SESSION['last_name'])) {
		$_SESSION['errormsg'] = 'Bitte einen gültigen Nachnahmen eingeben.</div></div>';
		$_SESSION['error'] = true;
		header("location: register.php");
	}
  	
  	// check for vaild email
	if(!filter_var($_SESSION['email'], FILTER_VALIDATE_EMAIL)) {
		$_SESSION['errormsg'] = 'Bitte eine gültige E-Mail-Adresse eingeben</div></div>';
		$_SESSION['error'] = true;
		header("location: register.php");
	} 

	if (!preg_match("/^[0-9]{5}$/",$_SESSION['postal_code'])) {
		$_SESSION['errormsg'] = 'Bitte einen gültige PLZ eingeben.</div></div>';
		$_SESSION['error'] = true;
		header("location: register.php");
	}

	// check for password
	if(strlen($_SESSION['password']) == 0) {
		$_SESSION['errormsg'] = 'Bitte ein password angeben</div></div>';
		$_SESSION['error'] = true;
		header("location: register.php");
	}

	// double check password
	if($_SESSION['password'] != $_SESSION['password2']) {
		$_SESSION['errormsg'] = 'Die Passwörter müssen übereinstimmen</div></div>';
		$_SESSION['error'] = true;
		header("location: register.php");
	}
	
	// use PHPIBAN library to check iban for machine format and valid iso format
	if(!verify_iban($_SESSION['iban'],$machine_format_only=true)) {
		$_SESSION['errormsg'] = 'Dies ist eine ungültige IBAN</div></div>';
		$_SESSION['error'] = true;
		header("location: register.php");
	}

	if (!preg_match("/^[A-Z]{6,6}[A-Z2-9][A-NP-Z0-9]([A-Z0-9]{3,3}){0,1}?$/i", $_SESSION['bic'])) {
		$_SESSION['errormsg'] = 'Bitte einen gültige BIC eingeben.</div></div>';
		$_SESSION['error'] = true;
		header("location: register.php");
	}
	
	// check if email exists
	if(!$_SESSION['error']) { 
		$statement = $pdo->prepare("SELECT * FROM users WHERE email = :email");
		$result = $statement->execute(array('email' => $_SESSION['email']));
		$user = $statement->fetch();
		
		if($user !== false) {
			$_SESSION['errormsg'] = 'Diese E-Mail-Adresse ist bereits vergeben</div></div>';
			$_SESSION['error'] = true;
			header("location: register.php");
		}	
	}
	
	// no error -> continue
	if (!$_SESSION['error']) {
	
		$antrag_header = '  namiko Hannover e.V.
					Hahnenstraße 13
					30167 Hannover
					kontakt@namiko.org
					https://namiko.org';

		// create contract, split document to insert data later
		$_SESSION['memberDoc1'] = '<div>
		<span style="font-size: 3.3em; font-weight: bold;">Aufnahameantrag für die Mitgliedschaft im namiko e.V.</span><br>
		<br>
		<br>
		<br><br>
		<table cellpadding="5" cellspacing="0" style="width: 100%; font-size: 1.3em;">
		 <tr>
		 <td>'. htmlentities($_SESSION['first_name']).' '. htmlentities($_SESSION['last_name']) .'<br>
			'.  htmlentities($_SESSION['street']) . ' ' . htmlentities($_SESSION['street_number']) .'<br>
			'.  htmlentities($_SESSION['postal_code']) .' '. htmlentities($_SESSION['region']) .'<br>
			'.  htmlentities($_SESSION['email']) .'<br>
		 </td>
		 
		 <td style="text-align: right">
			'.nl2br(trim($antrag_header)).'
		 </td>
		 </tr>
		 </table>
		 <span style="font-size:1.3em; font-weight: bold;">
			<br><br><br><br>
			Ich erkenne die Satzung und Ordnungen des Vereins an.<br><br>
			Hiermit ermächtige ich den namiko e.V.,<br>
			meinen Mitgliedsbeitrag von ';

		$_SESSION['memberDoc2'] = '€ und sonstige finanzielle Verbindlichkeiten, welche laut Satzung erhoben werden dürfen, bei Fälligkeit von dem angegebenen Konto per Lastschrift zu erheben.
			<br><br>
			Mit der Speicherung, Übermittlung und Verarbeitung meiner personenbezogenen Daten für Vereinszwecke gemäß Bundesdatenschutzgesetz bin ich einverstanden.
		<br><br><br>
		</span>
		 
		 <table cellpadding="5" cellspacing="0" style="width: 80%;" >
		 
		<tr>
		<td><span style="font-size:1.3em">'. htmlentities($_SESSION['first_name']) .' '. htmlentities($_SESSION['last_name']) .'</span><br><hr>Unterschrift</td>
		</tr>
		</table>
		<br>
		<br>
		<table cellpadding="5" cellspacing="0" style="width: 60%;">
			<tr>
			<td><span style="font-size:1.3em">'. date("d.m.y") .'</span><br><hr>Datum</td>
			<td><span style="font-size:1.3em">'. htmlentities($_SESSION['region']) .'</span><br><hr>Ort</td>
			</tr>
		</table>
		<br><br>
		</div>';

		$_SESSION['sepaDoc1'] = '<div>
		
		<span style="font-size: 3.3em; font-weight: bold;">SEPA Lastschriftmandat</span><br>
		<br>
		<br>
		<br><br>
		<table style="width: 100%">
			<tr>
				<th><span style="font-size:1.5em; font-weight: bold;">Zahlungsempfänger</span></th>
				<th><span style="font-size:1.5em; font-weight: bold;">Kontoinhaber</span></th>
			</tr>
			<tr>
			<td>
				<table cellpadding="5" cellspacing="0" style="width: 100%">
				<tr>
					<td><span style="font-weight: bold;">Name</span></td>
					<td>namiko Hannover e.V.</td>
				</tr>
				<tr>
					<td><span style="font-weight: bold;">Str., Hausnr.</span></td>
					<td>Hahnenstraße Nr. 13</td>
				</tr>
				<tr>
					<td><span style="font-weight: bold;">PLZ, Ort</span></td>
					<td>30167 Hannover</td>
				</tr>
				<tr>
					<td><span style="font-weight: bold;">Gläubiger-ID</span></td>
					<td>'. $creditorId .'</td>
				</tr>
				<tr>
					<td><span style="font-weight: bold;">Mandatsreferenz</span></td>
					<td>';
			
			$_SESSION['sepaDoc2'] = '</td>
				</tr>
				</table>
			</td>


			<td>
				<table cellpadding="5" cellspacing="0" style="width: 100%">
				<tr>
					<td><span style="font-weight: bold;">Name</span></td>
					<td>'. htmlentities($_SESSION['first_name']) .' '. htmlentities($_SESSION['last_name']) .'</td>
				</tr>
				<tr>
					<td><span style="font-weight: bold;">Str., Hausnr.</span></td>
					<td>'. htmlentities($_SESSION['street']) .' '. htmlentities($_SESSION['street_number']) .'</td>
				</tr>
				<tr>
					<td><span style="font-weight: bold;">PLZ, Ort</span></td>
					<td>'. htmlentities($_SESSION['postal_code']) .' '. htmlentities($_SESSION['region']) .'</td>
				</tr>
				<tr>
					<td><span style="font-weight: bold;">IBAN</span></td>
					<td>'. htmlentities($_SESSION['iban']) .'</td>
				</tr>
				<tr>
					<td><span style="font-weight: bold;">BIC</span></td>
					<td>'. htmlentities($_SESSION['bic']) .'</td>
				</tr>
				</table>
			</td>
			</tr>
		</table>

		<span style="font-size:1.3em; font-weight: bold;">
			<br><br><br><br>
			A) Ich ermächtige den namiko e.V., im Rahmen eines Dauermandats, (wiederkehrend) Zahlungen von meinem Konto mittels SEPA-Lastschrift einzuziehen. <br><br>
			B) Zugleich weise ich mein Kreditinstitut an, die von dem namiko e.V. auf mein Konto gezogenen SEPA-Lastschrigten einzulösen.<br><br>
			C) Ich kann innerhalb von acht Wochen, beginnend mit dem Belastungsdatum, die Erstattung des belasteten Betrags verlangen. Es gelten die mit meinem Kreditinstitut vereinbarten Bedingungen.<br><br>
			D) Zahlungen sind generell sofort fällig, daher wird die Ankündigungsfrist auf einen Tag verkürzt. Der Einzug der SEPA Lastschrift erfolgt ca. zwei Bankarbeitstage nach der Bestellung. 
		<br><br><br>
		</span>
		 
		 <table cellpadding="5" cellspacing="0" style="width: 80%;" >
		 
		<tr>
		<td><span style="font-size:1.3em">'. htmlentities($_SESSION['first_name']) .' '. htmlentities($_SESSION['last_name']) .'</span><br><hr>Unterschrift</td>
		</tr>
		</table>
		<br>
		<br>
		<table cellpadding="5" cellspacing="0" style="width: 60%;">
			<tr>
			<td><span style="font-size:1.3em">'. date("d.m.y") .'</span><br><hr>Datum</td>
			<td><span style="font-size:1.3em">'. htmlentities($_SESSION['region']) .'</span><br><hr>Ort</td>
			</tr>
		</table>
		<br><br>
		</div>';

	}
} else {
	$_SESSION['errormsg'] = 'Bitte alle Felder ausfüllen</div></div>';
	$_SESSION['error'] = true;
	header("location: register.php");
}
?>

<div id="reg-con" class="login-background">
</div>

<div id="reg" class="reg-container center-vertical">
	<div class="form-container register">

		<div class="info">
			<span>
			<i class="fa fa-info-circle" aria-hidden="true"></i> Um die Registration abzuschließen, musst du zunächst unserem Mitgliedsantrag zustimmen. Wir werden Dir den Antrag per email zuschicken.
			<br>
			</span>
		</div>

		<form action="loan.php" method="post">
			<label>
				<input type="checkbox"  name="satzungUndOrdnung" value="1" required>Ich erkenne die <a target="_blank" href="media/satzung.pdf">Satzung</a> und <a target="_blank" href="media/geschaeftsordnung.pdf">Ordnungen</a> des Vereins an.
			</label>

			<label>
				<input type="checkbox"  name="data" value="1" required>Ich bin damit einverstanden, dass entsprechend der <a href="<?php echo getSiteUrl().'data.php'; ?>" target="_blank">Datenschutzerklärung</a> personenbezogene Daten erhoben und verarbeitet werden.
			</label>
			
			<div class="info">
				<span>
				<i class="fa fa-info-circle" aria-hidden="true"></i> Wir erheben einen Mitgliedsbeitrag in Höhe von mindestens 5€, um unsere Fixkosten zu decken. Falls du für mehrere Personen mitbestellst bitten wir dich den Beitrag entsprechend anzupassen, da die Kosten mitskalieren. 
				<br>
				</span>
			</div>
			
			<label>Ich entrichte einen monatlichen Mitgliedsbeitrag von
				<input id="inputContribution" type="number" name="contribution" value="5" min="5" required>€
			</label>

			<div style="margin-bottom: 15px"><i class="fa fa-file-text-o" aria-hidden="true"></i><a href="javascript:void(0)" onclick="open_closer(1)"> Mitgliedsantrag anzeigen</a></div>

			<button type="submit" name="member" class="register-btn">Weiter <i class="fa fa-arrow-circle-o-right" aria-hidden="true"></i></button>
		</form>
	</div>
</div>

<!-- Hidden container for popUp -->
<div id="popUp1" class="popUp">
	<div class="spacer sizer">
		<div><a href="javascript:void(0)" title="Close" class="closebtn" onclick="open_closer(1)">&times;</a></div>
		<?php 
		echo $_SESSION['memberDoc1'], $_SESSION['contribution'], $_SESSION['memberDoc2'];
		?>
	</div>
</div>

<script>

function close_error () {
	var x = document.getElementsByClassName('error');
	document.body.classList.remove('noscroll');
	
	for (var i = 0; i <= x.item.length; i++) {
		if (x.item(i) != null) {
		x.item(i).style.height = '0';
		}
	}
}

var state = 0;

// open/close Popups
function open_closer (id) {
	var x;
	x = document.getElementById('popUp'+id);
	
	if (state == 0) {
		document.body.className += 'noscroll';
		x.style.height = '100%';
		state = 1;	
	} else  {
		document.body.classList.remove('noscroll');
		x.style.height = '0';
		state = 0;
	}
}
</script>
<?php 
include("templates/footer.inc.php")
?>