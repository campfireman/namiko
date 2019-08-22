<?php
session_start();
require_once("inc/config.inc.php");
require_once("inc/functions.inc.php");

//Überprüfe, dass der User eingeloggt ist
//Der Aufruf von check_user() muss in alle internen Seiten eingebaut sein
$user = check_user();
check_consul();

include("templates/header.inc.php");
include("templates/nav.inc.php");
include("templates/admin-nav.inc.php");

if (isset($_POST['create-template'])) {

	$name = $_POST['name'];
	$template = $_POST['template'];
	$creator = $user['uid'];

	$statement = $pdo->prepare("INSERT INTO mail_templates (name, template, creator) VALUES (:name, :template, :creator)");
	$result = $statement->execute(array('name' => $name, 'template' => $template, 'creator' => $creator));

	if ($result) {
		$_SESSION['notification'] = true;
		$_SESSION['notificationmsg'] = 'Die Vorlage wurde erfolgreich gespeichert.';
		header('Location: '. $_SERVER['PHP_SELF']);
	} else {
		$_SESSION['notification'] = true;
		$_SESSION['notificationmsg'] = 'Die Vorlage konnte nicht gespeichert werden.';
		header('Location: '. $_SERVER['PHP_SELF']);
	}
}

if (isset($_POST['add-recipient'])) {
	$first_name = $_POST['first_name'];
	$last_name = $_POST['last_name'];
	$email = $_POST['email'];
	$created_by = $user['uid'];

	$statement = $pdo->prepare("INSERT INTO newsletter_recipients (first_name, last_name, email, verified, created_by) VALUES (:first_name, :last_name, :email, :verified, :created_by)");
	$result = $statement->execute(array('first_name' => $first_name, 'last_name' => $last_name, 'email' => $email, 'verified' => 1, 'created_by' => $created_by));

	print_r($statement->errorInfo());

	if ($result) {
		$_SESSION['notification'] = true;
		$_SESSION['notificationmsg'] = 'Der Empfänger wurde erfolgreich hinzugefügt.';
		header('Location: '. $_SERVER['PHP_SELF']);
	}

	if (!$result) {
		$_SESSION['notification'] = true;
		$_SESSION['notificationmsg'] = 'Es gab einen Fehler!';
		header('Location: '. $_SERVER['PHP_SELF']);
	}
}

if (isset($_POST['remove-recipient'])) {
	$rid = $_POST['rid'];

	$statement = $pdo->prepare("DELETE FROM newsletter_recipients WHERE rid = '$rid'");
	$result = $statement->execute();

	if ($result) {
		$_SESSION['notification'] = true;
		$_SESSION['notificationmsg'] = 'Der Empfänger wurde erfolgreich gelöscht.';
		header('Location: '. $_SERVER['PHP_SELF']);
	}

	if (!$result) {
		$_SESSION['notification'] = true;
		$_SESSION['notificationmsg'] = 'Es gab einen Fehler!';
		header('Location: '. $_SERVER['PHP_SELF']);
	}
}
?>


<div id="notification2" class="notificationClosed">
	<div id="closer"></div>
	<div class="center-vertical">
		<div class="center">
		<div id="loadScreen"></div>
		</div>
	</div>
</div>


