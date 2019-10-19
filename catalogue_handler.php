<?php
session_start();

require_once("inc/config.inc.php");
require_once("inc/functions.inc.php");

$user = check_user();

function createItem($row) {
	global $db;
	global $user;
	global $currency;
	$result = "";
	$pid = $row['pid'];
	$stock = $db->getStock($pid);
	$unit_size = $row['unit_size'] * 1;
	$unit_tag = $row['unit_tag'];
	$units_in_stock = $stock * $unit_size;
	$last_price = $row['last_price'];
	$price_KG_L = $row['price_KG_L'];
	$price_difference = $price_KG_L - $last_price;
	$preorders = '<span class="blue">'. $db->getPreorders($pid)*1 .' E</span>';

	// colored output based on amount
	if ($stock < 0) {
		$stockOut = '<span class="red">'. $units_in_stock . $unit_tag .'</span>';
	} else if ($stock > 0) {
		$stockOut = '<span class="green">'. $units_in_stock  . $unit_tag .' ('. $stock .' E)</span>';
	} else {
		$stockOut = '<span>'. $stock .'</span>';
	}

	if ($row['is_storage_item'] == 1) {
		$is_storage_item = '| <span class="yellow">Lagerware</span>';
	} else {
		$is_storage_item = '';
	}

	if ($price_difference > 0) {
		$price_development = '<span class="red"> <i class="fa fa-arrow-circle-up" aria-hidden="true"></i> <small>(+'. $price_difference . $currency .')</small></span>';
	} else if ($price_difference < 0) {
		$price_development = '<span class="green"> <i class="fa fa-arrow-circle-down" aria-hidden="true"></i> <small>('. $price_difference . $currency .')</small></span>';
	} else {
		$price_development = '';
	}
	
	$result .= '<div class="col-sm-3 item">
		<form class="order-item">
			<span class="data">'. htmlspecialchars($row['origin']) .' | 
				<a class="producer_info" data-code="'. $row['pro_id'] .'">'. htmlspecialchars($row['producerName']) .'</a>
				'. $is_storage_item .'
			</span>
			<h2 class="name">'. htmlspecialchars($row['productName']) .'</h2>
			<div>'. $row['productDesc'] .'<br>
			<span class="emph">Preis: '. $row['price_KG_L'] .'€/'. $unit_size . $unit_tag. '</span>'. $price_development .'
			</div>
			<div>
			<span class="italic">auf Lager: </span>'. $stockOut .'</div>
			<div>
			<span class="italic">vorbestellt: </span>'. $preorders .'</div>
			<div>
			<span class="italic">Gebindegröße: </span>' .$row['container']*$row['unit_size'] . $unit_tag .' ('. $row['container']*1 .' E)</div>';

	if ($user['rights'] > 1) {
		$result .= '
		<div class="price">
			<label>Menge:
			<span>
				<input class="quantity" type="number" name="quantity" min="1" step="1" value="1" required></label><span> x '. $unit_size .$unit_tag .'
				<input type="hidden" name="pid" value="'. $row['pid'] .'">
			</span>
			<button class="addCart green" type="submit" name="addCart"><i class="fa fa-cart-plus" aria-hidden="true"></i></button>
		</div>';
	}
	$result .= '</form></div>';

	return $result;
}

if (isset($_POST['category']) && isset($_POST['producer'])) {
	$count = 1;
	$selector = '';

	foreach($_POST['category'] as $cid){
		if ($cid == 0) {
			break;
		}

		if ($count == 1) {
			$selector .= ' AND (';
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
		SELECT categories.*, products.*, producers.producerName, producers.pro_id FROM products 
		LEFT JOIN categories
			ON categories.cid = products.category
		LEFT JOIN producers 
			ON products.producer = producers.pro_id 
		WHERE cid > 1 ". $selector . "
		ORDER BY category");
	$result = $statement->execute();
	$catalogue = '';
	$cid = -1;
	$count = 0;
	$first = true;

	if ($statement->rowCount() == 0) {
		die(json_encode("Keine Daten gefunden."));
	}
	while ($row = $statement->fetch()) {
		if ($cid != $row['cid']) {

			$categoryIMG = $row['categoryIMG'];
			$num = 0;

			while (strpos($categoryIMG, '|') !== false) {
				$num++;
				${'img'.$num} = substr($categoryIMG, 0, strpos($categoryIMG, '|'));
				$categoryIMG = substr($categoryIMG, (strpos($categoryIMG, '|') + 1));
			}

			$catalogue .= '	<div class="catThumb spacer">';

			$arr = ["first", "second", "third"];
			$n = sizeof($arr)*2;
			for($i = 0; $i < $n; $i++) {
				if ($i < $n/2) {
					$class = $arr[$i];
				} else {
					$pos = $n - ($i + 1);
					$class = $arr[$pos];
				}

				$catalogue .= '<div class="'. $class .' catImg"><div class="center-vertical"><div class="center"><div class="img"><img src="media/'. ${'img'. ($i +1)} .'"></div></div></div></div>';
				if ($i == $n / 2 -1) {
					$catalogue .= '<h2 class="header">'. htmlspecialchars($row['category_name']) .'</h2>';
				}
			}

			$catalogue .= '</div>';
		}
		
		if ($cid != $row['cid']) {
			$cid = $row['cid'];
			$count = 0;

			if ($first) {
				$first = false;
			} else {
				$catalogue.= '</div>';
			}
		}

		$count++;
		if ($count == 5) $count = 1;

		if ($count == 1) {
			$catalogue .= '<div class="row">'; 
		}
		$catalogue .= createItem($row);

		if ($count == 4) {
			$catalogue .= '</div>'; }
		
	}
	if ($count < 4) {
		$catalogue .= '</div>';
	}

	die(json_encode($catalogue));
}

if (isset($_POST['search'])) {
	$search_string = '%'. $_POST['search'] .'%';
	$statement = $pdo->prepare("
		SELECT products.*, producers.*, categories.* FROM products 
		LEFT JOIN producers ON products.producer = producers.pro_id
		LEFT JOIN categories ON categories.cid = products.category 
		WHERE (ProductName LIKE :search_string 
			OR origin LIKE :search_string
			OR producerName LIKE :search_string 
			OR category_name LIKE :search_string) AND cid > 1");
	$statement->bindParam(':search_string', $search_string);
	$result = $statement->execute();
	$column = 1;
	$catalogue = ' <div class="spacer">
							<span class="subtitle2">Suchergebnisse</span>
							</div>';

	if ($statement->rowCount() > 0) {
		while ($row = $statement->fetch()) {
			if ($column == 1) {
				$catalogue .= '<div class="row">';
			}

			$column++;
			$catalogue.= createItem($row);

			if ($column == 5) {
				$catalogue .= '</div>';
				$column = 1;
			}
		} 
		if ($column < 5) $catalogue .= '</div>';
		$catalogue .= '</div>';
	} else {
		$catalogue .= 'Keine Ergbnisse für "'. htmlspecialchars($_POST['search']) .'" gefunden.';
	}
	die(json_encode($catalogue));
}

die(json_encode('Keine Daten gefunden.'));
?>


