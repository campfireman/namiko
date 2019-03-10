<?php
require_once("inc/config.inc.php");
require_once("inc/functions.inc.php");
ini_set('dislay_errors', 1);

if (isset($_GET['remove-order'])) {
	$oi_id = $_GET['oi_id'];
	$oid = $_GET['oid'];

	$statement = $pdo->prepare("SELECT * FROM order_items WHERE oid = '$oid'");
	$result = $statement->execute();

	if ($result) {
		$count = $statement->rowCount();
		
		$statement = $pdo->prepare("DELETE FROM order_items WHERE oi_id = '$oi_id'");
		$result = $statement->execute();

		if ($count == 1) {
			$statement = $pdo->prepare("DELETE FROM orders WHERE oid = '$oid'");
			$result = $statement->execute();
		}

		if ($result) {
			res(0, "Erfolgreich");
		} else {
			res(1, json_encode($statement->errorInfo()));
		}
	}
}

if (isset($_POST['mark-delivered'])) {
	$oid = $_POST['oid'];

	$statement = $pdo->prepare("SELECT delivered FROM orders WHERE oid = '$oid'");
	$result = $statement->execute();

	if (!$result) {
		res(1, "Es gab einen Fehler");
	} else {
		$row = $statement->fetch();
		if ($row['delivered'] == 1) {
			res(1, "Bereits markiert.");
		}
	}

	$statement = $pdo->prepare("UPDATE orders SET delivered = 1 WHERE oid = '$oid'");
	$result = $statement->execute();

	if ($result) {
		$statement = $pdo->prepare("SELECT * FROM order_items WHERE oid = '$oid'");
		$result = $statement->execute();
		if ($result) {
			while ($row = $statement->fetch()) {
				$quantity = $row['quantity'];
				$pid = $row['pid'];

				$statement2 = $pdo->prepare("UPDATE inventory_items SET quantity_KG_L = quantity_KG_L - '$quantity' WHERE pid = '$pid'");
				$result2 = $statement2->execute();

				if (!$result2) {
					res(1, json_encode($statement2->errorInfo()));
				}
			}
		} else {
			res(1, json_encode($statement->errorInfo()));
		}
		res(0, "Erfolgreich.");
	} else {
		res(1, json_encode($statement->errorInfo()));
	}
}
res(1, "Keine Daten übermittelt");
?>