<?php

function selfURL() {
  // get complete url for current page
  // http://www.weberdev.com/get_example-4291.html
  $s = empty($_SERVER["HTTPS"]) ? '' : ($_SERVER["HTTPS"] == "on") ? "s" : "";
  $protocol = strleft(strtolower($_SERVER["SERVER_PROTOCOL"]), "/") . $s;
  $port = ($_SERVER["SERVER_PORT"] == "80") ? "" : (":" . $_SERVER["SERVER_PORT"]);
  return $protocol . "://" . $_SERVER['SERVER_NAME'] . $port . $_SERVER['REQUEST_URI'];
}

function strleft($s1, $s2) {
  // used for selfURL()
  return substr($s1, 0, strpos($s1, $s2));
}

function is_valid_name($username) {
  // http://stackoverflow.com/questions/1330693/php-validate-username-as-alphanumeric-with-underscores
  // Enter other valid characters below
  $valid_chars = "-_+\.@";
  $min_length = 3;
  $max_length = 25;
  if (!preg_match("/^[A-Za-z0-9$valid_chars]+$/", $username) || strlen($username) < $min_length || strlen($username) > $max_length) {
    return false;
  }
  return true;
}

function is_valid_real_name($username, $max_length=35) {
  // http://stackoverflow.com/questions/1330693/php-validate-username-as-alphanumeric-with-underscores
  // Enter other valid characters below
  $valid_chars = "-_+\.@' \/";
  $min_length = 3;
  //$max_length = 35;
  if (!preg_match("/^[A-Za-z0-9$valid_chars]+$/", $username) || strlen($username) < $min_length || strlen($username) > $max_length) {
    return false;
  }
  return true;
}

function is_valid_event_name($username, $max_length=35) {
  // http://stackoverflow.com/questions/1330693/php-validate-username-as-alphanumeric-with-underscores
  // Enter other valid characters below
  $valid_chars = "-_+\.@': ";
  $min_length = 3;
  //$max_length = 35;
  if (!preg_match("/^[A-Za-z0-9$valid_chars]+$/", $username) || strlen($username) < $min_length || strlen($username) > $max_length) {
    return false;
  }
  return true;
}

function is_valid_password($password1, $password2) {
  // http://stackoverflow.com/questions/1330693/php-validate-username-as-alphanumeric-with-underscores
  // Enter other valid characters below
  $valid_chars = "-_+\.@";
  $min_length = 6;
  $max_length = 25;
  if ($password1 != $password2 || strlen($password1) < $min_length || strlen($password1) > $max_length) {
    return false;
  }
  return true;
}

function is_valid_amount($amount) {
  // http://stackoverflow.com/questions/1330693/php-validate-username-as-alphanumeric-with-underscores
  // Enter other valid characters below
  $valid_chars = "-_+\.@ ";
  $min_amount = 1;
  $max_amount = 500 ;
  if (!preg_match("/(^[0-9]{1,5}$)|(^[0-9]{1,5}[.,][0-9]{2,2}$)/", $amount) || intval($amount) < $min_amount || intval($amount) > $max_amount) {
    return false;
  }
  return true;
}

function is_valid_group($group_id) {
  $sql = "SELECT id FROM groups WHERE id = $group_id";
  $result = mysql_query($sql);
  if (mysql_num_rows($result) == 1) {
    return true;
  }
  return false;
}

function is_curr_password($password, $user) {
  $uid = $user->data['user_id'];
  $hash = $user->hash_pass($password);
  $sql = "SELECT user_id FROM users WHERE user_id = $uid AND password = '$hash'";
  $result = mysql_query($sql);
  if (mysql_num_rows($result) == 1) {
    return true;
  }
  return false;
}

function username_exists($username) {
  $sql = "SELECT user_id FROM users WHERE username = '$username'";
  $result = mysql_query($sql);
  if (mysql_num_rows($result) == 1) {
    return true;
  }
  return false;
}

function realname_exists($realname) {
  $sql = "SELECT user_id FROM users WHERE realname = '$realname'";
  $result = mysql_query($sql);
  if (mysql_num_rows($result) > 0) {
    return true;
  }
  return false;
}

function email_exists($email, $activated=false) {
  // checks if email exists
  if ($activated)
    $and = " AND activated = 1";
  $sql = "SELECT user_id FROM users WHERE email = '$email' $and";
  $result = mysql_query($sql);
  if (mysql_num_rows($result) == 1) {
    return true;
  }
  return false;
}

function regcode_exists($regcode) {
  // checks if email exists
  $code = strip_codespaces($regcode);
  $monthback = mktime(0, 0, 0, date("m")  , date("d")-21, date("Y"));
  $monthbacksql = date('YmdHis',$monthback);
  $sql = "SELECT * FROM register WHERE code = '{$code}' AND timestamp > '{$monthbacksql}' ";
  $result = mysql_query($sql);
  if (mysql_num_rows($result) == 1) {
    return true;
  }
  return false;
}

function strip_codespaces ($code){
  $code = str_replace(' ', '', $code);
  return $code;
}

function get_userid_by_email($email) {
  // checks if email exists and returns corresponding userid if true
  $sql = "SELECT user_id FROM users WHERE email = '$email'";
  $result = mysql_query($sql);
  if (mysql_num_rows($result) == 1) {
    return mysql_result($result, 0);
  }
  return false;
}

function get_userid_by_code($code) {
  // checks if email exists and returns corresponding userid if true
  $sql = "SELECT userid FROM register WHERE code = '$code'";
  $result = mysql_query($sql);
  if (mysql_num_rows($result) == 1) {
    return mysql_result($result, 0);
  }
  return false;
}

function update_user_by_email($details, $user) {
  $uid = get_userid_by_email($details['email']);
  $hash = $user->hash_pass($details['password']);
  $sql = "UPDATE users SET username = '" . $details['username'] . "', realname = '" . $details['realname'] . "',
          password = '$hash', activated = '1', reg_date = '" . time() . "' 
          WHERE user_id = $uid LIMIT 1";
  $result = mysql_query($sql);
  if ($result) {
    $groups = get_groups($uid);
    return $groups;
  }
  return false;
}

function update_user_by_code($details, $user) {
  $details['code'] = strip_codespaces($details['code']);
  $uid = get_userid_by_code($details['code']);
  $hash = $user->hash_pass($details['password']);
  $sql = "UPDATE users SET username = '" . $details['username'] . "', realname = '" . $details['realname'] . "',
          password = '$hash',  email = '" . $details['email'] . "', activated = '1', reg_date = '" . time() . "' 
          WHERE user_id = $uid LIMIT 1";
  $result = mysql_query($sql);
  if ($result) {
    cleanup_register($details['code'] );
    $groups = get_groups($uid);
    return $groups;
  }
  return false;
}

function reset_pass($email, $user){
   $newpass= get_random_string();
   $hash = $user->hash_pass($newpass);
    $sql = "UPDATE users SET password = '$hash' WHERE email = '{$email}' LIMIT 1";
   $result = mysql_query($sql);
    if ($result) 
      return $newpass;
    else 
      return false;
}

function cleanup_register($code){
   $sql = "DELETE FROM register WHERE code = '$code'";
   $result = mysql_query($sql);
}

function update_user_profile($user, $update) {
  $_SESSION['uFlex']['update'] = true;
  $uid = $user->data['user_id'];
  if (isset($update['realname']))
    $q[] = "realname = '" . $update['realname'] . "'";
  if (isset($update['email']))
    $q[] = "email = '" . $update['email'] . "'";
  if (isset($update['password'])) {
    $hash = $user->hash_pass($update['password']);
    $q[] = "password = '" . $hash . "'";
  }

  $sql = "UPDATE users SET ";
  foreach ($q as $key => $value) {
    $sql .= $value . ", ";
  }
  $sql = rtrim($sql, ", ");
  $sql .= " WHERE user_id = $uid LIMIT 1";
  $result = mysql_query($sql);
  unset($q);
  if ($result) {
    if (isset($update['name_format']))
      $q[] = "name_format = '" . $update['name_format'] . "'";
    if (isset($update['email_notify']))
      $q[] = "email_notify = '" . $update['email_notify'] . "'";
    $sql = "REPLACE INTO preferences SET user_id = $uid, ";
    foreach ($q as $key => $value) {
      $sql .= $value . ", ";
    }
    $sql = rtrim($sql, ", ");
    //echo $sql;
    $result = mysql_query($sql);
    if ($result) {
      return true;
    }
  }
  return false;
}

function output_send() {
  if (!headers_sent() && error_get_last() == NULL) {
    return false;
  }
  return true;
}

function check_output($file, $line) {
  if (!headers_sent() && error_get_last() == NULL) {
    // echo "\n<br>NO output send<br>\n"; 
    return;
  } else {
    echo "\n<br><b>OUTPUT send</b> in " . $file . ", line " . $line . "<br>\n";
  }
}

function print_user($user) {
  echo "<pre>\n\n";
  print_r($user->data);
  echo "</pre> \n\n";
}

function print_log($user) {
  echo "<pre>\n\n";
  print_r($user->report());
  echo "</pre> \n\n";
}

function group_exists($name) {
  $sql = "SELECT group_id FROM groups WHERE name = '$name'";
  $result = mysql_query($sql);
  if (mysql_num_rows($result) == 1) {
    return true;
  }
  return false;
}

function add_group($name, $desc, $userid) {
  $sql = "INSERT INTO `goingdutch`.`groups` (`name` , `description` , `reg_date`)
          VALUES ('" . mysql_real_escape_string($name) . "', '" . mysql_real_escape_string($desc) . "', CURRENT_TIMESTAMP )";
  if (!$result = mysql_query($sql)) {
    return false;
  } else {
    $groupid = mysql_insert_id();
    $sql = "INSERT INTO `goingdutch`.`users_groups` (`user_id` , `group_id` , `role_id` , `join_date`)
            VALUES ('$userid', '$groupid', '0', CURRENT_TIMESTAMP)";
    if (!$result = mysql_query($sql)) {
      return false;
    }
  }
  return true;
}

function get_groups($userid, $onlyids = false) {
  // get the groups for a member
  // group_id - group_name - role - join_datemember_count
  $sql = "SELECT users_groups.group_id AS gid, groups.name, shortname as role, UNIX_TIMESTAMP(join_date) as join_date, groups.description, 
            (SELECT COUNT(*) FROM users_groups WHERE group_id = gid) as member_count
          FROM `users_groups`, groups, roles 
          WHERE users_groups.group_id = groups.group_id and users_groups.role_id = roles.role_id 
          AND user_id = $userid ORDER BY groups.name ASC";

  if (!$result = mysql_query($sql)) {
    return false;
  } else {
    $c = 0;
    while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
      if ($onlyids) {
        $groups[] = $row['gid'];
      } else {
        $groups[$c]['group_id'] = $row['gid'];
        $groups[$c]['group_name'] = $row['name'];
        $groups[$c]['role'] = $row['role'];
        //$groups[$c]['join_date'] = date("F j, Y, g:i a", $row['join_date']);
        $groups[$c]['join_date'] = date("j M Y", $row['join_date']);
        $groups[$c]['member_count'] = $row['member_count'];
        $groups[$c]['description'] = $row['description'];
        $c++;
      }
    }
  }
  return $groups;
}

function get_groupids($groups) {
  // works with the array returned by get_groups($userid, false)
  $list_size = count($groups);
  for ($i = 0; $i < $list_size; $i++) {
    $groupids[] = $groups[$i]['group_id'];
  }
  return $groupids;
}

function get_groupid_by_expenseid($expid) {
  // checks if email exists and returns corresponding userid if true
  $sql = "SELECT group_id FROM expenses WHERE expense_id = $expid";
  $result = mysql_query($sql);
  if (mysql_num_rows($result) == 1) {
    return mysql_result($result, 0);
  }
  return false;
}

function get_groupid_by_acid($acid) {
  // checks if email exists and returns corresponding userid if true
  $sql = "SELECT group_id FROM users_achievements WHERE id = $acid";
  $result = mysql_query($sql);
  if (mysql_num_rows($result) == 1) {
    return mysql_result($result, 0);
  }
  return false;
}


function get_groupid_by_eventid($eventid) {
  // checks if email exists and returns corresponding userid if true
  $sql = "SELECT group_id FROM events WHERE event_id = $eventid";
  $result = mysql_query($sql);
  if (mysql_num_rows($result) == 1) {
    return mysql_result($result, 0);
  }
  return false;
}

function group_permissions($groupid, $userid) {
  $sql = "SELECT permissions.permission_id, `action` , roles_permissions.role_id
          FROM users_groups, roles_permissions, permissions
          WHERE users_groups.role_id = roles_permissions.role_id
          AND roles_permissions.permission_id = permissions.permission_id
          AND users_groups.group_id = $groupid
          AND users_groups.user_id = $userid
          ORDER BY permissions.permission_id ASC";
  if ((!$result = mysql_query($sql)) || (mysql_num_rows($result) == 0)) {
    return false;
  } else {
    while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
      $perm[$row['permission_id']] = $row['action'];
    }
  }
  /* Permissions:
    0 - Invite
    1 - Delete own expense
    2 - Delete other expense
    3 - Add own expense
    4 - Add other expense
    5 - View */
  return($perm);
}

function get_groupmembers($groupids, $inc_removed=false, $keybyuserid=false) {
  if (is_array($groupids))
    $groupids = implode(",", $groupids);
  if ($inc_removed == false)
    $removed = "AND users_groups.removed = 0";
  $sql = "SELECT users_groups.user_id, username, realname, email, shortname AS role, UNIX_TIMESTAMP(join_date) as join_date, last_login
          FROM users_groups, users, roles
          WHERE users.user_id = users_groups.user_id
          AND users_groups.role_id = roles.role_id
          $removed
          AND users_groups.group_id IN ($groupids)";
  if (!$result = mysql_query($sql)) {
    return false;
  } else {
    $c = 0;
    while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
      $members[$c]['username'] = $row['username'];
      $members[$c]['realname'] = $row['realname'];
      $members[$c]['email'] = $row['email'];
      $members[$c]['role'] = $row['role'];
      $members[$c]['user_id'] = $row['user_id'];
      $members[$c]['joindate'] = date("j M Y", $row['join_date']);
      //$members[$c]['lastlogin'] = date("d M Y H:i", $row['last_login']);
      $members[$c]['lastlogin'] = date("d M H:i", $row['last_login']);
      $members[$c]['lastloginunix'] = $row['last_login'];
      $c++;
    }
  }
  if (!$keybyuserid)
    return $members;
  else {
    foreach ($members as $member)
      $keyed[$member['user_id']] = $member;
    return $keyed;
  }
}

