<?php
session_start(); //start session
//ini_set('display_errors', 1);
require_once("inc/config.inc.php"); //include config file
require_once('inc/functions.inc.php');
require_once('inc/Cart.inc.php');
ini_set('display_errors', 1);

$orders = [];
function insertOrder($item) {
    global $orders;
    $uid = $item['uid'];
    $pro_id = $item['producer'];
    $pid = $item['pid'];

    if (array_key_exists($uid, $orders)) {
        if (array_key_exists($pid, $orders[$uid][$pro_id])) {
           $orders[$uid][$pro_id][$pid]['quantity'] += $item['quantity'];
        } else {
            $orders[$uid][$pro_id][$pid] = $item;
        }
    } else {
        $orders[$uid][$pro_id][$pid] = $item;
    }
}

############# add products to session #########################
if(isset($_POST["pid"])) {
    foreach ($_POST['pid'] as $pos => $pid) {
        $pid =  filter_var($pid, FILTER_SANITIZE_STRING);
        $new_total['pid'] = $pid;

        $statement = $pdo->prepare("SELECT productName, container, priceContainer, producer, is_storage_item FROM products WHERE pid=:pid");
        $result = $statement->execute(array('pid' => $pid));

        if (!$result) {
            res(1, json_encode($statement->errorInfo()));
        }

        while($row = $statement->fetch()){ 
            $new_total["productName"] = $row['productName']; //fetch product name from database
            $new_total["priceContainer"] = $row['priceContainer']; 
            $pro_id = $row['producer'];
            $new_total["container"] = $row['container']; //fetch product price from database
            $new_total["quantityContainer"] = $_POST['quantityContainer'][$pos];
            $new_total['is_storage_item'] = $row['is_storage_item'];
            
            if(isset($_SESSION["total"])){  //if session var already exist
                if(isset($_SESSION["total"][$pro_id][$pid])) //check item exist in products array
                {
                    unset($_SESSION["total"][$pro_id][$pid]); //unset old item
                }           
            }
            
            $_SESSION["total"][$pro_id][$pid] = $new_total; //update products with new item array   
        }
    }
    res(0, "Success"); //output json 
}

################## list products in cart ###################
if(isset($_POST["load_cart"]) && $_POST["load_cart"] == 1) {

    if(isset($_SESSION["total"]) && count($_SESSION["total"]) > 0) { //if we have session variable
       $count = 0;
       $cart_box = "";
        foreach ($_SESSION['total'] as $pro_id => $order_item) {

            $statement = $pdo->prepare("SELECT producerName FROM producers WHERE pro_id = '$pro_id'");
            $result = $statement->execute();
            $producerName = $statement->fetch();

            $count++;
            $item_count = count($_SESSION["total"]);

            if ($item_count > 1) {
                if ($count == 1) {
                    $cart_box .= '<div class="row">';
                }
            }

            if ($item_count > 1) {
                $cart_box .= '<div class="col-md-6">';
            }

            $cart_box .= '  
                            <div class="spacer">
                            <span class="subtitle3">'. $producerName['producerName'] .'</span><br><br>
                            <table class="cartTable">
                                <tr>
                                    <th>Artikel</th>
                                    <th>Preis Gebinde</th>
                                    <th>Menge</th><th>&#931;</th>
                                </tr>';
            $total = 0;
            foreach($order_item as $product) { //loop though items and prepare html content
                
                //set variables to use them in HTML content below
                $productName = $product["productName"]; 
                $is_storage_item = $product['is_storage_item'];
                $priceContainer = $product["priceContainer"];
                $pid = $product["pid"];
                $quantity = $product["quantityContainer"];
                $item_total = ($priceContainer * $quantity);
                
                $cart_box .=  ' <tr>
                                    <td>'. $productName .'</td>
                                    <td>'. $currency . sprintf("%01.2f", $priceContainer) .'</td>
                                    <td>'. $quantity .'</td>
                                    <td><input type="hidden" name="item_total" value="'. $item_total .'">'. $currency . sprintf("%01.2f", $item_total). '</td>
                                    <td><a href="#" class="remove-item" data-pid="'. $pid. '" data-pro_id="'. $pro_id .'"><i class="fa fa-trash-o" aria-hidden="true"></i></a></td>
                                </tr>';

                $subtotal = ($priceContainer * $quantity);
                $total = ($total + $subtotal);
            }
            $cart_box .= '  <tr>
                                <td></td><td></td><td></td>
                                <td class="emph">
                                    <input type="hidden" name="total" value="'. $total .'">'. $currency. '
                                    <span id="total">'.sprintf("%01.2f",$total).'</span>
                                </td>
                            </tr>
                            </table>
                            </div>';
                            if ($item_count > 1) {
                                $cart_box .= '</div>';
                            }
            if ($item_count > 1) {
                if ($count == 2) {
                    $count = 0;
                    $cart_box .= '</div>';
                }
            }

        } 

        $cart_box .= '  <br>
                        </div>';
    
        $cart_box.= '   </div>
                        <div class="right spacer">
                            <form action="//'. $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) .'/inventory.php" method="post">
                                <button type="submit" class="clean-btn green" name="checkOut">Bestellung verbuchen <i class="fa fa-arrow-circle-o-right" aria-hidden="true"></i></button>
                            </form>
                        </div>
                        <br><br>';

        die($cart_box); //exit and output content
    } else {
        die("Keine offene Bestellung gefunden."); //we have empty cart
    }

}

