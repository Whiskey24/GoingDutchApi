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


}



/*
           $response = $this->client->get('/books', [
                   'query' => [
                       'bookId' => 'hitchhikers-guide-to-the-galaxy'
                   ]
               ]);

               $this->assertEquals(200, $response->getStatusCode());

               $data = $response->json();

               $this->assertArrayHasKey('bookId', $data);
               $this->assertArrayHasKey('title', $data);
               $this->assertArrayHasKey('author', $data);
               $this->assertEquals(42, $data['price']);
         */


/*
// create our http client (Guzzle)
$client = new Client('http://api.gdutch.dev:80', array(
    'request.options' => array(
        'exceptions' => false,
    )
));

$nickname = 'ObjectOrienter'.rand(0, 999);
$data = array(
    'nickname' => $nickname,
    'avatarNumber' => 5,
    'tagLine' => 'a test dev!'
);

$request = $client->post('', null, json_encode($data));
$response = $request->send();

$this->assertEquals(201, $response->getStatusCode());
$this->assertTrue($response->hasHeader('Location'));
$data = json_decode($response->getBody(true), true);
$this->assertArrayHasKey('nickname', $data);
*/