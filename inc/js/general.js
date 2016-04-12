var previewcache = {};

function expandImage(image) {
	image.onclick = function(e) {
		if(e.which != 1) { return image; }
		var newsrc = image.src.replace("thumb", "src");
		image.src = newsrc;
		contractImage(image);
	}
}
function contractImage(image) {
	image.onclick = function(e) {
		if(e.which != 1) { return image; }
		var newsrc = image.src.replace("src", "thumb");
		image.src = newsrc;
		expandImage(image);
	}
}
function quotePost(postid) {
	postid.onclick = function() {
		document.getElementsByTagName("textarea")[0].innerHTML += ">>" + postid.innerHTML + "\n";
	}
}
function previewPost(quotelink) {
	quotelink.onmouseover = function() {
		var previews = document.getElementsByClassName("preview");
		while(previews.length > 0){
			previews[0].parentNode.removeChild(previews[0]);
		}
		var postid = quotelink.innerHTML.substring(8);
		// check cache
		if(previewcache[postid] != null) {
			var preview = document.createElement('div');
			var html = previewcache[postid];
			preview.innerHTML += html;
			preview.className = "preview";
			quotelink.parentElement.appendChild(preview);
		} else {
			var xhttp = new XMLHttpRequest();
			xhttp.onreadystatechange = function() {
				if (xhttp.readyState == 4 && xhttp.status == 200) {
					var preview = document.createElement('div');
					var html = xhttp.responseText;
					preview.innerHTML += html;
					preview.className = "preview";
					quotelink.parentElement.appendChild(preview);
					previewcache[postid] = html;
				}
			};
			xhttp.open("GET", siteurl + "/inc/postpreview.php?board=" + board + "&id=" + postid, true);
			xhttp.send();
		}
	}
	quotelink.onmouseout = function() {
		var previews = document.getElementsByClassName("preview");
		while(previews.length > 0){
			previews[0].parentNode.removeChild(previews[0]);
		}
	}
}

function selectPost() {
	// remove other selected
	var selected = document.getElementsByClassName("selected");
	for(var i=0; i<selected.length; i++) {
		selected[i].className = "reply";
	}
	var selectedPost = document.getElementById(window.location.hash.substring(1));
	if(selectedPost !== null) {
		selectedPost.className = "selected";
	}
}

function changeStyle() {
	document.getElementById("styleswitch").onchange = function() {
		var stylename = document.getElementById("styleswitch").value;
		var styleurl = siteurl + "/inc/css/" + stylename + ".css";
		document.getElementById("pagestyle").setAttribute("href", styleurl);  
		document.cookie="style=" + stylename + ";";
	}
}

function getStyleCookie() {
	var cookies = document.cookie.split(";");
	for(var i=0; i<cookies.length; i++) {
		if(cookies[i].substring(0, 5) == "style") {
			var stylename = cookies[i].substring(6);
			// change style
			var styleurl = siteurl + "/inc/css/" + stylename + ".css";
			document.getElementById("pagestyle").setAttribute("href", styleurl);
			// change dropdown
			document.getElementById("styleswitch").value = stylename;
		}
	}
}

window.onhashchange = function() {
	selectPost();
}

window.onload = function() {
	var links = document.getElementsByClassName("imagelink");
	for(var i=0; i<links.length; i++) {
		links[i].onclick = function(e) {
			if(e.which == 1) {
				return false;
			}
		};
	}
	var images = document.getElementsByClassName("image");
	for(var i=0; i<images.length; i++) {
		expandImage(images[i]);
	}
	var postids = document.getElementsByClassName("postid");
	for(var i=0; i<postids.length; i++) {
		quotePost(postids[i]);
	}
	var quotelinks = document.getElementsByClassName("quotelink");
	for(var i=0; i<quotelinks.length; i++) {
		previewPost(quotelinks[i]);
	}
	selectPost();
	changeStyle();
	getStyleCookie();
};