function get_groupexpenses($groupid, $uid=false, $paid=false, $ownuid=false, $depSeparate = false) {
  // Return all expenses by a group, limit to a user if user_id is specified.
  // paid can be "pos" or "neg", if specified, only paid or participated expenses are shown
  // if ownuid is given, expense amounts will be hidden for that id if the expense type is presents 
  if ($uid)
    $ids = get_user_expenses_idonly($uid);
  if ($ownuid)
    $ownids = get_user_expenses_idonly($ownuid);
  /*
    $sql = "SELECT expense_id as expid, type, expense_types.description AS type_description, expenses.user_id,
    username, realname, expenses.group_id, expenses.description, amount, UNIX_TIMESTAMP(expense_date) AS expense_date, currency,event_name, event_description, deposit_id,
    (SELECT  COUNT(DISTINCT users_expenses.user_id)
    FROM users_expenses, users_groups
    WHERE users_expenses.user_id = users_groups.user_id
    AND users_expenses.expense_id = expid
    GROUP BY users_expenses.expense_id) AS member_count
    FROM  users, expense_types, expenses
    LEFT JOIN events on expenses.event_id = events.event_id
    WHERE expenses.user_id = users.user_id AND expenses.type = expense_types.expense_type_id AND expenses.group_id = $groupid
    ORDER BY expense_date DESC, expid DESC";

   */

  $sql = "SELECT expense_id as expid, type, expense_types.description AS type_description, expenses.user_id, 
                    username, realname, expenses.group_id, expenses.description, amount, UNIX_TIMESTAMP(expense_date) AS expense_date, currency,event_name, event_description, deposit_id as depid,
                      (SELECT  COUNT(DISTINCT users_expenses.user_id) 
                       FROM users_expenses, users_groups
                       WHERE users_expenses.user_id = users_groups.user_id
                       AND users_expenses.expense_id = expid
                       GROUP BY users_expenses.expense_id) AS member_count,
                      (SELECT  COUNT(DISTINCT expense_id) 
                       FROM expenses
                       WHERE deposit_id = depid
                       GROUP BY deposit_id) AS deposit_count      
                    FROM  users, expense_types, expenses
          LEFT JOIN events on expenses.event_id = events.event_id 
                    WHERE expenses.user_id = users.user_id AND expenses.type = expense_types.expense_type_id AND expenses.group_id = $groupid
                    ORDER BY expense_date DESC, expid DESC";

  if (!$result = mysql_query($sql)) {
    return false;
  } else {
    $expenses = array();
    $c = 0;
    $depcount = 1;
    while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
      if (
              !$uid ||
              ( $uid && in_array($row['expid'], $ids) && !$paid ) ||
              ( $uid && $paid == "pos" && $uid == $row['user_id'] ) ||
              //( $uid && $paid == "neg" && $uid != $row['user_id'] && in_array($row['expid'],$ids)  )
              ( $uid && $paid == "neg" && in_array($row['expid'], $ids) )
      ) {
        //  check for deposit, if found, only show first one if deposit id is the same
        if ($row['type'] == 0 &&
                $expenses[$c - 1]['type_id'] == 0 &&
                $expenses[$c - 1]['depid'] === $row['depid'] &&
                !$depSeparate
        ) {
          // similar deposit
          $depcount++;
          $expenses[$c - 1]['username'] = "{$depcount} participants";
          $expenses[$c - 1]['realname'] = "{$depcount} participants";
        } else {
          $depcount = 1;
          $expenses[$c]['depid'] = $row['depid'];
          $expenses[$c]['expense_id'] = $row['expid'];
          $expenses[$c]['type_id'] = $row['type'];
          $expenses[$c]['type_name'] = $row['type_description'];
          $expenses[$c]['userid'] = $row['user_id'];
          $expenses[$c]['username'] = $row['username'];
          $expenses[$c]['realname'] = $row['realname'];
          if ($row['event_name'])
            $expenses[$c]['description'] = $row['event_name'] . " (" . $row['type_description'] . ")";
          else
            $expenses[$c]['description'] = $row['description'];
          if ($uid && $paid == "neg") {
            $expenses[$c]['amount'] = number_format(($row['amount'] / $row['member_count']), DECIMALS , DSEP,TSEP) ;
            $expenses[$c]['bsign'] = "_neg";
          } elseif ($uid && $paid == "pos") {
            $expenses[$c]['amount'] = $row['amount'];
            $expenses[$c]['bsign'] = "_pos";
          } elseif ($row['type'] == 0 && !$depSeparate) {
            $expenses[$c]['amount'] = number_format(($row['amount'] * $row['deposit_count']), DECIMALS , DSEP,TSEP) ;
            $expenses[$c]['bsign'] = "_pos";
          }
          else
            $expenses[$c]['amount'] = $row['amount'];

          // make amount green if deposit
          //if ($row['type']  ==0)  $expenses[$c]['bsign'] = "_pos";

          $expenses[$c]['date'] = date("d-m-Y H:i", $row['expense_date']);
          $expenses[$c]['currency'] = $row['currency'];
          //if ($uid && $uid == $row['user_id']) $expenses[$c]['bsign'] = "_pos";
          //elseif ($uid) $expenses[$c]['bsign'] = "_neg";
          $expenses[$c]['member_count'] = $row['member_count'];

          // hide amount for presents 
          if (isset($ownids) &&   !in_array($row['expid'], $ownids) && $row['user_id'] != $ownuid && $row['type'] == 3) {
            // user did not participate in expense and expense is present
            $expenses[$c]['amount'] = "-";
          }
          $c++;
        }
      }
    }
  }
  return $expenses;
}

function get_groupmember_ids($member_array) {
  // works with the array returned by get_groupmembers($groupid)
  $list_size = count($member_array);
  for ($i = 0; $i < $list_size; $i++) {
    $memberids[] = $member_array[$i]['user_id'];
  }
  return $memberids;
}

function is_valid_groupid($groupid) {
  if (!preg_match("/^[0-9]+$/", $groupid)) {
    return false;
  }
  return true;
}

function get_groupdetails($groupid) {
  $sql = "SELECT group_id, name, description, UNIX_TIMESTAMP(reg_date) as reg_date FROM groups WHERE group_id = $groupid";
  if ((!$result = mysql_query($sql)) || (mysql_num_rows($result) == 0) || (mysql_num_rows($result) > 1)) {
    return false;
  } else {
    while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
      $details['group_id'] = $groupid;
      $details['name'] = $row['name'];
      $details['description'] = $row['description'];
      $details['reg_date'] = date("j M Y", $row['reg_date']);
    }
  }
  return $details;
}

function get_achievementdetails($id) {
  $sql = " SELECT * FROM achievements a, users_achievements ua, users u
                 WHERE ua.achievement_id = a.achievement_id
                 AND ua.user_id = u.user_id
                 AND ua.id ={$id}" ;
   $details = array();
  if ((!$result = mysql_query($sql)) || (mysql_num_rows($result) == 0) || (mysql_num_rows($result) > 1)) {
    return false;
  } else {
    while ($row = mysql_fetch_array($result, MYSQL_ASSOC))
        $details = $row;
  }
  return $details;
}

function get_expensedetails($expid, $ownuid = false) {
  // get all expense detail by expenseid
  /*
    $sql = "SELECT expense_id, deposit_id, expense_types.description AS type, expense_types.expense_type_id AS typeid, e.group_id, e.description,
    e.user_id, username, realname,
    amount,	UNIX_TIMESTAMP(expense_date) as expense_date, groups.name AS groupname, groups.description AS groupdescr,
    sign, UNIX_TIMESTAMP(e.timestamp) AS timestamp, e.event_id, v.event_name, v.event_description, v.date
    FROM (expenses e, expense_types, currency, groups, users)
    left join events v on (e.event_id = v.event_id)
    WHERE type = expense_types.expense_type_id AND currency = currency_id
    AND e.group_id = groups.group_id AND e.user_id = users.user_id
    AND expense_id = $expid";
   */
  $sql = "SELECT expense_id, e.deposit_id, expense_types.description AS type, expense_types.expense_type_id AS typeid, e.group_id, e.description,
                    e.user_id, users.username, users.realname,
                    amount, UNIX_TIMESTAMP(expense_date) as expense_date, groups.name AS groupname, groups.description AS groupdescr, 
                    sign, UNIX_TIMESTAMP(e.timestamp) AS timestamp, e.event_id, v.event_name, v.event_description, v.date, d.holder as deposit_holder_id, holders.username as holder_username, holders.realname as holder_realname
                    FROM (expenses e, expense_types, currency, groups, users)
          left join events v on (e.event_id = v.event_id)
          left join deposits d on (e.deposit_id = d.deposit_id)
          left join users holders on (d.holder = holders.user_id)
                    WHERE type = expense_types.expense_type_id AND currency = currency_id 
                    AND e.group_id = groups.group_id AND e.user_id = users.user_id 
                    AND expense_id =  $expid";


  if ((!$result = mysql_query($sql)) || (mysql_num_rows($result) == 0) || (mysql_num_rows($result) > 1)) {
    return false;
  } else {
    while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
      $details['expense_id'] = $expid;
      $details['type'] = $row['type'];
      $details['typeid'] = $row['typeid'];

      if ($row['typeid'] == 0) {
        // deposit is a special case, owner is now owner/holder of the deposit
        $details['ownerid'] = $row['deposit_holder_id'];
        $details['ownerusername'] = $row['holder_username'];
        $details['ownerreal'] = $row['holder_realname'];
        $details['depositid'] = $row['deposit_id'];
      } else {
        $details['ownerid'] = $row['user_id'];
        $details['ownerusername'] = $row['username'];
        $details['ownerreal'] = $row['realname'];
      }

      $details['groupid'] = $row['group_id'];
      $details['groupname'] = $row['groupname'];
      $details['groupdescr'] = $row['groupdescr'];
      $details['eventid'] = $row['event_id'];
      $details['eventname'] = $row['event_name'];
      $details['eventdescr'] = $row['event_description'];
      $details['description'] = $row['description'];
      $details['amount'] = $row['amount'];
      $details['expense_date'] = date('D j M Y H:i', $row['expense_date']);
      //$details['expense_date'] = $row['expense_date'];
      $details['expense_date_unix'] = $row['expense_date'];
      $details['timestamp'] = date('D j M Y H:i', $row['timestamp']);
      $details['currency'] = $row['sign'];
    }
    if ($details['typeid'] == 0) {
      $sql = "SELECT `deposit_id`, `expenses`.`user_id`, `username`, `realname` 
                 FROM expenses, users 
                 WHERE expenses.user_id = users.user_id AND deposit_id = " . $details['depositid'];
    } else {
      $sql = "SELECT users_expenses.user_id, username, realname 
              FROM users_expenses, users 
              WHERE users_expenses.user_id = users.user_id AND expense_id = $expid";
    }
    if (!$result = mysql_query($sql)) {
      return false;
    } else {
      $c = 0;
      while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
;
        $details['members'][$row['user_id']]['username'] = $row['username'];
        $details['members'][$row['user_id']]['realname'] = $row['realname'];
        $c++;
      }
    }
  }
  return $details;
}

function get_eventdetails($eventid) {
  // get all expense detail by expenseid
  $sql = "SELECT events.event_id, event_name, event_description, events.group_id, organizer_id, UNIX_TIMESTAMP( date ) AS date, 
  					expense_types.description AS type , expense_types.expense_type_id AS typeid, count( expense_id ) AS expcount, 
  					SUM(amount) AS amount, username, realname, name AS groupname, groups.description AS groupdescr
  				FROM (events , expense_types, users, groups)
					LEFT JOIN expenses e ON ( events.event_id = e.event_id )
					WHERE expense_types.expense_type_id = events.expense_type_id
						AND events.organizer_id = users.user_id
						AND events.group_id = groups.group_id
						AND events.event_id = $eventid
				  GROUP BY events.event_id";
  if ((!$result = mysql_query($sql)) || (mysql_num_rows($result) == 0) || (mysql_num_rows($result) > 1)) {
    return false;
  } else {
    while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
      $details['eventid'] = $eventid;
      $details['type'] = $row['type'];
      $details['typeid'] = $row['typeid'];
      $details['organizerid'] = $row['organizer_id'];
      $details['organizerusername'] = $row['username'];
      $details['organizerreal'] = $row['realname'];
      $details['groupid'] = $row['group_id'];
      $details['groupname'] = $row['groupname'];
      $details['groupdescr'] = $row['groupdescr'];
      $details['eventname'] = $row['event_name'];
      $details['eventdescr'] = $row['event_description'];
      $details['expcount'] = $row['expcount'];
      $details['expsum'] = $row['amount'];
      $details['event_date'] = date('D j M Y', $row['date'] + 4000);
      //$details['expense_date'] = $row['expense_date'];
      $details['event_date_unix'] = $row['date'];
    }
    $sql = "SELECT users_events.user_id, username, realname 
            FROM users_events, users 
            WHERE users_events.user_id = users.user_id AND event_id = $eventid";
    if (!$result = mysql_query($sql)) {
      return false;
    } else {
      $c = 0;
      while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
;
        $details['members'][$row['user_id']]['username'] = $row['username'];
        $details['members'][$row['user_id']]['realname'] = $row['realname'];
        $c++;
      }
    }
  }
  return $details;
}

function add_member($name, $email, $groupid, $role=4) {
 // $sql = "INSERT INTO users (email, realname, reg_date, group_id)
 //          VALUES ('$email','$name'," . time() . ", 0)";
  $sql = "INSERT INTO users (email, realname, reg_date)
           VALUES ('$email','$name'," . time() . ")";
  $sql = "INSERT INTO users (email, realname, reg_date)
           VALUES ('$email','$name'," . time() . ")";
  if (!$result = mysql_query($sql)) {
    return false;
  } else {
    $userid = mysql_insert_id();
    $sql = "INSERT INTO `goingdutch`.`users_groups` (`user_id` , `group_id` , `role_id` , `join_date`)
            VALUES ('$userid', '$groupid', '$role', CURRENT_TIMESTAMP)";
    if (!$result = mysql_query($sql)) {
      return false;
    }
  }
  return $userid;
}

function add_member_to_group($userid, $groupid, $role=4) {
  $sql = "INSERT INTO `goingdutch`.`users_groups` (`user_id` , `group_id` , `role_id` , `join_date`)
          VALUES ('$userid', '$groupid', '$role', CURRENT_TIMESTAMP)";
  if (!$result = mysql_query($sql)) {
    return false;
  }
  return true;
}

function check_group($post, $get) {
  // check if valid group specified and return group details
  // get groupid
  if (isset($post['groupid'])) {
    $groupid = $post['groupid'];
  } elseif (isset($get['groupid'])) {
    $groupid = $get['groupid'];
  } else {
    fatal_error("No group specified");
  }

  if (!is_valid_groupid($groupid)) {
    fatal_error("Not a valid groupid");
  }

  // get group details (name etc), or exit in case of problems
  if (!$groupdetails = get_groupdetails($groupid)) {
    fatal_error("Could not retrieve group details");
  }

  // $groupdetails: [group_id] [name]	[description] [reg_date] 
  return $groupdetails;
}

function get_expense_types() {
  $sql = "SELECT * FROM expense_types WHERE expense_type_id > 0 ORDER BY expense_type_id ASC";
  if (!$result = mysql_query($sql)) {
    return false;
  } else {
    while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
      $expense_types[$row['expense_type_id']] = $row['description'];
    }
  }
  return $expense_types;
}

function printr($array) {
  echo "\n\n<p>\n<br />\n";
  echo "--- PRINT_R DEBUG:<br /><br />\n\n<pre>\n\n";
  print_r($array);
  echo "\n\n<br /><br />\n---\n<br />\n</pre\n</p>\n\n";
}

function fatal_error($message) {
  // print header, message, link to home and footer 
  if (!headers_sent()) {
    print_header();
    print_body_start();
  }
  $link = "    <a href=\"http://" . $_SERVER['HTTP_HOST'] . DIR . "index.php\">Home</a>";
  print_pageitem_text_html("Something went wrong:", array($message, $link));
  print_footer();
  exit;
}

function is_valid_bookdate($unixtime) {
  // not valid if more than n months ago
  $n = 3;
  //echo date("j M Y H:i", $unixtime);
  //echo "<br>";
  //echo date("j M Y H:i", mktime(date("H"),date("i"),date("s"),date("n")-$n,date("j"),date("Y")));
  if ($unixtime - mktime(date("H"), date("i"), date("s"), date("n") - $n, date("j"), date("Y")) < 0) {
    return false;
  }
  return true;
}

