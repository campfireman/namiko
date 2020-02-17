<?php
session_start();
require_once("inc/config.inc.php");
require_once("inc/functions.inc.php");

//ini_set('display_errors', 1);

//Überprüfe, dass der User eingeloggt ist
//Der Aufruf von check_user() muss in alle internen Seiten eingebaut sein
$user = check_user();
check_admin();

include("templates/header.inc.php");
include("templates/nav.inc.php");
include("templates/admin-nav.inc.php");

if (isset($_POST['create-protocol'])) {

		//The name of the CSV file that will be downloaded by the user.
		$fileName = date("c"). '_inventur_protokoll.csv';
		 
		$statement = $pdo->prepare("
			SELECT producers.producerName, p.pid, p.productName, p.price_KG_L, p.unit_size, p.unit_tag, i.quantity_KG_L AS maximum  FROM products AS p
			LEFT JOIN inventory_items AS i ON i.pid = p.pid
			LEFT JOIN producers ON p.producer = producers.pro_id
			LEFT JOIN (
					SELECT oi.pid, SUM(oi.quantity) AS sum
					FROM order_items AS oi
					WHERE delivered = 0
					GROUP BY oi.pid
				) AS orders ON orders.pid = p.pid
			WHERE i.quantity_KG_L > 0
			ORDER BY p.producer, p.productName
		");
		$result = $statement->execute();

		//Set the Content-Type and Content-Disposition headers.
		header('Content-Type: application/excel; charset=utf-8');
		header('Content-Disposition: attachment; filename="' . $fileName . '"');
		header('Accept-Language: en-US');

		while(ob_get_level()) {
			ob_end_clean();
		}
		 
		//Open up a PHP output stream using the function fopen.
		$fp = fopen('php://output', 'w');

		$header = ["Hersteller", "ID", "Produktname", "Preis", "Einheitsgroesse", "Einheit", "Soll-bestand (E)", "Ist-Bestand (E)", "Differenz Soll-Ist", "Wert in EUR"];
		fputcsv($fp, $header);
		$count = 2;
		 
		//Loop through the array containing our CSV data.
		while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
		    //fputcsv formats the array into a CSV format.
		    //It then writes the result to our output stream.
		    array_push($row, 0, "=(G". $count ." - H". $count .")", "=(D". $count . " * H". $count .")");
		    fputcsv($fp, $row);
		    $count++;
		}
		$end_line = [];

		for ($i = 1; $i < sizeof($header); $i++) {
			array_push($end_line, "");
		}
		$sum = "=SUM(L2:L". ($count -1) .")";
		array_push($end_line, $sum);
		fputcsv($fp, $end_line);
		 
		//Close the file handle.
		fclose($fp);
		exit();
	}

// Get all Session dates and calculate last ordering time point
$curr = date('Y-m-d H:i:s');
$statement = $pdo->prepare("SELECT start FROM events WHERE type = 1 AND start > '$curr' ORDER BY start ASC");
$result = $statement->execute();
$timeframeOut = '';

while ($row = $statement->fetch()) {
	$nextSession = date_create_from_format('Y-m-d H:i:s', $row['start']);
	$calc = clone $nextSession;

	$last = date_sub($calc, date_interval_create_from_date_string($lastPossibleOrder));

	$timeframeOut .= '<option value="'. $last->format('Y-m-d H:i:s') .'"';

	if ($last->format('Y-m-d H:i:s') == $_POST['timeframe']) {
		$timeframeOut .= 'selected="selected"';
	}

	$timeframeOut .= '>'. $last->format('d.m.Y H:i:s') .' ('. $nextSession->format('d.m.Y H:i:s') .')</option>';
}

$timeToggle = '';

if (isset($_POST['toggleTimeframe'])) {
	$timeframe = $_POST['timeframe'];

	if ($timeframe == 0) {

	} else {
		$timeToggle = "AND (orders.created_at < '". $timeframe ."')";
	}
}



// manually update the current stock
if (isset($_POST['update'])) {
	$ii_id = $_POST['ii_id'];
	$quantity_KG_L = $_POST['quantity_KG_L'];
	$last_edited_by = $user['uid'];

	$statement = $pdo->prepare("UPDATE inventory_items SET quantity_KG_L = '$quantity_KG_L', last_edited_by = '$last_edited_by' WHERE ii_id = '$ii_id'");
	$result = $statement->execute();

	if ($result) {
		header("Location: " . $_SERVER['PHP_SELF']);
	}

	if (!$result) {
		echo 'fehler';
	}
}

