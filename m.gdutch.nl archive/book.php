<?php
include("inc/common.php");
include("inc/datetime_dropdown.php");
include("inc/achievements.class.php");

// check if valid group specified and return group details 
$groupdetails = check_group($_POST, $_GET);

// get permisssions for group
if (!$permissions = group_permissions($groupdetails['group_id'], $user->data['user_id'])) {
  fatal_error ("<b>Error:</b> No permissions for this group");
} elseif ( !(array_key_exists(3, $permissions) || array_key_exists(4, $permissions)) ) {
  fatal_error("<b>Error:</b> No permission to add expenses to this group");
}

// get post mode
if (isset($_POST['mode'])) {
	$mode = $_POST['mode'];
} else {
	$mode = "add";
}

$expense_types = get_expense_types();
$members = get_groupmembers($groupdetails['group_id']);
$members_size = count($members);

switch($mode){
	case "add":
		//$user = new uFlex();
		$showaddform = true;    
		break;

  case "validate":

    $timestamp = mktime($_POST['start_hour'],$_POST['start_minute'],0,$_POST['start_month'],$_POST['start_day'],$_POST['start_year']);
    $errorString = "";
  	if ($_POST['token'] != $_SESSION['token']) {
  			$errorString[] = "Invalid token! (what are you doing??)<br />";
  	}
    $token_age = time() - $_SESSION['token_time'];	// force to resubmit after 5 minutes
		if ($token_age > 300) {
  			$errorString[] = "Timout value exceeded, resubmit<br />";
  	}
  	
    if (!is_valid_real_name($_POST['description']) && $_POST['event_id'] == 0) {
			$errorString[] = "Invalid description <br />";
		}
    if (!is_valid_amount($_POST['amount'])) {
			$errorString[] = "Invalid amount <br />";
		}
    if (!is_valid_bookdate($timestamp)) {
			$errorString[] = "Invalid bookdate (more than 3 months ago)<br />";  // month limit is set in function is_valid_bookdate
		}
    if ( !array_key_exists($_POST['type_id'], $expense_types) ) {
			$errorString[] = "Invalid expense type! (what are you doing??) <br />";
		}
    for ($i = 0; $i < $members_size; $i++) {
      $member_ids[] = $members[$i]['user_id'];
    }
    $post_members_size = count($_POST['members']);
    for ($i = 0; $i < $post_members_size; $i++) {
      if (!in_array($_POST['members'][$i],$member_ids)) {
        $errorString[] = "Invalid member selected! (what are you doing??) <br />";
      }
    }
    if ($_POST['expense_owner']) {
      if (!in_array($_POST['expense_owner'],$member_ids)) {
          $errorString[] = "Invalid expense owner selected! (what are you doing??) <br />";
      }
    }
    if(!empty($errorString)) {
			$showaddform = true;
		} else {

      // no errors, add expense
      if ($_POST['expense_owner']) $xuid = $_POST['expense_owner'];
      else $xuid = $user->data['user_id'];
      
      
      
      if (book_expense($_POST['description'],$_POST['amount'],$_POST['type_id'],$timestamp,$xuid,$groupdetails['group_id'],$_POST['members'],$_POST['event_id'])) {
        //$resultString = "Expense succesfully added";
        // update achievements
        $ac = new Achievements($groupdetails['group_id']);
        $msg = "b1";
        mail_expense($_POST['description'],$_POST['amount'],$_POST['type_id'],$timestamp,$xuid,$groupdetails['group_id'],$_POST['members'],$_POST['event_id']);
      } else {
        //$resultString = "Error: Could not add expense";
        $msg = "b2";
      }
      if ($_POST['redirect'] != "") {
        $redirect = "http://" . $_SERVER['HTTP_HOST'] . DIR . $_POST['redirect'] . "&msg=$msg";
        header("Location: $redirect");
        exit;
      }
    }
    break;
}

if ($msg) { 
  $redirect = "http://" . $_SERVER['HTTP_HOST'] . DIR . "group_detail.php?groupid=" . $groupdetails['group_id'] . "&msg=$msg";
  header("Location: $redirect");
}

print_header(); 
$topbar['title'] = $groupdetails['name'];
$topbar['leftnav'][0]['name'] = "Group";
$topbar['leftnav'][0]['url'] = "http://" . $_SERVER['HTTP_HOST'] . DIR . "group_detail.php?groupid=" . $groupdetails['group_id'];
print_topbar($topbar);
print_body_start();

