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


if (isset($_POST['toggleTimeframe'])) {
	$_SESSION['timeframe'] = $_POST['timeframe'];

	if ($_SESSION['timeframe'] == 0) {
		$_SESSION['timeToggle'] = '';
	} else {
		$_SESSION['timeToggle'] = " AND (orders.created_at < '". $_SESSION['timeframe'] ."')";
	}
}
/*
$curr = date('Y-m-d H:i:s');
$statement = $pdo->prepare("SELECT start FROM events WHERE type = 1 AND start > '$curr' ORDER BY start ASC");
$result = $statement->execute();
$timeframeOut = '';

while ($row = $statement->fetch()) {
	$nextSession = date_create_from_format('Y-m-d H:i:s', $row['start']);
	$calc = clone $nextSession;

	$last = date_sub($calc, date_interval_create_from_date_string($lastPossibleOrder));

	$timeframeOut .= '<option value="'. $last->format('Y-m-d H:i:s') .'"';

	if ($last->format('Y-m-d H:i:s') == $_SESSION['timeframe']) {
		$timeframeOut .= 'selected="selected"';
	}

	$timeframeOut .= '>'. $last->format('d.m.Y H:i:s') .' ('. $nextSession->format('d.m.Y H:i:s') .')</option>';
}*/


if(isset($_POST['memberPay'])) {
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

		$sepa->createHdr();

		$statement = $pdo->prepare("
			SELECT users.*, mandates.mid, mandates.created_at AS cd FROM users 
			LEFT JOIN mandates ON users.uid = mandates.uid 
			WHERE users.rights > 1 
			AND users.rights < 5 
			AND (NOT EXISTS (SELECT created_at FROM contributions WHERE users.uid = contributions.uid LIMIT 1) 
			OR (DATEDIFF(NOW(), 
			(SELECT MAX(created_at) FROM contributions WHERE uid = users.uid LIMIT 1) ) >= 87))");
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
				$sepa->signed = substr($row['cd'], 0, 10);
				$sepa->InstdAmt = (3 * $row['contribution']);
				$sepa->RmtInf = 'Mitgliedsbeitrag für 3 Monate';
				$totalTx += $sepa->InstdAmt;
				
				$statement2 = $pdo->prepare("INSERT INTO payments (uid, reference, amount) VALUES (:uid, :reference, :amount)");
				$result2 = $statement2->execute(array('uid' => $uid, 'reference' => $sid, 'amount' => $sepa->InstdAmt));

				$statement3 = $pdo->prepare("SELECT pay_id FROM payments WHERE uid = '$uid' AND reference = '$sid'");
				$result3 = $statement3->execute();
				$pay_id = $statement3->fetch();

				$sepa->txID = 'ID'. $pay_id['pay_id'] .'D'. $date .'T'. $time;

				
				$statement2 = $pdo->prepare("INSERT INTO contributions (uid, pay_id) VALUES (:uid, :pay_id)");
				$result2 = $statement2->execute(array('uid' => $uid, 'pay_id' => $pay_id['pay_id']));

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
				    $mail->Subject = 'Einzug des Mitgliedsbeitrages';
				    $mail->Body    = '<h1>Moin, '. htmlspecialchars($first_name) .'!</h1>
				    	<p>Den Mitgliedsbeitrag für dieses Qurtal von EUR '. sprintf("%01.2f", ($sepa->InstdAmt)) .' ziehen wir mit der SEPA-Lastschrift zum Mandat mit der Referenznummer '. $mid .' zu der Gläubiger-Identifikationsnummer '. $creditorId .' von Deinem Konto IBAN '. $sepa->IBAN .' bei BIC '. $sepa->BIC .' zum Fälligkeitstag '. $sepa->collectionDt .' ein.
				    	<br><br><span style="font-style: italic">Dein namiko Hannover e.V. Team</span><br><br><br><br><br><br>
				    					Bei Rückfragen einfach an kontakt@namiko.org schreiben.</p>';
				    $mail->AltBody = 'Moin, '. htmlspecialchars($first_name) .'!
				    	Den Mitgliedsbeitrag für dieses Qurtal von EUR '. sprintf("%01.2f", ($sepa->InstdAmt)) .' ziehen wir mit der SEPA-Lastschrift zum Mandat mit der Referenznummer '. $mid .' zu der Gläubiger-Identifikationsnummer '. $creditorId .' von Deinem Konto IBAN '. $sepa->IBAN .' bei BIC '. $sepa->BIC .' zum Fälligkeitstag '. $sepa->collectionDt .' ein.
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

			$statement = $pdo->prepare("UPDATE sepaDocs SET PmtInfId = '$sepa->pymntID' WHERE sid = '$sid'");
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
				notify('Es konnte kein gültiges XML Dokument erstellt werden.'. $sepa->errorDetails);
			}

		} else {
			notify('Es konnte kein XML Dokument erstellt werden.');
		}

	} else {
		notify('Es konnte kein Datenbankeintrag für das Dokument erstellt werden.');
	}

}