// Fill Inventory table with inventory db with join to product data
			$statement = $pdo->prepare("
				SELECT inventory_items.*, products.*, users.first_name, users.last_name 
				FROM inventory_items LEFT JOIN products ON inventory_items.pid = products.pid 
				LEFT JOIN users ON inventory_items.last_edited_by = users.uid 
				ORDER BY productName");
			$result = $statement->execute();

			
			$checker = array(); // array for checking the items in currently in stock for the add feature
			$recommendations = array(); // array for saving all items with current deficit

			while ($row = $statement->fetch()) {
				$pid = $row['pid'];
				$quantity_KG_L = $row['quantity_KG_L'];
				$unit_tag = $row['unit_tag'];
				$unit_size = $row['unit_size'];
				$producer = $row['producer'];
				$is_storage_item = $row['is_storage_item'];
				$is_in_catalogue = $row['category'] > 1 ? true : false; 
				$container = $row['container'];
				$quantityOrdered = $db->getTotalOrders($pid);
				$quantityDelivery = 0;
				$intventory_in_unit = $quantity_KG_L * $unit_size;
				
				// calculate deficit
				$realStock = ($quantity_KG_L - $quantityOrdered);

				// check for undelivered stock refills 
				$statement3 = $pdo->prepare("SELECT order_total_items.quantityContainer, order_total_items.container, order_total.delivered FROM order_total_items LEFT JOIN order_total ON order_total_items.tid = order_total.tid WHERE order_total_items.pid = '$pid' AND order_total.delivered = 0");
				$result3 = $statement3->execute();

				while ($row3 = $statement3->fetch()) {

					$quantityDelivery = ($row3['quantityContainer'] * $row3['container']);
				}
				
				// preorders
				$preorders = $db->getPreorders($pid);

				// calculate actual deficit with pending stock refills and saving items with negative deficit
				// colored output based on amount
				$sum = ($realStock + $quantityDelivery - $preorders);

				if ($is_storage_item == 1 && $is_in_catalogue) {
					$recommendations[$producer][] = array('pid' => $pid, 'deficit' => $sum);
				}

				if ($sum < 0) {
					if ($is_storage_item == 0 && $is_in_catalogue) {
 						$div = intdiv(abs($sum), $container);
						if ($div > 0) {
							$recommendations[$producer][] = array('pid' => $pid, 'deficit' => $sum);
						}
 					}
				}
				

				// add product to table if it is in the inventory or has been ordered
				if ($quantity_KG_L || $quantityOrdered > 0) {
					// save item
					array_push($checker, $pid);
				}
			}

			// create <select> list for form for adding all products that are currently not in the table
			$statement = $pdo->prepare("SELECT pid, productName, container FROM products ORDER BY productName");
			$result = $statement->execute();

			$optionList = '';

			while ($row = $statement->fetch()) {
				$pid = $row['pid'];
				if (!in_array($pid, $checker)) {
					$optionList .= '<option value="'. $pid .'">'. $row['productName'] .' ('. $row['container'] .' E Gebinde)</option>';
				}
			}

?>

<div class="sizer spacer">
	<form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']) ?>">
		<button class="clean-btn blue" name="create-protocol" type="submit">Inventurliste erstellen</button>
	</form><br><br>
	<span class="subtitle2">Inventar</span><br><br>
	<form class="form" method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']) ?>">
		<select name="timeframe">
			<option value="0">Alle</option>
			<optgroup label="Zeitpunkte">
			<?php echo $timeframeOut; ?>
			</optgroup>
		</select>
		<button type="submit" class="clean-btn blue" name="toggleTimeframe">Aktualisieren <i class="fa fa-refresh" aria-hidden="true"></i></button>
	</form><br>
			<h4 class="white">Filter <i class="fa fa-filter" aria-hidden="true"></i></h4>
		<div class="indent row">
			<form class="spacer2 filter">
				<div class="indent spacer2 col-sm-6">
					<span class="subtitle">Kategorien</span>
					<div><label><input id="all" class="category" type="checkbox" name="category[]" value="0" id="all" checked> alle</label></div>
					<hr class="separator">
					<div>
						<?php
						$statement = $pdo->prepare("SELECT * FROM categories ORDER BY cid");
						$result = $statement->execute();

						if ($statement->rowCount() > 0) {
							while ($row = $statement->fetch()) {
								echo '<div><label><input type="checkbox" name="category[]" class="category other" value="'. $row['cid'] .'"> '. $row['category_name'] .'</label></div>';
							}
						} else {
							echo 'Keine Kategorien gefunden.';
						}
						?>
					</div>
				</div>
				<div class="indent spacer2 col-sm-6">
					<span class="subtitle">Lieferant</span>
					<div><label><input id="allprod" class="producer" type="checkbox" name="producer[]" value="0" id="all" checked> alle</label></div>
					<hr class="separator">
					<?php
					$statement = $pdo->prepare("SELECT * FROM producers ORDER BY pro_id");
					$result = $statement->execute();

					if ($statement->rowCount() > 0) {
						while ($row = $statement->fetch()) {
							echo '<div><label><input type="checkbox" name="producer[]" class="otherprod" value="'. $row['pro_id'] .'" unchecked> '. $row['producerName'] .'</label></div>';
						}
					} else {
						echo 'Keine Orte gefunden.';
					}
					?>
				</div>
				<br><button type="submit" name="filterSubmit" class="empty blue">Aktualisieren <i class="fa fa-repeat" aria-hidden="true"></i></button>
			</form>
		</div>
	<div class="center-vertical">
				<div class="center">
				<div id="loadScreen" class="loader"></div>
				</div>
			</div>
	<div id="inventory-table"></div>
	
	<?php
	// function for adding items to the inventory
	if (isset($_POST['addItem'])) {
		$pid = $_POST['pid'];
		$quantity_KG_L = $_POST['quantity_KG_L'];
		$last_edited_by = $user['uid'];

		$statement = $pdo->prepare("SELECT * FROM inventory_items WHERE pid = :pid");
		$result2 = $statement->execute(array('pid' => $pid));
		$check = $statement->fetch();

		// product already in the table -> update
		if ($check !== false) {
			$statement = $pdo->prepare("UPDATE inventory_items SET quantity_KG_L = '$quantity_KG_L' WHERE pid ='$pid'");
			$result = $statement->execute();

			if ($result) {
			$_SESSION['notification'] = true;
			$_SESSION['notificationmsg'] = 'Produkt wurde erfolgreich aktualisiert.';
			header('location: '. $_SERVER['PHP_SELF']);
			
			} else {
				$_SESSION['notification'] = true;
				$_SESSION['notificationmsg'] = 'Das Produkt konnte nicht aktualisiert werden.';
				header('location: '. $_SERVER['PHP_SELF']);
			}

		// product not in the table -> insert
		} else {
			$statement = $pdo->prepare("INSERT INTO inventory_items (pid, quantity_KG_L, last_edited_by) VALUES (:pid, :quantity_KG_L, :last_edited_by)");
			$result = $statement->execute(array('pid' => $pid, 'quantity_KG_L' => $quantity_KG_L, 'last_edited_by' => $last_edited_by));

			if ($result) {
			$_SESSION['notification'] = true;
			$_SESSION['notificationmsg'] = 'Produkt wurde dem Inventar erfolgreich hinzugefügt.';
			header('location: '. $_SERVER['PHP_SELF']);

			} else {
				$_SESSION['notification'] = true;
				$_SESSION['notificationmsg'] = 'Das Produkt konnte nicht dem Inventar hinzugefügt werden';
				header('location: '. $_SERVER['PHP_SELF']);
			}
		}

		
		
	}

	// adding any product to order_total
	$statement = $pdo->prepare("SELECT producers.pro_id, producers.producerName, products.pid, products.productName, products.container, products.priceContainer FROM producers LEFT JOIN products ON producers.pro_id = products.producer ORDER BY producers.producerName, products.productName");
	$result = $statement->execute();
	$pro_id = '';
	$orderTotalAdd = '';
	$first = true;

	while ($row = $statement->fetch()) {

			// sorting and dividing by producer
			if ($pro_id != $row['pro_id']) {
				if ($first) {
					$first = false;
				} else {
					$orderTotalAdd .= '</optgroup>';
				}

				$pro_id = $row['pro_id'];

				$orderTotalAdd .= '<optgroup label="'. $row['producerName'] .'">';
			}

			$orderTotalAdd .= '<option value="'. $row['pid'] .'">'. $row['productName'] .' ('. $currency . sprintf('%01.2f', $row['priceContainer']) .' / '. $row['container']*1 .'E)</option>';

	}

	$orderTotalAdd .= '</optgroup>';

	// checking out order_total items
	if (isset($_POST['checkOut'])) {
		if(isset($_SESSION["total"]) && count($_SESSION["total"]) > 0) { //if we have session variable

			$issued_by = $user['uid'];

	        foreach ($_SESSION['total'] as $pro_id => $order_item) { // create for each producer separate order
	        	
	        	$producer = $pro_id;

	        	$statement = $pdo->prepare("INSERT INTO order_total (producer, issued_by) VALUES (:producer, :issued_by)");
	        	$result = $statement->execute(array('producer' => $producer, 'issued_by' => $issued_by));

	        	// find the identifier (tid) of the latest order
	        	$statement = $pdo->prepare("SELECT tid FROM order_total WHERE producer = '$producer' AND issued_by = '$issued_by' AND ordered_at = (SELECT MAX(ordered_at) FROM order_total)");
	        	$result = $statement->execute();
	        	$result = $statement->fetch(); 


	        	foreach($order_item as $product) { //loop though items and insert into order_total_items
	        		$tid = $result['tid'];
	        		$pid = $product['pid'];
	        		$container = $product['container'];
	        		$quantityContainer = $product['quantityContainer'];
	        		$price = $product['priceContainer'];
	        		$total = ($price * $quantityContainer);

	        		$statement2 = $pdo->prepare("INSERT INTO order_total_items (tid, pid, container, quantityContainer, total) VALUES (:tid, :pid, :container, :quantityContainer, :total)");
	        		$result2 = $statement2->execute(array('tid' => $tid, 'pid' => $pid, 'container' => $container, 'quantityContainer' => $quantityContainer, 'total' => $total));

	        	}
	        }

	        if ($result && $result2) {
	        	unset($_SESSION['total']); // empty cart

	        	$_SESSION['notification'] = true;
				$_SESSION['notificationmsg'] = 'Die Bestellung wurde erfolgreich verbucht.';
				header('location: //'. $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) .'/order_total.php');
	        } else {
	        	$_SESSION['notification'] = true;
				$_SESSION['notificationmsg'] = 'Es ist ein Fehler aufgetreten.';
				header('location: '. $_SERVER['PHP_SELF']);
	        }

    	} else {
    		$_SESSION['notification'] = true;
			$_SESSION['notificationmsg'] = 'Es gibt keine Produkte im Warenkorb';
			header('location: '. $_SERVER['PHP_SELF']);
    	}
	}

	// recommend ordering items when there is negative deficit, ordered by producers
	$statement = $pdo->prepare("SELECT * FROM producers");
	$result = $statement->execute();

	$recommendationsOut = '';
	if ($statement->rowCount() > 0) {
		while ($row = $statement->fetch()) {
			$pro_id = $row['pro_id'];
			$grandtotal = 0;
			$producerName = $row['producerName'];

			if (array_key_exists($pro_id, $recommendations)) { // if producer in array
				$category = $recommendations[$pro_id];

				// preparing individual tables for output
				$recommendationsOut .= '<span class="subtitle3 spacer">'. $producerName .'</span>
										<form class="order-total">
										<table class="table panel panel-default" style="max-width: 820px">
											<thead>
												<tr>
												<th>Produktname</th>
												<th>Produkt ID</th>
												<th>Lagerware</th>
												<th>Defizit Einheiten</th>
												<th>Gebinde Einheiten</th>
												<th>empf. Menge</th>
												<th>Preis Gebinde</th>
												<th>&#931;</th>
												<th></th>
												</tr>
											</thead>';
				// iterating the recommendations array for this specific producer
				foreach ($category as $product) {
					$pid = $product['pid'];
					$deficit = abs($product['deficit']);
					$statement2 = $pdo->prepare("SELECT * FROM products WHERE pid = '$pid'");
					$result2 = $statement2->execute();
					$productData = $statement2->fetch();

					$is_storage_item = $productData['is_storage_item'];
					$priceContainer = $productData['priceContainer'];
					$container = $productData['container'];

					if ($is_storage_item == 1) {
						if ($product['deficit'] < 0) {
							$quantityContainer = ceil($deficit / $container);
						} else {
							$quantityContainer = 1;
						}
					} else {
						$div = intdiv($deficit, $container);
						if ($div > 0) {
							$quantityContainer = $div;
						} else {
							continue;
						}
					}

					$total = ($priceContainer * $quantityContainer); // calculating price
					$grandtotal += $total; // calculating total price

					if ($is_storage_item == 1) {
						$is_storage_item_out = '<span> <i class="fa fa-database" aria-hidden="true"></i></span>';
					} else {
						$is_storage_item_out = '';
					}
					// adding item to table
					$recommendationsOut .= '<tr>
											<td>'. $productData['productName'] .'</td>
											<td><input type="hidden" name="pid[]" value="'. $pid .'">'. $pid .'</td>
											<td>'. $is_storage_item_out .'</td>
											<td>'. $product['deficit'] .'</td>
											<td>'. $productData['container'] .'</td>
											<td><input class="width100" type="number" name="quantityContainer[]" value="'. $quantityContainer . '"></td>
											<td>'. $currency.sprintf("%01.2f", $priceContainer) .'</td>
											<td>'. $currency.sprintf("%01.2f", $total) .'</td>
											<td><button class="rm-recommendation remove-item empty"><i class="fa fa-trash-o" aria-hidden="true"></i></button></td>
											</tr>';
				}

				// closing table
				$recommendationsOut .= '<tr>
											<td></td>
											<td></td>
											<td></td>
											<td></td>
											<td></td>
											<td></td>
											<td></td>
											<td><span class="emph">'.$currency.sprintf("%01.2f", $grandtotal). '</span></td>
											<td></td>
										</tr>
										</table>
										<button type="submit" name="orderRecommendation" class="left clean-btn blue">Hinzufügen <i class="fa fa-plus" aria-hidden="true"></i></button><br>
										</form>
										<br><br>';
			} 
		}
	}
	?>
	<form class="form" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']) ?>" method="post">
		<select class="smallForm" name="pid" required>
		<?php echo $optionList ?>
		</select>
		<input type="number" name="quantity_KG_L" placeholder="Menge in E" class="smallForm" required>
		<button type="submit" name="addItem" class="clean-btn blue">Hinzufügen <i class="fa fa-plus" aria-hidden="true"></i></button>
	</form>

	<div class="spacer">
		<span class="subtitle2">Nachschub bestellen</span><br><br>
		<span class="subtitle">Aktuelle Bestellung</span><br>
		<div id="order-total-results"></div>
	</div><br>

	<div class="spacer">
		<span class="subtitle">Artikel hinzufügen</span>
		<form class="form order-total-add">
			<select name="pid[]" class="smallForm" required>
			<option value="-1">- Produkt wählen -</option>
			<?php print_r($orderTotalAdd) ?>
			</select>
			<input type="number" name="quantityContainer" placeholder="Menge Gebinde" class="smallForm" required> 
			<button type="submit" class="clean-btn blue">Hinzufügen <i class="fa fa-plus" aria-hidden="true"></i></button>
		</form>
	</div>

	<div class="spacer full">
		<span class="subtitle2">Bestellempfehlungen</span><br><br>
		
		<?php echo $recommendationsOut ?>
	</div>
