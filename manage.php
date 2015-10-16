<?php

require "inc/config.php";
require "inc/templates.php";
require "inc/functions.php";

$conn = sqlConnect();

if(isset($_GET['configure'])) {
	foreach($config as $key=>$value) {
		echo "$key - $value<br>";
	}
	
	die();
}

if(isset($_GET['install'])) {
	installBoard($conn);
}

if(isset($_GET['createboard'])) {
	installBoard($conn);
}

if(!$config['installed']) {
	die("<a href=\"?install\">Install now</a>");
}

$boards = getBoards($conn);

echo getPage("manage.html", array("boards" => $boards));

?>