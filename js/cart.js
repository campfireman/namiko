$(document).ready(function() {
	$("#cartContent").on('click', 'a.remove-item', function(e) {
		e.preventDefault();
		// get product id
		var pid = $(this).attr("data-code");
		var type = $(this).attr("type");
		// get data from hidden fields
		var item_total = $(this).closest('tr').find('[name=item_total]').val();
		var grandtotal = $('#grandtotal_val').val();

		// find the fields
		var total = $(this).closest('table').find('[name=total]');
		var total_val = total.val();
		var total_td = $(this).closest('table').find('[class=total]');

		$(this).closest('tr').fadeOut();
		$.getJSON("cart_process.php", {"remove_code":pid, "type":type}).done(function(data) {
			// subtract deleted product from order total_val 
			total_val = total_val - item_total;
			grandtotal = grandtotal - item_total;

			// save new total in hidden input fields
			total.val(total_val);
			$('#grandtotal_val').val(grandtotal);

			grandtotal = Math.abs(grandtotal.toFixed(2));
			total_var = Math.abs(total_val.toFixed(2));

			// update fields and delete old total and insert new total
			$('#grandtotal').html("ges. "+ grandtotal +"€");
			total_td.html('').html(total_var);
			//update Item count in cart-info
			$("#cartCount").html(data.items);
		});
	});

	// when user clicks on cart box
	$( "#shoppingCart").on('click', function(e) {
		e.preventDefault(); 
		$("#shopping-cart-results").load( "cart_process.php", {"load_cart":"1"});
	});
});