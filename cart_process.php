<?php
session_start(); //start session
ini_set('display_errors', 1);
require_once "inc/config.inc.php"; //include config file
require_once "inc/functions.inc.php";
require_once "inc/Cart.inc.php";

$user = check_user();

if (isset($_POST["pid"])) {
    foreach ($_POST as $key => $value) {
        $new_item[$key] = filter_var($value, FILTER_SANITIZE_STRING); //create a new product array
    }

    try {
        $statement = $pdo->prepare("SELECT products.* FROM products WHERE pid=:pid");
        $statement->bindParam(':pid', $new_item['pid']);
        $result = $statement->execute();

        if (!$result) {
            throw new Exception(json_encode($statement->errorInfo()));
        }

        $row = $statement->fetch();
        $new_item["productName"] = $row['productName'];
        $new_item["price_KG_L"] = $row['price_KG_L'];
        $new_item['pro_id'] = $row['producer'];
        $new_item['unit_size'] = $row['unit_size'];
        $new_item['unit_tag'] = $row['unit_tag'];
        $cart = new Cart();
        $cart->insert($new_item);
    } catch (Exception $e) {
        error($e->getMessage());
    }

    die(json_encode(array('items' => cartCount()))); //output json
}

if (isset($_POST["load_cart"]) && $_POST["load_cart"] == 1) {
    if (cartCount() > 0) { //if we have session variable
        $_SESSION['orders'] = empty($_SESSION['orders']) ? null : $_SESSION['orders'];
        $_SESSION['preorders'] = empty($_SESSION['preorders']) ? null : $_SESSION['preorders'];
        $html = "";
        try {
            $cart = new Cart();
            $html .= $cart->buildHTML($_SESSION['orders'], $_SESSION['preorders'], true);
        } catch (Exception $e) {
            error($e->getMessage());
        }

        if (isset($_POST['pay'])) {
            $html .= '
                <div class="box pad">
                  <form action="pay.php" method="post">
                  <label><input type="checkbox" name="agree1" required> Ich bin damit einverstanden, dass ein Betrag von ' . sprintf("%01.2f %s", $cart->getGrandtotal(), $currency) . ' von meinem Konto abgebucht wird, entsprechend der Bedingungen des vereinbarten Lastschriftmandates.</label><br>
                  <br>
                  <div class="center"><button class="clean-btn green" type="submit" name="pay" required>abschicken <i class="fa fa-paper-plane" aria-hidden="true"></i></button></div>
                  </form>
                  </div>';
        } else {
            $html .= '
            <br>
            <input id="grandtotal_val" type="hidden" name="grandtotal" value="' . $cart->getGrandtotal() . '">
            <div id="grandtotal" class="emph">ges. ' . sprintf("%01.2f %s", $cart->getGrandtotal(), $currency) . '
            </div>
            <a href="check-out.php" title="Zur Kasse gehen">
            <button class="clean-btn green">Zur Kasse <i class="fa fa-arrow-circle-o-right" aria-hidden="true"></i></button>
            </a>
            </div>';
        }
        die($html); //exit and output content
    } else {
        die("Dein Warenkorb ist leer."); //we have empty cart
    }
}

if (isset($_GET["remove_code"])) {
    $pid = filter_var($_GET["remove_code"], FILTER_SANITIZE_STRING); //get the product code to remove
    $type = filter_var($_GET["type"], FILTER_SANITIZE_STRING);
    Cart::delete($type, $pid);

    die(json_encode(array('items' => cartCount())));
    //die(json_encode($_SESSION['orders']));

}
