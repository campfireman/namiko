<?php
include('inc/config.inc.php');
include('inc/functions.inc.php');

if (isset($_POST['update-inventory'])) {
	foreach ($_POST['values'] as $ii_id => $product) {
		try {
			$pdo->beginTransaction();
			$quantity_KG_L = $product['quantity_KG_L'];

			$statement = $pdo->prepare("
				UPDATE inventory_items
				SET quantity_KG_L = :quantity_KG_L
				WHERE ii_id = :ii_id");
			$result = $statement->execute(array('quantity_KG_L' => $quantity_KG_L, 'ii_id' => $ii_id));

			if (!$result) {
				throw new Exeption(json_encode($statement->errorInfo()));
			}

			$pdo->commit();
		} catch (Exception $e) {
			$pdo->rollBack();
			res(1, $e->getMessage());
		}
	}
	res(0, "Success");
}

if (isset($_POST['category']) && isset($_POST['producer'])) {
	$selector = '';
	$count = 1;

	foreach($_POST['category'] as $cid){
		if ($cid == 0) {
			break;
		}

		if ($count == 1) {
			$selector .= 'AND (';
		}

		if ($count > 1) {
			$selector .= " OR ";
		}

		$selector .= "category= ". intval($cid);
		$count++;
	}

	if ($count >= 1 && $cid != 0) $selector .= ")";
	$count = 1;

	foreach($_POST['producer'] as $producer){
		if ($producer == 0) {
			break;
		}

		if ($count == 1) {
			$selector .= ' AND (';
		}

		if ($count > 1) {
			$selector .= " OR ";
		}

		$selector .= "producer = ". intval($producer);
		$count++;
	}
	if ($count >= 1 && $producer != 0) $selector .= ")";

	$statement = $pdo->prepare("
		SELECT inventory_items.*, products.*, users.first_name, users.last_name 
			FROM inventory_items LEFT JOIN products ON inventory_items.pid = products.pid 
			LEFT JOIN users ON inventory_items.last_edited_by = users.uid 
		WHERE category >= 0 ". $selector ."
		ORDER BY productName");
	$result = $statement->execute();

	$table = '
	<table class="inventory-t table panel panel-default">
		<thead>
			<tr>
				<th>Produktname</th>
				<th>Produkt ID</th>
				<th>Lagerware</th>
				<th>Lagermenge umgerechnet</th>
				<th class="width100">Lagermenge (E)</th>
				<th class="width100">bestellt (E)</th>
				<th class="width100">&#916;</th>
				<th class="width100">Nachschub</th>
				<th>vorbest.</th>
				<th class="width100">&#931;</th>
				<th>zuletzt editiert</th>
			</tr>
		</thead>';

	while($row = $statement->fetch()) {
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

				if ($is_storage_item) {
					$is_storage_item_out = '<span> <i class="fa fa-database" aria-hidden="true"></i></span>';
				} else {
					$is_storage_item_out = '';
				}

				// add product to table if it is in the inventory or has been ordered
				if ($quantity_KG_L || $quantityOrdered > 0) {
					 $table .= '
					 <tr class="product"><form action="'. htmlspecialchars($_SERVER['PHP_SELF']) .'" method="post">
					 <input type="hidden" name="ii_id" value="'. $row['ii_id'] .'">
					 <td>'. $row['productName'] . $warn .'</td>
					 <td>'. $pid .'</td>
					 <td>'. $is_storage_item_out .'</td>
					 <td>'. $intventory_in_unit . $unit_tag .'</td>
					 <td><input class="stock" type="number" name="quantity_KG_L" step="1" value="'. $quantity_KG_L .'" required></td>
					 <td>'. $quantityOrderedOut .'</td>
					 <td>'. $realStockOut .'</td>
					 <td>'. $quantityDeliveryOut .'</td>
					 <td>'. $preordersOut . '</td>
					 <td class="emph">'. $sumOut .'</td>
					 <td>'. $row['first_name'] .' '. $row['last_name'] . '</td>
					 </form></tr>';
				}
	}

	$table .= '</table>';

	res(0, $table);
}