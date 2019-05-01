<?php
session_start();
require_once("inc/config.inc.php");
require_once("inc/functions.inc.php");
require_once("inc/Cart.inc.php");
//ini_set('display_errors', 1);

$user = check_user();
include("templates/header.inc.php");

if ($user['rights'] == 1) {
	
	echo '  <script>document.body.className += "noscroll"</script>
			<div id="notification0" class="notification">
				<div><a href="javascript:void(0)" title="Close" class="closebtn" onclick="closeNotification(0)">&times;</a></div>
				<div class="box center-vertical">
					<div><h1>Hallo <span class="emph">'. htmlspecialchars($user['first_name']). '!</span></h1></div>
					<div class="subtitle"><br>
					Herzlich Willkommen bei der Nahrungsmittel-Kooperative! Wir freuen uns sehr, Dich in unserem Verein zu begrüßen :) <br>
					Du kannst bereits den Katalog einsehen, aber es dauert noch ein wenig, bis wir Dich für Bestellungen freischalten. Wenn Du freigeschaltet bist, erhältst du eine E-Mail.
					<br><br> Dein, <br>namiko e.V. Team</div>
				</div>
			</div>';
	
	
}

if ($user['notification'] == 1) {
	$uid = $user['uid'];
	$statement2 = $pdo->prepare("UPDATE users SET notification = 0 WHERE uid = $uid");
	$resulter = $statement2->execute();
	
	$statement = $pdo->prepare("SELECT * FROM notification");
	$result = $statement->execute();
	
	while($notification = $statement->fetch()) {
	echo '  <script>document.body.className += "noscroll"</script>
			<div id="notification2" class="notification">
				<div class="box center-vertical">
					<div><h1>' . htmlspecialchars($notification['title']) . '</h1></div>
					<div class="subtitle"><br>'. htmlspecialchars($notification['text']) .'</div>
					<div class="spacer"><a href="javascript:void(0)" title="Close" class="clean-btn red check" onclick="closeNotification(2)">Geht klar!</a></div>
				</div>
			</div>';
	}
	
	
}
/*
$curr = date('Y-m-d H:i:s');
$statement = $pdo->prepare("SELECT start FROM events WHERE type = 1 AND start > '$curr' ORDER BY start ASC");
$result = $statement->execute();

$result = $statement->fetchAll();
	if (!empty($result)) {
	$nextSession = date_create_from_format('Y-m-d H:i:s', $result[0]['start']);
	$calc = clone $nextSession;

	$last = date_sub($calc, date_interval_create_from_date_string($lastPossibleOrder));
	$curr = date_create_from_format('Y-m-d H:i:s', $curr);

	if ($curr > $last) {
		$secondSession = date_create_from_format('Y-m-d H:i:s', $result[1]['start']);
		$calc = clone $secondSession;
		date_sub($calc, date_interval_create_from_date_string($lastPossibleOrder));
		
		$output = 'Die nächste Ausgabe ist am '. $nextSession->format('d.m.Y') .' um '. $nextSession->format('H:i') .'. Bestellungen zählen allerdings bereits zur nächsten Ausgabe am '. $secondSession->format('d.m.Y') .' um '. $secondSession->format('H:i') .'. Du kannst noch bis zum '. $calc->format('d.m.Y H:i') .' für diesen Termin bestellen.';
	} else {
		$output = 'Die nächste Ausgabe ist am '. $nextSession->format('d.m.Y') .' um '. $nextSession->format('H:i') .' und Du kannst noch bis zum '. $last->format('d.m.Y H:i') .' für diesen Termin bestellen.';
	}
}
*/


include("templates/main-nav.inc.php");
?>

<!-- Full Calendar Library -->
<link rel="stylesheet" href="util/fullcalendar/fullcalendar.css"/>
<script src="js/moment.min.js"></script>
<script src="util/fullcalendar/fullcalendar.js"></script>
<script src='util/fullcalendar/locale/de.js'></script>
<script src='util/fullcalendar/gcal.min.js'></script>

<div id="notification3" class="notificationClosed">
	<div><a href="javascript:void(0)" title="Close" class="closebtn" onclick="closeNotification(3)">&times;</a></div>
		<div id="producer_popup"></div>
</div>

