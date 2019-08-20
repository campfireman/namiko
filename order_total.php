<?php
session_start();
require_once("inc/config.inc.php");
require_once("inc/functions.inc.php");


//Überprüfe, dass der User eingeloggt ist
//Der Aufruf von check_user() muss in alle internen Seiten eingebaut sein
$user = check_user();
check_admin();

include("templates/header.inc.php");
include("templates/nav.inc.php");
include("templates/admin-nav.inc.php");

if (isset($_POST['csv'])) {

	$tid = $_POST['tid'];
	$csv = array();

	$statement = $pdo->prepare("SELECT products.productName, order_total_items.container, order_total_items.quantityContainer, order_total_items.total FROM order_total_items LEFT JOIN products ON order_total_items.pid = products.pid WHERE order_total_items.tid = '$tid'");
	$result = $statement->execute();

	// loop over the rows, outputting them
	while ($row = $statement->fetch()) {
		$productName = $row['productName'];
		$container = $row['container'];
		$quantityContainer = $row['quantityContainer'];
		$total = $row['total'];
		$price = ($total / $quantityContainer);

		$line = array(utf8_decode($productName), $price, $container, $quantityContainer, $total);
		array_push($csv, $line);
	}

	while (ob_get_level()) {
		 ob_end_clean();
	}
	
	header('Content-Type: text/csv; charset=utf-8');
	header('Content-Disposition: attachment; filename=order#'. $tid .'.csv');

	// create a file pointer connected to the output stream
	$output = fopen('php://output', 'w');

	// output the column headings
	fputcsv($output, array('Artikel', 'PreisGebinde', utf8_decode('GrößeKG/L'), 'Menge', 'Summe'));

	foreach ($csv as $row) {
		fputcsv($output, $row);
	}

	fclose($output);
	
	exit();
}

