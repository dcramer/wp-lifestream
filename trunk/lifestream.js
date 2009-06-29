function lifestream_toggle(source, id, lblshow, lblhide) {
	if (document.getElementById(id).style.display == 'none')
	{
		source.innerHTML = lblhide;
		document.getElementById(id).style.display = 'block';
	} else {
		source.innerHTML = lblshow;
		document.getElementById(id).style.display = 'none';
	}
}