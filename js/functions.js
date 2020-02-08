<script>

var menuState = 0;
function open_close () {
	if (menuState == 0) {
	document.getElementById('menuContent').style.visibility = 'visible';
	document.getElementById('menuContent').style.opacity = '1';
	document.getElementById('nav-btn').style.color = '#F6002E';
	menuState = 1;
	} else {
		document.getElementById('menuContent').style.opacity = '0';
		document.getElementById('menuContent').style.visibility = 'hidden';
		document.getElementById('nav-btn').style.color = 'white';
		menuState = 0;
	}
}

</script>