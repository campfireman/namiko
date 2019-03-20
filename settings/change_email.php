<?php
session_start();
require_once("../inc/config.inc.php");
require_once("../inc/functions.inc.php");
//ini_set('display_errors', 1);

//Überprüfe, dass der User eingeloggt ist
//Der Aufruf von check_user() muss in alle internen Seiten eingebaut sein
$user = check_user();

include("../templates/header.inc.php");
include("../templates/nav.inc.php");
include("../templates/settings-nav.inc.php");

if(isset($_POST['save'])) {
    $password = $_POST['password'];
    $email = trim($_POST['email']);
    $email2 = trim($_POST['email2']);
    
    if($email != $email2) {
        $_SESSION['notification'] = true;
        $_SESSION['notificationmsg'] = 'Die angegebenen Adressen stimmen nicht überein.';
        header("Location: " . $_SERVER['REQUEST_URI']);
    } else if(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['notification'] = true;
        $_SESSION['notificationmsg'] = 'Bitte eine gültige E-Mail-Adresse eingeben.';
        header("Location: " . $_SERVER['REQUEST_URI']);
    } else if(!password_verify($password, $user['password'])) {
        $_SESSION['notification'] = true;
        $_SESSION['notificationmsg'] = 'Bitte korrektes Passwort eingeben.';
        header("Location: " . $_SERVER['REQUEST_URI']);
    } else {
        $statement = $pdo->prepare("UPDATE users SET email = :email WHERE uid = :uid");
        $result = $statement->execute(array('email' => $email, 'uid' => $user['uid'] ));
            
        if ($result) { 
            $_SESSION['notification'] = true;
            $_SESSION['notificationmsg'] = 'E-Mail erfolgreich geändert.';
            header("Location: " . $_SERVER['REQUEST_URI']);
        } else {
            $_SESSION['notification'] = true;
            $_SESSION['notificationmsg'] = 'Beim Abspeichern gab es einen Fehler.';
            header("Location: " . $_SERVER['REQUEST_URI']);
        }
    }
}

?>
<div class="sizer spacer">
    <p><i class="fa fa-info-circle" aria-hidden="true"></i> Zum Änderen deiner E-Mail-Adresse gib bitte dein aktuelles Passwort sowie die neue E-Mail-Adresse ein.</p><br>
    <form action="<?php htmlspecialchars($_SERVER['REQUEST_URI']); ?>" method="post" class="form">
        <div class="row">
            <label for="inputPasswort" class="col-sm-2 control-label">Passwort</label>
            <div class="col-sm-10">
                <input id="inputPasswort" name="password" type="password" required>
            </div>
        </div><br>
        
        <div class="row">
            <label for="inputEmail" class="col-sm-2 control-label">E-Mail</label>
            <div class="col-sm-10">
                <input id="inputEmail" name="email" type="email" value="<?php echo htmlentities($user['email']); ?>" required>
            </div>
        </div><br>
        
        
        <div class="row">
            <label for="inputEmail2" class="col-sm-2 control-label">E-Mail (wiederholen)</label>
            <div class="col-sm-10">
                <input id="inputEmail2" name="email2" type="email"  required>
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
include("../templates/footer.inc.php")
?>
