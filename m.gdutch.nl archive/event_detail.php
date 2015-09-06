<?php
include("inc/common.php");
include("inc/datetime_dropdown.php");
include("inc/achievements.class.php");

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
if (isset($_POST['eventid'])) {
	$eventid = $_POST['eventid'];
} elseif (isset($_GET['eventid'])) {
	$eventid = $_GET['eventid'];
} else {
  fatal_error("No event specified");
}

// Get group for expense
$groupid = get_groupid_by_eventid($eventid);

// get permisssions for group
$permissions = group_permissions($groupid, $user->data['user_id']);
if (!$permissions || !array_key_exists(5, $permissions)) {
  fatal_error("No permissions for this event");
}

// get message
if (isset($_GET['msg'])) {
	$message = get_msg($_GET['msg']);
}

$details = get_eventdetails($eventid);
$expense_types = get_expense_types();
$members = get_groupmembers($groupid);
$members_size = count($members);

foreach($details['members'] as $key => $value) {
  $emembers[] = $key;
}


switch($mode){
	case "show":
		$show = true;
		break;

  case "edit":
   if ( array_key_exists(4, $permissions) || $details['organizerid'] == $user->data['user_id']) {
		$showmembers = true;
		$edit = true;
    } else {
      fatal_error("No permission to edit this event");
    }
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
      if (update_expense($expid, $_POST['description'],$_POST['amount'],$_POST['type_id'],$timestamp,$xuid,$groupid,$_POST['members'],$_POST['event_id'], $emembers)) {
        //$resultString = "Expense succesfully added";
        $msg = "x1";
        $ac = new Achievements($details['groupid']);
      } else {
        //$resultString = "Error: Could not add expense";
        $msg = "x2";
      }
    }
    $redirect = "http://" . $_SERVER['HTTP_HOST'] . DIR . "expense_detail.php?expid=$expid&msg=$msg";
    header("Location: $redirect");
    break;    
    
}



print_header();
// array structure: $bararray['title'], $bararray['leftnav'][$i][name|url], $bararray['rightnav'][$i][name|url]
$urll = $_SERVER['PHP_SELF'] . "?eventid=$eventid&mode=edit";
$topbar['title'] = $groupdetails['name'];
/*
$topbar['leftnav'][0]['name'] = "List";
$topbar['leftnav'][0]['url'] = $_SESSION['back'];*/
$back = get_back_page();
$topbar['leftnav'][0]['name'] = $back['name'];
$topbar['leftnav'][0]['url'] =  $back['url'];

/*
if ( (array_key_exists(4, $permissions) || $details['organizerid'] == $user->data['user_id']) && $mode != "edit") {
$topbar['rightnav'][0]['name'] = "Edit";
$topbar['rightnav'][0]['url'] = $urll;
}*/
print_topbar($topbar);
print_body_start();



if ($show) {
    if ($message) { 
    print_pagetitle($message);
  }
  print_eventdetails_html ($details,$user);
}

if ($edit) {

  if ( array_key_exists(4, $permissions) ) {
    // user is allowed to enter expenses for others
    for ($i = 0; $i < $members_size; $i++) {
      /*if (!empty($members[$i]['username'])) $uname = " (" . $members[$i]['username'] . ")";
      else $uname = "";
      $gmembers[$members[$i]['user_id']] = $members[$i]['realname'] . $uname;*/
      $gmembers[$members[$i]['user_id']] = format_name($user,$members[$i]['username'],$members[$i]['realname']);

    }
    if (isset($_POST['expense_owner'])) $selected = $_POST['expense_owner'];
    else $selected = $details['ownerid'];
    
    $formarray['rows'][0]['type'] = "select";
    $formarray['rows'][0]['label'] = "Expense by: ";
    $formarray['rows'][0]['name'] = "expense_owner";
    $formarray['rows'][0]['selected'] = $selected;
    $formarray['rows'][0]['value'] = $gmembers;
  }

  if (!$event = get_all_events($groupid,true) ) {
  
    $formarray['rows'][1]['type'] = "select";
    $formarray['rows'][1]['label'] = "Current event:";
    $formarray['rows'][1]['name'] = "event_id";
    $formarray['rows'][1]['selected'] = $_POST['event_id'];
    $formarray['rows'][1]['value'] = array(0=>"No event found");
  
  } else {

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
    else $eselected = $details['eventid'];
    
    
    $formarray['rows'][1]['type'] = "select";
    $formarray['rows'][1]['label'] = "Current event:";
    $formarray['rows'][1]['name'] = "event_id";
    $formarray['rows'][1]['selected'] = $eselected;
    $formarray['rows'][1]['value'] = $event;
    
    if (!isset($_POST['type_id'])) $_POST['type_id'] = $event['expense_type'];
    if (!isset($_POST['members'])) $_POST['members'] = $event['member_ids'];
    
  }
  
  $formarray['action'] = $_SERVER['PHP_SELF'];
  if (!isset($_POST['description'])) $_POST['description'] = $details['description'];
  $formarray['rows'][2]['items'] = "Description:|description|text|" . $_POST['description'];
  if (!isset($_POST['amount'])) $_POST['amount'] = $details['amount'];
  $formarray['rows'][3]['items'] = "Amount:|amount|text|" . $_POST['amount'];
  if (!isset($_POST['type_id'])) $_POST['type_id'] = $details['typeid'];
  $formarray['rows'][4]['type'] = "select";
  $formarray['rows'][4]['label'] = "Type:";
  $formarray['rows'][4]['name'] = "type_id";
  $formarray['rows'][4]['selected'] = $_POST['type_id'];
  $formarray['rows'][4]['value'] = $expense_types;
  
  if (!isset($_POST['start_day'])) $_POST['start_day'] = date('j',$details['expense_date_unix']);
  if (!isset($_POST['start_month'])) $_POST['start_month'] = date('n',$details['expense_date_unix']);
  if (!isset($_POST['start_year'])) $_POST['start_year'] = date('Y',$details['expense_date_unix']);
  if (!isset($_POST['start_hour'])) $_POST['start_hour'] = date('H',$details['expense_date_unix']);
    if(is_null($_POST['start_minute'])) $_min =  date('i',$details['expense_date_unix']);
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
  $formarray['rows'][8]['items'] = "|expid|hidden|$expid";
  $formarray['rows'][9]['items'] = "||submit|Update expense";


  if (!isset($_POST['members'])) $_POST['members'] = $emembers;
  for ($i = 0; $i < $members_size; $i++) {
    /*if (!empty($members[$i]['username'])) $uname = " (" . $members[$i]['username'] . ")";
    else $uname = "";*/
    $uname = format_name($user,$members[$i]['username'],$members[$i]['realname']);

    if (in_array($members[$i]['user_id'], $_POST['members']) || empty($_POST['members'])) $checked = 1;
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

print_footer($user,7);
?>