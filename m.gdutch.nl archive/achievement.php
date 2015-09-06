<?php
include("inc/common.php");
include("inc/datetime_dropdown.php");
date_default_timezone_set('UTC');
// get post mode
if (isset($_POST['mode'])) {
	$mode = $_POST['mode'];
} elseif (isset($_GET['mode'])) {
	$mode = $_GET['mode'];
} else {
  $mode = "show";
}

// get post mode
if (isset($_POST['acid'])) {
	$acid = $_POST['acid'];
} elseif (isset($_GET['acid'])) {
	$acid = $_GET['acid'];
} else {
  fatal_error("No achievement specified");
}

// Get group for expense
$groupid = get_groupid_by_acid($acid);
// get permisssions for group
$permissions = group_permissions($groupid, $user->data['user_id']);
if (!$permissions || !array_key_exists(5, $permissions)) {
  fatal_error("No permissions for this achievement");
}

// get message
if (isset($_GET['msg'])) {
	$message = get_msg($_GET['msg']);
}

$details = get_achievementdetails($acid);

print_header();
// array structure: $bararray['title'], $bararray['leftnav'][$i][name|url], $bararray['rightnav'][$i][name|url]
$urll = $_SERVER['PHP_SELF'] . "?acid=$acid&mode=edit";
$urlld = $_SERVER['PHP_SELF'] . "?acid=$acid&mode=del";
$topbar['title'] = $groupdetails['name'];
/*
$topbar['leftnav'][0]['name'] = "List";
$topbar['leftnav'][0]['url'] = $_SESSION['back'];*/
$back = get_back_page();
$topbar['leftnav'][0]['name'] = $back['name'];
//$topbar['leftnav'][0]['url'] =  $back['url'];
$topbar['leftnav'][0]['url'] =  "javascript:history.back()";

print_topbar($topbar);
print_body_start();




if ($message) { 
   print_pagetitle($message);
}

$groupdetails = get_groupdetails($groupid);

print_achievementdetails_html($details,$user, $groupdetails);





print_footer($user,7);
?>