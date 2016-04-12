<?php

require "config.php";
require "templates.php";
require "functions.php";
require "generatepages.php";

function date_compare($a, $b)
{
    $t1 = strtotime($a['date']);
    $t2 = strtotime($b['date']);
    return $t2 - $t1;
}    

$conn = sqlConnect();

$boards = getBoards($conn);
$allposts = [];
$images = [];
$ips = [];
$stats['totalposts'] = 0;
$postindex = 0;

foreach($boards as $board) {
	$posts = getAllPosts($conn, $board['urlid']);
	foreach($posts as $key=>$value) {
		if($value['parent'] == 0) {
			$posts[$key]['url'] = $value['id'];
		} else {
			$posts[$key]['url'] = $value['parent'];
		}
		$posts[$key]['board'] = $board;
		if($value['filename'] != "") {
			array_push($images, $posts[$key]);
		}
		$allposts[$postindex] = $posts[$key];
		$postindex++;
		array_push($ips, $value['ip']);
	}
	$stats['totalposts'] += $value['id'];
}

$stats['uniqueposters'] = sizeof(array_unique($ips));

usort($allposts, 'date_compare');
usort($images, 'date_compare');
$allposts = array_slice($allposts, 0, 10, true);
$images = array_slice($images, 0, 12, true);

die(getPage("recentposts.html", array("posts"=>$allposts, "images"=>$images, "stats"=>$stats)));

?>