################# remove item from shopping cart ################
if(isset($_GET["remove_pid"]) && isset($_SESSION["total"])) {
    $pid   = filter_var($_GET["remove_pid"], FILTER_SANITIZE_STRING); //get the product code to remove
    $pro_id   = filter_var($_GET["pro_id"], FILTER_SANITIZE_STRING); //get the product code to remove

    if(isset($_SESSION["total"][$pro_id][$pid])) {
        unset($_SESSION["total"][$pro_id][$pid]);

        if (count($_SESSION["total"][$pro_id]) == 0) {
            unset($_SESSION["total"][$pro_id]);
        }
    }
    
    die(json_encode(1));

}

if (isset($_POST['delivered'])) {
    $tid = $_POST['tid'];
    try {
        
        $db->lock(["order_total", "order_total_items", "inventory_items", "preorders", "preorder_items", "orders", "order_items", "products"], "WRITE");
        $pdo->beginTransaction();

        $statement = $pdo->prepare("SELECT delivered FROM order_total WHERE tid = '$tid'");
        $result = $statement->execute();
        $result = $statement->fetch();

        if ($result['delivered'] == 1) {
            res(1, "Bereits, als bezahl markiert");
        }

        $statement = $pdo->prepare("UPDATE order_total SET delivered = 1 WHERE tid = '$tid'");
        $result = $statement->execute();

        $statement = $pdo->prepare("SELECT pid, container, quantityContainer FROM order_total_items WHERE tid = '$tid'");
        $result = $statement->execute();

        while ($row = $statement->fetch()) {
            $pid = $row['pid'];
            $container = $row['container'];
            $quantityContainer = $row['quantityContainer'];
            $totalQuantity = ($quantityContainer * $container);

            $statement2 = $pdo->prepare("UPDATE inventory_items SET quantity_KG_L = (quantity_KG_L + '$totalQuantity') WHERE pid = '$pid'");
            $result2 = $statement2->execute();

            if (!$result2) {
                throw new Exception(json_encode($statement2->errorInfo()));
            }

            $sum = 0;

            $statement2 = $pdo->prepare("
                SELECT preorder_items.*, preorders.uid, products.*
                FROM preorder_items 
                LEFT JOIN preorders 
                ON preorders.oid = preorder_items.oid 
                LEFT JOIN products
                ON preorder_items.pid = products.pid
                WHERE preorder_items.transferred = 0 
                AND preorder_items.pid = '$pid'
                ORDER BY preorders.created_at ASC");

            $result2 = $statement2->execute();

            if (!$result2) {
                throw new Exception(json_encode($statement2->errorInfo()));
            }

            while ($preorder = $statement2->fetch()) {
                $quantity = $preorder['quantity'];
                $uid = $preorder['uid'];
                $oi_id = $preorder['oi_id'];
                $pro_id = $preorder['producer'];
                $pid = $preorder['pid'];
                $curSum = $sum + $quantity;

                if ($curSum <= $totalQuantity) {
                    $sum += $quantity;
                    insertOrder($preorder);
                    $db->markTransferred($oi_id);

                    if ($curSum == $totalQuantity) {
                        break;
                    }
                } else {
                    $preorder['quantity'] = $totalQuantity - $sum;
                    $db->updatePreorderItem($oi_id, $curSum - $totalQuantity);
                    insertOrder($preorder);
                    break;
                }
            }
        }

        foreach ($orders as $uid => $order) {
            $cart = new Cart();
            $user = $db->getUser($uid);
            $cart->process($uid, $order);
            $cart->mail($user, $smtp_host, $smtp_username, $smtp_password, $myEmail, $myEntity, false, false);
        }

        $pdo->commit();
        $db->unlock();
        res(0, "Erfolgreich");

    } catch (Exception $e) {
        $pdo->rollBack();
        logErr($e->getMessage());
        res(1, $e->getMessage());
    }
}

if (isset($_POST['paid'])) {
    $tid = $_POST['tid'];

    $statement = $pdo->prepare("SELECT paid FROM order_total WHERE tid = '$tid'");
    $result = $statement->execute();
    $result = $statement->fetch();

    if ($result['paid'] == 0) {

        $statement = $pdo->prepare("UPDATE order_total SET paid = 1 WHERE tid = '$tid'");
        $result = $statement->execute();

        if ($result) {
            res(0, "Erfolgreich.");
        } else {
            res(1, "Fehler");
        }
    } else {
        res(1, "Bereits als bezahlt markiert.");
    }
}

if (isset($_GET['remove-order-total-item'])) {
    $oti_id = $_GET['oti_id'];
    $tid = $_GET['tid'];

    try {
        $pdo->beginTransaction();
        $statement = $pdo->prepare("SELECT * FROM order_total_items WHERE tid = :tid");
        $statement->bindParam('tid', $tid);
        $result = $statement->execute();

        if ($result) {
            $count = $statement->rowCount();
            
            $statement = $pdo->prepare("DELETE FROM order_total_items WHERE oti_id = :oti_id");
            $statement->bindParam('oti_id', $oti_id);
            $result = $statement->execute();

            if ($count == 1) {
                $statement = $pdo->prepare("DELETE FROM order_total WHERE tid = :tid");
                $statement->bindParam('tid', $tid);
                $result = $statement->execute();
            }

            if ($result) {
                $pdo->commit();
                res(0, "Erfolgreich");
            } else {
                throw new Exception(json_encode($statement->errorInfo()));
            }
        } else {
            throw new Exception(json_encode($statement->errorInfo()));
        }
    } catch (Execption $e) {
        $pdo->rollBack();
        res(1, $e->getMessage());
    }
}
?>