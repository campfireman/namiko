<?php

class Bill {
	public $bill = "";
	private $grandtotal = 0;
	private $count = 1;

	function __construct($bid, $created_at, $organization, $first_name, $last_name, $street, $street_number, $postal_code, $region, $email) {
		global $myEntity;
		global $myStreet;
		global $myRegion;
		global $myEmail;
		global $myWebsite;
		global $myIBAN;
		global $myBIC;
		global $tax_number;

		$rechnung_header = $myEntity."<br>".
						$myStreet."<br>".
						$myRegion."<br>".
						$myEmail."<br>".
						$myWebsite."<br>".
						$tax_number."<br>".
						$myIBAN."<br>".
						$myBIC;

		// create contract, split document to insert data later
		$this->bill .= '<div>
		<span style="font-size: 3.0em; font-weight: bold;">Rechnung #'. $bid .'</span><br><span>'. $created_at .'</span>

		<br><br><br>
		<br><br><br>

		<table cellpadding="5" cellspacing="0" style="width: 100%; font-size: 1.3em;">
			<tr>
				<td>'. htmlentities($organization) .'<br>
				'. htmlentities($first_name).' '. htmlentities($last_name) .'<br>
				'.  htmlentities($street) . ' ' . htmlentities($street_number) .'<br>
				'.  htmlentities($postal_code) .' '. htmlentities($region) .'<br>
				'.  htmlentities($email) .'<br>
				</td>

				<td style="text-align: right">
				'.nl2br(trim($rechnung_header)).'
				</td>
			</tr>
		</table>

		<br><br><br>
		<br><br><br>

		<table style="width: 100%; font-size: 1.3em;">
			<tr style="font-weight: bold; margin-bottom: 10px">
				<th>Posten</th>
				<th>ID</th>
				<th>Artikel</th>
				<th>Preis/E</th>
				<th>Einheiten</th>
				<th>Menge</th>
				<th>Summe</th>
			</tr>
		 ';
	}

	function insertItem ($pid, $article, $unit_price, $quantity, $unit_size, $unit_tag) {
		global $currency;
		$total = $quantity * $unit_price;
		$this->grandtotal += $total;
		$this->bill .= '
		<tr>
			<td>'. $this->count++ . '</td>
			<td>' . $pid .'</td>
			<td>'. $article .'</td>
			<td>'. sprintf('%01.2f', $unit_price).$currency .'</td>
			<td>'. $quantity .'</td>
			<td>'. $unit_size * $quantity . $unit_tag .'</td>
			<td>'. sprintf('%01.2f', $total).$currency .'</td>
		</tr>
			';
	}

	function createBill ($tax_rate) {
		global $currency;
		$netto = round_up($this->grandtotal/(1 + $tax_rate), 2);
		$tax = $this->grandtotal - $netto;
		$this->bill .= '	
			<tr>
				<td></td><td></td>
				<td></td><td></td>
				<td></td>
				<td>Netto</td>
				<td>'. sprintf('%01.2f', $netto).$currency .'</td>
			</tr>
			<tr>
				<td></td><td></td>
				<td></td><td></td>
				<td></td>
				<td>Mwst. '. $tax_rate * 100 .'%</td>
				<td>'. sprintf('%01.2f', abs($tax)).$currency .'</td>
			</tr>
			<tr style="font-weight: bold;">
				<td></td><td></td>
				<td></td><td></td>
				<td></td>
				<td>Brutto</td>
				<td>'. sprintf('%01.2f', $this->grandtotal).$currency .'</td>
			</tr>
		</table>';
	}
}


?>