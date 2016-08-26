<?php
//namespace Slim\Tests;

use Slim\App;
use GuzzleHttp\Client;

class UsersTest extends \PHPUnit_Framework_TestCase
{
    protected $client;

    protected $knownuser = array('name' => 'exitspam-bert@yahoo.com', 'email' => 'exitspam-bert@yahoo.com', 'pass' => 'testpassword');
    protected $knownuser2 = array('user_id' => 2, 'name' => 'exitspam-daan@yahoo.com', 'email' => 'exitspam-daan@yahoo.com', 'pass' => 'testpassword');
    protected $knownuser3 = array('user_id' => 3, 'name' => 'exitspam-jp@yahoo.com', 'email' => 'exitspam-jp@yahoo.com', 'pass' => 'testpassword');
    protected $knownuser4 = array('user_id' => 4, 'name' => 'exitspam-martijn@yahoo.com', 'email' => 'exitspam-martijn@yahoo.com', 'pass' => 'testpassword');
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
        $response = $this->client->request('PUT', "/user/{$uid}/details", ['auth' => [$newDetails['email'], $this->knownuser['pass']], 'json' => $existingDetails]);
    }

    public function testEmailExists() {
        $details = array('email' =>  $this->knownuser['email']);
        $response = $this->client->request('POST', "/emailexists", ['json' => $details]);
        $content = $response->getBody()->getContents();
        $resultArray = json_decode($content, true);
        $this->assertArrayHasKey('exists', $resultArray, "EmailExists: Key 'exists' not found in response when checking for existing email address");
        $this->assertEquals(1, $resultArray['exists'], "EmailExists: email not flagged as existing");

        $details = array('email' => 'aa@bb.com');
        $response = $this->client->request('POST', "/emailexists", ['json' => $details]);
        $content = $response->getBody()->getContents();
        $resultArray = json_decode($content, true);
        $this->assertArrayHasKey('exists', $resultArray, "EmailExists: Key 'exists' not found in response when checking for existing email address");
        $this->assertEquals(0, $resultArray['exists'], "EmailExists: email flagged as existing while it should not be");
        $this->assertEquals(0, $resultArray['exists'], "EmailExists: email flagged as existing while it should not be");
    }

    public function testAddDeleteNewUser()
    {
        $newDetails = array();
        $newDetails['firstName'] = "Test1";
        $newDetails['lastName']  = "Test2";
        $newDetails['fullName']  = "Test1 Test2";
        $newDetails['nickName']  = "Test3";
        $newDetails['pass']  = "1234";
        $newDetails['email'] = "test-email-" . time();

        $response = $this->client->request('POST', "/user", ['json' => $newDetails]);
        $content = $response->getBody()->getContents();
        $resultArray = json_decode($content, true);

        $this->assertArrayHasKey('success', $resultArray, "AddNewUser: Key 'success' not found in response when adding new user");
        $this->assertArrayHasKey('uid', $resultArray, "AddNewUser: Key 'uid' not found in response when adding new user");
        $this->assertEquals(1, $resultArray['success'], "AddNewUser: Could not add new user");

        // see if new user can be authorized
        $response = $this->client->get('/version', ['auth' => [$newDetails['email'], $newDetails['pass']]]);
        $result = $response->getBody()->getContents();
        $resultArray = json_decode($result, true);
        $expected = 'Going Dutch API';
        $this->assertEquals(200, $response->getStatusCode(), "AddNewUser: Could not authenticate with email");
        $this->assertEquals($expected, $resultArray['service']);
        $this->assertGreaterThan(0, $resultArray['uid']);

        $uid = $resultArray['uid'];
        $delDetails = array();
        $delDetails['email'] = $newDetails['email'];

        // try to delete the user with wrong id
        $delDetails['uid'] = $uid -1;
        $response = $this->client->request('DELETE', "/user", ['auth' => [$this->knownuser['name'], $this->knownuser['pass']], 'json' => $delDetails]);
        $content = $response->getBody()->getContents();
        $resultArray = json_decode($content, true);
        $this->assertArrayHasKey('success', $resultArray, "DeleteUser: Key 'success' not found in response when deleting user");
        $this->assertEquals(0, $resultArray['success'], "DeleteUser: Could delete user with non valid uid / email combination");

        // try to delete the user
        $delDetails['uid'] = $uid;
        $response = $this->client->request('DELETE', "/user", ['auth' => [$newDetails['email'], $newDetails['pass']], 'json' => $delDetails]);
        $content = $response->getBody()->getContents();
        $resultArray = json_decode($content, true);
        $this->assertArrayHasKey('success', $resultArray, "DeleteUser: Key 'success' not found in response when deleting user");
        $this->assertEquals(1, $resultArray['success'], "DeleteUser: Could not delete the new user");
    }

    public function testForgetPwd()
    {
        $userDetails = array('email' => $this->knownuser2['email']);
        $response = $this->client->request('POST', "/user/forgetpwd", ['json' => $userDetails]);
        $content = $response->getBody()->getContents();
        $resultArray = json_decode($content, true);
        $this->assertArrayHasKey('success', $resultArray, "ForgetPwd: Key 'success' not found in response when requesting new password");
        $this->assertEquals(1, $resultArray['success'], "ForgetPwd: Could not get new password for  " . $this->knownuser2['email']);
    }

}