<?php

require "inc/config.php";
require "inc/templates.php";
require "inc/functions.php";
require "inc/generatepages.php";

session_name("manage-login");
session_set_cookie_params(1200, $_SERVER['SERVER_NAME']); // 1200 seconds = 20 mins
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

if(isset($_GET['install'])) {
	if($config['installed'] !== 0 && !isset($_SESSION['username'])) {
		error("Site already installed - Otherwise, change 'installed' value in config to 0");
	}
	installSite($conn);
}

if(!$config['installed']) {
	echo "<br>Board doesn't seem to be installed. <a href=\"?install\">Install now</a>. Otherwise, change 'installed' value in config to 1";
}

// very basic login stuff
if(!isset($_SESSION['username'])) {
	die(getPage("managepages/login.html", array()));
}

$boards = getBoards($conn);

require "inc/managepages.php";

die(getPage("managepages/manage.html", array("boards" => $boards)));

?>