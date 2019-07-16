<?php

session_start();
require_once("inc/config.inc.php");
require_once("inc/functions.inc.php");

//Überprüfe, dass der User eingeloggt ist
//Der Aufruf von check_user() muss in alle internen Seiten eingebaut sein
$user = check_user();

include("templates/header.inc.php");
include("templates/nav.inc.php");

?>
<div id="cartContent" class="sizer center-text spacer3">
  <div id="shopping-cart-results" class="pad">
  </div>
</div>

<script type="text/javascript" src="js/cart.js"></script>
<script type="text/javascript">
    $("#shopping-cart-results").load( "cart_process.php", {"load_cart":"1", "pay": 1}); //Make ajax request using jQuery Load() & update results
</script>

<pre><?php print_r($_SESSION) ?></pre>
<?php
include("templates/footer.inc.php")
?>