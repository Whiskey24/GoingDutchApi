<?php

include("inc/common.php");
include("inc/highchart_graph.php");
include("inc/Browser.php");


// check if valid group specified and return group details 
$groupdetails = check_group($_POST, $_GET);

$browser = new Browser();
if ($browser->isMobile())
  $divSize = array('x' =>400, 'y' => 350);
else 
  $divSize = array('x' =>960, 'y' => 600);

$js = '<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.6.1/jquery.min.js" type="text/javascript"></script>
<script src="inc/highcharts/js/highcharts.js" type="text/javascript"></script>';

$memberlist = get_groupmembers($groupdetails['group_id']);
$balanceArr = get_graph_balance_array($groupdetails['group_id'], $memberlist);

foreach ($memberlist as $member) {
  $series[$member['user_id']] = array('name' => format_name($user,$member['username'],$member['realname']), 'type' => 'spline', 'data' => array());
}
$a = 1;
$max = 0;
$min = 0;
$fromd = '';
$tod = '';

//            [Date.UTC(2010, 0, 1), 29.9],
//            [Date.UTC(2010, 2, 1), 71.5],
//            [Date.UTC(2010, 3, 1), 106.4]
$override = false;

foreach ($balanceArr as $expid => $values) {
  if (isset($lastexpid) && $values['date'] == $balanceArr[$lastexpid]['date']){
    // override previous entry
    $override = true;
  } else 
    $override = false;
    
  foreach ($values['balance_member'] as $memberid => $balance) {
    if ($override){
      //$series[$memberid]['data'][$lastdatakey[$memberid]] = $balance;
      $series[$memberid]['data'][$lastdatakey[$memberid]] = array (strtotime($values['isodate'])*1000, $balance);
    }else {
      //$series[$memberid]['data'][] = $balance;
      $series[$memberid]['data'][] = array (strtotime($values['isodate'])*1000, (float)$balance);
    }
    $max = ($balance > $max) ? $balance : $max;
    $min = ($balance < $min) ? $balance : $min;
    end($series[$memberid]['data']);
    $lastdatakey[$memberid] = key($series[$memberid]['data']);
  }
  $lastexpid = $expid;
}

$plotArr = array();
foreach ($series as $serie) {
  $plotArr[] = $serie;
}

$max = intval($max + 5);
$min = intval($min - 5);



///$dataset = array( 'dataseries' => array ($dataNormal, $dataLow), 'axis'=>$axis);
//$dataset = array( 'dataseries' => array ($dataNormal), 'axis'=>$axis);
//$dataset = array( 'dataseries' => array ($dataNormal, $dataT), 'axis'=>$axis);
$dataset = array('dataseries' => $plotArr);

$graph = new HighchartGraph();
$graph->plotOptions['series']['marker']['enabled'] = false;
$graph->plotOptions['series']['marker']['states'] = array('hover' => array('enabled' => true));


$_graphtitle = $groupdetails['name'];
$graph->graphtitle = $_graphtitle;
//$graph->legend = array ('align' => 'center', 'verticalAlign' => 'bottom', 'x' => 40, 'y' => -15, 'floating' => false);
$graph->legend = array('align' => 'center', 'verticalAlign' => 'bottom', 'floating' => false);


$graph->tooltip['formatter'] = "function(){ return Highcharts.dateFormat('%A, %b %e, %Y', this.x) + '<br />' + '<span style=\"color:'+this.series.color+';\">' + this.series.name + '</span>' + ': ' + '<b>\u20AC ' +Highcharts.numberFormat( this.y,2) + '</b>'; } ";


//$_yAxis_title = "Balance";
// $graph->xAxis['categories'] = $series_array[0]['axis'];

//$graph->yAxis_title = $_yAxis_title;
// $graph->plotOptions['column']['groupPadding'] = 0.1;
//  if ($bkoutput)
//    $graph->plotOptions['series']['marker']['enabled'] = false;
// $graph->series = $series_array[0]['dataseries'];

$graph->yAxis['min'] = $min;
$graph->yAxis['max'] = $max;
$graph->yAxis['endOnTick'] = false;
$graph->yAxis['labels']['formatter'] = "function() {  return '\u20AC ' + this.value; }";

$graph->xAxis['type'] = 'datetime';



// http://jsfiddle.net/5AYzJ/
// only for stock charts
// $graph->xAxis['maxZoom'] =  14 * 24 * 3600000;
//$graph->rangeSelector['selected'] = 1;
//        rangeSelector: {
//            selected: 1
//        },
//          
//        xAxis: {
//            maxZoom: 14 * 24 * 3600000 // fourteen days
//        },
   

$graph->series = $dataset['dataseries'];
//  $graph->clickThrough = true;
//  $graph->formatFunction = true;
//  $graph->formatFunctionPie = true;
$graphdata = $graph->toJSON(true);

$_dsep = DSEP;
$_tsep = TSEP;

