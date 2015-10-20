<?php

require "inc/config.php";
require "inc/templates.php";
require "inc/functions.php";

$conn = sqlConnect();
$boards = getBoards($conn);
die(getPage("index.html", array("boards"=>$boards)));

?>