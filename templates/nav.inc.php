<?php

if (is_checked_in()) {
	if ($user['rights'] == 0) {

				header("location: verify.php");
	}
}
?>

<header>
	<div class="banner-main">
		<nav>
			<div id="nav" class="sizer">

			<?php
				$menu = '';
				if (is_checked_in()) {
					if ($user['rights'] == 1) {
						echo '  <div id="left-nav" class="left-nav newUser">';
					echo $menu='<ul>
									<li><a class="settings" title="Einstellungen" href="personal_data.php"><i class="fa fa-user" aria-hidden="true"></i><span class="desc"> Einstellungen</span></a></li>
								</ul>';
					echo 	   '</div>';  
					} else if ($user['rights'] == 2) {
						echo '  <div id="left-nav" class="left-nav user">';
					echo $menu='<ul>
									<li><a class="orders" title="Meine Bestellungen" href="my-orders.php"><i class="fa fa-list-ul" aria-hidden="true"></i><span class="desc"> Meine Bestellungen</span></a></li>
									<li><a class="settings" title="Einstellungen" href="personal_data.php"><i class="fa fa-user" aria-hidden="true"></i><span class="desc"> Einstellungen</span></a></li>
								</ul>';
					echo 	   '</div>';  
					} else if ($user['rights'] >= 3) {
						echo '  <div id="left-nav" class="left-nav administrator">';
					echo $menu='<ul>
									<li><a class="orders" title="Meine Bestellungen" href="my-orders.php"><i class="fa fa-list-ul" aria-hidden="true"></i><span class="desc"> Meine Bestellungen</span></a></li>
									<li><a class="settings" title="Einstellungen" href="personal_data.php"><i class="fa fa-user" aria-hidden="true"></i><span class="desc"> Einstellungen</span></a></li>
									<li><a class="admin" title="Administratorenbereich" href="inventory.php"><i class="fa fa-user-secret" aria-hidden="true"></i><span class="desc"> Administratorenbereich</span></a></li>
								</ul>';
					echo 	   '</div>';  
					}
				}
			?>

			  <a id="nav-btn" class="left-nav mobileMenu" onclick="open_close()"><i class="fa fa-bars" aria-hidden="true"></i></a>


			<a title="zur Hauptseite" href="internal.php"><img class="title" src="https://namiko.org/wp-content/uploads/2018/06/logo.png"></a>

			<?php
			if (is_checked_in()) {
				echo
				'<a class="log red" title="Ausloggen" href="logout.php">
					<i class="fa fa-sign-out" aria-hidden="true"></i>
				</a>';
			} else {
				echo
				'<a class="log green" title="Einloggen" href="login.php">
					<i class="fa fa-sign-in" aria-hidden="true"></i>
				</a>';
			}
			?>
			</div>
		</nav>
	</div>
</header>

<div id="menuContent" class="menu-content">
<?php
echo $menu;
?>
</div>

<?php
if ($_SESSION['notification']) {
	echo '<script>document.body.className += "noscroll"</script>
			<div id="notification1" class="notification">
				<div><a id="close" href="javascript:void(0)" title="Schließen" class="closebtn" onclick="closeNotification(1)">&times;</a></div>
				<div class="box center-vertical">
					<div><h1><span class="emph">'; echo htmlentities($user['first_name']); echo '!</span></h1></div>
					<div class="subtitle"><br>
					'.$_SESSION['notificationmsg'].'</div>
				</div>
			</div>';
			$_SESSION['notification'] = false;
}
?>

<script type="text/javascript">
	$('body').keyup(function(event) {
    if (event.keyCode === 13) {
        $("#close").click();
        $("#close2").click();
    }
});
</script>