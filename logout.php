<?php 
session_start();
session_destroy();
unset($_SESSION['userid']);

//Remove Cookies
setcookie("identifier","",time()-(3600*24*365)); 
setcookie("securitytoken","",time()-(3600*24*365)); 

require_once("inc/config.inc.php");
require_once("inc/functions.inc.php");

include("templates/header.inc.php");
?>

<div class="login-background">
</div>
<div class="center-vertical" style="height: 100vh">
<div class="login form-container">
Der Logout war erfolgreich. <a href="login.php">Zurück zum Login</a>.
</div>
</div>
<?php 
include("templates/footer.inc.php")
?>