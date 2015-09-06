<?php
include("inc/common.php");


// check if valid group specified and return group details 
$groupdetails = check_group($_POST, $_GET);

// get permisssions for group
if (!$permissions = group_permissions($groupdetails['group_id'], $user->data['user_id'])) {
  fatal_error("No permissions for this group");
}



// Start HTML output

print_header();

// array structure: $bararray['title'], $bararray['leftnav'][$i][name|url], $bararray['rightnav'][$i][name|url]
$topbar['title'] = $groupdetails['name'];
$topbar['leftnav'][0]['name'] = "Back";
$topbar['leftnav'][0]['url'] = "http://" . $_SERVER['HTTP_HOST'] . DIR . "group_detail.php?groupid=" . $groupdetails['group_id'];
print_topbar($topbar);
print_body_start();

 print_pageitem_text_html("Login log for " . $groupdetails['name'] . ":");
 
 
  $memberlist = get_groupmembers($groupdetails['group_id']);
  $groupmemberids = get_groupmember_ids($memberlist);
  $user_expenses = get_user_expenses($groupmemberids);
  $user_paid_expenses = get_user_paid_expenses($groupmemberids);
  $size = count($memberlist);
    
  for ($i = 0; $i < $size; $i++) {
    if (!empty($memberlist[$i]['username'])) $uname = " (" . $memberlist[$i]['username'] . ")";
    else $uname = "";

    $uexpense = $user_expenses['users'][$memberlist[$i][user_id]]['groups'][$groupdetails['group_id']]['group_total'];
    $upaid = $user_paid_expenses['users'][$memberlist[$i][user_id]]['groups'][$groupdetails['group_id']]['group_total'];
  
    $listarray[$i]['link'] = "http://" . $_SERVER['HTTP_HOST'] . DIR . "profile.php?uid=" . $memberlist[$i][user_id];
    //$listarray[$i]['name'] = $memberlist[$i]['realname'] . $uname;
    $listarray[$i]['name'] = format_name($user,$memberlist[$i]['username'],$memberlist[$i]['realname']);
    $listarray[$i]['balance'] = number_format(($upaid-$uexpense),DECIMALS , DSEP,TSEP) ;
    $listarray[$i]['user_id'] = $memberlist[$i]['user_id'];
    $listarray[$i]['lastlogin'] = $memberlist[$i]['lastlogin'];
    $listarray[$i]['lastloginunix'] = $memberlist[$i]['lastloginunix'];
  }
  
    print_loginlog_html($listarray, SORT_DESC);
 

  

print_footer($user,2);
?>