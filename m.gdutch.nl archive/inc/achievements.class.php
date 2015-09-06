<?php

include_once('functions.php');

class Achievements {

  var $groupid; // id of the group in question
  var $balanceArr; // an array of each expense with balance for each member at that time
  var $memberlist;
  var $balancePositionLog;  // an array by balance position with time in that position (in sec) for each member
  var $expenseList;   // all expenses of this group
  var $members; // array of member details keyed by member id
  var $totaltime;   // total time in seconds between first and last expense
  var $achievements;   // array with all achievements of members
  var $achievementList; // array with all defined achievements
  
  function __construct($groupid) {
    $this->groupid = $groupid;
    $this->memberlist = get_groupmembers($this->groupid);
    $this->balanceArr = $this->get_graph_balance_array();
    $this->membersById();
    $this->memberExpenseCount();
    $this->createPositionsArray();
    $this->getAchievementList();
    $this->updateAchievements();
    $this->updateEventAchievements();
    $this->save();
  }

  function updateAchievements() {

    foreach ($this->balancePositionLog as $pos)
      reset($pos);
    $this->achievements['longest_top_balance'] = array(
          'key' => 'longest_top_balance',
        'memberid' => key($this->balancePositionLog[0]),
        'value' => $this->inDays($this->balancePositionLog[0][key($this->balancePositionLog[0])]) 
    );
    $this->achievements['longest_bottom_balance'] = array(
        'key' => 'longest_bottom_balance',
        'memberid' => key($this->balancePositionLog[count($this->balancePositionLog) - 1]),
        'value' => $this->inDays($this->balancePositionLog[count($this->balancePositionLog) - 1][key($this->balancePositionLog[count($this->balancePositionLog) - 1])]) 
    );
    next($this->balancePositionLog[0]);
    $this->achievements[ 'longest_top_balance2nd'] = array(
        'key' => 'longest_top_balance2nd',
        'memberid' => key($this->balancePositionLog[0]),
        'value' => $this->inDays($this->balancePositionLog[0][key($this->balancePositionLog[0])]) 
    );
    next($this->balancePositionLog[count($this->balancePositionLog) - 1]);
    $this->achievements['longest_bottom_balance2nd'] = array(
        'key' => 'longest_bottom_balance2nd',
        'memberid' => key($this->balancePositionLog[count($this->balancePositionLog) - 1]),
        'value' => $this->inDays($this->balancePositionLog[count($this->balancePositionLog) - 1][key($this->balancePositionLog[count($this->balancePositionLog) - 1])]) 
    );


    // find members always above or below 0
    // find highest and lowest balance
    // find fastest riser and faller
    $topbalance = array('memberid' => 0, 'balance' => 0);
    $lowbalance = array('memberid' => 0, 'balance' => 0);
    $fastestriser = array('memberid' => 0, 'value' => 0);
    $fastestfaller = array('memberid' => 0, 'value' => 0);
    $a = 1;
    foreach ($this->balanceArr as $exp) {
      foreach ($exp['balance_member'] as $memid => $blc) {
        if ($blc > 0)
          $this->members[$memid]['bal_below_zero_always'] = false;
        if ($blc < 0)
          $this->members[$memid]['bal_above_zero_always'] = false;
        if ($blc > $topbalance['balance'])
          $topbalance = array('memberid' => $memid, 'balance' => $blc);
        if ($blc < $lowbalance['balance'])
          $lowbalance = array('memberid' => $memid, 'balance' => $blc);
        if (!isset($lastexp))
          continue;
        $diff = abs($blc-floatval($lastexp['balance_member'][$memid]));
        if ($blc > floatval($lastexp['balance_member'][$memid]) && $diff > $fastestriser['value'])
          $fastestriser = array('memberid' => $memid, 'value' => $diff); 
        if ($blc < floatval($lastexp['balance_member'][$memid]) && $diff > $fastestfaller['value']) {
          $ta = $lastexp;
          $tb = $exp;
          $fastestfaller = array('memberid' => $memid, 'value' => $diff);      
        }
      }
      
      $lastexp = $exp;
    }
    $abovezero = array();
    $belowzero = array();
    foreach ($this->members as $mid => $member) {
      if ($member['bal_above_zero_always'] == true && $member['bal_below_zero_always'] == false)
        $abovezero[] = array('memberid' => $mid, 'value' => '');
      if ($member['bal_below_zero_always'] == true && $member['bal_above_zero_always'] == false)
        $belowzero[] = array('memberid' => $mid, 'value' => '');
    }
    
    $this->achievements['balance_always_above_zero'] = array(
        'key' => 'balance_always_above_zero',
        'value' => $abovezero
    );
    $this->achievements['balance_always_below_zero'] = array(
        'key' => 'balance_always_below_zero',
        'value' => $belowzero
    );
    $this->achievements[ 'highest_balance'] = array(
        'key' => 'highest_balance',
        'memberid' => $topbalance['memberid'],
        'value' => $topbalance['balance']
    );
    $this->achievements['lowest_balance'] = array(
        'key' => 'lowest_balance',
        'memberid' => $lowbalance['memberid'],
        'value' => $lowbalance['balance']
    );
    $this->achievements['fastest_riser'] = array(
        'key' => 'fastest_riser',
        'memberid' => $fastestriser['memberid'],
        'value' => $fastestriser['value']
    );
    $this->achievements['fastest_faller'] = array(
        'key' => 'fastest_faller',
        'memberid' => $fastestfaller['memberid'],
        'value' => $fastestfaller['value']
    );
    // find highest and lowest paid and expense participated
    $toppaid = array('memberid' => 0, 'value' => false);
    $lowpaid = array('memberid' => 0, 'value' => false);
    $topexpense = array('memberid' => 0, 'value' => false);
    $lowexpense = array('memberid' => 0, 'value' => false);


    $expcountArray = array();
    foreach ($this->members as $uid => $details) {
      $expcountArray[$uid] = $details['expensecount'];
      
      $user_expenses = get_user_expenses($uid);
      $user_paid_expenses = get_user_paid_expenses($uid);
      $uexpense = $user_expenses['users'][$uid]['groups'][$this->groupid]['group_total'];
      $upaid = $user_paid_expenses['users'][$uid]['groups'][$this->groupid]['group_total'];
      if ($upaid > $toppaid['value'] || $toppaid['value'] == false)
        $toppaid = array('memberid' => $uid, 'value' => $upaid);
      if ($upaid < $lowpaid['value'] || $lowpaid['value'] == false)
        $lowpaid = array('memberid' => $uid, 'value' => $upaid);
      if ($uexpense > $topexpense['value'] || $topexpense['value'] == false)
        $topexpense = array('memberid' => $uid, 'value' => $uexpense);
      if ($uexpense < $lowexpense['value'] || $lowexpense['value'] == false)
        $lowexpense = array('memberid' => $uid, 'value' => $uexpense);
    }

    $this->achievements['most_paid'] = array(
        'key' => 'most_paid',
        'memberid' => $toppaid['memberid'],
        'value' => $toppaid['value']
    );
    $this->achievements['least_paid'] = array(
        'key' => 'least_paid',
        'memberid' => $lowpaid['memberid'],
        'value' => $lowpaid['value']
    );
    $this->achievements['most_spent'] = array(
        'key' => 'most_spent',
        'memberid' => $topexpense['memberid'],
        'value' => $topexpense['value']
    );
    $this->achievements['least_spent'] = array(
        'key' => 'least_spent',
        'memberid' => $lowexpense['memberid'],
        'value' => $lowexpense['value']
    );
    
    // most expenses
    arsort($expcountArray);
    $mostexpenses = array();
    $mostexpenses2nd = array();
    $expC2nd = 0;
    foreach ($expcountArray as $uid => $count){
      if (count($mostexpenses) == 0 || $count == $mostexpenses[0]['value']) 
        $mostexpenses[] = array('memberid' => $uid, 'value' => $count);
      if ((count($mostexpenses2nd) == 0 && $count < $mostexpenses[0]['value']) || ($count == $expC2nd && $count > 0))  {
        $expC2nd = $count;
        $mostexpenses2nd[] = array('memberid' => $uid, 'value' => $count);
      }
    }
    
    $this->achievements['highest_expense_count'] = array(
        'key' => 'highest_expense_count',
        'value' => $mostexpenses
    );
   $this->achievements['highest_expense_count2nd'] = array(
        'key' => 'highest_expense_count2nd',
        'value' => $mostexpenses2nd
    );
        
    // find biggest and smallest expense
    $smallexp = array('memberid' => 0, 'value' => false);
    $bigexp = array('memberid' => 0, 'value' => false);
    foreach ($this->expenseList as $expense) {
      if (floatval($expense['amount']) > $bigexp['value'] || $bigexp['value'] == false )
          $bigexp = array('memberid'  => $expense['userid'], 'value' => floatval($expense['amount'])) ;
      if (floatval($expense['amount']) < $smallexp['value'] || $smallexp['value'] == false ) 
              $smallexp = array('memberid'  => $expense['userid'], 'value' => floatval($expense['amount'])) ;
    }
    $this->achievements['biggest_expense'] = array(
        'key' => 'biggest_expense',
        'memberid' => $bigexp['memberid'],
        'value' => $bigexp['value']
    );
    $this->achievements['smallest_expense'] = array(
        'key' => 'smallest_expense',
        'memberid' => $smallexp['memberid'],
        'value' => $smallexp['value']
    );

  }
  
