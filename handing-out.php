<?php 
session_start();
require_once("inc/config.inc.php");
require_once("inc/functions.inc.php");

//Überprüfe, dass der User eingeloggt ist
//Der Aufruf von check_user() muss in alle internen Seiten eingebaut sein
$user = check_user();
check_admin();

include("templates/header.inc.php");
?>

<div class="spacer sizer"> 

<?php
	$searchID = $_POST['searchID'];

	$oid = substr($searchID,8);
	$verify_code = substr($searchID,0,8);

	$statement = $pdo->prepare("SELECT orders.oid, orders.delivered, users.verify_code FROM orders LEFT JOIN users ON orders.uid = users.uid WHERE oid = '$oid'");
	$result = $statement->execute();
	$order_exists = $statement->fetch();

	if ($order_exists) {
		$check = $order_exists['verify_code'];
		$delivered = $order_exists['delivered'];
		$uid = $order_exists['uid'];
		
			if ($verify_code == $check) {
					if ($delivered == 0) {

						$statement2 = $pdo->prepare("SELECT order_items.*, products.productName FROM order_items LEFT JOIN products ON order_items.pid = products.pid WHERE oid = '$oid'");
						$result2 = $statement2->execute();

						//print_r($arr = $statement2->errorInfo());
							echo '<div class="center">';
							echo '<table class="output table panel panel-default">';
							echo '<tr><th>Artikel ID</th><th>Artikelname</th><th>Preis pro KG/L</th><th>Bestellmenge</th><th>&#931;</th></tr>';

						while ($row = $statement2->fetch()) {
							$total = $row['total'];
							$quantity = $row['quantity'];
							$price = ($total / $quantity);
							$grandtotal += $total;
							$gerDate = date('d.m.Y');

							echo '<tr><td>'. $row['pid'] .'</td>';
							echo '<td>'. $row['productName'] .'</td>';
							echo '<td>'. $currency.sprintf("%01.2f",$price) .'</td>';
							echo '<td>'. $quantity .'</td>';
							echo '<td>'. $currency.sprintf("%01.2f",$total) .'</td></tr>';
						}

						echo '<tr><td></td><td></td><td></td><td></td><td class="emph">'. $currency.sprintf("%01.2f",$grandtotal) .'</td></table></div>';

						echo '<div id="signature-pad" class="spacer box">';
						echo '<label for="signed">Hiermit erkläre ich, dass die oben genannten Artikel in aller Vollständigkeit und frei von Mängeln ausgehändigt wurden.</label>';
						echo '<button type="button" onclick="clear()" data-action="clear" class="empty" style="float: right;">Zurücksetzen <i class="fa fa-eraser" aria-hidden="true"></i></button>';

						echo '<form action="hand-out_process.php" method="post">';
						echo '<input type="hidden" name="oid" value="'. $oid .'">';
						echo '<input id="signature" type="hidden" name="signature" value="0">';
						echo '<canvas width="465" height="200" style=" touch-action: none; outline: 2px solid black;"></canvas><br><br><br>';
						echo '<div class="row">';
						echo '<div class="col-md-6 sign">';
						echo '<span>'. $place .'</span><br>';
						echo '<hr>';
						echo '</div>';
						echo '<div class="col-md-6 sign">';
						echo '<span>'. $gerDate .'</span><br>';
						echo '<hr>';
						echo '</div>';
						echo '</div><br>';
						echo '<button id="submit" type="submit" name="signed" class="clean-btn green">Ausgabe bestätigen <i class="fa fa-check" aria-hidden="true"></i></button>';
						echo '</div>';


					} else {
						$_SESSION['notification'] = true;
						$_SESSION['notificationmsg'] = 'Die Bestellung wurde bereits ausgegeben.';
						header('location: session.php');
					}

			} else {
				$_SESSION['notification'] = true;
				$_SESSION['notificationmsg'] = 'Der Nutzer konnte sich nicht authentifizieren.';
				header('location: session.php');
			}

		} 

	if ($order_exists == null) {
		$_SESSION['notification'] = true;
		$_SESSION['notificationmsg'] = 'Es konnte keine übereinstimmende Bestellung gefunden werden.';
		header('location: session.php');
	}
?>

</div>

<div style="margin-top: 500px;">
</div>
<script src="js/signature_pad.js"></script>
<script type="text/javascript">
	
	var canvas = document.querySelector("canvas");

	var signaturePad = new SignaturePad(canvas);

	var wrapper = document.getElementById("signature-pad");
	var clearButton = wrapper.querySelector("[data-action=clear]");
	
	clearButton.addEventListener("click", function (event) {
  		signaturePad.clear();
	});

	var btn = document.getElementById('submit');

	btn.addEventListener('click', function () {

	// Returns true if canvas is empty, otherwise returns false
	if (signaturePad.isEmpty()) {
		alert('Bitte unterschreiben!');
	} else {
		SignaturePad.prototype.removeBlanks = function () {
        var imgWidth = this._ctx.canvas.width;
        var imgHeight = this._ctx.canvas.height;
        var imageData = this._ctx.getImageData(0, 0, imgWidth, imgHeight),
        data = imageData.data,
        getAlpha = function(x, y) {
            return data[(imgWidth*y + x) * 4 + 3]
        },
        scanY = function (fromTop) {
            var offset = fromTop ? 1 : -1;

            // loop through each row
            for(var y = fromTop ? 0 : imgHeight - 1; fromTop ? (y < imgHeight) : (y > -1); y += offset) {

                // loop through each column
                for(var x = 0; x < imgWidth; x++) {
                    if (getAlpha(x, y)) {
                        return y;                        
                    }      
                }
            }
            return null; // all image is white
        },
        scanX = function (fromLeft) {
            var offset = fromLeft? 1 : -1;

            // loop through each column
            for(var x = fromLeft ? 0 : imgWidth - 1; fromLeft ? (x < imgWidth) : (x > -1); x += offset) {

                // loop through each row
                for(var y = 0; y < imgHeight; y++) {
                    if (getAlpha(x, y)) {
                        return x;                        
                    }      
                }
            }
            return null; // all image is white
        };

        var cropTop = scanY(true),
        cropBottom = scanY(false),
        cropLeft = scanX(true),
        cropRight = scanX(false);

        var relevantData = this._ctx.getImageData(cropLeft, cropTop, cropRight-cropLeft, cropBottom-cropTop);
        this._canvas.width = cropRight-cropLeft;
        this._canvas.height = cropBottom-cropTop;
        this._ctx.clearRect(0, 0, cropRight-cropLeft, cropBottom-cropTop);
        this._ctx.putImageData(relevantData, 0, 0);
    	};

    	signaturePad.removeBlanks();
		var data = signaturePad.toDataURL();
		var form = document.getElementById('signature');
		form.value = data;
	}

	});

</script>

<?php
include("templates/footer.inc.php")
?>
