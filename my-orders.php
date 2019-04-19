<?php
session_start();
require_once("inc/config.inc.php");
require_once("inc/functions.inc.php");

//Überprüfe, dass der User eingeloggt ist
//Der Aufruf von check_user() muss in alle internen Seiten eingebaut sein
$user = check_user();

include("templates/header.inc.php");
include("templates/nav.inc.php");
include("templates/orders-nav.inc.php");
?>

<h3 class="header">Bestellungen</h3>
<div class="sizer spacer">


<?php
$uid = $user['uid'];
$count = 0;

//Query joins orders and order_items & retrieves productName and price via pid
$statement = $pdo->prepare("SELECT orders.* FROM orders WHERE uid = '$uid' ORDER BY orders.oid DESC");
$result = $statement->execute();
//print_r($arr = $statement->errorInfo());

while ($row = $statement->fetch()) {
	$count++;
	$oid = $row['oid'];
	$date = new DateTime($row['created_at']);

	if ($count == 1) {
			echo '<div class="row spacer3">';
	}

	echo '<div class="col-md-6">';
	echo '<div class="subtitle2 inline"><span>Bestellung #'. $oid .'</span></div>';
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
	if ($row['delivered'] == 0) {
		echo '<button class="picked-up clean-btn red" oid="'. $row['oid'] .'">nicht abgeholt <i class="fa fa-times" aria-hidden="true"></i></button><br><br>';
	} else {
		echo '<button class="clean-btn green">abgeholt <i class="fa fa-check" aria-hidden="true"></i></button><br><br>';
	}
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
</div>
<?php 
include("templates/footer.inc.php")
?>
<script type="text/javascript">
	
	$(".picked-up").on("click", function(e){ 
		$(this).prop("disabled", true);
		var oid = $(this).attr('oid');
		var ref = $(this);

		e.preventDefault();
		$.ajax({
			type: "POST",
			url: 'session_process.php',
			dataType:"json",
			data: {"oid": oid, "mark-delivered" : 1} // serializes the form's elements.
		}).done(function(data){
			if (data.error == 1) {
				alert(data.text);
			} else {
				ref.removeClass("picked-up").removeClass('red').addClass('green').html('abgeholt <i class="fa fa-check" aria-hidden="true"></i>');
			}
		});
	});
</script>