if (isset($_POST['pdf'])) {
	$tid = $_POST['tid'];

	$statement = $pdo->prepare("
		SELECT * FROM order_total 
		LEFT JOIN order_total_items ON order_total.tid = order_total_items.tid
		LEFT JOIN products ON order_total_items.pid = products.pid
		WHERE order_total.tid = :tid");
	$result = $statement->execute(array('tid' => $tid));

	if ($result) {
		$week = date('W');
		$title = 'Bestellung KW '. $week;
		$info = '  namiko Hannover e.V.
					Hahnenstraße 13
					30167 Hannover
					bestellungen@namiko.org
					https://namiko.org';

		// create contract, split document to insert data later
		$doc = '<div>
		<span style="font-size: 3.3em; font-weight: bold;">'. $title .'</span><br>
		<br>
		<br>
		<br><br>
		<table cellpadding="5" cellspacing="0" style="width: 100%; font-size: 1.3em;">
		 <tr>
		 <td>

		 </td>
		 
		 <td style="text-align: right">
			'.nl2br(trim($info)).'
		 </td>
		 </tr>
		 </table>
		 <br>
		 <br>
		 <br>
		<table  border="1" frame="void" rules="rows" cellpadding="5" cellspacing="0"  style="width: 100%; font-size: 1.3em;"> 
			<tr style="text-align: left; font-weight: bold;">
				<th>Artikelname</th>
				<th>Größe Gebinde</th>
				<th>Menge Gebinde</th>
				<th>Summe</th>
			</tr>';

		// import tcpdf library
		require_once('util/tcpdf/tcpdf.php');

		##################### Create PDFs #####################
 
		// Erstellung des PDF Dokuments
		$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
			 
			
		while ($row = $statement->fetch()) {
			$productName = $row['productName'];
			$container = $row['container'];
			$unit_size = $row['unit_size'];
			$unit_tag = $row['unit_tag'];
			$quantityContainer = $row['quantityContainer'];
			$container_size = $container * $unit_size;
			$total = $container_size * $quantityContainer;


			$doc .= '
			<tr>
				<td>'. $productName .'</td>
				<td>'. $container_size .' '. $unit_tag .'</td>
				<td>'. $quantityContainer .'</td>
				<td>'. $total .' '. $unit_tag .'</td>
			</tr>';
		}

		$doc .= '</table>';

		// Dokumenteninformationen
		$pdf->SetCreator(PDF_CREATOR);
		$pdf->SetAuthor('admin');
		$pdf->SetTitle($title);
		$pdf->SetSubject($title);
		 
		 
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
		$pdf->writeHTML($doc, true, false, true, false, '');
		 
		//Ausgabe der PDF
		 
		$filename = 'KW_'. $week .'_Bestellung.pdf';

		 
		//Variante 2: PDF im Verzeichnis abspeichern:
		ob_end_clean();
		$pdf->Output($filename, 'I');
	} else {
		error(json_encode($statement->errorInfo()));
	}
}
?>

<div class="sizer spacer">
	<div>
		<?php
		$statement = $pdo->prepare("SELECT * FROM producers");
		$result = $statement->execute();

		while ($row = $statement->fetch()) {
			echo '<span class="subtitle2 sub" style="font-size: 18px">'. $row['producerName'] .'</span><br>';

			$pro_id = $row['pro_id'];
			$tid = '';
			$grandtotal = 0;
			$newTable = true;
			$count = 0;
			$breakCount = 0;

			$statement2 = $pdo->prepare("SELECT order_total.*, order_total_items.*, products.productName, users.first_name, users.last_name FROM order_total LEFT JOIN order_total_items ON order_total.tid = order_total_items.tid LEFT JOIN products ON order_total_items.pid = products.pid LEFT JOIN users ON order_total.issued_by = users.uid WHERE order_total.producer = '$pro_id' ORDER BY order_total.ordered_at DESC");
			$result2 = $statement2->execute();

			if ($statement2->rowCount() > 0) {
				while ($row2 = $statement2->fetch()) {
					if ($tid != $row2['tid']) {
						$breakCount ++;
						if ($breakCount == 5) {
							break;
						}

						if (!$newTable) {
							echo '<tr>';
							echo '<td></td><td></td><td></td><td></td>';
							echo '<td class="emph">'. $currency.sprintf("%01.2f", $grandtotal) .'</td>';
							echo '</tr>';
							echo '</table><br>';
							echo '<div class="right">';
									echo '<form class="inline" method="post">';
									echo '<input type="hidden" name="tid" value="'. $tid .'">';
									echo '<input type="hidden" name="pdf" value="1">';
									echo '<button type="submit" class="clean-btn red">PDF <i class="fa fa-file-pdf-o" aria-hidden="true"></i></button>';
									echo '</form>';
									if ($delivered == 0) {
										echo '<form class="delivered inline">';
										echo '<input type="hidden" name="tid" value="'. $tid .'">';
										echo '<input type="hidden" name="delivered" value="1">';
										echo '<button type="submit" name="delivered" class="clean-btn blue">geliefert <i class="fa fa-truck" aria-hidden="true"></i></button>';
										echo '</form>';
									}
									if ($paid == 0) {
										echo '<form class="paid inline">';
										echo '<input type="hidden" name="tid" value="'. $tid .'">';
										echo '<input type="hidden" name="paid" value="1">';
										echo '<button type="submit" name="paid" class="clean-btn blue leftSpace">bezahlt <i class="fa fa-money" aria-hidden="true"></i></button>';
										echo '</form>';
									}
							echo '<form action="'. $_SERVER['PHP_SELF'] .'" method="post" class="inline">';
									echo '<input type="hidden" name="tid" value="'. $tid .'">';
									echo '<button type="submit" name="csv" class="clean-btn green leftSpace">CSV <i class="fa fa-table" aria-hidden="true"></i></button>';
							echo '</form>';
							echo '</div><br>';
							echo '</div>';

							$grandtotal = 0;
						}

						if ($count == 2) {
							$count = 0;
							echo '</div><br>';
						}

						$newTable = false;
						$tid = $row2['tid'];
						$count++;
						$delivered = $row2['delivered'];
						$paid = $row2['paid'];
						$date = $date = substr($row2['ordered_at'], 8, 2) .'.'. substr($row2['ordered_at'], 5, 2) .'.'. substr($row2['ordered_at'], 0, 4);
						if ($count == 1) {
							echo '<div class="row">';
						}

						echo '<div class="col-sm-6 spacer3 order">';
						echo '<span>ID #'. $tid .'</span>';
						echo '<span class="right subtitle3">'. $row2['first_name'] .' '. $row2['last_name'] .' am '. $date .'</span><br>';
						echo '<table class="orderTable" style="min-width: 430px;"> 
								<tr style="text-align: left;">
									<th>Artikel</th>
									<th>Preis Gebinde</th>
									<th>Größe KG/L</th>
									<th>Menge</th>
									<th>&#931;</th>
								</tr>';
					}

					$total = $row2['total'];
					$quantityContainer = $row2['quantityContainer'];
					$price = ($total / $quantityContainer);
					$grandtotal += $total;
					$tid = $row2['tid'];
					$oti_id = $row2['oti_id'];

					echo '<tr>';
					echo '<td>'. $row2['productName'] .'</td>';
					echo '<td>'. $currency . sprintf('%01.2f', $price) .'</td>';
					echo '<td>'. $row2['container'] .'</td>';
					echo '<td>'. $quantityContainer .'</td>';
					echo '<td>'. $currency . sprintf('%01.2f', $total) . '</td>';
					if ($row2['delivered'] == 0) {
						echo '<td><a href="#" class="remove-item" tid="'. $tid .'" oti_id="'. $oti_id .'"><i class="fa fa-trash-o" aria-hidden="true"></i></td>'; 
					}
					echo '</tr>';


				}

				echo '<tr>';
				echo '<td></td><td></td><td></td><td></td>';
				echo '<td class="emph">'. $currency.sprintf("%01.2f", $grandtotal) .'</td>';
				echo '</tr>';
				echo '</table><br>';
				echo '<div class="right">';
							echo '<form class="inline"  method="post">';
							echo '<input type="hidden" name="tid" value="'. $tid .'">';
							echo '<input type="hidden" name="pdf" value="1">';
							echo '<button type="submit" class="clean-btn red">PDF <i class="fa fa-file-pdf-o" aria-hidden="true"></i></button>';
							echo '</form>';
						if ($delivered == 0) {
							echo '<form class="delivered inline">';
							echo '<input type="hidden" name="tid" value="'. $tid .'">';
							echo '<input type="hidden" name="delivered" value="1">';
							echo '<button type="submit" name="delivered" class="clean-btn blue">geliefert <i class="fa fa-truck" aria-hidden="true"></i></button>';
							echo '</form>';
						}
						if ($paid == 0) {
							echo '<form class="paid inline">';
							echo '<input type="hidden" name="tid" value="'. $tid .'">';
							echo '<input type="hidden" name="paid" value="1">';
							echo '<button type="submit" name="paid" class="clean-btn blue leftSpace">bezahlt <i class="fa fa-money" aria-hidden="true"></i></button>';
							echo '</form>';
						}
				echo '<form action="'. $_SERVER['PHP_SELF'] .'" method="post" class="inline">';
						echo '<input type="hidden" name="tid" value="'. $tid .'">';
						echo '<button type="submit" name="csv" class="clean-btn green leftSpace">CSV <i class="fa fa-table" aria-hidden="true"></i></button>';
				echo '</form>';
				echo '</div><br>';
				echo '</div>';
				echo '</div><br>';
			}
		}
		?>
	</div>
</div>

<script type="text/javascript">
$('document').ready(function () {
	$('.delivered').submit(function(e) {
		e.preventDefault();
		var form_data = $(this).serialize();
		var btn_txt = $(this).find('button[type=submit]');
		btn_txt.html('...');

		$.ajax({
			data: form_data,
			dataType: 'json',
			type: 'POST',
			url: 'order_total_process.php'
		}).done(function(data) {
			if (data.error == 0) {
				btn_txt.removeClass('blue').addClass('green').html('geliefert <i class="fa fa-check" aria-hidden="true">');
			} else if (data.error == 1) {
				alert(data.text);
			}
		})
	});

	$('.paid').submit(function(e) {
		e.preventDefault();
		var form_data = $(this).serialize();
		var btn_txt = $(this).find('button[type=submit]');
		btn_txt.html('...');

		$.ajax({
			data: form_data,
			dataType: 'json',
			type: 'POST',
			url: 'order_total_process.php'
		}).done(function(data) {
			if (data.error == 0) {
				btn_txt.removeClass('blue').addClass('green').html('bezahlt <i class="fa fa-check" aria-hidden="true">');
			} else if (data.error == 1) {
				alert(data.text);
			}
		})
	});


	$('.remove-item').on("click", function(e) {
		$(this).prop("disabled", true);
		e.preventDefault();

		var oti_id = $(this).attr("oti_id");
		var tid = $(this).attr("tid");
		
	    $(this).closest('tr').fadeOut();
	    $.getJSON( "order_total_process.php", {"remove-order-total-item":1, "tid": tid, "oti_id" : oti_id}).done(function(data){ 
	    	if (data.error == 1) alert(data.text);
	    });
	});
})
</script>

<?php 
include("templates/footer.inc.php")
?>