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

	public static function createTable($list, $currency, $functions=false, $type='order') {
		$total = 0;
		$total_data = "";
		$description = $type == 'my-orders' ? '<th>Abgeholt?</th>' : '<th></th>';
		$html = '
		<div class="center">
		<table class="cartTable">
			<tr style="text-align: left;">
				<th>Artikel</th>
				<th>Preis/E</th>
				<th>Einheiten</th>
				<th>Menge</th>
				<th>&#931;</th>'.
				$description .'
			</tr>';

		foreach ($list as $product) {
			$buttons = '';
			$price_KG_L = $product["price_KG_L"];
			$pid = $product["pid"];
			$quantity = $product["quantity"] * 1;
			$productName = $product['productName'];
			$unit_size = $product['unit_size']*1;
			$unit_tag = $product['unit_tag'];
			$item_total = ($price_KG_L * $quantity);
			$total += $item_total;
			$amount = $quantity * $unit_size;

			// delete function wanted?
			if ($functions) {
				if ($type == 'my-orders') {
					if ($product['delivered']) {
						$buttons = '
						<td>
							<span class="green font-size18"><i class="fa fa-check-square-o" aria-hidden="true"></i></span>
						</td>';
					} else {
						$buttons = '
						<td>
							<a href="#" class="mark-delivered red font-size18" oi_id="'. $product['oi_id'] . '"><i class="fa fa-minus-square-o" aria-hidden="true"></i></a>
						</td>';
					}
				} else {
					$buttons = '
					<td>
						<input type="hidden" name="item_total" value="'. $item_total .'">
						<a href="#" class="remove-item" type="'. $type .'" data-code="'. $pid. '"><i class="fa fa-trash-o" aria-hidden="true"></i>
						</a>
					</td>';
				}
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
		$html .= '
			<tr>
				<td></td>	
				<td></td>
				<td></td>
				<td></td>
				<td class="total" style="font-weight: 600; text-align: left;">'
				. $total_data
				. sprintf("%01.2f %s", $total, $currency).' </td>
			</tr>
		</table>
		</div>';

		return array('html' => $html, 'total' => $total);
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
		/*foreach ($_SESSION['preorders'] as $pro_id => $producer) {
			foreach ($producer as $pid => $item) {
				if ($this->hasSurplus($item)) {
					$stock = $this->db->getStock($pid);
					$old_quantity = $item['quantity'];
					$surplus = $stock - $old_quantity;

					if ($surplus >= 0) {
						self::delete('preorders', $pid);
						$quantity_to_order = $old_quantity;
							
					} else {
						$quantity_to_order = $stock;
						$_SESSION['preorders'][$pro_id][$pid]['quantity'] = abs($surplus);
						
					}

					if (array_key_exists($pid, $_SESSION['orders'][$pro_id])) {
						$_SESSION['orders'][$pro_id][$pid]['quantity'] += $quantity_to_order;
					} else {
						$_SESSION['orders'][$pro_id][$pid] = $item;
						$_SESSION['orders'][$pro_id][$pid]['quantity'] = $quantity_to_order;
					}
				}
			}
		}*/

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
			$html.= '<h3>'. $this->db->getProducer($pro_id) .'</h3>';
			$table = self::createTable($producer, $currency, $functions, $type);
			// items from producer
			$html .= $table['html'];

			
			$this->grandtotal += $table['total'];
			

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
				<h2 class="green">Deine Bestellung</h2>
				<br>'. 
				$this->toHTML($orders, 'orders', $functions). '
			</div>';
		}

		if (!empty($preorders)) {
			$html .= '
			<div>
				<h2 class="blue">Deine Vorbestellung</h2>
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

		/*foreach ($_SESSION['preorders'] as $producer) {
			foreach ($producer as $item) {
				if ($this->hasSurplus($item)) {
					$error = true;
					$this->itemConflicts[] = $item['productName'];
				}
			}
		}*/
		return $error;
	}

	private function hasDeficit($item) {
		return $this->getDeficit($item) < 0;
	}

	private function hasSurplus($item) {
		$deficit = $this->getDeficit($item);
		return $deficit >= 0 || abs($deficit)  != $item['quantity'];
	}

	private function getDeficit($item) {
		return $this->db->getStock($item['pid']) - $item['quantity'];
	}

	public function mail($user, $smtp_host, $smtp_username, $smtp_password, $myEmail, $myEntity, $standardSubject=true, $standardText=true) {

		// mail info
		$email = $user['email'];
		$first_name = $user['first_name'];
		$last_name = $user['last_name'];

		if ($standardSubject) {
			$subject = empty($this->order_ids) ? 'Vorbestellung' : 'Bestellung Nr. '. $this->order_ids;
		} else {
			$subject = 'Deine Vorbestellung ist auf dem Weg! (Bestellung #' . $this->order_ids . ')';
		}

		$text = '<h1>Moin, '. htmlspecialchars($first_name) .'!</h1>';

		if ($standardText) {
			$text .= '
			<p>Hiermit, bestätigen wir, dass Deine Bestellung bei uns eingangen ist. Zur Übersicht noch einmal eine Aufstellung der Artikel:';
		} else {
			$text .= '<p>Wir haben einen Teil (oder alle) Deiner Vorbestellungen nun in eine Bestellung umgewandelt. Im Folgenden siehst Du einer Aufstellung der Artikel, die für dich bestellt wurde:</p>';
		}

		$text .= '<br><br>
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