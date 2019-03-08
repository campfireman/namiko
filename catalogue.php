<?php
session_start();
require_once("inc/config.inc.php");
require_once("inc/functions.inc.php");
// ini_set('display_errors', 1);

if (is_checked_in()) {
	$user = check_user();
}

include("templates/header.inc.php");
include("templates/nav.inc.php");
?>
<div class="sizer spacer">

<?php 

$statement = $pdo->prepare("SELECT * FROM categories WHERE cid > 1");
$result = $statement->execute();

while ($row = $statement->fetch()) {
	$selector = $row['cid'];
	$categoryIMG = $row['categoryIMG'];
	$num = 0;

	while (strpos($categoryIMG, '|') !== false) {
		$num++;
		${'img'.$num} = substr($categoryIMG, 0, strpos($categoryIMG, '|'));
		$categoryIMG = substr($categoryIMG, (strpos($categoryIMG, '|') + 1));
	}

	echo '	<div class="catThumb spacer">
			<div class="first catImg"><div class="center-vertical"><div class="center"><div class="img"><img src="media/'. $img1 .'"></div></div></div></div>
			<div class="second catImg"><div class="center-vertical"><div class="center"><div class="img"><img src="media/'. $img2 .'"></div></div></div></div>
			<div class="third catImg"><div class="center-vertical"><div class="center"><div class="img"><img src="media/'. $img3 .'"></div></div></div></div>
			<h2 class="header">'. htmlspecialchars($row['category_name']) .'</h2>
			<div class="third catImg"><div class="center-vertical"><div class="center"><div class="img"><img src="media/'. $img4 .'"></div></div></div></div>
			<div class="second catImg"><div class="center-vertical"><div class="center"><div class="img"><img src="media/'. $img5 .'"></div></div></div></div>
			<div class="first catImg"><div class="center-vertical"><div class="center"><div class="img"><img src="media/'. $img6 .'"></div></div></div></div>
		  	</div>';
	

		$stmnt = $pdo->prepare("SELECT products.*, producers.producerName FROM products LEFT JOIN producers ON products.producer = producers.pro_id WHERE category = '$selector'");
		$result2 = $stmnt->execute();
		$count = 0;
		while ($row = $stmnt->fetch()) {
			$count++;
			if ($count == 5) { $count = 1; }
			if ($count == 1) {
			echo '<div class="row">'; }
			echo '<div class="col-sm-3 item">';
			echo '<span class="data">'. htmlspecialchars($row['origin']) .' | '. htmlspecialchars($row['producerName']) .'</span>';
			echo '<h2 class="name">'. htmlspecialchars($row['productName']) .'</h2>';
			echo '<div>'. $row['productDesc'] .'<br><span class="emph">Preis: '. $row['price_KG_L'] .'€/KG</span></div>';
			echo '</div>';
			if ($count == 4) {
			echo '</div>'; }
		}
	if ($count < 4) {
		echo '</div>';
	}
}
?>

</div>
<?php 
include("templates/footer.inc.php");
?>