<?php
session_start();
require_once "inc/config.inc.php";
require_once "inc/functions.inc.php";

//Überprüfe, dass der User eingeloggt ist
//Der Aufruf von check_user() muss in alle internen Seiten eingebaut sein
$user = check_user();
check_admin();

include "templates/header.inc.php";
include "templates/nav.inc.php";
include "templates/admin-nav.inc.php";
?>

<div>
	<div class="sizer spacer">
		<span class="subtitle2">Offene Bestellungen</span><br><br>
		<table style="width: 100%;" class="table panel panel-default">
			<tr>
				<th>Name</th>
				<th>Bestellnr.</th>
				<th>Abgeh.</th>
				<th>Artikel</th>
				<th>Menge</th>
				<th>bestellt am</th>
				<th></th>
			</tr>
			<?php
$statement = $pdo->prepare("SELECT order_items.oi_id, orders.uid, orders.oid, orders.created_at, order_items.*, users.first_name, users.last_name, products.productName FROM order_items LEFT JOIN orders ON order_items.oid = orders.oid LEFT JOIN users ON orders.uid = users.uid LEFT JOIN products ON order_items.pid = products.pid WHERE order_items.delivered = 0");
$result = $statement->execute();

$statement = $pdo->prepare("SELECT orders.* FROM orders ORDER BY created_at");
$result = $statement->execute();
$orders = $statement->fetchAll();

if ($result) {
    foreach ($orders as $order) {
        $oid = $order['oid'];
        $created_at = new DateTime($order['created_at']);
        $uid = $order['uid'];
        $first = true;

        $statement = $pdo->prepare("SELECT order_items.*, users.first_name, users.last_name, products.productName FROM order_items LEFT JOIN users ON '$uid' = users.uid LEFT JOIN products ON order_items.pid = products.pid WHERE oid = '$oid' AND delivered = 0");
        $result = $statement->execute();

        if ($result) {
            while ($row = $statement->fetch()) {
                echo "<tr>";

                if ($first) {
                    $first = false;
                    echo "<td>" . $row['first_name'] . " " . $row['last_name'] . "</td>";
                    echo "<td>" . $oid . "</td>";

                } else {
                    echo "<td></td><td></td>";
                }
                echo '<td><button oi_id="' . $row['oi_id'] . '" class="mark-delivered red"><i class="fa fa-times" aria-hidden="true"></i></button></td>';
                echo "<td>" . $row['productName'] . "</td>";
                echo "<td>" . $row['quantity'] . "</td>";
                echo "<td>" . $created_at->format('d.m.Y H:i:s') . "</td>";
                echo '<td><button oi_id="' . $row['oi_id'] . '" oid="' . $oid . '" class="remove-order red"><i class="fa fa-trash" aria-hidden="true"></i></button>';
                echo "</tr>";
            }
        } else {
            print_r($statement->errorInfo());
        }
    }
} else {
    print_r($statement->errorInfo());
}
?>
		</table>
	</div>
</div>

<script type="text/javascript">
	$('document').ready(function () {
		$('.remove-order').on("click", function(e) {
			$(this).prop("disabled", true);
			e.preventDefault();

			var oi_id = $(this).attr("oi_id");
			var oid = $(this).attr("oid");
		    $(this).closest('tr').fadeOut();
		    $.getJSON( "session_process.php", {"remove-order":1, "oid": oid, "oi_id" : oi_id}).done(function(data){
		    	if (data.error == 1) alert(data.text);
		    });
		})

		$(".mark-delivered").on("click", function(e){
		$(this).prop("disabled", true);
		var oi_id = $(this).attr('oi_id');
		var ref = $(this);

		e.preventDefault();
		$.ajax({
			type: "POST",
			url: 'session_process.php',
			dataType:"json",
			data: {"oi_id": oi_id, "mark-delivered" : 1} // serializes the form's elements.
		}).done(function(data){
			if (data.error == 1) {
				alert(data.text);
			} else {
				ref.removeClass("picked-up").removeClass('red').addClass('green').html('<i class="fa fa-check" aria-hidden="true"></i>');
			}
		});
	});
	})
</script>
<?php
include "templates/footer.inc.php"
?>