function book_expense($description, $amount, $type, $timestamp, $userid, $groupid, $members, $eventid, $deposit=0) {
  if (!preg_match("/(,|\.)/", $amount)) {
    $amount .= ".00";
  } elseif (preg_match("/,/", $amount)) {
    $amount = str_replace(",", ".", $amount);
  }
  if ($deposit == 0) {
    //$mysqldate = date('Y-m-d H:i:s', $timestamp);
    $sql = "INSERT INTO expenses (type, user_id, group_id, description, amount, expense_date, event_id, timestamp, currency) 
            VALUES ('$type', '$userid', '$groupid', '$description', '$amount', FROM_UNIXTIME($timestamp), $eventid, CURRENT_TIMESTAMP , '1')";
    if (!$result = mysql_query($sql)) {
      return false;
    } else {
      $expenseid = mysql_insert_id();
      foreach ($members as $key => $value) {
        $sql = "INSERT INTO users_expenses (`user_id` , `expense_id`) VALUES ('$value', '$expenseid')";
        if (!$result = mysql_query($sql)) {
          $inserterror = true;
        }
      }
      if ($inserterror)
        return false;
      return true;
    }
  }
  else {
    // making a deposit
    // for each person, make an expense with only deposit holder ($userid) as recipient
    // first register deposit to get a deposit id
    $sql = "INSERT INTO deposits (holder, description) VALUES ($userid, '$description')";
    if (!$result = mysql_query($sql)) {
      return false;
    } else {
      $depositid = mysql_insert_id();
    }

    foreach ($members as $key => $value) {
      $sql = "INSERT INTO expenses (type, user_id, group_id, description, amount, expense_date, event_id, timestamp, currency, deposit_id) 
            VALUES ('$type', '$value', '$groupid', '$description', '$amount', FROM_UNIXTIME($timestamp), $eventid, CURRENT_TIMESTAMP , '1', $depositid)";
      if (!$result = mysql_query($sql)) {
        return false;
      } else {
        $expenseid = mysql_insert_id();
        $sql = "INSERT INTO users_expenses (`user_id` , `expense_id`) VALUES ('$userid', '$expenseid')";
        if (!$result = mysql_query($sql)) {
          $inserterror = true;
        }
      }
    }
  }
  if ($inserterror)
    return false;
  return true;
}

function str_format() {
    $args = func_get_args();
    $str = mb_ereg_replace('{([0-9]+)}', '%\\1$s', array_shift($args));
    return vsprintf($str, array_values($args));
}


function mail_expense($description, $amount, $type, $timestamp, $booker_userid, $groupid, $members, $eventid, $deposit=0) {
  if (!preg_match("/(,|\.)/", $amount)) {
    $amount .= ".00";
  } elseif (preg_match("/,/", $amount)) {
    $amount = str_replace(",", ".", $amount);
  }
  $userList = array();
  foreach ($members as $id) {
    $userList[$id] = get_user_profile($id);
  }

  $allgroupmembers = get_groupmembers($groupid, false, true);
  
  $groupdetails = get_groupdetails($groupid);
  $balancelist = array_sort(get_group_balance_list($groupdetails), 'unformatted_balance', SORT_DESC);
  
  if ($deposit == 0) {
    $p1 = date('l jS \of F Y');
           
    $p3 = number_format($amount,  DECIMALS , DSEP,TSEP) ;
    $p4 = '';
    if (!empty($eventid)){
      $eventdetails = get_eventdetails($eventid);
      $p4 = " for event \"{$eventdetails['eventname']}\"";
    }
    $p5 = $description;
    $p7 = number_format($amount/(count($members)),  DECIMALS , DSEP,TSEP) ;

    
    $message  = "On {1} {2} booked an expense of &#8364; {3}{4} with description \"{5}\".<br /><br />";
    $message .= "You were listed as a participant, together with {6}.<br /><br />";
    $message .= "The costs per person are &#8364; {7} making your balance &#8364; {8} which comes to position {9} in the group. ";
    $message .= "The balance list is now: <br /><br />{10}";
    $message .= "<br /><br /><a href=\"".LOGIN_URL."\">Going Dutch</a>";
    
    $from = 'admin@inthere.nl';
    $from_name = 'Going Dutch';
    $subject = "Going Dutch expense booked in group \"{$groupdetails['name']}\"";
    $subject = addslashes($subject);
foreach ($userList as $user) {
    // skip user if prefs are not set
    if (!isset($user['name_format']) || !isset($user['email_notify']) )
      continue;
  
    if ($user['user_id'] == $booker_userid)
      $p2 = 'you have';
    else
      $p2 = format_name($user['name_format'],$userList[$booker_userid]['username'], $userList[$booker_userid]['realname']) . ' has';
    $others = '';
    foreach ($members as $participant){
      if ($participant != $user['user_id'])
        $others .= ', ' . format_name($user['name_format'],$userList[$participant]['username'], $userList[$participant]['realname']);
    }
    //
    $p6 = preg_replace('/(.*),/','$1 and', trim($others, ', '));
    //$text = preg_replace('/(.*),/','$1 and',$text)

    $p8 = $balancelist[$user['user_id']]['balance'];
    $i=1;
    foreach ($balancelist as $key => $val) {
      if ($key == $user['user_id'])
        break;
      $i++;
    }
    $p9 = $i;
    
    
    
    $tablehtml = create_memberlist_html_table($balancelist, $allgroupmembers, $user['name_format'], SORT_DESC);
    $p10 = $tablehtml;
    $a=1;
    $body = str_format($message,$p1,$p2,$p3,$p4,$p5,$p6,$p7,$p8,$p9,$p10);
    $pp = $amount/(count($members));
    $floatval =floatval($user['email_notify']);
    
    if ($user['email_notify'] != '-1' && $pp >= $floatval){
      // exec($command, $output = array()); 
      // smtpmailer($user['email'], $from, $from_name, $subject, $body, $replyto = '', $sendas='to');
      $replyto = '';
      $sendas='to';
      $background_mailfile = dirname(__FILE__) . '/background_mailer.php';
      //$output = '/var/log/test';
      $output = '/dev/null';
      
      $body = addslashes($body);
      $cmd = "/usr/bin/php5 {$background_mailfile} {$user['email']} {$from} \"{$from_name}\" \"{$subject}\" \"{$body}\" \"{$replyto}\" \"{$sendas}\"";
      //exec("/usr/bin/php {$background_mailfile} {$user['email']} {$from} {$from_name} {$subject} {$body} {$replyto} {$sendas} > {$ouput} &");
      exec("{$cmd} > {$output} &");
      //exec("/usr/bin/php {$background_mailfile} {$user['email']} {$from} \"{$from_name}\" \"{$subject}\" \"{$body}\" \"{$replyto}\" \"{$sendas}\" > {$output} &");
      }
}
  
//
    //      foreach ($members as $key => $value) {
    //      
    //      }
    //$mysqldate = date('Y-m-d H:i:s', $timestamp);
    $sql = "INSERT INTO expenses (type, user_id, group_id, description, amount, expense_date, event_id, timestamp, currency) 
            VALUES ('$type', '$userid', '$groupid', '$description', '$amount', FROM_UNIXTIME($timestamp), $eventid, CURRENT_TIMESTAMP , '1')";
//    if (!$result = mysql_query($sql)) {
//      return false;
//    } else {
//      $expenseid = mysql_insert_id();
//      foreach ($members as $key => $value) {
//        $sql = "INSERT INTO users_expenses (`user_id` , `expense_id`) VALUES ('$value', '$expenseid')";
//        if (!$result = mysql_query($sql)) {
//          $inserterror = true;
//        }
//      }
//      if ($inserterror)
//        return false;
//      return true;
//    }
  }
  else {
    // making a deposit
    // for each person, make an expense with only deposit holder ($userid) as recipient
    // first register deposit to get a deposit id
//    $sql = "INSERT INTO deposits (holder, description) VALUES ($userid, '$description')";
//    if (!$result = mysql_query($sql)) {
//      return false;
//    } else {
//      $depositid = mysql_insert_id();
//    }
//
//    foreach ($members as $key => $value) {
//      $sql = "INSERT INTO expenses (type, user_id, group_id, description, amount, expense_date, event_id, timestamp, currency, deposit_id) 
//            VALUES ('$type', '$value', '$groupid', '$description', '$amount', FROM_UNIXTIME($timestamp), $eventid, CURRENT_TIMESTAMP , '1', $depositid)";
//      if (!$result = mysql_query($sql)) {
//        return false;
//      } else {
//        $expenseid = mysql_insert_id();
//        $sql = "INSERT INTO users_expenses (`user_id` , `expense_id`) VALUES ('$userid', '$expenseid')";
//        if (!$result = mysql_query($sql)) {
//          $inserterror = true;
//        }
//      }
//    }
  }
  if ($inserterror)
    return false;
  return true;
}

function get_group_balance_list($groupdetails) {
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
  
    $listarray[$memberlist[$i]['user_id']]['link'] = "http://" . $_SERVER['HTTP_HOST'] . DIR . "profile.php?uid=" . $memberlist[$i]['user_id'];
    //$listarray[$i]['name'] = $memberlist[$i]['realname'] . $uname;
    //$listarray[$memberlist[$i]['user_id']]['name'] = format_name($user,$memberlist[$i]['username'],$memberlist[$i]['realname']);
    $listarray[$memberlist[$i]['user_id']]['balance'] = number_format(($upaid-$uexpense),DECIMALS , DSEP,TSEP) ;
    $listarray[$memberlist[$i]['user_id']]['unformatted_balance'] = $upaid-$uexpense ;
    $listarray[$memberlist[$i]['user_id']]['user_id'] = $memberlist[$i]['user_id'];
  }
  return $listarray;
}
  

function delete_expense($expid) {
  $sql = "DELETE FROM `goingdutch`.`users_expenses` WHERE `users_expenses`.`expense_id` = $expid";
  if (!$result = mysql_query($sql)) {
    return false;
  } else {
    $sql = "DELETE FROM `goingdutch`.`expenses` WHERE `expenses`.`expense_id` = $expid";
    if (!$result = mysql_query($sql)) {
      return false;
    }
  }
  return true;
}

function update_expense($expid, $description, $amount, $type, $timestamp, $userid, $groupid, $members, $eventid, $currentmembers) {
  if (!preg_match("/(,|\.)/", $amount)) {
    $amount .= ".00";
  } elseif (preg_match("/,/", $amount)) {
    $amount = str_replace(",", ".", $amount);
  }
  //$mysqldate = date('Y-m-d H:i:s', $timestamp);
  $sql = "UPDATE expenses SET type = '$type', user_id = $userid, group_id = $groupid, description = '$description', amount='$amount',
          expense_date = FROM_UNIXTIME($timestamp), event_id = $eventid
          WHERE expense_id = $expid";
  if (!$result = mysql_query($sql)) {
    return false;
  } else {
    foreach ($members as $key => $value) {
      // add all new members for this expense
      if (!in_array($value, $currentmembers)) {
        $sql = "REPLACE INTO users_expenses (`user_id` , `expense_id`) VALUES ('$value', '$expid')";
        if (!$result = mysql_query($sql)) {
          $inserterror = true;
        }
      }
    }
    $diff = array_diff($currentmembers, $members);

    foreach ($diff as $key => $value) {
      $sql = "DELETE FROM `goingdutch`.`users_expenses` WHERE `users_expenses`.`user_id` = $value AND `users_expenses`.`expense_id` = $expid";
      if (!$result = mysql_query($sql)) {
        $inserterror = true;
      }
    }
  }
  if ($inserterror)
    return false;
  return true;
}

function book_event($name, $description, $typeid, $date, $userid, $groupid, $members) {
  //$mysqldate = date('Y-m-d', $date);
  $sql = "INSERT INTO events (event_name, event_description, group_id, organizer_id, date, expense_type_id, timestamp)
          VALUES ('$name', '$description', $groupid, $userid, FROM_UNIXTIME($date), $typeid, CURRENT_TIMESTAMP)";
  if (!$result = mysql_query($sql)) {
    return false;
  } else {
    $eventid = mysql_insert_id();
    foreach ($members as $key => $value) {
      $sql = "INSERT INTO users_events (`user_id` , `event_id`) VALUES ('$value', '$eventid')";
      if (!$result = mysql_query($sql)) {
        $inserterror = true;
      }
    }
  }
  if ($inserterror)
    return false;
  return true;
}

function get_recent_event($groupid, $nameonly = false) {
  $onemonthback = mktime(0, 0, 0, date("m") - 1, date("d"), date("Y"));
  $twoweeklater = mktime(0, 0, 0, date("m"), date("d") + 14, date("Y"));
  $sql = "SELECT event_id, event_name, event_description,	organizer_id, UNIX_TIMESTAMP(date) AS date, expense_type_id 
          FROM events WHERE group_id = $groupid 
          AND date > FROM_UNIXTIME($onemonthback) AND date < FROM_UNIXTIME($twoweeklater)
          ORDER BY date DESC, timestamp DESC ";
  if (!$result = mysql_query($sql)) {
    return false;
  } else {
    if ($nameonly) {
      while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
        $event[$row['event_id']] = $row['event_name'];
      }
    } else {
      while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
        $event['event_id'] = $row['event_id'];
        $event['event_name'] = $row['event_name'];
        $event['description'] = $row['event_description'];
        $event['organizer'] = $row['organizer_id'];
        $event['date'] = date("j M Y", $row['date']);
        $event['expense_type'] = $row['expense_type_id'];
      }
      $sql = "SELECT user_id FROM users_events WHERE event_id = " . $event['event_id'];
      if (!$result = mysql_query($sql)) {
        return false;
      } else {
        while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
          $event['member_ids'][] = $row['user_id'];
        }
      }
    }
  }
  return $event;
}

function get_all_events($groupid, $nameonly = false) {
  $sql = "SELECT event_id, event_name, event_description,	organizer_id, UNIX_TIMESTAMP(date) AS date, expense_type_id 
          FROM events WHERE group_id = $groupid 
          ORDER BY date DESC, timestamp DESC";
  if (!$result = mysql_query($sql)) {
    return false;
  } else {
    if ($nameonly) {
      while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
        $event[$row['event_id']] = $row['event_name'];
      }
    } else {
      while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
        $event[$row['event_id']]['event_id'] = $row['event_id'];
        $event[$row['event_id']]['event_name'] = $row['event_name'];
        $event[$row['event_id']]['event_name_date'] = $row['event_name'] . " (" . date("D j M Y", $row['date']) . ")";
        $event[$row['event_id']]['description'] = $row['event_description'];
        $event[$row['event_id']]['organizer'] = $row['organizer_id'];
        $event[$row['event_id']]['date'] = date("j M Y", $row['date']);
        $event[$row['event_id']]['expense_type'] = $row['expense_type_id'];
      }
    }
  }
  return $event;
}