$js .= "<script type=\"text/javascript\">
var chart;
			$(document).ready(function() { 

    Highcharts.setOptions({
        lang: {
          decimalPoint: '{$_dsep}',
          thousandsSep: '{$_tsep}'
        }
    });

  chart = new Highcharts.Chart(
$graphdata
)
			});
   </script>";

// Start HTML output

print_header($js);
$back = get_back_page();
$topbar['leftnav'][0]['name'] = $back['name'];
$topbar['leftnav'][0]['url'] = $back['url'];

$topbar['title'] = "Graph!";

/* $topbar['rightnav'][0]['name'] = "Edit";
  $topbar['rightnav'][0]['url'] =  $_SERVER['PHP_SELF']. "?groupid=" . $groupdetails['group_id'] . "&mode=edit";
 */
print_topbar($topbar);
print_body_start();
//echo "Browser {$browser}, Platform {$platform}";

echo '<div id="graph_container" style="width: ' . $divSize['x'] . 'px; height: ' . $divSize['y'] . 'px; margin: 0 auto"></div>';

print_footer($user, 2,$groupdetails['group_id']);


/*  FUNCTIONS */

function get_graph_balance_array($groupid, $memberlist) {
 
  
  $groupexp = get_groupexpenses($groupid, false, false, false, true);
// reverse array so earliest expense first
  $groupexp = array_reverse($groupexp);


  $groupmemberids = get_groupmember_ids($memberlist);

  $user_expenses = get_user_expenses_test($groupmemberids);
  $user_paid_expenses = get_user_paid_expenses_test($groupmemberids);
  $size = count($memberlist);

  $expcount = count($groupexp);

  $balance_array = array();
foreach ($groupexp as $key => $value) {
$a=1;
    foreach ($groupmemberids as $keyy => $memberid) {
      if (isset($lastkey) && $balance_array[$lastkey]['balance_member'][$memberid]) {
        // previous key exists for this member, use that balance
        $balance = $balance_array[$lastkey]['balance_member'][$memberid];
      } else {
        // no key for this member, balance = 0;
        $balance = 0;
      }
      // get last key balance, substract current expense and add paid
       $_curexp = (isset($user_expenses['users'][$memberid]['groups'][$groupid][$value['expense_id']]['per_person'])) ? $user_expenses['users'][$memberid]['groups'][$groupid][$value['expense_id']]['per_person'] : 0;
      $_curpay = (isset($user_paid_expenses['users'][$memberid]['groups'][$groupid][$value['expense_id']]['amount'])) ? $user_paid_expenses['users'][$memberid]['groups'][$groupid][$value['expense_id']]['amount'] : 0;
      $balance_array[$key]['balance_member'][$memberid] = round($balance - $_curexp +$_curpay, 2);
 
      $balance_array[$key]['expense_id'] = $value['expense_id'];
      $balance_array[$key]['date_time'] = $value['date'];
      $balance_array[$key]['date'] = substr($value['date'], 0 ,10);
      $balance_array[$key]['isodate'] = dutchDate2iso($balance_array[$key]['date'] );
      
//      if ($memberid == 3) {
//        //echo "Key: $key, expense_id: " . $value['expense_id'] . "Balance: " . $balance_array[$key]['balance_member'][$memberid] . " ==> $balance - " .
//        $user_expenses['users'][$memberid]['groups'][$groupid][$value['expense_id']]['per_person'] . " + " . $user_paid_expenses['users'][$memberid]['groups'][$groupid][$value['expense_id']]['amount'] . "<br>";
//      }
    }

    $lastkey = $key;
  }
  return $balance_array;
}

function get_user_expenses_test($userid_array) {
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
  // array structure: ['users'][$userid]['groups'][$groupid][$expenseid]['total_amount|member_count|per_person']
  // below picture

  return $expenses;
}

function get_user_paid_expenses_test($userid_array) {
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

      
      $paid['users'][$row['userid']]['groups'][$row['group_id']][$row['exid']]['running_total']  = 
        (isset($paid['users'][$row['userid']]['groups'][$row['group_id']][$row['exid']]['running_total'] ) ) ? $paid['users'][$row['userid']]['groups'][$row['group_id']][$row['exid']]['running_total'] : 0;
      
      $paid['users'][$row['userid']]['groups'][$row['group_id']][$row['exid']]['running_total'] += $row['amount'] + $last_running[$row['userid']][$row['group_id']];
      $last_running[$row['userid']][$row['group_id']] = $paid['users'][$row['userid']]['groups'][$row['group_id']][$row['exid']]['running_total'];
    }
    //$paid['total'] = number_format($total, DECIMALS, DSEP, TSEP);
    $paid['total'] = $total;
  }
  // array structure: ['users'][$userid]['groups'][$groupid][$expenseid]['amount|expense_date|description']
  return $paid;
}

function dutchDate2iso ($date){
  $parts = explode('-', $date);
  return ($parts[2].'-'.$parts[1].'-'.$parts[0]);
  
}

?>
