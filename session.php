<?php
session_start();
require_once("inc/config.inc.php");
require_once("inc/functions.inc.php");

//Überprüfe, dass der User eingeloggt ist
//Der Aufruf von check_user() muss in alle internen Seiten eingebaut sein
$user = check_user();
check_admin();

include("templates/header.inc.php");
include("templates/nav.inc.php");
include("templates/admin-nav.inc.php");
?>

<div>
	<div class="center spacer">
		<form class="form" action="handing-out.php" method="post">
			<input type="text" name="searchID" placeholder="Scan-input here" autofocus>
		</form>
	</div>
	<div class="sizer spacer">
		<span class="subtitle2">Offene Bestellungen</span><br><br>
		<table style="width: 100%;">
			<tr>
				<th>Name</th>
				<th>Artikel</th>
				<th>Menge</th>
				<th>bestellt am</th>
			</tr>
			<?php
			$statement = $pdo->prepare("SELECT orders.uid, orders.created_at, order_items.*, users.first_name, users.last_name, products.productName FROM order_items LEFT JOIN orders ON order_items.oid = orders.oid LEFT JOIN users ON orders.uid = users.uid LEFT JOIN products ON order_items.pid = products.pid WHERE orders.delivered = 0");
			$result = $statement->execute();
			$uid = "";
			$count = 0;

			while ($row = $statement->fetch()) {
				$switch = true;
				if ($count == 1) {
					echo "</tr>";
				}

				$count = 1;

				if ($uid != $row['uid']) {
					$uid = $row['uid'];
					$switch = false;

					echo "<tr>";
					echo "<td>". $row['first_name'] ." ". $row['last_name'] ."</td>";
				}

				if ($switch) {
					echo "<td></td>";
				}

				echo "<td>". $row['productName'] ."</td>";
				echo "<td>". $row['quantity'] ."</td>";
				echo "<td>". $row['created_at']. "</td>";

			}

			echo "</tr>";
			?>
		</table>
	</div>
</div>

<?php
include("templates/footer.inc.php")
?>