////////////////////////////////////// 

if(isset($_POST['orderPay'])) {
	$creator = $user['uid'];
	$statement = $pdo->prepare("
		SELECT orders.oid, users.uid, users.first_name, users.last_name, users.email, users.account_holder, users.IBAN, users.BIC
		FROM orders LEFT JOIN users ON orders.uid = users.uid 
		WHERE (orders.paid = 0)". $_SESSION['timeToggle'] ."");
	$result = $statement->execute();
	$payments = [];

	if ($result && $statement->rowCount() > 0) {
		while($row = $statement->fetch()) {
			$payments[$row['uid']][] = $row['oid'];
		}
	} else {
		notify("Keine offenen Zahlungen gefunden.");
	}

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

		// insert header data into xml file
		$sepa->createHdr();

		$numberTx = 0;
		$totalTx = 0;

		foreach ($payments as $uid => $order) {
			$txSum = 0;
			$RmtInfString = "Bestellung Nr. ";
			$count = 1;

			foreach ($order as $oid) {
				if ($count == 1) {
					$RmtInfString .= $oid;
				} else {
					$RmtInfString .= " + ". $oid;
				}
				$count++;
				$statement = $pdo->prepare("SELECT total FROM order_items WHERE oid = '$oid'");
				$result = $statement->execute();

				if ($result) {
					while ($row = $statement->fetch()) {
						$txSum += $row['total'];
					}

					$statement2 = $pdo->prepare("UPDATE orders SET paid = 1 WHERE (oid='$oid')". $_SESSION['timeToggle'] ."");
					$result2 = $statement2->execute();
				}

			}

			if (strlen($RmtInfString) > 140) {
				$RmtInfString = substr($RmtInfString, 0, 139);
			}

			$statement = $pdo->prepare("
				SELECT users.uid, users.first_name, users.last_name, users.email, users.account_holder, users.IBAN, users.BIC, mandates.mid, mandates.created_at FROM users
				LEFT JOIN mandates ON users.uid = mandates.uid 
				WHERE users.uid = '$uid'");
			$result = $statement->execute();
			$row = $statement->fetch();
			print_r($row);

			$numberTx++;
			$first_name = $row['first_name'];
			$last_name = $row['last_name'];
			$email = $row['email'];
			$sepa->account_holder = $row['account_holder'];
			$sepa->IBAN = $row['IBAN'];
			$sepa->BIC = $row['BIC'];
			$sepa->mid = $row['mid'];
			$sepa->signed = substr($row['created_at'], 0, 10);
			$sepa->RmtInf = $RmtInfString;

			$totalTx += $txSum;
			$sepa->InstdAmt = $txSum;
			
			$statement3 = $pdo->prepare("INSERT INTO payments (uid, reference, amount) VALUES (:uid, :reference, :amount)");
			$result3 = $statement3->execute(array('uid' => $uid, 'reference' => $sid, 'amount' => $txSum));

			$statement3 = $pdo->prepare("SELECT pay_id FROM payments WHERE uid = '$uid' AND reference = '$sid'");
			$result3 = $statement3->execute();
			$pay_id = $statement3->fetch();

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
			    $mail->Subject = 'Bestellung Nr. '. $oid .'';
			    $mail->Body    = '<h1>Moin, '. htmlspecialchars($first_name) .'!</h1>
			    	<p>Wir ziehen die Bezahlung für die '. $RmtInfString .' ein. <br>
			    	Die Summe von EUR '. sprintf("%01.2f", ($txSum)) .' ziehen wir mit der SEPA-Lastschrift zum Mandat mit der Referenznummer '. $mid .' zu der Gläubiger-Identifikationsnummer '. $creditorId .' von Deinem Konto IBAN '. $sepa->IBAN .' bei BIC '. $sepa->BIC .' zum Fälligkeitstag '. $sepa->collectionDt .' ein.
			    	<br><br><span style="font-style: italic">Dein namiko Hannover e.V. Team</span><br><br><br><br><br><br>
			    					Bei Rückfragen einfach an kontakt@namiko.org schreiben.</p>';
			    $mail->AltBody = 'Moin, '. htmlspecialchars($first_name) .'!
			    	Wir ziehen die Bezahlung für die '. $RmtInfString .' ein. 
			    	Die Summe von EUR '. sprintf("%01.2f", ($txSum)) .' ziehen wir mit der SEPA-Lastschrift zum Mandat mit der Referenznummer '. $mid .' zu der Gläubiger-Identifikationsnummer '. $creditorId .' von Deinem Konto IBAN '. $sepa->IBAN .' bei BIC '. $sepa->BIC .' zum Fälligkeitstag '. $sepa->collectionDt .' ein.
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
			notify('Es konnte kein gültiges XML Dokument erstellt werden.'. json_encode($sepa->errorDetails));
		}
	} else {
		notify('Es konnte kein Datenbankeintrag für das Dokument erstellt werden.');
	}

}

?>

<div class="sizer">
	<div class="row">
		<div class="col-md-6 spacer">
			<span class="subtitle2">Mitgliedsbeiträge einziehen</span><br>
			<p>aktuelle Höhe der Quartalsbezüge:
				<span class="green emph">
					<?php
						$statement = $pdo->prepare("SELECT users.* FROM users WHERE users.rights > 1 AND users.rights < 5 AND (NOT EXISTS (SELECT * FROM contributions WHERE users.uid = contributions.uid) OR (DATEDIFF(NOW(), (SELECT MAX(created_at) FROM contributions WHERE uid = users.uid LIMIT 1) ) >= 87))");
						$result = $statement->execute();

						$total = 0;

						while ($row = $statement->fetch()) {
							$contribution = $row['contribution'];
							$quartalsbeitrag = ($contribution *3);
							$total += $quartalsbeitrag;
						}

						echo $currency.sprintf("%01.2f", ($total));
					?>
				</span>
			</p><br><br>
			<span><i class="fa fa-info-circle" aria-hidden="true"></i> Bei Erstellung des Dokuments wird automatisch an alle Mitglieder eine Email verschickt, die über den Einzug des Geldes informiert. Abhängig von der Internetverbindung kann dies etwas dauern, also den Tab offen lassen, nicht neu laden, bis der Download des Dokuments erscheint.<br>Es muss wie folgt berechnet werden: Aktueller Tag + 2 Bankarbeitstage (TARGET2)!</span><br><br>

			<form action="<?php htmlspecialchars($_SERVER['PHP_SELF']) ?>" method="post" class="form">
				<input type="date" name="date" placeholder="fälligskeitsdatum" required>
				<button class="clean-btn green" name="memberPay" type="submit">XML erstellen <i class="fa fa-file-text-o" aria-hidden="true"></i></button>
			</form>
		</div>
		<div class="col-md-6 spacer">
			<span class="subtitle2">Offene Lastschriften einziehen</span><br>
			<p>aktuelle Höhe der Lastschriften:
				<span class="green emph">
					<?php
						$total = 0;
						$statement = $pdo->prepare("SELECT orders.oid, orders.paid, order_items.total FROM orders LEFT JOIN order_items ON order_items.oid = orders.oid WHERE (orders.paid = 0)". $_SESSION['timeToggle'] ."");
						$result = $statement->execute();

						while ($row = $statement->fetch()) {
							$item_sum = $row['total'];
							$total += $item_sum;
						}

						echo $currency.sprintf("%01.2f", ($total));
					?>
				</span><br>
			</p><br><br>
			<span><i class="fa fa-info-circle" aria-hidden="true"></i> Bei Erstellung des Dokuments wird automatisch an alle Mitglieder eine Email verschickt, die über den Einzug des Geldes informiert. Abhängig von der Internetverbindung kann dies etwas dauern, also den Tab offen lassen, nicht neu laden, bis der Download des Dokuments erscheint.<br>Es muss wie folgt berechnet werden: Aktueller Tag + 2 Bankarbeitstage (TARGET2)!</span><br><br>

			<form class="form" method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']) ?>">
				<!--<select name="timeframe">
					<option value="0">Alle</option>
					<optgroup label="Zeitpunkte">
					
					</optgroup>
				</select>-->
				<input type="date" name="timeframe" value="<?php echo $_SESSION['timeframe'] ?>">
				<button type="submit" class="clean-btn blue" name="toggleTimeframe">Aktualisieren <i class="fa fa-refresh" aria-hidden="true"></i></button>
			</form><br>

			<form action="<?php htmlspecialchars($_SERVER['PHP_SELF']) ?>" method="post" class="form">
				<input type="date" name="date" placeholder="fälligskeitsdatum" required>
				<button class="clean-btn green" name="orderPay" type="submit">XML erstellen <i class="fa fa-file-text-o" aria-hidden="true"></i></button>
			</form>
		</div>
	</div>
	<div class="row">
		<div class="col-md-6 spacer">
			<span class="subtitle2">Mitgliedsbeiträge</span><br>
			<p>Summe der eingezogenen Mitgliedsbeiträge:
				<span class="green emph">
				<?php
				$statement = $pdo->prepare("SELECT users.contribution FROM contributions LEFT JOIN users ON contributions.uid = users.uid");
				$result = $statement->execute();

				while ($row = $statement->fetch()) {
					$sum += $row['contribution']*3;
				}
				echo sprintf("%01.2f", $sum).$currency;
				?>
				</span>
			</p>
		</div>
		<div class="col-md-6 spacer">
			<span class="subtitle2">Mitgliedsdarlehen</span><br>
			<p>Summe der eingezogenen Darlehen:
				<span class="green emph">
				<?php
				$sum = 0;
				$statement = $pdo->prepare("SELECT users.loan FROM users LEFT JOIN loans ON users.uid = loans.uid WHERE loans.recieved = 1");
				$result = $statement->execute();

				while ($row = $statement->fetch()) {
					$sum += $row['loan'];
				}
				echo sprintf("%01.2f", $sum).$currency;
				?>
				</span>
			</p>
		</div>
	</div>
	<div class="spacer full">
	<span class="subtitle2">Kontoinformationen</span><br><br>
		<table class="table panel panel-default" style="min-width: 820px">
		<tr>
			<th>#</th>
			<th>Vorname</th>
			<th>Nachname</th>
			<th>Kontoinhaber</th>
			<th>IBAN</th>
			<th>BIC</th>
			<th>Darlehen</th>
			<th>Beitrag</th>
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
			echo '<td>'. htmlspecialchars($row['account_holder']) .'</td>';
			echo '<td>'. $row['IBAN'] .'</td>';
			echo '<td>'. $row['BIC'] . '</td>';
			echo '<td>'. sprintf("%01.2f", $row['loan']). $currency .'</td>';
			echo '<td>'. sprintf("%01.2f", $row['contribution']). $currency .'</td>';
			echo "</tr>";
		}
		?>
		</table>
	</div>
</div>

<?php 
include("templates/footer.inc.php")
?>