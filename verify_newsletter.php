<?php 
session_start();
require_once("inc/config.inc.php");
require_once("inc/functions.inc.php");

include("templates/header.inc.php");
?>

<div class="login-background">
</div>

<div class="center-vertical" style="height: 100vh">
	<div class="login form-container">
		<?php 
		if (isset($_GET['rid'])) {
			$rid = $_GET['rid'];
			$timestamp = urldecode($_GET['created_at']);

			$statement = $pdo->prepare("SELECT verified, created_at FROM newsletter_recipients WHERE rid = $rid");
			$result = $statement->execute();

			if ($result) {
				while ($row = $statement->fetch()) {
					$created_at = $row['created_at'];
					$verified = $row['verified'];

					if ($verified == 0) {

						if ($timestamp == $created_at) {

						    $ip = '';
						    if (getenv('HTTP_CLIENT_IP'))
						        $ip = getenv('HTTP_CLIENT_IP');
						    else if(getenv('HTTP_X_FORWARDED_FOR'))
						        $ip = getenv('HTTP_X_FORWARDED_FOR');
						    else if(getenv('HTTP_X_FORWARDED'))
						        $ip = getenv('HTTP_X_FORWARDED');
						    else if(getenv('HTTP_FORWARDED_FOR'))
						        $ip = getenv('HTTP_FORWARDED_FOR');
						    else if(getenv('HTTP_FORWARDED'))
						        $ip = getenv('HTTP_FORWARDED');
						    else if(getenv('REMOTE_ADDR'))
						        $ip = getenv('REMOTE_ADDR');
						    else
						        $ip = 'UNKNOWN';

							$statement = $pdo->prepare("INSERT INTO newsletter_proof (rid, ip) VALUES (:rid, :ip)");
							$result = $statement->execute(array('rid' => $rid, 'ip' => inet_pton($ip)));

							if ($result) {
								$statement = $pdo->prepare("UPDATE newsletter_recipients SET verified = 1");
								$result = $statement->execute();

								if ($result) {
									echo 'Der Newsletter wurde erfolgreich abonniert.';
								} else {
									echo 'Es gab einen Fehler bei der Aktivierung.';
								}
							} else {
								echo 'Es konnte kein Nachweis erstellt werden. Der Newsletter ist nicht abonniert.';
							}
						} else {
							echo 'Ungültiger Zeitstempel.';
						}
					} else {
						echo 'Der Newsletter ist bereits abonniert.';
					}
				}
			} else {
				echo 'Es wurden ungültige Parameter weitergegeben.';
			}
		}
		?>
		</form>
	</div>
</div>


 

<?php 
include("templates/footer.inc.php")
?>