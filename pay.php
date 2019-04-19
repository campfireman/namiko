<?php 
ini_set('display_errors', 1);
session_start();
require_once("inc/config.inc.php");
require_once("inc/functions.inc.php");
require_once("inc/Cart.inc.php");
require_once("inc/IntegrityException.inc.php");


//Überprüfe, dass der User eingeloggt ist
//Der Aufruf von check_user() muss in alle internen Seiten eingebaut sein
$user = check_user();

include("templates/header.inc.php");
include("templates/nav.inc.php");

if (isset($_POST['pay'])) {
	if ($user['rights'] > 1) {
		if ($_POST['agree1']) {
			try {
				$pdo->beginTransaction();
				$db->lock(["orders", "order_items", "preorders", "preorder_items", "inventory_items"], "WRITE");
				// process cart
				$cart = new Cart();
				if ($cart->hasConflict()) {
					$cart->update();
					throw new IntegrityException("Die Verfügbarkeit folgender Artikel hat sich verändert: ". $cart->getItemConflicts());
					
				}
				$cart->process($user['uid'], $_SESSION['orders'], $_SESSION['preorders']);
				$cart->mail($user, $smtp_host, $smtp_username, $smtp_password, $myEmail, $myEntity);

				// save
				$pdo->commit();
				$db->unlock();

				unset($_SESSION['orders']);
				unset($_SESSION['preorders']);
				notify('Deine Bestellung wurde erfolgreich abgeschickt. Die Eingangsbestätigung hast du per E-Mail erhalten. Alternativ einfach direkt zu <a href="my-orders.php">deinen Bestellungen</a>.', "internal.php");

			} catch (IntegrityException $e) {
				notify($e->getMessage(), "check-out.php");
			} catch (Exception $e) {
				error($e->getMessage(), "internal.php");
			} finally {
				$pdo->rollBack();
			}
		} else {
			notify('Du musst den AGB zustimmen.');
		}
	} else {
		notify('Du bist leider noch nicht freigeschaltet für Bestellungen.');
	}
}
?>