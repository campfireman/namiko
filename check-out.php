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
<div id="cartContent" class="sizer center-text">
  <h3 class="header spacer">Deine Bestellung</h3>
  <div id="shopping-cart-results" class="pad">
  </div>
</div>
<script type="text/javascript">
    $("#shopping-cart-results").load( "cart_process.php", {"load_cart":"1", "pay": 1}); //Make ajax request using jQuery Load() & update results

    $("#cartContent").on('click', 'a.remove-item', function(e) {
     e.preventDefault(); 
      var pid = $(this).attr("data-code"); //get product code
      var item_total = $(this).closest('tr').find('[name=item_total]').val(); // get value of item
      var total = $(this).closest('table').find('[name=total]'); // get element of order total
      var total_val = total.val(); // get value of order total
      $(this).closest('tr').fadeOut(); // fade out the table row containing the item
      $.getJSON( "cart_process.php", {"remove_code":pid}).done(function(data){ 
          total_val = total_val - item_total; // subtract deleted product from order total_val
          total.val(total_val); // save new total in hidden input field
          total.closest('tr').find('#total').html('').html(total_val.toFixed(2)); // delete old total & insert new total
          $("#cartCount").html(data.items); //update Item count in cart-info
      });
});
</script>
<?php
include("templates/footer.inc.php")
?>