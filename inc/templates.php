<?php

if(basename(__FILE__) == basename($_SERVER["SCRIPT_FILENAME"])) die();

$twig = false;

function loadTwig() {
	global $config, $twig;
	
	require 'lib/Twig/Autoloader.php';
	Twig_Autoloader::register();
	$loader = new Twig_Loader_Filesystem($config['rootdir'] . "templates/");
	$twig = new Twig_Environment($loader, array(
		'cache' => false,
	));
}

function getPage($file, array $args) {
	global $config, $twig;
	
	if(!$twig) loadTwig();
	$template = $twig->loadTemplate($file);
	$config = array('config' => $config);
	return $template->render(array_merge($config, $args));
}

?>