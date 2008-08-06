function lifestream_toggle(source, id, lblshow, lblhide) {
	if (source.innerHTML == lblshow) {
		source.innerHTML = lblhide;
		document.getElementById(id).style.display = 'block';
	} else {
		source.innerHTML = lblshow;
		document.getElementById(id).style.display = 'none';
	}
}