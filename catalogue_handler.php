<?php
session_start();

require_once("inc/config.inc.php");
require_once("inc/functions.inc.php");

$user = check_user();

function createItem($row) {
	global $db;
	global $user;
	$result = "";
	$pid = $row['pid'];
	$stock = $db->getStock($pid);
	$unit_size = $row['unit_size'] * 1;
	$preorders = '<span class="blue">'. $db->getPreorders($pid) .'</span>';

	// colored output based on amount
	if ($stock < 0) {
		$stockOut = '<span class="red">'. $stock .'KG</span>';
	} else if ($stock > 0) {
		$stockOut = '<span class="green">'. $stock .'KG</span>';
	} else {
		$stockOut = '<span>'. $stock .'</span>';
	}
	$result .= '<div class="col-sm-3 item">
		<form class="order-item">
			<span class="data">'. htmlspecialchars($row['origin']) .' | 
				<a class="producer_info" data-code="'. $row['pro_id'] .'">'. htmlspecialchars($row['producerName']) .'</a>
			</span>
			<h2 class="name">'. htmlspecialchars($row['productName']) .'</h2>
			<div>'. $row['productDesc'] .'<br>
			<span class="emph">Preis: '. $row['price_KG_L'] .'€/'. $unit_size . $row['unit_tag']. '</span>
			</div>
			<div>
			<span class="italic">auf Lager: </span>'. $stockOut .'</div>
			<div>
			<span class="italic">vorbestellt: </span>'. $preorders .'</div>
			<div>
			<span class="italic">Gebindegröße: </span>' .$row['container']*$row['unit_size'] . $row['unit_tag'] .' ('. $row['container']*1 .' Einheiten)</div>';

	if ($user['rights'] > 1) {
		$result .= '
		<div class="price">
			<label>Menge:
			<span>
				<input class="quantity" type="number" name="quantity" min="1" step="1" value="1" required></label><span> x '. $unit_size .$row['unit_tag'] .'
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
		WHERE ProductName LIKE :search_string 
			OR origin LIKE :search_string
			OR producerName LIKE :search_string 
			OR category_name LIKE :search_string");
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


