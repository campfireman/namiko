<?php
include('templates/back-up-button.php');
?>

  	<footer>
    
    <div class="footer">
    	<span><a href="contact.php" class="green">Kontakt <i class="fa fa-envelope" aria-hidden="true"></i></a><a href="imprint.php" class="orange leftSpacer">Impressum <i class="fa fa-file" aria-hidden="true"></i></a><a href="data.php" class="blue leftSpacer">Datenschutz <i class="fa fa-database" aria-hidden="true"></i></a></span><br><br>
    	<div><a href="https://gitlab.com/CampFireMan/namiko">Version 1.5.4</a></div>
    </div>
      </footer>
		<script src="js/bootstrap.min.js"></script>

  	<script>
  	function deny_IE() {
  		if((navigator.userAgent.indexOf("MSIE") != -1 ) || (!!document.documentMode == true )) {
      		alert('Internet Explorer, leider funktioniert diese Seite, mit deinem Browser nicht!'); 
    	}  
  	}
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

	/**
	 *  THANKS to https://codepen.io/jgx/pen/wiIGc
	 */
	(function($) {
	   $.fn.fixMe = function() {
	      return this.each(function() {
	         var $this = $(this),
	            $t_fixed;
	         function init() {
	            $t_fixed = $this.clone();
	            $t_fixed.find("tbody").remove().end().addClass("fixed-th").insertBefore($this);
	            resizeFixed();
	         }
	         function resizeFixed() {
	            $t_fixed.find("th").each(function(index) {
	               $(this).css("width",$this.find("th").eq(index).outerWidth()+"px");
	            });
	         }
	         function scrollFixed() {
	            var offset = $(this).scrollTop(),
	            tableOffsetTop = $this.offset().top,
	            tableOffsetBottom = tableOffsetTop + $this.height() - $this.find("thead").height();
	            if(offset < tableOffsetTop || offset > tableOffsetBottom)
	               $t_fixed.hide();
	            else if(offset >= tableOffsetTop && offset <= tableOffsetBottom && $t_fixed.is(":hidden"))
	               $t_fixed.show();
	         }
	         $(window).resize(resizeFixed);
	         $(window).scroll(scrollFixed);
	         init();
	      });
	   };
	})(jQuery);

	$(document).ready(function(){
	   $("table").fixMe();
	   $(".up").click(function() {
	      $('html, body').animate({
	      scrollTop: 0
	   }, 2000);
	 });
	 });

	</script>
  </body>
</html>