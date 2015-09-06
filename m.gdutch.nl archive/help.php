<?php
include("inc/common.php");


// get help parameter
if (isset($_POST['h'])) {
	$param = $_POST['h'];
} elseif (isset($_GET['h'])) {
	$param = $_GET['h'];
} else {
  $param = 0;
}

if (isset($_SESSION['back'])) {
  $url = $_SESSION['back'];
} else {
		$host  = $_SERVER['HTTP_HOST'];
		$uri   = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
		$extra = LOGINPAGE;
		$url = "http://$host$uri/$extra";
}

switch($param){
	case 0:
		// no help param
		$title = "Going Dutch";
		$text[] =  "An application to manage al your group expense needs";
    $text[] =  "Copyright 2010 - InThere";
    break;

	case 1:
    // group page
		$title = "Going Dutch - Your groups";
		$text[] =  "An application to manage al your group expense needs";
    $text[] =  "Copyright 2010 - InThere";
    break;
   
	case 2:
		// group_detail page
		$title = "Going Dutch - Balance overview of the group";
		$text[] =  "An application to manage al your group expense needs";
    $text[] =  "Copyright 2010 - InThere";
    break;

	case 3:
    // expenses page
		$title = "Going Dutch - The expenses overview";
		$text[] =  "An application to manage al your group expense needs";
    $text[] =  "Copyright 2010 - InThere";
    break;
   
	case 4:
		// add expense page
		$title = "Going Dutch - The expense overview page";
		$text[] =  "An application to manage al your group expense needs";
    $text[] =  "Copyright 2010 - InThere";
    break;

	case 5:
    // register page
		$title = "Going Dutch - Registering and logging in";
		$text[] =  "An application to manage al your group expense needs";
    $text[] =  "Copyright 2010 - InThere";
    break;
 
	case 6:
    // profile page
		$title = "Going Dutch - Your profile page";
		$text[] =  "An application to manage al your group expense needs";
    $text[] =  "Copyright 2010 - InThere";
    break;
 
	case 7:
    // expense detail  page
		$title = "Going Dutch - Expense detail page";
		$text[] =  "An application to manage al your group expense needs";
    $text[] =  "Copyright 2010 - InThere";
    break;       
}
print_header();

$topbar['title'] = "Help";
$topbar['leftnav'][0]['name'] = "Back";
$topbar['leftnav'][0]['url'] =  $url;
print_topbar($topbar);
print_body_start();

print_pageitem_text_html($title, $text);
