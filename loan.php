<?php
session_start();
require_once "inc/config.inc.php";
require_once "inc/functions.inc.php";

include "templates/header.inc.php";

if ($_SESSION['error']) {
    echo $_SESSION['errortxt'] . $_SESSION['errormsg'];
}

if (isset($_POST['member'])) {

    if (!isset($_POST['satzungUndOrdnung'])) {
        $_SESSION['errormsg'] = 'Du musst unserer Satzung und Ordnung zustimmen</div></div>';
        $_SESSION['error'] = true;
        header("location: member.php");
    }

    if (!$_SESSION['error']) {
        $_SESSION['contribution'] = $_POST['contribution'];
    }

} else {
    $_SESSION['errormsg'] = 'Du musst erst zustimmen</div></div>';
    $_SESSION['error'] = true;
    header("location: member.php");
}
?>

<div id="reg-con" class="login-background">
</div>

<div id="reg" class="reg-container center-vertical">
	<div class="form-container register">
		<div class="info">
			<span>
			<i class="fa fa-info-circle" aria-hidden="true"></i> Bei jedem Einkauf geht der Verein in Vorleistung. Außerdem bestellen wir häufig Großgebinde, deswegen benötigen wir von unseren Mitgliedern ein Darlehen, um die notwendige Liquidität zu gewährleisten. Versuche die ungefähre Summe Deiner monatlichen Bestellungen abzuschätzen.
			<br>
			Der Darlehen wird spätestens 3 Monate nach dem Austritt zurückgezahlt. Nach Erhalt des Darlehens ist Dein Beitritt abgeschlossen. Der Darlehen, sowie alle anderen Posten werden per Lastschrift abgebucht.
			</span>
		</div>

		<form action="check.php" method="post">
			<div class="loan-btn">
				<label>
					<input type="radio" name="loan" value="25" checked>25€
				</label>
				<label>
					<input type="radio" name="loan" value="50">50€
				</label>
				<label>
					<input type="radio" name="loan" value="100">100€
				</label>
				<label>
					<input type="radio" name="loan" value="150">150€
				</label>
				<label>
					<input type="radio" name="loan" value="200">200€
				</label>
			</div><br>
			<div>
				<label>
					<input type="checkbox"  name="agreeLoan" value="1" required>Ich erkläre mich bereit, das Mitgliedsdarlehen von <span id="loan"></span>€ zu entrichten.
				</label>
			</div>
			<button type="submit" name="loaned" class="register-btn">Weiter <i class="fa fa-arrow-circle-o-right" aria-hidden="true"></i></button>
		</form>

	</div>
</div>

<script>

function getLoan () {
	var loan = $("input:radio[name ='loan']:checked").val();
	$('#loan').html(loan);
}

(function insertLoan () {
	getLoan();
	$("input:radio[name ='loan']").click(function () {
		getLoan();
	})

})();

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
include "templates/footer.inc.php"
?>