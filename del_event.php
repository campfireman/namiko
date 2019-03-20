<?php
session_start();
require_once("inc/config.inc.php");
ini_set('display_errors', 1);

if (isset($_POST['eid'])) {
	$eid = $_POST['eid'];

	$statement = $pdo->prepare("DELETE FROM events WHERE eid ='$eid'");
	$result = $statement->execute();

	if ($result) $json = 1;
	else $json = 0;
	die(json_encode($json));
}
?>