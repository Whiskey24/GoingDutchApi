<?php
include("inc/common.php");
//require_once("inc/array_dump.php"); 

// check if valid group specified and return group details 
//$groupdetails = check_group($_POST, $_GET);
$groupid = 1;


function get_user_expenses_test($userid_array) {
  $itemids = array();
  if (is_array($userid_array)) $userids = implode(",",$userid_array);
  else $userids = $userid_array;
  $sql = "SELECT users_expenses.user_id AS userid, group_id, users_expenses.expense_id AS exid, amount as total_amount, 
           (SELECT COUNT(*) FROM users_expenses WHERE expense_id = exid) as member_count,
            ROUND((SELECT total_amount/member_count),2) as per_person
          FROM users_expenses, expenses WHERE users_expenses.expense_id = expenses.expense_id AND users_expenses.user_id IN ($userids)
          ORDER BY userid, group_id ASC, users_expenses.expense_id ASC";
  if(!$result = mysql_query($sql)) {
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


function get_user_paid_expenses_test ($userid_array) {
  if (is_array($userid_array)) $userids = implode(",",$userid_array);
  else $userids = $userid_array;
    $sql = "SELECT user_id as userid, expense_id as exid, group_id, amount, UNIX_TIMESTAMP(expense_date) AS expensedate, 
            description FROM expenses WHERE user_id in ($userids)";
  if(!$result = mysql_query($sql)) {
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
      $paid['users'][$row['userid']]['groups'][$row['group_id']]['total'] = number_format($paid['users'][$row['userid']]['groups'][$row['group_id']]['total'],DECIMALS , DSEP,TSEP) ;
      $paid['users'][$row['userid']]['total'] = number_format($paid['users'][$row['userid']]['total'],DECIMALS , DSEP,TSEP) ;
      
      $paid['users'][$row['userid']]['groups'][$row['group_id']][$row['exid']]['running_total'] += $row['amount'] + $last_running[$row['userid']][$row['group_id']];
      $last_running[$row['userid']][$row['group_id']] = $paid['users'][$row['userid']]['groups'][$row['group_id']][$row['exid']]['running_total'];
    
    }
    $paid['total'] = number_format($total,DECIMALS , DSEP,TSEP) ;
  }
  // array structure: ['users'][$userid]['groups'][$groupid][$expenseid]['amount|expense_date|description']
  return $paid;
}

$a=1;

  //$dump = new array_dump();
$a=1;

$groupexp = get_groupexpenses($groupid);
// reverse array so earliest expense first
$groupexp = array_reverse($groupexp);

 $memberlist = get_groupmembers($groupid);
  $groupmemberids = get_groupmember_ids($memberlist);
  
  $user_expenses = get_user_expenses_test($groupmemberids);
  $user_paid_expenses = get_user_paid_expenses_test($groupmemberids);
  $size = count($memberlist);

$expcount = count($groupexp);



foreach ($groupexp as $key => $value) {

	foreach ($groupmemberids as $keyy => $memberid) {
  	if ($balance_array[$lastkey]['balance_member'][$memberid] ) {
  		// previous key exists for this member, use that balance
  		$balance = $balance_array[$lastkey]['balance_member'][$memberid];
  	} else {
  		// no key for this member, balance = 0;
  		$balance = 0; 
  	}
  	// get last key balance, substract current expense and add paid
  	$balance_array[$key]['balance_member'][$memberid] =  
  					round ($balance - $user_expenses['users'][$memberid]['groups'][$groupid][$value['expense_id']]['per_person'] + 
						$user_paid_expenses['users'][$memberid]['groups'][$groupid][$value['expense_id']]['amount'] , 2);
		$balance_array[$key]['expense_id'] = $value['expense_id'];
		
		if ($memberid == 3){
			//echo "Key: $key, expense_id: " . $value['expense_id'] . "Balance: " . $balance_array[$key]['balance_member'][$memberid] . " ==> $balance - " .
				$user_expenses['users'][$memberid]['groups'][$groupid][$value['expense_id']]['per_person'] . " + " . $user_paid_expenses['users'][$memberid]['groups'][$groupid][$value['expense_id']]['amount'] . "<br>";
		}
		
	}

	$lastkey = $key; 
}

  // create jpgraph array
  foreach ($balance_array as $expid => $values) {
  	foreach ($values['balance_member'] as $memberid => $balance ) {
  		$jpgraph_data[$memberid][] = $balance;	 
  	}
  }

  foreach ($memberlist as $key => $value) {
	$membernames[$value['user_id']] = $value['username'];
  }	 
 

  /*
  $dump = new array_dump();

  echo $dump->dump($balance_array); */
  //  echo $dump->dump($memberlist);
   //echo $dump->dump($jpgraph_data);
  
 
require_once ('jpgraph/jpgraph.php');
require_once ('jpgraph/jpgraph_line.php');

$marks[] = "MARK_SQUARE";
$marks[] = "MARK_UTRIANGLE";
$marks[] = "MARK_DTRIANGLE";
$marks[] = "MARK_DIAMOND";
$marks[] = "MARK_CIRCLE";
$marks[] = "MARK_FILLEDCIRCLE";
$marks[] = "MARK_CROSS";
$marks[] = "MARK_STAR";
$marks[] = "MARK_X";
$marks[] = "MARK_LEFTTRIANGLE";
$marks[] = "MARK_RIGHTTRIANGLE";
$marks[] = "MARK_FLASH";

//$datay1 = array(20,15,23,15);
//$datay2 = array(12,9,42,8);
//$datay3 = array(5,17,32,24);

$datay1 = $jpgraph_data[1];
$datay2 = $jpgraph_data[2];
$datay3 = $jpgraph_data[3];

// Setup the graph
$graph = new Graph(960,640);
$graph->SetScale("textlin");

$theme_class=new UniversalTheme;

$graph->SetTheme($theme_class);
$graph->img->SetAntiAliasing(true);
//$graph->title->Set('Filled Y-grid');
$graph->SetBox(false);
$graph->SetMargin(35,0,0,0);
$graph->img->SetAntiAliasing();
$graph->yaxis->HideZeroLabel();
$graph->yaxis->HideLine(false);
$graph->yaxis->HideTicks(false,false);

$graph->xgrid->Show();
$graph->xgrid->SetLineStyle("solid");
//$graph->xaxis->SetTickLabels(array('A','B','C','D'));
$graph->xgrid->SetColor('#E3E3E3');
$i=0;
foreach ($jpgraph_data as $key => $value) {
//echo "KEY: $key <br>";  
// Create the first line
  $p[$key] = new LinePlot($value);
  //$graph->Add($p[$key]);
  //$p[$key]->SetColor("#6495ED");
  $p[$key]->SetLegend($membernames[$key]);
  //$p[$key]->SetLegend($key);
  $p[$key]->SetWeight(2);
  $p[$key]->mark->SetType(constant($marks[$i]));
  //$p[$key]->mark->SetType(MARK_SQUARE);

  $graph->Add($p[$key]);
  $i++;
}




/*

// Create the first line
$p1 = new LinePlot($datay1);
$graph->Add($p1);
$p1->SetColor("#6495ED");
$p1->SetLegend('Line 1');


// Create the second line
$p2 = new LinePlot($datay2);
$graph->Add($p2);
$p2->SetColor("#B22222");
$p2->SetLegend('Line 2');

// Create the third line
$p3 = new LinePlot($datay3);
$graph->Add($p3);
$p3->SetColor("#FF1493");
$p3->SetLegend('Line 3');
*/
$graph->legend->SetFrameWeight(1);
$graph->legend->SetColor('navy');
$graph->legend->SetFillColor('lightgreen');
$graph->legend->SetLineWeight(1);
//$graph->legend->SetFont(FF_ARIAL,FS_BOLD,8);
$graph->legend->SetShadow('gray@0.4',3);
$graph->legend->SetAbsPos(45,5,'left','top');

// Output line
$graph->Stroke();
  
  
  
  ?>
