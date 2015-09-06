<?php
include("inc/common.php");

$grouplist = get_groups($user->data['user_id']);
$size = count($grouplist);

if ($size == 1) {
  $groupurl = "http://" . $_SERVER['HTTP_HOST'] . DIR . "group_detail.php?groupid=" . $grouplist[0]['group_id'];
  header("Location: $groupurl");
} elseif ( $ngroupid = get_last_active_group ($user->data['user_id']) ) {
  $groupurl = "http://" . $_SERVER['HTTP_HOST'] . DIR . "group_detail.php?groupid=" . $ngroupid;
  header("Location: $groupurl");
}

print_header();
print_body_start();



$table  = "    <table>\n";
for ($i = 0; $i < $size; $i++) {
	// get permisssions for each group
	if (!$permissions = group_permissions($grouplist[$i]['group_id'], $user->data['user_id'])) {
    echo "    <p><b>Error:</b> No permissions for this group</p>";
    print_footer($user);
    exit;
  } elseif ( array_key_exists(3, $permissions) || array_key_exists(4, $permissions) ) {
    $addexpense = true;
    $table .= "      <tr>";
    $table .= "<td><a href=\"http://" . $_SERVER['HTTP_HOST'] . DIR . "book.php?groupid=" . $grouplist[$i]['group_id'] . "\">Book expense for group " . $grouplist[$i]['group_name'] . "</a></td>";
    $table .= "</tr>\n";
  }  
}
$table .= "    </table>\n";

if ($addexpense) {
  echo $table;
}
if ($size == 0) {
  	$text = "<a href=\"http://" . $_SERVER['HTTP_HOST'] . DIR . "group.php?mode=add\">create one now!</a>";
    print_pageitem_text_html("No groups found", $text);
} else {
  echo "    <p>\n    <a href=\"http://" . $_SERVER['HTTP_HOST'] . DIR . "group.php\">Your groups</a><br /> ";
}

print_footer($user);


/* TODO

- add balance statement 
- add pay option
- add select role when inviting group members
- add option to delete group
- add option to leave group
- add option to email new group invites
- use existing email addresses to directly link to existing users
*/
?>

