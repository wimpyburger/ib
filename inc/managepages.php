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
	header("Location: " . $config['siteurl'] . "/manage.php?viewboard&board=" . $_GET['board']);
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

if(isset($_GET['editboard'])) {
	if(isset($_POST['title']) && isset($_POST['urlid'])) {
		$stmt = $conn->prepare("UPDATE boards SET title = :title WHERE urlid = :urlid");
		$stmt->bindParam(':title', $_POST['title'], PDO::PARAM_STR);
		$stmt->bindParam(':urlid', $_POST['urlid'], PDO::PARAM_STR);
		$stmt->execute();
		completed("Board info changed");
	}
	if(isset($_GET['board']) && boardExists($conn, $_GET['board'])) {
		$boardinfo = getBoardInfo($conn, $_GET['board']);
		die(getPage("managepages/editboard.html", array('boardInfo'=>$boardinfo)));
	}
	error("Board doesn't exist");
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
		// create catalogue page
		createCataloguePage($conn, $board['urlid']);
		$completedtext .= "<b>" . $board['urlid'] . "</b> catalogue page created<br>";
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

if(isset($_GET['editaccount'])) {
	if(!isset($_GET['id']) || !is_numeric($_GET['id'])) {
		error("Invalid account");
	}
	if(!isset($_POST['username'])) {
		die(getPage("managepages/editaccount.html", array('id'=>$_GET['id'])));
	}
	if($_POST['username'] == "") {
		error("Username field can't be blank");
	}
	$passwordhash = password_hash($_POST['password'], PASSWORD_BCRYPT);
	$stmt = $conn->prepare("UPDATE users SET username = :username, password = :password WHERE id = :id");
	$stmt->bindParam(':id', $_GET['id'], PDO::PARAM_INT);
	$stmt->bindParam(':username', $_POST['username'], PDO::PARAM_STR);
	$stmt->bindParam(':password', $passwordhash, PDO::PARAM_STR);
	$stmt->execute();
	completed("Account information modified");
}

if(isset($_GET['deleteboard'])) {
	if(!isset($_GET['board']) || !boardExists($conn, $_GET['board'])) {
		error("Board doesn't exist");
	}
	deleteBoard($conn, $_GET['board']);
}

if(isset($_GET['banposter'])) {
	if($_GET['banposter'] == 1) {
		if(!is_numeric($_POST['length']) || $_POST['length'] <= 0) {
			error("Invalid ban length");
		}
		if(!filter_var($_POST['ip'], FILTER_VALIDATE_IP)) {
			error("Invalid ip address");
		}
		$_POST['reason'] = htmlentities($_POST['reason']);
		// submit ban
		$stmt = $conn->prepare("INSERT INTO bans (ip, reason, expires) VALUES (:ip, :reason, DATE_ADD(NOW(), INTERVAL :length HOUR))");
		$stmt->bindParam(':ip', $_POST['ip'], PDO::PARAM_STR);
		$stmt->bindParam(':reason', $_POST['reason'], PDO::PARAM_STR);
		$stmt->bindParam(':length', $_POST['length'], PDO::PARAM_STR);
		try {
			$stmt->execute();
		} catch(PDOException $ex) {
			error("Couldn't ban user: " . $ex);
		}
		completed("Banned");
	}
	// check if previous bans exist
	$previousbans = false;
	$ip = "";
	if(isset($_GET['ip'])) {
		if(getBan($conn, $_GET['ip'])) {
			$previousbans = true;
		}
		$ip = $_GET['ip'];
	}
	die(getPage("managepages/banposter.html", array("previousbans"=>$previousbans,"ip"=>$ip)));
}

if(isset($_GET['unbanuser'])) {
	if(!isset($_GET['ip']) || !filter_var($_GET['ip'], FILTER_VALIDATE_IP)) {
		error("Invalid IP");
	}
	$stmt = $conn->prepare("DELETE FROM bans WHERE ip = :ip");
	$stmt->bindParam(":ip", $_GET['ip'], PDO::PARAM_STR);
	$stmt->execute();
	completed("User has been unbanned");
}

if(isset($_GET['managebans'])) {
	// get all current bans
	$ip = NULL;
	if(isset($_GET['ip'])) {
		if(!filter_var($_GET['ip'], FILTER_VALIDATE_IP)) {
			error("Invalid ip address");
		}
		$stmt = $conn->prepare("SELECT * FROM bans WHERE ip = :ip");
		$stmt->bindParam(":ip", $_GET['ip'], PDO::PARAM_STR);
		$ip = $_GET['ip'];
	}
	$stmt = $conn->prepare("SELECT * FROM bans WHERE expires > NOW()");
	$stmt->execute();
	$result = $stmt->fetchAll(PDO::FETCH_ASSOC);
	die(getPage("managepages/managebans.html", array("bans"=>$result, "ip"=>$ip)));
}

if(isset($_GET['postsbyip'])) {
	// get all posts by a specified ip address
	if(!isset($_GET['ip']) || !filter_var($_GET['ip'], FILTER_VALIDATE_IP)) {
		error("Invalid ip address");
	}
	// get for each board
	$posts = [];
	$boards = getBoards($conn);
	foreach($boards as $board) {
		$stmt = $conn->prepare("SELECT * FROM posts_" . $board['urlid'] . " WHERE ip = :ip");
		$stmt->bindParam(":ip", $_GET['ip'], PDO::PARAM_STR);
		$stmt->execute();
		$result = $stmt->fetchAll(PDO::FETCH_ASSOC);
		foreach($result as $post) {
			$post['urlid'] = $board['urlid'];
			array_push($posts, $post);
		}
	}
	die(getPage("managepages/postsbyip.html", array("posts"=>$posts)));
}

?>