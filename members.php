<?php
session_start();
require_once("inc/config.inc.php");
require_once("inc/functions.inc.php");
require_once("inc/SepaXML.inc.php");

require('util/phpmailer/Exception.php');
require('util/phpmailer/PHPMailer.php');
require('util/phpmailer/SMTP.php');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

//Überprüfe, dass der User eingeloggt ist
//Der Aufruf von check_user() muss in alle internen Seiten eingebaut sein
$user = check_user();
check_consul();

include("templates/header.inc.php");
include("templates/nav.inc.php");
include("templates/admin-nav.inc.php");

if (isset($_POST['notification'])) {
	$title = trim($_POST['title']);
	$text = trim($_POST['text']);
	$error = false;


	if (empty($title) || empty($text)) {
		$_SESSION['notification'] = true;
		$_SESSION['notificationmsg'] = 'Bitte alle Felder ausfüllen.';
		header("Location: " . $_SERVER['PHP_SELF']);
	}

	if (!$error) {
		$creator = $user['uid'];
		
		$statement = $pdo->prepare("UPDATE notification SET title = '$title', text = '$text', created_by = '$creator' WHERE id = 1");
		$result = $statement->execute();
		
		$statement = $pdo->prepare("UPDATE users SET notification = 1");
		$result = $statement->execute();
		
		if ($result) {
			$_SESSION['notification'] = true;
			$_SESSION['notificationmsg'] = 'Benachrichtigung erfolgreich hinzugefügt.';
			header("Location: " . $_SERVER['PHP_SELF']);
		}

		if (!$result) {
			$_SESSION['notification'] = true;
			$_SESSION['notificationmsg'] = 'Es gab einen Fehler';
			header("Location: " . $_SERVER['PHP_SELF']);
		}
	}
	
}

if (isset($_POST['save'])) {
	$rights = $_POST['rights'];
	$selector = $_POST['selector'];

	$statement = $pdo->prepare("UPDATE users SET rights = '$rights' WHERE uid = '$selector'");
	$result = $statement->execute();

	if ($result) {
		$_SESSION['notification'] = true;
		$_SESSION['notificationmsg'] = 'Rechte erfolgreich geändert';
		header("Location: " . $_SERVER['PHP_SELF']);
	}

	if (!$result) {
		$_SESSION['notification'] = true;
		$_SESSION['notificationmsg'] = 'Es gab einen Fehler';
		header("Location: " . $_SERVER['PHP_SELF']);
	}
}

if (isset($_POST['delete'])) {
	$uid = $_POST['uid'];
	$statement = $pdo->prepare("DELETE FROM security_tokes WHERE security_tokens.user_id = '$uid'");
	$result = $statement->execute();
	$statement = $pdo->prepare("DELETE FROM mandates WHERE mandates.uid = '$uid'");
	$result = $statement->execute();
	$statement = $pdo->prepare("DELETE FROM users WHERE users.uid = '$uid'");
	$result = $statement->execute();
	
	
	
	print_r($statement->errorInfo());

	if ($result) {
		$_SESSION['notification'] = true;
		$_SESSION['notificationmsg'] = 'User erfolgreich gelöscht.';
		header("Location: " . $_SERVER['PHP_SELF']);
	}

	if (!$result) {
		$_SESSION['notification'] = true;
		$_SESSION['notificationmsg'] = 'Es gab einen Fehler';
		header("Location: " . $_SERVER['PHP_SELF']);
	}
}

