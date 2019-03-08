<?php 
ini_set('display_errors', 1);
session_start();
require_once("inc/config.inc.php");
require_once("inc/functions.inc.php");
require_once('util/tcpdf/tcpdf_barcodes_2d.php');

require('util/phpmailer/Exception.php');
require('util/phpmailer/PHPMailer.php');
require('util/phpmailer/SMTP.php');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

//Überprüfe, dass der User eingeloggt ist
//Der Aufruf von check_user() muss in alle internen Seiten eingebaut sein
$user = check_user();

include("templates/header.inc.php");
include("templates/nav.inc.php");

if (isset($_POST['pay'])) {
	if ($user['rights'] > 1) {
		if ($_POST['agree1'] == true && $_POST['agree2'] == true) {
			 		
			 $uid = $user['uid'];
			 $verify_code = $user['verify_code'];
			 $statement = $pdo->prepare("INSERT INTO orders (uid) VALUES ($uid)");
			 $result1 = $statement->execute();

			 $statement = "SELECT oid FROM orders WHERE uid = '$uid' AND created_at = (SELECT MAX(created_at) FROM orders)";
			 $oid = $pdo->query($statement)->fetchAll(PDO::FETCH_COLUMN);

			 $mailtxt = '<h3>Deine Bestellung</h3>
	                     <table><tr style="text-align: left;"><th>Artikel</th><th>Preis KG/L</th><th>Menge</th><th>&#931;</th></tr>';
	         $grandtotal = 0;

			foreach($_SESSION["products"] as $product){
	            
	            $price_KG_L = $product["price_KG_L"];
	            $pid = $product["pid"];
	            $quantity = $product["quantity"];
	            $productName = $product['productName'];
	            $total = ($price_KG_L * $quantity);
	            $grandtotal += $total;

				$statement = $pdo->prepare("INSERT INTO order_items (pid, oid, quantity, total) VALUES (:pid, :oid, :quantity, :total)");
				$result2 = $statement->execute(array('pid' => $pid, 'oid' => $oid[0], 'quantity' => $quantity, 'total' => $total));
				$mailtxt .= '<tr><td>'. htmlspecialchars($productName) .'</td><td>'. $currency. sprintf("%01.2f", ($price_KG_L)) .'</td><td>'. $quantity .'</td><td>'.$currency. sprintf("%01.2f", ($total)). '</td></tr>';
			}

			$mailtxt .= '<tr><td></td><td></td><td></td><td style="font-weight: 600">'.$currency.sprintf("%01.2f",$grandtotal).' </td></table>';

			$email = $user['email'];
			$first_name = $user['first_name'];
			$last_name = $user['last_name'];

			require_once('util/tcpdf/tcpdf.php');
		 
				// Erstellung des PDF Dokuments
				$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
				 
				// Dokumenteninformationen
				$pdf->SetCreator(PDF_CREATOR);
				$pdf->SetAuthor('admin');
				$pdf->SetTitle('Mitglied Nr. '. $uid[0] .'_'. $_SESSION['first_name'] .' '. $name);
				$pdf->SetSubject('Mitgliedschaft');
				 
				 
				// Header und Footer Informationen
				$pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
				$pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));
				 
				// Auswahl des Font
				$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
				 
				// Auswahl der MArgins
				$pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
				$pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
				$pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
				 
				// Automatisches Autobreak der Seiten
				$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
				 
				// Image Scale 
				$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
				 
				// Schriftart
				$pdf->SetFont('helvetica', '', 10);
				 
				// Neue Seite
				$pdf->AddPage();

				$qrObject = $verify_code . $oid[0];

				$pdf->write2DBarcode($qrObject, 'QRCODE,H', 45, 30, 120, 120, $style, 'N');

				$pdf->Output(dirname(__FILE__).'/qr/'. $qrObject. '.pdf', 'F');

			$mail = new PHPMailer(true);                              // Passing `true` enables exceptions
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

				    $mail->addAttachment('qr/'. $qrObject. '.pdf');         // Add attachments
				   
				    //Content
				    $mail->isHTML(true);                                  // Set email format to HTML
				    $mail->Subject = 'Deine Bestellung beim namiko Hannover e.V.';
				    $mail->Body    = '<h1>Moin, '. htmlspecialchars($first_name) .'!</h1>
				    					<p>Hiermit, bestätigen wir, dass Deine Bestellung bei uns eingangen ist. Zur Übersicht noch einmal eine Aufstellung der Artikel:<br><br>
				    					'. $mailtxt .'
				    					<br><br>
				    					Um deine Bestellung abzuholen, musst Du den QR Code im Anhang vorzeigen.
				    					<br><br>
				    					Wir freuen uns sehr mit Dir zusammenzuarbeiten. Alternativ kannst Du Dich auch bei einloggen und unter <a href="m.namiko.org/my-orders.php">"Meine Bestellungen"</a> Deine Bestellung und den QR Code einsehen.<br><br><span style="font-style: italic">Dein namiko Hannover e.V. Team</span><br><br><br><br><br><br>
				    					Bei Rückfragen einfach an kontakt@namiko.org schreiben.</p>';
				    $mail->AltBody = 'Moin, '. $first_name .'!
				    					iermit, bestätigen wir, dass Deine Bestellung bei uns eingangen ist. Zur Übersicht noch einmal eine Aufstellung der Artikel:'. 
				    					$mailtxt .'
				    					<br><br>
				    					Um deine Bestellung abzuholen, musst Du den QR Code im Anhang vorzeigen. 
				    					<br><br>
				    					Wir freuen uns sehr mit Dir zusammenzuarbeiten. Alternativ kannst Du Dich auch bei namiko.org einloggen und unter m.namiko.org/my-orders.php Deine Bestellung und den QR Code einsehen. Dein namiko Hannover e.V. Team
				    					Bei Rueckfragen einfach an kontakt@namiko.org schreiben.';

				    $mail->send();
				    $result3 = true;
				} catch (Exception $e) {
				    $result3 = false;
				}

				$files = glob('qr/*'); // get all file names
				foreach($files as $file){ // iterate files
				  if(is_file($file))
				    unlink($file); // delete file
				}

			if ($result1 && $result2 && $result3) {
				unset($_SESSION['products']);
				$_SESSION['notification'] = true;
				$_SESSION['notificationmsg'] = 'Deine Bestellung wurde erfolgreich abgeschickt. Die Eingangsbestätigung hast du per E-Mail erhalten. Alternativ einfach direkt zu <a href="my-orders.php">deinen Bestellungen</a>.';
				header('location: internal.php');
			}

			if (!$result1 || !$result2 || !$result3) {
				unset($_SESSION['products']);
				$_SESSION['notification'] = true;
				$_SESSION['notificationmsg'] = 'Es gab einen Fehler beim Absenden Deiner Bestellung. Überprüfe, ob die Bestellung trotzdem unter <a href="my-orders.php">deinen Bestellungen</a> angezeigt wird.';
				header('location: internal.php');
			}
		}
	} else {
		unset($_SESSION['products']);
		$_SESSION['notification'] = true;
		$_SESSION['notificationmsg'] = 'Du bist leider noch nicht freigeschaltet für Bestellungen.';
		header('location: internal.php');
	}
}

?>