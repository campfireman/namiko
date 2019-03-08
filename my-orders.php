<?php
session_start();
require_once("inc/config.inc.php");
require_once("inc/functions.inc.php");
require_once('util/tcpdf/tcpdf.php');

//Überprüfe, dass der User eingeloggt ist
//Der Aufruf von check_user() muss in alle internen Seiten eingebaut sein
$user = check_user();

include("templates/header.inc.php");
include("templates/nav.inc.php");

if (isset($_POST['deliveryProof'])) {
	$oid = $_POST['oid'];

	$pdfOut = '<h1>Bestellung #'. $oid .'</h1>';

	$statement = $pdo->prepare("SELECT delivery_proof.witness, delivery_proof.created_at, delivery_proof.signature, orders.oid, users.first_name, users.last_name FROM delivery_proof LEFT JOIN orders ON delivery_proof.oid = orders.oid LEFT JOIN users ON orders.uid = users.uid WHERE delivery_proof.oid = '$oid'");
	$result = $statement->execute();

	while ($row = $statement->fetch()) {
		$witness = $row['witness'];
		$date = substr($row['created_at'], 8, 2) .'.'. substr($row['created_at'], 5, 2) .'.'. substr($row['created_at'], 0, 4);
		$signature = $row['signature'];
		$first_name = $row['first_name'];
		$last_name = $row['last_name'];
		$imgdata = base64_decode(substr($signature, 22));
		$grandtotal = 0;

		$statement2 = $pdo->prepare("SELECT order_items.*, products.productName FROM order_items LEFT JOIN products ON order_items.pid = products.pid WHERE oid = '$oid'");
		$result2 = $statement2->execute();

		$pdfOut .='<div class="center">
		 		<table style="">
		 		<tr style="font-weight: bold;"><th>Artikel ID</th><th>Artikelname</th><th>Preis pro KG/L</th><th>Bestellmenge</th><th>Summe</th></tr>';

		while ($row2 = $statement2->fetch()) {
			$total = $row2['total'];
			$quantity = $row2['quantity'];
			$price = ($total / $quantity);
			$grandtotal += $total;

			$pdfOut .= '<tr><td>'. $row2['pid'] .'</td>
			<td>'. $row2['productName'] .'</td>
			<td>'. $currency.sprintf("%01.2f",$price) .'</td>
			<td>'. $quantity .'</td>
			<td>'. $currency.sprintf("%01.2f",$total) .'</td></tr>';
		}

		$pdfOut .= '	<tr>
							<td></td>
							<td></td>
							<td></td>
							<td></td>
							<td style="font-weight: bold;">'. $currency.sprintf("%01.2f", $grandtotal) .'</td>
						</tr>
					</table>
					</div><br><br>
					<span style="font-weight: bold;">Hiermit erkläre ich, dass die oben genannten Artikel in aller Vollständigkeit und frei von Mängeln ausgehändigt wurden.</span><br><br><br><br>';

		$pdfOut2 .= '<br><br><br><br><br><br><br><br><br>
					<table cellpadding="5" cellspacing="0" style="width: 80%;" >
					<tr>
					<td><hr>'. htmlentities($user['first_name']) .' '. htmlentities($user['last_name']) .'</td>
					</tr>
					</table>
					<br><br>
					<br><br>
					<table cellpadding="5" cellspacing="0" style="width: 60%;">
						<tr>
						<td><span style="font-size:1.3em">'. $date .'</span><br><hr>Datum</td>
						<td><span style="font-size:1.3em">'. $place .'</span><br><hr>Ort</td>
						</tr>
					</table>
					<br><br>';

		
		 
		// Erstellung des PDF Dokuments
		$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
		 
		// Dokumenteninformationen
		$pdf->SetCreator(PDF_CREATOR);
		$pdf->SetAuthor('admin');
		$pdf->SetTitle('Bestellung #'. $oid);
		$pdf->SetSubject('Bestellung');
		 
		 
		// Header und Footer Informationen
		$pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
		$pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));
		 
		// Auswahl des Font
		$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
		 
		// Auswahl der MArgins
		$pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
		$pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
		$pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
		 
		// Automatisches Autobreak der Seiten
		$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
		 
		// Image Scale 
		$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
		 
		// Schriftart
		$pdf->SetFont('helvetica', '', 10);
		 
		// Neue Seite
		$pdf->AddPage();

		// Fügt den HTML Code in das PDF Dokument ein
		$pdf->writeHTML($pdfOut, true, false, true, false, '');
		$img = file_get_contents(TEMPIMGLOC);
		// The '@' character is used to indicate that follows an image data stream and not an image file name
		$pdf->Image('@'.$imgdata, 20);
		$pdf->writeHTML($pdfOut2, true, false, true, false, '');

		while (ob_get_level()) {
			    ob_end_clean();
			}
		$pdf->Output('order#'. $oid .'.pdf', 'I');
		

	}
}
?>

