<?php
/**
 * Created by PhpStorm.
 * User: Whiskey
 * Date: 4-9-2015
 * Time: 23:07
 */

namespace Models;

use Db;

class Member
{
    function getGroupsBalance($uid)
    {
        // TODO: rewrite for all groups that this user is in (and all user balances in these groups)

        // get all the groups for this user
        $sql = "SELECT users_groups.group_id AS gid,
                groups.name, shortname AS role, UNIX_TIMESTAMP(join_date) AS join_date, groups.description,
                (SELECT COUNT(*) FROM users_groups WHERE group_id = gid) AS member_count,
                (SELECT GROUP_CONCAT(user_id) AS user_id_list FROM users_groups WHERE group_id = gid) AS user_id_list
                FROM `users_groups`, groups, roles
                WHERE users_groups.group_id = groups.group_id AND users_groups.role_id = roles.role_id
                AND user_id = :uid ORDER BY groups.name ASC";
        $stmt = Db::getInstance()->prepare($sql);
        $stmt->execute(array(':uid' => $uid));
        $groups = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $groups = $this->rearrangeArrayKey('gid', $groups);

        // Get the total expense for this user in each group
        $sql = "SELECT userid, group_id, SUM(expenses_summary.per_person) AS expense FROM (
                  SELECT users_expenses.user_id AS userid, group_id, users_expenses.expense_id AS exid, amount AS total_amount,
                    (SELECT COUNT(*) FROM users_expenses WHERE expense_id = exid) AS member_count,
                    (SELECT total_amount/member_count) AS per_person
                  FROM users_expenses, expenses WHERE users_expenses.expense_id = expenses.expense_id AND users_expenses.user_id = :uid
                  ORDER BY userid, group_id ASC, users_expenses.expense_id ASC
                  ) AS expenses_summary
                GROUP BY userid, group_id";
        $stmt = Db::getInstance()->prepare($sql);
        $stmt->execute(array(':uid' => $uid));
        $expense_summary = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $expense_summary = $this->rearrangeArrayKey('group_id', $expense_summary);

        // Get the total paid by this user in each group
        $sql = "SELECT user_id, group_id, SUM(amount) AS paid from expenses WHERE user_id = :uid GROUP BY user_id, group_id";
        $stmt = Db::getInstance()->prepare($sql);
        $stmt->execute(array(':uid' => $uid));
        $paid_summary = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $paid_summary = $this->rearrangeArrayKey('group_id', $paid_summary);

        // foreach group add expense, paid and balance
        foreach ($groups as $group_id => $group) {
            $expense = array_key_exists($group_id, $expense_summary) ? floatval($expense_summary[$group_id]['expense']) : 0;
            $paid = array_key_exists($group_id, $paid_summary) ? floatval($paid_summary[$group_id]['paid']) : 0;
            $groups[$group_id]['expense'] = $expense;
            $groups[$group_id]['paid'] = $paid;
            $groups[$group_id]['balance'] = $paid-$expense;
        }

        return json_encode(($groups));
    }

    /*
     * Rearranges an array of array to be indexed by one of the keys of the sub arrays
     * If a key occurs more than once, the last one overrides the previous array with that key
     * If the key is not found in the original sub arrays, the original array is returned
     */
    private function rearrangeArrayKey($keyname, $array)
    {
        $newArray = array();
        foreach ($array as $item) {
            if (array_key_exists($keyname,$item)) {
                $newArray[$item[$keyname]] = $item;
            }
        }
        return empty($newArray) ? $array : $newArray;
    }

}