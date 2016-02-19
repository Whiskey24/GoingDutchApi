<?php
//namespace Slim\Tests;

use Slim\App;
use GuzzleHttp\Client;

class UsersTest extends \PHPUnit_Framework_TestCase
{
    protected $client;

    protected $knownuser = array('name' => 'whiskey', 'pass' => 'testpassword');
    protected $unknownuser = array('name' => 'whiskea', 'pass' => 'testpassword');
    protected $uidNotInOwnGroups = 999;

    protected function setUp()
    {
        $this->client = new GuzzleHttp\Client([
            'base_uri' => 'http://api.gdutch.dev',
            'defaults' => ['exceptions' => false]
        ]);
    }

    public function testUsersArrayStructure()
    {
        $response = $this->client->get('/users', ['auth' => [$this->knownuser['name'], $this->knownuser['pass']]]);
        $content = $response->getBody()->getContents();
        $resultArray = json_decode($content, true);

        $this->assertInternalType('array', $resultArray, "Users call does not return an array");
        $this->assertGreaterThan(0, count($resultArray), "Users call/array does not contain any entries");
        foreach ($resultArray as $user) {
            $keysToCheck = array('uid', 'nickName', 'active', 'created', 'email', 'realname', 'firstName', 'lastName', 'updated');
            $index = 1;
            foreach ($keysToCheck as $key) {
                $this->assertArrayHasKey($key, $user, "Key '{$key}' not found in user array #{$index} of users call/array");
                $index++;
            }
        }
    }

    public function testUserGroupSort()
    {
        $response = $this->client->get('/version', ['auth' => [$this->knownuser['name'], $this->knownuser['pass']]]);
        $result = $response->getBody()->getContents();
        $resultArray = json_decode($result, true);
        $uid = $resultArray['uid'];

        $response = $this->client->get('/groups', ['auth' => [$this->knownuser['name'], $this->knownuser['pass']]]);
        $content = $response->getBody()->getContents();
        $resultArray = json_decode($content, true);

        // create sort groups array
        $sortGroups = array();
        foreach ($resultArray as $gid => $group) {
            $sortGroups[$gid] = array ('gid' => $gid, 'sort' => $group['sort']);
        }

        $count = count($sortGroups);
        $newSort = $sortGroups;
        foreach ($newSort as &$group) {
            $group['sort'] = $count;
            $count--;
        }

        $response = $this->client->request('PUT', "/user/{$uid}/groups", ['auth' => [$this->knownuser['name'], $this->knownuser['pass']], 'json' => $newSort]);
        $content = $response->getBody()->getContents();
        $resultArray = json_decode($content, true);

        $newSortKeys = array_keys($newSort);
        $resultKeys = array_keys($resultArray);
        sort($newSortKeys);
        sort($resultKeys);

        $sameGroups = $newSortKeys == $resultKeys ? true : false;
        $this->assertEquals($sameGroups, true, "UserGroupSort: a different set of groups was returned than the set submitted");

        foreach ($newSort as $gid => $val) {
            $this->assertEquals($resultArray[$gid]['sort'], $val['sort'], "UserGroupSort: expected sort {$val['sort']} for group {$gid}, but got sort {$resultArray[$gid]['sort']}");
        }

        // reset old values
        $response = $this->client->request('PUT', "/user/{$uid}/groups", ['auth' => [$this->knownuser['name'], $this->knownuser['pass']], 'json' => $sortGroups]);
        $content = $response->getBody()->getContents();
    }


    /*user update json
 {
		"uid": 1,
		"firstName": "Jane",
		"lastName": "Doe",
		"nickName": "JD"
		"realname": "Jennifer Diade,
		"email": "jd@diade-email.com",
}
 */
    public function testGetUserDetails()
    {
        // check that details of a user not in own groups cannot be retrieved
        $response = $this->client->get("/user/{$this->uidNotInOwnGroups}/details", ['auth' => [$this->knownuser['name'], $this->knownuser['pass']]]);
        $content = $response->getBody()->getContents();
        $this->assertEquals('Error: invalid request', $content, "Unexpected response for retrieving a user that is not in own groups");

        // first get uid of current user
        $response = $this->client->get('/version', ['auth' => [$this->knownuser['name'], $this->knownuser['pass']]]);
        $result = $response->getBody()->getContents();
        $resultArray = json_decode($result, true);
        $uid = $resultArray['uid'];

        $expenseKeysToCheck = array('uid', 'firstName', 'lastName', 'nickName', 'realname', 'email');

        $response = $this->client->get("/user/{$uid}/details", ['auth' => [$this->knownuser['name'], $this->knownuser['pass']]]);
        $content = $response->getBody()->getContents();
        $resultArray = json_decode($content, true);
        foreach ($expenseKeysToCheck as $key) {
            $this->assertArrayHasKey($key, $resultArray, "Key '{$key}' not found in user details for user  #{$uid}");
            if ($key == 'uid'){
                $this->assertEquals($uid, $resultArray[$key], "'{$key}' not equal to expected uid");
            }
        }
    }

    public function testUpdateUserDetails()
    {
        // first get uid of current user
        $response = $this->client->get('/version', ['auth' => [$this->knownuser['name'], $this->knownuser['pass']]]);
        $result = $response->getBody()->getContents();
        $resultArray = json_decode($result, true);
        $uid = $resultArray['uid'];

        $response = $this->client->get("/user/{$uid}/details", ['auth' => [$this->knownuser['name'], $this->knownuser['pass']]]);
        $content = $response->getBody()->getContents();
        $existingDetails = json_decode($content, true);

        $newDetails = $existingDetails;
        $newDetails['firstName'] .= " Test1";
        $newDetails['lastName']  .= " Test2";
        $newDetails['nickName']  .= " Test3";
        $newDetails['realname']  .= " Test4";
        $newDetails['email'] = "test-" . $newDetails['email'];

        $response = $this->client->request('PUT', "/user/{$uid}/details", ['auth' => [$this->knownuser['name'], $this->knownuser['pass']], 'json' => $newDetails]);
        $content = $response->getBody()->getContents();
        $resultArray = json_decode($content, true);
        foreach ($newDetails as $key => $val){
            $this->assertArrayHasKey($key, $resultArray, "UpdateUser: Key '{$key}' not found in updated details");
            $this->assertEquals($val, $resultArray[$key], "UpdateUser: '{$key}' not equal to value of updated details (expected {$val}, got $resultArray[$key])");
        }

        // restore old values
        $response = $this->client->request('PUT', "/user/{$uid}/details", ['auth' => [$newDetails['nickName'], $this->knownuser['pass']], 'json' => $existingDetails]);
    }
}