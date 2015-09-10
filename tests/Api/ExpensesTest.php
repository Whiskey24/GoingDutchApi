<?php
//namespace Slim\Tests;

use Slim\App;
use GuzzleHttp\Client;

class ExpensesTest extends \PHPUnit_Framework_TestCase
{
    protected $client;

    protected $knownuser = array('name' => 'whiskey', 'pass' => 'testpassword');
    protected $unknownuser = array('name' => 'whiskea', 'pass' => 'testpassword');
    protected $gid = 1;

    protected function setUp()
    {
        $this->client = new GuzzleHttp\Client([
            'base_uri' => 'http://api.gdutch.dev',
            'defaults' => ['exceptions' => false]
        ]);
    }

    public function testExpensesArrayStructure()
    {
        $response = $this->client->get("/group/{$this->gid}/expenses", ['auth' => [$this->knownuser['name'], $this->knownuser['pass']]]);
        $content = $response->getBody()->getContents();
        $resultArray = json_decode($content, true);

        $this->assertInternalType('array', $resultArray, "Expenses call does not return an array");
        $this->assertGreaterThan(0, count($resultArray), "Expenses call/array does not contain any entries");

        reset($resultArray);
        $first_key = key($resultArray);
        $this->assertEquals($this->gid, $first_key, "Expenses call/array is not encapsulated in an array with group id as key");

        // only check first 3 and last 3 entries
        $index = 0;
        $keysToCheck = array('eid', 'etitle', 'uid', 'amount', 'ecreated', 'eupdated', 'timezoneoffset', 'event_id', 'depid', 'uids', 'deposit_count');
        foreach ($resultArray[$this->gid] as $expense) {
            $index++;
            if ($index > 3 && $index <= (count($resultArray[$this->gid]) - 3))
                continue;
            foreach ($keysToCheck as $key) {
                $this->assertArrayHasKey($key, $expense, "Key '{$key}' not found in expense array #{$index} of expenses call/array");
            }
        }
    }
}