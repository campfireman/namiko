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

//Query joins orders and order_items & retrieves productName and price via pid
$statement = $pdo->prepare("SELECT orders.* FROM orders WHERE uid = '$uid' ORDER BY orders.created_at DESC");
$result = $statement->execute();
//print_r($arr = $statement->errorInfo());

while ($row = $statement->fetch()) {
	$count++;
	$oid = $row['oid'];
	$date = new DateTime($row['created_at']);

	if ($count == 1) {
			echo '<div class="row spacer3">';
	}

	echo '<div class="col-md-6">';
	echo '<div class="subtitle2 inline"><span>Bestellung #'. $oid .'</span></div>';
	echo '<div class="subtitle3 inline" style="float: right"><span>'. $date->format("d.m.Y H:i:s") .'</span></div><br><br>';
	echo '<table class="max"><tr style="text-align: left;"><th>Artikel</th><th>Preis KG/L</th><th>Menge</th><th>&#931;</th></tr>';
	
	$grandtotal = 0;

	$statement2 = $pdo->prepare("SELECT order_items.pid, products.productName,  products.price_KG_L, order_items.quantity, order_items.total FROM order_items LEFT JOIN products ON order_items.pid = products.pid WHERE order_items.oid = '$oid'");
	$result2 = $statement2->execute();

	while ($row2 = $statement2->fetch()) {
		$pid = $row2['pid'];
		$productName = $row2['productName'];
		$price_KG_L = $row2['price_KG_L'];
		$quantity = $row2['quantity'];
		$delivered = $row2['delivered'];
		$total = $row2['total'];
		$total = ($quantity * $price_KG_L);
		$grandtotal += $total;

		echo '<tr>';
		echo '<td>'. $productName .'</td>';
		echo '<td>'. $currency. sprintf("%01.2f", $price_KG_L) .'</td>';
		echo '<td>'. $quantity .'</td><td>'.$currency. sprintf("%01.2f", $total). '</td>';
		echo '</tr>';
	}

	echo '<tr><td></td><td></td><td></td><td class="emph">'. $currency.sprintf("%01.2f",$grandtotal) .'</td></table>';
	if ($row['delivered'] == 0) {
		echo '<button class="picked-up clean-btn red" oid="'. $row['oid'] .'">nicht abgeholt <i class="fa fa-times" aria-hidden="true"></i></button><br><br>';
	} else {
		echo '<button class="clean-btn green">abgeholt <i class="fa fa-check" aria-hidden="true"></i></button><br><br>';
	}
	echo '</div>';
	if ($count == 2) {
		echo '</div>';
		$count = 0;
	}
		
}	

if ($count == 1) { //closes .row if number of orders is uneven
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
	
	$(".picked-up").on("click", function(e){ 
		$(this).prop("disabled", true);
		var oid = $(this).attr('oid');
		var ref = $(this);

		e.preventDefault();
		$.ajax({
			type: "POST",
			url: 'session_process.php',
			dataType:"json",
			data: {"oid": oid, "mark-delivered" : 1} // serializes the form's elements.
		}).done(function(data){
			if (data.error == 1) {
				alert(data.text);
			} else {
				ref.removeClass("picked-up").removeClass('red').addClass('green').html('abgeholt <i class="fa fa-check" aria-hidden="true"></i>');
			}
		});
	});
</script>