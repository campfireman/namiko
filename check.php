<?php 
session_start();
require_once("inc/config.inc.php");
require_once("inc/functions.inc.php");

include("templates/header.inc.php");

if ($_SESSION['error']) {
	echo $_SESSION['errortxt'] . $_SESSION['errormsg'];
}

if (isset($_POST['loaned'])) {


	if (!isset($_POST['agreeLoan'])) {
		$_SESSION['errormsg'] = 'Du musst dem Darlehn zustimmen</div></div>';
		$_SESSION['error'] = true;
		header("location: loan.php");
	}

	if (!$_SESSION['error']) {
		$_SESSION['loan'] = $_POST['loan'];
	}


	
} else {
	$_SESSION['errormsg'] = 'Du musst erst zustimmen</div></div>';
	$_SESSION['error'] = true;
	header("location: loan.php");
}
?>

<div id="reg-con" class="login-background">
</div>

<div id="reg" class="reg-container center-vertical">
	<div class="form-container register">
		<div class="info">
			<span>
			<i class="fa fa-info-circle" aria-hidden="true"></i> Wir erheben den Mitgliedsbeitrag per SEPA Lastschriftverfahren. Außerdem wickeln jegliche Wareneinkäufe auf diese Weise ab.
			<br>
			</span>
		</div>
		
		<form action="agree.php" method="post">
		
			<label>
			<input type="checkbox"  name="SEPAagree" value="1" required>Ich ermächtige den namiko e.V., im Rahmen eines Dauermandats, (wiederkehrend) Zahlungen von meinem Konto mittels SEPA-Lastschrift einzuziehen. B) Zugleich weise ich mein Kreditinstitut an, die von dem namiko e.V. auf mein Konto gezogenen SEPA-Lastschrigten einzulösen. C) Ich kann innerhalb von acht Wochen, beginnend mit dem Belastungsdatum, die Erstattung des belasteten Betrags verlangen. Es gelten die mit meinem Kreditinstitut vereinbarten Bedingungen. D) Zahlungen sind generell sofort fällig, daher wird die Ankündigungsfrist auf einen Tag verkürzt. Der Einzug der SEPA Lastschrift erfolgt ca. zwei Bankarbeitstage nach der Bestellung. 
			</label>
			<div  style="margin-bottom: 19px; margin-top: 10px;"><i class="fa fa-file-text-o" aria-hidden="true"></i><a href="javascript:void(0)" onclick="open_closer(2)"> Lastschriftmandat anzeigen</a></div>
			<button type="submit" name="sepa" class="login-btn">Registrieren <i class="fa fa-handshake-o" aria-hidden="true"></i></button>
		
		</form>

	</div>
</div>

<!-- Hidden container for popUp -->
<div id="popUp2" class="popUp">
	<div class="sizer">
		<div><a href="javascript:void(0)" title="Close" class="closebtn" onclick="open_closer(2)">&times;</a></div>
		<?php 
		echo $_SESSION['sepaDoc1'], $_SESSION['mid'], $_SESSION['sepaDoc2'];
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