<div id="notification2" class="notificationClosed">
	<div><a href="javascript:void(0)" title="Close" class="closebtn" onclick="closeNotification(2)">&times;</a></div>
	<div class="box center-vertical">
		<div class="subtitle center">
			<span id="qrCode"></span>
		</div>
	</div>
</div>


<h3 class="header">Bestellungen</h3>
<div class="sizer spacer">


<?php
$uid = $user['uid'];
$count = 0;
$newTable = 0;
$oid;

//Query joins orders and order_items & retrieves productName and price via pid
$statement = $pdo->prepare("SELECT orders.*, order_items.pid, products.productName,  products.price_KG_L, order_items.quantity, order_items.total FROM orders LEFT JOIN order_items ON orders.oid = order_items.oid LEFT JOIN products ON order_items.pid = products.pid WHERE uid = '$uid' ORDER BY orders.created_at DESC");
$result = $statement->execute(array('uid' => $uid));
//print_r($arr = $statement->errorInfo());

while ($row = $statement->fetch()) {


	if ($oid != $row['oid']) {
		$newTable++;
		$count++;

		if ($newTable == 2) {
			echo '<tr><td></td><td></td><td></td><td class="emph">'. $currency.sprintf("%01.2f",$grandtotal) .'</td></table>';
			if ($delivered == 0) {
				echo '<button type="submit" name="generateQR" class="clean-btn blue">Ausgabe QR <i class="fa fa-qrcode" aria-hidden="true"></i></button></form><br><br>';
			} else {
				echo '</form>';
				echo '<form action="'. htmlspecialchars($_SERVER['REQUEST_URI']) .'" method="post">';
				echo '<input type="hidden" name="oid" value="'. $oid .'">';
				echo '<button type="submit" name="deliveryProof" class="clean-btn green">Bestätigung <i class="fa fa-file-pdf-o" aria-hidden="true"></i></button>';
				echo '</form><br><br>';
			}
			echo '</div>';
		$newTable = 1;
		}

		if ($count == 3) {
			echo '</div>';
			$count = 1;
		}
		
		if ($count == 1) {
			echo '<div class="row">';
		}

		$oid = $row['oid'];
		$date = $row['created_at'];

		echo '<div class="col-md-6">';
		echo '<div class="subtitle2 inline"><span>Bestellung #'. $oid .'</span></div>';
		echo '<form class="qr">';
		echo '<input type="hidden" value="'. $oid . '" name="oid">';
		echo '<input type="hidden" value="'. $user['verify_code'] .'" name="verify_code">';
		echo '<div class="subtitle3 inline" style="float: right"><span>'. $date .'</span></div><br><br>';
		echo '<table class="max"><tr style="text-align: left;"><th>Artikel</th><th>Preis KG/L</th><th>Menge</th><th>&#931;</th></tr>';
		
		$grandtotal = 0;
	}
	
	$pid = $row['pid'];
	$productName = $row['productName'];
	$price_KG_L = $row['price_KG_L'];
	$quantity = $row['quantity'];
	$delivered = $row['delivered'];
	$total = $row['total'];
	$total = ($quantity * $price_KG_L);
	$grandtotal += $total;
	
	

	echo '<tr>';
	echo '<td>'. $productName .'</td>';
	echo '<td>'. $currency. sprintf("%01.2f", $price_KG_L) .'</td>';
	echo '<td>'. $quantity .'</td><td>'.$currency. sprintf("%01.2f", $total). '</td>';
	echo '</tr>';
		
}	

if ($count == 1) { //closes .row if number of orders is uneven
		echo '</div>';
}

if ($newTable == 1) {  //closes table if number of orders is uneven
	echo '<tr><td></td><td></td><td></td><td class="emph">'. $currency.sprintf("%01.2f",$grandtotal) .'</td></table>';
	if ($delivered == 0) {
		echo '<button type="submit" name="generateQR" class="clean-btn blue">Ausgabe QR <i class="fa fa-qrcode" aria-hidden="true"></i></button></form><br><br>';
	} else {
		echo '</form>';
		echo '<form action="'. htmlspecialchars($_SERVER['REQUEST_URI']) .'" method="post">';
		echo '<input type="hidden" name="oid" value="'. $oid .'">';
		echo '<button type="submit" name="deliveryProof" class="clean-btn green">Bestätigung <i class="fa fa-file-pdf-o" aria-hidden="true"></i></button>';
		echo '</form><br><br>';
	}
	echo '</div>';
}
?>

</div>
</div>

<script type="text/javascript" src="js/qrcode.js"></script>
<?php 
include("templates/footer.inc.php")
?>
<script type="text/javascript">
	
	$(".qr").submit(function(e){ 
		var form_data = $(this).serialize();
		
		$.ajax({
           type: "POST",
           url: 'qr_handle.php',
           dataType:"json",
           data: form_data // serializes the form's elements.
           }).done(function(data){
               document.body.className += "noscroll";
               var x = document.getElementById('notification2');
				x.style.height = '100%';

				var y = document.getElementById("qrCode");
				y.innerHTML = '';
				y.innerHTML = data;
           
         });
		e.preventDefault();
	});
</script>