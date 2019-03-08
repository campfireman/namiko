<div class="admin-nav">
	<div class="limiter">
	<ul>
		<li id="personal_data"><a href="/namiko/settings/personal_data.php">Daten</a></li>
		<li id="change_email"><a href="/namiko/settings/change_email.php">E-Mail</a></li>
		<li id="change_password"><a href="/namiko/settings/change_password.php">Passwort</a></li>
		<li id="documents"><a href="/namiko/settings/documents.php">Dokumente</a></li>
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
