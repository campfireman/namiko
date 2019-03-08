<?php
/* Login functionality thanks to: https://github.com/PHP-Einfach/loginscript Thank you very much Nils Reimers! */

session_start();
require_once("inc/config.inc.php");
require_once("inc/functions.inc.php");
ini_set('display_errors', 1);

//Überprüfe, dass der User eingeloggt ist
//Der Aufruf von check_user() muss in alle internen Seiten eingebaut sein
$user = check_user();
check_consul();

include("templates/header.inc.php");
include("templates/nav.inc.php");
include("templates/admin-nav.inc.php");


function echo_select ($array, $exclusion) {
	foreach ($array as $item) {
		if ($item['cid'] != $exclusion) {
			echo '<option value="'. $item['cid'] .'">'. htmlspecialchars($item['category_name']) .'</option>';
		}
	}
}

if(isset($_POST['product'])) {

	$error = false;
	$productName = trim($_POST['productName']);
	$productDesc = trim($_POST['productDesc']);
	$price_KG_L = $_POST['price_KG_L'];
	$category = $_POST['category'];
	$container = $_POST['container'];
	$priceContainer = $_POST['priceContainer'];
	$origin = $_POST['origin'];
	$producer = $_POST['producer'];
	
	if(empty($productName) || empty($productDesc) || empty($price_KG_L) || empty($category) || empty($container) || empty($origin) || empty($producer)) {
		notify('Bitte alle Felder ausfüllen.');
	}

	if ($category == 0 || $producer == 0) {
		notify('Bitte eine Auswahl treffen.');
	}

	$statement = $pdo->prepare("INSERT INTO products (productName, productDesc, price_KG_L, category, container, priceContainer, origin, producer) VALUES (:productName, :productDesc, :price_KG_L, :category, :container, :priceContainer, :origin, :producer)");
	$result = $statement->execute(array('productName' => $productName, 'productDesc' => $productDesc, 'price_KG_L' => $price_KG_L, 'category' => $category, 'container' => $container, 'priceContainer' => $priceContainer, 'origin' => $origin, 'producer' => $producer));
	
	if($result) {
		$pid = $pdo->lastInsertId();
		$statement = $pdo->prepare("INSERT INTO inventory_items (pid, quantity_KG_L, last_edited_by) VALUES (:pid, :quantity_KG_L, :last_edited_by)");
		$result = $statement->execute(array('pid' => $pid, 'quantity_KG_L' => 0, 'last_edited_by' => $user['uid']));

		if ($result) {
			notify('Das Produkt wurde erfolgreich hinzugefügt.');
		} else {
			notify('Beim Abspeichern ist leider ein Fehler aufgetreten. '. json_encode($statement->errorInfo()));
		}
	} else {
		notify('Beim Abspeichern ist leider ein Fehler aufgetreten. '. json_encode($statement->errorInfo()));
	}
}

if(isset($_POST['addCat'])) {
	$category_name = $_POST['category_name'];
	$categoryIMG = $_POST['categoryIMG1'] .'|'. $_POST['categoryIMG2'] .'|'. $_POST['categoryIMG3'] .'|'. $_POST['categoryIMG4'] .'|'. $_POST['categoryIMG5'] .'|'. $_POST['categoryIMG6'] .'|';
	$error2 = false;

	if (empty($category_name) || empty($categoryIMG)) {
		notify('Bitte alle Felder ausfüllen.');
	}

	$statement = $pdo->prepare("INSERT INTO categories (category_name, categoryIMG) VALUES (:category_name, :categoryIMG)");
	$result = $statement->execute(array('category_name' => $category_name, 'categoryIMG' => $categoryIMG));

	if ($result) {
		notify('Kategorie erfolgreich gespeichert.');
	} else {
		notify('Es gab einen Fehler.');
	}
	
}

/* Credit for this code fully goes to W3S: https://www.w3schools.com/php/php_file_upload.asp */

