<?php
session_start();
require_once("inc/config.inc.php");
require_once("inc/functions.inc.php");
require_once("inc/SEPAprocedure.inc.php");

//Überprüfe, dass der User eingeloggt ist
//Der Aufruf von check_user() muss in alle internen Seiten eingebaut sein
$user = check_user();
check_consul();

include("templates/header.inc.php");
include("templates/nav.inc.php");
include("templates/admin-nav.inc.php");


if (isset($_POST['toggleTimeframe'])) {
	$_SESSION['timeframe'] = $_POST['timeframe'];

	if ($_SESSION['timeframe'] == 0) {
		$_SESSION['timeToggle'] = '';
	} else {
		$_SESSION['timeToggle'] = " AND (orders.created_at < '". $_SESSION['timeframe'] ."')";
	}
}
/*
$curr = date('Y-m-d H:i:s');
$statement = $pdo->prepare("SELECT start FROM events WHERE type = 1 AND start > '$curr' ORDER BY start ASC");
$result = $statement->execute();
$timeframeOut = '';

while ($row = $statement->fetch()) {
	$nextSession = date_create_from_format('Y-m-d H:i:s', $row['start']);
	$calc = clone $nextSession;

	$last = date_sub($calc, date_interval_create_from_date_string($lastPossibleOrder));

	$timeframeOut .= '<option value="'. $last->format('Y-m-d H:i:s') .'"';

	if ($last->format('Y-m-d H:i:s') == $_SESSION['timeframe']) {
		$timeframeOut .= 'selected="selected"';
	}

	$timeframeOut .= '>'. $last->format('d.m.Y H:i:s') .' ('. $nextSession->format('d.m.Y H:i:s') .')</option>';
}*/


