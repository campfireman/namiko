<?php
session_start();
require_once("inc/config.inc.php");
require_once("inc/functions.inc.php");

//Überprüfe, dass der User eingeloggt ist
//Der Aufruf von check_user() muss in alle internen Seiten eingebaut sein
$user = check_user();
check_consul();

include("templates/header.inc.php");
include("templates/nav.inc.php");
include("templates/admin-nav.inc.php");
?>

<div class="sizer spacer">

<?php
$count = 0;

//Query joins orders and order_items & retrieves productName and price via pid
$statement = $pdo->prepare("SELECT orders.*, users.first_name, users.last_name FROM orders LEFT JOIN users ON orders.uid = users.uid WHERE delivered = 1 ORDER BY orders.created_at DESC");
$result = $statement->execute();
//print_r($arr = $statement->errorInfo());

while ($row = $statement->fetch()) {
	$count++;
	$oid = $row['oid'];
	$first_name = $row['first_name'];
	$last_name = $row['last_name'];
	$date = new DateTime($row['created_at']);

	if ($count == 1) {
			echo '<div class="row spacer3">';
	}

	echo '<div class="col-md-6">';
	echo '<div class="subtitle2"><span>Bestellung #'. $oid .'</span></div>';
	echo '<div class="inline">'. $first_name . ' ' . $last_name . '</div>';
	echo '<div class="subtitle3 inline" style="float: right"><span>'. $date->format("d.m.Y H:i:s") .'</span></div><br><br>';
	echo '<table class="max"><tr style="text-align: left;"><th>Artikel</th><th>Preis KG/L</th><th>Menge</th><th>&#931;</th></tr>';
	
	$grandtotal = 0;

	$statement2 = $pdo->prepare("SELECT order_items.pid, products.productName,  products.price_KG_L, order_items.quantity, order_items.total FROM order_items LEFT JOIN products ON order_items.pid = products.pid WHERE order_items.oid = '$oid'");
	$result2 = $statement2->execute();

	while ($row2 = $statement2->fetch()) {
		$pid = $row2['pid'];
		$productName = $row2['productName'];
		$price_KG_L = $row2['price_KG_L'];
		$quantity = $row2['quantity'];
		$delivered = $row2['delivered'];
		$total = $row2['total'];
		$total = ($quantity * $price_KG_L);
		$grandtotal += $total;

		echo '<tr>';
		echo '<td>'. $productName .'</td>';
		echo '<td>'. $currency. sprintf("%01.2f", $price_KG_L) .'</td>';
		echo '<td>'. $quantity .'</td><td>'.$currency. sprintf("%01.2f", $total). '</td>';
		echo '</tr>';
	}

	echo '<tr><td></td><td></td><td></td><td class="emph">'. $currency.sprintf("%01.2f",$grandtotal) .'</td></table>';
	echo '</div>';
	if ($count == 2) {
		echo '</div>';
		$count = 0;
	}
		
}	

if ($count == 1) { //closes .row if number of orders is uneven
		echo '</div>';
}

?>

</div>

<?php
include("templates/footer.inc.php")
?>
