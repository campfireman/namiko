<div class="admin-nav">
	<div class="limiter">
	<ul>
		<li id="inventory"><a href="inventory.php">Inventar</a></li>
		<li id="order_total"><a href="order_total.php">Bestellungen</a></li>
		<?php
		if ($user['rights'] == 4) {
			echo 
			    '<li id="admin"><a href="admin.php">Katalog</a></li>
				<li id="members"><a href="members.php">Mitglieder</a></li>
				<li id="sepa"><a href="sepa.php">SEPA</a></li>
				<li id="emailcenter"><a href="emailcenter.php">EmailCenter</a></li>';
		}
		?>
		<li id="calendar"><a href="calendar.php">Kalender</a></li>
		<li id="session"><a href="session.php">Ausgabe</a></li>
  	</ul>
  	</div>
</div>



<script type="text/javascript">
	//marks the current navigation item, that has been selected
	(function markNav ()  {
		var path = window.location.pathname;
		path = path.slice(0, path.indexOf('.'));
		
		while (path.indexOf('/') != -1) {
			path = path.slice(path.indexOf('/') + 1);
		}

		var selector = document.getElementById(path);
		selector.className += 'select';
		
	})();
</script>
