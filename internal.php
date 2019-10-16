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

function get_preorder_sum() {
	global $pdo;
	$statement = $pdo->prepare("
		SELECT SUM(total) AS total, SUM(quantity) AS quantity, container, producer, price_KG_L, producerName FROM preorder_items 
		LEFT JOIN products ON preorder_items.pid = products.pid
		LEFT JOIN producers ON products.producer = producers.pro_id
		WHERE preorder_items.transferred = 0 
	    GROUP BY products.pid
    ");
    $result = $statement->execute();
    $preorders = array();

    while ($row = $statement->fetch()) {
    	$quantity = $row['quantity'];
    	$total = $row['total'];
    	$container = $row['container'];
    	$pro_id = $row['producer'];
    	$unit_price = $row['price_KG_L'];
    	$producerName = $row['producerName'];
    	$full_containers = intdiv($quantity, $container);

    	if ($full_containers > 0) {
    		$covered_quantity = $quantity - ($quantity % $container);
    		$covered_total = $covered_quantity * $unit_price;

    		if (array_key_exists($pro_id, $preorders)) {
				$preorders[$pro_id]['total'] += $covered_total;
			} else {
				$preorders[$pro_id]['total'] = $covered_total;
				$preorders[$pro_id]['producerName'] = $producerName; 
			}
    	}
    }

    return $preorders;
}

function covered_preorders() {
	global $currency;
	$preorders = get_preorder_sum();
	$covered_preorders = '<div>';

	foreach ($preorders as $pro_id => $value) {
		$covered_preorders .= '<p><span class="emph">'. $value['producerName'] .': </span><span class="blue">'. sprintf("%01.2f", $value['total']) . $currency .'</span></p>';
	}
	$covered_preorders .= '</div>';

	return $covered_preorders;
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

<div class="sizer2">
	<div class="row">
		<div class="col-md-5 spacer5">
			<div class="greet">
			<h1><span id="greeter"></span></h1>
			<span class="subtitle">Willkommen zurück, <span class="emph"><?php echo htmlspecialchars($user['first_name']); ?></span>! Was möchtest Du bestellen?</span><br><br>
			<h4 class="">Mindestbestellwert erreicht?</h4>
			<p>Summe voller Gebinde nach Lieferant geordnet:</p>
			<?php echo covered_preorders() ?>
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
</div>

<?php
	include('templates/sidebar.inc.php');
?>

<div class="sizer2">
	<div class="spacer">
		<div class="center-vertical">
			<div class="center">
			<div id="loadScreen" class="loader"></div>
			</div>
		</div>
		<div id="catalogue">
		</div>
	</div>
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
$(document).ready(function () {
	function loadCatalogue (form) {
		var data = $(form).serialize();
		$.ajax({
			url: 'catalogue_handler.php',
			type: 'POST',
			dataType: 'json',
			data: data
		}).done(function(data) {
			$('#catalogue').html(data);
			
  				removeLoader('#loadScreen');
  			
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
			
		}); 
	};
	loadCatalogue('.filter');

	$('.filter').submit(function(e) {
		loader('#loadScreen');
		loadCatalogue('.filter');
		e.preventDefault();
	});

	$('#search-items').submit(function(e) {
		loader('#loadScreen');
		loadCatalogue('#search-items');
		e.preventDefault();
	});

	function loader (tag) {
		$(tag).addClass('loader');
	}

	function removeLoader (tag) {
		$(tag).removeClass('loader');
	}

	$('#all').click(function() {
		if($('#all').prop('checked')) {
			$('.other').prop("checked", false);

		}
	});
	$('.other').click(function() {
		if(this.checked) {
			$('#all').prop('checked', false);
		}
	});

	$('#allprod').click(function() {
		if($('#allprod').prop('checked')) {
			$('.otherprod').prop("checked", false);

		}
	});
	$('.otherprod').click(function() {
		if(this.checked) {
			$('#allprod').prop('checked', false);
		}
	});
});
</script>

<?php 
include("templates/footer.inc.php")
?>