<?php

if(basename(__FILE__) == basename($_SERVER["SCRIPT_FILENAME"])) die();

function sqlConnect() {
	global $config;
	return new PDO("mysql:host={$config['sqlhost']};dbname={$config['sqldb']}", $config['sqluser'], $config['sqlpass'], array(PDO::ATTR_EMULATE_PREPARES => false, PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION));
}

function installSite($conn) {
	global $config;
	$installsql = file_get_contents($config['rootdir'] . "inc/createtables.sql");
	try {
		$installquery = $conn->exec($installsql);
	} catch(PDOException $ex) {
		error("Couldn't create tables: " . $ex);
	}
	$username = "admin";
	$password = '$2y$10$TAolHkHcItf5v7ZpWKsYPut6pmWNemNBpgkgEw8rqIY.9BATJeenG'; // 'test' encoded
	
	// create admin account with username 'admin' and password 'test'
	$stmt = $conn->prepare("INSERT INTO users (username, password) VALUES (:username, :password)"); // get threads
	$stmt->bindParam(':username', $username, PDO::PARAM_STR);
	$stmt->bindParam(':password', $password, PDO::PARAM_STR);
	$stmt->execute();
	
	$config['installed'] = 1;
	recreateConfig();
	
	completed("Script installed. Login to the admin panel with username <b>admin</b> and password <b>test</b>");
}

function getBan($conn, $ip) {
	$stmt = $conn->prepare("SELECT * FROM bans WHERE ip = :ip AND expires > NOW()");
	$stmt->bindParam(':ip', $ip);
	$stmt->execute();
	$result = $stmt->fetchAll(PDO::FETCH_ASSOC);
	if(!$result) {
		return false;
	} else {
		return $result;
	}
}

function banned($info) {
	die(getPage("banned.html", array("info"=>$info[0])));
}

