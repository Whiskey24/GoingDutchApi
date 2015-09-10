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

    function getExpenses($gid)
    {
        $sql = "SELECT expense_id AS eid, description AS etitle, user_id as uid,
                amount, description as etitle, amount, UNIX_TIMESTAMP(expense_date) AS ecreated,
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
                WHERE expenses.group_id = :gid
                ORDER BY expense_date DESC, eid DESC";
        $stmt = Db::getInstance()->prepare($sql);
        $stmt->execute(array(':gid' => $gid));
        // put the results in an array with gid as key
        $expense_list = array($gid => $stmt->fetchAll(\PDO::FETCH_ASSOC));

        //$expense_list = Member::rearrangeArrayKey('eid', $expense_list);


        return json_encode($expense_list, JSON_NUMERIC_CHECK | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
    }
}