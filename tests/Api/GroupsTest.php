<?php
//namespace Slim\Tests;


//DELETE FROM expenses where expense_id > 570;
//DELETE FROM users_expenses where expense_id > 570;

use Slim\App;
use GuzzleHttp\Client;

class GroupsTest extends \PHPUnit_Framework_TestCase
{
    protected $client;

    protected $knownuser  = array('user_id' => 1, 'name' => 'whiskey', 'pass' => 'testpassword');
    protected $knownuser2 = array('user_id' => 2, 'name' => 'monc', 'email' => 'exitspam-daan@yahoo.com', 'pass' => 'testpassword');
    protected $knownuser3 = array('user_id' => 3, 'name' => 'jeepee', 'email' => 'exitspam-jp@yahoo.com', 'pass' => 'testpassword');
    protected $knownuser4 = array('user_id' => 4, 'name' => 'martijn', 'email' => 'exitspam-martijn@yahoo.com', 'pass' => 'testpassword');
    protected $unknownuser = array('name' => 'whiskea', 'pass' => 'testpassword');
    protected $gid = 1;
    protected $eid = 1;
    protected function setUp()
    {
        $this->client = new GuzzleHttp\Client([
            'base_uri' => 'http://api.gdutch.dev',
            'defaults' => ['exceptions' => false]
        ]);
    }

    public function testGroupsArrayStructure()
    {
        $response = $this->client->get('/groups', ['auth' => [$this->knownuser['name'], $this->knownuser['pass']]]);
        $content = $response->getBody()->getContents();
        $resultArray = json_decode($content, true);

        foreach ($resultArray as $group) {
            $keysToCheck = array('gid', 'currency', 'sort', 'name', 'description', 'balance', 'members', 'balance', 'categories');
            foreach ($keysToCheck as $key)
                $this->assertArrayHasKey($key, $group, "Key '{$key}' not found in array of group {$group['gid']}");

            $this->assertInternalType('array', $group['members']);
            $this->assertGreaterThan(0, count($group['members']));
            $resultMemberArray = $group['members'];

            // check if user_id_list uids are all in members array
            $memberList = explode(',', $group['user_id_list']);
            $memberListCount = count($memberList);
            $resultMemberArrayCount = count($resultMemberArray);
            $this->assertEquals($memberListCount, $resultMemberArrayCount, "Member count in user_id_list ({$memberListCount}) does not match member array count ({$resultMemberArrayCount}) in group {$group['gid']}");

            foreach ($memberList as $uid) {
                $this->assertArrayHasKey($uid, $resultMemberArray, "Member '{uid}' was listed 'user_id_list', but not found in 'members' array of group {$group['gid']}");
            }

            $keysToCheck = array('paid', 'expense', 'balance', 'uid');
            $totalscheck = array('paid' => 0, 'expense' => 0, 'balance' => 0);
            foreach ($resultMemberArray as $uid => $member) {
                foreach ($keysToCheck as $key)
                    $this->assertArrayHasKey($key, $member, "Key '{$key}' not found in 'members' array at index/uid {$uid} of group {$group['gid']}");
                $msg_b = "Balance for member {$uid} in group {$group['gid']} ";
                if ($member['paid'] > $member['expense']) {
                    $msg = $msg_b . "should be positive, but is not (paid: {$member['paid']} | expense: {$member['expense']} | balance: {$member['balance']})";
                    $this->assertGreaterThan(0, $member['balance'], $msg);
                } elseif ($member['paid'] < $member['expense']) {
                    $msg = $msg_b . "should be negative, but is not (paid: {$member['paid']} | expense: {$member['expense']} | balance: {$member['balance']})";
                    $this->assertLessThan(0, $member['balance'], $msg);
                } else {
                    $msg = $msg_b . "should be zero, but is not (paid: {$member['paid']} | expense: {$member['expense']} | balance: {$member['balance']})";
                    $this->assertEquals(0, $member['balance'], $msg);
                }
                $totalscheck['paid'] += $member['paid'];
                $totalscheck['expense'] += $member['expense'];
                $totalscheck['balance'] += $member['balance'];
            }

            // check group totals and balance
            $group_balance = round($totalscheck['paid'] - $totalscheck['expense'], 2);
            $this->assertEquals(0, $group_balance, "Paid totals are not equal to expense totals for group {$group['gid']} ( total paid: {$totalscheck['paid']} | total expense: {$totalscheck['expense']})");
            $this->assertEquals(0, round($totalscheck['balance'], 2), "Total balance for group {$group['gid']} is not zero (but {$totalscheck['balance']}");

            $this->assertInternalType('array', $group['categories']);
            $this->assertGreaterThan(0, count($group['categories']));
            $keysToCheck = array('cid', 'group_id', 'title', 'presents', 'inactive', 'can_delete', 'sort');
            foreach ($group['categories'] as $category) {
                foreach ($keysToCheck as $key)
                    $this->assertArrayHasKey($key, $category, "Key '{$key}' not found in 'categories' array of group {$group['gid']}");
            }
        }

    }