function get_upcoming_events($groupid) {
  //$now = mktime(0, 0, 0, date("m"), date("d")-0.5,   date("Y"));
  $now = date('Y-m-d', mktime(0, 0, 0, date("m"), date("d"), date("Y")));
  // dirty hack (4000 seconds) to get correct events on right day (today and onward)
  //$now = mktime();
  $today = mktime(0, 0, 0, date("m"), date("d"), date("Y"));
  $sql = "SELECT event_id, event_name, event_description,	organizer_id, UNIX_TIMESTAMP(date) AS date, expense_type_id 
          FROM events WHERE group_id = $groupid 
          AND date >= FROM_UNIXTIME($today)
          ORDER BY date DESC, timestamp DESC";

  if (!$result = mysql_query($sql)) {
    return false;
  } else {
    if ($nameonly) {
      while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
        $event[$row['event_id']] = $row['event_name'];
      }
    } else {
      while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
        $event[$row['event_id']]['event_id'] = $row['event_id'];
        $event[$row['event_id']]['event_name'] = $row['event_name'];
        $event[$row['event_id']]['event_name_date'] = $row['event_name'] . " (" . date("j M Y", $row['date']) . ")";
        $event[$row['event_id']]['description'] = $row['event_description'];
        $event[$row['event_id']]['organizer'] = $row['organizer_id'];
        $event[$row['event_id']]['date'] = date("D j M Y", $row['date'] + 4000);
        $event[$row['event_id']]['expense_type'] = $row['expense_type_id'];
      }
    }
  }
  return $event;
}

function get_user_expenses($userid_array) {
  if (is_array($userid_array))
    $userids = implode(",", $userid_array);
  else
    $userids = $userid_array;
  $sql = "SELECT users_expenses.user_id AS userid, group_id, users_expenses.expense_id AS exid, amount as total_amount, 
           (SELECT COUNT(*) FROM users_expenses WHERE expense_id = exid) as member_count,
            ROUND((SELECT total_amount/member_count),2) as per_person
          FROM users_expenses, expenses WHERE users_expenses.expense_id = expenses.expense_id AND users_expenses.user_id IN ($userids)
          ORDER BY userid, group_id ASC, users_expenses.expense_id ASC";
  if (!$result = mysql_query($sql)) {
    return false;
  } else {
    $total = 0;
    while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
      if (!isset($expenses['users'][$row['userid']]['groups'][$row['group_id']]['group_total']))
        $expenses['users'][$row['userid']]['groups'][$row['group_id']]['group_total'] = 0;
      if (!isset($expenses['users'][$row['userid']]['user_total']))
        $expenses['users'][$row['userid']]['user_total'] = 0;
      $expenses['users'][$row['userid']]['groups'][$row['group_id']][$row['exid']]['total_amount'] = $row['total_amount'];
      $expenses['users'][$row['userid']]['groups'][$row['group_id']][$row['exid']]['member_count'] = $row['member_count'];
      $expenses['users'][$row['userid']]['groups'][$row['group_id']][$row['exid']]['per_person'] = $row['per_person'];
      $expenses['users'][$row['userid']]['groups'][$row['group_id']]['group_total'] += $row['per_person'];
      $expenses['users'][$row['userid']]['user_total'] += $row['per_person'];
      $total += $row['per_person'];
    }
    $expenses['total'] = $total;
  }
  // array structure: ['users'][$userid]['groups'][$groupid][$expenseid]['total_amount|member_count|per_person']
  // below picture
  return $expenses;
}

function get_user_expenses_idonly($userid_array) {
  if (is_array($userid_array))
    $userids = implode(",", $userid_array);
  else
    $userids = $userid_array;
  $sql = "SELECT expense_id FROM users_expenses WHERE users_expenses.user_id IN ($userids)";
  if (!$result = mysql_query($sql)) {
    return false;
  } else {
    while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
      $expense_ids[] = $row['expense_id'];
    }
  }
  return $expense_ids;
}

function get_user_paid_expenses($userid_array) {
  if (is_array($userid_array))
    $userids = implode(",", $userid_array);
  else
    $userids = $userid_array;
  $sql = "SELECT user_id as userid, expense_id as exid, group_id, amount, UNIX_TIMESTAMP(expense_date) AS expensedate, 
            description FROM expenses WHERE user_id in ($userids)";
  if (!$result = mysql_query($sql)) {
    return false;
  } else {
    $total = 0;
    while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
      if (!isset($paid['users'][$row['userid']]['groups'][$row['group_id']]['group_total']))
        $paid['users'][$row['userid']]['groups'][$row['group_id']]['group_total'] = 0;
      if (!isset($paid['users'][$row['userid']]['user_total']))
        $paid['users'][$row['userid']]['user_total'] = 0;
      if (!isset($paid['users'][$row['userid']]['groups'][$row['group_id']]['total']))
        $paid['users'][$row['userid']]['groups'][$row['group_id']]['total'] = 0;
      if (!isset($paid['users'][$row['userid']]['total']))
        $paid['users'][$row['userid']]['total'] = 0;
      $paid['users'][$row['userid']]['groups'][$row['group_id']][$row['exid']]['amount'] = $row['amount'];
      $paid['users'][$row['userid']]['groups'][$row['group_id']][$row['exid']]['expense_date'] = date("j M Y", $row['expensedate']);
      $paid['users'][$row['userid']]['groups'][$row['group_id']][$row['exid']]['description'] = $row['description'];
      $paid['users'][$row['userid']]['groups'][$row['group_id']]['group_total'] += $row['amount'];
      $paid['users'][$row['userid']]['user_total'] += $row['amount'];
      $total += $row['amount'];
      // format totals with two decimals
      $paid['users'][$row['userid']]['groups'][$row['group_id']]['total'] = number_format($paid['users'][$row['userid']]['groups'][$row['group_id']]['total'], DECIMALS , DSEP,TSEP) ;
      $paid['users'][$row['userid']]['total'] = number_format($paid['users'][$row['userid']]['total'], DECIMALS , DSEP,TSEP) ;
    }
    $paid['total'] = number_format($total, DECIMALS , DSEP,TSEP) ;
  }
  // array structure: ['users'][$userid]['groups'][$groupid][$expenseid]['amount|expense_date|description']
  return $paid;
}

function get_user_balance($userid) {
  
}

function get_msg($msgid) {
  switch ($msgid) {
    case "b1":
      return "<b>Succes:</b> Expense added";
      break;

    case "b2":
      return "<b>Error:</b> Could not add expense";
      break;

    case "e1":
      return "<b>Succes:</b> Event added";
      break;

    case "e2":
      return "<b>Error:</b> Could not add event";
      break;

    case "x1":
      return "<b>Succes:</b> Expense updated";
      break;

    case "x2":
      return "<b>Error:</b> Could not update expense";
      break;

    case "x3":
      return "<b>Succes:</b> Expense deleted";
      break;

    case "x4":
      return "<b>Error:</b> Could not delete expense";
      break;


    case "p1":
      return "<b>Succes:</b> Profile updated";
      break;

    case "s1":
      return "<b>Succes:</b> Accounts settled";
      break;

    case "s2":
      return "<b>Error:</b> Errors occured while settling accounts";
      break;
  }
}

function n($count) {
  $char = " ";
  return str_repeat($char, $count);
}

function create_form_html($formarray) {
  //$formarray['action']: submit url
  //$formarray['rows'][$i][type|label|name|value|checked]
  $submithtml = '';
  $hiddenhtml = '';
  $formhtml = "\n";
  $formhtml .= n(4) . "<fieldset>\n";
  $formhtml .= n(6) . "<form method=\"post\" action=\"" . $formarray['action'] . "\">\n";
  $formhtml .= n(8) . "<ul class=\"pageitem\">\n";
  //$formhtml .= n(8) . "<p>\n";
  $rows = count($formarray['rows']);
  if (isset($formarray['rows'][0]))
    $j = 0;
  else
    $j = 1;
  for ($i = 0 + $j; $i < $rows + $j; $i++) {
    if (!isset($formarray['rows'][$i]))
      $j++;
    if (!empty($formarray['rows'][$i]['items'])) {
      $items = explode('|', $formarray['rows'][$i]['items']);
      $formarray['rows'][$i]['label'] = $items[0];
      $formarray['rows'][$i]['name'] = $items[1];
      $formarray['rows'][$i]['type'] = $items[2];
      $formarray['rows'][$i]['value'] = $items[3];
    }

    if ($formarray['rows'][$i]['type'] == "select" && $formarray['rows'][$i]['html']) {
      // take html
      $formhtml .= $formarray['rows'][$i]['html'];
    } elseif ($formarray['rows'][$i]['type'] == "select") {
      if ($formarray['rows'][$i]['label']) {
        $llabel = $formarray['rows'][$i]['label'];
      }
      if (isset($formarray['rows'][$i]['selected'])) {
        $sselect = $formarray['rows'][$i]['selected'];
      }
      $formhtml .= n(12);
      //$formhtml .= "<li class=\"select\"><select name=\"" .  $formarray['rows'][$i]['name'] . "\">\n";
      //
      $formhtml .= "<li class=\"selectrow\">";
      $formhtml .= "<span class=\"name\">" . $formarray['rows'][$i]['label'] . "</span>\n";
      $formhtml .= "<select class=\"rightselect\" name=\"" . $formarray['rows'][$i]['name'] . "\">\n";


      //
      foreach ($formarray['rows'][$i]['value'] as $key => $value) {
        if (isset($sselect) && $sselect == $key)
          $_selected = "selected=\"selected\"";
        else
          $_selected = "";
        //$formhtml .= n(14) . "<option value=\"$key\" $_selected>$llabel $value</option>\n";
        $formhtml .= n(14) . "<option value=\"$key\" $_selected>$value</option>\n";
      }
      $formhtml .= n(12) . "</select><span class=\"arrow\"></li>\n";
    }


    if ($formarray['rows'][$i]['type'] == "text" || $formarray['rows'][$i]['type'] == "password") {
      $formhtml .= n(12);
      //$formhtml .= "<label>" . $formarray['rows'][$i]['label'] . "</label>\n";
      $formhtml .= "<li class=\"smallfield\">\n" . n(14);
      $formhtml .= "<span class=\"name\">" . $formarray['rows'][$i]['label'] . "</span>\n";
      $formhtml .= n(14);
      $formhtml .= "<input name=\"" . $formarray['rows'][$i]['name'] . "\"";
      $formhtml .= " type=\"" . $formarray['rows'][$i]['type'] . "\"";
      $formhtml .= " value=\"" . $formarray['rows'][$i]['value'] . "\" />\n";
      $formhtml .= n(12) . "</li>\n";
      ;
    } elseif ($formarray['rows'][$i]['type'] == "hidden") {
      $hiddenhtml .= n(10);
      $hiddenhtml .= "<input name=\"" . $formarray['rows'][$i]['name'] . "\"";
      $hiddenhtml .= " type=\"" . $formarray['rows'][$i]['type'] . "\"";
      $hiddenhtml .= " value=\"" . $formarray['rows'][$i]['value'] . "\" />\n";
    } elseif ($formarray['rows'][$i]['type'] == "checkbox") {
      $checked = "";
      $formhtml .= n(12);
      if ($formarray['rows'][$i]['checked'] == 1)
        $checked = "checked=\"checked\"";
      $formhtml .= "<li class=\"checkbox\">\n" . n(14);
      $formhtml .= "<span class=\"name\">" . $formarray['rows'][$i]['label'] . "</span>\n";
      $formhtml .= n(14);
      $formhtml .= "<input name=\"" . $formarray['rows'][$i]['name'] . "\"";
      $formhtml .= " type=\"" . $formarray['rows'][$i]['type'] . "\"";
      $formhtml .= " value=\"" . $formarray['rows'][$i]['value'] . "\"";
      $formhtml .= " $checked/>\n";
      $formhtml .= n(12) . "</li>\n";
    }
    elseif ($formarray['rows'][$i]['type'] == "submit") {
      $submithtml .= n(12);
      $submithtml .= "<li class=\"button\">\n" . n(14);
      $submithtml .= "<input value=\"" . $formarray['rows'][$i]['value'] . "\"";
      $submithtml .= " type=\"" . $formarray['rows'][$i]['type'] . "\" />\n";
      $submithtml .= n(12) . "</li>\n";
    } elseif ($formarray['rows'][$i]['type'] == "textrow") {
      $formhtml .= n(12);
      $formhtml .= "<li class=\"textrow\">\n" . n(14);
      $formhtml .= $formarray['rows'][$i]['label'];
      //$submithtml .= "<input value=\"" . $formarray['rows'][$i]['value'] . "\"";
      //$submithtml .= " type=\"" . $formarray['rows'][$i]['type'] . "\" />\n";
      $formhtml .= n(12) . "</li>\n";
    }
    //$formhtml .= n(12) . "<br />\n";
  }
  // prevent XSRF (http://shiflett.org/articles/cross-site-request-forgeries#content)
  if (!isset($_SESSION['token_time']))
    $ttime = 0;
  else
    $ttime = $_SESSION['token_time'];
  if (time() - $ttime < 5) // use same token
    $token = $_SESSION['token'];
  else
    $token = md5(uniqid(rand(), TRUE));


  $_SESSION['token'] = $token;
  $_SESSION['token_time'] = time();
  $hiddenhtml .= n(10) . "<input type=\"hidden\" name=\"token\" value=\"$token\" />\n";


  $hiddenhtml = n(8) . "<fieldset>\n" . $hiddenhtml . n(8) . "</fieldset>\n";
  $formhtml .= $submithtml;
  $formhtml .= n(8) . "</ul>\n";
  $formhtml .= $hiddenhtml;
  $formhtml .= n(6) . "</form>\n";
  $formhtml .= n(4) . "</fieldset>\n";

  return $formhtml;
}

function print_pagetitle($title) {
  echo n(4) . "<span class=\"graytitle\">$title</span>\n";
}

function print_memberlist_html($array, $order=SORT_DESC) {
  $listhtml = n(6) . "<ul class=\"pageitem\">\n";
  //$listhtml .= n(8) . "<li class=\"textbox\"><span class=\"header\">Members</span></li>\n";
  $array = array_sort($array, 'balance', $order);
  $count = count($array);
  //for ($i = 0; $i < $count; $i++) {
  foreach ($array as $key => $value) {
    $listhtml .= n(8) . "<li class=\"menu\">\n";
    if ($value['link'] != "")
      $listhtml .= n(10) . "<a href=\"" . $value['link'] . "\">\n";
    $listhtml .= n(10) . "<span class=\"name\">" . $value['name'] . "</span>\n";
    $listhtml .= n(10) . "<span class=\"balance\">\n";
    if ($value['balance'] < 0)
      $class = "_neg";
    else
      $class = "_pos";
    $listhtml .= n(12) . "<span class=\"price$class\">" . number_format($value['balance'],DECIMALS , DSEP,TSEP)  . "</span>\n";
    $listhtml .= n(12) . "<span class=\"euro$class\">&euro;</span>\n";
    $listhtml .= n(10) . "</span>\n";
    if ($value['link'] != "")
      $listhtml .= n(10) . "</a>\n";
    $listhtml .= n(8) . "</li>\n";
  }
  $listhtml .= n(6) . "</ul>";
   echo $listhtml;
}

function create_memberlist_html_table($array, $userList, $name_format, $order=SORT_DESC) {
  $listhtml = "<table>\n";
  $array = array_sort($array, 'unformatted_balance', $order);
  $count = count($array);
  $i=1;
  foreach ($array as $key => $value) {
    $listhtml .= n(2) . "<tr>\n";
    
    $name =  format_name($name_format,$userList[$value['user_id']]['username'], $userList[$value['user_id']]['realname']);
    $listhtml .= n(4) . "<td>" . $i . ".</td>\n";
    $listhtml .= n(4) . "<td>" . $name . "</td>\n";
    $listhtml .= n(4) . "<td>&euro;</td>\n";
    $listhtml .= n(4) . "<td nowrap align=\"right\">" . $value['balance'] . "</td>\n";
    $listhtml .= n(2) . "</tr>\n";
    $i++;
  }
  $listhtml .= "</table>";
   return $listhtml;
}

