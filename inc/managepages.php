<?php

if(basename(__FILE__) == basename($_SERVER["SCRIPT_FILENAME"])) die();

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
	die(getPage("managepages/configure.html", array()));
}

if(isset($_GET['deletepost'])) {
	if(!isset($_GET['board']) || !boardExists($conn, $_GET['board'])) {
		error("Board doesn't exist");
	}
	deletePost($conn, $_GET['board'], $_GET['id']);
	completed("Post deleted");
}

if(isset($_GET['viewboard'])) {
	if(!isset($_GET['board']) || !boardExists($conn, $_GET['board'])) {
		error("Board doesn't exist");
	}
	if(isset($_GET['thread'])) {
		if(!threadExists($conn, $_GET['board'], $_GET['thread'])) {
			error("Thread doesn't exist");
		}
		die(getManageBoardReply($conn, $_GET['board'], $_GET['thread']));
	}
	die(getManageBoardIndex($conn, $_GET['board']));
}

if(isset($_GET['createboard'])) {
	if(isset($_POST['title']) && isset($_POST['urlid'])) {
		createBoard($conn, $_POST['title'], $_POST['urlid']);
		completed("Board created");
	}
	die(getPage("managepages/createboard.html", array()));
}

if(isset($_GET['manageaccounts'])) {
	$users = getUsers($conn);
	die(getPage("managepages/manageaccounts.html", array("users"=>$users,"username"=>$_SESSION['username'])));
}

if(isset($_GET['refreshstatic'])) {
	$boards = getBoards($conn);
	$completedtext = "";
	// create index page;
	createSiteIndex($conn);
	$completedtext .= "Created index page<br>";
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

if(isset($_GET['logout'])) {
	session_destroy();
	completed("Logged out successfully");
}

if(isset($_GET['createaccount'])) {
	if(isset($_POST['createusername']) && isset($_POST['createpassword'])) {
		if(preg_match("/^[0-9a-zA-Z_]+$/", $_POST['createusername']) === 0) {
			error("Invalid characters in username");
		}
		if(strlen($_POST['createusername']) < 1 || strlen($_POST['createpassword']) < 4) {
			error("Username or password given is too short");
		}
		$passwordhash = password_hash($_POST['createpassword'], PASSWORD_BCRYPT);
		// submit to database
		$stmt = $conn->prepare("INSERT INTO users (username, password) VALUES (:username, :password)"); // get threads
		$stmt->bindParam(':username', $_POST['createusername'], PDO::PARAM_STR);
		$stmt->bindParam(':password', $passwordhash, PDO::PARAM_STR);
		$stmt->execute();
		completed("User account created");
	}
	die(getPage("managepages/createaccount.html", array()));
}

if(isset($_GET['deleteaccount'])) {
	if(!isset($_GET['id']) || !is_numeric($_GET['id'])) {
		error("Invalid account");
	}
	$stmt = $conn->prepare("DELETE FROM users WHERE id = :id");
	$stmt->bindParam(':id', $_GET['id'], PDO::PARAM_INT);
	$stmt->execute();
	completed("User account deleted");
}

if(isset($_GET['deleteboard'])) {
	if(!isset($_GET['board']) || !boardExists($conn, $_GET['board'])) {
		error("Board doesn't exist");
	}
	$completedtext = "";
	$stmt = $conn->prepare("DELETE FROM boards WHERE urlid = :urlid");
	$stmt->bindParam(':urlid', $_GET['board'], PDO::PARAM_STR);
	$stmt->execute();
	$completedtext .= "Deleted from 'boards' table<br>";
	$stmt = $conn->prepare("DROP TABLE posts_" . $_GET['board']);
	$stmt->execute();
	$completedtext .= "Dropped posts table<br>";
	rmdir($config['rootdir'] . "/" . $_GET['board']);
	$completedtext .= "Deleted board directory<br>";
	completed($completedtext);
}

?>