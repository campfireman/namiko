<button onclick="topFunction()" id="back-up" title="Go to top">
	<svg xmlns:xlink="http://www.w3.org/1999/xlink" xmlns="http://www.w3.org/2000/svg" width="25px" height="25px" viewBox="0 0 560 560">
	 <g  transform="translate(0, -0.561)" style="fill:#ff5e16; fill-rule:evenodd; stroke:none; stroke-width:1; stroke-linecap:butt; stroke-linejoin:miter; stroke-dasharray:none;">
	  <path id="arrow" d="M0 559.991 C0 558.504 279.994 0 280.458 0.561456 C282.014 2.44528 560.512 560.13 559.999 560.337 C559.665 560.472 496.562 533.384 419.77 500.142 C419.77 500.142 280.15 439.701 280.15 439.701 C280.15 439.701 140.756 500.131 140.756 500.131 C64.0894 533.368 1.05572 560.561 0.681114 560.561 C0.306506 560.561 8e-06 560.304 8e-06 559.991 C8e-06 559.991 0 559.991 0 559.991 Z"/>
	 </g>
	</svg>
</button>

<script type="text/javascript">
	function topFunction () {
	    overlay.scroll({
			top: 0,
			behavior: 'smooth'
		});
	}
	
	$(document).ready(function() {
		
		(function(){
			overlay = document.documentElement;
			window.onscroll = function() {scrollFunction()};
		})();

		function scrollFunction() {
		    if (overlay.scrollTop > 200) {
		        document.getElementById("back-up").style.opacity = "1";
				document.getElementById("back-up").style.visibility = "visible";
				document.getElementById("back-up").style.transitionDelay = "0s";
				
		    } else {
		        document.getElementById("back-up").style.opacity = "0";
				document.getElementById("back-up").style.visibility = "hidden";
				document.getElementById("back-up").style.transitionDelay = "";
		    }
		}
	});
</script>