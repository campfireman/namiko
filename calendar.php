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
		notify('Die Terminart wurde erfolgreich gespeichert.');
	} else {
		$_SESSION['notification'] = true;
		$_SESSION['notificationmsg'] = 'Die Terminart konnte nicht gespeichert werden.';
		header("location:". htmlspecialchars($_SERVER['PHP_SELF']));
	}
}

if (isset($_POST['event'])) {
	$type = $_POST['type'];
	$start = $_POST['date-start'] .' '. $_POST['time-start'];
	$end = $_POST['date-end'].' '.$_POST['time-end'];
	$created_by = $user['uid'];

	$statement = $pdo->prepare("INSERT INTO events (type, start, end, created_by) VALUES (:type, :start, :end, :created_by)");
	$result = $statement->execute(array('type' => $type, 'start' => $start, 'end' => $end, 'created_by' => $created_by));

	print_r($statement->errorInfo());

	if ($result) {
		$_SESSION['notification'] = true;
		$_SESSION['notificationmsg'] = 'Das Event wurde erfolgreich gespeichert.';
		header("location:". htmlspecialchars($_SERVER['PHP_SELF']));
	} else {
		$_SESSION['notification'] = true;
		$_SESSION['notificationmsg'] = 'Das Event konnte nicht gespeichert werden.';
		header("location:". htmlspecialchars($_SERVER['PHP_SELF']));
	}
}
?>

<div class="sizer">
	<div class="row">
		<div class="col-sm-6 spacer">
			<span class="subtitle2">Terminart hinzufügen</span><br><br>
			<form class="form" method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
				<input type="text" name="event_name" placeholder="Terminart" required><br>
				#<input type="text" name="color" maxlength="6" placeholder="Farbe (Hexadezimal)"><br><br>
				<button type="submit" class="clean-btn blue" name="event_type">Speichern <i class="fa fa-floppy-o" aria-hidden="true"></i></button>
			</form>
		</div>
		<div class="col-sm-6 spacer">
			<span class="subtitle2">Event hinzufügen</span><br><br>
			<form class="form" method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
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
				<span class="subtitle2">Anfang</span><br>
				<label>Datum</label>
				<input type="date" name="date-start" required><br>
				<label>Uhrzeit</label>
				<input type="time" name="time-start" required><br>
				<span class="subtitle2">Ende</span><br>
				<label>Datum</label>
				<input type="date" name="date-end" required><br>
				<label>Uhrzeit</label>
				<input type="time" name="time-end" required><br><br>
				<button type="submit" class="clean-btn blue" name="event">Hinzufügen <i class="fa fa-plus" aria-hidden="true"></i></button>
			</form>
		</div>
	</div>
	<div class="spacer">
		<span class="subtitle2">Termine</span><br><br>
		<form class="order-total">
			<table class="table panel panel-default" style="min-width: 620px">
			<thead>
				<tr>
					<th>Bezeichnung</th>
					<th>Start</th>
					<th>Ende</th>
					<th>Erstellt von</th>
					<th></th>
				</tr>
			</thead>
			<?php
			$statement = $pdo->prepare("SELECT events.*, event_types.name, users.first_name, users.last_name FROM events LEFT JOIN event_types ON events.type = event_types.tyid LEFT JOIN users ON events.created_by = users.uid ORDER BY events.start");
			$result = $statement->execute();

			if ($result) {
				if ($statement->rowCount() > 0) {
					while ($row = $statement->fetch()) {
						echo '<tr>';
						 echo '<td>'. $row['name'] .'</td>';
						 echo '<td>'. $row['start'] .'</td>';
						 echo '<td>'. $row['end'] .'</td>';
						 echo '<td>'. $row['first_name'] .' '. $row['last_name'] .'</td>';
						 echo '<form class="delete">';
						 echo '<input type="hidden" name="eid" value="'. $row['eid'] .'">';
						 echo '<td><button class="red empty" type="submit">&times;</button></td>';
						 echo '</form>';
						echo '</tr>';
					}
				} else {
					echo 'Keine Termine gefunden.';
				}
			} else {
				echo 'Fehler beim Abrufen der Termine.';
			}
			?>
		</table>
	</div>
</div>

<script type="text/javascript">
	$(document).ready(function() {
		$('.delete').submit(function(e) {
			e.preventDefault();
			var form_data = $(this).serialize();
			$(this).closest('tr').fadeOut();

			$.ajax({
				url: "del_event.php",
				type: "POST",
				dataType: "JSON",
				data: form_data
			}).success(function(data) {
				if (data == 0) alert('not successfully deleted!');
			})
		});
	});
</script>

<?php 
include("templates/footer.inc.php")
?>