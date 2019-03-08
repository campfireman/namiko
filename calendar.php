<?php
session_start();
require_once("inc/config.inc.php");
require_once("inc/functions.inc.php");
//ini_set('display_errors', 1);

//Überprüfe, dass der User eingeloggt ist
//Der Aufruf von check_user() muss in alle internen Seiten eingebaut sein
$user = check_user();
check_admin();

include("templates/header.inc.php");
include("templates/nav.inc.php");
include("templates/admin-nav.inc.php");

if (isset($_POST['event_type'])) {
	$name = $_POST['event_name'];
	$color = '#'. $_POST['color'];

	$statement = $pdo->prepare("INSERT INTO event_types (name, color) VALUES (:name, :color)");
	$result = $statement->execute(array('name' => $name, 'color' => $color));

	if ($result) {
		$_SESSION['notification'] = true;
		$_SESSION['notificationmsg'] = 'Die Terminart wurde erfolgreich gespeichert.';
		header("location:". htmlspecialchars($_SERVER['REQUEST_URI']));
	} else {
		$_SESSION['notification'] = true;
		$_SESSION['notificationmsg'] = 'Die Terminart konnte nicht gespeichert werden.';
		header("location:". htmlspecialchars($_SERVER['REQUEST_URI']));
	}
}

if (isset($_POST['event'])) {
	$type = $_POST['type'];
	$start = $_POST['start'];
	$end = $_POST['end'];
	$created_by = $user['uid'];

	$statement = $pdo->prepare("INSERT INTO events (type, start, end, created_by) VALUES (:type, :start, :end, :created_by)");
	$result = $statement->execute(array('type' => $type, 'start' => $start, 'end' => $end, 'created_by' => $created_by));

	print_r($statement->errorInfo());

	if ($result) {
		$_SESSION['notification'] = true;
		$_SESSION['notificationmsg'] = 'Das Event wurde erfolgreich gespeichert.';
		header("location:". htmlspecialchars($_SERVER['REQUEST_URI']));
	} else {
		$_SESSION['notification'] = true;
		$_SESSION['notificationmsg'] = 'Das Event konnte nicht gespeichert werden.';
		header("location:". htmlspecialchars($_SERVER['REQUEST_URI']));
	}
}
?>

<div class="sizer">
	<div class="row">
		<div class="col-sm-6 spacer">
			<span class="subtitle2">Terminart hinzufügen</span><br><br>
			<form class="form" method="post" action="<?php echo htmlspecialchars($_SERVER['REQUEST_URI']); ?>">
				<input type="text" name="event_name" placeholder="Terminart" required><br>
				#<input type="text" name="color" maxlength="6" placeholder="Farbe (Hexadezimal)"><br><br>
				<button type="submit" class="clean-btn blue" name="event_type">Speichern <i class="fa fa-floppy-o" aria-hidden="true"></i></button>
			</form>
		</div>
		<div class="col-sm-6 spacer">
			<span class="subtitle2">Event hinzufügen</span><br><br>
			<form class="form" method="post" action="<?php echo htmlspecialchars($_SERVER['REQUEST_URI']); ?>">
				<select name="type" required>
					<option value="-1">- Terminart wählen -</option>
					<?php
					$statement = $pdo->prepare("SELECT * FROM event_types ORDER BY tyid");
					$result = $statement->execute();

					while ($row = $statement->fetch()) {
						echo '<option value="'. $row['tyid'] .'" style="color: '. $row['color'] .'">'. $row['name'] .'</option>';
					}
					?>
				</select><br>
				<label>Anfang:</label>
				<input type="datetime-local" name="start" required><br>
				<label>Ende:</label>
				<input type="datetime-local" name="end" required><br><br>
				<button type="submit" class="clean-btn blue" name="event">Hinzufügen <i class="fa fa-plus" aria-hidden="true"></i></button>
			</form>
		</div>
	</div>
</div>

<?php 
include("templates/footer.inc.php")
?>