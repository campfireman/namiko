<?php
session_start();
require_once("inc/config.inc.php");
ini_set('display_errors', 1);

$startTimestamp = $_GET['start'];
$endTimestamp = $_GET['end'];


$statement = $pdo->prepare("SELECT events.type AS id, events.start, events.end, event_types.color AS backgroundColor, event_types.name AS title FROM events LEFT JOIN event_types ON events.type = event_types.tyid WHERE start >= '$startTimestamp' AND end < '$endTimestamp' ORDER BY start ASC");
$statement->execute();
$results = $statement->fetchAll();

die(json_encode($results));
?>