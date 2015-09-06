<?php

include("inc/common.php");

// check if valid group specified and return group details 
$groupdetails = check_group($_POST, $_GET);

// get permisssions for group
if (!$permissions = group_permissions($groupdetails['group_id'], $user->data['user_id'])) {
  fatal_error("No permissions for this group");
}


if (!array_key_exists(0, $permissions) ) { 
  fatal_error("Only group owner can edit the group.");
}

// get post mode
if (isset($_POST['mode'])) {
	$mode = $_POST['mode'];
} elseif (isset($_GET['mode'])) {
	$mode = $_GET['mode'];
} else {
  $mode = "showgroup";
}

if (isset($_POST['mode'])) {
  	if ($_POST['token'] != $_SESSION['token']) {
  			fatal_error("Wrong token!");
  	}
    $token_age = time() - $_SESSION['token_time'];	// force to resubmit after 5 minutes
		if ($token_age > 300) {
  			fatal_error("Timout value exceeded");
  	}
 }

// get memberuid
if (isset($_POST['uid'])) {
	$memberuid = $_POST['uid'];
} elseif (isset($_GET['uid'])) {
	$memberuid = $_GET['uid'];
} else {
  $memberuid = "";
}


// get message
if (isset($_GET['msg'])) {
	$message = get_msg($_GET['msg']);
}

$memberlist = get_groupmembers($groupdetails['group_id'], false);
$size = count($memberlist);
$groupmemberids = get_groupmember_ids($memberlist);
$user_expenses = get_user_expenses($groupmemberids);
$user_paid_expenses = get_user_paid_expenses($groupmemberids);

for ($i = 0; $i < $size; $i++) {
  if ($memberlist[$i]['user_id'] == $memberuid ) $mname = format_name($user,$memberlist[$i]['username'],$memberlist[$i]['realname']);
}

