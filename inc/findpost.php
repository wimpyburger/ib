<?php

require "config.php";
require "functions.php";

$conn = sqlConnect();

if(!isset($_GET['board'])) {
	error("Board doesn't exist");
}
if(!boardExists($conn, $_GET['board'])) {
	error("Board doesn't exist");
}
if(!is_numeric($_GET['id'])) {
	error("Invalid post number");
}

$stmt = $conn->prepare("SELECT parent FROM posts_{$_GET['board']} WHERE id = :id");
$stmt->bindParam(":id", $_GET['id']);
$stmt->execute();
$result = $stmt->fetch();

if($result['parent'] == 0) {
	header("Location: {$config['siteurl']}/{$_GET['board']}/res/{$_GET['id']}.html");
} else {
	header("Location: {$config['siteurl']}/{$_GET['board']}/res/{$result['parent']}.html#{$_GET['id']}");
}

?>