function print_loginlog_html($array, $order=SORT_DESC) {
  $listhtml = n(6) . "<ul class=\"pageitem\">\n";
  //$listhtml .= n(8) . "<li class=\"textbox\"><span class=\"header\">Members</span></li>\n";
  $array = array_sort($array, 'lastloginunix', $order);
  $count = count($array);
  //for ($i = 0; $i < $count; $i++) {
  foreach ($array as $key => $value) {
    if ($value['lastloginunix'] == 0)
      $value['lastlogin'] = "Not registered:";
    $listhtml .= n(8) . "<li class=\"menu\">\n";
    $listhtml .= n(10) . "<span class=\"name\">" . $value['lastlogin'] . " - " . $value['name'] . "</span>\n";
    $listhtml .= n(8) . "</li>\n";
  }
  $listhtml .= n(6) . "</ul>";
  echo $listhtml;
}

function print_upcoming_events_html($events, $groupid) {
  $listhtml = n(6) . "<ul class=\"pageitem\">\n";
  $listhtml .= n(8) . "<li class=\"textbox\"><span class=\"header\">Upcoming events</span></li>\n";
  /* foreach($events as $key => $value) {
    $listhtml .= n(8) . "<li class=\"textbox\">" . $value['event_name']  . " (".  $value['date']  . ")</li>\n";
    } */
  foreach ($events as $key => $value) {
    $listhtml .= n(8) . "<li class=\"menu_event\">\n";
    $listhtml .= n(10) . "<a href=\"event_detail.php?eventid=" . $value['event_id'] . "\">\n";
    $listhtml .= n(10) . "<span class=\"name\">" . $value['event_name_date'] . "</span>\n";
    $listhtml .= n(10) . "</a>\n";
    $listhtml .= n(8) . "</li>\n";
  }
  $listhtml .= n(8) . "<li class=\"menu\">\n";
  $listhtml .= n(10) . "<a href=\"events.php?groupid=$groupid\">\n";
  $listhtml .= n(10) . "<span class=\"name\"><i>All events</i></span>\n";
  $listhtml .= n(10) . "</a>\n";
  $listhtml .= n(8) . "</li>\n";
  $listhtml .= n(6) . "</ul>";
  echo $listhtml;
}

function print_all_events_html($events) {
  $listhtml = n(6) . "<ul class=\"pageitem\">\n";
  $listhtml .= n(8) . "<li class=\"textbox\"><span class=\"header\">Event list</span></li>\n";
  /* foreach($events as $key => $value) {
    $listhtml .= n(8) . "<li class=\"textbox\">" . $value['event_name']  . " (".  $value['date']  . ")</li>\n";
    } */
  foreach ($events as $key => $value) {
    $listhtml .= n(8) . "<li class=\"menu_event\">\n";
    $listhtml .= n(10) . "<a href=\"event_detail.php?eventid=" . $value['event_id'] . "\">\n";
    $listhtml .= n(10) . "<span class=\"name\">" . $value['event_name_date'] . "</span>\n";
    $listhtml .= n(10) . "</a>\n";
    $listhtml .= n(8) . "</li>\n";
  }
  $listhtml .= n(6) . "</ul>";
  echo $listhtml;
}

function print_expenselist_html($array, $user) {
  $listhtml = n(6) . "<ul class=\"pageitem\">\n";
  //$listhtml .= n(8) . "<li class=\"textbox\"><span class=\"header\">Members</span></li>\n";
  $count = count($array);
  $total = 0;
  for ($i = 0; $i < $count; $i++) {
    // total uitrekenen
    $total += $array[$i]['amount'];
  }/*
    $listhtml .= n(8) . "<li class=\"textbox\"><span class=\"name\">Total:</span>\n";
    $listhtml .= n(10). "<span class=\"expense_balance\">\n";
    $listhtml .= n(12). "<span class=\"expense_amount". $array[$i]['bsign'] . "\">" . number_format($total,DECIMALS , DSEP,TSEP)  . "</span>\n";
    $listhtml .= n(12). "<span class=\"euro". $array[$i]['bsign'] . "\">&euro;</span>\n";
    $listhtml .= n(10). "</span></li>\n"; */

  for ($i = 0; $i < $count; $i++) {
    // if (!empty($array[$i]['username'])) $uname = " (" . $array[$i]['username'] . ")";
    // else $uname = "";
    $name = format_name($user, $array[$i]['username'], $array[$i]['realname']);

    $listhtml .= n(8) . "<li class=\"menu_expense\">\n";
    $listhtml .= n(10) . "<a href=\"http://" . $_SERVER['HTTP_HOST'] . DIR . "expense_detail.php?expid=" . $array[$i]['expense_id'] . "\">\n";
    $listhtml .= n(10) . "<span class=\"expense_descr\">" . $array[$i]['description'] . "</span>\n";
    $listhtml .= n(10) . "<span class=\"expense_owner\">" . $array[$i]['date'] . " $name</span>\n";
    $listhtml .= n(10) . "<span class=\"expense_balance\">\n";
    $listhtml .= n(12) . "<span class=\"expense_amount" . $array[$i]['bsign'] . "\">" . number_format($array[$i]['amount'], DECIMALS , DSEP,TSEP) . "</span>\n";

    $listhtml .= n(12) . "<span class=\"euro" . $array[$i]['bsign'] . "\">&euro;</span>\n";
    $listhtml .= n(10) . "</span>\n";
    $listhtml .= n(10) . "</a>\n";
    $listhtml .= n(8) . "</li>\n";
  }
  $listhtml .= n(6) . "</ul>";
  echo $listhtml;
}

function print_expenselist_excel($array, $groupid) {
  // http://www.appservnetwork.com/modules.php?name=News&file=article&sid=8
  $members = get_groupmembers($groupid);
  $mcount = count($members);

  // construct array to hold all expense id's per member
  for ($j = 0; $j < $mcount; $j++) {
    $expids[$members[$j]['user_id']] = get_user_expenses_idonly($members[$j]['user_id']);
  }

  // Send Header
  header("Pragma: public");
  header("Expires: 0");
  header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
  header("Content-Type: application/force-download");
  header("Content-Type: application/octet-stream");
  header("Content-Type: application/download");
  ;
  header("Content-Disposition: attachment;filename=test.xls ");
  header("Content-Transfer-Encoding: binary ");

  // XLS Data Cell
  $result = mysql_db_query($dbname, "select id,prename,name,sname,grade from appdata where course='$courseid' and sec='$section'");
  xlsBOF();
  xlsWriteLabel(0, 0, "ID");
  xlsWriteLabel(0, 1, "Date");
  xlsWriteLabel(0, 2, "Type");
  xlsWriteLabel(0, 3, "Description");
  xlsWriteLabel(0, 4, "Amount");
  xlsWriteLabel(0, 5, "Paid by");
  for ($j = 0; $j < $mcount; $j++) {
    if (!empty($members[$j]['username']))
      $uname = " (" . $members[$j]['username'] . ")";
    else
      $uname = "";
    xlsWriteLabel(0, 6 + $j, $members[$j]['realname'] . $uname);
  }

  $xlsRow = 1;
  $count = count($array);
  for ($i = 0; $i < $count; $i++) {
    if (!empty($array[$i]['username']))
      $uname = " (" . $array[$i]['username'] . ")";
    else
      $uname = "";
    xlsWriteNumber($xlsRow, 0, $array[$i]['expense_id']);
    xlsWriteLabel($xlsRow, 1, $array[$i]['date']);
    xlsWriteLabel($xlsRow, 2, $array[$i]['type_name']);
    xlsWriteLabel($xlsRow, 3, $array[$i]['description']);
    xlsWriteNumber($xlsRow, 4, $array[$i]['amount']);
    xlsWriteLabel($xlsRow, 5, $array[$i]['realname'] . $uname);
    $x = 0;
    for ($j = 0; $j < $mcount; $j++) {
      // check number of participants for the expense
      if (in_array($array[$i]['expense_id'], $expids[$members[$j]['user_id']])) {
        $x +=1;
      }
    }
    for ($j = 0; $j < $mcount; $j++) {
      // write expense/person for each member
      if (in_array($array[$i]['expense_id'], $expids[$members[$j]['user_id']])) {
        xlsWriteNumber($xlsRow, 6 + $j, number_format(($array[$i]['amount'] / $x), DECIMALS , DSEP,TSEP)) ;
      } else {
        xlsWriteLabel($xlsRow, 6 + $j, "");
      }
    }
    $xlsRow++;
  }
  xlsEOF();
  exit();
}

function xlsBOF() {
  echo pack("ssssss", 0x809, 0x8, 0x0, 0x10, 0x0, 0x0);
  return;
}

function xlsEOF() {
  echo pack("ss", 0x0A, 0x00);
  return;
}

function xlsWriteNumber($Row, $Col, $Value) {
  echo pack("sssss", 0x203, 14, $Row, $Col, 0x0);
  echo pack("d", $Value);
  return;
}

function xlsWriteLabel($Row, $Col, $Value) {
  $L = strlen($Value);
  echo pack("ssssss", 0x204, 8 + $L, $Row, $Col, 0x0, $L);
  echo $Value;
  return;
}

function print_grouplist_html($array) {
  $listhtml = n(6) . "<ul class=\"pageitem\">\n";
  //$listhtml .= n(8) . "<li class=\"textbox\"><span class=\"header\">Members</span></li>\n";
  $count = count($array);
  for ($i = 0; $i < $count; $i++) {
    $listhtml .= n(8) . "<li class=\"menu\">\n";
    $listhtml .= n(10) . "<a href=\"" . $array[$i]['link'] . "\">\n";
    $listhtml .= n(12) . "<span class=\"grouplist_name\">" . $array[$i]['group_name'] . "</span>\n";
    $listhtml .= n(12) . "<span class=\"grouplist_descr\">" . $array[$i]['description'] . "</span>\n";
    $listhtml .= n(12) . "<span class=\"balance\">\n";
    if ($array[$i]['balance'] < 0)
      $class = "_neg";
    else
      $class = "_pos";
    $listhtml .= n(14) . "<span class=\"price$class\">" . $array[$i]['balance'] . "</span>\n";
    $listhtml .= n(14) . "<span class=\"euro$class\">&euro;</span>\n";
    $listhtml .= n(12) . "</span>\n";
    $listhtml .= n(10) . "</a>\n";
    $listhtml .= n(8) . "</li>\n";
  }
  $listhtml .= n(6) . "</ul>";
  echo $listhtml;
}

function print_group_balance_list_html($array, $order=SORT_DESC) {
  //$array = array_sort($array, 'balance', $order);  
  $count = count($array);
  for ($i = 0; $i < $count; $i++) {
    $listhtml .= n(6) . "<ul class=\"pageitem\">\n";
    if ($array[$i]['canedit']) {
      $link = "<a href=\"group_edit.php?groupid=" . $array[$i]['group_id'] . "\">\n";
      $listhtml .= n(8) . "<li class=\"textbox\">$link<span class=\"header\">" . $array[$i]['group_name'] . "</span></a></li>\n";
    }
    else
      $listhtml .= n(8) . "<li class=\"textbox\"><span class=\"header\">" . $array[$i]['group_name'] . "</span></li>\n";
    $listhtml .= n(8) . "<li class=\"menu\">\n";
    if ($array[$i]['paid'] != "0.00")
      $listhtml .= n(10) . "<a href=\"" . $array[$i]['link'] . "&xtype=pos\">\n";
    $listhtml .= n(10) . "<span class=\"name\">Paid</span>\n";
    $listhtml .= n(10) . "<span class=\"balance\">\n";
    $listhtml .= n(12) . "<span class=\"price_pos\">" . $array[$i]['paid'] . "</span>\n";
    $listhtml .= n(12) . "<span class=\"euro_pos\">&euro;</span>\n";
    $listhtml .= n(10) . "</span>\n";
    if ($array[$i]['paid'] != "0.00")
      $listhtml .= n(10) . "</a>\n";
    $listhtml .= n(8) . "</li>\n";

    $listhtml .= n(8) . "<li class=\"menu\">\n";
    if ($array[$i]['expenses'] != "0.00")
      $listhtml .= n(10) . "<a href=\"" . $array[$i]['link'] . "&xtype=neg\">\n";
    $listhtml .= n(10) . "<span class=\"name\">Expenses</span>\n";
    $listhtml .= n(10) . "<span class=\"balance\">\n";
    $listhtml .= n(12) . "<span class=\"price_neg\">" . $array[$i]['expenses'] . "</span>\n";
    $listhtml .= n(12) . "<span class=\"euro_neg\">&euro;</span>\n";
    $listhtml .= n(10) . "</span>\n";
    if ($array[$i]['expenses'] != "0.00")
      $listhtml .= n(10) . "</a>\n";
    $listhtml .= n(8) . "</li>\n";

    $listhtml .= n(8) . "<li class=\"menu\">\n";
    if ($array[$i]['expenses'] != "0.00" || $array[$i]['paid'] != "0.00")
      $listhtml .= n(10) . "<a href=\"" . $array[$i]['link'] . "\">\n";
    $listhtml .= n(10) . "<span class=\"name\">Balance</span>\n";
    $listhtml .= n(10) . "<span class=\"balance\">\n";
    if ($array[$i]['balance'] < 0)
      $class = "_neg";
    else
      $class = "_pos";
    $listhtml .= n(12) . "<span class=\"price$class\">" . $array[$i]['balance'] . "</span>\n";
    $listhtml .= n(12) . "<span class=\"euro$class\">&euro;</span>\n";
    $listhtml .= n(10) . "</span>\n";
    if ($array[$i]['expenses'] != "0.00" || $array[$i]['paid'] != "0.00")
      $listhtml .= n(10) . "</a>\n";
    $listhtml .= n(8) . "</li>\n";

    $listhtml .= n(6) . "</ul>";
  }
  echo $listhtml;
}

function print_topbar($bararray) {
  // array structure: $bararray['title'], $bararray['leftnav'][$i][name|url], $bararray['rightnav'][$i][name|url]
  if (!is_array($bararray)) {
    $barhtml = n(4) . "<div id=\"topbar\">\n";
    $barhtml .= n(6) . "<div id=\"title\">" . $bararray . "</div>\n";
    $barhtml .= n(4) . "</div>\n";
    echo $barhtml;
    return;
  }

  $barhtml = n(4) . "<div id=\"topbar\">\n";
  if (isset($bararray['title'])) {
    $barhtml .= n(6) . "<div id=\"title\">" . $bararray['title'] . "</div>\n";
  }
  if (isset($bararray['leftnav'])) {
    $barhtml .= n(6) . "<div id=\"leftnav\">\n";
    $button_count = count($bararray['leftnav']);
    for ($i = 0; $i < $button_count; $i++) {
      $barhtml .= n(8) . "<a href=\"" . $bararray['leftnav'][$i]['url'] . "\">" . $bararray['leftnav'][$i]['name'] . "</a>\n";
    }
    $barhtml .= n(6) . "</div>\n";
  }
  if (isset($bararray['rightnav'])) {
    $barhtml .= n(6) . "<div id=\"rightnav\">\n";
    $buttoncount = count($bararray['rightnav']);
    for ($i = 0; $i < $button_count; $i++) {
      $barhtml .= n(8) . "<a href=\"" . $bararray['rightnav'][$i]['url'] . "\">" . $bararray['rightnav'][$i]['name'] . "</a>\n";
    }
    $barhtml .= n(6) . "</div>\n";
  }
  $barhtml .= n(4) . "</div>\n";
  echo $barhtml;
}

