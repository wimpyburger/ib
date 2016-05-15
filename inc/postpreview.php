<?php

require "config.php";
require "functions.php";
require "templates.php";

$conn = sqlConnect();

if(!isset($_GET['board'])) {
	die("<div class=\"thread\">Board doesn't exist</div>");
}
if(!boardExists($conn, $_GET['board'])) {
	die("<div class=\"thread\">Board doesn't exist</div>");
}
if(!is_numeric($_GET['id'])) {
	die("<div class=\"thread\">Invalid post number</div>");
}

$post = getPost($conn, $_GET['board'], $_GET['id']);

if(!$post) {
	die("<div class=\"thread\">Post not found</div>");
}

die(getPage("postpreview.html", array("post"=>$post, "board"=>$_GET['board'])));

?>