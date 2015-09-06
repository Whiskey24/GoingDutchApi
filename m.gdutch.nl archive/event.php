<?php
include("inc/common.php");
include("inc/datetime_dropdown.php");

// check if valid group specified and return group details 
$groupdetails = check_group($_POST, $_GET);

// get permisssions for group
if (!$permissions = group_permissions($groupdetails['group_id'], $user->data['user_id'])) {
  fatal_error ("<b>Error:</b> No permissions for this group");
} elseif ( !array_key_exists(0, $permissions) ) {
  fatal_error("<b>Error:</b> No permission to add events to this group");
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
    
    if (!is_valid_event_name($_POST['eventname'])) {
			$errorString[] = "Invalid event name";
		}
    if ($_POST['description'] && !is_valid_event_name($_POST['description'])) {
			$errorString[] = "Invalid description ";
		}
    if (!is_valid_bookdate($timestamp)) {
			$errorString[] = "Invalid bookdate (more than 3 months ago)";  // month limit is set in function is_valid_bookdate
		}
    if ( !array_key_exists($_POST['type_id'], $expense_types) ) {
			$errorString[] = "Invalid expense type! (what are you doing??)";
		}
    for ($i = 0; $i < $members_size; $i++) {
      $member_ids[] = $members[$i]['user_id'];
    }
    $post_members_size = count($_POST['members']);
    for ($i = 0; $i < $post_members_size; $i++) {
      if (!in_array($_POST['members'][$i],$member_ids)) {
        $errorString[] = "Invalid member selected! (what are you doing??)";
      }
    }
    if(!empty($errorString)) {
			$showaddform = true;
		} else {
      // no errors, add event
      
      if (book_event($_POST['eventname'],$_POST['description'],$_POST['type_id'],$timestamp,$user->data['user_id'],$groupdetails['group_id'],$_POST['members'])) {
        //$resultString = "Expense succesfully added";
        $msg = "e1";
      } else {
        //$resultString = "Error: Could not add expense";
        $msg = "e2";
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
/*
$topbar['leftnav'][0]['name'] = "Group";
$topbar['leftnav'][0]['url'] = "http://" . $_SERVER['HTTP_HOST'] . DIR . "group_detail.php?groupid=" . $groupdetails['group_id'];
*/
$back = get_back_page();
$topbar['leftnav'][0]['name'] = $back['name'];
$topbar['leftnav'][0]['url'] =  $back['url'];
print_topbar($topbar);
print_body_start();

if ($showaddform) {

  if ($errorString) {
    print_pageitem_text_html("Please correct the following:", $errorString);
  }

  $formarray['action'] = $_SERVER['PHP_SELF'];
  $formarray['rows'][1]['items'] = "Event name:|eventname|text|" . $_POST['eventname'];
  $formarray['rows'][2]['items'] = "Description:|description|text|" . $_POST['description'];
    if(is_null($_POST['start_minute'])) $_min =  date('i');
    else $_min = $_POST['start_minute'];
    $selecthtml  = createDaysEvent('start_day', $_POST['start_day']);
    $selecthtml .= createMonthsEvent('start_month', $_POST['start_month']);
    //$selecthtml .= createYearsEvent(2010, date('Y'), 'start_year', $_POST['start_year']);
    $selecthtml .= createYearsEvent(date('Y'), date('Y')+1, 'start_year', $_POST['start_year']);
    //$selecthtml .= createHours('start_hour', $_POST['start_hour']);
    //$selecthtml .= createMinutes('start_minute', $_min);
  $formarray['rows'][3]['type'] = "select";
  $formarray['rows'][3]['html'] = $selecthtml;
  $formarray['rows'][4]['type'] = "select";
  $formarray['rows'][4]['label'] = "Default type:";
  $formarray['rows'][4]['name'] = "type_id";
  $formarray['rows'][4]['selected'] = $_POST['type_id'];
  $formarray['rows'][4]['value'] = $expense_types;
  
  $formarray['rows'][5]['items'] = "|groupid|hidden|" . $groupdetails['group_id'];
  $formarray['rows'][6]['items'] = "|mode|hidden|validate";
  $formarray['rows'][7]['items'] = "||submit|Add event";

  $formarray['rows'][8]['type'] = "textrow";
  $formarray['rows'][8]['label'] = "Default participants";
  
  for ($i = 0; $i < $members_size; $i++) {
    /*if (!empty($members[$i]['username'])) $uname = " (" . $members[$i]['username'] . ")";
    else $uname = "";*/
    $uname = format_name($user,$members[$i]['username'],$members[$i]['realname']);
    if (in_array($members[$i]['user_id'], $_POST['members']) || empty($_POST['members'])) $checked = 1;
    else $checked = 0;
    $formarray['rows'][9+$i]['type'] = "checkbox";
    $formarray['rows'][9+$i]['label'] = $uname;
    $formarray['rows'][9+$i]['name'] = "members[]";
    $formarray['rows'][9+$i]['checked'] = $checked;
    $formarray['rows'][9+$i]['value'] =  $members[$i]['user_id']; 
  }     

  echo create_form_html($formarray);
  unset($formarray);  

}
print_footer($user);
?>