// Check if image file is a actual image or fake image
if(isset($_POST["upload"])) {

		$target_dir = "media/";
		$target_file = $target_dir . basename($_FILES["fileToUpload"]["name"]);
		$uploadOk = 1;
		$imageFileType = strtolower(pathinfo($target_file,PATHINFO_EXTENSION));

	    $check = getimagesize($_FILES["fileToUpload"]["tmp_name"]);
	    if($check !== false) {
	        //echo "File is an image - " . $check["mime"] . ".";
	        $uploadOk = 1;

	        // Check if file already exists
			if (file_exists($target_file)) {
			    $_SESSION['notification'] = true;
				$_SESSION['notificationmsg'] = 'Diese Datei existiert bereits.';
			    $uploadOk = 0;
			}
			// Check file size
			if ($_FILES["fileToUpload"]["size"] > 500000) {
			    $_SESSION['notification'] = true;
				$_SESSION['notificationmsg'] = 'Die Datei ist zu groß.';
			    $uploadOk = 0;
			}
			// Allow certain file formats
			if($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg"
			&& $imageFileType != "gif" ) {
			    $_SESSION['notification'] = true;
				$_SESSION['notificationmsg'] = 'Nur JPG, JPEG, PNG & GIF Dateien sind zulässig.';
			    $uploadOk = 0;
			}
			// Check if $uploadOk is set to 0 by an error
			if ($uploadOk == 0) {
			    $_SESSION['notification'] = true;
				$_SESSION['notificationmsg'] = 'Die Datei konnte nicht hochgeladen werden.';
				header("Location: " . $_SERVER['REQUEST_URI']);
			// if everything is ok, try to upload file
			} else {
			    if (move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $target_file)) {
			        $_SESSION['notification'] = true;
					$_SESSION['notificationmsg'] = "Die Datei ". basename( $_FILES["fileToUpload"]["name"]). " wurde erfolgreich hochgeladen.";
					header("Location: " . $_SERVER['REQUEST_URI']);
			    } else {
			        $_SESSION['notification'] = true;
					$_SESSION['notificationmsg'] = "Es gab einen Fehler beim Upload.";
					header("Location: " . $_SERVER['REQUEST_URI']);
			    }
			}

    } else {
        $_SESSION['notification'] = true;
		$_SESSION['notificationmsg'] = 'Das ist keine Bilddatei.';
        $uploadOk = 0;
    }
}

if (isset($_POST['save'])) {
	$pid = $_POST['pid'];
	$productName = $_POST['productName'];
	$productDesc = $_POST['productDesc'];
	$category = $_POST['category'];
	$price_KG_L = $_POST['price_KG_L'];
	$container = $_POST['container'];
	$origin = $_POST['origin'];
	$producer = $_POST['producer'];

	$statement = $pdo->prepare("UPDATE products SET productName = '$productName', productDesc = '$productDesc', category='$category', price_KG_L = '$price_KG_L', container = '$container', origin = '$origin', producer = '$producer'  WHERE pid = '$pid'");
	$result = $statement->execute();

	if ($result) {
		notify('Artikel erfolgreich aktualisiert.');
	}

	if (!$result) {
		notify('Es gab einen Fehler.');
	}
}
?>

