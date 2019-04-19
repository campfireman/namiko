<?php

if (is_checked_in()) {
	if ($user['rights'] == 0) {

				header("location: verify.php");
	}
}
require_once('inc/functions.inc.php');
?>

<header>
	<div class="banner-main fixed adjust">
		<nav>
			<div id="nav" class="padder sizer">

			<?php
				$menu = '';
				if (is_checked_in()) {
					if ($user['rights'] == 1) {
						echo '  <div id="left-nav" class="left-nav newUser">';
					echo $menu='<ul>
									<li><a class="settings" title="Einstellungen" href="personal_data.php"><i class="fa fa-user" aria-hidden="true"></i><span class="desc"> Einstellungen</span></a></li>';
					} else if ($user['rights'] == 2) {
						echo '  <div id="left-nav" class="left-nav user">';
					echo $menu='<ul>
									<li><a class="orders" title="Meine Bestellungen" href="my-orders.php"><i class="fa fa-list-ul" aria-hidden="true"></i><span class="desc"> Meine Bestellungen</span></a></li>
									<li><a class="settings" title="Einstellungen" href="personal_data.php"><i class="fa fa-user" aria-hidden="true"></i><span class="desc"> Einstellungen</span></a></li>';
					} else if ($user['rights'] >= 3) {
						echo '  <div id="left-nav" class="left-nav administrator">';
					echo $menu='<ul>
									<li><a class="orders" title="Meine Bestellungen" href="my-orders.php"><i class="fa fa-list-ul" aria-hidden="true"></i><span class="desc"> Meine Bestellungen</span></a></li>
									<li><a class="settings" title="Einstellungen" href="personal_data.php"><i class="fa fa-user" aria-hidden="true"></i><span class="desc"> Einstellungen</span></a></li>
									<li><a class="admin" title="Administratorenbereich" href="admin.php"><i class="fa fa-user-secret" aria-hidden="true"></i><span class="desc"> Administratorenbereich</span></a></li>';  
					}
					$menu .=
							'<li><a class="red" title="Ausloggen" href="logout.php">
								<i class="fa fa-sign-out" aria-hidden="true"></i> <span class="desc"> Ausloggen</span>
							</a></li>
							</ul>';
					echo '</div>';
				}
			?>

			  <a id="nav-btn" class="left-nav mobileMenu" onclick="open_close()"><i class="fa fa-bars" aria-hidden="true"></i></a>


				<a title="zur Hauptseite" href="internal.php"><img class="title" src="https://namiko.org/wp-content/uploads/2018/06/logo.png"></a>
				
				<div class="right-nav">
					<ul>
					<?php
					if ($user['rights'] >= 2) {
						echo '<li>';
						echo '<a class="shoppingCart" id="shoppingCart" onclick="open_close_Cart()"><i class="fa fa-shopping-cart" aria-hidden="true"></i>
							<span id="cartCount">';
								if(isset($_SESSION['orders']) || isset($_SESSION['preorders'])){
								    echo cartCount(); 
								}else{
								    echo '0'; 
								}
						echo '</span>
						</a></li>';
					}
					echo '<li id="log"><a class="red" title="Ausloggen" href="logout.php">
								<i class="fa fa-sign-out" aria-hidden="true"></i> <span class="desc"> Ausloggen</span>
							</a></li>';
					?>
					</ul>
				</div>
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
if (isset($_SESSION['notification']) && $_SESSION['notification']) {
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

$(document).ready(function(){
	function unchecker () {
		$('input.category').on('click', function() {
			$('#all').prop('checked', false);
		})

		$('#all').on('click', function() {
			$('.category').prop('checked', false);
		})
	}
})
</script>