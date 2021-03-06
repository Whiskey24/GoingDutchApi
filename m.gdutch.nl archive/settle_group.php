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
  $mode = "settle";
}

   
   
    $memberlist = get_groupmembers($groupdetails['group_id']);
    $groupmemberids = get_groupmember_ids($memberlist);
    $user_expenses = get_user_expenses($groupmemberids);
    $user_paid_expenses = get_user_paid_expenses($groupmemberids);
    $size = count($memberlist);

    for ($i = 0; $i < $size; $i++) {
      if (!empty($memberlist[$i]['username'])) $uname = " (" . $memberlist[$i]['username'] . ")";
      else $uname = "";

      $uexpense = $user_expenses['users'][$memberlist[$i]['user_id']]['groups'][$groupdetails['group_id']]['group_total'];
      if (isset ($user_paid_expenses['users'][$memberlist[$i]['user_id']]['groups'][$groupdetails['group_id']]['group_total']) )
        $upaid = $user_paid_expenses['users'][$memberlist[$i]['user_id']]['groups'][$groupdetails['group_id']]['group_total'];
      else
        $upaid = 0;
      
      
      $listarray[$i]['link'] = "http://" . $_SERVER['HTTP_HOST'] . DIR . "profile.php?uid=" . $memberlist[$i]['user_id'];
      //$listarray[$i]['name'] = $memberlist[$i]['realname'] . $uname;
      $listarray[$i]['name'] = format_name($user,$memberlist[$i]['username'],$memberlist[$i]['realname']);
        $listarray[$i]['balance'] = $upaid-$uexpense;
      $listarray[$i]['user_id'] = $memberlist[$i]['user_id'];
    }
    
    //print_memberlist_html($listarray, SORT_DESC);
    $a=1;
    $settle_array = close_group_expenses($listarray);  






if ($mode == 'settle') {
  // Start HTML output
  print_header();

  // array structure: $bararray['title'], $bararray['leftnav'][$i][name|url], $bararray['rightnav'][$i][name|url]
  $topbar['title'] = $groupdetails['name'];
  $topbar['leftnav'][0]['name'] = "Back";
  $topbar['leftnav'][0]['url'] = "http://" . $_SERVER['HTTP_HOST'] . DIR . "group_detail.php?groupid=" . $groupdetails['group_id'];
  print_topbar($topbar);
  print_body_start();

   print_pageitem_text_html("These transactions will give every member a zero balance.");

  // even out the balances
  print_settle_group_list_html($settle_array);
    
  // show link to the transactions to settle balances
    if (array_key_exists(0, $permissions) ) {       
      $purl = "http://" . $_SERVER['HTTP_HOST'] . DIR . "settle_group.php?groupid=" . $groupdetails['group_id'];
      $formarray['action'] = $purl;
      $formarray['rows'][1]['items'] = "|mode|hidden|mark";
      $formarray['rows'][2]['items'] = "|groupid|hidden|" .$groupdetails['group_id'];
      $formarray['rows'][3]['items'] = "||submit|Mark these transactions paid";
      echo create_form_html($formarray);
      unset($formarray);
    }
  }

  elseif ($mode == 'mark') {
  // Start HTML output
  print_header();
  print_settle_group_list_form($settle_array, $groupdetails['group_id']);
}
  
  elseif ($mode == 'process') {
    if (!validate_check_arr($groupdetails['group_id'], $_POST['hash']) ) {
      // cannot settle, transactions changed during submit
      // Start HTML output
      print_header();
      fatal_error("<b>Error:</b> Could not settle accounts, transactions occured while processing");
    }
  
    foreach ($settle_array as $key => $value) {
      $pay_array[$value['pay_uid']][] = array("pay_name" => $value['pay_name'], "get_uid" => $value['get_uid'], "get_name" => $value['get_name'], "sum" => $value['sum']);
    }
    $desc = "Account settlement";
    foreach ($_POST['paylist'] as $val) {
      $ids = explode('-', $val);
      foreach ($pay_array[$ids[0]] as $getter) {
        if ( $ids[1] == $getter['get_uid'] ) {
           // echo "id {$ids[0]} ({$getter['pay_name']})  pays id {$ids[1]} ({$getter['get_name']} {$getter['sum']}<br>";
          if (book_expense($desc,$getter['sum'],5,time(),$ids[0],$groupdetails['group_id'],array($ids[1]),0)) {
            //$resultString = "Accounts settled";
            $msg = "s1";
          } else {
            //$resultString = "Errors occured while settling accounts";
            $msg = "s2";
          }
        }
      }
    }
    $redirect = "http://" . $_SERVER['HTTP_HOST'] . DIR . "group_detail.php?groupid=" . $groupdetails['group_id'] . "&msg=$msg";
    header("Location: $redirect");
  }
  
print_footer($user,2,$groupdetails['group_id']);
?>