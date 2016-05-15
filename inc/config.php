<?php

$config['installed'] = 0;
$config['sitename'] = "AYY";
$config['stylesheets'] = array("yotsubab", "yotsuba", "gamma", "oldschool", "3hat", "3hatlight");
$config['defaultcss'] = "gamma";
$config['siteurl'] = "http://localhost/_OTHER_/imageboard";
$config['sqlhost'] = "localhost";
$config['sqluser'] = "root";
$config['sqlpass'] = "";
$config['sqldb'] = "ib";
$config['rootdir'] = dirname(__FILE__) . "/../";
$config['minmessagechars'] = 1;
$config['maxmessagechars'] = 1200;
$config['allowimageonly'] = 1;
$config['defaultpostername'] = "Anonymous";
$config['maximagesize'] = 1048576;
$config['postdelay'] = 15;
$config['threaddelay'] = 90;
$config['imagerequired'] = 0;
$config['noko'] = 1;
$config['accounttypes'] = array("Admin", "Mod", "Janitor");
$config['modpages'] = array("deletepost", "viewboard", "manageaccounts", "refreshstatic", "logout", "editaccount", "banposter", "unbanuser", "managebans", "postsbyip");
$config['janitorpages'] = array("deletepost", "viewboard", "manageaccounts", "logout", "editaccount", "postsbyip");
$config['threadlimit'] = 150;
$config['displayedreplies'] = 3;
$config['textonly'] = 0;

?>