<?php
include("inc/common.php");


// check if valid group specified and return group details 
$groupdetails = check_group($_POST, $_GET);

// get permisssions for group
if (!$permissions = group_permissions($groupdetails['group_id'], $user->data['user_id'])) {
  fatal_error("No permissions for this group");
}

// get post mode
if (isset($_POST['mode'])) {
	$mode = $_POST['mode'];
} elseif (isset($_GET['mode'])) {
	$mode = $_GET['mode'];
} else {
  $mode = "showmembers";
}

// get message
if (isset($_GET['msg'])) {
	$message = get_msg($_GET['msg']);
}

switch($mode){
	case "edit":
		$editgroup = true;
		break;

  case "showmembers":
		$showmembers = true;
		$ask_add = true;
    $ask_event = true;
    break;

  case "addmembers":
    $showmembers = true;
    $ask_add = false;
    $add_form = true;
    $membersize = intval($_POST['number']);
    if ($membersize > 10) $membersize = 10;
    if ($membersize < 1) $membersize = 1;
    break;
    
  case "validatemembers":
    include("inc/email_validator.php");
    $membersize = intval($_POST['number']);
    if ($membersize > 10) $membersize = 10;
    if ($membersize < 1) $membersize = 1;
    // validate all members
    $errorString = ""; 

  	if ($_POST['token'] != $_SESSION['token']) {
  			$errorString[] = "Invalid token! (what are you doing??)<br />";
  	}
    $token_age = time() - $_SESSION['token_time'];	// force to resubmit after 5 minutes
		if ($token_age > 300) {
  			$errorString[] = "Timout value exceeded, resubmit<br />";
  	}
    
    
    for ($i = 1; $i < $membersize+1; $i++) { 
      $uid = false;
      // first check email for existing user
      if (!is_rfc3696_valid_email_address($_POST["invite-email-$i"]) && !empty($_POST["invite-email-$i"])) {
        $errorString[] = "Not a valid email address for number $i<br />";
      }
      if (email_exists($_POST["invite-email-$i"]) && !empty($_POST["invite-email-$i"])) {
        // $errorString .= "<b>Error:</b> Email address already in use for number $i<br />";
        // user is already registered, take that user_id by storing it in temp array
        $uid = get_userid_by_email($_POST["invite-email-$i"]);
        $existing_users[$uid] = $_POST["invite-email-$i"];
      }
  
      // ignore empty field sets
      if (empty($_POST["invite-name-$i"]) && !empty($_POST["invite-email-$i"]) && !$uid ) {
        $errorString[] = "Name is mandatory, but only email given for number $i<br />";
      }
      elseif (!empty($_POST["invite-name-$i"]) ) {
        if (!is_valid_real_name($_POST["invite-name-$i"])) {
          $errorString[] = "Invalid name for number $i<br />";
        } elseif (realname_exists($_POST["invite-name-$i"]) ) {
          $errorString[] = "Name exists for number $i (try adding by email)<br />";
        }
      } 
		}
    if(!empty($errorString)) {
			$add_form = true;
		} else {
      // no errors add members
      $resultString = "";
      for ($i = 1; $i < $membersize+1; $i++) { 
        if (!empty($_POST["invite-name-$i"]) && !in_array($_POST["invite-email-$i"],$existing_users) ) {
          $newuserid = add_member($_POST["invite-name-$i"], $_POST["invite-email-$i"], $groupdetails['group_id']);
          if ($newuserid != false ) {
            $resultString[] = "Added " . $_POST["invite-name-$i"] . "<br />";
            if (isset($_POST["email_invite"]) && $_POST["email_invite"] == 1)
              invite_member( $_POST["invite-email-$i"],   $newuserid, $groupdetails['group_id'], $groupdetails['name'] );
          } else {
            $resultString[] = "Cannot add " . $_POST["invite-name-$i"] . "<br />";
          }
        }
      }
      foreach ($existing_users as $key => $value) {
        if (add_member_to_group($key,$groupdetails['group_id'])) {
          $resultString[] = "User with email $value already registered. Added this user.<br />";
        } else {
          $resultString[] = "Cannot add existing user with email: $value <br />";
        }
      }
      $showmembers = true;
		  $ask_add = true;
      $ask_event = true;
    }      
    break;
    
}

// Start HTML output

print_header();

