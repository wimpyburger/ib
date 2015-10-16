<?php

require "inc/config.php";
require "inc/templates.php";

$conn = sqlConnect();

echo getPage("index.html", array());

?>