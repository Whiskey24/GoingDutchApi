<?php
//namespace Slim\Tests;

use Slim\App;
use GuzzleHttp\Client;

class AuthorizationTest extends \PHPUnit_Framework_TestCase
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

}