if (isset($_POST['memberPay'])) {
	$creator = $user['uid'];
	$mails = [];
	$collectionDt = $_POST['date'];
	$transactions = [];
	
	try {
		$pdo->beginTransaction();
		$sepa = new SEPAprocedure($pdo, $creator, $collectionDt, $myEntity, $myIBAN, $myBIC, $creditorId, $user['first_name']. ' ' .$user['last_name']);

		$statement = $pdo->prepare("
			SELECT users.*, mandates.mid, mandates.created_at AS cd FROM users 
			LEFT JOIN mandates ON users.uid = mandates.uid 
			WHERE users.rights > 1 
			AND users.rights < 5 
			AND (NOT EXISTS (SELECT created_at FROM contributions WHERE users.uid = contributions.uid LIMIT 1) 
			OR (DATEDIFF(NOW(), 
			(SELECT MAX(created_at) FROM contributions WHERE uid = users.uid LIMIT 1) ) >= 87))");
		$result = $statement->execute();

		while ($row = $statement->fetch()) {
			$uid = $row['uid'];
			$transactions[$uid]['first_name'] = $row['first_name'];
			$transactions[$uid]['last_name'] = $row['last_name'];
			$transactions[$uid]['email'] = $row['email'];
			$transactions[$uid]['account_holder'] = $row['account_holder'];
			$transactions[$uid]['IBAN'] = $row['IBAN'];
			$transactions[$uid]['BIC'] = $row['BIC'];
			$transactions[$uid]['mid'] = $row['mid'];
			$transactions[$uid]['signed'] = substr($row['cd'], 0, 10);
			$transactions[$uid]['instdAmt'] = (3 * $row['contribution']);
			$transactions[$uid]['rmtInf'] = 'Mitgliedsbeitrag für 3 Monate';
		}

		$sepa->insertTx($transactions, "contributions");
		$sepa->create();
		$sepa->notify($smtp_host, $smtp_username, $smtp_password, $myEmail, $myEntity);
		$pdo->commit();
		$sepa->startDownload();
	} catch (Exception $e) {
		$pdo->rollBack();
		error($e->getMessage());
	}

}

////////////////////////////////////// 

if(isset($_POST['orderPay'])) {
	$creator = $user['uid'];
	$mails = [];
	$collectionDt = $_POST['date'];
	$transactions = [];
	$uid = -1;

	try {
		$pdo->beginTransaction();
		$sepa = new SEPAprocedure($pdo, $creator, $collectionDt, $myEntity, $myIBAN, $myBIC, $creditorId, $user['first_name']. ' ' .$user['last_name']);

		$statement = $pdo->prepare("
			SELECT orders.oid, users.*, mandates.mid, mandates.created_at AS cd, SUM(order_items.total) AS total
			FROM orders 
			LEFT JOIN users ON orders.uid = users.uid 
			LEFT JOIN order_items ON orders.oid = order_items.oid 
			LEFT JOIN mandates ON users.uid = mandates.uid
			WHERE (orders.paid = 0) ". $_SESSION['timeToggle'] ."
			GROUP BY orders.oid
			ORDER BY users.uid, orders.oid ASC");
		$result = $statement->execute();

		if ($result && $statement->rowCount() > 0) {
			while($row = $statement->fetch()) {
				$oid = $row['oid'];
				if ($row['uid'] != $uid) {
					$uid = $row['uid'];
					$transactions[$uid]['first_name'] = $row['first_name'];
					$transactions[$uid]['last_name'] = $row['last_name'];
					$transactions[$uid]['email'] = $row['email'];
					$transactions[$uid]['account_holder'] = $row['account_holder'];
					$transactions[$uid]['IBAN'] = $row['IBAN'];
					$transactions[$uid]['BIC'] = $row['BIC'];
					$transactions[$uid]['mid'] = $row['mid'];
					$transactions[$uid]['signed'] = substr($row['cd'], 0, 10);
					$transactions[$uid]['instdAmt'] = $row['total'];
					$transactions[$uid]['rmtInf'] = "Bestellung Nr. " . $oid;

				} else {
					$transactions[$uid]['instdAmt'] += $row['total'];
					$transactions[$uid]['rmtInf'] .= " + " . $row['oid'];

					if (strlen($transactions[$uid]['rmtInf']) > 140) {
						$transactions[$uid]['rmtInf'] = substr($transactions[$uid]['rmtInf'], 0, 139);
					}
				}
				$statement2 = $pdo->prepare("UPDATE orders SET paid = 1 WHERE oid= '$oid'");
				$result2 = $statement2->execute();

				if (!$result2) {
					throw new Exception(json_encode($statement2->errorInfo()));
					
				}
			}
		} else {
			notify("Keine offenen Zahlungen gefunden.");
		}

		$sepa->insertTx($transactions);
		$sepa->create();
		$sepa->notify($smtp_host, $smtp_username, $smtp_password, $myEmail, $myEntity);
		$pdo->commit();
		$sepa->startDownload();
	} catch (Exception $e) {
		$pdo->rollBack();
		error($e->getMessage());
	}
}

?>

<div class="sizer">
	<div class="row">
		<div class="col-md-6 spacer">
			<span class="subtitle2">Mitgliedsbeiträge einziehen</span><br>
			<p>aktuelle Höhe der Quartalsbezüge:
				<span class="green emph">
					<?php
						$statement = $pdo->prepare("SELECT users.* FROM users WHERE users.rights > 1 AND users.rights < 5 AND (NOT EXISTS (SELECT * FROM contributions WHERE users.uid = contributions.uid) OR (DATEDIFF(NOW(), (SELECT MAX(created_at) FROM contributions WHERE uid = users.uid LIMIT 1) ) >= 87))");
						$result = $statement->execute();

						$total = 0;

						while ($row = $statement->fetch()) {
							$contribution = $row['contribution'];
							$quartalsbeitrag = ($contribution *3);
							$total += $quartalsbeitrag;
						}

						echo $currency.sprintf("%01.2f", ($total));
					?>
				</span>
			</p><br><br>
			<span><i class="fa fa-info-circle" aria-hidden="true"></i> Bei Erstellung des Dokuments wird automatisch an alle Mitglieder eine Email verschickt, die über den Einzug des Geldes informiert. Abhängig von der Internetverbindung kann dies etwas dauern, also den Tab offen lassen, nicht neu laden, bis der Download des Dokuments erscheint.<br>Es muss wie folgt berechnet werden: Aktueller Tag + 2 Bankarbeitstage (TARGET2)!</span><br><br>

			<form action="<?php htmlspecialchars($_SERVER['PHP_SELF']) ?>" method="post" class="form">
				<input type="date" name="date" placeholder="fälligskeitsdatum" required>
				<button class="clean-btn green" name="memberPay" type="submit">XML erstellen <i class="fa fa-file-text-o" aria-hidden="true"></i></button>
			</form>
		</div>
		<div class="col-md-6 spacer">
			<span class="subtitle2">Offene Lastschriften einziehen</span><br>
			<p>aktuelle Höhe der Lastschriften:
				<span class="green emph">
					<?php
						$total = 0;
						$statement = $pdo->prepare("SELECT orders.oid, orders.paid, order_items.total FROM orders LEFT JOIN order_items ON order_items.oid = orders.oid WHERE (orders.paid = 0)". $_SESSION['timeToggle'] ."");
						$result = $statement->execute();

						while ($row = $statement->fetch()) {
							$item_sum = $row['total'];
							$total += $item_sum;
						}

						echo $currency.sprintf("%01.2f", ($total));
					?>
				</span><br>
			</p><br><br>
			<span><i class="fa fa-info-circle" aria-hidden="true"></i> Bei Erstellung des Dokuments wird automatisch an alle Mitglieder eine Email verschickt, die über den Einzug des Geldes informiert. Abhängig von der Internetverbindung kann dies etwas dauern, also den Tab offen lassen, nicht neu laden, bis der Download des Dokuments erscheint.<br>Es muss wie folgt berechnet werden: Aktueller Tag + 2 Bankarbeitstage (TARGET2)!</span><br><br>

			<form class="form" method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']) ?>">
				<!--<select name="timeframe">
					<option value="0">Alle</option>
					<optgroup label="Zeitpunkte">
					
					</optgroup>
				</select>-->
				<input type="date" name="timeframe" value="<?php echo $_SESSION['timeframe'] ?>">
				<button type="submit" class="clean-btn blue" name="toggleTimeframe">Aktualisieren <i class="fa fa-refresh" aria-hidden="true"></i></button>
			</form><br>

			<form action="<?php htmlspecialchars($_SERVER['PHP_SELF']) ?>" method="post" class="form">
				<input type="date" name="date" placeholder="fälligskeitsdatum" required>
				<button class="clean-btn green" name="orderPay" type="submit">XML erstellen <i class="fa fa-file-text-o" aria-hidden="true"></i></button>
			</form>
		</div>
	</div>
	<div class="row">
		<div class="col-md-6 spacer">
			<span class="subtitle2">Mitgliedsbeiträge</span><br>
			<p>Summe der eingezogenen Mitgliedsbeiträge:
				<span class="green emph">
				<?php
				$statement = $pdo->prepare("SELECT users.contribution FROM contributions LEFT JOIN users ON contributions.uid = users.uid");
				$result = $statement->execute();

				while ($row = $statement->fetch()) {
					$sum += $row['contribution']*3;
				}
				echo sprintf("%01.2f", $sum).$currency;
				?>
				</span>
			</p>
		</div>
		<div class="col-md-6 spacer">
			<span class="subtitle2">Mitgliedsdarlehen</span><br>
			<p>Summe der eingezogenen Darlehen:
				<span class="green emph">
				<?php
				$sum = 0;
				$statement = $pdo->prepare("SELECT users.loan FROM users LEFT JOIN loans ON users.uid = loans.uid WHERE loans.recieved = 1");
				$result = $statement->execute();

				while ($row = $statement->fetch()) {
					$sum += $row['loan'];
				}
				echo sprintf("%01.2f", $sum).$currency;
				?>
				</span>
			</p>
		</div>
	</div>
	<div class="spacer full">
	<span class="subtitle2">Kontoinformationen</span><br><br>
		<table class="table panel panel-default" style="min-width: 820px">
		<tr>
			<th>#</th>
			<th>Vorname</th>
			<th>Nachname</th>
			<th>Kontoinhaber</th>
			<th>IBAN</th>
			<th>BIC</th>
			<th>Darlehen</th>
			<th>Beitrag</th>
		</tr>
		<?php 
		$count = 1;
		$statement = $pdo->prepare("SELECT * FROM users ORDER BY uid");
		$result = $statement->execute();
		
		
		while($row = $statement->fetch()) {
			echo "<tr>";
			echo "<td>";
				echo $count++;
				if ($row['rights'] == 1) echo ' <span class="inline emph red"><i class="fa fa-exclamation-triangle" aria-hidden="true"></i></span>';
			echo "</td>";
			echo "<td>". htmlspecialchars($row['first_name']) ."</td>";
			echo "<td>". htmlspecialchars($row['last_name']) ."</td>";
			echo '<td>'. htmlspecialchars($row['account_holder']) .'</td>';
			echo '<td>'. $row['IBAN'] .'</td>';
			echo '<td>'. $row['BIC'] . '</td>';
			echo '<td>'. sprintf("%01.2f", $row['loan']). $currency .'</td>';
			echo '<td>'. sprintf("%01.2f", $row['contribution']). $currency .'</td>';
			echo "</tr>";
		}
		?>
		</table>
	</div>
</div>

<?php 
include("templates/footer.inc.php")
?>