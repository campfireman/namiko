<?php
session_start(); //start session
ini_set('display_errors', 1);
require_once("inc/config.inc.php"); //include config file

function cartCount () {
    $total_items = 0;
    foreach($_SESSION['products'] as $producer) {
        $total_items += count($producer); //count total items
    }
    return $total_items;
}

if (isset($_POST["pid"])) {
    foreach($_POST as $key => $value){
        $new_product[$key] = filter_var($value, FILTER_SANITIZE_STRING); //create a new product array 
    }
    
    $selector = $new_product['pid'];
    $statement = $pdo->prepare("SELECT productName, price_KG_L, producer FROM products WHERE pid='$selector'");
    $statement->execute();

    while($row = $statement->fetch()){ 
        $new_product["productName"] = $row['productName']; //fetch product name from database
        $new_product["price_KG_L"] = $row['price_KG_L'];  //fetch product price from database
        
        if (isset($_SESSION["products"][$row['producer']])) {
            if (isset($_SESSION["products"][$row['producer']][$_POST['pid']])) {
                unset($_SESSION["products"][$row['producer']][$_POST['pid']]); //unset old item
            }

        }
        $_SESSION["products"][$row['producer']][$new_product['pid']] = $new_product; //update products with new item array   
    }
    die(json_encode(array('items' => cartCount()))); //output json 
}

if (isset($_POST["load_cart"]) && $_POST["load_cart"] == 1) {
    if (isset($_SESSION["products"]) && cartCount() > 0) { //if we have session variable
        $cart_box = "";
        $grandtotal = 0;
        foreach ($_SESSION['products'] as $pro_id => $producer) {
            $statement = $pdo->prepare("SELECT producerName FROM producers WHERE pro_id = '$pro_id'");
            $statement->execute();
            $row = $statement->fetch();

            $cart_box .= '<span class="subtitle2">'. $row['producerName'] .'</span>
                        <div class="center spacer3">
                            <table class="cartTable">
                                <tr>
                                    <th>Artikel</th>
                                    <th>Preis KG/L</th>
                                    <th>Menge</th><th>&#931;</th>
                                </tr>';
            $total = 0;
            foreach ($producer as $product) { //loop though items and prepare html content
                
                //set variables to use them in HTML content below
                $productName = $product["productName"]; 
                $price_KG_L = $product["price_KG_L"];
                $pid = $product["pid"];
                $quantity = $product["quantity"];
                $item_total = ($price_KG_L * $quantity);
                
                $cart_box .=  ' <tr>
                                <td>'. $productName .'</td>
                                <td>'. $currency. sprintf("%01.2f", $price_KG_L) .'</td>
                                <td>'. $quantity .'</td>
                                <td><input type="hidden" name="item_total" value="'. $item_total .'">'. $currency . sprintf("%01.2f", $item_total). '</td>
                                <td><a href="#" class="remove-item" data-code="'. $pid. '"><i class="fa fa-trash-o" aria-hidden="true"></i></a></td>
                                </tr>';
                $subtotal = ($price_KG_L * $quantity);
                $total = ($total + $subtotal);
                $grandtotal += $total;
            }
            $cart_box .= '  <tr>
                            <td></td><td></td><td></td>
                            <td class="emph">
                                <input type="hidden" name="total" value="'. $total .'">'. $currency .'
                                <span id="total">'.sprintf("%01.2f", $total).'</span>
                            </td>
                            </tr>
                            </table>
                            </div>';
            
        }
        if (isset($_POST['pay'])) {
            $cart_box .= '
                <div class="box pad">
                  <form action="pay.php" method="post">
                  <label><input type="checkbox" name="agree1" required> Ich bin damit einverstanden, dass ein Betrag von '.$currency.sprintf("%01.2f",$grandtotal).' von meinem Konto abgebucht wird, entsprechend der Bedingungen des vereinbarten Lastschriftmandates.</label><br>
                  <br>
                  <div class="center"><button class="clean-btn green" type="submit" name="pay" required>abschicken <i class="fa fa-paper-plane" aria-hidden="true"></i></button></div>
                  </form>
                  </div>
            ';
        } else {
            $cart_box .= '  
            <br>
            <div>
            <div class="emph">ges. '.$currency.sprintf("%01.2f",$grandtotal).'</div>
                <a href="check-out.php" title="Zur Kasse gehen">
                    <button class="clean-btn green">Zur Kasse <i class="fa fa-arrow-circle-o-right" aria-hidden="true"></i></button>
                </a>
            </div>';
        }
            die($cart_box); //exit and output content
    }else{
        die("Dein Warenkorb ist leer."); //we have empty cart
    }
}

################# remove item from shopping cart ################
if(isset($_GET["remove_code"]) && isset($_SESSION["products"]))
{
    $pid   = filter_var($_GET["remove_code"], FILTER_SANITIZE_STRING); //get the product code to remove

    if(isset($_SESSION["products"][$pid]))
    {
        unset($_SESSION["products"][$pid]);
    }
    
    $total_items = count($_SESSION["products"]);
    die(json_encode(array('items'=>$total_items)));

}
?>