  function updateEventAchievements() {
    $a=1;
    $events = get_all_events($this->groupid);
    $a=1;    
    $sql = " SELECT users_expenses.user_id, event_id, COUNT(event_id) AS expense_count FROM `users_expenses`, expenses WHERE users_expenses.expense_id = expenses.expense_id AND users_expenses.expense_id IN (SELECT expense_id FROM expenses WHERE event_id > 0 AND group_id=1) GROUP BY user_id, event_id";
    if (!$result = mysql_query($sql)) {
      return false;
    } else {
      $maxevents = 0;
      while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
        $this->members[ $row['user_id']]['events'][$row['event_id']] = array('eventid' => $row['event_id'], 'expense_count' => $row['expense_count']);
        $this->members[ $row['user_id']]['eventcount'] += 1;
        $maxevents = $this->members[ $row['user_id']]['eventcount']  > $maxevents ? $this->members[ $row['user_id']]['eventcount'] : $maxevents;
      }
      
    }
    $mostevents = array();
    foreach ($this->members as $member) {
      if ($member['eventcount'] == $maxevents) {
        $mostevents[] = array('memberid' => $member['user_id'], 'value' => $maxevents);
      }      
    }
        $this->achievements['participated_most_events'] = array(
        'key' => 'participated_most_events',
        'value' => $mostevents
    );
    