switch($mode){
	case "edit":
		$editgroup = true;
		break;

    case "delgroup":
      $askusure = true;
      $delgroup = false;
      $ask_add = false;
      $ask_event = false;
      $ask_delmember = false;
      $ask_delgroup = false;
      break;

    case "delmember":
      $askusure = false;
      $delgroup = false;
      $ask_add = false;
      $ask_event = false;
      $ask_delmember = false;
      $ask_delgroup = false;
      $pick_member = true;
     
      break;

    case "membersure":
      $askusure = false;
      $askusuremember = true;
      $delgroup = false;
      $ask_add = false;
      $ask_event = false;
      $ask_delmember = false;
      $ask_delgroup = false;
      $pick_member = false;
     
      break;
      
      
    case "memberpicked":
      $askusure = false;
      $delgroup = false;
      $ask_add = false;
      $ask_event = false;
      $ask_delmember = false;
      $ask_delgroup = false;
      $pick_member = false;


      
      $uexpense = $user_expenses['users'][$memberuid]['groups'][$groupdetails['group_id']]['group_total'];
      $upaid = $user_paid_expenses['users'][$memberuid]['groups'][$groupdetails['group_id']]['group_total'];
      $mbalance = number_format(($upaid-$uexpense),DECIMALS , DSEP,TSEP) ;
      
      if ($mbalance > 0.1 || $mbalance < -0.1) {
       $delmemberstatus = "Only members with a zero balance can be removed from the group (balance of $mname is $mbalance).";
       $pick_member = false;
       include("inc/datetime_dropdown.php");
       $bookexpense = true;
      } else {
        $showgroup = true;
        $ask_add = true;
        $ask_event = true;
        $ask_delmember = true;
        $ask_delgroup = true;
        $show_log = true;
        
        if (remove_member_from_group($groupdetails['group_id'], $memberuid)) $statusmessage = "Member \"$mname\" successfully removed from the group";
        else $statusmessage = "Member \"$mname\" could not be removed from the group";

      }       
      
      break;
      
    case "delgroupreally":
      $delgroup = true;
      $ask_add = false;
      $ask_event = false;
      $ask_delmember = false;
      $ask_delgroup = false;
        
      $candeletegroup = true;
      for ($i = 0; $i < $size; $i++) {
        if (!empty($memberlist[$i]['username'])) $uname = " (" . $memberlist[$i]['username'] . ")";
        else $uname = "";

        $uexpense = $user_expenses['users'][$memberlist[$i]['user_id']]['groups'][$groupdetails['group_id']]['group_total'];
        $upaid = $user_paid_expenses['users'][$memberlist[$i]['user_id']]['groups'][$groupdetails['group_id']]['group_total'];
      
        $listarray[$i]['link'] = "http://" . $_SERVER['HTTP_HOST'] . DIR . "profile.php?uid=" . $memberlist[$i][user_id];
        //$listarray[$i]['name'] = $memberlist[$i]['realname'] . $uname;
        $listarray[$i]['name'] = format_name($user,$memberlist[$i]['username'],$memberlist[$i]['realname']);
        $listarray[$i]['balance'] = number_format(($upaid-$uexpense),DECIMALS , DSEP,TSEP) ;
        if ($upaid-$uexpense > 1 || $upaid-$uexpense < -1 ) {
          $candeletegroup = false;
          $nonzero[] = $listarray[$i]['name'] . " ( " .$listarray[$i]['balance'] . " )" ;
        }
      }
      if ($candeletegroup)
        if (delete_group($groupdetails['group_id']) ) $delresult = "Group \"". $groupdetails['name'] . "\" successfully deleted"; 
        else $delresult =  "Errors occured while trying to delete group \"". $groupdetails['name'] . "\".";
      
      break;
        
        
  case "showgroup":
		$showgroup = true;
		$ask_add = true;
    $ask_event = true;
      $ask_delmember = true;
      $ask_delgroup = true;
      $show_log = true;
    break;

  case "addmembers":
    $showmembers = true;
    $ask_add = false;
    $add_form = true;
    $membersize = intval($_POST['number']);
    if ($membersize > 10) $membersize = 10;
    if ($membersize < 1) $membersize = 1;
    break;
    

    
}

// Start HTML output

print_header();

// array structure: $bararray['title'], $bararray['leftnav'][$i][name|url], $bararray['rightnav'][$i][name|url]
$topbar['title'] = $groupdetails['name'];
$back['name'] = "Back";
if ($candeletegroup) $back['url'] = "profile.php";
else $back['url'] = "group_edit.php?groupid=" . $groupdetails['group_id'];
$topbar['leftnav'][0]['name'] = $back['name'];
$topbar['leftnav'][0]['url'] =  $back['url'];

print_topbar($topbar);

print_body_start();

  if ($message) { 
    print_pagetitle($message);
  }

if ($delgroup && $candeletegroup == false) {
     print_pageitem_text_html("Group cannot be deleted because these members do not have a zero balance:", $nonzero);
     

    for ($i = 0; $i < $size; $i++) {
    if (!empty($memberlist[$i]['username'])) $uname = " (" . $memberlist[$i]['username'] . ")";
    else $uname = "";

    $uexpense = $user_expenses['users'][$memberlist[$i][user_id]]['groups'][$groupdetails['group_id']]['group_total'];
    $upaid = $user_paid_expenses['users'][$memberlist[$i][user_id]]['groups'][$groupdetails['group_id']]['group_total'];
    if ($memberlist[$i][user_id] != $user->data['user_id']) $listarray[$i]['link'] = "http://" . $_SERVER['HTTP_HOST'] . DIR . "group_edit.php?groupid=". $groupdetails['group_id'] . "&uid=" . $memberlist[$i][user_id] . "&mode=membersure";
    else $listarray[$i]['link'] = "";
    //$listarray[$i]['name'] = $memberlist[$i]['realname'] . $uname;
    $listarray[$i]['name'] = format_name($user,$memberlist[$i]['username'],$memberlist[$i]['realname']);
    $listarray[$i]['uid'] = $memberlist[$i][user_id];
    $listarray[$i]['balance'] = number_format(($upaid-$uexpense),DECIMALS , DSEP,TSEP) ;
  } 
  
  
  close_group_expenses($listarray);  
     
}
elseif ($delgroup && $candeletegroup == true) {
     print_pageitem_text_html($delresult);

}