function createBoard($conn, $title, $urlid) {
	global $config;
	
	// only allow numbers + letters + underscores
	
	if(preg_match("/^[0-9a-zA-Z_]+$/", $urlid) === 0) {
		error("Invalid board url");
	}
	
	// add to boards table
	
	$stmt = $conn->prepare("INSERT INTO boards (title, urlid) VALUES (:title, :urlid)");
	$stmt->bindParam(':title', $title, PDO::PARAM_STR);
	$stmt->bindParam(':urlid', $urlid, PDO::PARAM_STR);
	try {
		$stmt->execute();
	} catch(PDOException $ex) {
		error("Couldn't create board: " . $ex);
	}
	
	// create table
	
	$stmt = $conn->prepare("CREATE TABLE posts_$urlid (
		id INT(50) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
		name VARCHAR(200),
		subject VARCHAR(200),
		message VARCHAR(3500),
		parent INT(50) NOT NULL,
		date DATETIME NOT NULL DEFAULT NOW(),
		ip VARCHAR(60) NOT NULL,
		lastreply INT(50) NOT NULL,
		filesum BINARY(20),
		filename VARCHAR(255)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
	try {
		$stmt->execute();
	} catch(PDOException $ex) {
		error("Couldn't create board table: " . $ex);
	}
	
	// create directory and files
	
	mkdir($config['rootdir'] . "/" . $urlid);
	mkdir($config['rootdir'] . "/" . $urlid . "/res");
	mkdir($config['rootdir'] . "/" . $urlid . "/src", 0755, true);
	mkdir($config['rootdir'] . "/" . $urlid . "/thumb", 0755, true);
	
	createBoardIndex($conn, $urlid);
	createSiteIndex($conn);
	
	// create config file
	$configcontents = "<?php\n\n// use this file to overwrite defaults from inc/config.php\n\n?>";
	$configfile = fopen($config['rootdir'] . $urlid . "/config.php", "w+");
	if (fwrite($configfile, $configcontents) === 0) {
        error("Couldn't create config file");
    }
}

function deleteDir($dir) {
	if(!file_exists($dir)) {
		return true;
	}
	if(!is_dir($dir)) {
		return unlink($dir);
	}
	foreach(scandir($dir) as $item) {
		if ($item == '.' || $item == '..') {
			continue;
		}
		if(!deleteDir($dir . DIRECTORY_SEPARATOR . $item)) {
			return false;
		}
	}
	return rmdir($dir);
}

function deleteBoard($conn, $urlid) {
	global $config;
	$completedtext = "";
	$stmt = $conn->prepare("DELETE FROM boards WHERE urlid = :urlid");
	$stmt->bindParam(':urlid', $urlid, PDO::PARAM_STR);
	$stmt->execute();
	$completedtext .= "Deleted from 'boards' table<br>";
	$stmt = $conn->prepare("DROP TABLE posts_" . $urlid);
	$stmt->execute();
	$completedtext .= "Dropped posts table<br>";
	// Delete files
	deleteDir($config['rootdir'] . "/" . $urlid);
	$completedtext .= "Deleted board directory<br>";
	completed($completedtext);
}

function getBoards($conn) {
	try {
		$boardsquery = $conn->query("SELECT title, urlid FROM boards");
		$boardsquery->execute();
		$boards = $boardsquery->fetchAll(PDO::FETCH_ASSOC);
	} catch(PDOException $ex) {
		echo "Couldn't retrieve board list";
		return false;
	}
	return $boards;
}

function getUsers($conn) {
	try {
		$usersquery = $conn->query("SELECT id, username FROM users");
		$usersquery->execute();
		$users = $usersquery->fetchAll(PDO::FETCH_ASSOC);
	} catch(PDOException $ex) {
		echo "Couldn't retrieve user list";
		return false;
	}
	return $users;
}

function fileExists($conn, $urlid, $hash) {
	$stmt = $conn->prepare("SELECT 1 FROM posts_$urlid WHERE filesum = UNHEX(:filesum)");
	$stmt->bindParam(':filesum', $hash, PDO::PARAM_STR);
	$stmt->execute();
	if($stmt->fetch()) {
		return true;
	} else {
		return false;
	}
}

function createThumbnail($extension, $path, $destination, $width, $height, $oldwidth, $oldheight) {
	if($extension == "jpg") {
		$img = imagecreatefromjpeg($path);
		$newimg = imagecreatetruecolor($width, $height);
		imagecopyresampled($newimg, $img, 0, 0, 0, 0, $width, $height, $oldwidth, $oldheight);
		imagejpeg($newimg, $destination, 100);
		return true;
	}
	if($extension == "png") {
		$img = imagecreatefrompng($path);
		$newimg = imagecreatetruecolor($width, $height);
		imagecopyresampled($newimg, $img, 0, 0, 0, 0, $width, $height, $oldwidth, $oldheight);
		imagepng($newimg, $destination);
		return true;
	}
	if($extension == "gif") {
		$img = imagecreatefromgif($path);
		$newimg = imagecreatetruecolor($width, $height);
		imagegif($newimg, $destination);
		imagecopyresampled($newimg, $img, 0, 0, 0, 0, $width, $height, $oldwidth, $oldheight);
		
		return true;
	}
	return false;
}

function recreateConfig() {
	global $config;
	$configcontents = "<?php\n\n";
	foreach($config as $key=>$value) {
		if($key == 'rootdir') {
			$configcontents .= '$config[\'rootdir\'] = dirname(__FILE__) . "/../";' . "\n";
		} else if(is_string($value)) {
			$configcontents .= '$config[\'' . $key . '\'] = "' . $value . "\";\n";
		} else {
			$configcontents .= '$config[\'' . $key . '\'] = ' . $value . ";\n";
		}
	}
	$configcontents .= "\n?>";
	
	$configfile = fopen($config['rootdir'] . "inc/config.php", "w+");
	if (fwrite($configfile, $configcontents) === 0) {
        error("Couldn't write to file");
    }
}

function error($msg) {
	if(!function_exists('loadTwig')) {
		require "templates.php";
	}
	die(getPage("error.html", array("errormsg"=>$msg)));
}

function completed($msg) {
	if(!function_exists('loadTwig')) {
		require "templates.php";
	}
	die(getPage("completed.html", array("msg"=>$msg)));
}

function boardExists($conn, $board) {
	$stmt = $conn->prepare("SELECT 1 FROM boards WHERE urlid = :board");
	$stmt->bindParam(':board', $board, PDO::PARAM_STR);
	$stmt->execute();
	if($stmt->fetch()) {
		return true;
	} else {
		return false;
	}
}

function getBoardInfo($conn, $urlid) {
	$stmt = $conn->prepare("SELECT * FROM boards WHERE urlid = :board");
	$stmt->bindParam(':board', $urlid, PDO::PARAM_STR);
	$stmt->execute();
	$result = $stmt->fetch();
	return $result;
}

function getPosts($conn, $urlid) {
	$stmt = $conn->prepare("SELECT * FROM posts_$urlid WHERE parent = '0' ORDER by lastreply DESC"); // get threads
	$stmt->execute();
	$result = $stmt->fetchAll();
	return $result;
}

function getPost($conn, $urlid, $postid) {
	$stmt = $conn->prepare("SELECT * FROM posts_$urlid WHERE id = :postid");
	$stmt->bindParam(':postid', $postid, PDO::PARAM_INT);
	$stmt->execute();
	$result = $stmt->fetch();
	return $result;
}

function getReplies($conn, $urlid, $id) {
	$stmt = $conn->prepare("SELECT * FROM posts_$urlid WHERE parent = :parentid ORDER by id");
	$stmt->bindParam(':parentid', $id, PDO::PARAM_INT);
	$stmt->execute();
	$result = $stmt->fetchAll();
	return $result;
}

function threadExists($conn, $urlid, $thread) {
	$stmt = $conn->prepare("SELECT 1 FROM posts_$urlid WHERE id = :thread AND parent = 0");
	$stmt->bindParam(':thread', $thread, PDO::PARAM_INT);
	$stmt->execute();
	if($stmt->fetch()) {
		return true;
	} else {
		return false;
	}
}

function deletePost($conn, $urlid, $postid) {
	global $config;
	// check if its a thread
	// thread - delete post, replies and reply page
	$stmt = $conn->prepare("SELECT parent, filename FROM posts_$urlid WHERE id = :postid");
	$stmt->bindParam(":postid", $postid, PDO::PARAM_INT);
	$stmt->execute();
	$result = $stmt->fetch();
	// delete post
	$stmt = $conn->prepare("DELETE FROM posts_$urlid WHERE id = :postid");
	$stmt->bindParam(':postid', $postid, PDO::PARAM_INT);
	$stmt->execute();
	// if it was a thread, delete replies and reply page
	if($result['parent'] === 0) {
		// delete replies
		$stmt = $conn->prepare("DELETE FROM posts_$urlid WHERE parent = :postid");
		$stmt->bindParam(':postid', $postid, PDO::PARAM_INT);
		$stmt->execute();
		// delete res page
		unlink($config['rootdir'] . "/" . $urlid . "/res/" . $postid . ".html");
	} else {
		// remake parent thread
		createReplyPage($conn, $urlid, $result['parent']);
	}
	// if it had an image
	if(!is_null($result['filename'])) {
		// delete files
		unlink($config['rootdir'] . "/" . $urlid . "/src/" . $result['filename']);
		unlink($config['rootdir'] . "/" . $urlid . "/thumb/" . $result['filename']);
	}
	// remake index page
	createBoardIndex($conn, $urlid);
}

?>