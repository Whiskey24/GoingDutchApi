<?php
/**
 * Created by PhpStorm.
 * User: Whiskey
 * Date: 4-9-2015
 * Time: 23:07
 */

namespace Models;

use Db;

class Group
{

    protected $readExpensesSql = "SELECT expense_id AS eid, group_id as gid, cid, type, description AS etitle, user_id as uid,
                amount, amount, UNIX_TIMESTAMP(expense_date) AS ecreated,
                UNIX_TIMESTAMP(timestamp) AS eupdated, timezoneoffset, event_id, deposit_id as depid,
                (SELECT GROUP_CONCAT(DISTINCT users_expenses.user_id)
                    FROM users_expenses, users_groups
                    WHERE users_expenses.user_id = users_groups.user_id AND users_expenses.expense_id = eid
                    GROUP BY users_expenses.expense_id
                ) AS uids,
                (SELECT COUNT(DISTINCT expense_id)
                    FROM expenses
                    WHERE deposit_id = depid
                    GROUP BY deposit_id
                ) AS deposit_count
                FROM expenses
                WHERE expenses.group_id = :gid ";

    protected $readExpensesDelSql = "SELECT expense_id AS eid, group_id as gid, cid, type, description AS etitle, user_id as uid, uids,
                amount, amount, UNIX_TIMESTAMP(expense_date) AS ecreated, UNIX_TIMESTAMP(delete_date) AS edeleted,
                UNIX_TIMESTAMP(timestamp) AS eupdated, timezoneoffset, event_id, deposit_id as depid
                FROM expenses_del
                WHERE expenses_del.group_id = :gid ";