<div>
<button id="save-btn" class="no-display green empty">
	<i class="fa fa-floppy-o" aria-hidden="true"></i>
</button>
</div>

</div>

<script type="text/javascript">
var updates ={};

function loader (tag) {
		$(tag).addClass('loader');
	}

	function removeLoader (tag) {
		$(tag).removeClass('loader');
	}
	
function loadCatalogue(form) {
	var data = $(form).serialize();
	$('inventory-table').html('');
	$.ajax({
		url: 'inventory_handler.php',
		type: 'POST',
		dataType: 'json',
		data: data
	}).done(function(data) {
		$('#inventory-table').html(data.text);
		$(".inventory-t").fixMe();
		removeLoader('#loadScreen');
		

		$('.product').on('input', function(e) {
			e.preventDefault();
			//get select row and table
			var row = $(this);

			//get data from hidden input fields
			var ii_id = parseFloat(row.find('input[name="ii_id"]').val());
			var quantity_KG_L = row.find('input[name="quantity_KG_L"]').val();

			var values = {
				quantity_KG_L: quantity_KG_L
			};

			//save updated values to object
			if (updates.hasOwnProperty(ii_id)) {
				updates[ii_id] = values;
			} else {
				updates = Object.assign({[ii_id]: values}, updates)
			}

			//display save button
			if ($('#save-btn').hasClass('no-display')) {
				$('#save-btn').removeClass('no-display');
			}

		});

		$('#save-btn').on('click', function(e) {
				e.preventDefault();
				$.ajax({
					url: "inventory_handler.php",
					type: "POST",
					dataType: "JSON",
					data: {"update-inventory": 1, values: updates}
				}).done(function(data) {
					if (data.error == 1) {
						alert(data.text);
					} else {
						submit = true;
						location.reload();
					}
				})
			});

	});
}