    public function testUpdateGroupDetails()
    {
        $response = $this->client->get('/groups', ['auth' => [$this->knownuser['name'], $this->knownuser['pass']]]);
        $content = $response->getBody()->getContents();
        $resultArray = json_decode($content, true);

        $group = array_shift($resultArray);

        $gid = $group['gid'];

        $currentDetails = array(
            $gid =>
                array(
                    'gid' => $gid,
                    'currency' => $group['currency'],
                    'name' => $group['name'],
                    'description' => $group['description']
                )
        );

        $now = ' @' . date("H:i");

        $newDetails = array(
            $gid =>
                array(
                    'gid' => $gid,
                    'currency' => $group['currency'] == 'EUR' ? 'USD' : 'EUR',
                    'name' => $group['name'] . $now,
                    'description' => $group['description'] . $now
                )
        );

        $response = $this->client->request('PUT', "/groups", ['auth' => [$this->knownuser['name'], $this->knownuser['pass']], 'json' => $newDetails]);
        $content = $response->getBody()->getContents();
        $resultArray = json_decode($content, true);
        $this->assertArrayHasKey($gid, $resultArray, "UpdateGroupDetails: Group id key '{$gid}' not found in returned array");

        foreach ($newDetails[$gid] as $key => $val) {
            $this->assertArrayHasKey($key, $resultArray[$gid], "UpdateGroupDetails: Key '{$key}' not found in updated group");
            $this->assertEquals($val, $resultArray[$gid][$key], "UpdateGroupDetails: '{$key}' not equal to value of updated group (expected {$val}, got {$resultArray[$gid][$key]})");
        }
        // restore old values
        $response = $this->client->request('PUT', "/groups", ['auth' => [$this->knownuser['name'], $this->knownuser['pass']], 'json' => $currentDetails]);
        $content = $response->getBody()->getContents();
    }

    public function testUpdateGroupCategories()
    {
        $response = $this->client->get('/groups', ['auth' => [$this->knownuser['name'], $this->knownuser['pass']]]);
        $content = $response->getBody()->getContents();
        $resultArray = json_decode($content, true);

        $group = array_shift($resultArray);
        $gid = $group['gid'];

        $currentCategories = $group['categories'];
        $newCategories = $currentCategories;
        $count = 1;

        foreach ($newCategories as &$category){
            $category['title'] = $category['title'] . ' #' . $count;
            $category['sort'] = $category['sort']++;
            $count++;
        }

        $response = $this->client->request('PUT', "/group/{$gid}/categories", ['auth' => [$this->knownuser['name'], $this->knownuser['pass']], 'json' => $newCategories]);
        $content = $response->getBody()->getContents();
        $resultArray = json_decode($content, true);

        // check the count of categories
        $newCatCount = count($newCategories);
        $resultCatCount = count($resultArray);
        $this->assertEquals($newCatCount, $resultCatCount, "UpdateGroupCategories: category count of submitted categories ({$newCatCount}) is not equal to count of returned categories ({$resultCatCount})");

        // check the values of each category
        foreach ($newCategories as $category){
            $this->assertArrayHasKey($category['cid'], $resultArray, "UpdateGroupCategories: Category id key '{$category['cid']}' not found in returned array");
            foreach ($category as $key => $value){
                $this->assertEquals($value, $resultArray[$category['cid']][$key], "UpdateGroupCategories: '{$key}' not equal to value of updated category (expected {$value}, got {$resultArray[$category['cid']][$key]})");
            }
        }

        // restore old values
        $response = $this->client->request('PUT', "/group/{$gid}/categories", ['auth' => [$this->knownuser['name'], $this->knownuser['pass']], 'json' => $currentCategories]);
        $content = $response->getBody()->getContents();
    }