if(isset($_POST['loanPay'])) {
	$creator = $user['uid'];
	$sepa = new SepaXML;
	$sepa->collectionDt = $_POST['date'];

	$statement = $pdo->prepare("INSERT INTO sepaDocs (creator) VALUES (:creator)");
	$result = $statement->execute(array('creator' => $creator));

	if ($result) {
		$statement = $pdo->prepare("SELECT sid FROM sepaDocs WHERE created_at = (SELECT MAX(created_at) FROM sepaDocs)");
		$result = $statement->execute();
		$sepaDoc = $statement->fetch();

		$sid = $sepaDoc['sid'];
		$date = date('Ymd');
		$time = date('His');
		$sepa->msgID = $myBIC . 'SID' . $sid .'-'. $date . $time;
		$sepa->creDtTm = date('Y-m-d') . 'T' . date('H:i:s');
		$sepa->InitgPty = $user['first_name']. ' ' .$user['last_name'];
		$sepa->pymntID = 'SID'. $sid .'D'. $date .'T'. $time;
		$sepa->filename = dirname(__FILE__).'/sepa/'. $sepa->pymntID .'.xml';
		$sepa->myEntity = $myEntity;
		$sepa->myIBAN = $myIBAN;
		$sepa->myBIC = $myBIC;
		$sepa->creditorId = $creditorId;
		$sepa->SeqTp = 'FRST';

		$sepa->createHdr();


		$statement = $pdo->prepare("SELECT users.*, mandates.* FROM users LEFT JOIN mandates ON users.uid = mandates.uid WHERE users.rights = 1 AND NOT EXISTS (SELECT * FROM loans WHERE loans.uid = users.uid)");
		$result = $statement->execute();

		if ($result) {
			$numberTx = 0;
			$totalTx = 0;

			while ($row = $statement->fetch()) {
				$numberTx++;
				$uid = $row['uid'];
				$first_name = $row['first_name'];
				$last_name = $row['last_name'];
				$email = $row['email'];
				$sepa->account_holder = $row['account_holder'];
				$sepa->IBAN = $row['IBAN'];
				$sepa->BIC = $row['BIC'];
				$sepa->mid = $row['mid'];
				$sepa->signed = substr($row['created_at'], 0, 10);
				$sepa->RmtInf = 'Mitgliedsdarlehn';
				$loan = $row['loan'];
				$sepa->InstdAmt = $loan;
				$totalTx += $loan;
				
				$statement2 = $pdo->prepare("INSERT INTO payments (uid, reference, amount) VALUES (:uid, :reference, :amount)");
				$result2 = $statement2->execute(array('uid' => $uid, 'reference' => $sid, 'amount' => $loan));

				$statement3 = $pdo->prepare("SELECT pay_id FROM payments WHERE uid = '$uid' AND reference = '$sid'");
				$result3 = $statement3->execute();
				$pay_id = $statement3->fetch();

				$statement4 = $pdo->prepare("INSERT INTO loans (uid, pay_id) VALUES (:uid, :pay_id)");
				$result4 = $statement4->execute(array('uid' => $uid, 'pay_id' => $pay_id['pay_id']));

				$sepa->txID = 'ID'. $pay_id['pay_id'] .'D'. $date .'T'. $time;

				$mail = new PHPMailer(true);
				try {
				    //Server settings
				    $mail->SMTPDebug = 0;
				    $mail->isSMTP();
				    $mail->Host = $smtp_host;
				    $mail->SMTPAuth = true;
				    $mail->Username = $smtp_username;
				    $mail->Password = $smtp_password;
				    $mail->SMTPSecure = 'tls';
				    $mail->Port = 587;
				    $mail->CharSet = 'UTF-8';
					$mail->Encoding = 'base64';

				    //Recipients
				    $mail->setFrom('noreply@namiko.org', 'namiko e.V. Hannover');
				    $mail->addAddress($email, $first_name.' '.$last_name);
				    $mail->addReplyTo('noreply@namiko.org', 'NoReply');

				    //Content
				    $mail->isHTML(true);
				    $mail->Subject = 'Einzug des Mitgliedsdarlehn';
				    $mail->Body    = '<h1>Moin, '. htmlspecialchars($first_name) .'!</h1>
				    	<p>Den Mitgliedsdarlehn von EUR '. sprintf("%01.2f", ($loan)) .' ziehen wir mit der SEPA-Lastschrift zum Mandat mit der Referenznummer '. $mid .' zu der Gläubiger-Identifikationsnummer '. $creditorId .' von Deinem Konto IBAN '. $IBAN .' bei BIC '. $BIC .' zum Fälligkeitstag '. $collectionDt .' ein.
				    	<br><br><span style="font-style: italic">Dein namiko Hannover e.V. Team</span><br><br><br><br><br><br>
				    					Bei Rückfragen einfach an kontakt@namiko.org schreiben.</p>';
				    $mail->AltBody = 'Moin, '. htmlspecialchars($first_name) .'!
				    	Den Mitgliedsdarlehn von EUR '. sprintf("%01.2f", ($loan)) .' ziehen wir mit der SEPA-Lastschrift zum Mandat mit der Referenznummer '. $mid .' zu der Gläubiger-Identifikationsnummer '. $creditorId .' von Deinem Konto IBAN '. $IBAN .' bei BIC '. $BIC .' zum Fälligkeitstag '. $collectionDt .' ein.
				    	Dein namiko Hannover e.V. Team
				    					Bei Rückfragen einfach an kontakt@namiko.org schreiben.';

				    $mail->send();
				    $result3 = true;
				} catch (Exception $e) {
				    $result3 = false;
				}

				$sepa->appendDbtr();

			}

			$sepa->numberTx = $numberTx;
			$sepa->totalTx = $totalTx;

			$statement = $pdo->prepare("UPDATE sepaDocs SET PmtInfId = '$pymntID' WHERE sid = '$sid'");
			$result = $statement->execute();

			if ($sepa->createdoc()) {
				$statement = $pdo->prepare("UPDATE sepaDocs SET PmtInfId = '$sepa->pymntID' WHERE sid = '$sid'");
				$result = $statement->execute();


				header('Content-type: "text/xml"; charset="utf8";');
				header('Content-Transfer-Encoding: Binary');
				header('Content-disposition: attachment; filename="'. $sepa->pymntID .'.xml"');
				while (ob_get_level()) {
				    ob_end_clean();
				}
				readfile($sepa->filename);
				exit();
			} else {
				$_SESSION['notification'] = true;
				$_SESSION['notificationmsg'] = 'Es konnte kein gültiges XML Dokument erstellt werden.'. print_r($sepa->errorDetails);
				header("Location: " . $_SERVER['PHP_SELF']);
			}

		} else {
			$_SESSION['notification'] = true;
			$_SESSION['notificationmsg'] = 'Es konnte kein XML Dokument erstellt werden.';
			header("Location: " . $_SERVER['PHP_SELF']);
		}

	} else {
		$_SESSION['notification'] = true;
		$_SESSION['notificationmsg'] = 'Es konnte kein Datenbankeintrag für das Dokument erstellt werden.';
		header("Location: " . $_SERVER['PHP_SELF']);
	}

}

