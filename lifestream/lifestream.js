function lifestream_toggle(source, id) {
	if (source.innerHTML == 'Show Events') {
		source.innerHTML = 'Hide Events';
		document.getElementById(id).style.display = 'block';
	} else {
		source.innerHTML = 'Show Events';
		document.getElementById(id).style.display = 'none';
	}
}