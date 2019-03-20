<?php 
session_start();
require_once("inc/config.inc.php");
require_once("inc/functions.inc.php");

//Überprüfe, dass der User eingeloggt ist
//Der Aufruf von check_user() muss in alle internen Seiten eingebaut sein
$user = check_user();
check_admin();

include("templates/header.inc.php");

$oid = $_POST['oid'];
$witness = $user['uid'];
$signature = $_POST['signature'];

$statement = $pdo->prepare("UPDATE orders SET delivered = CASE WHEN delivered = 0 THEN  1 WHEN delivered = 1 THEN 'failed' END WHERE oid = '$oid'");
	$result = $statement->execute();
	$arr = $statement->errorInfo();
	print_r($arr);


if ($result) {
	$statement = $pdo->prepare("INSERT INTO delivery_proof (oid, witness, signature) VALUES (:oid, :witness, :signature)");
		$result = $statement->execute(array('oid' => $oid, 'witness' => $witness, 'signature' => $signature));

		if ($result) {

		$statement = $pdo->prepare("SELECT quantity, pid FROM order_items WHERE oid = '$oid'");
		$result = $statement->execute();

		while ($row = $statement->fetch()) {
			$quantity = $row['quantity'];
			$pid = $row['pid'];
			$uid = $user['uid'];

			$statement2 = $pdo->prepare("UPDATE inventory_items SET quantity_KG_L = quantity_KG_L - '$quantity', last_edited_by = '$uid' WHERE pid = '$pid'");
			$result2 = $statement2->execute();
		}

		if ($result) {
			header('location: session.php');
		} else {
			$_SESSION['notification'] = true;
			$_SESSION['notificationmsg'] = 'Das Inventar konnte nicht aktualisiert werden.';
			header('location: session.php');
		}

	} else {
		$_SESSION['notification'] = true;
		$_SESSION['notificationmsg'] = 'Der Ausstellungsnachweis konnte nicht abgespeichert werden.';
		header('location: session.php');
	}

} else {
	$_SESSION['notification'] = true;
	$_SESSION['notificationmsg'] = 'Die Bestellung wurde bereits ausgegeben.';
	header('location: session.php');
}



include("templates/footer.inc.php")
?>