<?php
include("inc/common.php");




// get post mode
if (isset($_POST['mode'])) {
	$mode = $_POST['mode'];
} elseif (isset($_GET['mode'])) {
	$mode = $_GET['mode'];
} else {
  $mode = "show";
}

if (isset($_SESSION['back'])) {
  $url = $_SESSION['back'];
} else {
		$host  = $_SERVER['HTTP_HOST'];
		$uri   = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
		$extra = LOGINPAGE;
		$url = "http://$host$uri/$extra";
}

switch($mode){
	case "show":
		$showprofile = true;
    $profile = get_user_profile($user->data['user_id']);
		break;
  
  case "edit":
    $editprofile = true;
    $profile = get_user_profile($user->data['user_id']);
    $backurl = $url;
		break;
}


// start HTML output

print_header();

$topbar['title'] = "Profile";
$topbar['leftnav'][0]['name'] = "Back";
$topbar['leftnav'][0]['url'] =  $url;
print_topbar($topbar);
print_body_start();
print_profile_html($profile,$user->data['user_id']);
print_footer($user,6);
?>
