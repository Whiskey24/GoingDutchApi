<?php
include("inc/common.php");

// check if valid group specified and return group details 
$groupdetails = check_group($_POST, $_GET);

  //$expenselist = get_groupexpenses($groupdetails['group_id'],$uid, "neg");
  $expenselist = get_groupexpenses($groupdetails['group_id']);
  print_expenselist_excel($expenselist,$groupdetails['group_id']);

?>