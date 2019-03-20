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
		if ($_POST['agree1'] == true) {
			 		
			$uid = $user['uid'];

			$mailtxt = '<h2>Deine Bestellung</h2>';

			foreach($_SESSION["products"] as $pro_id => $producer) {
				$grandtotal = 0;
				$statement = $pdo->prepare("INSERT INTO orders (uid) VALUES ($uid)");
				$result1 = $statement->execute();

				$oid = $pdo->lastInsertId();

				$statement = $pdo->prepare("SELECT producerName FROM producers WHERE pro_id = '$pro_id'");
				$statement->execute();
				$row = $statement->fetch();
				

				$mailtxt .= '
				<h3>'. $row['producerName'] .'</h3>
				<table>
					<tr style="text-align: left;">
					<th>Artikel</th><th>Preis KG/L</th>
					<th>Menge</th>
					<th>&#931;</th>
				</tr>';
				foreach ($producer as $product) {
	            
	            $price_KG_L = $product["price_KG_L"];
	            $pid = $product["pid"];
	            $quantity = $product["quantity"];
	            $productName = $product['productName'];
	            $total = ($price_KG_L * $quantity);
	            $grandtotal += $total;

				$statement = $pdo->prepare("INSERT INTO order_items (pid, oid, quantity, total) VALUES (:pid, :oid, :quantity, :total)");
				$result2 = $statement->execute(array('pid' => $pid, 'oid' => $oid, 'quantity' => $quantity, 'total' => $total));
				$mailtxt .= '<tr><td>'. htmlspecialchars($productName) .'</td><td>'. $currency. sprintf("%01.2f", ($price_KG_L)) .'</td><td>'. $quantity .'</td><td>'.$currency. sprintf("%01.2f", ($total)). '</td></tr>';
				}

				$mailtxt .= '
					<tr>
						<td></td>
						<td></td>
						<td></td>
						<td style="font-weight: 600">'.$currency.sprintf("%01.2f",$grandtotal).' </td>
					</tr>
				</table>';
			}

			$email = $user['email'];
			$first_name = $user['first_name'];
			$last_name = $user['last_name'];

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
			    $mail->setFrom('kontakt@namiko.org', 'namiko e.V. Hannover');
			    $mail->addAddress($email, $first_name.' '.$last_name);
			    $mail->addReplyTo('kontakt@namiko.org', 'namiko e.V. Hannover');

			    //Content
			    $mail->isHTML(true);
			    $mail->Subject = 'Deine Bestellung beim namiko Hannover e.V.';
			    $mail->Body    = '<h1>Moin, '. htmlspecialchars($first_name) .'!</h1>
			    					<p>Hiermit, bestätigen wir, dass Deine Bestellung bei uns eingangen ist. Zur Übersicht noch einmal eine Aufstellung der Artikel:<br><br>
			    					'. $mailtxt .'
			    					<br><br>
			    					Denk daran, Deine Bestellung als abgeholt zu markieren unter <a href="https://m.namiko.org/my-orders.php">Meine Bestellungen</a>.
			    					<br><br>
			    					<span style="font-style: italic">Dein namiko Hannover e.V. Team</span><br><br><br><br><br><br>
			    					Bei Rückfragen einfach an kontakt@namiko.org schreiben.</p>';
			    $mail->AltBody = 'Moin, '. $first_name .'!
			    					iermit, bestätigen wir, dass Deine Bestellung bei uns eingangen ist. Zur Übersicht noch einmal eine Aufstellung der Artikel:'. 
			    					$mailtxt .'
			    					<br><br>
			    					Denk daran, Deine Bestellung als abgeholt zu markieren unter Meine Bestellungen.
			    					<br><br>
			    					Dein namiko Hannover e.V. Team
			    					Bei Rueckfragen einfach an kontakt@namiko.org schreiben.';

			    $mail->send();
			    $result3 = true;
			} catch (Exception $e) {
			    $result3 = false;
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
		$_SESSION['notification'] = true;
		$_SESSION['notificationmsg'] = 'Du bist leider noch nicht freigeschaltet für Bestellungen.';
		header('location: internal.php');
	}
}

?>