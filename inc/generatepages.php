<?php

if(basename(__FILE__) == basename($_SERVER["SCRIPT_FILENAME"])) die();

function createBoardIndex($conn, $urlid) {
	global $config;
	$boardinfo = getBoardInfo($conn, $urlid);
	$threads = getPosts($conn, $urlid);
	// create file
	$path = $config['rootdir'] . "/$urlid/index.html";
	$index = fopen($path, "w");
	$html = getPage("boardpage.html", array("board"=>$boardinfo, "threads"=>$threads));
	fwrite($index, $html);
}

function createReplyPage($conn, $urlid, $id) {
	global $config;
	$boardinfo = getBoardInfo($conn, $urlid);
	$replies = getReplies($conn, $urlid, $id);
	$post = getPost($conn, $urlid, $id); // get original post
	$path = $config['rootdir'] . "/$urlid/res/$id.html";
	$index = fopen($path, "w");
	$html = getPage("boardreply.html", array("board"=>$boardinfo, "replies"=>$replies, "threadid"=>$id, "post"=>$post));
	fwrite($index, $html);
}

?>