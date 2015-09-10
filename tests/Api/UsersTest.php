<?php
//namespace Slim\Tests;

use Slim\App;
use GuzzleHttp\Client;

class UsersTest extends \PHPUnit_Framework_TestCase
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
}