function print_topbutton_html($name, $url) {
  $expensebutton = n(4) . "<div id=\"topbutton\">\n";
  $expensebutton .= n(6) . "<a href=\"$url\">$name</a>\n";
  $expensebutton .= n(4) . "</div>\n";
  echo $expensebutton;
}

function print_pageitem_text_html($title, $items="") {
  $itemhtml = n(4) . "<fieldset>\n";
  $itemhtml .= n(6) . "<ul class=\"pageitem\">\n";
  $itemhtml .= n(8) . "<li class=\"textbox\"><span class=\"header\">$title</span></li>\n";
  if (is_array($items)) {
    foreach ($items as $key => $value) {
      $itemhtml .= n(8) . "<li class=\"textbox\">$value</li>\n";
    }
  } elseif ($items != "") {
    $itemhtml .= n(8) . "<li class=\"textbox\">$items</li>\n";
  }
  $itemhtml .= n(6) . "</ul>\n";
  $itemhtml .= n(4) . "</fieldset>\n";
  echo $itemhtml;
}

function print_profile_html($profile, $ownuid) {
  $itemhtml .= n(4) . "<fieldset>\n";
  $itemhtml .= n(6) . "<ul class=\"pageitem\">\n";
  $itemhtml .= n(8) . "<li class=\"textbox\"><span class=\"header\">Profile</span></li>\n";
  if ($profile['activated'] == 1) {
    $itemhtml .= n(8) . "<li class=\"textbox\">Username: " . $profile['username'] . "</li>\n";
    $itemhtml .= n(8) . "<li class=\"textbox\">Name: " . $profile['realname'] . "</li>\n";
    $itemhtml .= n(8) . "<li class=\"textbox\">Email: " . $profile['email'] . "</li>\n";
    $itemhtml .= n(8) . "<li class=\"textbox\">Registered user: yes</li>\n";
    $itemhtml .= n(8) . "<li class=\"textbox\">Registration date: " . $profile['regdate'] . "</li>\n";
    $itemhtml .= n(8) . "<li class=\"textbox\">Last login date: " . $profile['lastlogin'] . "</li>\n";
  } else {
    $itemhtml .= n(8) . "<li class=\"textbox\">Name: " . $profile['realname'] . "</li>\n";
    $itemhtml .= n(8) . "<li class=\"textbox\">Email: " . $profile['email'] . "</li>\n";
    $itemhtml .= n(8) . "<li class=\"textbox\">Registered user: no</li>\n";
  }
  if ($profile['user_id'] == $ownuid) {
    $itemhtml .= n(8) . "<li class=\"textbox\">Display names as: " . get_name_format($profile['name_format']) . "</li>\n";

    if ($profile['email_notify'] == 0)
      $enotify = "always";
    elseif ($profile['email_notify'] == -1)
      $enotify = "none";
    else
      $enotify = "Expenses of " . number_format($profile['email_notify'], DECIMALS , DSEP,TSEP)  . " or more.";
    $itemhtml .= n(8) . "<li class=\"textbox\">Email notification: $enotify</li>\n";
  }
  /*
    foreach ($profile as $key => $value) {
    if ($key != "password" && $key != "confirmation" && $key != "user_id") {
    if ($key == 'activated' && $hideactive == false)  $itemhtml .= n(8) . "<li class=\"textbox\">Registered user: yes</li>\n";
    else $itemhtml .= n(8) . "<li class=\"textbox\">$key: $value</li>\n";
    }
    } */
  $itemhtml .= n(6) . "</ul>\n";
  $itemhtml .= n(4) . "</fieldset>\n";
  echo $itemhtml;
}

function print_achievement_html($acList,$owngroupids,$grouplist) {

  $groupnames = array();
  foreach ($grouplist as $g)
    $groupnames[$g['group_id']] = $g['group_name'];
  
  
  $starthtml = n(4) . "<fieldset>\n";
  $starthtml .= n(6) . "<ul class=\"pageitem\">\n";
  $starthtml .= n(8) . "<li class=\"textbox\"><span class=\"header\">Achievements %groupname</span></li>\n";

  $itemhtml = n(8) . "<li class=\"textbox\">";
  $itemhtml .= n(10) . "<a href=\"http://" . $_SERVER['HTTP_HOST'] . DIR . "achievement.php?acid=%acid\">\n";
  $itemhtml .= n(10) . "<span>%name";
  $itemhtml .= n(10) . "</span>\n";
  $itemhtml .= n(10) . "</a>\n";
  $itemhtml .= n(8) . "</li>\n";

  $endhtml = n(6) . "</ul>\n";
  $endhtml.= n(4) . "</fieldset>\n";

  $groups = array();
  foreach ($acList as $ac) {
    if (!array_key_exists($ac['group_id'], $groups)) {
      $start = str_replace('%groupname', $groupnames[$ac['group_id']], $starthtml);
      $groups[$ac['group_id']] = $start;
    }
    $add = str_replace('%acid', $ac['id'], $itemhtml);
    $addd = str_replace('%name', $ac['name'], $add);
    $groups[$ac['group_id']] .= $addd;
  }
  $html = '';
  foreach ($groups as $group) {
    $html .= $group . $endhtml;
  }
  
  foreach ($grouplist as $g) {
    if (!array_key_exists($g['group_id'], $groups) && in_array($g['group_id'],$owngroupids)) {
      // add no achievements
      $start = str_replace('%groupname', $groupnames[$g['group_id']], $starthtml);
        $itemhtml = n(8) . "<li class=\"textbox\">";
        $itemhtml .= n(10) . "<span>No achievements :-(";
        $itemhtml .= n(10) . "</span>\n";
        $itemhtml .= n(8) . "</li>\n";
      $html .= $start . $itemhtml . $endhtml;
    }
  }
  

  echo $html;
}

function print_achievementdetails_html($details, $user, $groupdetails) {
  $name = format_name($user, $details['username'], $details['realname']);
  switch ($details['value_type']) {
     case "days":
       $val = " with " . $details['value'] . " days";
       break;
     case "amount":
       $val = " with &euro;" . number_format($details['value'], DECIMALS , DSEP,TSEP);
       break;
     case "count":
       $val = " with " .$details['value'] . " times" ;
       break;
  }
  
  $itemhtml .= n(4) . "<fieldset>\n";
  $itemhtml .= n(6) . "<ul class=\"pageitem\">\n";
  $itemhtml .= n(8) . "<li class=\"textbox\"><span class=\"header\">${details['name']}</span></li>\n";
  $itemhtml .= n(8) . "<li class=\"textbox\">Awarded to {$name}{$val} </li>\n";
  $itemhtml .= n(8) . "<li class=\"textbox\">In group {$groupdetails['name']} </li>\n";
  $itemhtml .= n(8) . "<li class=\"textbox\">{$details['description']}</li>\n";
  $itemhtml .= n(6) . "</ul>\n";
  $itemhtml .= n(4) . "</fieldset>\n";
  echo $itemhtml;
}

function print_expensedetails_html($details, $user) {
  $mcount = 0;
  $ownids = get_user_expenses_idonly($user->data['user_id']);
  foreach ($details['members'] as $key => $value) {
    if (!empty($value['username']))
      $uname = " (" . $value['username'] . ")";
    else
      $uname = "";
    $memberhtml .= n(8) . "<li class=\"textbox\">" . $value['realname'] . $uname . "</li>\n";
    $mcount++;
  }
  $expdep = 'Expense';
  $expdepname = "Paid by: ";
  if ($details['typeid'] == 0) {
    $expdep = 'Deposit';
    $expdepname = "Deposit holder: ";
  }
  $itemhtml .= n(4) . "<fieldset>\n";
  $itemhtml .= n(6) . "<ul class=\"pageitem\">\n";
  $itemhtml .= n(8) . "<li class=\"textbox\"><span class=\"header\">${expdep} Details</span></li>\n";
  //if ($details['eventname']) $itemhtml .= n(8) . "<li class=\"textbox\">Event: " . $details['eventname'] . "</li>\n";  
  if ($details['eventname']) {
    $itemhtml .= n(8) . "<li class=\"textbox\">Event: <a href=\"event_detail.php?eventid=" . $details['eventid'] . "\">" . $details['eventname'] . "</a></li>\n";
  }


  if ($details['eventdescr'])
    $itemhtml .= n(8) . "<li class=\"textbox\">Event descr: " . $details['eventdescr'] . "</li>\n";
  if ($details['description'])
    $itemhtml .= n(8) . "<li class=\"textbox\">{$expdep} descr: " . $details['description'] . "</li>\n";
  if (!in_array($details['expense_id'], $ownids) && $details['ownerid'] != $user->data['user_id'] && $details['typeid'] == 3)
    $itemhtml .= n(8) . "<li class=\"textbox\">Amount: -</li>\n";
  if ($details['typeid'] == 0)
    $itemhtml .= n(8) . "<li class=\"textbox\">Total deposit: " . number_format(($details['amount'] * $mcount), DECIMALS , DSEP,TSEP)  . " (" . $details['amount'] . " per person)</li>\n";
  else
    $itemhtml .= n(8) . "<li class=\"textbox\">Amount: " .  number_format($details['amount'], DECIMALS , DSEP,TSEP) . " (" . number_format(($details['amount'] / $mcount), DECIMALS , DSEP,TSEP) . " per person)</li>\n";

  //$itemhtml .= n(8) . "<li class=\"textbox\">Amount: " . $details['amount'] . " (" . number_format(($details['amount']/$mcount),DECIMALS , DSEP,TSEP) . " per person)</li>\n";  
  $itemhtml .= n(8) . "<li class=\"textbox\">Group: " . $details['groupname'] . "</li>\n";
  $itemhtml .= n(8) . "<li class=\"textbox\">Group description: " . $details['groupdescr'] . "</li>\n";
  if (!empty($details['ownerusername']))
    $uname = " (" . $details['ownerusername'] . ")";
  else
    $uname = "";
  $name = format_name($user, $details['ownerusername'], $details['ownerreal']);
  //$itemhtml .= n(8) . "<li class=\"textbox\">Paid by: " . $details['ownerreal'] . $uname . "</li>\n";  


  $itemhtml .= n(8) . "<li class=\"textbox\">{$expdepname}{$name}</li>\n";
  $itemhtml .= n(8) . "<li class=\"textbox\">Type: " . $details['type'] . "</li>\n";
  $itemhtml .= n(8) . "<li class=\"textbox\">Expense date: " . $details['expense_date'] . "</li>\n";
  $itemhtml .= n(8) . "<li class=\"textbox\">Submit date: " . $details['timestamp'] . "</li>\n";
  $parthold = 'Participants';
  if ($details['typeid'] == 0)
    $parthold = 'Deposit Participants';
  $itemhtml .= n(8) . "<li class=\"textbox\"><span class=\"header\">${parthold}</span></li>\n";
  $itemhtml .= $memberhtml;
  $itemhtml .= n(6) . "</ul>\n";
  $itemhtml .= n(4) . "</fieldset>\n";
  echo $itemhtml;
}

/*
  $details['eventid'] = $eventid;
  $details['type'] = $row['type'];
  $details['typeid'] = $row['typeid'];
  $details['organizerid'] = $row['organizer_id'];
  $details['organizerusername'] = $row['username'];
  $details['organizerreal'] = $row['realname'];
  $details['groupid'] = $row['group_id'];
  $details['groupname'] = $row['groupname'];
  $details['groupdescr'] = $row['groupdescr'];
  $details['eventname'] = $row['event_name'];
  $details['eventdescr'] = $row['event_description'];
  $details['expcount'] = $row['expcount'];
  $details['event_date'] = date('D j M Y',$row['date']+4000);
  //$details['expense_date'] = $row['expense_date'];
  $details['event_date_unix'] = $row['date'];
 */

function print_eventdetails_html($details, $user) {
  $mcount = 0;
  foreach ($details['members'] as $key => $value) {
    if (!empty($value['username']))
      $uname = " (" . $value['username'] . ")";
    else
      $uname = "";
    $memberhtml .= n(8) . "<li class=\"textbox\">" . $value['realname'] . $uname . "</li>\n";
    $mcount++;
  }
  $itemhtml .= n(4) . "<fieldset>\n";
  $itemhtml .= n(6) . "<ul class=\"pageitem\">\n";
  $itemhtml .= n(8) . "<li class=\"textbox\"><span class=\"header\">Event Details</span></li>\n";
  $itemhtml .= n(8) . "<li class=\"textbox\">Event: " . $details['eventname'] . "</li>\n";
  $itemhtml .= n(8) . "<li class=\"textbox\">Description: " . $details['eventdescr'] . "</li>\n";
  $itemhtml .= n(8) . "<li class=\"textbox\">Date: " . $details['event_date'] . "</li>\n";
  $name = format_name($user, $details['organizerusername'], $details['organizerreal']);
  //$itemhtml .= n(8) . "<li class=\"textbox\">Paid by: " . $details['ownerreal'] . $uname . "</li>\n";  
  $itemhtml .= n(8) . "<li class=\"textbox\">Submitted by: $name</li>\n";
  $itemhtml .= n(8) . "<li class=\"textbox\">Default expense: " . $details['type'] . "</li>\n";
  $itemhtml .= n(8) . "<li class=\"textbox\">Expense total: " . $details['expsum'] . " (" . $details['expcount'] . " expenses)</li>\n";
  $itemhtml .= n(8) . "<li class=\"textbox\"><span class=\"header\">Participants</span></li>\n";
  $itemhtml .= $memberhtml;
  $itemhtml .= n(6) . "</ul>\n";
  $itemhtml .= n(4) . "</fieldset>\n";
  echo $itemhtml;
}

function get_last_active_group($userid) {
  $sql = "SELECT group_id
          FROM users_expenses, expenses WHERE users_expenses.user_id = $userid 
          AND users_expenses.expense_id = expenses.expense_id order by timestamp DESC LIMIT 1";
  if ((!$result = mysql_query($sql)) || (mysql_num_rows($result) == 0) || (mysql_num_rows($result) > 1)) {
    return false;
  } else {
    while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
      $groupid = $row['group_id'];
    }
  }
  return $groupid;
}

function array_sort($array, $on, $order=SORT_ASC) {
  // http://php.net/manual/en/function.sort.php
  $new_array = array();
  $sortable_array = array();

  if (count($array) > 0) {
    foreach ($array as $k => $v) {
      if (is_array($v)) {
        foreach ($v as $k2 => $v2) {
          if ($k2 == $on) {
            $sortable_array[$k] = $v2;
          }
        }
      } else {
        $sortable_array[$k] = $v;
      }
    }

    switch ($order) {
      case SORT_ASC:
        asort($sortable_array);
        break;
      case SORT_DESC:
        arsort($sortable_array);
        break;
    }

    foreach ($sortable_array as $k => $v) {
      $new_array[$k] = $array[$k];
    }
  }

  return $new_array;
}

function get_user_profile($userid) {
  // TODO only allow a user to get a profile from people in his groups
  //$sql = "SELECT * FROM users WHERE user_id = $userid LIMIT 1";
  $sql = "SELECT users.*, name_format, email_notify 
  		  FROM users LEFT JOIN preferences ON users.user_id = preferences.user_id 
  		  WHERE users.user_id=$userid ";
  if (!$result = mysql_query($sql)) {
    return false;
  } else {
    while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
      $profile['user_id'] = $userid;
      $profile['username'] = $row['username'];
      $profile['realname'] = $row['realname'];
      $profile['email'] = $row['email'];
      $profile['password'] = $row['password'];
      $profile['activated'] = $row['activated'];
      $profile['confirmation'] = $row['confirmation'];
      $profile['regdate'] = date("j M Y", $row['reg_date']);
      $profile['lastlogin'] = date("j M Y", $row['last_login']);
      $profile['name_format'] = $row['name_format'];
      $profile['email_notify'] = $row['email_notify'];
    }
  }
  return $profile;
}

