<?php
session_start();
require_once("inc/config.inc.php");
require_once("inc/functions.inc.php");
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
}


if(isset($_POST['memberPay'])) {
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


		$statement = $pdo->prepare("SELECT users.uid, users.first_name, users.last_name, users.email, users.account_holder, users.IBAN, users.BIC, users.contribution, mandates.mid, mandates.created_at FROM users LEFT JOIN mandates ON users.uid = mandates.uid WHERE rights >= 1");
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
				$contribution = (3 * $row['contribution']);
				$totalTx += $contribution;
				
				$statement2 = $pdo->prepare("INSERT INTO payments (uid, reference, amount) VALUES (:uid, :reference, :amount)");
				$result2 = $statement2->execute(array('uid' => $uid, 'reference' => $sid, 'amount' => $contribution));

				$statement3 = $pdo->prepare("SELECT pay_id FROM payments WHERE uid = '$uid' AND reference = '$sid'");
				$result3 = $statement3->execute();
				$pay_id = $statement3->fetch();

				$txID = 'ID'. $pay_id['pay_id'] .'D'. $date .'T'. $time;

				// Layer 2: PmtInf
				$DrctDbtTxInf = $doc->createElement('DrctDbtTxInf');
				$DrctDbtTxInf = $PmtInf->appendChild($DrctDbtTxInf);

					// Layer 3: DrctDbtTxInf
					$PmtId = $doc->createElement('PmtId');
					$PmtId = $DrctDbtTxInf->appendChild($PmtId);
						
						// Layer 4: PmtId
						$PmtId->appendChild($doc->createElement('EndToEndId', $txID));

					$InstdAmt = $doc->createElement('InstdAmt', $contribution);
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
						$RmtInf->appendChild($doc->createElement('Ustrd', 'Mitgliedsbeitrag Quartal namiko Hannover e.V.'));

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
				    $mail->Subject = 'Einzug des Mitgliedsbeitrages';
				    $mail->Body    = '<h1>Moin, '. htmlspecialchars($first_name) .'!</h1>
				    	<p>Den Mitgliedsbeitrag für dieses Qurtal von EUR '. sprintf("%01.2f", ($contribution)) .' ziehen wir mit der SEPA-Lastschrift zum Mandat mit der Referenznummer '. $mid .' zu der Gläubiger-Identifikationsnummer '. $creditorId .' von Deinem Konto IBAN '. $IBAN .' bei BIC '. $BIC .' zum Fälligkeitstag '. $collectionDt .' ein.
				    	<br><br><span style="font-style: italic">Dein namiko Hannover e.V. Team</span><br><br><br><br><br><br>
				    					Bei Rückfragen einfach an kontakt@namiko.org schreiben.</p>';
				    $mail->AltBody = 'Moin, '. htmlspecialchars($first_name) .'!
				    	Den Mitgliedsbeitrag für dieses Qurtal von EUR '. sprintf("%01.2f", ($contribution)) .' ziehen wir mit der SEPA-Lastschrift zum Mandat mit der Referenznummer '. $mid .' zu der Gläubiger-Identifikationsnummer '. $creditorId .' von Deinem Konto IBAN '. $IBAN .' bei BIC '. $BIC .' zum Fälligkeitstag '. $collectionDt .' ein.
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

////////////////////////////////////// 

if(isset($_POST['orderPay'])) {
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


		$statement = $pdo->prepare("SELECT orders.oid, users.uid, users.first_name, users.last_name, users.email, users.account_holder, users.IBAN, users.BIC, mandates.mid, mandates.created_at FROM orders LEFT JOIN users ON orders.uid = users.uid LEFT JOIN mandates ON users.uid = mandates.uid WHERE (orders.paid = 0)". $_SESSION['timeToggle'] ."");
		$result = $statement->execute();

		if ($result) {
			$numberTx = 0;
			$totalTx = 0;

			while ($row = $statement->fetch()) {
				$numberTx++;
				$oid = $row['oid'];
				$uid = $row['uid'];
				$first_name = $row['first_name'];
				$last_name = $row['last_name'];
				$email = $row['email'];
				$account_holder = $row['account_holder'];
				$IBAN = $row['IBAN'];
				$BIC = $row['BIC'];
				$mid = $row['mid'];
				$signed = substr($row['created_at'], 0, 10);

				$statement2 = $pdo->prepare("SELECT total FROM order_items WHERE oid = '$oid'");
				$result2 = $statement2->execute();

				$item_sum = 0;
				$txSum = 0;

				while ($row2 = $statement2->fetch()) {
					$item_sum = $row2['total'];
					$txSum += $item_sum;
				}

				$totalTx += $txSum;
				
				$statement3 = $pdo->prepare("INSERT INTO payments (uid, reference, amount) VALUES (:uid, :reference, :amount)");
				$result3 = $statement3->execute(array('uid' => $uid, 'reference' => $sid, 'amount' => $txSum));

				$statement3 = $pdo->prepare("SELECT pay_id FROM payments WHERE uid = '$uid' AND reference = '$sid'");
				$result3 = $statement3->execute();
				$pay_id = $statement3->fetch();

				$txID = 'ID'. $pay_id['pay_id'] .'D'. $date .'T'. $time;

				// Layer 2: PmtInf
				$DrctDbtTxInf = $doc->createElement('DrctDbtTxInf');
				$DrctDbtTxInf = $PmtInf->appendChild($DrctDbtTxInf);

					// Layer 3: DrctDbtTxInf
					$PmtId = $doc->createElement('PmtId');
					$PmtId = $DrctDbtTxInf->appendChild($PmtId);
						
						// Layer 4: PmtId
						$PmtId->appendChild($doc->createElement('EndToEndId', $txID));

					$InstdAmt = $doc->createElement('InstdAmt', $txSum);
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
						$RmtInf->appendChild($doc->createElement('Ustrd', 'Bezahlung der Bestellung #'. $oid .''));

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
				    $mail->Subject = 'Bezahlung der Bestellung #'. $oid .'';
				    $mail->Body    = '<h1>Moin, '. htmlspecialchars($first_name) .'!</h1>
				    	<p>Die Summe von EUR '. sprintf("%01.2f", ($txSum)) .' ziehen wir mit der SEPA-Lastschrift zum Mandat mit der Referenznummer '. $mid .' zu der Gläubiger-Identifikationsnummer '. $creditorId .' von Deinem Konto IBAN '. $IBAN .' bei BIC '. $BIC .' zum Fälligkeitstag '. $collectionDt .' ein.
				    	<br><br><span style="font-style: italic">Dein namiko Hannover e.V. Team</span><br><br><br><br><br><br>
				    					Bei Rückfragen einfach an kontakt@namiko.org schreiben.</p>';
				    $mail->AltBody = 'Moin, '. htmlspecialchars($first_name) .'!
				    	Die Summe von EUR '. sprintf("%01.2f", ($txSum)) .' ziehen wir mit der SEPA-Lastschrift zum Mandat mit der Referenznummer '. $mid .' zu der Gläubiger-Identifikationsnummer '. $creditorId .' von Deinem Konto IBAN '. $IBAN .' bei BIC '. $BIC .' zum Fälligkeitstag '. $collectionDt .' ein.
				    	Dein namiko Hannover e.V. Team
				    					Bei Rückfragen einfach an kontakt@namiko.org schreiben.';

				    $mail->send();
				    $result3 = true;
				} catch (Exception $e) {
				    $result3 = false;
				}

				$statement2 = $pdo->prepare("UPDATE orders SET paid = 1 WHERE (oid='$oid')". $_SESSION['timeToggle'] ."");
				$result2 = $statement2->execute();

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

?>

<div class="sizer">
	<div class="row">
		<div class="col-md-6 spacer">
			<span class="subtitle2">Mitgliedsbeiträge einziehen</span><br>
			<p>aktuelle Höhe der Quartalsbezüge:
				<span class="green emph">
					<?php
						$statement = $pdo->prepare("SELECT contribution FROM users WHERE rights >= 1");
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
			<span><i class="fa fa-info-circle" aria-hidden="true"></i> Bei Erstellung des Dokuments wird automatisch an alle Mitglieder eine Email verschickt, die über den Einzug des Geldes informiert. Abhängig von der Internetverbindung kann dies etwas dauern, also den Tab offen lassen, nicht neu laden, bis der Download des Dokuments erscheint.<br>Das Fälligkeitsdatum muss in folgendem Format eingegeben werden: JJJJ-MM-TT. Es muss wie folgt berechnet werden: Aktueller Tag + 2 Bankarbeitstage (TARGET2)!</span><br><br>

			<form action="<?php htmlspecialchars($_SERVER['PHP_SELF']) ?>" method="post" class="form">
				<input type="text" name="date" placeholder="fälligskeitsdatum" required>
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
			<span><i class="fa fa-info-circle" aria-hidden="true"></i> Bei Erstellung des Dokuments wird automatisch an alle Mitglieder eine Email verschickt, die über den Einzug des Geldes informiert. Abhängig von der Internetverbindung kann dies etwas dauern, also den Tab offen lassen, nicht neu laden, bis der Download des Dokuments erscheint.<br>Das Fälligkeitsdatum muss in folgendem Format eingegeben werden: JJJJ-MM-TT. Es muss wie folgt berechnet werden: Aktueller Tag + 2 Bankarbeitstage (TARGET2)!</span><br><br>

			<form class="form" method="post" action="<?php echo htmlspecialchars($_SERVER['REQUEST_URI']) ?>">
				<select name="timeframe">
					<option value="0">Alle</option>
					<optgroup label="Zeitpunkte">
					<?php echo $timeframeOut; ?>
					</optgroup>
				</select>
				<button type="submit" class="clean-btn blue" name="toggleTimeframe">Aktualisieren <i class="fa fa-refresh" aria-hidden="true"></i></button>
			</form><br>

			<form action="<?php htmlspecialchars($_SERVER['PHP_SELF']) ?>" method="post" class="form">
				<input type="text" name="date" placeholder="fälligskeitsdatum" required>
				<button class="clean-btn green" name="orderPay" type="submit">XML erstellen <i class="fa fa-file-text-o" aria-hidden="true"></i></button>
			</form>
		</div>
	</div>
</div>

<?php 
include("templates/footer.inc.php")
?>