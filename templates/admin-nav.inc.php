<?php
$admin = "";
if ($user['rights'] == 4) {
	$admin = '
	<li id="admin"><a href="admin.php">Katalog</a></li>
	<li id="producers"><a href="producers.php">Hersteller</a></li>
	<li id="members"><a href="members.php">Mitglieder</a></li>
	<li id="sepa"><a href="sepa.php">SEPA</a></li>
	<li id="emailcenter"><a href="emailcenter.php">EmailCenter</a></li>';
}
?>

<div class="admin-nav">
	<div class="limiter">
	<ul>
		<li id="inventory"><a href="inventory.php">Inventar</a></li>
		<li id="order_total"><a href="order_total.php">Bestellungen</a></li>
		<li id="delivered-orders"><a href="delivered-orders.php">ausgegeben</a></li>
		<?php echo $admin ?>
		<li id="calendar"><a href="calendar.php">Kalender</a></li>
		<li id="session"><a href="session.php">Ausgabe</a></li>
  	</ul>
  	</div>
</div>



<script type="text/javascript" src="js/nav.js"></script>