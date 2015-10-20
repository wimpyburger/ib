<?php

require "inc/config.php";
require "inc/templates.php";
require "inc/functions.php";
require "inc/generatepages.php";

session_set_cookie_params('time()+600', '/', $_SERVER['SERVER_NAME'], false, true);
session_regenerate_id(true);
session_start();

$conn = sqlConnect();

if(isset($_POST['loginusername']) && isset($_POST['loginpassword'])) {
	// check its valid
	$stmt = $conn->prepare("SELECT password FROM users WHERE username = :username");
	$stmt->bindParam(':username', $_POST['loginusername'], PDO::PARAM_STR);
	$stmt->execute();
	$passwordhash = $stmt->fetch()[0];
	if(!$passwordhash) {
		error("Invalid username or password"); // username doesn't exist
	}
	if(password_verify($_POST['loginpassword'], $passwordhash)) {
		$_SESSION['username'] = $_POST['loginusername'];
	} else {
		error("Invalid username or password"); // wrong pass
	}
}

if(!$config['installed']) {
	echo "Board not installed.<br><a href=\"?install\">Install now</a><br>Otherwise, change 'installed' value in config to 1";
}

if(isset($_GET['install'])) {
	installSite($conn);
}

// very basic login stuff
if(!isset($_SESSION['username'])) {
	die(getPage("managepages/login.html", array()));
}


$boards = getBoards($conn);

require "inc/managepages.php";

die(getPage("managepages/manage.html", array("boards" => $boards)));

?>