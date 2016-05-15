var previewcache = {};
var stylename = "";

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
		document.getElementsByTagName("textarea")[0].value += ">>" + postid.innerHTML + "\n";
	}
}
function previewPost(quotelink) {
	quotelink.onmouseover = function() {
		var previews = document.getElementsByClassName("preview");
		var postid = quotelink.innerHTML.substring(8);
		// highlight
		var post = document.getElementById(postid);
		if(post.className != "op") {
			post.className = "selected";
		}
		// show preview
		while(previews.length > 0){
			previews[0].parentNode.removeChild(previews[0]);
		}
		// check cache
		if(previewcache[postid] != null) {
			var preview = document.createElement('div');
			var html = previewcache[postid];
			preview.innerHTML += html;
			preview.className = "preview";
			quotelink.appendChild(preview);
		} else {
			var xhttp = new XMLHttpRequest();
			xhttp.onreadystatechange = function() {
				if (xhttp.readyState == 4 && xhttp.status == 200) {
					var preview = document.createElement('div');
					var html = xhttp.responseText;
					preview.innerHTML += html;
					preview.className = "preview";
					quotelink.appendChild(preview);
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
		var selected = document.getElementsByClassName("selected");
		for(var i=0; i<selected.length; i++) {
			var postnum = selected[i].getElementsByClassName("postid")[0].innerHTML;
			if(postnum != window.location.hash.substring(1)) {
				selected[i].className = "reply";
			}
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
	if(selectedPost !== null && selectedPost.className != "op") {
		selectedPost.className = "selected";
	}
}

function changeStyle() {
	document.getElementById("styleswitch").onchange = function() {
		stylename = document.getElementById("styleswitch").value;
		var styleurl = siteurl + "/inc/css/" + stylename + ".css";
		document.getElementById("pagestyle").setAttribute("href", styleurl); 
		if(board == "") {
			document.cookie = "mainstyle=" + stylename + ";path=/";
		} else {
			var path = siteurl + "/" + board + "/";
			document.cookie = "style=" + stylename + ";path=" + path;
		}
	}
}

function getStyleCookie() {
	var cookievalue = "; " + document.cookie;
	var cookies = document.cookie.split("; ");
	for(var i=0; i<cookies.length; i++) {
		if(board == "") {
			if(cookies[i].substring(0, 9) == "mainstyle") {
				stylename = cookies[i].substring(10);
				var styleurl = siteurl + "/inc/css/" + stylename + ".css";
				document.getElementById("pagestyle").setAttribute("href", styleurl);
			}
		} else {
			if(cookies[i].substring(0, 5) == "style") {
				stylename = cookies[i].substring(6);
				var styleurl = siteurl + "/inc/css/" + stylename + ".css";
				document.getElementById("pagestyle").setAttribute("href", styleurl);
			}
		}
	}
}

getStyleCookie();

window.onhashchange = function() {
	selectPost();
}

window.onload = function() {
	// change dropdown
	if(stylename != "")
		document.getElementById("styleswitch").value = stylename
	
	var messages = document.getElementsByClassName("message");
	for(var i=0; i<messages.length; i++) {
		var postid = messages[i].parentNode.getElementsByClassName("postid")[0].innerHTML;
		
		// make links clickable
		messages[i].innerHTML = messages[i].innerHTML.replace(/(http:\/\/\S+)/gi, "<a href=\"$1\">$1</a>");
		messages[i].innerHTML = messages[i].innerHTML.replace(/(https:\/\/\S+)/gi, "<a href=\"$1\">$1</a>");
		
		// add reply lists to posts
		var replyregex = /(?:^|\W)&gt;&gt;(\w+)(?!\w)/g;
		var match = replyregex.exec(messages[i].innerHTML);
		while (match != null) {
			var quoted = document.getElementById(match[1]);
			if(quoted != null) {
				var array = quoted.getElementsByClassName("postinfo")[0].getElementsByClassName("replylink");
				var valuesarray = [];
				for(var x=0; x<array.length; x++) {
					valuesarray.push(array[x].innerHTML);
				}
				if(valuesarray.indexOf("&gt;&gt;" + postid) == -1) {
					var replylink = document.createElement('a');
					replylink.innerHTML = ">>" + postid;
					replylink.href = "#" + postid;
					replylink.className = "replylink";
					previewPost(replylink);
					quoted.getElementsByClassName("postinfo")[0].appendChild(replylink);
				}
			}
			match = replyregex.exec(messages[i].innerHTML);
		}
		
		// make post quotes work
		messages[i].innerHTML = messages[i].innerHTML.replace(/(?:^|\W)&gt;&gt;(\w+)(?!\w)/g, "<a href=\"" + siteurl + "/inc/findpost.php?id=$1&board=" + board + "\" class=\"quotelink\" class=\"quotelink\">&gt;&gt;$1</a>");
		// add greentext
		messages[i].innerHTML = messages[i].innerHTML.replace(/(\n|^)&gt;(.*?)(\n|$)/g, "$1<span class=\"greentext\">>$2</span>$3");
	}
	
	// makes all images clickable
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
};