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

    function getExpense($gid, $eid)
    {
        $sql = $this->readExpensesSql . "AND expenses.expense_id = :eid";
        $stmt = Db::getInstance()->prepare($sql);
        $stmt->execute(array(':gid' => $gid, ':eid' => $eid));
        $expense = $stmt->fetch(\PDO::FETCH_ASSOC);
        $expense['etitle'] = utf8_encode($expense['etitle']);
        return json_encode($expense, JSON_NUMERIC_CHECK | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
    }

    function addExpense($gid, $expense){
        // ToDo: reject expense if uid or uids not in group uids
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
                ':description' => utf8_decode($expense->etitle),
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

        return $this->getExpense($gid, $eid);
    }

    function deleteExpense($gid, $eid)
    {
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
                ':description' => utf8_decode($expense->etitle),
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
        $result= $stmt->fetchColumn();
        $validUids = explode(',', $result);
        foreach ($uids as $uid){
            if (!in_array($uid, $validUids))
                return false;
        }
        return true;
    }

}