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

if (isset($_POST['application'])) {

    $memberName = $user['uid'] .'_'. $user['first_name'] .'_'. $user['last_name'] .'_'. $user['verify_code']  .'.pdf';

    $memDir = dirname(__FILE__). '/applications/'.$memberName;

    header('Content-type:application/pdf');
    header('Content-Transfer-Encoding: Binary');
    header('Content-disposition: attachment; filename="'. $memberName .'"');
    while (ob_get_level()) {
        ob_end_clean();
    }
    readfile($memDir);
    exit();
}

if (isset($_POST['mandate'])) {

    $uid = $user['uid'];
    $statement = $pdo->prepare("SELECT mid FROM mandates WHERE uid = '$uid'");
    $result = $statement->execute();
    $mid = $statement->fetch();

    $mandateName = $mid['mid'] .'_'. $user['first_name'] .'_'. $user['last_name'] .'_'. $user['verify_code']  .'.pdf';

    $sepaDir = dirname(__FILE__). '/mandates/'.$mandateName;

    header('Content-type:application/pdf');
    header('Content-Transfer-Encoding: Binary');
    header('Content-disposition: attachment; filename="'. $mandateName .'"');
    while (ob_get_level()) {
        ob_end_clean();
    }
    readfile($sepaDir);
    exit();
}

?>
<div class="sizer spacer">
    <p><i class="fa fa-info-circle" aria-hidden="true"></i> Hier findest Du die Dokumente, die bei Deiner Anmeldung erstellt wurden.</p><br>
    <div>
        <span class="subtitle2">Dein Mitgliedsantrag</span><br><br>
        <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']) ?>" method="post">
            <button type="submit" name="application" class="clean-btn blue"><i class="fa fa-file-pdf-o" aria-hidden="true"></i> Mitgliedsantrag herunterladen</button>
        </form>
    </div>
    <div class="spacer">
        <span class="subtitle2" >Dein Lastschriftmandat</span><br><br>
        <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']) ?>" method="post">
            <button type="submit" name="mandate" class="clean-btn blue"><i class="fa fa-file-pdf-o" aria-hidden="true"></i> Lastschriftmandat herunterladen</button>
        </form>
    </div>
</div>
<?php 
include("templates/footer.inc.php")
?>
