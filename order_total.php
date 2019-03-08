<?php
header('Content-Type: text/html; charset=UTF-8');
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

if (isset($_POST['csv'])) {

	$tid = $_POST['tid'];
	$csv = array();

	$statement = $pdo->prepare("SELECT products.productName, order_total_items.container, order_total_items.quantityContainer, order_total_items.total FROM order_total_items LEFT JOIN products ON order_total_items.pid = products.pid WHERE order_total_items.tid = '$tid'");
	$result = $statement->execute();

	// loop over the rows, outputting them
	while ($row = $statement->fetch()) {
		$productName = $row['productName'];
		$container = $row['container'];
		$quantityContainer = $row['quantityContainer'];
		$total = $row['total'];
		$price = ($total / $quantityContainer);

		$line = array(utf8_decode($productName), $price, $container, $quantityContainer, $total);
		array_push($csv, $line);
	}

	while (ob_get_level()) {
		 ob_end_clean();
	}
	
	header('Content-Type: text/csv; charset=utf-8');
	header('Content-Disposition: attachment; filename=order#'. $tid .'.csv');

	// create a file pointer connected to the output stream
	$output = fopen('php://output', 'w');

	// output the column headings
	fputcsv($output, array('Artikel', 'PreisGebinde', utf8_decode('GrößeKG/L'), 'Menge', 'Summe'));

	foreach ($csv as $row) {
		fputcsv($output, $row);
	}

	fclose($output);
	
	exit();
}
?>

