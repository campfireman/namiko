<?php
/* Login functionality thanks to: https://github.com/PHP-Einfach/loginscript Thank you very much Nils Reimers! */

session_start();
require_once("inc/config.inc.php");
require_once("inc/functions.inc.php");
ini_set("display_errors", 1);

//Überprüfe, dass der User eingeloggt ist
//Der Aufruf von check_user() muss in alle internen Seiten eingebaut sein
$user = check_user();
check_consul();

include("templates/header.inc.php");
include("templates/nav.inc.php");
include("templates/admin-nav.inc.php");

if (isset($_POST['addProducer'])) {
	$producerName = $_POST['producerName'];
	$description = $_POST['description'];

	$statement = $pdo->prepare("SELECT producerName FROM producers WHERE producerName = :producerName");
	$result = $statement->execute(array('producerName' => $producerName));

	if (!$result) {
		$_SESSION['notification'] = true;
		$_SESSION['notificationmsg'] = 'Der Herstellername ist bereits vergeben.';
	} else {
		$statement = $pdo->prepare("INSERT INTO producers (producerName, description) VALUES (:producerName, :description)");
		$result = $statement->execute(array('producerName' => $producerName, 'description' => $description));

		if ($result) {
			$_SESSION['notification'] = true;
			$_SESSION['notificationmsg'] = 'Hersteller bzw. Lieferant wurde erfolgreich gespeichert.';
			header("Location: " . $_SERVER['REQUEST_URI']);
		} else {
			$_SESSION['notification'] = true;
			$_SESSION['notificationmsg'] = 'Es gab einen Fehler.';
			header("Location: " . $_SERVER['REQUEST_URI']);
		}
	}
}

$statement = $pdo->prepare("SELECT * FROM producers ORDER BY pro_id DESC");
$result = $statement->execute();

if ($result) {
	if ($statement->rowCount() > 0) {
		$table = '
			<table class="table panel panel-default">
			<tr>
				<th>#</th>
				<th>Herstellername</th>
				<th></th>
			</tr>';
		while ($row = $statement->fetch()) {
			$table .= '
				<tr>
					<td>'. $row['pro_id'] .'</td>
					<td>'. $row['producerName'] .'</td>
					<td>
						<form method="post" action="edit_producers.php">
							<input type="hidden" name="pro_id" value="'. $row['pro_id'] .'">
							<button class="empty" type="submit" name="edit_producer"><i class="fa fa-pencil" aria-hidden="true"></i></button>
						</form>
					</td>
				</tr>
			';
		}
		$table .= '</table>';
	} else {
		$table = "Keine Produzenten gefunden.";
	}
} else {
	$table = "Query nicht erfolgreich.";
}
?>

<div class="sizer spacer">
	<div>
		<div class="spacer2"><span class="subtitle2">Hersteller/Lieferant hinzufügen</span></div>
		<form class="form" action="<?php htmlspecialchars($_SERVER['PHP_SELF']) ?>" method="post">
			<input type="text" name="producerName" placeholder="Name des Lieferanten" required><br><br>
			<div id="description">
				<input type="hidden" name="description">
				<label>Herstellerinfos</label>
				<div id="summernote"></div>
			</div><br>
			<button id="add_producer" type="submit" name="addProducer" class="clean-btn green">Hinzufügen <i class="fa fa-plus" aria-hidden="true"></i></button>
		</form>
	</div><br><br>
	<div class="row">
		<div class="col-sm-6">
			<div class="spacer2"><span class="subtitle2">Hersteller/Lieferant bearbeiten</span></div><br>
			<?php
			echo $table;
			?>
		</div>
	</div>
</div>

<link href="https://cdnjs.cloudflare.com/ajax/libs/summernote/0.8.11/summernote-lite.css" rel="stylesheet">
<script src="https://cdnjs.cloudflare.com/ajax/libs/summernote/0.8.11/summernote-lite.js"></script>

<script>
	$('document').ready(function(){
      $('#summernote').summernote({
        placeholder: '',
        tabsize: 2,
        height: 200
      });
      $('#add_producer').on('click', function(){
      	var description = $('#summernote').summernote('code');
      	$('input[name="description"]').val(description);
      })
  	})
</script>
<?php 
include("templates/footer.inc.php")
?>