    $a=1;
  }
  
  
  function save(){
    $sql = "DELETE FROM users_achievements WHERE group_id = " .$this->groupid;
    $result = mysql_query($sql);
    
    $sql = "INSERT INTO users_achievements (user_id, group_id, achievement_id, value) VALUES ";
    foreach( $this->achievementList as $key => $ac ) {
      if (isset ($this->achievements[$ac['key']]) && !empty($this->achievements[$ac['key']]) ) {
          if (!is_array($this->achievements[$ac['key']]['value'])) {
            $sql .= "( " . $this->achievements[$ac['key']]['memberid'] ." ,
                               {$this->groupid},
                               {$key},
                             '{$this->achievements[$ac['key']]['value']}' ),";
          } else {
            foreach ($this->achievements[$ac['key']]['value'] as $val)
              $sql .= "( " . $val['memberid'] ." ,
                               {$this->groupid},
                               {$key},
                             '{$val['value']}' ),";
          }
      }
    }
    $sql = substr($sql, 0, -1);
    //$this->logToFile($sql);
    $result = mysql_query($sql);

  }
 function logToFile($string) {
  $myFile = "sql.txt";
  $fh = fopen($myFile, 'a') or die("can't open file");
  fwrite($fh, $string."\n");
  fclose($fh);
}
 

  function inDays($val) {
    return round($val / (60 * 60 * 24));
  }

  function getPositionTable() {
    $table = 'Total days: ' . $this->totaltime / (60 * 60 * 24);
    foreach ($this->balancePositionLog as $pos => $arr) {
      $table .= '<table>';
      $table .= "<tr><th colspan =2>Position {$pos}</th></tr>";
      foreach ($arr as $memberid => $val) {
        $days = round($val / (60 * 60 * 24));
        $table .= "<tr><td>{$this->members[$memberid]['realname']}</td><td>{$days} days</td></tr>";
      }
      $table .= '</table>';
    }
    return $table;
  }

  function getAchievementsTable() {
    $table = '<table>';
    foreach ($this->achievements as $achievement) {
      $table .= "<tr><th colspan =2>Achievement {$achievement['key']}</th></tr>";
      if (!is_array($achievement['value']))
        $table .= "<tr><td>{$this->members[$achievement['memberid']]['realname']}</td><td>{$achievement['value']}</td></tr>";
      else {
        foreach ($achievement['value'] as $arr)
          $table .= "<tr><td>{$this->members[$arr['memberid']]['realname']}</td><td>{$arr['value']}</td></tr>";
      }
    }
    $table .= '</table>';
    return $table;
  }

  function createPositionsArray() {
    $mbp = array(); // member balance performance array
    foreach ($this->balanceArr as $expenseid => $val) {
      if ($expenseid == count($this->balanceArr) - 1) {
        $this->totaltime = $next - strtotime($this->balanceArr[0]['date_time']);
        break;
      }
      $next = strtotime($this->balanceArr[$expenseid + 1]['date_time']);
      $now = strtotime($val['date_time']);

      $timepast = strtotime($this->balanceArr[$expenseid + 1]['date_time']) - strtotime($val['date_time']);
      $balances = $val['balance_member'];
      arsort($balances);

      // get the time spent in balance order for each member grouped by member
      $i = 0;
      $j = 0;
      foreach ($balances as $memberid => $balance) {
        $j = ($i > 0 && $balance == $prevbalance) ? $j : $j + 1;
        $mbp[$memberid][$j] += $timepast;
        $i++;
        $prevbalance = $balance;
      }
    }

    // create new array with time spent per member in balance order grouped by order position
    $posArr = array();
    foreach ($mbp as $memberid => $list) {
      foreach ($list as $pos => $timespent) {
        $posArr[$pos][$memberid] += $timespent;
      }
    }

    // sort the arrays for each order position
    $posArrSorted = array();
    ksort($posArr);
    foreach ($posArr as $pos) {
      $new = $pos;
      arsort($new);
      $posArrSorted[] = $new;
    }

    $this->balancePositionLog = $posArrSorted;
  }

  function get_graph_balance_array() {


    $groupexp = get_groupexpenses($this->groupid, false, false, false, true);
    // reverse array so earliest expense first
    $groupexp = array_reverse($groupexp);
    $this->expenseList = $groupexp;
    
    $groupmemberids = get_groupmember_ids($this->memberlist);

    $user_expenses = $this->get_user_expenses($groupmemberids);
    $user_paid_expenses = $this->get_user_paid_expenses($groupmemberids);
    $size = count($this->memberlist);

    $expcount = count($groupexp);

    $balance_array = array();
    foreach ($groupexp as $key => $value) {
      $a = 1;
      foreach ($groupmemberids as $keyy => $memberid) {
        if (isset($lastkey) && $balance_array[$lastkey]['balance_member'][$memberid]) {
          // previous key exists for this member, use that balance
          $balance = $balance_array[$lastkey]['balance_member'][$memberid];
        } else {
          // no key for this member, balance = 0;
          $balance = 0;
        }
        // get last key balance, substract current expense and add paid
        $_curexp = (isset($user_expenses['users'][$memberid]['groups'][$this->groupid][$value['expense_id']]['per_person'])) ? $user_expenses['users'][$memberid]['groups'][$this->groupid][$value['expense_id']]['per_person'] : 0;
        $_curpay = (isset($user_paid_expenses['users'][$memberid]['groups'][$this->groupid][$value['expense_id']]['amount'])) ? $user_paid_expenses['users'][$memberid]['groups'][$this->groupid][$value['expense_id']]['amount'] : 0;
        $balance_array[$key]['balance_member'][$memberid] = round($balance - $_curexp + $_curpay, 2);

        $balance_array[$key]['expense_id'] = $value['expense_id'];
        $balance_array[$key]['date_time'] = $value['date'];
        $balance_array[$key]['date'] = substr($value['date'], 0, 10);
        $balance_array[$key]['isodate'] = $this->dutchDate2iso($balance_array[$key]['date']);

//      if ($memberid == 3) {
//        //echo "Key: $key, expense_id: " . $value['expense_id'] . "Balance: " . $balance_array[$key]['balance_member'][$memberid] . " ==> $balance - " .
//        $user_expenses['users'][$memberid]['groups'][$this->groupid][$value['expense_id']]['per_person'] . " + " . $user_paid_expenses['users'][$memberid]['groups'][$this->groupid][$value['expense_id']]['amount'] . "<br>";
//      }
      }

      $lastkey = $key;
    }
    return $balance_array;
  }

  function get_user_expenses($userid_array) {
    $itemids = array();
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
        $expenses['users'][$row['userid']]['groups'][$row['group_id']][$row['exid']]['total_amount'] = $row['total_amount'];
        $expenses['users'][$row['userid']]['groups'][$row['group_id']][$row['exid']]['member_count'] = $row['member_count'];
        $expenses['users'][$row['userid']]['groups'][$row['group_id']][$row['exid']]['per_person'] = $row['per_person'];
        $expenses['users'][$row['userid']]['groups'][$row['group_id']]['group_total'] += $row['per_person'];
        $expenses['users'][$row['userid']]['user_total'] += $row['per_person'];
        $total += $row['per_person'];
        $expenses['users'][$row['userid']]['groups'][$row['group_id']][$row['exid']]['running_total'] += $row['per_person'] + $last_running[$row['userid']][$row['group_id']];
        $last_running[$row['userid']][$row['group_id']] = $expenses['users'][$row['userid']]['groups'][$row['group_id']][$row['exid']]['running_total'];
      }
      $expenses['total'] = $total;
    }
    // array structure: ['users'][$userid]['groups'][$this->groupid][$expenseid]['total_amount|member_count|per_person']
    // below picture

    return $expenses;
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
        $paid['users'][$row['userid']]['groups'][$row['group_id']][$row['exid']]['amount'] = $row['amount'];
        $paid['users'][$row['userid']]['groups'][$row['group_id']][$row['exid']]['expense_date'] = date("j M Y", $row['expensedate']);
        $paid['users'][$row['userid']]['groups'][$row['group_id']][$row['exid']]['description'] = $row['description'];
        $paid['users'][$row['userid']]['groups'][$row['group_id']]['group_total'] += $row['amount'];
        $paid['users'][$row['userid']]['user_total'] += $row['amount'];
        $total += $row['amount'];



