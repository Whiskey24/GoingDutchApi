<?php
//namespace Slim\Tests;


//DELETE FROM expenses where expense_id > 570;
//DELETE FROM users_expenses where expense_id > 570;

use Slim\App;
use GuzzleHttp\Client;

class GroupsTest extends \PHPUnit_Framework_TestCase
{
    protected $client;

    protected $knownuser = array('name' => 'whiskey', 'pass' => 'testpassword');
    protected $unknownuser = array('name' => 'whiskea', 'pass' => 'testpassword');

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


}