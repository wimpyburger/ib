<?php

require "inc/config.php";
require "inc/templates.php";
require "inc/functions.php";
require "inc/generatepages.php";

$conn = sqlConnect();
$boards = getBoards($conn);

if(isset($_GET['configure'])) {
	if(isset($_POST['installed'])) {
		foreach($_POST as $key=>$value) {
			if(isset($config[$key])) {
				$config[$key] = $value;
			}
		}
		recreateConfig();
		completed("Config updated");
	}
	die(getPage("managepages/configure.html", array("config" => $config)));
}

if(isset($_GET['install'])) {
	installSite($conn);
}

if(isset($_GET['createboard'])) {
	if(isset($_POST['title']) && isset($_POST['urlid'])) {
		createBoard($conn, $_POST['title'], $_POST['urlid']);
		completed("Board created");
	}
	die(getPage("managepages/createboard.html", array("config" => $config)));
}

if(isset($_GET['refreshstatic'])) {
	$boards = getBoards($conn);
	$completedtext = "";
	foreach($boards as $board) {
		// create index page
		createBoardIndex($conn, $board['urlid']);
		$completedtext .= "<b>" . $board['urlid'] . "</b> index page created<br>";
		// create reply pages
		$posts = getPosts($conn, $board['urlid']);
		foreach($posts as $post) {
			createReplyPage($conn, $board['urlid'], $post['id']);
			$completedtext .= " - " . $post['id'] . " reply page created<br>";
		}
	}
	completed($completedtext);
}

if(!$config['installed']) {
	die("<br><a href=\"?install\">Install now</a>");
}

die(getPage("manage.html", array("boards" => $boards)));

?>