// format totals with two decimals
        // $paid['users'][$row['userid']]['groups'][$row['group_id']]['total'] = number_format($paid['users'][$row['userid']]['groups'][$row['group_id']]['total'], DECIMALS, DSEP, TSEP);
//      $paid['users'][$row['userid']]['groups'][$row['group_id']]['total'] = 
//        (isset($paid['users'][$row['userid']]['groups'][$row['group_id']]['total'])) ? number_format($paid['users'][$row['userid']]['groups'][$row['group_id']]['total'], DECIMALS, DSEP, TSEP) : number_format(0, DECIMALS, DSEP, TSEP);
        $paid['users'][$row['userid']]['groups'][$row['group_id']]['total'] =
                (isset($paid['users'][$row['userid']]['groups'][$row['group_id']]['total'])) ? $paid['users'][$row['userid']]['groups'][$row['group_id']]['total'] : 0;

        //$paid['users'][$row['userid']]['total'] = number_format($paid['users'][$row['userid']]['total'], DECIMALS, DSEP, TSEP);
//      $paid['users'][$row['userid']]['total'] = 
//        (isset($paid['users'][$row['userid']]['total'])) ? number_format($paid['users'][$row['userid']]['total'], DECIMALS, DSEP, TSEP) : number_format(0, DECIMALS, DSEP, TSEP);
        $paid['users'][$row['userid']]['total'] =
                (isset($paid['users'][$row['userid']]['total'])) ? $paid['users'][$row['userid']]['total'] : 0;


        $paid['users'][$row['userid']]['groups'][$row['group_id']][$row['exid']]['running_total'] =
                (isset($paid['users'][$row['userid']]['groups'][$row['group_id']][$row['exid']]['running_total']) ) ? $paid['users'][$row['userid']]['groups'][$row['group_id']][$row['exid']]['running_total'] : 0;

        $paid['users'][$row['userid']]['groups'][$row['group_id']][$row['exid']]['running_total'] += $row['amount'] + $last_running[$row['userid']][$row['group_id']];
        $last_running[$row['userid']][$row['group_id']] = $paid['users'][$row['userid']]['groups'][$row['group_id']][$row['exid']]['running_total'];
      }
      //$paid['total'] = number_format($total, DECIMALS, DSEP, TSEP);
      $paid['total'] = $total;
    }
    // array structure: ['users'][$userid]['groups'][$this->groupid][$expenseid]['amount|expense_date|description']
    return $paid;
  }

  function dutchDate2iso($date) {
    $parts = explode('-', $date);
    return ($parts[2] . '-' . $parts[1] . '-' . $parts[0]);
  }

  function membersById() {
    foreach ($this->memberlist as $member) {
      $this->members[$member['user_id']] = $member;
      $this->members[$member['user_id']]['bal_above_zero_always'] = true;
      $this->members[$member['user_id']]['eventcount'] = 0;
    }
  }
  
  function memberExpenseCount() {
    $sql = "SELECT users_expenses.user_id, count( users_expenses.expense_id ) as ecount
                  FROM `users_expenses`, `expenses` 
                  WHERE users_expenses.expense_id = expenses.expense_id
                  AND group_id = 1
                  GROUP BY users_expenses.user_id";
     if (!$result = mysql_query($sql)) {
      return false;
    } else {
      while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) 
        $this->members[$row['user_id']]['expensecount'] = $row['ecount'];
    }
  }
  

    function getAchievementList() {

    $sql = "SELECT * FROM achievements";
    if (!$result = mysql_query($sql)) {
      return false;
    } else {
      while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) 
        $this->achievementList[$row['achievement_id']] = $row;
      }
    }

}
