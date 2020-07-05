<?php
session_start();
require_once "inc/config.inc.php";
require_once "inc/functions.inc.php";

//Überprüfe, dass der User eingeloggt ist
//Der Aufruf von check_user() muss in alle internen Seiten eingebaut sein
$user = check_user();
include "templates/header.inc.php";

?>
<div class="notification center-vertical">
	<div class="box">
<?php
$showForm = true;

if (isset($_GET['send'])) {

    $checker = $_POST['checker'];
    echo '<script>console.log("' . $checker . '")</script>';

    if ($checker == $user['verify_code']) {

        $selector = $user['uid'];
        $statement = $pdo->prepare("UPDATE users SET rights = 1 WHERE uid = '$selector'");
        $result = $statement->execute();

        if ($result) {
            $showForm = false;
            echo 'Verifzierung erfolgreich: <a href="internal.php" title="zur Hauptseite">Zur Hauptseite</a>';
        }

    } else {
        echo "Der Code stimmt nicht überein. Überprüfe Deine Angabe.<br>";

    }

}

if ($showForm) {
    if ($user['rights'] == 0) {
        ?>


		<span>Bitte bestätige Deinen Account mit dem Code, den du <span class="emph">per Email</span> erhalten hast:</span><br><br>
		<form class="form" action="?send=1" method="post">
		<div><input type="text" name="checker" placeholder="CODE" maxlength="8"></div><br><br>
		<div><button class="register-btn" type="submit" name="submit">Verifizieren <i class="fa fa-check" aria-hidden="true"></i></button></div>
		</form>

<?php
} else {
        header("location: internal.php");
    }
}
?>
	</div>
</div>
