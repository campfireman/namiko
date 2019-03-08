<?php
session_start();
require_once("inc/config.inc.php");
require_once("inc/functions.inc.php");
//ini_set('display_errors', 1);
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
		header("Location: " . $_SERVER['REQUEST_URI']);
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
			header("Location: " . $_SERVER['REQUEST_URI']);
		}

		if (!$result) {
			$_SESSION['notification'] = true;
			$_SESSION['notificationmsg'] = 'Es gab einen Fehler';
			header("Location: " . $_SERVER['REQUEST_URI']);
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
		header("Location: " . $_SERVER['REQUEST_URI']);
	}

	if (!$result) {
		$_SESSION['notification'] = true;
		$_SESSION['notificationmsg'] = 'Es gab einen Fehler';
		header("Location: " . $_SERVER['REQUEST_URI']);
	}
}

if (isset($_POST['delete'])) {
	$uid = $_POST['uid'];

	$statement = $pdo->prepare("DELETE mandates.*, users.*  FROM mandates LEFT JOIN users ON users.uid = mandates.uid WHERE mandates.uid = '$uid'");
	$result = $statement->execute();
	print_r($statement->errorInfo());

	if ($result) {
		$_SESSION['notification'] = true;
		$_SESSION['notificationmsg'] = 'User erfolgreich gelöscht.';
		header("Location: " . $_SERVER['REQUEST_URI']);
	}

	if (!$result) {
		$_SESSION['notification'] = true;
		$_SESSION['notificationmsg'] = 'Es gab einen Fehler';
		//header("Location: " . $_SERVER['REQUEST_URI']);
	}
}

