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

<h3 class="header">Vorbestellungen</h3>
<div class="sizer spacer">


<?php
$uid = $user['uid'];
$count = 0;

$statement = $pdo->prepare("SELECT preorders.* FROM preorders WHERE uid = :uid AND oid IN (
							SELECT oid FROM preorder_items WHERE transferred = 0 GROUP BY oid)
							ORDER BY oid DESC");
$result = $statement->execute(array('uid' => $uid));


if ($statement->rowCount() > 0) {
	while ($row = $statement->fetch()) {
		$count++;
		$oid = $row['oid'];
		$date = new DateTime($row['created_at']);

		if ($count == 1) {
				echo '<div class="row spacer3">';
		}

		echo '<div class="col-md-6">';
		echo '<div class="subtitle2 inline"><span>Vorbestellung #'. $oid .'</span></div>';
		echo '<div class="subtitle3 inline" style="float: right"><span>'. $date->format("d.m.Y H:i:s") .'</span></div><br><br>';
		echo '<table class="max"><tr style="text-align: left;"><th>Artikel</th><th>Preis E</th><th>Menge</th><th>&#931;</th></tr>';
		
		$grandtotal = 0;

		$statement2 = $pdo->prepare("SELECT preorder_items.*, products.productName,  products.price_KG_L FROM preorder_items LEFT JOIN products ON preorder_items.pid = products.pid WHERE preorder_items.oid = '$oid' AND preorder_items.transferred = 0");
		$result2 = $statement2->execute();

		while ($row2 = $statement2->fetch()) {
			$pid = $row2['pid'];
			$productName = $row2['productName'];
			$price_KG_L = $row2['total'] / $row2['quantity'];
			$quantity = $row2['quantity'];
			$total = $row2['total'];
			$grandtotal += $total;

			echo '<tr>';
			echo '<td>'. $productName .'</td>';
			echo '<td>'. $currency. sprintf("%01.2f", $price_KG_L) .'</td>';
			echo '<td>'. $quantity .'</td><td>'.$currency. sprintf("%01.2f", $total). '</td>';
			echo '<td><button oi_id="'. $row2['oi_id'] .'" oid="'. $oid .'" class="remove-order empty red"><i class="fa fa-trash" aria-hidden="true"></i></button>';
			echo '</tr>';
		}

		echo '<tr><td></td><td></td><td></td><td class="emph">'. $currency.sprintf("%01.2f",$grandtotal) .'</td></table>';
		echo '</div>';
		if ($count == 2) {
			echo '</div>';
			$count = 0;
		}
			
	}	

	if ($count == 1) { //closes .row if number of preorders is uneven
			echo '</div>';
	}
} else {
	echo "Keine offenen Vorbestellungen";
}
?>
</div>

<script type="text/javascript">
	$('document').ready(function () {
		$('.remove-order').on("click", function(e) {
			$(this).prop("disabled", true);
			e.preventDefault();

			var oi_id = $(this).attr("oi_id");
			var oid = $(this).attr("oid");
			
		    $(this).closest('tr').fadeOut();
		    $.getJSON( "session_process.php", {"remove-order":1, "oid": oid, "oi_id" : oi_id, "preorder" : 1}).done(function(data){ 
		    	if (data.error == 1) alert(data.text);
		    });
		})
	})
</script>

<?php 
include("templates/footer.inc.php")
?>