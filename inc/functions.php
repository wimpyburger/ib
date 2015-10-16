<?php

if(basename(__FILE__) == basename($_SERVER["SCRIPT_FILENAME"])) die();

function sqlConnect() {
	global $config;
	return new PDO("mysql:host={$config['sqlhost']};dbname={$config['sqldb']}", $config['sqluser'], $config['sqlpass'], array(PDO::ATTR_EMULATE_PREPARES => false, PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION));
}

function installBoard($conn) {
	global $config;
	$installsql = file_get_contents($config['rootdir'] . "inc/createtables.sql");
	try {
		$installquery = $conn->query($installsql);
	} catch(PDOException $ex) {
		die("Couldn't create tables: " . $ex);
	}
	
	echo "Installed";
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

?>