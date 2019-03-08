<?php
session_start(); //start session
//ini_set('display_errors', 1);
require_once("inc/config.inc.php"); //include config file

if (isset($_GET["pro_id"])) {
    $pro_id   = filter_var($_GET["pro_id"], FILTER_SANITIZE_STRING); //get the product code to remove
    
    $statement = $pdo->prepare("SELECT * FROM producers WHERE pro_id = :pro_id");
    $statement->bindParam(':pro_id', $pro_id);
    $result = $statement->execute();

    if ($result) {
    	if ($statement->rowCount() > 0) {
    		$json = '<div class="sizer spacer">';
    		while ($row = $statement->fetch()) {
    			$json .= '
    					<div><h1>'. $row['producerName'] .'</h1></div><br>
    					<span>'. $row['description'] .'</span>
    					</div>';
    		}
    	} else {
    		$json = 'Kein Hersteller gefunden.';
    	}
    } else {
    	$json = 'Query nicht erfolgreich.';
    }
    die(json_encode($json));
}
?>