<div class="sizer spacer">			
	<div class="row">
		<div class="col-sm-6">
			<div><span class="subtitle2">Produkt hinzufügen</span></div>
			<form class="form" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post">	
				<div>
					<input placeholder="Produktname" type="text" id="productName" maxlength="50" name="productName" required>
				</div>
				
				<div>
					<textarea class="scroll" placeholder="Produktbeschreibung" type="text" id="productDesc" rows="3" maxlength="2000" name="productDesc" required></textarea>
				</div>
				
				<div>
					<input placeholder="Preis pro KG/L (€)" type="number" id="price_KG_L" min="0" step="0.01" name="price_KG_L" required>
				</div>
				
				<div>
					<select type="number" id="category" min="1" maxlength="10" name="category" required>
						<option value="0">- Kategorie wälen -</option>
					    <?php
					    $statement = $pdo->prepare("SELECT * FROM categories ORDER BY cid");
					    $result = $statement->execute();

					    while($row = $statement->fetch()) {
					    	echo '<option value="'. $row['cid'] .'">'. htmlspecialchars($row['category_name']) .'</option>';
					    }
					    ?>
					</select>
				</div>

				<div>
					<input placeholder="Gebinde in KG/L" type="number" id="container" min="0" name="container">
				</div>

				<div>
					<input placeholder="Preis Gebinde" type="number" step="0.01" id="priceContainer" min="0" name="priceContainer">
				</div>

				<div>
					<input type="text" name="origin" placeholder="Herkunft">
				</div>

				<div>
					<select type="number" min="1" maxlength="10" name="producer" required>
						<option value="0">- Hersteller wälen -</option>
					    <?php
					    $statement = $pdo->prepare("SELECT * FROM producers ORDER BY pro_id");
					    $result = $statement->execute();

					    while($row = $statement->fetch()) {
					    	echo '<option value="'. $row['pro_id'] .'">'. htmlspecialchars($row['producerName']) .'</option>';
					    }
					    ?>
					</select>
				</div><br>
			
				<button class="clean-btn green" name="product" type="submit">Hinzufügen <i class="fa fa-plus" aria-hidden="true"></i></button>
			</form><br><br>
		</div>

		<div class="col-sm-6">
		<div><span class="subtitle2 spacer">Produktkategorie hinzufügen</span></div>
			<div>
				<form class="form" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post" enctype="multipart/form-data">
					<div class="spacer2"><span class="subtitle3">Kategoriebild auswählen und hochladen</span></div>
					<input type="file" name="fileToUpload" id="fileToUpload" required><br>
					<button class="clean-btn blue" type="submit" value="Upload Image" name="upload">Bild hochladen <i class="fa fa-upload" aria-hidden="true"></i></button>
				</form>
			</div><br>

			<div>
				<div class="spacer2"><span class="subtitle3">Kategorie erstellen</span></div>
				<form class="form" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']) ?>" method="post">
					<input type="text" name="category_name" placeholder="Name der Kategorie" required><br><br>
					<span><i class="fa fa-info-circle" aria-hidden="true"></i> Für jede Kategorie sind 6 Einzelbilder erforderlich. Nach Upload (oben) der Datei einfach Namen und Endung der Datei eingeben.</span><br>
					<label>1.</label><input type="text" name="categoryIMG1" placeholder="Name + Endung" required><br>
					<label>2.</label><input type="text" name="categoryIMG2" placeholder="Name + Endung" required><br>
					<label>3.</label><input type="text" name="categoryIMG3" placeholder="Name + Endung" required><br>
					<label>4.</label><input type="text" name="categoryIMG4" placeholder="Name + Endung" required><br>
					<label>5.</label><input type="text" name="categoryIMG5" placeholder="Name + Endung" required><br>
					<label>6.</label><input type="text" name="categoryIMG6" placeholder="Name + Endung" required><br>
					<br>
					<button class="clean-btn green" name="addCat" type="submit" require>Hinzufügen <i class="fa fa-plus" aria-hidden="true"></i></button>
				</form>
			</div>
		</div>
	</div>			 

	<div class="spacer full">
		<span class="subtitle2">Katalog verwalten</span><br><br>
		<table class="table panel panel-default" style="min-width: 820px">
		<tr>
			<th>#</th>
			<th>Produktname</th>
			<th>Produktbeschreibung</th>
			<th>Kategorie</th>
			<th>Preis KG/L (€)</th>
			<th>Gebinde (KG)</th>
			<th>Herkunft</th>
			<th>Hersteller</th>
			<th></th>
		</tr>
		<?php 

		$statement = $pdo->prepare("SELECT * FROM producers ORDER BY pro_id");
		$result = $statement->execute();

		$select;
		while ($row = $statement->fetch()) {

			$select .= '<option value="'. $row['pro_id'] .'">'. $row['producerName'] .'</option>';

		}

		$statement = $pdo->prepare("SELECT * FROM categories ORDER BY cid");
	    $result = $statement->execute();
	    $categories = array();
	    while($row = $statement->fetch()) {
	    	$categories[] = array('cid' => $row['cid'], 'category_name' => $row['category_name']);

	    }

		$statement = $pdo->prepare("SELECT products.*, producers.pro_id, producers.producerName, categories.category_name FROM products LEFT JOIN producers ON products.producer = producers.pro_id LEFT JOIN categories ON categories.cid = products.category ORDER BY products.pid");
		$result = $statement->execute();

		//print_r($arr = $statement->errorInfo());
		while($row = $statement->fetch()) {
			echo '<tr><form action="'. htmlspecialchars($_SERVER['PHP_SELF']) .'" method="post">';
			echo '<td>'. $row['pid']. '<input value="'. $row['pid'] .'" type="hidden" name="pid"></td>';
			echo '<td><input class="empty" type="text" name="productName" value="'. $row['productName'] .'"></td>';
			echo '<td><input class="empty" type="text" name="productDesc" value="'. $row['productDesc'] .'"></td>';
			echo '<td><select type="number" id="category" min="1" maxlength="10" name="category" required>';
			echo '<option value="'. $row['category'] .'">'. $row['category_name'] .'</option>';
					    echo_select($categories, $row['category']);
			echo '</select></td>';
			echo '<td><input class="empty" type="number" name="price_KG_L" step="0.01" value="'. $row['price_KG_L'] .'"></td>';
			echo '<td><input class="empty" type="number" name="container" step="0.1" value="'. $row['container'] .'"></td>';
			echo '<td><input class="empty" type="text" name="origin" value="'. $row['origin'] .'"></td>';
			echo '<td><select class="empty" type="text" name="producer"><option value="'. $row['pro_id'] .'">'. $row['producerName'] .'</option>'. $select .'</select></td>';
			echo '<td><button class="empty" type="submit" name="save"><i class="fa fa-floppy-o" aria-hidden="true"></i></button></td>';
			echo '</tr>';
			echo "</form></tr>";
		}
		?>
		</table>
	</div>
</div>

<?php 
include("templates/footer.inc.php")
?>