<?php
require_once('Mail.inc.php');

class Cart {

	private static function saveInSession($type, $new_item) {
		if (isset($_SESSION[$type][$new_item['pro_id']])) {
			if (isset($_SESSION[$type][$new_item['pro_id']][$new_item['pid']])) {
				//unset old item
				unset($_SESSION[$type][$new_item['pro_id']][$new_item['pid']]); 
			}
		}
		//update products with new item array
		$_SESSION[$type][$new_item['pro_id']][$new_item['pid']] = $new_item; 
	}

	public static function delete($key, $pid) {
		if (!empty($_SESSION[$key])) {
			foreach ($_SESSION[$key] as $pro_id => $producer) {
				if (isset($producer[$pid])) {
					unset($_SESSION[$key][$pro_id][$pid]);
					if (count($_SESSION[$key][$pro_id]) == 0) {
						unset($_SESSION[$key][$pro_id]);
					}
				}   
			}
		}
	}

	private $db;
	private $order_ids = "";
	private $cart;
	private $table;
	private $grandtotal = 0;
	private $itemConflicts = [];

	public function __construct() {
		global $db;
		$this->db = $db;
	}

	public function insert($new_item) {
		$pid = $new_item['pid'];
    	$quantity = $new_item['quantity'];
    	$stock = $this->db->getStock($pid);

		
		$deficit = $stock - $quantity;

		if ($deficit >= 0) {
			Cart::saveInSession('orders', $new_item);
		} else {
			if ($stock <= 0) {
				Cart::saveInSession('preorders', $new_item);
			} else {
				$new_item['quantity'] = $stock;
				Cart::saveInSession('orders', $new_item);
				$new_item['quantity'] = $quantity - $stock;
				Cart::saveInSession('preorders', $new_item);
			}
		}
	}

	public function update() {
		foreach ($_SESSION['orders'] as $pro_id => $producer) {
			foreach ($producer as $pid => $item) {
				if ($this->hasDeficit($item)) {
					$stock = $this->db->getStock($pid);
					$old_quantity = $item['quantity'];
					$preorder_quantity = $old_quantity - $stock;
					if ($stock > 0) {
						$_SESSION['orders'][$pro_id][$pid]['quantity'] = $stock;
					} else {
						self::delete('orders', $pid);
					}
					if (array_key_exists($pid, $_SESSION['preorders'][$pro_id])) {
						$_SESSION['preorders'][$pro_id][$pid]['quantity'] += $preorder_quantity;
					} else {
						$_SESSION['preorders'][$pro_id][$pid] = $item;
						$_SESSION['preorders'][$pro_id][$pid]['quantity'] = $preorder_quantity;
					}
				}
			}
		}
	}

	public function toHTML($cart, $type, $functions=false) {
		global $currency;
		$html = "";
		$buttons = "";
		$total_data = "";

		// sort by producer
		foreach ($cart as $pro_id => $producer) {
			$total = 0;
			$html .= '
			<h3>'. $this->db->getProducer($pro_id) .'</h3>
			<div class="center">
			<table class="cartTable">
				<tr style="text-align: left;">
					<th>Artikel</th><th>Preis/Einheit</th>
					<th>Einheiten</th>
					<th>Menge</th>
					<th>&#931;</th>
				</tr>';

			// items from producer
			foreach ($producer as $product) {
				$price_KG_L = $product["price_KG_L"];
				$pid = $product["pid"];
				$quantity = $product["quantity"];
				$productName = $product['productName'];
				$unit_size = $product['unit_size'];
				$unit_tag = $product['unit_tag'];
				$item_total = ($price_KG_L * $quantity);
				$total += $item_total;
				$amount = $quantity * $unit_size;

				// delete function wanted?
				if ($functions) {
					$buttons = '
					<td>
						<input type="hidden" name="item_total" value="'. $item_total .'">
						<a href="#" class="remove-item" type="'. $type .'" data-code="'. $pid. '"><i class="fa fa-trash-o" aria-hidden="true"></i>
						</a>
					</td>';
				}

				$html .= '
				<tr>
					<td>'. htmlspecialchars($productName) .'</td>
					<td>'. sprintf("%01.2f %s", $price_KG_L, $currency) . "/" . $unit_size. $unit_tag .'</td>
					<td>'. $quantity .'</td>
					<td>'. $amount . $unit_tag.'</td>
					<td>'. sprintf("%01.2f %s", $item_total, $currency). '</td>
					'. $buttons .'
				</tr>';
			}

			if ($functions) {
				$total_data = '<input type="hidden" name="total" value="'. $total .'">';
			}
			$this->grandtotal += $total;
			$html .= '
				<tr>
					<td></td>	
					<td></td>
					<td></td>
					<td></td>
					<td class="total" style="font-weight: 600">'
					. $total_data
					. sprintf("%01.2f %s", $total, $currency).' </td>
				</tr>
			</table>
			</div>';

		}
		return $html;
	}

