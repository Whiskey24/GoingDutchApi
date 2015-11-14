<?php
//namespace Slim\Tests;

use Slim\App;
use GuzzleHttp\Client;

class AuthorizationTest extends \PHPUnit_Framework_TestCase
{
    protected $client;

    protected $knownuser = array('name' => 'Whiskey', 'email' => 'test@test.com', 'pass' => 'testpassword');
    protected $unknownuser = array('name' => 'whiskea', 'email' => 'test2@test.com', 'pass' => 'testpassword');

    protected function setUp()
    {
        $this->client = new GuzzleHttp\Client([
            'base_uri' => 'http://api.gdutch.dev',
            'defaults' => ['exceptions' => false]
        ]);
    }

    public function testAuthorizeExistingUserCorrectPass()
    {
        // test authentication with username
        $response = $this->client->get('/version', ['auth' => [$this->knownuser['name'], $this->knownuser['pass']]]);
        $result = $response->getBody()->getContents();
        $resultArray = json_decode($result, true);
        $expected = 'Going Dutch API';
        $this->assertEquals(200, $response->getStatusCode(), "Could not authenticate with username");
        $this->assertEquals($expected, $resultArray['service']);
        $this->assertGreaterThan(0, $resultArray['uid']);

        // test authentication with email address
        $response = $this->client->get('/version', ['auth' => [$this->knownuser['email'], $this->knownuser['pass']]]);
        $result = $response->getBody()->getContents();
        $this->assertEquals(200, $response->getStatusCode(), "Could not authenticate with email address");
        $this->assertEquals($expected, $resultArray['service']);
        $this->assertGreaterThan(0, $resultArray['uid']);    }

    public function testAuthorizeExistingUserWrongPass()
    {
        // test authentication with wrong username
        try {
            $response = $this->client->get('/version', ['auth' => [$this->knownuser['name'], $this->knownuser['pass'] . '123']]);
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            $response = $e->getResponse();
        }
        $result = $response->getBody()->getContents();
        $expected = 'Not authorized';
        $this->assertEquals(403, $response->getStatusCode());
        $this->assertEquals($expected, $result);

        // test authentication with wrong email address
        try {
            $response = $this->client->get('/version', ['auth' => [$this->knownuser['email'], $this->knownuser['pass'] . '123']]);
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

        try {
            $response = $this->client->get('/version', ['auth' => [$this->knownuser['email'], '']]);
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

        try {
            $response = $this->client->get('/version', ['auth' => [$this->unknownuser['email'], $this->unknownuser['pass']]]);
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            $response = $e->getResponse();
        }
        $result = $response->getBody()->getContents();
        $expected = 'Not authorized';

        $this->assertEquals(403, $response->getStatusCode());
        $this->assertEquals($expected, $result);
    }

}