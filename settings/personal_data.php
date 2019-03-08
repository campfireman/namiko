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
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    
    if($first_name == "" || $last_name == "") {
        $_SESSION['notification'] = true;
        $_SESSION['notificationmsg'] = 'Bitte alle Felder ausfüllen.';
        header("Location: " . $_SERVER['REQUEST_URI']);
    } else {
        $statement = $pdo->prepare("UPDATE users SET first_name = :first_name, last_name = :last_name, updated_at=NOW() WHERE uid = :uid");
        $result = $statement->execute(array('first_name' => $first_name, 'last_name'=> $last_name, 'uid' => $user['uid'] ));
        
         if ($result) { 
            $_SESSION['notification'] = true;
            $_SESSION['notificationmsg'] = 'Daten erfolgreich geändert.';
            header("Location: " . $_SERVER['REQUEST_URI']);
        } else {
            $_SESSION['notification'] = true;
            $_SESSION['notificationmsg'] = 'Beim Abspeichern gab es einen Fehler.';
            header("Location: " . $_SERVER['REQUEST_URI']);
        }
    }
}

?>
<div class="sizer spacer row">
    <div class="col-sm-6">
    <form action="<?php htmlspecialchars($_SERVER['REQUEST_URI']); ?>" method="post" class="form">
        <div class="row">
            <label for="inputFirstName" class="col-sm-2">Vorname</label>
            <div class="col-sm-10">
                <input id="inputFirstName" name="first_name" type="text" value="<?php echo htmlentities($user['first_name']); ?>" required>
            </div>
        </div><br>
        
        <div class="row">
            <label for="inputLastName" class="col-sm-2">Nachname</label>
            <div class="col-sm-10">
                <input id="inputLastName" name="last_name" type="text" value="<?php echo htmlentities($user['last_name']); ?>" required>
            </div>
        </div><br>
        
        <div class="row">
            <div class="col-sm-offset-2 col-sm-10">
              <button type="submit" name="save" class="clean-btn green"><i class="fa fa-floppy-o" aria-hidden="true"></i> Speichern</button>
            </div>
        </div>
    </form>
    </div><br><br>

    <div class="col-sm-6" style="max-width: 700px">
        <span class="subtitle2">Diese Daten haben wir gespeichert:</span><br><br>
        <table style="width: 100%;">
        <?php
        $uid = $user['uid'];
        $statement = $pdo->prepare("SELECT users.*, mandates.ip FROM users LEFT JOIN mandates ON users.uid = mandates.uid WHERE users.uid = '$uid'");
        $result = $statement->execute();

        while ($row = $statement->fetch()) {
            echo "<tr>";
            echo    "<th>PLZ</th>";
            echo    "<td>". $row['postal_code']. "</td>";
            echo "</tr>";
            echo "<tr>";
            echo    "<th>Ort</th>";
            echo    "<td>". $row['region']. "</td>";
            echo "</tr>";
            echo "<tr>";
            echo    "<th>Straße & Hausnummer</th>";
            echo    "<td>". $row['street']." ". $row['street_number'] ."</td>";
            echo "</tr>";
            echo "<tr>";
            echo    "<th>Kontoinhaber</th>";
            echo    "<td>". $row['account_holder']. "</td>";
            echo "</tr>";
            echo "<tr>";
            echo    "<th>IBAN & BIC</th>";
            echo    "<td>". $row['IBAN']. " ". $row['BIC'] ."</td>";
            echo "</tr>";
            echo "<tr>";
            echo    "<th>monatlicher Beitrag</th>";
            echo    "<td>". $row['contribution']. "€</td>";
            echo "</tr>";
            echo "<tr>";
            echo    "<th>Mitgliedsdarlehn</th>";
            echo    "<td>". $row['loan']. "€</td>";
            echo "</tr>";
            echo "<tr>";
            echo    "<th>beigetreten</th>";
            echo    "<td>". $row['created_at']. "</td>";
            echo "</tr>";
            echo "<tr>";
            echo    "<th>IP bei Beitritt</th>";
            echo    "<td>". $row['ip']. "</td>";
            echo "</tr>";
        }
        ?>
        </table>
    </div>
</div>
<?php 
include("../templates/footer.inc.php")
?>
