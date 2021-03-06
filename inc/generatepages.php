<?php

if(basename(__FILE__) == basename($_SERVER["SCRIPT_FILENAME"])) die();

function createSiteIndex($conn) {
	global $config;
	$path = $config['rootdir'] . "/index.html";
	$boards = getBoards($conn);
	$html = getPage("index.html", array("boards"=>$boards));
	$index = fopen($path, "w");
	fwrite($index, $html);
}

function createBoardIndex($conn, $urlid) {
	global $config;
	include $config['rootdir'] . "/inc/config.php";
	include $config['rootdir'] . "/$urlid/config.php";
	$boardinfo = getBoardInfo($conn, $urlid);
	$boards = getBoards($conn);
	$threads = getPosts($conn, $urlid);
	foreach($threads as $key=>$value) {
		$threads[$key]['replies'] = getNumReplies($conn, $urlid, $value['id'], $config['displayedreplies']);
	}
	// create file
	$path = $config['rootdir'] . "/$urlid/index.html";
	$index = fopen($path, "w");
	$html = getPage("boardpage.html", array("board"=>$boardinfo, "boards"=>$boards, "threads"=>$threads, "managing"=>false));
	fwrite($index, $html);
}

function createReplyPage($conn, $urlid, $id) {
	global $config;
	$boardinfo = getBoardInfo($conn, $urlid);
	$boards = getBoards($conn);
	$replies = getReplies($conn, $urlid, $id);
	$post = getPost($conn, $urlid, $id); // get original post
	$path = $config['rootdir'] . "/$urlid/res/$id.html";
	$index = fopen($path, "w");
	$html = getPage("boardreply.html", array (
		"board"=>$boardinfo,
		"boards"=>$boards,
		"replies"=>$replies,
		"threadid"=>$id,
		"post"=>$post,
		"managing"=>false
	));
	fwrite($index, $html);
}

function createCataloguePage($conn, $urlid) {
	global $config;
	$boardinfo = getBoardInfo($conn, $urlid);
	$boards = getBoards($conn);
	$threads = getPosts($conn, $urlid);
	// create file
	$path = $config['rootdir'] . "/$urlid/catalogue.html";
	$index = fopen($path, "w");
	$html = getPage("boardcatalogue.html", array("board"=>$boardinfo, "boards"=>$boards, "threads"=>$threads, "managing"=>false));
	fwrite($index, $html);
}

function getManageBoardIndex($conn, $urlid) {
	global $config;
	$boardinfo = getBoardInfo($conn, $urlid);
	$boards = getBoards($conn);
	$threads = getPosts($conn, $urlid);
	foreach($threads as $key=>$value) {
		$threads[$key]['replies'] = getNumReplies($conn, $urlid, $value['id'], $config['displayedreplies']);
	}
	return getPage("boardpage.html", array("board"=>$boardinfo, "boards"=>$boards, "threads"=>$threads, "managing"=>true));
}

function getManageBoardReply($conn, $urlid, $id) {
	global $config;
	$boardinfo = getBoardInfo($conn, $urlid);
	$replies = getReplies($conn, $urlid, $id);
	$post = getPost($conn, $urlid, $id); // get original post
	return getPage("boardreply.html", array (
		"board"=>$boardinfo,
		"replies"=>$replies,
		"threadid"=>$id,
		"post"=>$post,
		"managing"=>true
	));
}

?>