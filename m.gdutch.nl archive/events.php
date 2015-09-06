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
  $mode = "listevents";
}

// get message
if (isset($_GET['msg'])) {
	$message = get_msg($_GET['msg']);
}

switch($mode){
	case "listevents":
		$listevents = true;
    break;

}

// Start HTML output

print_header();
$back = get_back_page();
$topbar['leftnav'][0]['name'] = $back['name'];
$topbar['leftnav'][0]['url'] =  $back['url'];

$topbar['title'] = $groupdetails['name'];

/*$topbar['rightnav'][0]['name'] = "Edit";
$topbar['rightnav'][0]['url'] =  $_SERVER['PHP_SELF']. "?groupid=" . $groupdetails['group_id'] . "&mode=edit"; 
*/
print_topbar($topbar);
print_body_start();

if ($listevents && array_key_exists(5, $permissions) ) {
  if ($message) { 
    print_pagetitle($message);
  }

  // only show add event if owner
  if (array_key_exists(0, $permissions) ) { 
    $purl = "http://" . $_SERVER['HTTP_HOST'] . DIR . "event.php?groupid=" . $groupdetails['group_id'];
    $formarray['action'] = $purl;
    $formarray['rows'][1]['items'] = "|mode|hidden|add";
    $formarray['rows'][2]['items'] = "|groupid|hidden|" .$groupdetails['group_id'];
    $formarray['rows'][3]['items'] = "||submit|Add event";
    echo create_form_html($formarray);
  unset($formarray);

}  
  
  // Upcoming event
/*          $event[$row['event_id']]['event_id'] = $row['event_id'];
        $event[$row['event_id']]['event_name'] = $row['event_name'];
        $event[$row['event_id']]['description'] = $row['event_description'];
        $event[$row['event_id']]['organizer'] = $row['organizer_id'];
        $event[$row['event_id']]['date'] = date("j M Y", $row['date']);
        $event[$row['event_id']]['expense_type'] = $row['expense_type_id']; */
  $events = get_all_events($groupdetails['group_id']);
 if ($events) print_all_events_html($events);
  
}  


print_footer($user,2);
?>