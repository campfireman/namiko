<?php
session_start();
require_once("inc/config.inc.php");
require_once("inc/functions.inc.php");
//ini_set('display_errors', 1);

//Überprüfe, dass der User eingeloggt ist
//Der Aufruf von check_user() muss in alle internen Seiten eingebaut sein
$user = check_user();

include("templates/header.inc.php");
include("templates/nav.inc.php");
include("templates/settings-nav.inc.php");

if(isset($_POST['save'])) {
    $passwordOld = $_POST['passwordOld'];
    $passwordNew = trim($_POST['passwordNew']);
    $passwordNew2 = trim($_POST['passwordNew2']);

    if($passwordNew != $passwordNew2) {
        $_SESSION['notification'] = true;
        $_SESSION['notificationmsg'] = 'Die angegebenen Passwörter stimmen nicht überein.';
        header("Location: " . $_SERVER['PHP_SELF']);
    } else if($passwordNew == "") {
        $_SESSION['notification'] = true;
        $_SESSION['notificationmsg'] = 'Bitte ein Passwort angeben.';
        header("Location: " . $_SERVER['PHP_SELF']);
    } else if(!password_verify($passwordOld, $user['password'])) {
        $_SESSION['notification'] = true;
        $_SESSION['notificationmsg'] = 'Bitte das korrekte, aktuelle Passwort angeben.';
        header("Location: " . $_SERVER['PHP_SELF']);
    } else {
        $password_hash = password_hash($passwordNew, PASSWORD_DEFAULT);
            
        $statement = $pdo->prepare("UPDATE users SET password = :password WHERE uid = :uid");
        $result = $statement->execute(array('password' => $password_hash, 'uid' => $user['uid'] ));
           
        if ($result) { 
            $_SESSION['notification'] = true;
            $_SESSION['notificationmsg'] = 'Passwort erfolgreich geändert.';
            header("Location: " . $_SERVER['PHP_SELF']);
        } else {
            $_SESSION['notification'] = true;
            $_SESSION['notificationmsg'] = 'Beim Abspeichern gab es einen Fehler.';
            header("Location: " . $_SERVER['PHP_SELF']);
        }
    }
}

?>
<div class="sizer spacer">
    <p><i class="fa fa-info-circle" aria-hidden="true"></i> Zum Änderen deines Passworts gib bitte dein aktuelles Passwort sowie das neue Passwort ein.</p><br>
    <form action="<?php htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post" class="form">
        <div class="row">
            <label for="inputPasswort" class="col-sm-2">Altes Passwort</label>
            <div class="col-sm-10">
                <input id="inputpasswordOld" name="passwordOld" type="password" required>
            </div>
        </div><br>
        
        <div class="row">
            <label for="inputpasswordNew" class="col-sm-2">Neues Passwort</label>
            <div class="col-sm-10">
                <input id="inputpasswordNew" name="passwordNew" type="password" required>
            </div>
        </div><br>
        
        
        <div class="row">
            <label for="inputpasswordNew2" class="col-sm-2">Neues Passwort (wiederholen)</label>
            <div class="col-sm-10">
                <input id="inputpasswordNew2" name="passwordNew2" type="password"  required>
            </div>
        </div><br>
        
        <div class="row">
            <div class="col-sm-offset-2 col-sm-10">
              <button type="submit" name="save" class="clean-btn green"><i class="fa fa-floppy-o" aria-hidden="true"></i> Speichern</button>
            </div>
        </div>
    </form>
</div>
<?php 
include("templates/footer.inc.php")
?>