if(isset($_POST['loanPay'])) {
	$creator = $user['uid'];
	$collectionDt = $_POST['date'];

	$statement = $pdo->prepare("INSERT INTO sepaDocs (creator) VALUES (:creator)");
	$result = $statement->execute(array('creator' => $creator));

	if ($result) {
		$statement = $pdo->prepare("SELECT sid FROM sepaDocs WHERE created_at = (SELECT MAX(created_at) FROM sepaDocs)");
		$result = $statement->execute();
		$sepaDoc = $statement->fetch();

		$sid = $sepaDoc['sid'];
		$payment;
		$date = date('Ymd');
		$time = date('His');
		$msgID = $myBIC . 'SID' . $sid .'-'. $date . $time;
		$creDtTm = date('Y-m-d') . 'T' . date('H:i:s');
		$pymntID = 'SID'. $sid .'D'. $date .'T'. $time;

		
		$doc = new DOMDocument('1.0', 'utf-8');
		$doc->formatOutput = true;

		$root = $doc->createElementNS('urn:iso:std:iso:20022:tech:xsd:pain.008.001.02', 'Document');
		$doc->appendChild($root);
		$root->setAttributeNS('http://www.w3.org/2000/xmlns/' ,'xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
		$root->setAttributeNS('http://www.w3.org/2001/XMLSchema-instance', 'xsi:schemaLocation', 'urn:iso:std:iso:20022:tech:xsd:pain.008.001.02 pain.008.001.02.xsd');

		$CstmrDrctDbtInitn = $doc->createElement('CstmrDrctDbtInitn');
		$CstmrDrctDbtInitn = $root->appendChild($CstmrDrctDbtInitn);
		
		// Layer 1: CstmrDrctDbtInitn
		$GrpHdr = $doc->createElement('GrpHdr');
		$GrpHdr = $CstmrDrctDbtInitn->appendChild($GrpHdr);

			// Layer 2: GrpHdr
			$GrpHdr->appendChild($doc->createElement('MsgId', $msgID));
			$GrpHdr->appendChild($doc->createElement('CreDtTm', $creDtTm));

		// Layer 1: CstmrDrctDbtInitn
		$PmtInf = $doc->createElement('PmtInf');
		$PmtInf = $CstmrDrctDbtInitn->appendChild($PmtInf);

			// Layer 2: PmtInf
			$PmtInf->appendChild($doc->createElement('PmtInfId', $pymntID));
			$PmtInf->appendChild($doc->createElement('PmtMtd', 'DD'));
			$PmtInf->appendChild($doc->createElement('BtchBookg', 'true'));
			$PmtTpInf = $doc->createElement('PmtTpInf');
			$PmtTpInf = $PmtInf->appendChild($PmtTpInf);

				// Layer 3: PmtTpInf
				$SvcLvl = $doc->createElement('SvcLvl');
				$SvcLvl = $PmtTpInf->appendChild($SvcLvl);

					// Layer 4: SvcLvl
					$SvcLvl->appendChild($doc->createElement('Cd', 'SEPA'));

				$LclInstrm = $doc->createElement('LclInstrm');
				$LclInstrm = $PmtTpInf->appendChild($LclInstrm);

					// Level 4: LclInstrm
					$LclInstrm->appendChild($doc->createElement('Cd', 'CORE'));

				$PmtTpInf->appendChild($doc->createElement('SeqTp', 'RCUR'));

			$PmtInf->appendChild($doc->createElement('ReqdColltnDt', $collectionDt));
			$Cdtr = $doc->createElement('Cdtr');
			$Cdtr = $PmtInf->appendChild($Cdtr);

				// Level 4: Cdtr
				$Cdtr->appendChild($doc->createElement('Nm', $myEntity));

			$CdtrAcct = $doc->createElement('CdtrAcct');
			$CdtrAcct = $PmtInf->appendChild($CdtrAcct);

				// Level 4: CdtrAcct
				$Id = $doc->createElement('Id');
				$Id = $CdtrAcct->appendChild($Id);

					// Level 5: Id
					$Id->appendChild($doc->createElement('IBAN', $myIBAN));

			$CdtrAgt = $doc->createElement('CdtrAgt');
			$CdtrAgt = $PmtInf->appendChild($CdtrAgt);

				// Level 4: CdtrAgt
				$FinInstnId = $doc->createElement('FinInstnId');
				$FinInstnId = $CdtrAgt->appendChild($FinInstnId);

					// Level 5: FinInstId
					$FinInstnId->appendChild($doc->createElement('BIC', $myBIC));

			$PmtInf->appendChild($doc->createElement('ChrgBr', 'SLEV'));
			$CdtrSchmeId = $doc->createElement('CdtrSchmeId');
			$CdtrSchmeId = $PmtInf->appendChild($CdtrSchmeId);

				// Level 4: CdtrSchmeId
				$Id = $doc->createElement('Id');
				$Id = $CdtrSchmeId->appendChild($Id);

					// Level 5: Id
					$PrvtId = $doc->createElement('PrvtId');
					$PrvtId = $Id->appendChild($PrvtId);

						// Level 6: PrvtId
						$Othr = $doc->createElement('Othr');
						$Othr = $PrvtId->appendChild($Othr);

							// Level 7: Othr
							$Othr->appendChild($doc->createElement('Id', $creditorId));
							$SchmeNm = $doc->createElement('SchmeNm');
							$SchmeNm = $Othr->appendChild($SchmeNm);

								// Level 8: SchmeNm
								$SchmeNm->appendChild($doc->createElement('Prtry', 'SEPA'));


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
				$account_holder = $row['account_holder'];
				$IBAN = $row['IBAN'];
				$BIC = $row['BIC'];
				$mid = $row['mid'];
				$signed = substr($row['created_at'], 0, 10);
				$loan = $row['loan'];
				$totalTx += $loan;
				
				$statement2 = $pdo->prepare("INSERT INTO payments (uid, reference, amount) VALUES (:uid, :reference, :amount)");
				$result2 = $statement2->execute(array('uid' => $uid, 'reference' => $sid, 'amount' => $loan));

				$statement3 = $pdo->prepare("SELECT pay_id FROM payments WHERE uid = '$uid' AND reference = '$sid'");
				$result3 = $statement3->execute();
				$pay_id = $statement3->fetch();

				$statement4 = $pdo->prepare("INSERT INTO loans (uid, pay_id) VALUES (:uid, :pay_id)");
				$result4 = $statement4->execute(array('uid' => $uid, 'pay_id' => $pay_id['pay_id']));

				$txID = 'ID'. $pay_id['pay_id'] .'D'. $date .'T'. $time;

				// Layer 2: PmtInf
				$DrctDbtTxInf = $doc->createElement('DrctDbtTxInf');
				$DrctDbtTxInf = $PmtInf->appendChild($DrctDbtTxInf);

					// Layer 3: DrctDbtTxInf
					$PmtId = $doc->createElement('PmtId');
					$PmtId = $DrctDbtTxInf->appendChild($PmtId);
						
						// Layer 4: PmtId
						$PmtId->appendChild($doc->createElement('EndToEndId', $txID));

					$InstdAmt = $doc->createElement('InstdAmt', $loan);
					$InstdAmt->setAttribute('Ccy', 'EUR');
					$DrctDbtTxInf->appendChild($InstdAmt);

					$DrctDbtTx = $doc->createElement('DrctDbtTx');
					$DrctDbtTx = $DrctDbtTxInf->appendChild($DrctDbtTx);

						// Layer 4: DrctDbtTx
						$MndtRltdInf = $doc->createElement('MndtRltdInf');
						$MndtRltdInf = $DrctDbtTx->appendChild($MndtRltdInf);

							// Layer 5: MndtRltdInf
							$MndtRltdInf->appendChild($doc->createElement('MndtId', $mid));
							$MndtRltdInf->appendChild($doc->createElement('DtOfSgntr', $signed));
							$MndtRltdInf->appendChild($doc->createElement('AmdmntInd', 'false'));

					$DbtrAgt = $doc->createElement('DbtrAgt');
					$DbtrAgt = $DrctDbtTxInf->appendChild($DbtrAgt);

						// Layer 4: DbtrAgt
						$FinInstnId = $doc->createElement('FinInstnId');
						$FinInstnId = $DbtrAgt->appendChild($FinInstnId);
							
							// Layer 5: FinInstnId
							$FinInstnId->appendChild($doc->createElement('BIC', $BIC));

					$Dbtr = $doc->createElement('Dbtr');
					$Dbtr = $DrctDbtTxInf->appendChild($Dbtr);
						
						// Layer 4: Dbtr
						$Dbtr->appendChild($doc->createElement('Nm', $account_holder));

					$DbtrAcct = $doc->createElement('DbtrAcct');
					$DbtrAcct = $DrctDbtTxInf->appendChild($DbtrAcct);

						// Layer 4: DbtrAcct
						$Id = $doc->createElement('Id');
						$Id = $DbtrAcct->appendChild($Id);
							
							// Layer 5: Id
							$Id->appendChild($doc->createElement('IBAN', $IBAN));

					$RmtInf = $doc->createElement('RmtInf');
					$RmtInf = $DrctDbtTxInf->appendChild($RmtInf);
						
						// Layer 4: RmtInf
						$RmtInf->appendChild($doc->createElement('Ustrd', 'Mitgliedsdarlehn namiko Hannover e.V.'));

				$mail = new PHPMailer(true);
				try {
				    //Server settings
				    $mail->SMTPDebug = 0;                                 // Enable verbose debug output
				    $mail->isSMTP();                                      // Set mailer to use SMTP
				    $mail->Host = $smtp_host;  // Specify main and backup SMTP servers
				    $mail->SMTPAuth = true;                               // Enable SMTP authentication
				    $mail->Username = $smtp_username;                 // SMTP username
				    $mail->Password = $smtp_password;                           // SMTP password
				    $mail->SMTPSecure = 'tls';                            // Enable TLS encryption, `ssl` also accepted
				    $mail->Port = 587;                                    // TCP port to connect to

				    //Recipients
				    $mail->setFrom('noreply@namiko.org', 'namiko e.V. Hannover');
				    $mail->addAddress($email, $first_name.' '.$last_name);     // Add a recipient
				    $mail->addReplyTo('noreply@namiko.org', 'NoReply');

				    //Content
				    $mail->isHTML(true);                                  // Set email format to HTML
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

			}


				// Layer 2: GrpHdr
				$GrpHdr->appendChild($doc->createElement('NbOfTxs', $numberTx));
				$GrpHdr->appendChild($doc->createElement('CtrlSum', sprintf("%01.2f", ($totalTx))));
				$InitgPty = $doc->createElement('InitgPty');
				$InitgPty = $GrpHdr->appendChild($InitgPty);
					
					// Layer 3: InitgPty
					$InitgPty->appendChild($doc->createElement('Nm', $myEntity));

				// Layer 2: PmtInf
				$CtrlSum = $doc->createElement('CtrlSum', sprintf("%01.2f", ($totalTx)));
				$CtrlSum = $PmtInf->insertBefore($CtrlSum, $PmtTpInf);
				$NbOfTxs = $doc->createElement('NbOfTxs', $numberTx);
				$NbOfTxs = $PmtInf->insertBefore($NbOfTxs, $CtrlSum);

			//echo $dom->saveXML();

			$filename = dirname(__FILE__).'/sepa/'. $pymntID .'.xml';
			$doc->save($filename);

			$statement = $pdo->prepare("UPDATE sepaDocs SET PmtInfId = '$pymntID' WHERE sid = '$sid'");
			$result = $statement->execute();

			header('Content-type: "text/xml"; charset="utf8";');
			header('Content-Transfer-Encoding: Binary');
			header('Content-disposition: attachment; filename="'. $pymntID .'.xml"');
			while (ob_get_level()) {
			    ob_end_clean();
			}
			readfile($filename);
			exit();

		} else {
			$_SESSION['notification'] = true;
			$_SESSION['notificationmsg'] = 'Es konnte kein XML Dokument erstellt werden.';
			header("Location: " . $_SERVER['REQUEST_URI']);
		}

	} else {
		$_SESSION['notification'] = true;
		$_SESSION['notificationmsg'] = 'Es konnte kein Datenbankeintrag für das Dokument erstellt werden.';
		header("Location: " . $_SERVER['REQUEST_URI']);
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
			    $mail->SMTPDebug = 0;                                 // Enable verbose debug output
			    $mail->isSMTP();                                      // Set mailer to use SMTP
			    $mail->Host = $smtp_host;  // Specify main and backup SMTP servers
			    $mail->SMTPAuth = true;                               // Enable SMTP authentication
			    $mail->Username = $smtp_username;                 // SMTP username
			    $mail->Password = $smtp_password;                           // SMTP password
			    $mail->SMTPSecure = 'tls';                            // Enable TLS encryption, `ssl` also accepted
			    $mail->Port = 587;                                    // TCP port to connect to

			    //Recipients
			    $mail->setFrom('noreply@namiko.org', 'namiko e.V. Hannover');
			    $mail->addAddress($email, $first_name.' '.$last_name);     // Add a recipient
			    $mail->addReplyTo('noreply@namiko.org', 'NoReply');

			    //Content
			    $mail->isHTML(true);                                  // Set email format to HTML
			    $mail->Subject = 'Freischaltung Deines Accounts';
			    $mail->Body    = '<h1>Moin, '. htmlspecialchars($first_name) .'!</h1>
			    	<p>Dein Mitgliedsdarlehn ist erfolgreich eingetroffen. Du bist nun für Bestellungen freigeschaltet.</p>
			    	<br><br><span style="font-style: italic">Dein namiko Hannover e.V. Team</span><br><br><br><br><br><br>
			    					Bei Rückfragen einfach an kontakt@namiko.org schreiben.</p>';
			    $mail->AltBody = 'Moin, '. htmlspecialchars($first_name) .'!
			    	Dein Mitgliedsdarlehn ist erfolgreich eingetroffen. Du bist nun für Bestellungen freigeschaltet.
			    	Dein namiko Hannover e.V. Team
			    					Bei Rückfragen einfach an kontakt@namiko.org schreiben.';

			    $mail->send();
			    $result = true;
			} catch (Exception $e) {
			    $result = false;
			}

			if ($result) {
				header("Location: " . $_SERVER['REQUEST_URI']);
			} else {
				$_SESSION['notification'] = true;
				$_SESSION['notificationmsg'] = 'Die Infomail konnte nicht verschickt werden.';
				header("Location: " . $_SERVER['REQUEST_URI']);
			}
		} else {
			$_SESSION['notification'] = true;
			$_SESSION['notificationmsg'] = 'Der Darlehn konnte nicht als bezahlt markiert werden.';
			header("Location: " . $_SERVER['REQUEST_URI']);
		}
	} else {
		$_SESSION['notification'] = true;
		$_SESSION['notificationmsg'] = 'Die Rechte konnten nicht aktualisiert werden.';
		header("Location: " . $_SERVER['REQUEST_URI']);
	}
}
?>

