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
        foreach ($expense_list[$gid] as $key => $expense)
        {
            $expense_list[$gid][$key]['etitle'] = utf8_encode($expense['etitle']);
        }
        //$expense_list = Member::rearrangeArrayKey('eid', $expense_list);
        return json_encode($expense_list, JSON_NUMERIC_CHECK | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
    }

    function getExpensesDel($gid)
    {
        $sql = $this->readExpensesDelSql . "ORDER BY expense_date DESC, eid DESC";
        $stmt = Db::getInstance()->prepare($sql);
        $stmt->execute(array(':gid' => $gid));
        // put the results in an array with gid as key
        $expense_list = array($gid => $stmt->fetchAll(\PDO::FETCH_ASSOC));
        foreach ($expense_list[$gid] as $key => $expense)
        {
            $expense_list[$gid][$key]['etitle'] = utf8_encode($expense['etitle']);
        }
        //$expense_list = Member::rearrangeArrayKey('eid', $expense_list);
        return json_encode($expense_list, JSON_NUMERIC_CHECK | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
    }

    function getExpense($gid, $eid, $json = true)
    {
        $sql = $this->readExpensesSql . "AND expenses.expense_id = :eid";
        $stmt = Db::getInstance()->prepare($sql);
        $stmt->execute(array(':gid' => $gid, ':eid' => $eid));
        $expense = $stmt->fetch(\PDO::FETCH_ASSOC);
        $expense['etitle'] = utf8_encode($expense['etitle']);
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
        $expense['etitle'] = utf8_encode($expense['etitle']);
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

        return $eid;
    }

    function updateExpense($gid, $expense){
        $uids = $expense->uids . ',' . $expense->uid;
        if (!$this->validateUids($uids, $gid)){
            return 'Error: invalid uids';
        }

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

    private function addExpenseEmail($expense, $eid){
        $uidDetails = $this->getUserDetails($expense->uids);


        //        error_log( print_r($uidDetails, 1));
        $uids = explode(',', $expense->uids);
        $uid = array_pop(array_values($uids));
        $member = new \Models\Member();
        $groupsInfo = $member->getGroupsBalance($uid, false);

        $groupName = $groupsInfo[$expense->gid]['name'];
        $subject = "Going Dutch expense booked in group \"{$groupName}\"";
        $created =  date('l jS \of F Y', $expense->ecreated);
        $from = 'goingdutch@santema.eu';

        // PHP Fatal error:  Class 'Models\NumberFormatter' not found
        // You just need to enable this extension in php.ini by uncommenting this line:
        //extension=ext/php_intl.dll
        $formatter = new \NumberFormatter('nl_NL', \NumberFormatter::CURRENCY);
        $amount = $formatter->formatCurrency($expense->amount, $groupsInfo[$expense->gid]['currency']);

//        $locale = 'nl-NL'; //browser or user locale
//        $currency = $groupsInfo[$expense->gid]['currency'];
//        $fmt = new \NumberFormatter( $locale."@currency=$currency", \NumberFormatter::CURRENCY );
//        $cSymbol = $fmt->getSymbol(\NumberFormatter::CURRENCY_SYMBOL);
        //header("Content-Type: text/html; charset=UTF-8;");
        //echo $symbol;


        $messageTemplate  = "On {date} {eowner} booked an expense of {amount} with description \"{description}\".<br /><br />";
        $messageTemplate .= "You were listed as a participant, together with {participants}.<br /><br />";
        $messageTemplate .= "The costs per person are {amountpp} making your balance {yourbalance} which comes to position {yourposition} in the group. ";
        $messageTemplate .= "The balance list is now: <br /><br />{balancelist}";
        //$message .= "<br /><br /><a href=\"".LOGIN_URL."\">Going Dutch</a>";

        foreach ($uids as $uid) {
            $message = str_replace('{date}', $created, $messageTemplate);
            $message = str_replace('{eowner}', $expense->uid == $uid ? "you" : $uidDetails[$uid]['realname'], $message);
            $message = str_replace('{amount}', $amount, $message);
            $message = str_replace('{description}', $expense->etitle, $message);

            $to = $uidDetails[$uid]['email'];

            $sql = "INSERT INTO email (gid , eid, subject, message, toaddress, fromaddress, submitted)
                    VALUES (:gid, :eid, :subject, :message, :toaddress, :fromaddress, FROM_UNIXTIME(:submitted))";
//            $sql = "INSERT INTO email (gid , eid)
//                    VALUES (:gid, :eid)";
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
//            error_log($amount);
//            error_log(utf8_decode($amount));

//            error_log($this->pdo_sql_debug($sql,
//                                array(
//                    ':gid' => $expense->gid,
//                    ':eid' => $eid,
////                    ':subject' => $subject,
////                    ':message' => $message,
////                    ':toaddress' => $to,
////                    ':fromaddress' => $from,
////                    ':submitted' => time()
//                )
//            ));
        }


//
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