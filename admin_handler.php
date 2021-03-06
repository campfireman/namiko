<?php
/* Login functionality thanks to: https://github.com/PHP-Einfach/loginscript Thank you very much Nils Reimers! */

session_start();
require_once "inc/config.inc.php";
require_once "inc/functions.inc.php";

//Überprüfe, dass der User eingeloggt ist
//Der Aufruf von check_user() muss in alle internen Seiten eingebaut sein
$user = check_user();
check_consul();

function echo_select($array, $exclusion)
{
    $result = '';
    foreach ($array as $item) {
        if ($item['cid'] != $exclusion) {
            $result .= '<option value="' . $item['cid'] . '">' . htmlspecialchars($item['category_name']) . '</option>';
        }
    }
    return $result;
}

if (isset($_POST['update-catalogue'])) {
    foreach ($_POST['values'] as $pid => $product) {
        try {
            $pdo->beginTransaction();
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
            $netto = $product['netto'];
            $is_storage_item = $product['is_storage_item'];

            $statement = $pdo->prepare("SELECT price_KG_L FROM products WHERE pid = :pid");
            $result = $statement->execute(array('pid' => $pid));

            if (!$result) {
                throw new Exception(json_encode($statement->erroInfo()));
            }

            $old_price_KG_L = $statement->fetch();
            $old_price_KG_L = $old_price_KG_L['price_KG_L'];

            if ($price_KG_L != $old_price_KG_L) {
                $statement = $pdo->prepare("INSERT INTO product_price_log (pid, price_per_unit_brutto) VALUES (:pid, :price_per_unit_brutto)");
                $result = $statement->execute(array('pid' => $pid, 'price_per_unit_brutto' => $price_KG_L));

                if (!$result) {
                    throw new Exception(json_encode($statement->errorInfo()));
                }

                $statement = $pdo->prepare("UPDATE products SET last_price = :old_price_KG_L WHERE pid = :pid");
                $result = $statement->execute(array('old_price_KG_L' => $old_price_KG_L, 'pid' => $pid));

                if (!$result) {
                    throw new Exception(json_encode($statement->errorInfo()));
                }
            }

            $statement = $pdo->prepare("SELECT unit_size FROM products WHERE pid = :pid");
            $result = $statement->execute(array('pid' => $pid));

            if (!$result) {
                throw new Exeption(json_encode($statement->errorInfo()));
            }

            $old_unit_size = $statement->fetch();
            $old_unit_size = $old_unit_size['unit_size'];

            if ($old_unit_size != $unit_size) {
                $ratio = $unit_size / $old_unit_size;
                $statement = $pdo->prepare("UPDATE order_items SET quantity = quantity / :ratio WHERE pid = :pid");
                $statement->bindValue('ratio', $ratio);
                $statement->bindParam('pid', $pid);
                $result = $statement->execute();

                if (!$result) {
                    throw new Exception(json_encode($statement->errorInfo()));
                }

                $statement = $pdo->prepare("UPDATE preorder_items SET quantity = quantity / :ratio WHERE pid = :pid");
                $statement->bindValue('ratio', $ratio);
                $statement->bindParam('pid', $pid);
                $result = $statement->execute();

                if (!$result) {
                    throw new Exception(json_encode($statement->errorInfo()));
                }

                $statement = $pdo->prepare("UPDATE product_price_log SET price_per_unit_brutto = price_per_unit_brutto * :ratio WHERE pid = :pid");
                $statement->bindValue('ratio', $ratio);
                $statement->bindParam('pid', $pid);
                $result = $statement->execute();

                if (!$result) {
                    throw new Exception(json_encode($statement->errorInfo()));
                }

                $statement = $pdo->prepare("UPDATE products SET last_price = :price_KG_L WHERE pid = :pid");
                $statement->bindValue('price_KG_L', $price_KG_L);
                $statement->bindParam('pid', $pid);
                $result = $statement->execute();

                if (!$result) {
                    throw new Exception(json_encode($statement->errorInfo()));
                }

                $statement = $pdo->prepare("UPDATE inventory_items SET quantity_KG_L = quantity_KG_L / :ratio WHERE pid = :pid");
                $statement->bindValue('ratio', $ratio);
                $statement->bindParam('pid', $pid);
                $result = $statement->execute();

                if (!$result) {
                    throw new Exception(json_encode($statement->errorInfo()));
                }
            }

            $statement = $pdo->prepare("UPDATE products SET productName = :productName, productDesc = :productDesc, category=:category, netto = :netto, price_KG_L = :price_KG_L, unit_size=:unit_size, unit_tag = :unit_tag, container = :container, priceContainer = :priceContainer, origin = :origin, producer = :producer, is_storage_item = :is_storage_item WHERE pid = :pid");
            $result = $statement->execute(array('productName' => $productName, 'productDesc' => $productDesc, 'category' => $category, 'netto' => $netto, 'price_KG_L' => $price_KG_L, 'unit_size' => $unit_size, 'unit_tag' => $unit_tag, 'container' => $container, 'priceContainer' => $priceContainer, 'origin' => $origin, 'producer' => $producer, 'pid' => $pid, 'is_storage_item' => $is_storage_item));

            if (!$result) {
                throw new Exeption(json_encode($statement->errorInfo()));
            }

            // deactivate update of preorders
            // $statement = $pdo->prepare("UPDATE preorder_items SET total = quantity * :price_KG_L WHERE pid = :pid AND transferred = 0");
            // $result = $statement->execute(array('price_KG_L' => $price_KG_L, 'pid' => $pid));

            // if (!$result) {
            //     throw new Exeption(json_encode($statement->errorInfo()));
            // }
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

    foreach ($_POST['category'] as $cid) {
        if ($cid == 0) {
            break;
        }

        if ($count == 1) {
            $selector .= 'AND (';
        }

        if ($count > 1) {
            $selector .= " OR ";
        }

        $selector .= "category= " . intval($cid);
        $count++;
    }

    if ($count >= 1 && $cid != 0) {
        $selector .= ")";
    }

    $count = 1;

    foreach ($_POST['producer'] as $producer) {
        if ($producer == 0) {
            break;
        }

        if ($count == 1) {
            $selector .= ' AND (';
        }

        if ($count > 1) {
            $selector .= " OR ";
        }

        $selector .= "producer = " . intval($producer);
        $count++;
    }
    if ($count >= 1 && $producer != 0) {
        $selector .= ")";
    }

    $statement = $pdo->prepare("SELECT * FROM producers ORDER BY pro_id");
    $result = $statement->execute();
    $select = "";
    $select;
    while ($row = $statement->fetch()) {

        $select .= '<option value="' . $row['pro_id'] . '">' . $row['producerName'] . '</option>';

    }

    $statement = $pdo->prepare("SELECT * FROM categories ORDER BY cid");
    $result = $statement->execute();
    $categories = array();
    while ($row = $statement->fetch()) {
        $categories[] = array('cid' => $row['cid'], 'category_name' => $row['category_name']);

    }

    $statement = $pdo->prepare("
		SELECT products.*, producers.pro_id, producers.producerName, categories.category_name
		FROM products
		LEFT JOIN producers ON products.producer = producers.pro_id
		LEFT JOIN categories ON categories.cid = products.category
		WHERE cid > 0 " . $selector . "
		ORDER BY products.pid");
    $result = $statement->execute();

    $table = '
	<table class="table panel panel-default">
		<thead>
			<tr>
				<th>#</th>
				<th>Produktname</th>
				<th>Produktbeschreibung</th>
				<th>Kategorie</th>
				<th>Netto/E</th>
				<th>MWST</th>
				<th>Brutto/E</th>
				<th>Einheitengr.</th>
				<th>Einheitenkürzel</th>
				<th>Gebinde (Einheiten)</th>
				<th>Bruttop. Geb.</th>
				<th>Herkunft</th>
				<th>Lieferant</th>
				<th>Lagerware</th>
				<th>Statistik</th>
			</tr>
		</thead>';

    while ($row = $statement->fetch()) {
        if ($row['is_storage_item'] == 1) {
            $is_storage_item = 'checked';
        } else {
            $is_storage_item = '';
        }

        $table .= '
		<tr class="product update">
			<td>' . $row['pid'] . '<input value="' . $row['pid'] . '" type="hidden" name="pid"></td>
			<td><input class="empty" type="text" name="productName" value="' . $row['productName'] . '"></td>
			<td><input class="empty" type="text" name="productDesc" value="' . $row['productDesc'] . '"></td>
			<td><select type="number" id="category" min="1" maxlength="10" name="category" required>
			<option value="' . $row['category'] . '">' . $row['category_name'] . '</option>.'
        . echo_select($categories, $row['category']) . '
			</select></td>
			<td><input class="empty netto" type="number" name="netto" step="0.01" value="' . $row['netto'] . '"></td>
			<td><input class="empty tax" type="number" name="tax" step="0.01" value="' . $row['tax'] . '"></td>
			<td><input class="empty" type="number" name="price_KG_L" step="0.01" value="' . $row['price_KG_L'] . '"></td>
			<td><input class="empty" type="number" name="unit_size" step="0.01" value="' . $row['unit_size'] . '"></td>
			<td><input class="empty" type="text" name="unit_tag" value="' . $row['unit_tag'] . '"></td>
			<td><input class="empty container" type="number" name="container" step="0.1" value="' . $row['container'] . '"></td>
			<td><input class="empty" type="number" name="priceContainer" step="0.01" value="' . $row['priceContainer'] . '"></td>
			<td><input class="empty" type="text" name="origin" value="' . $row['origin'] . '"></td>
			<td><select type="text" name="producer"><option value="' . $row['pro_id'] . '">' . $row['producerName'] . '</option>' . $select . '</select></td>
			<td><input type="checkbox" name="is_storage_item" value="1"' . $is_storage_item . '></td>
            <td>
                <form method="POST">
                    <input type="hidden" name="pid" value="' . $row['pid'] . '">
                    <button class="empty" type="submit" name="statistics-csv" value=""><i class="fa fa-area-chart" aria-hidden="true"></i></button>
                </form>
            </td>
			</tr>
		</tr>';
    }

    $table .= '</table>';

    res(0, $table);
}

res(0, "Keine Daten wurden uebermittelt");