if (isset($_POST['loanRecieved'])) {
	$uid = $_POST['uid'];

	$statement = $pdo->prepare("UPDATE users SET rights = 2 WHERE uid = '$uid'");
	$result = $statement->execute();

	if ($result) {
		$statement = $pdo->prepare("UPDATE loans SET recieved = 1 WHERE uid = '$uid'");
		$result = $statement->execute();

		if ($result) {
			$statement = $pdo->prepare("SELECT first_name, last_name, email FROM users WHERE uid = '$uid'");
			$result = $statement->execute();

			while ($row = $statement->fetch()) {
				$first_name = $row['first_name'];
				$last_name = $row['last_name'];
				$email = $row['email'];
			}
			
			$mail = new PHPMailer(true);
			try {
			    //Server settings
			    $mail->SMTPDebug = 0;
			    $mail->isSMTP();
			    $mail->Host = $smtp_host;
			    $mail->SMTPAuth = true;
			    $mail->Username = $smtp_username;
			    $mail->Password = $smtp_password;
			    $mail->SMTPSecure = 'tls';
			    $mail->Port = 587;
			    $mail->CharSet = 'UTF-8';
				$mail->Encoding = 'base64';

			    //Recipients
			    $mail->setFrom('noreply@namiko.org', 'namiko e.V. Hannover');
			    $mail->addAddress($email, $first_name.' '.$last_name);
			    $mail->addReplyTo('noreply@namiko.org', 'NoReply');

			    //Content
			    $mail->isHTML(true);
			    $mail->Subject = 'Freischaltung Deines Accounts';
			    $mail->Body    = '<h1>Moin, '. htmlspecialchars($first_name) .'!</h1>
			    	<p>Dein Mitgliedsdarlehn ist eingetroffen. Du bist nun für Bestellungen freigeschaltet.</p>
			    	<br><br><span style="font-style: italic">Dein namiko Hannover e.V. Team</span><br><br><br><br><br><br>
			    					Bei Rückfragen einfach an kontakt@namiko.org schreiben.</p>';
			    $mail->AltBody = 'Moin, '. htmlspecialchars($first_name) .'!
			    	Dein Mitgliedsdarlehn ist eingetroffen. Du bist nun für Bestellungen freigeschaltet.
			    	Dein namiko Hannover e.V. Team
			    					Bei Rückfragen einfach an kontakt@namiko.org schreiben.';

			    $mail->send();
			    $result = true;
			} catch (Exception $e) {
			    $result = false;
			}

			if ($result) {
				$_SESSION['notification'] = true;
				$_SESSION['notificationmsg'] = 'Mitglied freigeschaltet.';
				header("Location: " . $_SERVER['PHP_SELF']);
			} else {
				$_SESSION['notification'] = true;
				$_SESSION['notificationmsg'] = 'Die Infomail konnte nicht verschickt werden.';
				header("Location: " . $_SERVER['PHP_SELF']);
			}
		} else {
			$_SESSION['notification'] = true;
			$_SESSION['notificationmsg'] = 'Der Darlehn konnte nicht als bezahlt markiert werden.';
			header("Location: " . $_SERVER['PHP_SELF']);
		}
	} else {
		$_SESSION['notification'] = true;
		$_SESSION['notificationmsg'] = 'Die Rechte konnten nicht aktualisiert werden.';
		header("Location: " . $_SERVER['PHP_SELF']);
	}
}
?>