// array structure: $bararray['title'], $bararray['leftnav'][$i][name|url], $bararray['rightnav'][$i][name|url]
$topbar['title'] = $groupdetails['name'];
$topbar['leftnav'][0]['name'] = "Groups";
$topbar['leftnav'][0]['url'] = "http://" . $_SERVER['HTTP_HOST'] . DIR . "group.php";
$topbar['rightnav'][0]['name'] = "Exp.";
$topbar['rightnav'][0]['url'] = "http://" . $_SERVER['HTTP_HOST'] . DIR . "expenses.php?groupid=" . $groupdetails['group_id'];
print_topbar($topbar);
print_body_start();

if ($add_form) { 
  //print_topbar("Add members");
  if ($membersize == 1) print_pagetitle("Add member");
  else print_pagetitle("Add members");
  
  if ($errorString) {
    print_pageitem_text_html("Please correct the following:", $errorString);
  }
  //$formarray['rows'][$i]['items'] = "label|name|type|value";
  $formarray['action'] = $_SERVER['PHP_SELF'];
  $formarray['rows'][0]['items'] = "|groupid|hidden|" . $groupdetails['group_id'];
  $formarray['rows'][1]['items'] = "|number|hidden|" .$membersize;
  $formarray['rows'][2]['items'] = "|mode|hidden|validatemembers";
  $formarray['rows'][3]['items'] = "||submit|Add";
  $c = 4;
  for ($i = 1; $i < $membersize+1; $i++) {
    if ($membersize == 1) $index = "";
    else $index = "$i. ";
    $formarray['rows'][$c]['items'] = $index. "Name:|invite-name-$i|text|" . $_POST["invite-name-$i"] ;
    $formarray['rows'][$c+1]['items'] = "Email:|invite-email-$i|text|" . $_POST["invite-email-$i"] ;
    $c += 2;
  }
  
  
  
      $formarray['rows'][$c]['type'] = "checkbox";
    $formarray['rows'][$c]['label'] = 'Send invite by email';
    $formarray['rows'][$c]['name'] = "email_invite";
    $formarray['rows'][$c]['checked'] = 1;
    $formarray['rows'][$c]['value'] =  1; 
    
  echo create_form_html($formarray);
  unset($formarray);
}

if ($showmembers && array_key_exists(5, $permissions) ) {
  if ($message) { 
    print_pagetitle($message);
  }
  if ($resultString) {    
    echo n(4) . "<fieldset>\n";
    echo n(6) . "<ul class=\"pageitem\">\n";
    echo n(8) . "<li class=\"textbox\"><span class=\"header\">Processed:</span></li>\n";
    foreach ($resultString as $key => $value) {
      echo n(8) . "<li class=\"textbox\">$value</li>\n";  
    }
    echo n(6) . "</ul>\n";
    echo n(4) . "</fieldset>\n";
  }

  if ( ( array_key_exists(3, $permissions) || array_key_exists(4, $permissions) ) && !$add_form ) {
    // show add expense button
    $url = "http://" . $_SERVER['HTTP_HOST'] . DIR . "book.php?groupid=" . $groupdetails['group_id'];
    $name = "Add Expense";
    print_topbutton_html ($name, $url);
    // show group deposit button
//    if (array_key_exists(0, $permissions) ) { 
//      $url = "http://" . $_SERVER['HTTP_HOST'] . DIR . "deposit.php?groupid=" . $groupdetails['group_id'];
//      $name = "Group Deposit";
//      print_topbutton_html ($name, $url);
//      }
  }  
  
  // Upcoming event
/*          $event[$row['event_id']]['event_id'] = $row['event_id'];
        $event[$row['event_id']]['event_name'] = $row['event_name'];
        $event[$row['event_id']]['description'] = $row['event_description'];
        $event[$row['event_id']]['organizer'] = $row['organizer_id'];
        $event[$row['event_id']]['date'] = date("j M Y", $row['date']);
        $event[$row['event_id']]['expense_type'] = $row['expense_type_id']; */
  $events = get_upcoming_events($groupdetails['group_id']);
 if ($events) print_upcoming_events_html($events,$groupdetails['group_id']);
  
  $memberlist = get_groupmembers($groupdetails['group_id']);
  $groupmemberids = get_groupmember_ids($memberlist);
  $user_expenses = get_user_expenses($groupmemberids);
  $user_paid_expenses = get_user_paid_expenses($groupmemberids);
  $size = count($memberlist);
    
  for ($i = 0; $i < $size; $i++) {
    if (!empty($memberlist[$i]['username'])) $uname = " (" . $memberlist[$i]['username'] . ")";
    else $uname = "";

    $uexpense = $user_expenses['users'][$memberlist[$i]['user_id']]['groups'][$groupdetails['group_id']]['group_total'];
    $upaid = $user_paid_expenses['users'][$memberlist[$i]['user_id']]['groups'][$groupdetails['group_id']]['group_total'];
  
    $listarray[$i]['link'] = "http://" . $_SERVER['HTTP_HOST'] . DIR . "profile.php?uid=" . $memberlist[$i]['user_id'];
    //$listarray[$i]['name'] = $memberlist[$i]['realname'] . $uname;
    $listarray[$i]['name'] = format_name($user,$memberlist[$i]['username'],$memberlist[$i]['realname']);
    //$listarray[$i]['balance'] = number_format(($upaid-$uexpense),DECIMALS , DSEP,TSEP) ;
    $listarray[$i]['balance'] = $upaid-$uexpense;
    $listarray[$i]['user_id'] = $memberlist[$i]['user_id'];
  }
  
  print_memberlist_html($listarray, SORT_DESC);


// show link to the transactions to settle balances
  $purl = "http://" . $_SERVER['HTTP_HOST'] . DIR . "settle_group.php?groupid=" . $groupdetails['group_id'];
  $formarray['action'] = $purl;
  $formarray['rows'][1]['items'] = "|mode|hidden|settle";
  $formarray['rows'][2]['items'] = "|groupid|hidden|" .$groupdetails['group_id'];
  $formarray['rows'][3]['items'] = "||submit|Transactions to settle balances";
  echo create_form_html($formarray);
  unset($formarray);

    $purl = "http://" . $_SERVER['HTTP_HOST'] . DIR . "graph.php?groupid=" . $groupdetails['group_id'];
  $formarray['action'] = $purl;
  $formarray['rows'][2]['items'] = "|groupid|hidden|" .$groupdetails['group_id'];
  $formarray['rows'][3]['items'] = "||submit|Overview Chart (beta)";
  echo create_form_html($formarray);
  unset($formarray);
  
}  