<div class="sizer spacer">
	<div>
		<?php
		$statement = $pdo->prepare("SELECT * FROM producers");
		$result = $statement->execute();

		while ($row = $statement->fetch()) {
			echo '<span class="subtitle2 sub" style="font-size: 18px">'. $row['producerName'] .'</span><br>';

			$pro_id = $row['pro_id'];
			$tid = '';
			$grandtotal = 0;
			$newTable = true;
			$count = 0;
			$breakCount = 0;

			$statement2 = $pdo->prepare("SELECT order_total.*, order_total_items.*, products.productName, users.first_name, users.last_name FROM order_total LEFT JOIN order_total_items ON order_total.tid = order_total_items.tid LEFT JOIN products ON order_total_items.pid = products.pid LEFT JOIN users ON order_total.issued_by = users.uid WHERE order_total.producer = '$pro_id' ORDER BY order_total.ordered_at DESC");
			$result2 = $statement2->execute();

			if ($statement2->rowCount() > 0) {
				while ($row2 = $statement2->fetch()) {
					if ($tid != $row2['tid']) {
						$breakCount ++;
						if ($breakCount == 5) {
							break;
						}

						if (!$newTable) {
							echo '<tr>';
							echo '<td></td><td></td><td></td><td></td>';
							echo '<td class="emph">'. $currency.sprintf("%01.2f", $grandtotal) .'</td>';
							echo '</tr>';
							echo '</table><br>';
							echo '<div class="right">';
									if ($delivered == 0) {
										echo '<form class="functions inline">';
										echo '<input type="hidden" name="tid" value="'. $tid .'">';
										echo '<input type="hidden" name="delivered" value="1">';
										echo '<button type="submit" name="delivered" class="clean-btn blue">geliefert <i class="fa fa-truck" aria-hidden="true"></i></button>';
										echo '</form>';
									}
									if ($paid == 0) {
										echo '<form class="functions inline">';
										echo '<input type="hidden" name="tid" value="'. $tid .'">';
										echo '<input type="hidden" name="paid" value="1">';
										echo '<button type="submit" name="paid" class="clean-btn blue leftSpace">bezahlt <i class="fa fa-money" aria-hidden="true"></i></button>';
										echo '</form>';
									}
							echo '<form action="'. $_SERVER['PHP_SELF'] .'" method="post" class="inline">';
									echo '<input type="hidden" name="tid" value="'. $tid .'">';
									echo '<button type="submit" name="csv" class="clean-btn green leftSpace">CSV <i class="fa fa-table" aria-hidden="true"></i></button>';
							echo '</form>';
							echo '</div><br>';
							echo '</div>';

							$grandtotal = 0;
						}

						if ($count == 2) {
							$count = 0;
							echo '</div><br>';
						}

						$newTable = false;
						$tid = $row2['tid'];
						$count++;
						$delivered = $row2['delivered'];
						$paid = $row2['paid'];
						$date = $date = substr($row2['ordered_at'], 8, 2) .'.'. substr($row2['ordered_at'], 5, 2) .'.'. substr($row2['ordered_at'], 0, 4);
						if ($count == 1) {
							echo '<div class="row">';
						}

						echo '<div class="col-sm-6 spacer3 order">';
						echo '<span>ID #'. $tid .'</span>';
						echo '<span class="right subtitle3">'. $row2['first_name'] .' '. $row2['last_name'] .' am '. $date .'</span><br>';
						echo '<table class="orderTable" style="min-width: 430px;"> 
								<tr style="text-align: left;">
									<th>Artikel</th>
									<th>Preis Gebinde</th>
									<th>Größe KG/L</th>
									<th>Menge</th>
									<th>&#931;</th>
								</tr>';
					}

					$total = $row2['total'];
					$quantityContainer = $row2['quantityContainer'];
					$price = ($total / $quantityContainer);
					$grandtotal += $total;

					echo '<tr>';
					echo '<td>'. $row2['productName'] .'</td>';
					echo '<td>'. $currency . sprintf('%01.2f', $price) .'</td>';
					echo '<td>'. $row2['container'] .'</td>';
					echo '<td>'. $quantityContainer .'</td>';
					echo '<td>'. $currency . sprintf('%01.2f', $total) . '</td>';
					echo '</tr>';


				}

				echo '<tr>';
				echo '<td></td><td></td><td></td><td></td>';
				echo '<td class="emph">'. $currency.sprintf("%01.2f", $grandtotal) .'</td>';
				echo '</tr>';
				echo '</table><br>';
				echo '<div class="right">';
						if ($delivered == 0) {
							echo '<form class="functions inline">';
							echo '<input type="hidden" name="tid" value="'. $tid .'">';
							echo '<input type="hidden" name="delivered" value="1">';
							echo '<button type="submit" name="delivered" class="clean-btn blue">geliefert <i class="fa fa-truck" aria-hidden="true"></i></button>';
							echo '</form>';
						}
						if ($paid == 0) {
							echo '<form class="functions inline">';
							echo '<input type="hidden" name="tid" value="'. $tid .'">';
							echo '<input type="hidden" name="paid" value="1">';
							echo '<button type="submit" name="paid" class="clean-btn blue leftSpace">bezahlt <i class="fa fa-money" aria-hidden="true"></i></button>';
							echo '</form>';
						}
				echo '<form action="'. $_SERVER['PHP_SELF'] .'" method="post" class="inline">';
						echo '<input type="hidden" name="tid" value="'. $tid .'">';
						echo '<button type="submit" name="csv" class="clean-btn green leftSpace">CSV <i class="fa fa-table" aria-hidden="true"></i></button>';
				echo '</form>';
				echo '</div><br>';
				echo '</div>';
				echo '</div><br>';
			}
		}
		?>
	</div>
</div>

<?php 
include("templates/footer.inc.php")
?>

<script type="text/javascript">
	$('.functions').submit(function(e) {
		e.preventDefault();
		var form_data = $(this).serialize();
		var btn_txt = $(this).find('button[type=submit]');

		$.ajax({
			data: form_data,
			dataType: 'json',
			type: 'POST',
			url: 'order_total_process.php'
		}).done(function(data) {

			if (data == 1) {

				if (btn_txt.attr('name') == 'delivered') {
					btn_txt.removeClass('blue').addClass('green').html('geliefert <i class="fa fa-check" aria-hidden="true">');
				}

				if (btn_txt.attr('name') == 'paid') {
					btn_txt.removeClass('blue').addClass('green').html('bezahlt <i class="fa fa-check" aria-hidden="true">');
				}
			} else if (data == 0) {
				alert('fehler');
			} else if (data == 2) {
				alert('Die Bestellung wurde bereits als geliefert markiert.');
			} else if (data == 3) {
				alert('Die Bestellung wurde bereits als bezahlt markiert.');
			}
		})
	});
</script>