    public function testAddDeleteGroupCategory()
    {
        $response = $this->client->get('/groups', ['auth' => [$this->knownuser['name'], $this->knownuser['pass']]]);
        $content = $response->getBody()->getContents();
        $resultArray = json_decode($content, true);

        $group = array_pop($resultArray);
        $gid = $group['gid'];

        $currentCategories = $group['categories'];
        $newCategories = $currentCategories;

        // change sort so new category is first
        foreach ($newCategories as &$category){
            $category['sort'] = $category['sort']++;
        }

        $extraCategory = array(
            "cid" => 0,
            "group_id" => $gid,
            "title" => "New category!",
            "presents" => 0,
            "inactive" => 0,
            "can_delete" => 0,
            "sort" => 1
        );

        $allCategories = $newCategories;
        $allCategories[] = $extraCategory;

        $response = $this->client->request('PUT', "/group/{$gid}/categories", ['auth' => [$this->knownuser['name'], $this->knownuser['pass']], 'json' => $allCategories]);
        $content = $response->getBody()->getContents();
        $resultArray = json_decode($content, true);

        // check the count of categories
        $newCatCount = count($allCategories);
        $resultCatCount = count($resultArray);
        $this->assertEquals($newCatCount, $resultCatCount, "AddGroupCategory: category count of submitted categories ({$newCatCount}) is not equal to count of returned categories ({$resultCatCount})");

        // check the values of each existing category
        foreach ($newCategories as $category){
            $this->assertArrayHasKey($category['cid'], $resultArray, "testAddGroupCategory: Category id key '{$category['cid']}' not found in returned array for group {$gid}");
            foreach ($category as $key => $value){
                $this->assertEquals($value, $resultArray[$category['cid']][$key], "testAddGroupCategory: '{$key}' not equal to value of updated category for group {$gid} (expected {$value}, got {$resultArray[$category['cid']][$key]})");
            }
        }

        // remove checked categories
        foreach ($newCategories as $key => $category){
            unset($resultArray[$key]);
        }

        // check only one category left
        $leftCount = count($resultArray);
        $this->assertEquals(1, $leftCount, "testAddGroupCategory: expected 1 added category, but got {$leftCount})");

        // check newly added category
        $newCategory = array_pop($resultArray);
        foreach ($newCategory as $key => $value){
            if ($key == 'cid')
                continue;
            $this->assertEquals($value, $extraCategory[$key], "testAddGroupCategory: '{$key}' not equal to value of extra added category (expected {$value}, got {$extraCategory[$key]})");
        }

        // restore old values
        $response = $this->client->request('PUT', "/group/{$gid}/categories", ['auth' => [$this->knownuser['name'], $this->knownuser['pass']], 'json' => $currentCategories]);
        $content = $response->getBody()->getContents();

        $resultArray = json_decode($content, true);

        // check the count of categories
        $newCatCount = count($currentCategories);
        $resultCatCount = count($resultArray);
        $this->assertEquals($newCatCount, $resultCatCount, "AddGroupCategory: category count of submitted categories ({$newCatCount}) is not equal to count of returned categories ({$resultCatCount})");

    }

    public function testDeleteUsedGroupCategory()
    {
        $response = $this->client->get("/group/{$this->gid}/expenses/{$this->eid}", ['auth' => [$this->knownuser['name'], $this->knownuser['pass']]]);
        $content = $response->getBody()->getContents();
        $resultArray = json_decode($content, true);

        $cid = $resultArray['cid'];

        $response = $this->client->get('/groups', ['auth' => [$this->knownuser['name'], $this->knownuser['pass']]]);
        $content = $response->getBody()->getContents();
        $resultArray = json_decode($content, true);

        $group = $resultArray[$this->gid];
        $currentCategories = $group['categories'];
        $newCategories = $currentCategories;
        unset($newCategories[$cid]);

        $response = $this->client->request('PUT', "/group/{$this->gid}/categories", ['auth' => [$this->knownuser['name'], $this->knownuser['pass']], 'json' => $newCategories]);
        $content = $response->getBody()->getContents();
        $resultArray = json_decode($content, true);

        // check the count of categories
        $newCatCount = count($newCategories) + 1;   // +1 because the category should not have been deleted
        $resultCatCount = count($resultArray);
        $this->assertEquals($newCatCount, $resultCatCount, "DeleteUsedGroupCategory: category count of submitted categories ({$newCatCount}) +1 is not equal to count of returned categories ({$resultCatCount}). Category {$cid} of group {$this->gid} was deleted which should not happen!");

        $response = $this->client->request('PUT', "/group/{$this->gid}/categories", ['auth' => [$this->knownuser['name'], $this->knownuser['pass']], 'json' => $currentCategories]);
        $content = $response->getBody()->getContents();
    }