	public function process($uid, $orders, $preorders=null) {
		if (!empty($orders)) {
			$this->save($orders, 'order', $uid);
		}

		if (!empty($preorders)) {
			$this->save($preorders, 'preorder', $uid);
		}
		$this->table = $this->buildHTML($orders, $preorders);
	}

	public function buildHTML($orders, $preorders, $functions=false) {
		$html = "";
		if (!empty($orders)) {
			$html .= '
			<div>
				<h2>Deine Bestellung</h2>
				<br>'. 
				$this->toHTML($orders, 'orders', $functions). '
			</div>';
		}

		if (!empty($preorders)) {
			$html .= '
			<div>
				<h2>Deine Vorbestellung</h2>
				<br>'. 
				$this->toHTML($preorders, 'preorders', $functions) . '
			</div>';
		}
		return $html;
	}

	public function save($cart, $type, $uid) {
		$first = true;

		foreach ($cart as $pro_id => $producer) {
			$oid = $this->db->insert($type, $uid);

			if ($type == 'order') {
				$this->order_ids .= $first ? $oid : " + " . $oid;
				if ($first) $first = false;
			}

			foreach ($producer as $item) {
				$this->db->insertItem($type, $oid, $item);
			}
		}
	}

	public function hasConflict() {
		$error = false;
		foreach ($_SESSION['orders'] as $producer) {
			foreach ($producer as $item) {
				if ($this->hasDeficit($item)) {
					$error = true;
					$this->itemConflicts[] = $item['productName'];
				}
			}
		}
		return $error;
	}

	private function hasDeficit($item) {
		return $this->getDeficit($item) < 0;
	}

	private function getDeficit($item) {
		return $this->db->getStock($item['pid']) - $item['quantity'];
	}

	public function mail($user, $smtp_host, $smtp_username, $smtp_password, $myEmail, $myEntity) {

		// mail info
		$email = $user['email'];
		$first_name = $user['first_name'];
		$last_name = $user['last_name'];
		$subject = empty($this->order_ids) ? 'Vorbestellung' : 'Bestellung Nr. '. $this->order_ids;
		$text = '
		<h1>Moin, '. htmlspecialchars($first_name) .'!</h1>
		<p>Hiermit, bestätigen wir, dass Deine Bestellung bei uns eingangen ist. Zur Übersicht noch einmal eine Aufstellung der Artikel:
		<br><br>
		'. $this->table .'
		<br><br>
		Denk daran, Deine Bestellung als abgeholt zu markieren unter <a href="https://m.namiko.org/my-orders.php">Meine Bestellungen</a>.
		</p>';

		// send mail
		$mail = new Mail($smtp_host, $smtp_username, $smtp_password, $myEmail, $myEntity);
		$mail->send($email, $first_name. " " .$last_name, $subject, $text, true);

	}

	public function getGrandtotal() {
		return $this->grandtotal;
	}

	public function getItemConflicts() {
		return json_encode($this->itemConflicts);
	}
}
?>