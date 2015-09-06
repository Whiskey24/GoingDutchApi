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

$showgrouplist = true;
$text = "Your groups";
$showgroupaddform = false;

switch($mode){
  case "show":
		$showgroupaddlink = true;
		break;

	case "add":
		$showgroupaddform = true;
    $text = "Add a group";
		break;

  case "validate":
    $errorString = "";
    
  	if ($_POST['token'] != $_SESSION['token']) {
  			$errorString[] = "Invalid token! (what are you doing??)<br />";
  	}
    $token_age = time() - $_SESSION['token_time'];	// force to resubmit after 5 minutes
		if ($token_age > 300) {
  			$errorString[] = "Timout value exceeded, resubmit<br />";
  	}
  	
    if (!is_valid_real_name($_POST['name'])) {
			$errorString[] = "Invalid groupname";
		} elseif (group_exists($_POST['name'])) {
			$errorString[] = "Groupname already in use <br />";
      //$showgrouplist = false;  // do not show group list when dealing with add errors
    }
    if (!is_valid_real_name($_POST['description'])) {
			$errorString[] = "Invalid description";
    }
    if(!empty($errorString)) {
			$showgroupaddform = true;
		} else {
      // group does not exist, create group   
	  $addgroupresult = "";
      if (!add_group($_POST['name'],$_POST['description'], $user->data['user_id'] )) {
        $errorString[] = "Could not add group to database";
        $showgroupaddform = true;
      } else {
        $addgroupresult[] = "Group " . $_POST['name'] . " has been created";
      }
    }
    
}

print_header(); 


$topbar['title'] = "Your Groups";
print_topbar($topbar);
print_body_start();


if ($showgroupaddform) {
  if ($errorString) {
    print_pageitem_text_html("Please correct the following:", $errorString);
  }
  //$formarray['rows'][$i]['items'] = "label|name|type|value";
  $formarray['action'] = $_SERVER['PHP_SELF'];
  $formarray['rows'][0]['items'] = "Name:|name|text|" . $_POST['name'];
  $formarray['rows'][1]['items'] = "Description:|description|text|" . $_POST['description'];
  $formarray['rows'][2]['items'] = "|mode|hidden|validate";
  $formarray['rows'][3]['items'] = "||submit|Add Group";
  echo create_form_html($formarray);
  unset($formarray);

} 

  if ($addgroupresult) {
    print_pageitem_text_html("Succes:", $addgroupresult);
  }

if ($showgrouplist) {
  $grouplist = get_groups($user->data['user_id']);
  // group_id - group_name - role - join_date - member_count
  $size = count($grouplist);
  if ($size > 0) {
	$user_expenses = get_user_expenses($user->data['user_id']);
	$user_paid_expenses = get_user_paid_expenses($user->data['user_id']);
	for ($i = 0; $i < $size; $i++) {
		$uexpense = $user_expenses['users'][$user->data['user_id']]['groups'][$grouplist[$i]['group_id']]['group_total'];
		$upaid = $user_paid_expenses['users'][$user->data['user_id']]['groups'][$grouplist[$i]['group_id']]['group_total'];
		$grouplist[$i]['balance'] = number_format(($upaid-$uexpense),DECIMALS , DSEP,TSEP) ; 
		$grouplist[$i]['link'] = "http://" . $_SERVER['HTTP_HOST'] . DIR . "group_detail.php?groupid=" . $grouplist[$i]['group_id'];
	}
    print_grouplist_html($grouplist);

  } elseif (!$showgroupaddform) {
	$text = "<a href=\"http://" . $_SERVER['HTTP_HOST'] . DIR . "group.php?mode=add\">create one now!</a>";
    print_pageitem_text_html("No groups found", $text);
  }
  if ($showgroupaddlink) {
	$url = "http://" . $_SERVER['HTTP_HOST'] . DIR . "group.php?mode=add";
	$name = "Add Group";
	print_topbutton_html ($name, $url);
  }

}

print_footer($user,1);
?>