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
  $backurl = $_SESSION['back'];
} else {
		$host  = $_SERVER['HTTP_HOST'];
		$uri   = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
		$extra = LOGINPAGE;
		$backurl = "http://$host$uri/$extra";
}

// get post mode
if (isset($_POST['uid'])) {
	$uid = $_POST['uid'];
} elseif (isset($_GET['uid'])) {
	$uid = $_GET['uid'];
} else {
  $uid = $user->data['user_id'];
}

$grouplist = get_groups($uid);

//TODO CHECK GROUPS

if ($uid == $user->data['user_id']) {
  // only edit own profile 
  $editprofile = true;
  $owngroupids = get_groupids($grouplist);
} else {
  // check if other user is part of your groups, if not exit
  $groupids = get_groupids($grouplist);
  $memberlist = get_groupmembers($groupids);
  $memberids = get_groupmember_ids($memberlist);
  //printr($memberlist);
  if (!in_array($uid,$memberids)) {
    fatal_error("No permission to view this profile");
  }
  $owngroupids = get_groupids(get_groups($user->data['user_id']));
}
  
  // get message
if (isset($_GET['msg'])) {
	$message = get_msg($_GET['msg']);
}

switch($mode){
	case "show":
		$showprofile = true;
    $profile = get_user_profile($uid);
    $acList = get_user_achievements($uid, $owngroupids);
    
    if ($_SESSION['back'] != $_SERVER['PHP_SELF']."?mode=edit" && $_SESSION['back'] != $_SERVER['PHP_SELF'] && !strpos($_SESSION['back'],"expenses.php")) {
      $_SESSION['pshow_back'] = $_SESSION['back'];
      $backurl = $_SESSION['pshow_back'];
    }  else {
      $backurl = $_SESSION['pshow_back'];
    }
    break;
  
  case "edit":
    $editprofile = true;
    $profile = get_user_profile($uid);
    $backurl =$_SERVER['PHP_SELF'];
		break;
    
  case "validate":
		include("inc/email_validator.php");
		
		// validate fields
		$errorString = ""; 
    
  	if ($_POST['token'] != $_SESSION['token']) {
  			$errorString[] = "Invalid token! (what are you doing??)<br />";
  	}
    $token_age = time() - $_SESSION['token_time'];	// force to resubmit after 5 minutes
		if ($token_age > 300) {
  			$errorString[] = "Timout value exceeded, resubmit<br />";
  	}
  	
		if ($_POST['realname'] != $user->data['real_name'] && !is_valid_real_name($_POST['realname']) && $_POST['realname'] != "") {
			$errorString[] = "Invalid name";
		}
    if (!is_curr_password($_POST['curpassword'],$user)) {
			$errorString[] = "Current password not correct";
		}
    if (!is_valid_password($_POST['passwordx'],$_POST['password2']) && ($_POST['passwordx'] != "" || $_POST['password2'] != "") ) {
			$errorString[] = "Passwords do not match or are not of required length";
		}
		if ($_POST['email'] != $user->data['email'] && !is_rfc3696_valid_email_address($_POST['email']) && $_POST['email'] != "") {
			$errorString[] = "Invalid email address";
		}
		if ($_POST['email'] != $user->data['email'] && email_exists($_POST['email'],true)) {
			$errorString[] = "Email address already in use";
		}
		if ($_POST['name_format'] != $user->data['name_format']) {
			if ($_POST['name_format'] > 4 || $_POST['name_format'] < 1) $errorString[] = "Invalid name format! (what are you doing?)";
		} 
    if ( !is_valid_amount($_POST['amount']) && strtolower($_POST['amount']) != "always"
        && $_POST['amount'] != "" && $_POST['amount'] != "0" && $_POST['email_notify']) {
			$errorString[] = "Invalid notify amount";
		}
		
    if(!empty($errorString)) {
			$editprofile = true;
    } else {
    	
      if ($_POST['realname'] != $user->data['real_name'] && $_POST['realname'] != "") { 
        $update['realname'] = $_POST['realname'];
      }
      if ($_POST['email'] != $user->data['email'] && $_POST['email'] != "") {
        $update['email'] = $_POST['email'];
      }
      if ($_POST['passwordx'] != "") {
        $update['password'] = $_POST['passwordx'];
      }
      if ($_POST['name_format'] != $user->data['name_format']) {
      	$update['name_format'] = $_POST['name_format'];
      }

      //if ($_POST['amount'] != $user->data['email_notify'] && $_POST['amount'] != "") { 
     // if ($_POST['amount'] != $user->data['email_notify'] ) { 
      	if (!isset($_POST['email_notify']) || $_POST['email_notify'] == 0) $emailn = -1;
      	elseif (strtolower($_POST['amount']) == 'always' || $_POST['amount'] == ""  || $_POST['amount'] == 0) $emailn = 0;
      	else $emailn = $_POST['amount'];
        $update['email_notify'] = $emailn;
        
      //}

      if (update_user_profile($user,$update) ) $msg = "p1";
      else $msg = "p2";
    }
    
		break;
}

if ($msg) { 
  $redirect = "http://" . $_SERVER['HTTP_HOST'] . DIR . "profile.php?msg=$msg";
  header("Location: $redirect");
}


// start HTML output
  
