//marks the current navigation item, that has been selected
(function markNav ()  {
	var path = window.location.pathname;
	path = path.slice(0, path.indexOf('.'));
	
	while (path.indexOf('/') != -1) {
		path = path.slice(path.indexOf('/') + 1);
	}

	var selector = document.getElementById(path);
	selector.className += 'select';
	
})();