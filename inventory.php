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

?>

<div class="sizer spacer">
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
	<div class="full">
		<table class="table panel panel-default" style="min-width: 620px">
			<thead>
			<tr>
				<th>Produktname</th><th>Produkt ID</th>
				<th>Lagermenge umgerechnet</th>
				<th class="width100">Lagermenge (E)</th>
				<th class="width100">bestellt (E)</th>
				<th class="width100">&#916;</th>
				<th class="width100">Nachschub</th>
				<th>vorbest.</th>
				<th class="width100">&#931;</th>
				<th>zuletzt editiert</th>
				<th></th>
			</tr>
			</thead>
			<?php
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
				$quantityOrdered = $db->getTotalOrders($pid);
				$quantityDelivery = 0;
				$intventory_in_unit = $quantity_KG_L * $unit_size;
				

				// colored output based on amount
				if ($quantityOrdered > 0) {
					$quantityOrderedOut = '<span class="red">-'. $quantityOrdered .'</span>';
				} else {
					$quantityOrderedOut = $quantityOrdered;
				}
				
				// calculate deficit
				$realStock = ($quantity_KG_L - $quantityOrdered);

				// colored output based on amount
				if ($realStock < 0) {
					$realStockOut = '<span class="red">'. $realStock .'</span>';
				} else if ($realStock > 0) {
					$realStockOut = '<span class="green">'. $realStock .'</span>';
				} else {
					$realStockOut = '<span>'. $realStock .'</span>';
				}
				
				// check for undelivered stock refills 
				$statement3 = $pdo->prepare("SELECT order_total_items.quantityContainer, order_total_items.container, order_total.delivered FROM order_total_items LEFT JOIN order_total ON order_total_items.tid = order_total.tid WHERE order_total_items.pid = '$pid' AND order_total.delivered = 0");
				$result3 = $statement3->execute();

				while ($row3 = $statement3->fetch()) {

					$quantityDelivery = ($row3['quantityContainer'] * $row3['container']);
				}
				
				// colored output based on amount
				if ($quantityDelivery > 0) {
					$quantityDeliveryOut = '<span class="green">+'. $quantityDelivery .'</span>';
				} else {
					$quantityDeliveryOut = $quantityDelivery;
				}

				// preorders
				$preorders = $db->getPreorders($pid);
				$preordersOut = '<span class="inline emph blue">'. $preorders .' / '. $row['container'] .'</span>';

				// calculate actual deficit with pending stock refills and saving items with negative deficit
				// colored output based on amount
				$sum = ($realStock + $quantityDelivery - $preorders);

				if ($sum < 0) {
					$recommendations[$producer][] = array('pid' => $pid, 'deficit' => $sum);
 					$sumOut = '<span class="red">'. $sum .'</span>';
				} else if ($sum > 0) {
					$sumOut = '<span class="green">'. $sum .'</span>';
				} else {
					$sumOut = '<span>'. $sum .'</span>';
				}

				// warn if current stock is negative
				if ($quantity_KG_L < 0) {
					$warn = ' <span class="inline emph red"><i class="fa fa-exclamation-triangle" aria-hidden="true"></i></span>';
				} else {
					$warn = '';
				}

				// add product to table if it is in the inventory or has been ordered
				if ($quantity_KG_L || $quantityOrdered > 0) {
					echo '<tr><form action="'. htmlspecialchars($_SERVER['PHP_SELF']) .'" method="post">';
					echo '<input type="hidden" name="ii_id" value="'. $row['ii_id'] .'" required>';
					echo '<td>'. $row['productName'] . $warn .'</td>';
					echo '<td>'. $pid .'</td>';
					echo '<td>'. $intventory_in_unit . $unit_tag .'</td>';
					echo '<td><input class="stock" type="number" name="quantity_KG_L" step="0.05" value="'. $quantity_KG_L .'" required></td>';
					echo '<td>'. $quantityOrderedOut .'</td>';
					echo '<td>'. $realStockOut .'</td>';
					echo '<td>'. $quantityDeliveryOut .'</td>';
					echo '<td>'. $preordersOut . '</td>';
					echo '<td class="emph">'. $sumOut .'</td>';
					echo '<td>'. $row['first_name'] .' '. $row['last_name'] . '</td>';
					echo '<td><button type="submit" name="update" class="empty"><i class="fa fa-refresh" aria-hidden="true"></i></button></td>';
					echo '</form></tr>';

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
					$optionList .= '<option value="'. $pid .'">'. $row['productName'] .' ('. $row['container'] .' KG/L Gebinde)</option>';
				}
			}
			?>
		</table>
	</div>
	
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
	$statement = $pdo->prepare("SELECT producers.pro_id, producers.producerName, products.pid, products.productName, products.container, products.priceContainer FROM producers LEFT JOIN products ON producers.pro_id = products.producer ORDER BY producers.producerName");
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

			$orderTotalAdd .= '<option value="'. $row['pid'] .'">'. $row['productName'] .' ('. $currency . sprintf('%01.2f', $row['priceContainer']) .' / '. $row['container'] .'KG/L)</option>';

	}

	$orderTotalAdd .= '</optgroup';

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
												<th>Defizit Einheiten</th>
												<th>Gebinde Einheiten</th>
												<th>empf. Menge</th>
												<th>Preis Gebinde</th>
												<th>&#931;</th>
												</tr>
											</thead>';
				// iterating the recommendations array for this specific producer
				foreach ($category as $product) {
					$pid = $product['pid'];
					$deficit = ($product['deficit']);
					$statement2 = $pdo->prepare("SELECT productName, container, priceContainer FROM products WHERE pid = '$pid'");
					$result2 = $statement2->execute();
					$productData = $statement2->fetch();

					$priceContainer = $productData['priceContainer'];
					$quantityContainer = ceil(($deficit / $productData['container'])) * (-1);
					if ($quantityContainer == 0) { $quantityContainer = 1; }
					$total = ($priceContainer * $quantityContainer); // calculating price
					$grandtotal += $total; // calculating total price

					// adding item to table
					$recommendationsOut .= '<tr>
											<td>'. $productData['productName'] .'</td>
											<td><input type="hidden" name="pid" value="'. $pid .'">'. $pid .'</td>
											<td>'. $product['deficit'] .'</td>
											<td>'. $productData['container'] .'</td>
											<td><input class="width100" type="number" name="quantityContainer" value="'. $quantityContainer . '"></td>
											<td>'. $currency.sprintf("%01.2f", $priceContainer) .'</td>
											<td>'. $currency.sprintf("%01.2f", $total) .'</td>
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
											<td><span class="emph">'.$currency.sprintf("%01.2f", $grandtotal). '</span></td>
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
		<input type="number" name="quantity_KG_L" placeholder="Menge in KG/L" class="smallForm" required>
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
			<select name="pid" class="smallForm" required>
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

</div>

<script type="text/javascript">
	// sending recommendations to order_total cart
	$('.order-total').submit(function (e) {
		var form_data = $(this).serialize();
		var button_content = $(this).find('button[type=submit]');
		button_content.html('...'); 
		var arr = [];
		var rest = form_data;

		// if multiple items -> divide into single ajax calls
		for (var i = 1; i <= form_data.length; i+=5) {
		var checker = rest.indexOf('&pid', i);

		
			if (checker !== -1) {
				var send = rest.slice(0, checker); // slice string to next item
				rest = rest.slice(checker); // slice item off from string
				arr.push(send); // save item
				
			} else {
				arr.push(rest);

				arr.forEach(function(element) { // make ajax call for each item 
					 $.ajax({
						url: 'order_total_process.php',
						type: 'POST',
						dataType: 'json',
						data: element
					}).done(function(data) {
						
					})
				 }); 
				
				button_content.removeClass('blue').addClass('green').html('<span>Hinzugefügt <i class="fa fa-check" aria-hidden="true"></span>');
				$("#order-total-results").html('').load( "order_total_process.php", {"load_cart":"1"});
				break;
			}
		}
		e.preventDefault();
	});

	$('.order-total-add').submit(function(e) {
		var form_data = $(this).serialize();
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
</script>

<script type="text/javascript">
	(function () {
	$("#order-total-results").load( "order_total_process.php", {"load_cart":"1"});
	})();
</script>

<?php 
include("templates/footer.inc.php")
?>