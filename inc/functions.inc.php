<?php

include_once("password.inc.php");

/**
 * Checks that the user is logged in. 
 * @return Returns the row of the logged in user
 */
function check_user() {
	global $pdo;
	
	if(!isset($_SESSION['userid']) && isset($_COOKIE['identifier']) && isset($_COOKIE['securitytoken'])) {
		$identifier = $_COOKIE['identifier'];
		$securitytoken = $_COOKIE['securitytoken'];
		
		$statement = $pdo->prepare("SELECT * FROM securitytokens WHERE identifier = ?");
		$result = $statement->execute(array($identifier));
		$securitytoken_row = $statement->fetch();
	
		if(sha1($securitytoken) !== $securitytoken_row['securitytoken']) {
			//Vermutlich wurde der Security Token gestohlen
			//Hier ggf. eine Warnung o.ä. anzeigen
			
		} else { //Token war korrekt
			//Setze neuen Token
			$neuer_securitytoken = random_string();
			$insert = $pdo->prepare("UPDATE securitytokens SET securitytoken = :securitytoken WHERE identifier = :identifier");
			$insert->execute(array('securitytoken' => sha1($neuer_securitytoken), 'identifier' => $identifier));
			setcookie("identifier",$identifier,time()+(3600*24*365)); //1 Jahr Gültigkeit
			setcookie("securitytoken",$neuer_securitytoken,time()+(3600*24*365)); //1 Jahr Gültigkeit
	
			//Logge den Benutzer ein
			$_SESSION['userid'] = $securitytoken_row['user_id'];
		}
	}
	
	
	if(!isset($_SESSION['userid'])) {
		die('Bitte zuerst <a href="login.php">einloggen</a>');
	}
	

	$statement = $pdo->prepare("SELECT * FROM users WHERE uid = :uid");
	$result = $statement->execute(array('uid' => $_SESSION['userid']));
	$user = $statement->fetch();
	return $user;


}

function check_admin () {
	global $user;
	if ($user['rights'] < 3) {
		die('Dir fehlen die nötigen Rechte, um diese Seite einzusehen. <a href="internal.php">Zurück zur Hauptseite.</a>');
	}
}

function check_consul () {
	global $user;
	if ($user['rights'] < 4) {
		die('Dir fehlen die nötigen Rechte, um diese Seite einzusehen. <a href="internal.php">Zurück zur Hauptseite.</a>');
	}
}

/**
 * Returns true when the user is checked in, else false
 */
function is_checked_in() {
	return isset($_SESSION['userid']);
}
 
/**
 * Returns a random string
 */
function random_string() {
	if(function_exists('openssl_random_pseudo_bytes')) {
		$bytes = openssl_random_pseudo_bytes(16);
		$str = bin2hex($bytes); 
	} else if(function_exists('mcrypt_create_iv')) {
		$bytes = mcrypt_create_iv(16, MCRYPT_DEV_URANDOM);
		$str = bin2hex($bytes); 
	} else {
		//Replace your_secret_string with a string of your choice (>12 characters)
		$str = md5(uniqid('your_secret_string', true));
	}	
	return $str;
}

/**
 * Returns the URL to the site without the script name
 */
function getSiteURL() {
	$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
	return $protocol.$_SERVER['HTTP_HOST'].dirname($_SERVER['PHP_SELF']).'/';
}

/**
 * Outputs an error message and stops the further exectution of the script.
 */
function error($error_msg, $location=null, $hide = false) {
	global $debug;

	if (!$debug) {
		$hide = true;
	}

	if ($hide) {
		$error_msg = "Ein Fehler ist aufgetreten";
	}
	logErr($error_msg);
	notify($error_msg, $location);
	
}

function logErr($error_msg) {
	global $error_log;

	$date = new DateTime();
	$user = check_user();

	$log = sprintf("%s | uid:%s | msg: %s\n" ,$date->format("Y/m/d H:i:s"), $user['uid'], $error_msg ."\n");
	error_log($log, 3, $error_log);
} 

function clean($inhalt='') { //makes sure there's no executable code 
    $inhalt = trim($inhalt);
    $inhalt = htmlentities($inhalt, ENT_QUOTES, "UTF-8");
    return($inhalt);
}

function notify($msg, $location=null) {
	$location = isset($location) ? $location : $_SERVER['PHP_SELF'];
	$_SESSION['notification'] = true;
	$_SESSION['notificationmsg'] = $msg;
	header('Location: '. $location);
}

function res($code, $text) {
	die(json_encode(array('error' => $code, 'text' => $text)));
}

function maskString($string, $start) {
	return substr_replace($string, str_repeat("X", strlen($string)-$start), $start);
}

function cartCount () {
    $total_items = 0;
    if (!empty($_SESSION['orders'])) {
        foreach($_SESSION['orders'] as $orders) {
            $total_items += count($orders); //count total items
        }
    }

    if (!empty($_SESSION['preorders'])) {
        foreach($_SESSION['preorders'] as $preorders) {
            $total_items += count($preorders); //count total items
        }
    }
    return $total_items;
}

function round_up ( $value, $precision ) { 
    $pow = pow ( 10, $precision ); 
    return ( ceil ( $pow * $value ) + ceil ( $pow * $value - ceil ( $pow * $value ) ) ) / $pow; 
}
?>