    public function testAddDeleteNewGroup()
    {
        $newDetails = array();
        $newDetails['currency'] = "EUR";
        $newDetails['name'] = "NewGroupTest-" . time();
        $newDetails['description'] = "NewGroupTest @ " . time();

        $response = $this->client->request('POST', "/group", ['auth' => [$this->knownuser['name'], $this->knownuser['pass']], 'json' => $newDetails]);
        $content = $response->getBody()->getContents();
        $resultArray = json_decode($content, true);

        $this->assertInternalType('array',$resultArray, "AddDeleteNewGroup: Response is not of type array");
        $this->assertArrayHasKey('success', $resultArray, "AddDeleteNewGroup: Key 'success' not found in response when adding new group");
        $this->assertArrayHasKey('gid', $resultArray, "AddDeleteNewGroup: Key 'gid' not found in response when adding new group");
        $this->assertEquals(1, $resultArray['success'], "AddDeleteNewGroup: Could not add new group");
        $this->assertGreaterThan(1, $resultArray['gid'], "AddDeleteNewGroup: new gid not greater than 1");
        $gid = $resultArray['gid'];

        // check if group was correctly added
        $response = $this->client->get('/groups', ['auth' => [$this->knownuser['name'], $this->knownuser['pass']]]);
        $content = $response->getBody()->getContents();
        $resultArray = json_decode($content, true);
        $this->assertArrayHasKey($gid, $resultArray, "AddDeleteNewGroup: Key for new group id not found in groups array response when checking groups");
        $this->assertEquals($newDetails['currency'], $resultArray[$gid]['currency'], "AddDeleteNewGroup: currency not correct for new group");
        $this->assertEquals($newDetails['name'], $resultArray[$gid]['name'], "AddDeleteNewGroup: name not correct for new group");
        $this->assertEquals($newDetails['description'], $resultArray[$gid]['description'], "AddDeleteNewGroup: description not correct for new group");

        // Add a single existing user to the group by email
        $userDetails = array('emails');
        $userDetails['emails'][] = $this->knownuser2['email'];
        $response = $this->client->request('POST', "/group/{$gid}/members", ['auth' => [$this->knownuser['name'], $this->knownuser['pass']], 'json' => $userDetails]);
        $content = $response->getBody()->getContents();
        $resultArray = json_decode($content, true);
        $this->assertArrayHasKey('success', $resultArray, "AddDeleteNewGroup: Key 'success' not found in response when adding user to new group");
        $this->assertEquals(1, $resultArray['success'], "AddDeleteNewGroup: Could not add user to new group " . $gid);

        // Add multiple existing users to the group by email
        $userDetails = array('emails');
        $userDetails['emails'][] = $this->knownuser3['email'];
        $userDetails['emails'][] = $this->knownuser4['email'];
        $response = $this->client->request('POST', "/group/{$gid}/members", ['auth' => [$this->knownuser['name'], $this->knownuser['pass']], 'json' => $userDetails]);
        $content = $response->getBody()->getContents();
        $resultArray = json_decode($content, true);
        $this->assertArrayHasKey('success', $resultArray, "AddDeleteNewGroup: Key 'success' not found in response when adding user to new group");
        $this->assertEquals(1, $resultArray['success'], "AddDeleteNewGroup: Could not add user to new group " . $gid);

        // Check users have access to the group
        $addedUsers = array($this->knownuser2, $this->knownuser3, $this->knownuser4);
        foreach($addedUsers as $cUser) {
            $response = $this->client->get('/groups', ['auth' => [$cUser['name'], $cUser['pass']]]);
            $content = $response->getBody()->getContents();
            $resultArray = json_decode($content, true);
            $this->assertArrayHasKey($gid, $resultArray, "AddDeleteNewGroup: Key for new group id not found in groups array response of added user when checking groups");
        }

        // add expense in group
        $expense = array(
            'gid' => $gid,
            'cid' => 1,
            'uid' => $this->knownuser['user_id'],
            'uids' => $this->knownuser['user_id'] . ',' . $this->knownuser2['user_id']. ',' . $this->knownuser3['user_id'],
            'group_id' => $gid,
            'etitle' => 'New group test expense 1',
            'amount' => 12.33,
            'ecreated' => time(),
            'eupdated' => 0,
            'event_id' => 0,
            'timezoneoffset' => 120,
            'currency' => 1
        );

        $response = $this->client->request('POST', "/group/{$gid}/expenses", ['auth' => [$this->knownuser['name'], $this->knownuser['pass']], 'json' => $expense]);
        $content = $response->getBody()->getContents();
        $resultArray = json_decode($content, true);

        // Check balance user2 and user3 is -4.11 and user1 = 8.22
        $response = $this->client->get('/groups', ['auth' => [$this->knownuser['name'], $this->knownuser['pass']]]);
        $content = $response->getBody()->getContents();
        $resultArray = json_decode($content, true);
        $this->assertArrayHasKey($gid, $resultArray, "AddDeleteNewGroup: Key for group " . $gid . " not found in groups call");
        $this->assertEquals(8.22, $resultArray[$gid]['members'][$this->knownuser['user_id']]['balance'], "AddDeleteNewGroup: Incorrect balance for user " . $this->knownuser['user_id'] ." in new group " . $gid);
        $this->assertEquals(-4.11, $resultArray[$gid]['members'][$this->knownuser2['user_id']]['balance'], "AddDeleteNewGroup: Incorrect balance for user " . $this->knownuser2['user_id'] ." in new group " . $gid);
        $this->assertEquals(-4.11, $resultArray[$gid]['members'][$this->knownuser3['user_id']]['balance'], "AddDeleteNewGroup: Incorrect balance for user " . $this->knownuser3['user_id'] ." in new group " . $gid);

        $expense = array(
            'gid' => $gid,
            'cid' => 1,
            'uid' => $this->knownuser2['user_id'],
            'uids' => $this->knownuser['user_id'] . ',' . $this->knownuser3['user_id']. ',' . $this->knownuser4['user_id'],
            'group_id' => $gid,
            'etitle' => 'New group test expense 2',
            'amount' => 15.66,
            'ecreated' => time(),
            'eupdated' => 0,
            'event_id' => 0,
            'timezoneoffset' => 120,
            'currency' => 1
        );
        $response = $this->client->request('POST', "/group/{$gid}/expenses", ['auth' => [$this->knownuser['name'], $this->knownuser['pass']], 'json' => $expense]);
        $content = $response->getBody()->getContents();
        $resultArray = json_decode($content, true);

        $response = $this->client->get('/groups', ['auth' => [$this->knownuser['name'], $this->knownuser['pass']]]);
        $content = $response->getBody()->getContents();
        $resultArray = json_decode($content, true);
        $this->assertEquals(11.55, $resultArray[$gid]['members'][$this->knownuser2['user_id']]['balance'], "AddDeleteNewGroup: Incorrect balance for user " . $this->knownuser2['user_id'] ." in new group " . $gid);
        $this->assertEquals(3.00,  $resultArray[$gid]['members'][$this->knownuser['user_id']]['balance'], "AddDeleteNewGroup: Incorrect balance for user " . $this->knownuser['user_id'] ." in new group " . $gid);
        $this->assertEquals(-9.33, $resultArray[$gid]['members'][$this->knownuser3['user_id']]['balance'], "AddDeleteNewGroup: Incorrect balance for user " . $this->knownuser3['user_id'] ." in new group " . $gid);
        $this->assertEquals(-5.22, $resultArray[$gid]['members'][$this->knownuser4['user_id']]['balance'], "AddDeleteNewGroup: Incorrect balance for user " . $this->knownuser4['user_id'] ." in new group " . $gid);

        // make another user also admin by non admin
        $userDetails = array('role_id' => 1);
        $response = $this->client->request('PUT', "/group/{$gid}/members/{$this->knownuser3['user_id']}", ['auth' => [$this->knownuser2['name'], $this->knownuser2['pass']], 'json' => $userDetails]);
        $content = $response->getBody()->getContents();
        $resultArray = json_decode($content, true);
        $this->assertArrayHasKey('success', $resultArray, "AddDeleteNewGroup: Key 'success' not found in response when making user admin in new group");
        $this->assertEquals(0, $resultArray['success'], "AddDeleteNewGroup: Could not make user admin in new group " . $gid);
        $this->assertArrayHasKey('invalid_request', $resultArray, "AddDeleteNewGroup: Key 'invalid-request' not found in response when making user admin in new group");
        $this->assertEquals(1, $resultArray['invalid_request'], "AddDeleteNewGroup: non-admin user could make user admin in new group " . $gid);

        // make another user also admin by admin
        $userDetails = array('role_id' => 1);
        $response = $this->client->request('PUT', "/group/{$gid}/members/{$this->knownuser3['user_id']}", ['auth' => [$this->knownuser['name'], $this->knownuser['pass']], 'json' => $userDetails]);
        $content = $response->getBody()->getContents();
        $resultArray = json_decode($content, true);
        $this->assertArrayHasKey('success', $resultArray, "AddDeleteNewGroup: Key 'success' not found in response when making user admin in new group");
        $this->assertEquals(1, $resultArray['success'], "AddDeleteNewGroup: Could not make user admin in new group " . $gid);

        // set send emails to false for a user
        $userDetails = array('send_email' => 0);
        $response = $this->client->request('PUT', "/group/{$gid}/members/{$this->knownuser4['user_id']}/email", ['auth' => [$this->knownuser4['email'], $this->knownuser4['pass']], 'json' => $userDetails]);
        $content = $response->getBody()->getContents();
        $resultArray = json_decode($content, true);
        $this->assertArrayHasKey('success', $resultArray, "AddDeleteNewGroup: Key 'success' not found in response when setting email to false in new group");
        $this->assertEquals(1, $resultArray['success'], "AddDeleteNewGroup: Could not set email to false for user in new group " . $gid);

        $response = $this->client->get('/groups', ['auth' => [$this->knownuser['name'], $this->knownuser['pass']]]);
        $content = $response->getBody()->getContents();
        $resultArray = json_decode($content, true);
        $this->assertEquals(0, $resultArray[$gid]['members'][$this->knownuser4['user_id']]['send_mail'], "AddDeleteNewGroup: Send mail not set to false for user " . $this->knownuser4['user_id'] ." in new group " . $gid);

        // remove user from the group
//        $userDetails = array('user_ids');
//        $userDetails['user_ids'][] = $this->knownuser2['user_id'];
//        $response = $this->client->request('DELETE', "/group/{$gid}/members", ['auth' => [$this->knownuser['name'], $this->knownuser['pass']], 'json' => $userDetails]);
        $response = $this->client->request('DELETE', "/group/{$gid}/members/{$this->knownuser2['user_id']}", ['auth' => [$this->knownuser['name'], $this->knownuser['pass']], 'json' => $userDetails]);
        $content = $response->getBody()->getContents();
        $resultArray = json_decode($content, true);
        $this->assertArrayHasKey('success', $resultArray, "AddDeleteNewGroup: Key 'success' not found in response when removing user from new group");
        $this->assertEquals(1, $resultArray['success'], "AddDeleteNewGroup: Could not remove user from new group " . $gid);

        // Try to delete group as wrong user
//        $delDetails = array('gid' => $gid);
//        $response = $this->client->request('DELETE', "/group", ['auth' => [$this->knownuser2['name'], $this->knownuser['pass']], 'json' => $delDetails]);
        $response = $this->client->request('DELETE', "/group/{$gid}", ['auth' => [$this->knownuser2['name'], $this->knownuser['pass']]]);
        $content = $response->getBody()->getContents();
        $resultArray = json_decode($content, true);
        $this->assertArrayHasKey('success', $resultArray, "AddDeleteNewGroup: Key 'success' not found in response when deleting group");
        $this->assertEquals(0, $resultArray['success'], "AddDeleteNewGroup: Could delete group " . $gid . " as non-admin");

        // Delete group
//        $delDetails = array('gid' => $gid);
//        $response = $this->client->request('DELETE', "/group", ['auth' => [$this->knownuser['name'], $this->knownuser['pass']], 'json' => $delDetails]);
        $response = $this->client->request('DELETE', "/group/{$gid}", ['auth' => [$this->knownuser['name'], $this->knownuser['pass']]]);
        $content = $response->getBody()->getContents();
        $resultArray = json_decode($content, true);
        $this->assertArrayHasKey('success', $resultArray, "AddDeleteNewGroup: Key 'success' not found in response when deleting group");
        $this->assertEquals(1, $resultArray['success'], "AddDeleteNewGroup: Could not delete group " . $gid);
    }
}