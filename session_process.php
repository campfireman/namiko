<?php
require_once "inc/config.inc.php";
require_once "inc/functions.inc.php";
ini_set('dislay_errors', 1);

if (isset($_GET['remove-order'])) {
    $oi_id = $_GET['oi_id'];
    $oid = $_GET['oid'];
    $type = isset($_GET['preorder']) ? 'preorder' : 'order';

    try {
        $pdo->beginTransaction();
        $statement = $pdo->prepare("SELECT * FROM " . $type . "_items WHERE oid = '$oid'");
        $result = $statement->execute();

        if ($result) {
            $count = $statement->rowCount();

            $statement = $pdo->prepare("DELETE FROM " . $type . "_items WHERE oi_id = '$oi_id'");
            $result = $statement->execute();

            if ($count == 1) {
                $statement = $pdo->prepare("DELETE FROM " . $type . "s WHERE oid = '$oid'");
                $result = $statement->execute();
            }

            if ($result) {
                $pdo->commit();
                res(0, "Erfolgreich");
            } else {
                throw new Exception($statement->errorInfo());
            }
        } else {
            throw new Exception($statement->errorInfo());
        }
    } catch (Execption $e) {
        $pdo->rollBack();
        res(1, $e->getMessage());
    }
}

if (isset($_POST['mark-delivered'])) {
    $oi_id = $_POST['oi_id'];
    try {
        $pdo->beginTransaction();
        $statement = $pdo->prepare("SELECT delivered FROM order_items WHERE oi_id = '$oi_id'");
        $result = $statement->execute();

        if (!$result) {
            throw new Exception($statement->errorInfo());
        } else {
            $row = $statement->fetch();
            if ($row['delivered'] == 1) {
                res(1, "Bereits markiert.");
            }
        }

        $statement = $pdo->prepare("UPDATE order_items SET delivered = 1 WHERE oi_id = '$oi_id'");
        $result = $statement->execute();

        if ($result) {
            $statement = $pdo->prepare("SELECT * FROM order_items WHERE oi_id = '$oi_id'");
            $result = $statement->execute();
            if ($result) {
                while ($row = $statement->fetch()) {
                    $quantity = $row['quantity'];
                    $pid = $row['pid'];

                    $statement2 = $pdo->prepare("UPDATE inventory_items SET quantity_KG_L = CASE WHEN quantity_KG_L - '$quantity' >= 0 THEN quantity_KG_L - '$quantity' ELSE quantity_KG_L END WHERE pid = '$pid'");
                    $result2 = $statement2->execute();

                    if (!$result2) {
                        throw new Exception($statement2->errorInfo());
                    }
                }
            } else {
                throw new Exception($statement->errorInfo());
            }
            $pdo->commit();
            res(0, "Erfolgreich.");
        } else {
            throw new Exception($statement->errorInfo());
        }
    } catch (Exception $e) {
        $pdo->rollBack();
        res(1, $e->getMessage());
    }
}
res(1, "Keine Daten übermittelt");
