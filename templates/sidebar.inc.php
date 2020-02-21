<div class="sidebar">
	<h4 class="white">Suchen <i class="fa fa-search" aria-hidden="true"></i></h4>
	<div>
		<form id="search-items">
		<input class="inline search" type="text" name="search" placeholder="Hier schreiben">
		<button class="empty search-btn inline" type="submit"><i class="fa fa-hand-o-right" aria-hidden="true"></i></button>
		</form>
	</div>

	<h4 class="white">Filter <i class="fa fa-filter" aria-hidden="true"></i></h4>
	<div class="indent">
		<form class="spacer2 filter">
			<div class="indent spacer2">
				<span class="subtitle">Kategorien</span>
				<div><label><input id="all" class="category" type="checkbox" name="category[]" value="0" id="all" checked> alle</label></div>
				<hr class="separator">
				<div>
					<?php
					$statement = $pdo->prepare("SELECT * FROM categories WHERE cid > 1 ORDER BY cid");
					$result = $statement->execute();

					if ($statement->rowCount() > 0) {
						while ($row = $statement->fetch()) {
							echo '<div><label><input type="checkbox" name="category[]" class="category other" value="'. $row['cid'] .'"> '. $row['category_name'] .'</label></div>';
						}
					} else {
						echo 'Keine Kategorien gefunden.';
					}
					?>
				</div>
			</div>
			<div class="indent spacer2">
				<span class="subtitle">Lieferant</span>
				<div><label><input id="allprod" class="producer" type="checkbox" name="producer[]" value="0" id="all" checked> alle</label></div>
				<hr class="separator">
				<?php
				$statement = $pdo->prepare("SELECT * FROM producers ORDER BY pro_id");
				$result = $statement->execute();

				if ($statement->rowCount() > 0) {
					while ($row = $statement->fetch()) {
						echo '<div><label><input type="checkbox" name="producer[]" class="otherprod" value="'. $row['pro_id'] .'" unchecked> '. $row['producerName'] .'</label></div>';
					}
				} else {
					echo 'Keine Orte gefunden.';
				}
				?>
			</div>
			<button type="submit" name="filterSubmit" class="empty blue">Aktualisieren <i class="fa fa-repeat" aria-hidden="true"></i></button>
		</form>
	</div>
</div>