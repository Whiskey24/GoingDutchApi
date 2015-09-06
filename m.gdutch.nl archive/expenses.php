<?php
include("inc/common.php");


// check if valid group specified and return group details 
$groupdetails = check_group($_POST, $_GET);

// get permisssions for group
$permissions = group_permissions($groupdetails['group_id'], $user->data['user_id']);
if (!$permissions) {
  fatal_error("No permissions for this group");
}

// get post mode
if (isset($_POST['mode'])) {
	$mode = $_POST['mode'];
} elseif (isset($_GET['mode'])) {
	$mode = $_GET['mode'];
} else {
  $mode = "showexpenses";
}

// get user id if specified
if (isset($_POST['uid'])) {
	$uid = $_POST['uid'];
} elseif (isset($_GET['uid'])) {
	$uid = $_GET['uid'];
} 

// get paid / spent if specified
if (isset($_POST['xtype'])) {
	$xtype = $_POST['xtype'];
} elseif (isset($_GET['xtype'])) {
	$xtype = $_GET['xtype'];
} 

// get message
if (isset($_GET['msg'])) {
	$message = get_msg($_GET['msg']);
}

switch($mode){
  case "showexpenses":
		$showexpenses = true;
    break;    
}

// Start HTML output

print_header();

// array structure: $bararray['title'], $bararray['leftnav'][$i][name|url], $bararray['rightnav'][$i][name|url]
$topbar['title'] = $groupdetails['name'];
// $topbar['leftnav'][0]['name'] = "Group";
// $topbar['leftnav'][0]['url'] =  "http://" . $_SERVER['HTTP_HOST'] . DIR . "group_detail.php?groupid=" . $groupdetails['group_id'];


/*if ( !strpos($_SESSION['back'],"expenses.php") && !strpos($_SESSION['back'],"expense_detail.php")) {
  $_SESSION['eshow_back'] = $_SESSION['back'];
       $backurl = $_SESSION['eshow_back'];
    }  else {
      $backurl = $_SESSION['eshow_back'];
    }
    
if (strpos($backurl,"group_detail.php")) $bname = "Group";
elseif (strpos($backurl,"profile.php")) $bname = "Profile";
else $bname = "Back";

$topbar['leftnav'][0]['name'] = $bname;
$topbar['leftnav'][0]['url'] =  $_SESSION['back'];
$topbar['leftnav'][0]['url'] =  $backurl;*/

$back = get_back_page();
$topbar['leftnav'][0]['name'] = $back['name'];
$topbar['leftnav'][0]['url'] =  $back['url'];

print_topbar($topbar);
print_body_start();


if ($showexpenses && array_key_exists(5, $permissions) ) {
  if ($message) { 
    print_pagetitle($message);
  }
  
  //$expenselist = get_groupexpenses($groupdetails['group_id'],$uid, "neg");
  $expenselist = get_groupexpenses($groupdetails['group_id'],$uid, $xtype, $user->data['user_id']);
  if(!empty($expenselist))  print_expenselist_html($expenselist,$user);
  else print_pageitem_text_html("Sorry", "No expenses were found");
  
}  

//if ($ask_add && array_key_exists(0, $permissions) ) { 
//  $a = "Add";
//  $b = "more members";
//  $formarray['action'] = $_SERVER['PHP_SELF'];
//  $formarray['rows'][0]['type'] = "select";
//  $formarray['rows'][0]['name'] = "number";
//  $formarray['rows'][0]['value'] = array(1=>"$a 1 $b",2=>"$a 2 $b",3=>"$a 3 $b",4=>"$a 4 $b",5=>"$a 5 $b",6=>"$a 6 $b",7=>"$a 7 $b",8=>"$a 8 $b",9=>"$a 9 $b",10=>"$a 10 $b");
//  $formarray['rows'][1]['items'] = "|mode|hidden|addmembers";
//  $formarray['rows'][2]['items'] = "|groupid|hidden|" .$groupdetails['group_id'];
//  $formarray['rows'][3]['items'] = "||submit|Go";
//  echo create_form_html($formarray);
//  unset($formarray);
//}

print_footer($user,3,$groupdetails['group_id']);
?>