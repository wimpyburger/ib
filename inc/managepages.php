<?php

if(basename(__FILE__) == basename($_SERVER["SCRIPT_FILENAME"])) die();

$user = getUser($conn, $_SESSION['userid']);

$page = "";
if(!empty(array_keys($_GET)))
	$page = array_keys($_GET)[0];

if($user['type'] == 1 && $page != "") {
	if(!in_array($page, $config['modpages']))
		error("Your account cannot access this page");
}
if($user['type'] == 2 && $page != "") {
	if(!in_array($page, $config['janitorpages']))
		error("Your account cannot access this page");
}

if($page == 'configure') {
	if(isset($_POST['installed'])) {
		foreach($_POST as $key=>$value) {
			if(isset($config[$key])) {
				if(is_array($config[$key])) {
					$config[$key] = explode(", ", $value);
				} else {
					$config[$key] = $value;
				}
			}
		}
		recreateConfig();
		completed("Config updated");
	}
	$editconfig = $config;
	foreach($editconfig as $key=>$value) {
		if(is_array($value)) {
			$editconfig[$key] = implode (", ", $value);
		}
	}
	die(getPage("managepages/configure.html", array('editconfig'=>$editconfig)));
}

if($page == 'deletepost') {
	if(!isset($_GET['board']) || !boardExists($conn, $_GET['board'])) {
		error("Board doesn't exist");
	}
	deletePost($conn, $_GET['board'], $_GET['id']);
	addadminAction($conn, $user['id'], "Deleted post #" . $_GET['id'] . " on board " . $_GET['board']);
	header("Location: " . $config['siteurl'] . "/manage.php?viewboard&board=" . $_GET['board']);
}

if($page == 'viewboard') {
	if(!isset($_GET['board']) || !boardExists($conn, $_GET['board'])) {
		error("Board doesn't exist");
	}
	include $config['rootdir'] . "/" . $_GET['board'] . "/config.php";
	if(isset($_GET['thread'])) {
		if(!threadExists($conn, $_GET['board'], $_GET['thread'])) {
			error("Thread doesn't exist");
		}
		die(getManageBoardReply($conn, $_GET['board'], $_GET['thread']));
	}
	die(getManageBoardIndex($conn, $_GET['board']));
}

if($page == 'createboard') {
	if(isset($_POST['title']) && isset($_POST['urlid'])) {
		createBoard($conn, $_POST['title'], $_POST['urlid']);
		addadminAction($conn, $user['id'], "Created board /" . $_POST['urlid'] . "/ - " . $_POST['title']);
		completed("Board created");
	}
	die(getPage("managepages/createboard.html", array()));
}

if($page == 'editboard') {
	if(isset($_GET['updateinfo']) && isset($_POST['title']) && isset($_POST['urlid'])) {
		$stmt = $conn->prepare("UPDATE boards SET title = :title WHERE urlid = :urlid");
		$stmt->bindParam(':title', $_POST['title'], PDO::PARAM_STR);
		$stmt->bindParam(':urlid', $_POST['urlid'], PDO::PARAM_STR);
		$stmt->execute();
		addadminAction($conn, $user['id'], "Changed /" . $_POST['urlid'] . "/ info");
		completed("Board info changed");
	}
	
	if(isset($_GET['updateconfig']) && isset($_POST['installed']) && boardExists($conn, $_GET['board'])) {
		$newconfig = $config;
		foreach($_POST as $key=>$value) {
			if(isset($config[$key])) {
				if(is_array($config[$key])) {
					$newconfig[$key] = explode(", ", $value);
				} else {
					$newconfig[$key] = $value;
				}
			}
		}
		recreateBoardConfig($newconfig, $_GET['board']);
		addadminAction($conn, $user['id'], "Updated /" . $_GET['board'] . "/ config");
		completed("Config updated");
	}
	
	if(isset($_GET['board']) && boardExists($conn, $_GET['board'])) {
		$boardinfo = getBoardInfo($conn, $_GET['board']);
		$oldconfig = $config;
		include $config['rootdir'] . $_GET['board'] . '/config.php';
		$editconfig = $config;
		foreach($editconfig as $key=>$value) {
			if(is_array($value)) {
				$editconfig[$key] = implode(", ", $value);
			}
		}
		foreach($oldconfig as $key=>$value) {
			if(is_array($value)) {
				$oldconfig[$key] = implode(", ", $value);
			}
		}
		die(getPage("managepages/editboard.html", array('boardInfo'=>$boardinfo, 'oldconfig'=>$oldconfig, 'editconfig'=>$editconfig)));
	}
	error("Board doesn't exist");
}

if($page == 'manageaccounts') {
	$users = getUsers($conn);
	die(getPage("managepages/manageaccounts.html", array("users"=>$users, "username"=>$_SESSION['username'])));
}

