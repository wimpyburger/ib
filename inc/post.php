<?php


require "config.php";
require "functions.php";
require "templates.php";
require "generatepages.php";

$conn = sqlConnect();

if(!isset($_GET['board'])) {
	error("Board doesn't exist");
}
if(!boardExists($conn, $_GET['board'])) {
	error("Board doesn't exist");
}

if(!isset($_GET['parent'])) {
	error("Invalid parent");
}
if(!is_numeric($_GET['parent'])) {
	error("Invalid parent");
}
// a parent of 0 means a new thread
if($_GET['parent'] != 0 && !threadExists($conn, $_GET['board'], $_GET['parent'])) {
	error("Parent thread doesn't exist");
}

include $config['rootdir'] . "/" . $_GET['board'] . "/config.php";

// check post
if(!isset($_POST['namefield']) || !isset($_POST['textfield'])) {
	error("Fields not completed");
}

$parent = $_GET['parent'];
$board = $_GET['board'];
$name = htmlentities($_POST['namefield'], ENT_QUOTES, "utf-8");
if($parent == 0) {
	$subject = htmlentities($_POST['subjectfield'], ENT_QUOTES, "utf-8");
}
$text = htmlentities($_POST['textfield'], ENT_QUOTES, "utf-8");
$uploadedimage = $_FILES['imagefield'];
$ip = $_SERVER['REMOTE_ADDR'];
$lastreply = 0;
$fileupload = false;

if(strlen($text) < $config['minmessagechars'] || strlen($text) > $config['maxmessagechars']) {
	error("Message field length needs to be between " . $config['minmessagechars'] . " and " . $config['maxmessagechars'] . " characters");
}

if($name == "") {
	$name = $config['defaultpostername'];
}

if($uploadedimage['tmp_name'] != "") {
	// temp file exists
	$imageinfo = getimagesize($uploadedimage['tmp_name']);
	$filesize = filesize($uploadedimage['tmp_name']);
	$extension = false;
	if($imageinfo[2] == 2) {
		$extension = "jpg";
	}
	if($imageinfo[2] == 3) {
		$extension = "png";
	}
	if($imageinfo[2] == 1) {
		$extension = "gif";
	}
	if(!$extension) {
		error("Invalid file type");
	}
	if($filesize > $config['maximagesize']) {
		error("Image exceeds maximum size");
	}
	$filename = uniqid() . ".$extension";
	$filesum = sha1_file($uploadedimage['tmp_name']);
	if(fileExists($conn, $board, $filesum)) {
		error("File already exists");
	}
	createThumbnail($extension, $uploadedimage['tmp_name'], $config['rootdir'] . "/$board/thumb/$filename", 100, 100, $imageinfo[0], $imageinfo[1]);
	if(!move_uploaded_file($uploadedimage['tmp_name'], $config['rootdir'] . "/$board/src/$filename")) {
		error("Upload failed");
	}
	$fileupload = true;
}

// all checks passed
// submit
if($fileupload) {
	$stmt = $conn->prepare("INSERT INTO posts_$board (name, subject, message, parent, ip, lastreply, filesum, filename) VALUES (:name, :subject, :message, :parent, :ip, :lastreply, UNHEX(:filesum), :filename)");
} else {
	$stmt = $conn->prepare("INSERT INTO posts_$board (name, subject, message, parent, ip, lastreply) VALUES (:name, :subject, :message, :parent, :ip, :lastreply)");
}
$stmt->bindParam(':name', $name, PDO::PARAM_STR);
$stmt->bindParam(':subject', $subject, PDO::PARAM_STR);
$stmt->bindParam(':message', $text, PDO::PARAM_STR);
$stmt->bindParam(':parent', $parent, PDO::PARAM_STR);
$stmt->bindParam(':ip', $ip, PDO::PARAM_STR);
$stmt->bindParam(':lastreply', $lastreply, PDO::PARAM_INT);
if($fileupload) {
	$stmt->bindParam(':filesum', $filesum, PDO::PARAM_STR);
	$stmt->bindParam(':filename', $filename, PDO::PARAM_STR);
}
try {
	$stmt->execute();
} catch(PDOException $ex) {
	error("Post not submitted: " . $ex);
}
$lastid = $conn->lastInsertId();
if($parent == 0) {
	// update lastreply field with id
	$stmt = $conn->prepare("UPDATE `posts_$board` SET `lastreply` = :postid WHERE `id` = :postid2");
	$stmt->bindParam(':postid', $lastid, PDO::PARAM_INT);
	$stmt->bindParam(':postid2', $lastid, PDO::PARAM_INT); // can't use same param twice?
	try {
		$stmt->execute();
	} catch(PDOException $ex) {
		error("Error updating database: " . $ex);
	}
} else {
	// update lastreply field with id
	$stmt = $conn->prepare("UPDATE `posts_$board` SET `lastreply` = :postid WHERE `id` = :parent");
	$stmt->bindParam(':postid', $lastid, PDO::PARAM_INT);
	$stmt->bindParam(':parent', $parent, PDO::PARAM_INT); // can't use same param twice?
	try {
		$stmt->execute();
	} catch(PDOException $ex) {
		error("Error updating database: " . $ex);
	}
}
	
// recreate board static pages
createBoardIndex($conn, $board);
if($parent == 0) {
	createReplyPage($conn, $board, $lastid);
} else {
	createReplyPage($conn, $board, $parent);
}

// redirect back to index
header("Location: " . $config['siteurl'] . "/$board");

?>