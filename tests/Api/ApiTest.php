<?php
//namespace Slim\Tests;

use Slim\App;
use GuzzleHttp\Client;

class ApiTest extends \PHPUnit_Framework_TestCase
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

    public function testAuthorizeExistingUserCorrectPass()
    {
        $response = $this->client->get('/version', ['auth' => [$this->knownuser['name'], $this->knownuser['pass']]]);

        $result = $response->getBody()->getContents();
        $expected = 'Going Dutch API v';

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertContains($expected, $result);
    }

    public function testAuthorizeExistingUserWrongPass()
    {
        try {
            $response = $this->client->get('/version', ['auth' => [$this->knownuser['name'], $this->knownuser['pass'] . '123']]);
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            $response = $e->getResponse();
        }
        $result = $response->getBody()->getContents();
        $expected = 'Not authorized';

        $this->assertEquals(403, $response->getStatusCode());
        $this->assertEquals($expected, $result);
    }

    public function testAuthorizeExistingUserEmptyPass()
    {
        try {
            $response = $this->client->get('/version', ['auth' => [$this->knownuser['name'], '']]);
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            $response = $e->getResponse();
        }
        $result = $response->getBody()->getContents();
        $expected = 'Not authorized';

        $this->assertEquals(403, $response->getStatusCode());
        $this->assertEquals($expected, $result);
    }

    public function testAuthorizeUnknownUser()
    {
        try {
            $response = $this->client->get('/version', ['auth' => [$this->unknownuser['name'], $this->unknownuser['pass']]]);
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            $response = $e->getResponse();
        }
        $result = $response->getBody()->getContents();
        $expected = 'Not authorized';

        $this->assertEquals(403, $response->getStatusCode());
        $this->assertEquals($expected, $result);
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
            $keysToCheck = array('paid', 'expense', 'balance', 'uid');
            $totalscheck = array('paid' => 0, 'expense' => 0, 'balance' => 0);
            foreach ($resultMemberArray as $uid => $member) {
                foreach ($keysToCheck as $key)
                    $this->assertArrayHasKey($key, $member, "Key '{$key}' not found in 'members' array at index/uid {$uid} of group {$group['gid']}");
                $msg_b = "Balance for member {$uid} in group {$group['gid']} ";
                if ($member['paid'] > $member['expense']){
                    $msg = $msg_b . "should be positive, but is not (paid: {$member['paid']} | expense: {$member['expense']} | balance: {$member['balance']})";
                    $this->assertGreaterThan(0,$member['balance'], $msg);
                } elseif ($member['paid'] < $member['expense']){
                    $msg = $msg_b . "should be negative, but is not (paid: {$member['paid']} | expense: {$member['expense']} | balance: {$member['balance']})";
                    $this->assertLessThan(0,$member['balance'], $msg);
                } else {
                    $msg = $msg_b . "should be zero, but is not (paid: {$member['paid']} | expense: {$member['expense']} | balance: {$member['balance']})";
                    $this->assertEquals(0,$member['balance'], $msg);
                }
                $totalscheck['paid'] += $member['paid'];
                $totalscheck['expense'] += $member['expense'];
                $totalscheck['balance'] += $member['balance'];
            }

            // check group totals and balance
            $group_balance = round($totalscheck['paid'] - $totalscheck['expense'],2);
            $this->assertEquals(0,$group_balance, "Paid totals are not equal to expense totals for group {$group['gid']} ( total paid: {$totalscheck['paid']} | total expense: {$totalscheck['expense']})");
            $this->assertEquals(0,round($totalscheck['balance'],2), "Total balance for group {$group['gid']} is not zero (but {$totalscheck['balance']}");

            $this->assertInternalType('array', $group['categories']);
            $this->assertGreaterThan(0, count($group['categories']));
            $keysToCheck = array('cid', 'group_id', 'title', 'presents', 'inactive', 'can_delete', 'sort');
            foreach ($group['categories'] as $category) {
                foreach ($keysToCheck as $key)
                    $this->assertArrayHasKey($key, $category, "Key '{$key}' not found in 'categories' array of group {$group['gid']}");
            }
        }

    }
}