    function getExpenses($gid)
    {
        $sql = $this->readExpensesSql . "ORDER BY expense_date DESC, eid DESC";
        $stmt = Db::getInstance()->prepare($sql);
        $stmt->execute(array(':gid' => $gid));
        // put the results in an array with gid as key
        $expense_list = array($gid => $stmt->fetchAll(\PDO::FETCH_ASSOC));
        return json_encode($expense_list, JSON_NUMERIC_CHECK | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
    }

    function getExpensesDel($gid)
    {
        $sql = $this->readExpensesDelSql . "ORDER BY expense_date DESC, eid DESC";
        $stmt = Db::getInstance()->prepare($sql);
        $stmt->execute(array(':gid' => $gid));
        // put the results in an array with gid as key
        $expense_list = array($gid => $stmt->fetchAll(\PDO::FETCH_ASSOC));
        //$expense_list = Member::rearrangeArrayKey('eid', $expense_list);
        return json_encode($expense_list, JSON_NUMERIC_CHECK | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
    }

    function getExpense($gid, $eid, $json = true)
    {
        $sql = $this->readExpensesSql . "AND expenses.expense_id = :eid";
        $stmt = Db::getInstance()->prepare($sql);
        $stmt->execute(array(':gid' => $gid, ':eid' => $eid));
        $expense = $stmt->fetch(\PDO::FETCH_ASSOC);
        if ($json)
            return json_encode($expense, JSON_NUMERIC_CHECK | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
        else
            return $expense;
    }

    function getExpenseDel($gid, $eid, $json = true)
    {
        $sql = $this->readExpensesDelSql . "AND expenses_del.expense_id = :eid";
        $stmt = Db::getInstance()->prepare($sql);
        $stmt->execute(array(':gid' => $gid, ':eid' => $eid));
        $expense = $stmt->fetch(\PDO::FETCH_ASSOC);
        if ($json)
            return json_encode($expense, JSON_NUMERIC_CHECK | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
        else
            return $expense;
    }

    function addExpense($gid, $expense){
        $uids = $expense->uids . ',' . $expense->uid;
        if (!$this->validateUids($uids, $gid)){
            return 'Error: invalid uids';
        }

        if (!isset($expense->type))
            $expense->type = 1;

        $sql = "INSERT INTO expenses (type, cid, user_id, group_id, description, amount, expense_date, event_id, timestamp, currency, timezoneoffset)
                VALUES (:type, :cid, :user_id, :group_id, :description, :amount, FROM_UNIXTIME(:created), :event_id, FROM_UNIXTIME(:updated), :currency, :timezoneoffset)";
        $stmt = Db::getInstance()->prepare($sql);
        $stmt->execute(
            array(
                ':type' => $expense->type,
                ':cid' => $expense->cid,
                ':user_id' => $expense->uid,
                ':group_id' => $gid,
                ':description' => $expense->etitle,
                ':amount' => $expense->amount,
                ':created' => $expense->ecreated,
                ':updated' => $expense->eupdated,
                ':event_id' => $expense->event_id,
                ':timezoneoffset' => $expense->timezoneoffset,
                ':currency' => 1
            )
        );
        $eid = Db::getInstance()->lastInsertId();

        $sql = "INSERT INTO users_expenses (user_id , expense_id) VALUES (:user_id, :eid)";
        $stmt = Db::getInstance()->prepare($sql);
        $uids = explode(',', $expense->uids);
        foreach ($uids as $user_id){
            $stmt->execute(array(':user_id' => $user_id, ':eid' => $eid));
        }

        $this->addExpenseEmail($expense, $eid);

        return $this->getExpense($gid, $eid);
    }

    function deleteExpense($gid, $eid)
    {
        $expense = $this->getExpense($gid, $eid, false);

        if (!isset($expense['type']))
            $expense['type'] = 1;
        $sql = "INSERT INTO expenses_del (expense_id, type, cid, user_id, group_id, uids, description, amount, expense_date, event_id, timestamp, currency, timezoneoffset, delete_date)
                VALUES (:eid, :type, :cid, :user_id, :group_id, :uids, :description, :amount, FROM_UNIXTIME(:created), :event_id, FROM_UNIXTIME(:updated), :currency, :timezoneoffset, FROM_UNIXTIME(:now))";
        $stmt = Db::getInstance()->prepare($sql);
        $stmt->execute(
            array(
                ':eid' => $expense['eid'],
                ':type' => $expense['type'],
                ':cid' => $expense['cid'],
                ':user_id' => $expense['uid'],
                ':group_id' => $expense['gid'],
                ':uids' => $expense['uids'],
                ':description' => $expense['etitle'],
                ':amount' => $expense['amount'],
                ':created' => $expense['ecreated'],
                ':updated' => $expense['eupdated'],
                ':event_id' => $expense['event_id'],
                ':timezoneoffset' => $expense['timezoneoffset'],
                ':currency' => 1,
                ':now' => time()
            )
        );

        $sql = "DELETE FROM expenses WHERE expense_id = :eid AND group_id = :gid";
        $stmt = Db::getInstance()->prepare($sql);
        $stmt->execute(array(':gid' => $gid, ':eid' => $eid));

        $sql = "DELETE FROM users_expenses WHERE expense_id = :eid";
        $stmt = Db::getInstance()->prepare($sql);
        $stmt->execute(array(':eid' => $eid));

        $expense = json_decode(json_encode($expense), FALSE);
        $this->addExpenseEmail($expense, $eid, 'delete');

        return $eid;
    }

    function updateExpense($gid, $expense){
        $uids = $expense->uids . ',' . $expense->uid;
        if (!$this->validateUids($uids, $gid)){
            return 'Error: invalid uids';
        }

        $oldExpense = $this->getExpense($gid, $expense->eid, false);

        // keep track of any users removed from this expense
        $removedUids = array_diff(explode(',', $oldExpense['uids']  . ',' . $oldExpense['uid']), explode(',', $uids));

        if (!isset($expense->type))
            $expense->type = 1;
        $sql = "UPDATE expenses SET type=:type, cid=:cid, user_id=:user_id, description=:description, amount=:amount, event_id=:event_id, timestamp=:updated,
                currency=:currency, timezoneoffset=:timezoneoffset, expense_date=FROM_UNIXTIME(:created), timestamp=FROM_UNIXTIME(:updated)
                WHERE expense_id=:eid AND group_id=:group_id";
        $stmt = Db::getInstance()->prepare($sql);
        $stmt->execute(
            array(
                ':type' => $expense->type,
                ':cid' => $expense->cid,
                ':user_id' => $expense->uid,
                ':group_id' => $gid,
                ':description' => $expense->etitle,
                ':amount' => $expense->amount,
                ':event_id' => $expense->event_id,
                ':timezoneoffset' => $expense->timezoneoffset,
                ':currency' => 1,
                ':eid' => $expense->eid,
                ':updated' => $expense->eupdated,
                ':created' => $expense->ecreated
            )
        );

        $sql = "DELETE FROM users_expenses WHERE expense_id = :eid";
        $stmt = Db::getInstance()->prepare($sql);
        $stmt->execute(array(':eid' => $expense->eid));

        $sql = "INSERT INTO users_expenses (user_id , expense_id) VALUES (:user_id, :eid)";
        $stmt = Db::getInstance()->prepare($sql);
        $uids = explode(',', $expense->uids);
        foreach ($uids as $user_id){
            $stmt->execute(array(':user_id' => $user_id, ':eid' => $expense->eid));
        }

        $this->addExpenseEmail($expense, $expense->eid, 'update', $removedUids);

        return $this->getExpense($gid, $expense->eid);
    }

    private function validateUids($uids, $gid) {
        if (!is_array($uids))
            $uids = explode(',', $uids);
        // get member ids for $gid
        $sql = "SELECT GROUP_CONCAT(DISTINCT user_id) AS uids FROM users_groups WHERE group_id = :group_id";
        $stmt = Db::getInstance()->prepare($sql);
        $stmt->execute(
            array(
                ':group_id' => $gid
            )
        );
        $result = $stmt->fetchColumn();
        $validUids = explode(',', $result);
        foreach ($uids as $uid){
            if (!in_array($uid, $validUids))
                return false;
        }
        return true;
    }

    private function addExpenseEmail($expense, $eid, $type = 'add', $removedUids = array()){
        // error_log("START WITH EID " . $eid);

        $uids = explode(',', $expense->uids);
        $uid = array_pop(array_values($uids));
        $member = new \Models\Member();
        $groupsInfo = $member->getGroupsBalance($uid, false);

        $uidDetails = $this->getUserDetails(implode(',', array_keys($groupsInfo[$expense->gid]['members'])));

        // error_log(print_r($groupsInfo, 1));
        $groupName = $groupsInfo[$expense->gid]['name'];

        $created =  date('l jS \of F Y', $expense->ecreated);
        $from = 'goingdutch@santema.eu';

        // PHP Fatal error:  Class 'Models\NumberFormatter' not found
        // You just need to enable this extension in php.ini by uncommenting this line:
        //extension=ext/php_intl.dll
        $formatter = new \NumberFormatter('nl_NL', \NumberFormatter::CURRENCY);
        $amount = $formatter->formatCurrency($expense->amount, $groupsInfo[$expense->gid]['currency']);
        $amountpp = $formatter->formatCurrency($expense->amount/count($uids), $groupsInfo[$expense->gid]['currency']);

        switch($type) {
            case 'update':
                $subject = "Going Dutch expense updated in group \"{$groupName}\"";
                $messageTemplate  = "The expense made on {date} by {eowner} with {amount} and description \"{description}\" has been updated.<br /><br />\n{removed}";
                $messageTemplateEnd = "The costs per person are {amountpp} making your current balance {yourbalance} which comes to position {yourposition} in the group.\n";
                // error_log("Removed UIDS: " + implode(', ', $removedUids));
                break;
            case 'delete':
                $subject = "Going Dutch expense deleted in group \"{$groupName}\"";
                $messageTemplate  = "The expense on {date} made by {eowner} with {amount} and description \"{description}\" has been deleted.<br /><br />\n";
                $messageTemplateEnd = "The costs per person were {amountpp} making your current balance {yourbalance} which comes to position {yourposition} in the group.\n";
            break;
            default:
                $subject = "Going Dutch expense booked in group \"{$groupName}\"";
                $messageTemplate  = "On {date} {eowner} made an expense of {amount} with description \"{description}\".<br /><br />\n";
                $messageTemplateEnd = "The costs per person are {amountpp} making your current balance {yourbalance} which comes to position {yourposition} in the group.\n";
        }

        if (count($uids) == 1) {
            $messageTemplateOnlyPay = $messageTemplate . "{participants} was listed as the only participant (but you paid).<br /><br />\n";
            $messageTemplate .= "You were listed as the only participant.<br /><br />\n";
        }
        else {
            $messageTemplateOnlyPay = $messageTemplate . "{participants} were listed as the participants (but you paid).<br /><br />";
            $messageTemplate .= "You were listed as a participant, together with {participants}.<br /><br />\n";
        }

        $messageTemplateEnd .= "The balance list is now: <br /><br />{balancelist}\n<br /><br />\n";
        $messageTemplateEnd .= "This expense is logged with id {eid}.";
        $messageTemplate .= $messageTemplateEnd;
        $messageTemplateOnlyPay .= $messageTemplateEnd;

        //$message .= "<br /><br /><a href=\"".LOGIN_URL."\">Going Dutch</a>";

        $posArray = array();
        $balanceTable = "\n<table>\n";
        $i = 1;
        // error_log(print_r($uidDetails,1));
        foreach ($groupsInfo[$expense->gid]['members'] as $member){
            $posArray[$member['uid']] = $i;
            $b = $formatter->formatCurrency($member['balance'], $groupsInfo[$expense->gid]['currency']);
            // $style = $i < 0 ? '<style=\"color: red\">' : '<style = \"\">';
            // $balanceTable .= "<tr><td>{$i}</td><td>{$uidDetails[$member['uid']]['realname']}</td><td>{$style}{$b}</style></td></tr>\n";
            $balanceTable .= "<tr><td>{$i}</td><td>{$uidDetails[$member['uid']]['realname']}</td><td>{$b}</td></tr>\n";
            $i++;
        }
        $balanceTable .= "</table>\n";

        $onlyPay = false;
        if (!in_array($expense->uid, $uids)){
            $onlyPay = true;
            $uids[] = $expense->uid;
        }

        $uids = array_merge($uids, $removedUids);
        foreach ($uids as $uid) {
            if ($onlyPay && $uid == $expense->uid){
                $message = str_replace('{date}', $created, $messageTemplateOnlyPay);
            } else {
                $message = str_replace('{date}', $created, $messageTemplate);
            }
            // $style = $groupsInfo[$expense->gid]['members'][$uid]['balance'] < 0 ? '<style=\"color: red\">' : '<style = \"\">';
            $yourBalance = $formatter->formatCurrency($groupsInfo[$expense->gid]['members'][$uid]['balance'], $groupsInfo[$expense->gid]['currency']);
            // $yourBalance = $style . $yourBalance . '</style>';

            $message = str_replace('{eowner}', $expense->uid == $uid ? "you" : $uidDetails[$expense->uid]['realname'], $message);
            $message = str_replace('{amount}', $amount, $message);
            $message = str_replace('{amountpp}', $amountpp, $message);
            $message = str_replace('{yourbalance}', $yourBalance, $message);
            $message = str_replace('{description}', $expense->etitle, $message);
            $message = str_replace('{yourposition}', $posArray[$uid], $message);
            $message = str_replace('{balancelist}', $balanceTable, $message);
            $message = str_replace('{eid}', $eid, $message);
            if (in_array($uid, $removedUids)) {
                $message = str_replace('{removed}', "You are no longer listed as a participant for this expense.<br /><br />\n", $message);
            } else {
                $message = str_replace('{removed}', '', $message);
            }

            $participants = '';

            $count = count($uids) - count($removedUids) - ($onlyPay ? 1 : 0);
            // error_log("EID: " . $eid . " COUNT: " . $count . " Onlypay: " . $onlyPay . " Uids: " . implode(",", $uids) . ' UID: ' . $expense->uid);
            if ($count > 1) {
                // error_log("EID: " .  $eid . " COUNT: " . $count . " Onlypay: " . $onlyPay . " Uids: " . implode(",", $uids) . ' UID: ' . $expense->uid);

                foreach ($uids as $uidP) {
                    if ($uid == $uidP || ($onlyPay && $uidP == $expense->uid) || in_array($uidP, $removedUids))
                        continue;
                    $participants[] = $uidDetails[$uidP]['realname'];
                }
                $last = array_pop($participants);
                $participants = count($participants) ? implode(", ", $participants) . " and " . $last : $last;
            } elseif ($count == 1 && $onlyPay) {
                foreach ($uids as $uidP) {
                    if ($uidP != $expense->uid && !in_array($uidP, $removedUids)) {
                        $participants = $uidDetails[$uidP]['realname'];
                    }
                }
            }
            $message = str_replace('{participants}', $participants, $message);
            $to = $uidDetails[$uid]['email'];

            if (in_array($uid, $removedUids)){
                $message = preg_replace('/You were listed .*<br \/>/', '', $message);
                $message = preg_replace('/The costs per person .* current balance/', 'Your current balance is now', $message);
            }

            $sql = "INSERT INTO email (gid , eid, subject, message, toaddress, fromaddress, submitted)
                    VALUES (:gid, :eid, :subject, :message, :toaddress, :fromaddress, FROM_UNIXTIME(:submitted))";
            $stmt = Db::getInstance()->prepare($sql);
            $stmt->execute(
                array(
                    ':gid' => $expense->gid,
                    ':eid' => $eid,
                    ':subject' => $subject,
                    ':message' => $message,
                    ':toaddress' => $to,
                    ':fromaddress' => $from,
                    ':submitted' => time(),
                )
            );
        }

        $file = 'C:\xampp\htdocs\api.gdutch.nl\sendmail.php';

        //$cmd = "/usr/bin/php5 {$background_mailfile} {$user['email']} {$from} \"{$from_name}\" \"{$subject}\" \"{$body}\" \"{$replyto}\" \"{$sendas}\"";
        //exec("/usr/bin/php {$background_mailfile} {$user['email']} {$from} {$from_name} {$subject} {$body} {$replyto} {$sendas} > {$ouput} &");
        $cmd = "C:\\xampp\\php\\php.exe {$file}";
        $output = '/dev/null';
        // exec("{$cmd} > {$output} &");
        exec("{$cmd} ");
    }

    private function getUserDetails($uids) {
        $sql = "SELECT user_id, email, username, realname FROM users WHERE FIND_IN_SET (user_id, :uids)";
        $stmt = Db::getInstance()->prepare($sql);
        $stmt->execute(
            array(
                ':uids' => $uids
            )
        );
        $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $uidDetails = array();
        foreach ($result as $val){
            $uidDetails[$val['user_id']] = $val;
        }
        return $uidDetails;
    }

//    private function queueEmail($gid, $eid, $subject, $message, $to, $from){
//        if (!isset($expense->type))
//            $expense->type = 1;
//        $sql = "INSERT INTO expenses (type, cid, user_id, group_id, description, amount, expense_date, event_id, timestamp, currency, timezoneoffset)
//                VALUES (:type, :cid, :user_id, :group_id, :description, :amount, FROM_UNIXTIME(:created), :event_id, FROM_UNIXTIME(:updated), :currency, :timezoneoffset)";
//        $stmt = Db::getInstance()->prepare($sql);
//        $stmt->execute(
//            array(
//                ':type' => $expense->type,
//                ':cid' => $expense->cid,
//                ':user_id' => $expense->uid,
//                ':group_id' => $gid,
//                ':description' => utf8_decode($expense->etitle),
//                ':amount' => $expense->amount,
//                ':created' => $expense->ecreated,
//                ':updated' => $expense->eupdated,
//                ':event_id' => $expense->event_id,
//                ':timezoneoffset' => $expense->timezoneoffset,
//                ':currency' => 1
//            )
//        );
//        $eid = Db::getInstance()->lastInsertId();
//    }


    private function pdo_sql_debug($sql,$placeholders){
        foreach($placeholders as $k => $v){
            $sql = preg_replace('/'.$k.'/',"'".$v."'",$sql);
        }
        return $sql;
    }
}

/*
 * CREATE TABLE `Email` (
  `email_id` INT NOT NULL AUTO_INCREMENT,
  `gid` INT NOT NULL DEFAULT '0',
  `eid` INT NULL DEFAULT '0',
  `subject` TINYTEXT NULL,
  `message` TEXT NULL,
  `to` TEXT NULL,
  `from` TEXT NULL,
  `submitted` DATETIME NULL DEFAULT CURRENT_TIMESTAMP,
  `sent` DATETIME NULL DEFAULT '0',
  PRIMARY KEY (`email_id`)
)
  COLLATE='utf8_general_ci'
  ENGINE=InnoDB
;

 */