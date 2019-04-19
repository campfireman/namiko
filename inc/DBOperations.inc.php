<?php
class DBOperations {
	private $pdo;

	public function __construct($pdo) {
		$this->pdo = $pdo;
	}

	private function query($stmnt) {
		$statement = $this->pdo->prepare($stmnt);
		$result = $statement->execute();

		if (!$result) {
			throw new Exception(json_encode($statement->errorInfo()));
		}
		$result = $statement->fetchAll();
		return $result[0];
	}

	public function getProducer($pro_id) {
		$statement = $this->pdo->prepare("SELECT producerName FROM producers WHERE pro_id = '$pro_id'");
		$statement->execute();
		$row = $statement->fetch();

		return $row['producerName'];
	}

	public function getStock($pid) {
		return $this->getRealStock($pid) - $this->getTotalOrders($pid);
	}

	public function getRealStock($pid) {
		$statement = $this->pdo->prepare("SELECT quantity_KG_L AS quantity FROM inventory_items WHERE pid = :pid");
		$statement->bindParam(':pid', $pid);
		$result = $statement->execute();

		if (!$result) {
			throw new Exception(json_encode($statement->errorInfo()));
		}

		$row = $statement->fetch();

		if ($statement->rowCount() > 0 && !empty($row['quantity'])) {
			return $row['quantity'];
		} else {
			return 0;
		}
	}

	public function getTotalOrders($pid) {
		$statement = $this->pdo->prepare("SELECT SUM(order_items.quantity) AS sum FROM order_items LEFT JOIN orders ON order_items.oid = orders.oid WHERE (order_items.pid = :pid) AND (orders.delivered = 0)");
		$statement->bindParam(':pid', $pid);
		$result = $statement->execute();
		
		if (!$result) {
			throw new Exception(json_encode($statement->errorInfo()));
		}

		$row = $statement->fetch();

		if ($statement->rowCount() > 0 && !empty($row['sum'])) {
			return $row['sum'];
		} else {
			return 0;
		}
	}

	public function getPreorders($pid) {
		$statement = $this->pdo->prepare("SELECT SUM(preorder_items.quantity) AS sum FROM preorder_items WHERE (preorder_items.pid = :pid) AND (preorder_items.transferred = 0)");
		$statement->bindParam(':pid', $pid);
		$result = $statement->execute();
		
		if (!$result) {
			throw new Exception(json_encode($statement->errorInfo()));
		}

		$row = $statement->fetch();

		if ($statement->rowCount() > 0 && !empty($row['sum'])) {
			return $row['sum'];
		} else {
			return 0;
		}
	}

	public function insertOrder($uid) {
		return $this->insert('order', $uid);
	}

	public function insertPreorder($uid) {
		return $this->insert('preorder', $uid);
		
	}

	public function insert($type, $uid) {
		$statement = $this->pdo->prepare("INSERT INTO ". $type ."s (uid) VALUES ($uid)");
		$result = $statement->execute();

		if (!$result) {
			throw new Exception(json_encode($statement->errorInfo()));
		}

		return $this->pdo->lastInsertId();
	}

	public function insertItem($type ,$oid, $item) {
		$price_KG_L = $item["price_KG_L"];
		$pid = $item["pid"];
		$quantity = $item["quantity"];
		$total = ($price_KG_L * $quantity);

		$statement = $this->pdo->prepare("INSERT INTO ". $type ."_items (pid, oid, quantity, total) VALUES (:pid, :oid, :quantity, :total)");
		$result = $statement->execute(array('pid' => $pid, 'oid' => $oid, 'quantity' => $quantity, 'total' => $total));

		if (!$result) {
			throw new Exception(json_encode($statement->errorInfo()));
			
		}
		return $total;
	}

	public function lock($tables, $operation) {
		if (!empty($tables)) {
			$stmnt = "LOCK TABLES";
			$first = true;
			foreach ($tables as $table) {
				$comma = $first ? " " : ", ";
				$first = false;
				$stmnt .= $comma . $table . " " . $operation;
			}
			$statement = $this->pdo->prepare($stmnt);
			$result = $statement->execute();

			if (!$result) {
				throw new Exception(json_encode($statement->errorInfo()));
			}
		} else {
			throw new InvalidArgumentExeption("Leere Liste von Tabellen übergeben");
		}
	}

	public function unlock() {
		$statement = $this->pdo->prepare("UNLOCK TABLES");
		$result = $statement->execute();

		if (!$result) {
			throw new Exception(json_encode($statement->errorInfo()));
		}
	}

	public function updatePreorderItem($oi_id, $quantity) {
		$statement = $this->pdo->prepare("UPDATE preorder_items SET quantity = '$quantity' WHERE oi_id = '$oi_id'");
		$result = $statement->execute();

		if (!$result) {
			throw new Exception(json_encode($statement->errorInfo()));
		}
	}

	public function markTransferred($oi_id) {
		$statement = $this->pdo->prepare("UPDATE preorder_items SET transferred = 1 WHERE oi_id = '$oi_id'");
		$result = $statement->execute();

		if (!$result) {
			throw new Exception(json_encode($statement->errorInfo()));
		}
	}

	public function getUser($uid) {
		$stmnt = "SELECT * FROM users WHERE uid = '$uid'";
		return $this->query($stmnt);
	}
}
?>