$(document).ready(function() {
	loadCatalogue('.filter');

	$('.filter').submit(function(e) {
		loader('#loadScreen');
		loadCatalogue('.filter');
		e.preventDefault();
	});

	$('#search-items').submit(function(e) {
		loader('#loadScreen');
		loadCatalogue('.filter');
		e.preventDefault();
	});

	$('#all').click(function() {
		if($('#all').prop('checked')) {
			$('.other').prop("checked", false);

		}
	});
	$('.other').click(function() {
		if(this.checked) {
			$('#all').prop('checked', false);
		}
	});

	$('#allprod').click(function() {
		if($('#allprod').prop('checked')) {
			$('.otherprod').prop("checked", false);

		}
	});
	$('.otherprod').click(function() {
		if(this.checked) {
			$('#allprod').prop('checked', false);
		}
	});
	// sending recommendations to order_total cart
	$('.order-total').submit(function (e) {
		var form_data = $(this).serialize();
		var button_content = $(this).find('button[type=submit]');
		button_content.html('...'); 
		e.preventDefault();
		console.log(form_data);
		$.ajax({
			url: 'order_total_process.php',
			type: 'POST',
			dataType: 'json',
			data: form_data
		}).done(function(data) {
			if (data.error == 1) {
				alert(data.text);
			} else {
				button_content.removeClass('blue').addClass('green').html('<span>Hinzugefügt <i class="fa fa-check" aria-hidden="true"></span>');
				$("#order-total-results").html('').load( "order_total_process.php", {"load_cart":"1"});
			}
			
		}); 
	});

	$('.order-total-add').submit(function(e) {
		var form_data = $(this).serialize();
		console.log(form_data);
		$.ajax({
						url: 'order_total_process.php',
						type: 'POST',
						dataType: 'json',
						data: form_data
		}).done(function(data) {
			$("#order-total-results").html('').load( "order_total_process.php", {"load_cart":"1"});			
		})

		e.preventDefault();
	});

	$("#order-total-results").on('click', 'a.remove-item', function(e) {
		$(this).prop("disabled", true);
	    e.preventDefault(); 
	    var pid = $(this).attr("data-pid"); //get product code
	    var pro_id = $(this).attr("data-pro_id");
	    var item_total = $(this).closest('tr').find('[name=item_total]').val(); // get value of item
	    var total = $(this).closest('table').find('[name=total]'); // get element of order total
	    var total_val = total.val(); // get value of order total
	    $(this).closest('tr').fadeOut(); // fade out the table row containing the item
	    $.getJSON( "order_total_process.php", {"remove_pid":pid, "pro_id": pro_id}).done(function(data){ 
	        total_val = total_val - item_total; // subtract deleted product from order total_val
	        total.val(total_val); // save new total in hidden input field
	        total.closest('tr').find('#total').html('').html(total_val.toFixed(2)); // delete old total & insert new total
	    });
	});

	$('.rm-recommendation').on('click', function(e) {
		e.preventDefault();
		$(this).closest('tr').fadeOut();
		$(this).closest('tr').empty();

	});

	$('#save-btn').on('click', function(e) {
		e.preventDefault();
		$.ajax({
			url: "inventory_handler.php",
			type: "POST",
			dataType: "JSON",
			data: {"update-catalogue": 1, values: updates}
		}).done(function(data) {
			if (data.error == 1) {
				alert(data.text);
			} else {
				submit = true;
				location.reload();
			}
		})
	});
})
</script>

<script type="text/javascript">
	(function () {
	$("#order-total-results").load( "order_total_process.php", {"load_cart":"1"});
	})();
</script>

<?php 
include("templates/footer.inc.php")
?>