<?php
session_start();
require_once("inc/config.inc.php");
require_once("inc/functions.inc.php");
require_once("inc/Cart.inc.php");

//Überprüfe, dass der User eingeloggt ist
//Der Aufruf von check_user() muss in alle internen Seiten eingebaut sein
$user = check_user();

include("templates/header.inc.php");
include("templates/nav.inc.php");
include("templates/orders-nav.inc.php");

$uid = $user['uid'];
$count = 0;
$orders = '';

$statement = $pdo->prepare("SELECT orders.* FROM orders WHERE uid = '$uid' ORDER BY orders.oid DESC");
$result = $statement->execute();

while ($row = $statement->fetch()) {
	$count++;
	$oid = $row['oid'];
	$date = new DateTime($row['created_at']);

	if ($count == 1) {
			$orders .= '<div class="row spacer3">';
	}

	$orders .= '<div class="col-md-6">';
	$orders .= '<div class="subtitle2 inline"><span>Bestellung #'. $oid .'</span></div>';
	$orders .= '<div class="subtitle3 inline" style="float: right"><span>'. $date->format("d.m.Y H:i:s") .'</span></div><br><br>';
	
	$grandtotal = 0;
	$statement2 = $pdo->prepare("SELECT order_items.pid, products.*, order_items.quantity, order_items.total FROM order_items LEFT JOIN products ON order_items.pid = products.pid WHERE order_items.oid = '$oid'");
	$statement2->execute();
	
	$table = Cart::createTable($statement2->fetchAll(), $currency);
	$orders .= $table['html'];

	if ($row['delivered'] == 0) {
		$orders .= '<button class="picked-up clean-btn red" oid="'. $row['oid'] .'">nicht abgeholt <i class="fa fa-times" aria-hidden="true"></i></button><br><br>';
	} else {
		$orders .= '<button class="clean-btn green">abgeholt <i class="fa fa-check" aria-hidden="true"></i></button><br><br>';
	}
	$orders .= '</div>';
	if ($count == 2) {
		$orders .= '</div>';
		$count = 0;
	}
		
}	

if ($count == 1) { //closes .row if number of orders is uneven
	$orders .= '</div>';
}

?>

<h3 class="header">Bestellungen</h3>
<div class="sizer spacer">


<?php
	echo $orders;
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