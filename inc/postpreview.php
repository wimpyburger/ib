<?php

require "config.php";
require "functions.php";
require "templates.php";

$conn = sqlConnect();

if(!isset($_GET['board'])) {
	error("Board doesn't exist");
}
if(!boardExists($conn, $_GET['board'])) {
	error("Board doesn't exist");
}
if(!is_numeric($_GET['id'])) {
	error("Invalid post number");
}

$post = getPost($conn, $_GET['board'], $_GET['id']);

if(!$post) {
	die("<div class=\"thread\">Post not found</div>");
}

die(getPage("postpreview.html", array("post"=>$post, "board"=>$_GET['board'])));

?>