<div class="sizer spacer">
	<div class="row">
		<div class="col-sm-6">
		<span class="subtitle2">Benachrichtigung</span><br><br>
		<span><i class="fa fa-info-circle" aria-hidden="true"></i> Hier kannst Du eine Benachrichtigung erstellen, die bei jedem Mitglied beim Login angezeigt wird. Es kann immer nur eine Benachrichtigung angezeigt werden, alte und ungelesene Benachrichtigungen werden überschrieben.</span>
		</div>
		
		<div class="col-sm-6">
			<form class="form" action="<?php htmlspecialchars($_SERVER['PHP_SELF']) ?>" method="post">
				<div><input type="text" name="title" placeholder="Titel" required></div>
				<div><input type="text" name="text" placeholder="Nachrichtstext" required></div><br>
				<div><button class="clean-btn green" type="submit">Veröffentlichen <i class="fa fa-paper-plane" aria-hidden="true" name="notification"></i></button></div>
			</form>
		</div>
	</div>

	<div class="spacer full">
	<span class="subtitle2">Mitglieder verwalten</span><br><br>
			<table class="table panel panel-default" style="min-width: 820px">
			<tr>
				<th>#</th>
				<th>Vorname</th>
				<th>Nachname</th>
				<th>E-Mail</th>
				<th>Rechte</th>
				<th>Ort</th>
				<th>Anschrift</th>
				<th></th>
			</tr>
			<?php 
			$count = 1;
			$statement = $pdo->prepare("SELECT * FROM users ORDER BY uid");
			$result = $statement->execute();
			
			
			while($row = $statement->fetch()) {
				echo "<tr>";
				echo "<td>";
					echo $count++;
					if ($row['rights'] == 1) echo ' <span class="inline emph red"><i class="fa fa-exclamation-triangle" aria-hidden="true"></i></span>';
				echo "</td>";
				echo "<td>". htmlspecialchars($row['first_name']) ."</td>";
				echo "<td>". htmlspecialchars($row['last_name']) ."</td>";
				echo '<td><a href="mailto:'. htmlspecialchars($row['email']) .'">'. htmlspecialchars($row['email']) .'</a></td>';
				echo '<td>';
					    if ($row['rights'] == 4) {
					    	echo 'Consul</td>';
					    }

					    if ($row['rights'] == 0) {
					    	echo 'nicht verifiziert</td>';
					    } 

					    if ($row['rights'] == 1 || $row['rights'] == 2 || $row['rights'] == 3) {

					    	echo '<div>
					    		  <form style="position: relative; left: -5px;" action="'. htmlspecialchars($_SERVER['PHP_SELF']) .'" method="post">
					    		  <input type="hidden" name="selector" value="'. $row['uid'] .'">
					    		  <select class="empty" type="number" min="1" maxlength="10" name="rights">';

						    if ($row['rights'] == 1) {
						    	echo '<option value="1">Novize</option>';
						    	echo '<option value="2">Mitglied</option>';
						    	echo '<option value="3">Administrator</option>';
						    	echo '<option value="4">Consul</option>';
					    	}

					    	if ($row['rights'] == 2) {
						    	echo '<option value="2">Mitglied</option>';
						    	echo '<option value="1">Novize</option>';
						    	echo '<option value="3">Administrator</option>';
						    	echo '<option value="4">Consul</option>';
					    	}

					    	if ($row['rights'] == 3) {
						    	echo '<option value="3">Administrator</option>';
						    	echo '<option value="2">Mitglied</option>';
						    	echo '<option value="1">Novize</option>';
						    	echo '<option value="4">Consul</option>';
					    	}

					    	echo '</select>
					    		  <button class="empty save" type="submit" name="save"><i class="fa fa-floppy-o" aria-hidden="true"></i></button>
					    		  </form></div></td>';
						}
					    
				echo '</select>';
				echo '<td>'. $row['postal_code'] .' '. htmlspecialchars($row['region']) . '</td>';
				echo '<td>'. htmlspecialchars($row['street']) .' '. $row['street_number'] .'</td>';
				echo '<td>';
				if ($row['rights'] < 4) {
				echo 	'<form style="position: relative; left: -5px;" action="'. htmlspecialchars($_SERVER['PHP_SELF']) .'" method="post">';
				echo 		'<input type="hidden" name="uid" value="'. $row['uid'] .'">';
				echo 		'<button class="empty red" type="submit" name="delete"><i class="fa fa-trash" aria-hidden="true"></i></button>';
				echo 	'</form>';
				}
				echo '</td>';
				echo "</tr>";
			}
			?>
			</table>

	</div>
	<div class="spacer">
		<span class="subtitle2">Beitritte verwalten</span><br><br>
		<div class="row">
			<div class="col-sm-6">
				<span class="subtitle3">ausstehende Bestätigungen</span><br><br>
				<?php
				$statement = $pdo->prepare("SELECT users.*, loans.* FROM users LEFT JOIN loans ON users.uid = loans.uid WHERE users.rights = 1 AND EXISTS (SELECT * FROM loans WHERE loans.uid = users.uid AND loans.recieved = 0)");
				$result = $statement->execute();

				if ($statement->rowCount() > 0) {
					echo "<table>";
					while ($row = $statement->fetch()) {
						echo "<tr>";
						echo 	"<th>". $row['first_name'] ." ". $row['last_name'] ."</th>";
						echo 	"<td>";
						echo 		"<form method='post' action=". htmlspecialchars($_SERVER['PHP_SELF']) .">";
						echo 		"<input type='hidden' name='uid' value='". $row['uid'] ."'>";
						echo 		"<button type='submit' name='loanRecieved' class='empty'>Darlehn erhalten <i class='fa fa-question-circle-o' aria-hidden='true'></i></button>";
						echo 		"</form>";
						echo 	"</td>";
						echo "</tr>";
					}
					echo "</table>";
				} else {
					echo "<span>Keine gefunden.</span>";
				}
				?>
			</div>
			<div class="col-sm-6">
				<span class="subtitle3">Darlehn einziehen</span><br><br>
				<span class="green emph">
					<?php
						$statement = $pdo->prepare("SELECT * FROM users WHERE rights = 1 AND NOT EXISTS (SELECT * FROM loans WHERE loans.uid = users.uid)");
						$result = $statement->execute();
						$count = $statement->rowCount();

						$total = 0;

						if ($count > 0) {
							if ($count == 1) {
								$singular = 'r';
							}
							while ($row = $statement->fetch()) {
								$total += $row['loan'];
							}

							echo $count . " offene". $singular ." Darlehn über ". $currency. sprintf("%01.2f", $total);
						} else {
							echo "Keine offenen Darlehen.";
						}
					?>
				</span>
				</p><br><br>
				<span><i class="fa fa-info-circle" aria-hidden="true"></i> Bei Erstellung des Dokuments wird automatisch an alle Mitglieder eine Email verschickt, die über den Einzug des Geldes informiert. Abhängig von der Internetverbindung kann dies etwas dauern, also den Tab offen lassen, nicht neu laden, bis der Download des Dokuments erscheint.<br>Das Fälligkeitsdatum muss in folgendem Format eingegeben werden: JJJJ-MM-TT. Es muss wie folgt berechnet werden: Aktueller Tag + 2 Bankarbeitstage (TARGET2)!</span><br><br>

				<form action="<?php htmlspecialchars($_SERVER['PHP_SELF']) ?>" method="post" class="form">
					<input type="text" name="date" placeholder="fälligskeitsdatum" required>
					<button class="clean-btn green" name="loanPay" type="submit">XML erstellen <i class="fa fa-file-text-o" aria-hidden="true"></i></button>
				</form>
			</div>
		</div>
	</div>
</div>

<?php 
include("templates/footer.inc.php")
?>