if ($statusmessage) print_pageitem_text_html($statusmessage);


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

// only show add member if permission
if ($ask_add && array_key_exists(0, $permissions) ) {  
  $a = "Add";
  $b = "more members";
  $formarray['action'] = "group_detail.php";
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

// login log
if ($show_log && $size > 1)  {  
  $purl = "http://" . $_SERVER['HTTP_HOST'] . DIR . "login_log.php?groupid=" . $groupdetails['group_id'];
  $formarray['action'] = $purl;
  $formarray['rows'][1]['items'] = "|mode|hidden|loginlog";
  $formarray['rows'][2]['items'] = "|groupid|hidden|" .$groupdetails['group_id'];
  $formarray['rows'][3]['items'] = "||submit|Show login log";
  echo create_form_html($formarray);
  unset($formarray);
}


// delete member
if ($ask_delmember && $size > 1)  {  
  $purl = "http://" . $_SERVER['HTTP_HOST'] . DIR . "group_edit.php?groupid=" . $groupdetails['group_id'];
  $formarray['action'] = $purl;
  $formarray['rows'][1]['items'] = "|mode|hidden|delmember";
  $formarray['rows'][2]['items'] = "|groupid|hidden|" .$groupdetails['group_id'];
  $formarray['rows'][3]['items'] = "||submit|Remove member";
  echo create_form_html($formarray);
  unset($formarray);
}

if ($pick_member)  { 
if ($delmemberstatus != "")  print_pageitem_text_html($delmemberstatus);
  print_pageitem_text_html("Pick a member to remove from the group:");
  for ($i = 0; $i < $size; $i++) {
    if (!empty($memberlist[$i]['username'])) $uname = " (" . $memberlist[$i]['username'] . ")";
    else $uname = "";

    $uexpense = $user_expenses['users'][$memberlist[$i][user_id]]['groups'][$groupdetails['group_id']]['group_total'];
    $upaid = $user_paid_expenses['users'][$memberlist[$i][user_id]]['groups'][$groupdetails['group_id']]['group_total'];
    if ($memberlist[$i][user_id] != $user->data['user_id']) $listarray[$i]['link'] = "http://" . $_SERVER['HTTP_HOST'] . DIR . "group_edit.php?groupid=". $groupdetails['group_id'] . "&uid=" . $memberlist[$i][user_id] . "&mode=membersure";
    else $listarray[$i]['link'] = "";
    //$listarray[$i]['name'] = $memberlist[$i]['realname'] . $uname;
    $listarray[$i]['name'] = format_name($user,$memberlist[$i]['username'],$memberlist[$i]['realname']);
    $listarray[$i]['balance'] = number_format(($upaid-$uexpense),DECIMALS , DSEP,TSEP) ; 
  }  
  print_memberlist_html($listarray, SORT_DESC);
}



// delete group
if ($ask_delgroup)  {  
  $purl = "http://" . $_SERVER['HTTP_HOST'] . DIR . "group_edit.php?groupid=" . $groupdetails['group_id'];
  $formarray['action'] = $purl;
  $formarray['rows'][1]['items'] = "|mode|hidden|delgroup";
  $formarray['rows'][2]['items'] = "|groupid|hidden|" .$groupdetails['group_id'];
  $formarray['rows'][3]['items'] = "||submit|Close group";
  echo create_form_html($formarray);
  unset($formarray);
}

if ($askusure) {
  print_pageitem_text_html("Are you sure you want to delete the group \"" . $groupdetails['name'] . "\"?", $nonzero);
  $purl = "http://" . $_SERVER['HTTP_HOST'] . DIR . "group_edit.php?groupid=" . $groupdetails['group_id'];
  $formarray['action'] = $purl;
  $formarray['rows'][1]['items'] = "|mode|hidden|delgroupreally";
  $formarray['rows'][2]['items'] = "|groupid|hidden|" .$groupdetails['group_id'];
  $formarray['rows'][3]['items'] = "||submit|Yes, close this group";
  echo create_form_html($formarray);
  unset($formarray);
}

if ($askusuremember) {
  print_pageitem_text_html("Are you sure you want to remove the member \"" . $mname . "\"?");
  $purl = "http://" . $_SERVER['HTTP_HOST'] . DIR . "group_edit.php?groupid=" . $groupdetails['group_id'];
  $formarray['action'] = $purl;
  $formarray['rows'][1]['items'] = "|mode|hidden|memberpicked";
  $formarray['rows'][2]['items'] = "|groupid|hidden|" .$groupdetails['group_id'];
  $formarray['rows'][2]['items'] = "|uid|hidden|" .$memberuid;
  $formarray['rows'][3]['items'] = "||submit|Yes, remove this member";
  echo create_form_html($formarray);
  unset($formarray);
}

if ($bookexpense) {
//////
$expense_types = get_expense_types();
if ($delmemberstatus != "")  print_pageitem_text_html($delmemberstatus);
  print_pageitem_text_html("You can book an expense below to even the balance:");

  if ($errorString) {
    print_pageitem_text_html("Please correct the following:", $errorString);
  }

  if ( array_key_exists(4, $permissions) ) {
    // user is allowed to enter expenses for others
    for ($i = 0; $i < $size; $i++) {
      /*if (!empty($memberlist[$i]['username'])) $uname = " (" . $memberlist[$i]['username'] . ")";
      else $uname = "";
      $gmembers[$memberlist[$i]['user_id']] = $memberlist[$i]['realname'] . $uname;*/
      
      if ($memberlist[$i]['user_id'] == $memberuid) {
        $gmembers[$memberlist[$i]['user_id']] = format_name($user,$memberlist[$i]['username'],$memberlist[$i]['realname']);
      }
    }
    if (isset($_POST['expense_owner'])) $selected = $_POST['expense_owner'];
    else $selected = $memberuid;
    
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
  
  $formarray['action'] = "book.php";
  $formarray['rows'][2]['items'] = "Description:|description|text|" . $_POST['description'];
  $formarray['rows'][3]['items'] = "Amount:|amount|text|" . abs($mbalance);
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
  $formarray['rows'][8]['items'] = "|redirect|hidden|group_edit.php?groupid=1&mode=delmember";
  $formarray['rows'][9]['items'] = "||submit|Add expense";

  for ($i = 0; $i < $size; $i++) {
  
    if ($memberlist[$i]['user_id'] != $memberuid) {
      /*if (!empty($memberlist[$i]['username'])) $uname = " (" . $memberlist[$i]['username'] . ")";
      else $uname = "";*/
      $uname = format_name($user,$memberlist[$i]['username'],$memberlist[$i]['realname']);
      if (in_array($memberlist[$i]['user_id'], $_POST['members']) || empty($_POST['members'])) $checked = 1;
      else $checked = 0;
      $formarray['rows'][10+$i]['type'] = "checkbox";
      $formarray['rows'][10+$i]['label'] = $uname;
      $formarray['rows'][10+$i]['name'] = "members[]";
      $formarray['rows'][10+$i]['checked'] = $checked;
      $formarray['rows'][10+$i]['value'] =  $memberlist[$i]['user_id']; 
    }
  }     

  echo create_form_html($formarray);
  unset($formarray);  






/////
}


print_footer($user,2);
?>