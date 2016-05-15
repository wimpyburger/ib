function changeValue(id, value) {
	var element = document.getElementById(id);
	element.value = value;
}

function resetConfigValue(id, value) {
	var element = document.getElementById(id);
	element.value = value;
	element.className = "";
	var element = document.getElementById(id + "_reset");
	element.style.display = 'none';
}