<div id="cartContent" class="cart text-center">
	<div><a href="javascript:void(0)" id="close2" title="Close" class="closebtn" onclick="open_close_Cart()">&times;</a></div>
	<div id="shopping-cart-results" class="pad spacer3"></div>
</div>

<div class="sizer">
<div class="row">
	<div class="col-md-5 spacer5">
		<div class="greet">
		<h1><span id="greeter"></span></h1>
		<span class="subtitle">Willkommen zurück, <span class="emph"><?php echo htmlspecialchars($user['first_name']); ?></span>! Was möchtest Du bestellen?</span><br><br>
		<?php #echo $output ?>
		<br>
		</div>
	</div>
	<div class="col-md-7 spacer5" style="min-height: 500px">
		<div id="calendar" class="calendar"></div>
	</div>
</div>

<script>
(function ($) { 
$('#calendar').fullCalendar(
{
	eventLimit: true, // for all non-agenda views
	height: "parent",
	googleCalendarApiKey: 'AIzaSyAqNM_ODRVjh6z9txtK0qVgU5RCxiRufgA',
	views: {
		agenda: {
			eventLimit: 6 // adjust to 6 only for agendaWeek/agendaDay
		}
	},
	timeFormat: 'H:mm',
	eventSources: [
		{
			url: 'calendar_handler.php'

		},
		{
			googleCalendarId: 'kldej2vcurllpe1hold48ad3636cnrlk@import.calendar.google.com'
		}
	]
});

})(jQuery);
</script>

<div class="spacer center">

</div>

<?php

$statement = $pdo->prepare("SELECT * FROM categories WHERE cid > 1 ORDER BY FIELD(cid, 3, 6, 7, 4, 5)");
$result = $statement->execute();

while ($row = $statement->fetch()) {
	$selector = $row['cid'];
	$categoryIMG = $row['categoryIMG'];
	$num = 0;

	while (strpos($categoryIMG, '|') !== false) {
		$num++;
		${'img'.$num} = substr($categoryIMG, 0, strpos($categoryIMG, '|'));
		$categoryIMG = substr($categoryIMG, (strpos($categoryIMG, '|') + 1));
	}

	echo '	<div class="catThumb spacer">
			<div class="first catImg"><div class="center-vertical"><div class="center"><div class="img"><img src="media/'. $img1 .'"></div></div></div></div>
			<div class="second catImg"><div class="center-vertical"><div class="center"><div class="img"><img src="media/'. $img2 .'"></div></div></div></div>
			<div class="third catImg"><div class="center-vertical"><div class="center"><div class="img"><img src="media/'. $img3 .'"></div></div></div></div>
			<h2 class="header">'. htmlspecialchars($row['category_name']) .'</h2>
			<div class="third catImg"><div class="center-vertical"><div class="center"><div class="img"><img src="media/'. $img4 .'"></div></div></div></div>
			<div class="second catImg"><div class="center-vertical"><div class="center"><div class="img"><img src="media/'. $img5 .'"></div></div></div></div>
			<div class="first catImg"><div class="center-vertical"><div class="center"><div class="img"><img src="media/'. $img6 .'"></div></div></div></div>
		  	</div>';
	

		$statement2 = $pdo->prepare("SELECT products.*, producers.producerName, producers.pro_id, inventory_items.quantity_KG_L FROM products LEFT JOIN producers ON products.producer = producers.pro_id LEFT JOIN inventory_items ON inventory_items.pid = products.pid WHERE category = '$selector'");
		$result2 = $statement2->execute();
		
		$count = 0;
		if ($statement2->rowCount() > 0) {
				while ($row = $statement2->fetch()) {
					$pid = $row['pid'];
					$statement3 = $pdo->prepare("SELECT order_items.quantity, orders.delivered FROM order_items LEFT JOIN orders ON order_items.oid = orders.oid WHERE (order_items.pid = '$pid') AND (orders.delivered = 0)");
					$result3 = $statement3->execute();

					$quantityOrdered = 0;
					$quantity_KG_L = $row['quantity_KG_L'];

					while ($row2 = $statement3->fetch()) {
						$quantityOrdered += $row2['quantity'];
					}

					$stock = $db->getStock($pid);
					$preorders = '<span class="blue">'. $db->getPreorders($pid) .'</span>';

					// colored output based on amount
					if ($stock < 0) {
						$stockOut = '<span class="red">'. $stock .'KG</span>';
					} else if ($stock > 0) {
						$stockOut = '<span class="green">'. $stock .'KG</span>';
					} else {
						$stockOut = '<span>'. $stock .'</span>';
					}

					$count++;
					if ($count == 5) { $count = 1; }
					if ($count == 1) {
					echo '<div class="row">'; }
					echo '<div class="col-sm-3 item">
						<form class="order-item">';
					echo '<span class="data">'. htmlspecialchars($row['origin']) .' | 
							<a class="producer_info" data-code="'. $row['pro_id'] .'">'. htmlspecialchars($row['producerName']) .'</a></span>';
					echo '<h2 class="name">'. htmlspecialchars($row['productName']) .'</h2>';
					echo '<div>'. $row['productDesc'] .'<br><span class="emph">Preis: '. $row['price_KG_L'] .'€/KG</span></div>';
					echo '<div><span class="italic">auf Lager: </span>'. $stockOut .'</div>';
					echo '<div><span class="italic">vorbestellt: </span>'. $preorders .'</div>';
					echo '<div><span class="italic">Gebindegröße: </span>' .$row['container']. 'KG</div>';
					if ($user['rights'] > 1) {
					echo '<div class="price">
						  <label>Menge:
						  <span><input class="quantity" type="number" name="quantity" min="0" step="0.5" required> KG</label>
						  <input type="hidden" name="pid" value="'. $row['pid'] .'"</span>
						  ';
					echo '<button class="addCart green" type="submit" name="addCart"><i class="fa fa-cart-plus" aria-hidden="true"></i></button></div>';
					}
					echo '</form></div>';
					if ($count == 4) {
					echo '</div>'; }
				}
			if ($count < 4) {
				echo '</div>';
			}
		} else {
			echo '<div class="row">';
			echo '<div class="center spacer3">Aktuell nichts im Sortiment.</div>';
			echo '</div>';
		}
}
?>