if ($showprofile && $editprofile) {
  $urll = $_SERVER['PHP_SELF'] . "?mode=edit"; 
  $topbar['rightnav'][0]['name'] = "Edit";
  $topbar['rightnav'][0]['url'] =  $urll;
}
$topbar['title'] = "Profile";
/*
if (strpos($backurl,"group_detail.php")) $bname = "Group";
else $bname = "Back";
$topbar['leftnav'][0]['name'] = $bname;
$topbar['leftnav'][0]['url'] =  $backurl;*/
$back = get_back_page();
$topbar['leftnav'][0]['name'] = $back['name'];
$topbar['leftnav'][0]['url'] =  $back['url'];

print_header();
print_topbar($topbar);
print_body_start();

if ($showprofile) {
  if ($message) { 
    print_pagetitle($message);
  }
  
  if (is_array($acList)) {
    print_achievement_html($acList, $owngroupids, $grouplist);
  }
  
  print_profile_html($profile,$user->data['user_id']);

  //$grouplist = get_groups($uid);
  // group_id - group_name - role - join_date - member_count
  $size = count($grouplist);
  if ($size > 0) {
    $user_expenses = get_user_expenses($uid);
    $user_paid_expenses = get_user_paid_expenses($uid);
    for ($i = 0; $i < $size; $i++) {
      if (in_array($grouplist[$i]['group_id'],$owngroupids)) {
        // only show groups that are also in user's group list
        $uexpense = $user_expenses['users'][$uid]['groups'][$grouplist[$i]['group_id']]['group_total'];
        $upaid = $user_paid_expenses['users'][$uid]['groups'][$grouplist[$i]['group_id']]['group_total'];
        $group_balance_list[$i]['group_id'] = $grouplist[$i]['group_id'];
        $group_balance_list[$i]['group_name'] = $grouplist[$i]['group_name'];
        $group_balance_list[$i]['expenses'] = number_format(($uexpense),DECIMALS , DSEP,TSEP) ;
        $group_balance_list[$i]['paid'] = number_format(($upaid),DECIMALS , DSEP,TSEP) ;
        $group_balance_list[$i]['balance'] = number_format(($upaid-$uexpense),DECIMALS , DSEP,TSEP) ;
        $group_balance_list[$i]['link'] = "http://" . $_SERVER['HTTP_HOST'] . DIR . "expenses.php?groupid=" . $grouplist[$i]['group_id'] . "&uid=$uid";
        if ($grouplist[$i]['role'] == "founder") $group_balance_list[$i]['canedit'] = true;
        else $group_balance_list[$i]['canedit'] = false;
      }
    }
    print_group_balance_list_html($group_balance_list);

  }
} elseif ($editprofile) {
  
  if ($errorString) {
    print_pageitem_text_html("Please correct the following:", $errorString);
  }
  
  if (isset($_POST['realname'])) $realname = $_POST['realname'];
  else $realname = $user->data['realname'];
  if (isset($_POST['email'])) $email = $_POST['email'];
  else $email = $user->data['email'];
  
  if (isset($_POST['mode']) && isset($_POST['email_notify'])) 
    $checked = $_POST['email_notify'];
  elseif (isset($_POST['mode']) && !isset($_POST['email_notify'])) 
    $checked = 0;
  elseif ($user->data['email_notify'] == -1) $checked = 0;
  else $checked = 1;
  
  if (isset($_POST['name_format'])) $nformat = $_POST['name_format'];
  else $nformat = $user->data['name_format'];
  
  if (isset($_POST['amount'])) $amount = $_POST['amount'];
  elseif ($user->data['email_notify'] == -1) $amount = '';
  elseif ($user->data['email_notify'] == 0) $amount = "always";
  else $amount = $user->data['email_notify'];
  
  //$formarray['rows'][$i]['items'] = "label|name|type|value";
  $formarray['action'] = $_SERVER['PHP_SELF'];
  //$formarray['rows'][0]['items'] = "Username:|username|text|" . $_POST['username'];
  $formarray['rows'][0]['items'] = "Name:|realname|text|" . $realname;
  $formarray['rows'][1]['items'] = "New password:|passwordx|password";
  $formarray['rows'][2]['items'] = "Re-enter:|password2|password";
  $formarray['rows'][3]['items'] = "Email:|email|text|" . $email;
  
  $formarray['rows'][4]['label'] = "Name format:";
  $formarray['rows'][4]['type'] = "select";
  $formarray['rows'][4]['name'] = "name_format";
  $formarray['rows'][4]['value'] = get_name_format(1, true);
  $formarray['rows'][4]['selected'] = $nformat;
 
  $formarray['rows'][5]['type'] = "checkbox";
  $formarray['rows'][5]['label'] = "Notify by email:";
  $formarray['rows'][5]['name'] = "email_notify";
  $formarray['rows'][5]['checked'] = $checked;
  $formarray['rows'][5]['value'] = 1; 

  $formarray['rows'][6]['items'] = "Notify amount:|amount|text|" . $amount;
    
  
  $formarray['rows'][7]['items'] = "Current pass:|curpassword|password";
  $formarray['rows'][8]['items'] = "|mode|hidden|validate";
  $formarray['rows'][9]['items'] = "||submit|Change!";
  echo create_form_html($formarray);
  unset($formarray);
}

print_footer($user,6);
?>