// only show add event if owner
if ($ask_event && array_key_exists(0, $permissions) ) { 
  $purl = "http://" . $_SERVER['HTTP_HOST'] . DIR . "event.php?groupid=" . $groupdetails['group_id'];
  $formarray['action'] = $purl;
  $formarray['rows'][1]['items'] = "|mode|hidden|add";
  $formarray['rows'][2]['items'] = "|groupid|hidden|" .$groupdetails['group_id'];
  $formarray['rows'][3]['items'] = "||submit|Add event";
  echo create_form_html($formarray);
  unset($formarray);

}
    // show group deposit button
    if (array_key_exists(0, $permissions) ) { 
  $purl = "http://" . $_SERVER['HTTP_HOST'] . DIR . "deposit.php?groupid=" . $groupdetails['group_id'];
  $formarray['action'] = $purl;
  $formarray['rows'][1]['items'] = "|mode|hidden|add";
  $formarray['rows'][2]['items'] = "|groupid|hidden|" .$groupdetails['group_id'];
  $formarray['rows'][3]['items'] = "||submit|Make group deposit";
  echo create_form_html($formarray);
  unset($formarray);
    }

// only show add member if permission
if ($ask_add && array_key_exists(0, $permissions) ) { 
  $a = "Add";
  $b = "more members";
  $formarray['action'] = $_SERVER['PHP_SELF'];
  $formarray['rows'][0]['type'] = "select";
  $formarray['rows'][0]['label'] = "Add member(s):";
  $formarray['rows'][0]['name'] = "number";
  //$formarray['rows'][0]['value'] = array(1=>"$a 1 $b",2=>"$a 2 $b",3=>"$a 3 $b",4=>"$a 4 $b",5=>"$a 5 $b",6=>"$a 6 $b",7=>"$a 7 $b",8=>"$a 8 $b",9=>"$a 9 $b",10=>"$a 10 $b");
  $formarray['rows'][0]['value'] = array(1=>1,2=>2,3=>3,4=>4,5=>5,6=>6,7=>7,8=>8,9=>9,10=>10);
  
  $formarray['rows'][1]['items'] = "|mode|hidden|addmembers";
  $formarray['rows'][2]['items'] = "|groupid|hidden|" .$groupdetails['group_id'];
  $formarray['rows'][3]['items'] = "||submit|Go";
  echo create_form_html($formarray);
  unset($formarray);
}

print_footer($user,2, $groupdetails['group_id']);
?>