<div class="sizer spacer">
	<div class="row">
		<div class="col-sm-5">
			<span class="subtitle2">Vorlage erstellen</span>
			<form class="form" action="<?php htmlspecialchars($_SERVER['PHP_SELF']) ?>" method="post">
				<input type="text" name="name" placeholder="Name der Vorlage">
				<input type="hidden" name="template"><div id="template-text" class="summernote"></div><br>
				<button class="clean-btn blue" id="create-template" name="create-template">Vorlage speichern <i class="fa fa-floppy-o" aria-hidden="true"></i></button>
			</form><br><br>

			<span class="subtitle2">Vorlage einfügen</span>
			<form class="form paste-template">
				<div>
					<select  id="template" name="temp_id">
						<option value="">- Vorlage auswählen -</option>
						<?php
						$statement = $pdo->prepare("SELECT * FROM mail_templates");
						$result = $statement->execute();

						while ($row = $statement->fetch()) {
							$name = $row['name'];
							$temp_id = $row['temp_id'];

							echo '<option value="'. $temp_id .'">'. $name .'</option>';
						}
						?>
					</select>
				</div><br>
				<button class="clean-btn blue" name="paste-template">Vorlage einfügen <i class="fa fa-clipboard" aria-hidden="true"></i></button>
			</form><br><br>
		</div>
		<div class="col-sm-7">
			<span class="subtitle2">Rundmail verschicken</span><br><br>
			<form class="form send-mail">
				<div><input id="subject" type="text" name="subject" placeholder="Betreff" required></div><br>
				<div><label for="text">Moin, (Vorname)!</label><br><input type="hidden" name="text"><div id="mailtext" class="summernote"></div><br></div>
				<label><input type="checkbox" name="members" value="members" style="min-width: 20px" checked>Mitglieder</label><br>
				<label><input type="checkbox" name="others" value="others" style="min-width: 20px">Newsletter Empfänger</label><br><br>
				<button type="submit" id="send-mail" name="send-mail" class="clean-btn green">E-Mails senden <i class="fa fa-paper-plane" aria-hidden="true"></i></button>
			</form>
		</div>
	</div>
	<div class="row">
		<div class="col-sm-5 spacer">
			<span class="subtitle2">Empfänger hinzufügen</span><br><br>
			<form class="form" method="post" type="<?php echo htmlspecialchars($_SERVER['PHP_SELF']) ?>">
				<input type="text" name="first_name" placeholder="Vorname" required><br>
				<input type="text" name="last_name" placeholder="Nachname (optional)">
				<input type="email" name="email" placeholder="E-Mail" required=""><br><br>
				<button type="submit" name="add-recipient" class="clean-btn green">Hinzufügen <i class="fa fa-plus" aria-hidden="true"></i></button>
			</form>
		</div>
		<div class="col-sm-7 spacer">
			<span class="subtitle2">Newsletter Empfänger</span><br><br>
			<thead>
			<table class="max">
				<tr>
					<th>#</th>
					<th>Name</th>
					<th>E-Mail</th>
					<th></th>
				</tr>
			</thead>
			<?php
			$statement = $pdo->prepare("SELECT rid, first_name, last_name, email FROM newsletter_recipients WHERE verified = 1 ORDER BY rid");
			$result = $statement->execute();
			$count = 1;

			while ($row = $statement->fetch()) {
				$name = $row['first_name']. ' ' .$row['last_name'];
				$email = $row['email'];

				echo '<tr>';
				echo 	'<form method="post" action="'. htmlspecialchars($_SERVER['PHP_SELF']) .'">';
				echo 		'<td class="emph">'.$count++.'</td>';
				echo 		'<td>';
				echo 			'<input type="hidden" value="'. $row['rid'] .'" name="rid">'. htmlspecialchars($name);
				echo 		'</td>';
				echo 		'<td>';
				echo 			htmlspecialchars($email);
				echo 		'</td>';
				echo 		'<td>';
				echo 			'<button type="submit" name="remove-recipient" class="empty remove-item"><i class="fa fa-trash" aria-hidden="true"></i></button>';
				echo 		'</td>';
				echo 	'</form>';
				echo '</tr>';
			}
			?>
			</table>
		</div>
	</div>
</div>

<?php 
include("templates/footer.inc.php");
?>
<link href="https://cdnjs.cloudflare.com/ajax/libs/summernote/0.8.11/summernote-lite.css" rel="stylesheet">
<script src="https://cdnjs.cloudflare.com/ajax/libs/summernote/0.8.11/summernote-lite.js"></script>

<script>
	$('document').ready(function(){
      $('.summernote').summernote({
        placeholder: '',
        tabsize: 2,
        height: 200
      });
      $('#send-mail').on('click', function(){
      	var text = $('#mailtext').summernote('code');
      	$('input[name="text"]').val(text);
      });
      $('#create-template').on('click', function(){
      	var template = $('#template-text').summernote('code');
      	$('input[name="template"]').val(template);
      });
  	})
</script>
<script type="text/javascript">
	function closeLoader (text, error) {
		$('#closer').html('<a id="close2" href="javascript:void(0)" title="Close" class="closebtn" onclick="closeNotification(2)">&times;</a>');
		if (error == 1) {
			var icon = '<span class="red" style="font-size: 50px"><i class="fa fa-times" aria-hidden="true"></i></span>';
		} else {
			var icon = '<span class="green" style="font-size: 50px"><i class="fa fa-check" aria-hidden="true"></i></span>';
		}
		$('#loadScreen').removeClass('loader').html(icon +'<br><span>'+ text +'</span>');
	}
	
	$('.paste-template').submit(function(e){
		var temp_id = $(this).serialize();
		$.ajax({
			url: 'email_process.php',
			dataType: 'json',
			type: 'POST',
			data: temp_id
		}).done(function(data) {
			$('#mailtext').summernote('code', data);
		})
		
		e.preventDefault();
	});

	$('.send-mail').submit(function(e){
		var mail_data = $(this).serialize();
		$('body').addClass('noscroll');
		$('#loadScreen').addClass('loader');
		$('.notificationClosed').css('height', '100%');
		$(this).find(':submit').attr('disabled','disabled');

		$.ajax({
			url: 'email_process.php',
			type: "POST",
			dataType: 'json',
			data: mail_data
			}).done(function(data){
				closeLoader(data.text, data.error);
				$('#subject').val('');
				$('#text').val('');
			})
		e.preventDefault();
	});
</script>