if($page == 'refreshstatic') {
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
	addadminAction($conn, $user['id'], "Refreshed static pages");
	completed($completedtext);
}

if($page == 'logout') {
	session_destroy();
	addadminAction($conn, $user['id'], "Logged out");
	completed("Logged out successfully");
}

if($page == 'createaccount') {
	if(isset($_POST['createusername']) && isset($_POST['createpassword'])) {
		if(preg_match("/^[0-9a-zA-Z_]+$/", $_POST['createusername']) === 0) {
			error("Invalid characters in username");
		}
		if(strlen($_POST['createusername']) < 1 || strlen($_POST['createpassword']) < 4) {
			error("Username or password given is too short");
		}
		if(!is_numeric($_POST['createtype'])) {
			error("Invalid account type");
		}
		$passwordhash = password_hash($_POST['createpassword'], PASSWORD_BCRYPT);
		// submit to database
		$stmt = $conn->prepare("INSERT INTO users (username, password, type) VALUES (:username, :password, :type)"); // get threads
		$stmt->bindParam(':username', $_POST['createusername'], PDO::PARAM_STR);
		$stmt->bindParam(':password', $passwordhash, PDO::PARAM_STR);
		$stmt->bindParam(':type', $_POST['createtype'], PDO::PARAM_INT);
		$stmt->execute();
		addadminAction($conn, $user['id'], "Created " . $config['accounttypes'][$_POST['createtype']] . " account with username " . $_POST['createusername']);
		completed("User account created");
	}
	die(getPage("managepages/createaccount.html", array()));
}

if($page == 'deleteaccount') {
	if(!isset($_GET['id']) || !is_numeric($_GET['id'])) {
		error("Invalid account");
	}
	$stmt = $conn->prepare("DELETE FROM users WHERE id = :id");
	$stmt->bindParam(':id', $_GET['id'], PDO::PARAM_INT);
	$stmt->execute();
	addadminAction($conn, $user['id'], "Deleted account #" . $_GET['id']);
	completed("User account deleted");
}

if($page == 'editaccount') {
	if(!isset($_GET['id']) || !is_numeric($_GET['id'])) {
		error("Invalid account");
	}
	if(!isset($_POST['username'])) {
		$user = getUser($conn, $_GET['id']);
		die(getPage("managepages/editaccount.html", array('id'=>$_GET['id'], 'user'=>$user)));
	}
	if($_POST['username'] == "") {
		error("Username field can't be blank");
	}
	if(!is_numeric($_POST['type'])) {
		error("Invalid account type");
	}
	if($user['type'] != 0 && $_GET['id'] != $_SESSION['userid']) {
		error("Your account cannot modify other users");
	}
	if($user['type'] != 0 && $_POST['type'] != $user['type']) {
		error("You cannot change your own account type");
	}
	$passwordhash = password_hash($_POST['password'], PASSWORD_BCRYPT);
	$stmt = $conn->prepare("UPDATE users SET username = :username, password = :password, type = :type WHERE id = :id");
	$stmt->bindParam(':id', $_GET['id'], PDO::PARAM_INT);
	$stmt->bindParam(':username', $_POST['username'], PDO::PARAM_STR);
	$stmt->bindParam(':password', $passwordhash, PDO::PARAM_STR);
	$stmt->bindParam(':type', $_POST['type'], PDO::PARAM_INT);
	$stmt->execute();
	addadminAction($conn, $user['id'], "Edited user #" . $_GET['id'] . " account info");
	completed("Account information modified");
}

if($page == 'deleteboard') {
	if(!isset($_GET['board']) || !boardExists($conn, $_GET['board'])) {
		error("Board doesn't exist");
	}
	addadminAction($conn, $user['id'], "Deleted board " . $_GET['board']);
	deleteBoard($conn, $_GET['board']);
}

if($page == 'banposter') {
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
		addadminAction($conn, $user['id'], "Banned user " . $_POST['ip']);
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

if($page == 'unbanuser') {
	if(!isset($_GET['ip']) || !filter_var($_GET['ip'], FILTER_VALIDATE_IP)) {
		error("Invalid IP");
	}
	$stmt = $conn->prepare("DELETE FROM bans WHERE ip = :ip");
	$stmt->bindParam(":ip", $_GET['ip'], PDO::PARAM_STR);
	$stmt->execute();
	addadminAction($conn, $user['id'], "Unbanned user " . $_GET['ip']);
	completed("User has been unbanned");
}

if($page == 'managebans') {
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

if($page == 'postsbyip') {
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

if($page == 'adminaction') {
	$stmt = $conn->prepare("SELECT * FROM adminaction ORDER by id DESC");
	$stmt->execute();
	$result = $stmt->fetchAll(PDO::FETCH_ASSOC);
	die(getPage("managepages/adminaction.html", array("adminactions"=>$result)));
}

?>