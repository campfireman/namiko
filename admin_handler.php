<?php
/* Login functionality thanks to: https://github.com/PHP-Einfach/loginscript Thank you very much Nils Reimers! */

session_start();
require_once("inc/config.inc.php");
require_once("inc/functions.inc.php");

//Überprüfe, dass der User eingeloggt ist
//Der Aufruf von check_user() muss in alle internen Seiten eingebaut sein
$user = check_user();
check_consul();

if (isset($_POST['update-catalogue'])) {
	foreach ($_POST['values'] as $pid => $product) {
		$productName = $product['productName'];
		$productDesc = $product['productDesc'];
		$category = $product['category'];
		$price_KG_L = $product['price_KG_L'];
		$unit_size = $product['unit_size'];
		$unit_tag = $product['unit_tag'];
		$container = $product['container'];
		$priceContainer = $product['priceContainer'];
		$origin = $product['origin'];
		$producer = $product['producer'];

		$statement = $pdo->prepare("UPDATE products SET productName = :productName, productDesc = :productDesc, category=:category, price_KG_L = :price_KG_L, unit_size=:unit_size, unit_tag = :unit_tag, container = :container, priceContainer = :priceContainer, origin = :origin, producer = :producer  WHERE pid = :pid");
		$result = $statement->execute(array('productName' => $productName, 'productDesc' => $productDesc, 'category' => $category, 'price_KG_L' => $price_KG_L, 'unit_size' => $unit_size, 'unit_tag' => $unit_tag, 'container' => $container, 'priceContainer' => $priceContainer, 'origin' => $origin, 'producer' => $producer, 'pid' => $pid));

		if ($result) {
			continue;
		} else {
			res(1, json_encode($statement->errorInfo()));
		}
	}

	res(0, "Success");
}

?>