if ($showaddform) {

  if ($errorString) {
    print_pageitem_text_html("Please correct the following:", $errorString);
  }

  if ( array_key_exists(4, $permissions) ) {
    // user is allowed to enter expenses for others
    for ($i = 0; $i < $members_size; $i++) {
      /*if (!empty($members[$i]['username'])) $uname = " (" . $members[$i]['username'] . ")";
      else $uname = "";
      $gmembers[$members[$i]['user_id']] = $members[$i]['realname'] . $uname;*/
      $gmembers[$members[$i]['user_id']] = format_name($user,$members[$i]['username'],$members[$i]['realname']);

    }
    if (isset($_POST['expense_owner'])) $selected = $_POST['expense_owner'];
    else $selected = $user->data['user_id'];
    
    $formarray['rows'][0]['type'] = "select";
    $formarray['rows'][0]['label'] = "Expense by: ";
    $formarray['rows'][0]['name'] = "expense_owner";
    $formarray['rows'][0]['selected'] = $selected;
    $formarray['rows'][0]['value'] = $gmembers;
  }

  if (!$event = get_recent_event($groupdetails['group_id'], true) ) {
  
    $formarray['rows'][1]['type'] = "select";
    $formarray['rows'][1]['label'] = "Event:";
    $formarray['rows'][1]['name'] = "event_id";
    $formarray['rows'][1]['selected'] = $_POST['event_id'];
    $formarray['rows'][1]['value'] = array(0=>"No event found");
  
  } else {
    // event found
    //if (isset($_POST['event_id'])) $eselected = $_POST['event_id'];
    //else $eselected = $event['event_id'];
    
    
     // event found
    ksort($event);
    $event[0] = "No event"; 
    // reverse array while maintaining actual keys
    // http://nl.php.net/manual/en/function.array-reverse.php
    end($event);
    do {
      $part1=key($event);
      $part2=current($event);
      $event2[$part1]=$part2;
    } while(prev($event)); 
    $event = $event2;

    if (isset($_POST['event_id'])) $eselected = $_POST['event_id'];
    else $eselected = $details['event_id'];
    
    
    $formarray['rows'][1]['type'] = "select";
    $formarray['rows'][1]['label'] = "Event:";
    $formarray['rows'][1]['name'] = "event_id";
    $formarray['rows'][1]['selected'] = $eselected;
    $formarray['rows'][1]['value'] = $event;
    
    if (!isset($_POST['type_id'])) $_POST['type_id'] = $event['expense_type'];
    if (!isset($_POST['members'])) $_POST['members'] = $event['member_ids'];
    
  }
  
  $formarray['action'] = $_SERVER['PHP_SELF'];
  $formarray['rows'][2]['items'] = "Description:|description|text|" . $_POST['description'];
  $formarray['rows'][3]['items'] = "Amount:|amount|text|" . $_POST['amount'];;
  $formarray['rows'][4]['type'] = "select";
  $formarray['rows'][4]['label'] = "Type:";
  $formarray['rows'][4]['name'] = "type_id";
  $formarray['rows'][4]['selected'] = $_POST['type_id'];
  $formarray['rows'][4]['value'] = $expense_types;
    if(is_null($_POST['start_minute'])) $_min =  date('i');
    else $_min = $_POST['start_minute'];
    $selecthtml  = createDays('start_day', $_POST['start_day']);
    $selecthtml .= createMonths('start_month', $_POST['start_month']);
    $selecthtml .= createYears(2010, date('Y'), 'start_year', $_POST['start_year']);
    $selecthtml .= createHours('start_hour', $_POST['start_hour']);
    $selecthtml .= createMinutes('start_minute', $_min);
  $formarray['rows'][5]['type'] = "select";
  $formarray['rows'][5]['html'] = $selecthtml;
  $formarray['rows'][6]['items'] = "|groupid|hidden|" . $groupdetails['group_id'];
  $formarray['rows'][7]['items'] = "|mode|hidden|validate";
  $formarray['rows'][8]['items'] = "|redirect|hidden|" . $_POST['redirect'];
  $formarray['rows'][9]['items'] = "||submit|Add expense";

  for ($i = 0; $i < $members_size; $i++) {
    /*if (!empty($members[$i]['username'])) $uname = " (" . $members[$i]['username'] . ")";
    else $uname = "";*/
    $uname = format_name($user,$members[$i]['username'],$members[$i]['realname']);
    if (empty($_POST['members']) || in_array($members[$i]['user_id'], $_POST['members'])) $checked = 1;
    else $checked = 0;
    $formarray['rows'][10+$i]['type'] = "checkbox";
    $formarray['rows'][10+$i]['label'] = $uname;
    $formarray['rows'][10+$i]['name'] = "members[]";
    $formarray['rows'][10+$i]['checked'] = $checked;
    $formarray['rows'][10+$i]['value'] =  $members[$i]['user_id']; 
  }     

  echo create_form_html($formarray);
  unset($formarray);  

}


print_footer($user);
?>