function get_name_format($formatid=1, $list=false) {
  if ($list) {
    $listarray[1] = "Name (username)";
    $listarray[2] = "Name";
    $listarray[3] = "username (Name)";
    $listarray[4] = "username";
    return $listarray;
  }
  if ($formatid < 1 || $formatid > 4)
    $formatid = 1;
  switch ($formatid) {
    case "1":
      return("Name (username)");
      break;

    case "2":
      return("Name");
      break;

    case "3":
      return("username (Name)");
      break;

    case "4":
      return("username");
      break;
  }
}

function format_name($user, $username=false, $name=false) {
  // name formats
  // 1: Name (username)
  // 2: Name 
  // 3: username (Name)
  // 4: username
  if (is_object($user)) {
    if (!isset($user->data['name_format']) || $user->data['name_format'] > 4 || $user->data['name_format'] < 1)
      $format = 1;
    else
      $format = $user->data['name_format'];
  } else {
      $format = $user;
  }

  switch ($format) {
     case "0":
      if ($username)
        $uname = " ($username)";
      else
        $uname = "";
      return $name . $uname;
      break;
      
    case "1":
      if ($username)
        $uname = " ($username)";
      else
        $uname = "";
      return $name . $uname;
      break;

    case "2":
      return $name;
      break;

    case "3":
      if ($name)
        $uname = " ($name)";
      else
        $uname = "";
      return $username . $uname;
      break;

    case "4":
      if ($username)
        return $username;
      else
        return $name;
      break;
  }
}

function save_page_history($url) {
  $patterns[0] = '/&msg=../';
  $replacements[0] = '';
  $url = preg_replace($patterns, $replacements, $url);
  $key = FALSE;
  if (is_array($_SESSION['pagehistory']))
    $key = array_search($url, $_SESSION['pagehistory']);
  if ($key) {
    $count = count($_SESSION['pagehistory']);
    for ($i = $key; $i < $count; $i++) {
      unset($_SESSION['pagehistory'][$i]);
    }
    $_SESSION['pagehistory'] = array_values($_SESSION['pagehistory']);
  }
  $_SESSION['pagehistory'][] = $url;
}

function get_back_page() {
  $back['url'] = $_SESSION['pagehistory'][(count($_SESSION['pagehistory']) - 2)];
  if (strpos($back['url'], "group_detail.php"))
    $back['name'] = "Group";
  elseif (strpos($back['url'], "profile.php"))
    $back['name'] = "Profile";
  elseif (strpos($back['url'], "expenses.php"))
    $back['name'] = "Exp";
  elseif (strpos($back['url'], "events.php"))
    $back['name'] = "Events";
  else
    $back['name'] = "Back";
  return $back;
}

function delete_group($groupid, $action="delete") {

  // copy expenses to del
  // copy user_expenses to del
  // copy user_groups to del
  // copy groups to del
  //insert into table(col1,col2,col3,col4) select col1,col2,col3,col4 from table where condition;

  if ($action == "delete") {
    $a = "_del";
    $del_date = ", `del_date`";
    $now = ", NOW()";
    $b = "";
  } elseif ($action == "restore") {
    $a = "";
    $del_date = "";
    $now = "";
    $b = "_del";
  }

  // backup all records to $a tables
  $sql = "INSERT INTO `expenses$a` (`expense_id`, `type`, `user_id`, `group_id`, `description`, `amount`, `expense_date`, `event_id`, `timestamp`, `currency`$del_date)
          SELECT `expense_id`, `type`, `user_id`, `group_id`, `description`, `amount`, `expense_date`, `event_id`, `timestamp`, `currency`$now FROM `expenses$b`
          WHERE group_id = $groupid";
  if (!$result = mysql_query($sql)) {
    return false;
  }
  $sql = "INSERT INTO `users_expenses$a` (`user_id`, `expense_id`$del_date)
          SELECT `user_id`, `expense_id`$now FROM `expenses$b`
          WHERE `expense_id`  in ( SELECT `expense_id` FROM `expenses$b` WHERE group_id = $groupid)";
  if (!$result = mysql_query($sql)) {
    return false;
  }
  $sql = "INSERT INTO `users_groups$a` (`user_id`, `group_id`, `role_id`, `join_date`$del_date) 
          SELECT `user_id`, `group_id`, `role_id`, `join_date`$now FROM `users_groups$b`
          WHERE group_id = $groupid";
  if (!$result = mysql_query($sql)) {
    return false;
  }
  $sql = "INSERT INTO `groups$a` (`group_id`, `name`, `description`, `reg_date`$del_date)
          SELECT `group_id`, `name`, `description`, `reg_date`$now FROM `groups$b`
          WHERE group_id = $groupid";
  if (!$result = mysql_query($sql)) {
    return false;
  }

//  DELETE FROM `goingdutch`.`users_expenses_del` WHERE `users_expenses_del`.`user_id` = 11 AND `users_expenses_del`.`expense_id` = 30
  // delete all records 
  $sql = "DELETE FROM `users_expenses$b` 
          WHERE `expense_id`  in ( SELECT `expense_id` FROM `expenses$b` WHERE group_id = $groupid)";
  if (!$result = mysql_query($sql)) {
    return false;
  }
  $sql = "DELETE FROM `expenses$b`
          WHERE group_id = $groupid";
  if (!$result = mysql_query($sql)) {
    return false;
  }
  $sql = "DELETE FROM `users_groups$b`
          WHERE group_id = $groupid";
  if (!$result = mysql_query($sql)) {
    return false;
  }
  $sql = "DELETE FROM `groups$b`
          WHERE group_id = $groupid";
  if (!$result = mysql_query($sql)) {
    return false;
  }
  return true;
}

function remove_member_from_group($groupid, $uid) {
  // set removed flag for this member
  $sql = "UPDATE `users_groups` SET `removed` = '1' WHERE `users_groups`.`user_id` = $uid AND `users_groups`.`group_id` = $groupid";
  if (!$result = mysql_query($sql)) {
    return false;
  }
  return true;
}

function check_even_balance($listarray) {
  // check if one balance can wipe the other
  $expense_array = false;
  $stop = false;
  foreach ($listarray as $key => $value) {
    foreach ($listarray as $key2 => $value2) {
      ////echo "$key:$key2 - " . $value['balance'] ." == " .  $value2['balance'] ."<br>";
      if ($key != $key2 && $value['balance'] == -$value2['balance']) {
        //The expression (expr1) ? (expr2) : (expr3) evaluates to expr2 if expr1 evaluates to TRUE, and expr3 if expr1 evaluates to FALSE. 
        if ($value['balance'] > $value2['balance']) {
          $expense_array['pay_uid'] = $value2['user_id'];
          $expense_array['pay_name'] = $value2['name'];
          $expense_array['pay_key'] = $key2;
          $expense_array['get_uid'] = $value['user_id'];
          $expense_array['get_name'] = $value['name'];
          $expense_array['get_key'] = $key;
        } else {
          $expense_array['pay_uid'] = $value['user_id'];
          $expense_array['pay_name'] = $value['name'];
          $expense_array['pay_key'] = $key;
          $expense_array['get_uid'] = $value2['user_id'];
          $expense_array['get_name'] = $value2['name'];
          $expense_array['get_key'] = $key2;
        }

        $expense_array['sum'] = abs($value['balance']);
        ////echo "key $key and key $key2 are even balance";
        unset($listarray[$key]);
        unset($listarray[$key2]);
        $i++;
        $stop = true;
        break;
      }
    }
    if ($stop)
      break;
  }
  return $expense_array;
}

function check_even_balance_next_round($listarray) {
  $lastkey = false;
  $stop = false;
  foreach ($listarray as $key => $value) {
    //$temparray = $listarray;

    if ($lastkey) {
      $temparray = $listarray;

      foreach ($temparray as $key3 => $value3) {
        if ($key3 != $lastkey) {
          ($listarray[$lastkey]['balance'] < 0) ? $temparray[$key3]['balance'] -= $listarray[$lastkey]['balance'] : $temparray[$key3]['balance'] += $listarray[$lastkey]['balance'];
          unset($temparray[$lastkey]);
          // //echo "Checking KEY: $key, UID: ".$listarray[$key]['uid'].", balance of UID " .$listarray[$lastkey]['uid'] . " (key $lastkey) set to zero (balanced with uid " . $temparray[$key3]['uid'] ." (key $key3)<br>";
          // //echo "Running through this temparray: ";
          ////printr($temparray);
          $expense_array2[1] = check_even_balance($temparray);
          if ($expense_array2[1] != false) {
            if ($listarray[$lastkey]['balance'] > $temparray[$key3]['balance']) {
              $expense_array2[0]['pay_uid'] = $temparray[$key3]['user_id'];
              $expense_array2[0]['pay_name'] = $temparray[$key3]['name'];
              $expense_array2[0]['pay_key'] = $key3;
              $expense_array2[0]['get_uid'] = $listarray[$lastkey]['user_id'];
              $expense_array2[0]['get_name'] = $listarray[$lastkey]['name'];
              $expense_array2[0]['get_key'] = $lastkey;
            } else {
              $expense_array2[0]['pay_uid'] = $listarray[$lastkey]['user_id'];
              $expense_array2[0]['name'] = $listarray[$lastkey]['name'];
              $expense_array2[0]['pay_key'] = $lastkey;
              $expense_array2[0]['get_uid'] = $temparray[$key3]['user_id'];
              $expense_array2[0]['get_name'] = $temparray[$key3]['name'];
              $expense_array2[0]['get_key'] = $key3;
            }
            $expense_array2[0]['sum'] = abs($listarray[$lastkey]['balance']);
            //echo "FOUND<br>";
            ////printr($expense_array2);
            $return_array[] = $expense_array2;
            //$stop = true;
            //return ($expense_array2);
            //break;
          }

          //foreach ($temparray as $key2 => $value2) {
          //}
          //reset temp array
          ($listarray[$lastkey]['balance'] < 0) ? $temparray[$key3]['balance'] += $listarray[$lastkey]['balance'] : $temparray[$key3]['balance'] -= $listarray[$lastkey]['balance'];
        }
        //if ($stop) break;
      }
    }
    $lastkey = $key;

    //if ($stop) break;
  }
  // find transactions with lowest total sum
  $selected = 0;
  if (isset($return_array)) {
    foreach ($return_array as $key => $value) {
      if ($sum == false || $sum > $value[0]['sum'] + $value[1]['sum']) {
        $selected = $key;
        $sum = $value[0]['sum'] + $value[1]['sum'];
      }
    }
    return ($return_array[$selected]);
  }
//echo "=========";
//printr($return_array[$selected]);
//echo "=========+";
}

function close_group_expenses($listarray) {
  // http://stackoverflow.com/questions/877728/what-algorithm-to-use-to-determine-minimum-number-of-actions-required-to-get-the/
  // remove members that have a zero balance
  foreach ($listarray as $key => $val) {
    if ($val['balance'] == 0) {
      //echo $val['name'] . "<br>";
      unset($listarray[$key]);
    }
  }

  $listarraycp = $listarray;
  //printr($listarray);
  /* $listarray = array (
    0 => array("uid" => 1, "balance" => "10"),
    1 => array("uid" => 2, "balance" => "-5"),
    2 => array("uid" => 3, "balance" => "8.5"),
    3 => array("uid" => 4, "balance" => "-20"),
    4 => array("uid" => 5, "balance" => "25"),
    5 => array("uid" => 6, "balance" => "-10"),
    6 => array("uid" => 7, "balance" => "-5.5"),
    7 => array("uid" => 8, "balance" => "-2"),
    8 => array("uid" => 9, "balance" => "-1")
    ); */


  $i = 0;
  $stop = false;

  do {

    $texpense_array = false;
    $texpense_array = check_even_balance($listarray);
    if ($texpense_array) {
      $expense_array[$i] = $texpense_array;
      //printr($expense_array);
      // TODO: add loop again if succesful
      ////echo "key $key and key $key2 are even balance";
      unset($listarray[$expense_array[$i]['pay_key']]);
      unset($listarray[$expense_array[$i]['get_key']]);
      $i++;
    }
  } while ($texpense_array);

  // echo "LISTARRAY AFTER FIRST ROUND<br>";
  //printr($listarray);

  if (count($listarray) > 0) {
    // check if wiping one balance can wipe two in the next round
    do {
      $texpense_array = false;
      $texpense_array = check_even_balance_next_round($listarray);
      if ($texpense_array) {
        // $expense_array[] = $texpense_array;
        //printr($texpense_array);
        $expense_array[$i] = $texpense_array[0];
        unset($listarray[$texpense_array[0]['get_key']]);
        $expense_array[$i + 1] = $texpense_array[1];
        unset($listarray[$texpense_array[1]['pay_key']]);
        unset($listarray[$texpense_array[1]['get_key']]);
        $i = $i + 2;
        //printr($expense_array);
        //unset($listarray[$expense_array[$i]['pay_key']]);
        // TODO: add loop again if succesful
        ////echo "key $key and key $key2 are even balance";
        // unset($listarray[$expense_array[$i]['pay_key']]);
        // unset($listarray[$expense_array[$i]['get_key']]);
        // $i++;
      }
    } while ($texpense_array);
  }

  // echo "LISTARRAY AFTER SECOND ROUND<br>";
  //printr($listarray);

  do {
    // wipe any balance
    // kleinst mogelijke negatieve eerst met kleinst mogelijke positieve
    // echo get_memory_usage() . "<br>";
    if (count($listarray) > 0) {
      $lowbalance = 0;
      foreach ($listarray as $key => $value) {


        // find largest negative and positive balance
        //echo "($lowbalance > ". $value['balance'] . "&& " . $value['balance'] . " < 0 )<br>";
        if ((!isset($lowkey) && $value['balance'] < 0) || ($lowbalance < $value['balance'] && $value['balance'] < 0 )) {

          $lowkey = $key;
          $lowbalance = $value['balance'];
          //echo "lowbalance = $lowbalance<br>";
        }
      }
      $highbalance = 0;
      foreach ($listarray as $key => $value) {

        if ((!isset($highkey) && $value['balance'] > 0) || ($highbalance > $value['balance'] && $value['balance'] > 0 && ($value['balance'] > abs($lowbalance) || ( $value['balance'] > $highbalance && $highbalance < abs($lowbalance))) )) {
          $highkey = $key;
          $highbalance = $value['balance'];
        }
      }
    }

    if ($highbalance > abs($lowbalance)) {
      //$debugstr = "high > low: " .$listarray[$lowkey]['name'] . " (". $listarray[$lowkey]['balance'] . ", key: $lowkey) pays " . abs($lowbalance) ." to " . $listarray[$highkey]['name'] . " (". $listarray[$highkey]['balance'] . ", key: $highkey)<br>";
      $expense_array[$i]['pay_uid'] = $listarray[$lowkey]['user_id'];
      $expense_array[$i]['pay_name'] = $listarray[$lowkey]['name'];
      $expense_array[$i]['pay_key'] = $lowkey;
      $expense_array[$i]['sum'] = abs($lowbalance);
      unset($listarray[$lowkey]);
      $expense_array[$i]['get_uid'] = $listarray[$highkey]['user_id'];
      $expense_array[$i]['get_name'] = $listarray[$highkey]['name'];
      $expense_array[$i]['get_key'] = $highkey;
      $listarray[$highkey]['balance'] += $lowbalance;
    } else {
      //$debugstr = "low > high: " .$listarray[$lowkey]['name'] . " (". $listarray[$lowkey]['balance'] . ", key: $lowkey) pays ". abs($highbalance) . " to " . $listarray[$highkey]['name'] . " (". $listarray[$highkey]['balance'] . ", key: $highkey)<br>";
      $expense_array[$i]['pay_uid'] = $listarray[$lowkey]['user_id'];
      $expense_array[$i]['pay_name'] = $listarray[$lowkey]['name'];
      $expense_array[$i]['pay_key'] = $lowkey;
      $expense_array[$i]['sum'] = $highbalance;

      $expense_array[$i]['get_uid'] = $listarray[$highkey]['user_id'];
      $expense_array[$i]['get_name'] = $listarray[$highkey]['name'];
      $expense_array[$i]['get_key'] = $highkey;
      unset($listarray[$highkey]);
      $listarray[$lowkey]['balance'] += $highbalance;
    }

    $i++;
    //echo "LOWKEY: $lowkey, LOWBALANCE: $lowbalance, HIGHKEY: $highkey, HIGHBALANCE: $highbalance<br>";

    unset($highkey);
    unset($lowkey);
    unset($highbalance);
    unset($lowbalance);
    //echo "LISTARRAY ===<br>";
    //printr($listarray);
    // add loop after last wipe until count $listarray == 0
    //echo "Expense array  AFTER THIRD ROUND<br>";
    //printr($expense_array);
    /////////////////
    if (count($listarray) > 0) {
      // check if wiping one balance can wipe two in the next round
      do {
        $texpense_array = false;
        $texpense_array = check_even_balance_next_round($listarray);
        if ($texpense_array) {
          // $expense_array[] = $texpense_array;
          //printr($texpense_array);
          $expense_array[$i] = $texpense_array[0];
          unset($listarray[$texpense_array[0]['get_key']]);
          $expense_array[$i + 1] = $texpense_array[1];
          unset($listarray[$texpense_array[1]['pay_key']]);
          unset($listarray[$texpense_array[1]['get_key']]);
          $i = $i + 2;
          //printr($expense_array);
          //unset($listarray[$expense_array[$i]['pay_key']]);
          // TODO: add loop again if succesful
          ////echo "key $key and key $key2 are even balance";
          // unset($listarray[$expense_array[$i]['pay_key']]);
          // unset($listarray[$expense_array[$i]['get_key']]);
          // $i++;
        }
      } while ($texpense_array);
    }

    //echo "LISTARRAY AFTER FOURTH ROUND<br>";
    //printr($listarray);
  } while (count($listarray) > 1);


  // printr($expense_array);
  /* echo "To even out the balances: <br><br>";
    foreach ($expense_array as $key => $value) {
    echo $listarraycp[$value['pay_key']]['name'] . " pays &euro; " . number_format($value['sum'], DECIMALS , DSEP,TSEP)  . " to " . $listarraycp[$value['get_key']]['name'] . "<br>";
    } */
  return ($expense_array);
}

