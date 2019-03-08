<?php
session_start();
require_once("inc/config.inc.php");
require_once("inc/functions.inc.php");
// ini_set('display_errors', 1);

if (is_checked_in()) {
	$user = check_user();
}

include("templates/header.inc.php");
include("templates/nav.inc.php");
?>

<div id="notification2" class="notificationClosed">
	<div id="closer"></div>
	<div class="center-vertical">
		<div class="center">
		<div id="loadScreen"></div>
		</div>
	</div>
</div>

<div class="sizer">
	<div class="row">
		<div class="col-md-6 spacer login">
			<span class="subtitle2">Kontaktformular</span>
			<form class="emailForm">
				<input type="text" name="name" placeholder="Dein Name" value="<?php if (isset($user['uid'])) echo htmlspecialchars($user['first_name']).' '. htmlspecialchars($user['last_name']) ?>" required>
				<input type="email" name="email" placeholder="Deine Email" required value="<?php echo $user['email'] ?>">
				<input type="text" name="subject" placeholder="Betreff" required>
				<textarea class="textarea" placeholder="Deine Nachricht" rows="5" name="text"></textarea><br>
				<div class="checkbox">
					<label>
						<input type="checkbox" name="agree" required>
						Wenn Sie die im Kontaktformular eingegebenen Daten durch Klick auf den nachfolgenden Button übersenden, erklären Sie sich damit einverstanden, dass wir Ihre Angaben für die Beantwortung Ihrer Anfrage bzw. Kontaktaufnahme verwenden. Eine Weitergabe an Dritte findet grundsätzlich nicht statt, es sei denn geltende Datenschutzvorschriften rechtfertigen eine Übertragung oder wir dazu gesetzlich verpflichtet sind. Sie können Ihre erteilte Einwilligung jederzeit mit Wirkung für die Zukunft widerrufen. Im Falle des Widerrufs werden Ihre Daten umgehend gelöscht. Ihre Daten werden ansonsten gelöscht, wenn wir Ihre Anfrage bearbeitet haben oder der Zweck der Speicherung entfallen ist. Sie können sich jederzeit über die zu Ihrer Person gespeicherten Daten informieren. Weitere Informationen zum Datenschutz finden Sie auch in der <a href="<?php echo getSiteUrl().'data.php'; ?>" target="_blank">Datenschutzerklärung</a> dieser Webseite.
					</label>
				</div>
				<br>
				<button type="submit" class="clean-btn green">Absenden <i class="fa fa-paper-plane" aria-hidden="true"></i></button>
			</form>
		</div>
		<div class="col-md-6 spacer">
			<span class="subtitle2">Kontaktdaten</span><br>
			<span>kontakt@namiko.org</span>
		</div>
	</div>
</div>

<script type="text/javascript">
	$('.emailForm').submit(function(e){
		var mail_data = $(this).serialize();
		$('body').addClass('noscroll');
		$('#loadScreen').addClass('loader');
		$('.notificationClosed').css('height', '100%');
		$(this).find(':submit').attr('disabled','disabled');

		$.ajax({
			url: 'contact_process.php',
			type: "POST",
			dataType: 'json',
			data: mail_data
			}).done(function(data){
					$('#closer').html('<a id="close2" href="javascript:void(0)" title="Close" class="closebtn" onclick="closeNotification(2)">&times;</a>');
					$('#loadScreen').removeClass('loader').html('<span class="green" style="font-size: 50px"><i class="fa fa-check" aria-hidden="true"></i></span><br><span>'+ data +'</span>');
					$('#subject').val('');
					$('#text').val('');
			})
		e.preventDefault();
	});
</script>
<?php 
include("templates/footer.inc.php")
?>