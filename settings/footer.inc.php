<?php 

?> 



  	<footer>
    
    <div class="footer">
    	<span><a href="contact.php" class="green">Kontakt <i class="fa fa-envelope" aria-hidden="true"></i></a><a href="imprint.php" class="orange leftSpacer">Impressum <i class="fa fa-file" aria-hidden="true"></i></a><a href="data.php" class="blue leftSpacer">Datenschutz <i class="fa fa-database" aria-hidden="true"></i></a></span><br><br>
    	<div><a href="https://github.com/campfireman/food-coop/">Version 1.0</a></div>
    </div>
      </footer>
		<script src="js/bootstrap.min.js"></script>

  	<script>
    function closeNotification (value) {
		var x = document.getElementById('notification' + value);
		x.style.height = '0';
		
		document.body.classList.remove('noscroll');
	}
	
	var menuState = 0;
	function open_close () {
		var x = document.getElementById('menuContent');
		if (menuState == 0) {
		x.style.visibility = 'visible';
		x.style.opacity = '1';
		document.getElementById('nav-btn').style.color = '#F6002E';
		menuState = 1;
		} else {
			x.style.opacity = '0';
			x.style.visibility = 'hidden';
			document.getElementById('nav-btn').style.color = '#0e1111';
			menuState = 0;
		}
	}

	</script>
  </body>
</html>