<?php

session_start();
require_once("inc/config.inc.php");
require_once("inc/functions.inc.php");

//Überprüfe, dass der User eingeloggt ist
//Der Aufruf von check_user() muss in alle internen Seiten eingebaut sein
$user = check_user();

include("templates/header.inc.php");
include("templates/nav.inc.php");

if (isset($_SESSION["products"]) && count($_SESSION["products"]) > 0 ) {
    $cart_box       = '<h3 class="header spacer">Deine Bestellung</h3>
                       <div class="center pad">
                       <table class="cartTable"><tr><th>Artikel</th><th>Preis KG/L</th><th>Menge</th><th>&#931;</th></tr>';

    foreach($_SESSION["products"] as $product){ //Print each item, quantity and price.
        $productName = $product["productName"]; 
        $price_KG_L = $product["price_KG_L"];
        $pid = $product["pid"];
        $quantity = $product["quantity"];
        
        $cart_box       .=  '<tr><td>'. $productName .'</td><td>'. $currency. sprintf("%01.2f", ($price_KG_L)) .'</td><td>'. $quantity .'</td><td>'.$currency. sprintf("%01.2f", ($price_KG_L * $quantity)). '</td><td><a href="#" class="remove-item" data-code="'. $pid. '"><i class="fa fa-trash-o" aria-hidden="true"></i></a></td></tr>';
        
        $subtotal       = ($price_KG_L * $quantity); //Multiply item quantity * price
        $total          = ($total + $subtotal); //Add up to total price
    }
    
    //Total
   $cart_box .= '<tr><td></td><td></td><td></td><td class="emph">'.$currency.sprintf("%01.2f",$total).' </td></table></div>
                  <div class="box pad">
                  <form action="pay.php" method="post">
                  <label><input type="checkbox" name="agree1" required> Ich bin damit einverstanden, dass ein Betrag von '.$currency.sprintf("%01.2f",$total).' von meinem Konto abgebucht wird, entsprechend der Bedingungen des vereinbarten Lastschriftmandates.</label><br>
                  <br>
                  <div class="center"><button class="clean-btn green" type="submit" name="pay" required>abschicken <i class="fa fa-paper-plane" aria-hidden="true"></i></button></div>
                  </form>
                  </div>';
    
    echo $cart_box;
}else{
    echo "Dein Warenkorb ist leer!";
}


include("templates/footer.inc.php")
?>