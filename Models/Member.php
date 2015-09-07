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
        // get all the groups for this user
        $sql = "SELECT users_groups.group_id AS gid, groups.currency, sort,
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

        // get all the users that are members of these groups from the concatenated field
        $user_id_list = array();
        foreach ($groups as $group)
            $user_id_list = array_merge($user_id_list, explode(',',$group['user_id_list']));
        $user_id_list = implode(',', array_unique($user_id_list));

        // get group list for use in query
        $group_id_list = implode(',', array_keys($groups));

        // Get the total expense for all users in each group
        // Use find in set, see here: http://stackoverflow.com/questions/1586587/pdo-binding-values-for-mysql-in-statement
        $sql = "SELECT userid, group_id, SUM(expenses_summary.per_person) AS expense FROM (
                    SELECT users_expenses.user_id AS userid, group_id, users_expenses.expense_id AS exid,
                    amount AS total_amount,
                    (SELECT COUNT(*) FROM users_expenses WHERE expense_id = exid) AS member_count,
                    (SELECT total_amount/member_count) AS per_person
                  FROM users_expenses, expenses WHERE users_expenses.expense_id = expenses.expense_id
                  AND FIND_IN_SET(users_expenses.user_id, :uids) AND FIND_IN_SET(expenses.group_id, :gids)) AS expenses_summary
                GROUP BY userid, group_id";
        $stmt = Db::getInstance()->prepare($sql);
        $stmt->execute(array(':uids' => $user_id_list, 'gids' => $group_id_list));
        $expense_summary = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // add expense costs for each user to groups array, skip groups for which this user is not a member
        foreach ($expense_summary as $userJoined){
            if (!array_key_exists($userJoined['group_id'], $groups))
                continue;
            if (!array_key_exists('members', $groups[$userJoined['group_id']]))
                $groups[$userJoined['group_id']]['members'] = array();
            $groups[$userJoined['group_id']]['members'][$userJoined['userid']] =
                array('expense' => floatval($userJoined['expense']), 'paid' => 0, 'balance' => -floatval($userJoined['expense']));
        }

        // Get the total paid by this user in each group
        $sql = "SELECT user_id, group_id, SUM(amount) AS paid FROM expenses WHERE FIND_IN_SET(expenses.user_id, :uids) GROUP BY user_id, group_id";
        $stmt = Db::getInstance()->prepare($sql);
        $stmt->execute(array(':uids' => $user_id_list));
        $paid_summary = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // add paid balance for each user to groups array, skip groups for which this user is not a member
        foreach ($paid_summary as $userPaid){
            $gid = $userPaid['group_id'];
            $p_uid = $userPaid['user_id'];
            if (!array_key_exists($gid, $groups))
                continue;
            if (!array_key_exists('members', $groups[$gid]))
                $groups[$gid]['members'] = array();
            if (!array_key_exists($p_uid , $groups[$gid]['members'])) {
                $groups[$gid]['members'][$p_uid] =
                    array('expense' => 0, 'paid' => 0, 'balance' => 0);
            }
            $groups[$gid]['members'][$p_uid]['paid'] = floatval($userPaid['paid']);
            $groups[$gid]['members'][$p_uid]['balance'] = floatval($userPaid['paid']) - $groups[$gid]['members'][$p_uid]['expense'];
        }

        foreach ($groups as &$group){
            $group['balance'] = $group['members'][$uid]['balance'];
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
            if (array_key_exists($keyname, $item)) {
                $newArray[$item[$keyname]] = $item;
            }
        }
        return empty($newArray) ? $array : $newArray;
    }
}