function get_memory_usage() {
  $mem_usage = memory_get_usage(true);

  if ($mem_usage < 1024)
    $str = $mem_usage . " bytes";
  elseif ($mem_usage < 1048576)
    $str = round($mem_usage / 1024, 2) . " kilobytes";
  else
    $str = round($mem_usage / 1048576, 2) . " megabytes";
  return $str;
}

function print_settle_group_list_html($settle_array) {
  //printr($settle_array);
  //printr($memberlist);
  foreach ($settle_array as $key => $value) {
    $pay_array[$value['pay_uid']][] = array("pay_name" => $value['pay_name'], "get_uid" => $value['get_uid'], "get_name" => $value['get_name'], "sum" => $value['sum']);
  }

  /* foreach($events as $key => $value) {
    $listhtml .= n(8) . "<li class=\"textbox\">" . $value['event_name']  . " (".  $value['date']  . ")</li>\n";
    } */
  // $array = array_sort($array, 'balance', $order); 
  // $count = count($array);
  //for ($i = 0; $i < $count; $i++) {
  // printr($pay_array);
  $listhtml = "";
  foreach ($pay_array as $pay_uid => $get_array) {
    $listhtml .= n(6) . "<ul class=\"pageitem\">\n";
    $listhtml .= n(8) . "<li class=\"textbox\"><span class=\"header\">" . $get_array[0]['pay_name'] . " pays:</span></li>\n";
    $get_array = array_sort($get_array, 'sum', SORT_DESC);
    foreach ($get_array as $key => $value) {
      $listhtml .= n(8) . "<li class=\"menu\">\n";

      $listhtml .= n(10) . "<span class=\"name\">" . $value['get_name'] . "</span>\n";
      $listhtml .= n(10) . "<span class=\"balance\">\n";
      $listhtml .= n(12) . "<span class=\"price_neg\">" . number_format($value['sum'], DECIMALS , DSEP,TSEP) . "</span>\n";
      $listhtml .= n(12) . "<span class=\"euro_neg\">&euro;</span>\n";
      $listhtml .= n(10) . "</span>\n";

      $listhtml .= n(8) . "</li>\n";
    }
    $listhtml .= n(6) . "</ul>";
  }

  echo $listhtml;
}

function print_settle_group_list_form($settle_array, $groupid) {

  foreach ($settle_array as $key => $value) {
    $pay_array[$value['pay_uid']][] = array("pay_name" => $value['pay_name'], "get_uid" => $value['get_uid'], "get_name" => $value['get_name'], "sum" => $value['sum']);
  }
  $row = 1;
  $listhtml = "";
  $formarray['action'] = $_SERVER['PHP_SELF'];
  foreach ($pay_array as $pay_uid => $get_array) {
    $formarray['rows'][$row]['type'] = "textrow";
    $formarray['rows'][$row]['label'] = $get_array[0]['pay_name'] . " paid:";
    $row++;

    // $listhtml  .= n(6) . "<ul class=\"pageitem\">\n";
    // $listhtml .= n(8) . "<li class=\"textbox\"><span class=\"header\">". $get_array[0]['pay_name'] ." pays:</span></li>\n";


    $get_array = array_sort($get_array, 'sum', SORT_DESC);
    foreach ($get_array as $key => $value) {

      $formarray['rows'][$row]['type'] = "checkbox";
      $formarray['rows'][$row]['label'] = $value['get_name'] . ' &euro; ' . number_format($value['sum'], DECIMALS , DSEP,TSEP) ;
      $formarray['rows'][$row]['name'] = "paylist[]";
      $formarray['rows'][$row]['checked'] = 1;
      $formarray['rows'][$row]['value'] = $pay_uid . '-' . $value['get_uid'];
      $row++;
      /*

        $listhtml .= n(8) . "<li class=\"menu\">\n";

        $listhtml .= n(10). "<span class=\"name\">" . $value['get_name'] . "</span>\n";
        $listhtml .= n(10). "<span class=\"balance\">\n";
        $listhtml .= n(12). "<span class=\"price_neg\">" . number_format($value['sum'], DECIMALS , DSEP,TSEP)  . "</span>\n";
        $listhtml .= n(12). "<span class=\"euro_neg\">&euro;</span>\n";
        $listhtml .= n(10). "</span>\n";

        $listhtml .= n(8). "</li>\n";
       */
    }

    //$listhtml .= n(6) . "</ul>"; 
  }
  $checkarr = get_group_checkarr($groupid);
  $formarray['rows'][$row]['items'] = "|groupid|hidden|" . $groupid;
  $formarray['rows'][$row + 1]['items'] = "|hash|hidden|" . $checkarr['hash'];
  $formarray['rows'][$row + 2]['items'] = "|mode|hidden|process";
  $formarray['rows'][$row + 3]['items'] = "||submit|Submit payments";

  echo create_form_html($formarray);
}

// returns an array with groupid, total amount spent in group, total expensecount and hash
// can be used to check if a group had changes between two moments in time
function get_group_checkarr($groupid) {
  $checkarr = array();
  $sql = "SELECT group_id, SUM(AMOUNT) AS grouptotal, COUNT(*) AS expensecount,
             MD5(((SUM(AMOUNT) -8263)*45.32)/( COUNT(*) +993.3)) AS hash 
             FROM expenses WHERE group_id = " . $groupid;
  if ((!$result = mysql_query($sql)) || (mysql_num_rows($result) == 0) || (mysql_num_rows($result) > 1)) {
    return false;
  } else {
    while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
      $checkarr['groupid'] = $row['group_id'];
      $checkarr['grouptotal'] = $row['grouptotal'];
      $checkarr['expensecount'] = $row['expensecount'];
      $checkarr['hash'] = $row['hash'];
    }
  }
  return $checkarr;
}

function validate_check_arr($groupid, $givenhash) {
  $checkarr = get_group_checkarr($groupid);
  if ($checkarr['hash'] == $givenhash)
    return true;
  return false;
}

function invite_member($email, $userid, $group = '', $groupname = '') {
  $regcode = get_random_string();
  if (empty($group))
    $group = 'NULL';
  
    $sql = "INSERT INTO `goingdutch`.`register` (`email` ,`userid`,  `code` , `group`, `timestamp`)
          VALUES ('" . mysql_real_escape_string(email) . "', {$userid}, '" . mysql_real_escape_string($regcode) . "', {$group}, CURRENT_TIMESTAMP )";
            if (!$result = mysql_query($sql)) 
              return false;
            
            // send email

            $regcodes = space_code($regcode);
            $from = 'admin@inthere.nl';
            $from_name = 'Going Dutch';
            if (!empty($groupname)) 
              $groupname ='group "' . $groupname . '"';
            $subject = "Invitation to join Going Dutch {$groupname}";
            //$website = 'http://inthere.nl/dutch';
            $website = LOGIN_URL;
            $link = "<a href=\"{$website}?code={$regcode}\">this link</a>";
            $html = "Please register using {$link}<br /><br>";
            $html .= "Or register on {$website} with this code<br />{$regcodes}";
            if (!smtpmailer($email, $from, $from_name, $subject, $html, array('noreply@inthere.nl', 'Do not reply to this address'), 'to')) {
              echo " something went wrong <br />";
              echo $smtpmailer_error;
            }
}

function space_code($code, $charcount=3){
  $i=0;
  $j = $charcount;
  $len = strlen($code);
  $done = false;
  $parts = array();
  while (!$done){
    $parts[] =substr($code, $i, $j); 
    if (($i + $j) > $len)
      $done = true;
    $i += $j;    
  }
  $code = implode(' ', $parts);
  return $code;
}

define('GUSER', 'bert@inthere.nl'); // Gmail username
define('GPWD', 'nannhnapniboctio '); // Gmail password

function smtpmailer($to, $from, $from_name ,  $subject, $body, $replyto = '', $sendas='to') {

  require_once ( dirname(__FILE__) . '/PHPMailer_v5.1/class.phpmailer.php');
  global $smtpmailer_error;
  $mail = new PHPMailer();  // create a new object
  
  if ($sendas == 'to') {
  
  if (is_array($to)) {
    foreach ($to as $emailaddress)
      $mail->AddAddress($emailaddress);
  } elseif (substr_count($to, ',') > 0) {
    $tolist = explode(',', $to);
    foreach ($tolist as $emailaddress)
      $mail->AddAddress($emailaddress);
  } elseif (substr_count($to, ';') > 0) {
    $tolist = explode(';', $to);
    foreach ($tolist as $emailaddress)
      $mail->AddAddress($emailaddress);
  } else
    $mail->AddAddress($to);
  
  }
  
    elseif ($sendas == 'bcc') {
  
  if (is_array($to)) {
    foreach ($to as $emailaddress)
      $mail->AddBCC($emailaddress);
  } elseif (substr_count($to, ',') > 0) {
    $tolist = explode(',', $to);
    foreach ($tolist as $emailaddress)
      $mail->AddBCC($emailaddress);
  } elseif (substr_count($to, ';') > 0) {
    $tolist = explode(';', $to);
    foreach ($tolist as $emailaddress)
      $mail->AddBCC($emailaddress);
  } else
    $mail->AddBCC($to);
  
  }
  
  
  if (!empty($replyto)) {
    if (is_array($replyto)) {
      $mail->AddReplyTo($replyto[0], $replyto[1]);
    } else 
    $mail->AddReplyTo($replyto);
  }
    
  
  $mail->IsSMTP(); // enable SMTP
  $mail->SMTPDebug = 0;  // debugging: 1 = errors and messages, 2 = messages only
  $mail->SMTPAuth = true;  // authentication enabled
  $mail->SMTPSecure = 'ssl'; // secure transfer enabled REQUIRED for Gmail
  $mail->Host = 'smtp.gmail.com';
  $mail->Port = 465;
  $mail->Username = GUSER;
  $mail->Password = GPWD;
  $mail->SetFrom($from, $from_name);
  $mail->Subject = $subject;
  $mail->Body = $body;
//$mail->AddAddress($to);
  $mail->IsHTML(true);
  if (!$mail->Send()) {
    $smtpmailer_error = 'Mail error: ' . $mail->ErrorInfo;
    return false;
  } else {
    $smtpmailer_error = 'Message sent!';
    return true;
  }
}

function get_random_string($length = 9, $numbers = true, $upper = true) {
  if (1 > $length)
    $length = 8;

  $chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
  $numChars = 62;

  if (!$numbers) {
    $numChars = 52;
    $chars = substr($chars, 10, $numChars);
  }

  if (!$upper) {
    $numChars -= 26;
    $chars = substr($chars, 0, $numChars);
  }

  $string = '';

  for ($i = 0; $i < $length; $i++) {
    $string .= $chars[mt_rand(0, $numChars - 1)];
  }

  return $string;
}

function get_user_achievements($userid, $groupids){
  $groupids = is_array(($groupids)) ? implode(',',$groupids) : $groupids;
  $sql = "SELECT * FROM achievements a, users_achievements ua
               WHERE ua.achievement_id = a.achievement_id 
               AND ua.user_id = $userid AND ua.group_id IN ($groupids)";
  if (!$result = mysql_query($sql)) {
      return false;
    } else {
      while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
        $list[] = $row;
      }
   }  
   return $list;
}
  
  


/*
  TRUNCATE TABLE `users_groups_del`;
  TRUNCATE TABLE `users_expenses_del`;
  TRUNCATE TABLE `groups_del`;
  TRUNCATE TABLE `expenses_del`;
 */



/*
  Array Structure for get_user_expenses:
  --[users]
  |    |_____[user_id]
  |    |         |__groups
  |    |             |__[group_id]
  |    |             |     |_______
  |    |             |     |       [expense_id]
  |    |             |     |            |_______
  |    |             |     |                    [total_amount]
  |    |             |     |                    [member_count]
  |    |             |     |                    [per_person]
  |    |             |     |
  |    |             |     |______[expense_id]
  |    |             |     |            |_______
  |    |             |     |                    [total_amount]
  |    |             |     |                    [member_count]
  |    |             |     |                    [per_person]
  |    |             |     |
  |    |             |     |______[group_total]
  |    |             |
  |    |             |__[group_id]
  |    |                   |______
  |    |                           [expense_id]
  |    |                                |_______
  |    |                                        [total_amount]
  |    |                                        [member_count]
  |    |                                        [per_person]
  |    |_____[user_id]
  |              |__groups
  |                   |__[group_id]
  |                         |______
  |                                [expense_id]
  |                                     |_______
  |                                             [total_amount]
  |                                             [member_count]
  |                                             [per_person]
  --[total]
 */
?>