<div class="sizer spacer">
	<div class="row">
		<div class="col-sm-6">
		<span class="subtitle2">Benachrichtigung</span><br><br>
		<span><i class="fa fa-info-circle" aria-hidden="true"></i> Hier kannst Du eine Benachrichtigung erstellen, die bei jedem Mitglied beim Login angezeigt wird. Es kann immer nur eine Benachrichtigung angezeigt, alte und ungelesene Benachrichtigungen werden überschrieben.</span>
		</div>
		
		<div class="col-sm-6">
			<form class="form" action="<?php htmlspecialchars($_SERVER['REQUEST_URI']) ?>" method="post">
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
			$statement = $pdo->prepare("SELECT * FROM users ORDER BY uid");
			$result = $statement->execute();
			
			
			while($row = $statement->fetch()) {
				echo "<tr>";
				echo "<td>";
					echo htmlspecialchars($row['uid']);
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
					    		  <form style="position: relative; left: -5px;" action="'. htmlspecialchars($_SERVER['REQUEST_URI']) .'" method="post">
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
				echo 	'<form style="position: relative; left: -5px;" action="'. htmlspecialchars($_SERVER['REQUEST_URI']) .'" method="post">';
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
						echo 		"<form method='post' action=". htmlspecialchars($_SERVER['REQUEST_URI']) .">";
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
							while ($row = $statement->fetch()) {
								$total += $row['loan'];
							}

							echo $count . " offene Darlehn über ". $currency. sprintf("%01.2f", $total);
						} else {
							echo "Keine offenen Darlehn.";
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