</div>

<script>
// display time specific greeting text
(function greeter () {
	var time = new Date(); //get time
	var hour = time.getHours();
	var minutes = time.getMinutes();
	if (minutes < "10") minutes = "0" + minutes; //round 
	
	//specify output strings
	var text01 = "Guten Morgen!"; 
	var text02 = "Moin Moin!";
	var text03 = "N' Abend!";
	var text04 = "Gute Nacht!";
	var timeText;
	
	//specify time spans 
	if (hour >= "5" && hour <= "11") timeText = text01;
	if (hour >= "11" && hour <= "18") timeText = text02;
	if (hour >= "18" && hour <= "24") timeText = text03;
	if (hour >= "00" && hour < "5") timeText = text04;
	
	document.getElementById("greeter").innerHTML = timeText;
})();


//open & close cart full screen window
var state = 0;
function open_close_Cart () {
	
	var x = document.getElementById('cartContent');
	
	if (state == 0) {
		x.style.height = "100%";
		document.body.className += 'noscroll';
		state = 1;
	} else {
		x.style.height = "0";
		document.body.classList.remove('noscroll');
		state = 0;
	}
}
</script>

<script type="text/javascript" src="js/cart.js"></script>
<script>
//shopping cart functionality
$(".order-item").submit(function(e){ //user clicks form submit button
    var form_data = $(this).serialize(); //prepare form data for Ajax post
    var button_content = $(this).find('button[type=submit]'); //get clicked button info
    button_content.html('...'); //Loading button text //change button text 
    $.ajax({ //make ajax request to cart_process.php
        url: "cart_process.php",
        type: "POST",
        dataType:"json", //expect json value from server
        data: form_data
    }).done(function(data){ //on Ajax success
        $("#cartCount").html(data.items); //total items count fetch in cart-info element
        button_content.removeClass('green').addClass('blue').html('<i class="fa fa-check" aria-hidden="true"></i>'); //reset button text to original text
    })
    e.preventDefault();
});

$(".producer_info").on('click', function(e) {
     e.preventDefault(); 
	    var pro_id = $(this).attr("data-code");
	    $.getJSON( "producer_info.php", {"pro_id":pro_id}).done(function(data){ 
	    	$('body').addClass('noscroll');
	    	$('#notification3').css('height', '100%');
	    	$('#producer_popup').html(data);
	    });
});
</script>

<?php 
include("templates/footer.inc.php")
?>