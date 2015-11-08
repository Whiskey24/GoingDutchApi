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
    protected $eid = 1;
    protected $expenseKeysToCheck = array('eid', 'etitle', 'uid', 'cid', 'type', 'amount', 'ecreated', 'eupdated', 'timezoneoffset', 'event_id', 'depid', 'uids', 'deposit_count', 'gid');

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
        $keysToCheck = $this->expenseKeysToCheck;
        foreach ($resultArray[$this->gid] as $expense) {
            $index++;
            if ($index > 3 && $index <= (count($resultArray[$this->gid]) - 3))
                continue;
            foreach ($keysToCheck as $key) {
                $this->assertArrayHasKey($key, $expense, "Key '{$key}' not found in expense array #{$index} of expenses call/array");
                if ($key == 'gid'){
                    $this->assertEquals($this->gid, $expense[$key], "'{$key}' not equal to expected group id ({$this->gid}) in expense array #{$index} of expenses call/array");
                }
            }
        }
    }

    public function testExpenseRead()
    {
        $response = $this->client->get("/group/{$this->gid}/expenses/{$this->eid}", ['auth' => [$this->knownuser['name'], $this->knownuser['pass']]]);
        $content = $response->getBody()->getContents();
        $resultArray = json_decode($content, true);
        foreach ($this->expenseKeysToCheck as $key) {
            $this->assertArrayHasKey($key, $resultArray, "Key '{$key}' not found in expense #{$this->eid}");
            if ($key == 'gid'){
                $this->assertEquals($this->gid, $resultArray[$key], "'{$key}' not equal to expected group id ({$this->gid}) in expense #{$this->eid}");
            }
            else if ($key == 'eid'){
                $this->assertEquals($this->eid, $resultArray[$key], "'{$key}' not equal to expected expense id ({$this->eid}) in expense #{$this->eid} ");
            }
        }
    }

    public function testExpenseAdd()
    {
        $this->AddExpense('all');
        $this->AddExpense('two');
        $this->AddExpense('settle');
    }


    /*
     * $type can be 'all', 'two' or 'settle'
     */
    public function AddExpense($type)
    {
        $newExpense = array(
            'etitle' => utf8_encode('Test Expense 123 äëö!'),
            'amount' => 100,
            'timezoneoffset' => 120,
            'event_id' => 0,
            'deposit_count' => null,
            'depid' => null,
            'gid' => $this->gid,
            'cid' => 1,
            'type' => 1
        );

        $response = $this->client->get('/groups', ['auth' => [$this->knownuser['name'], $this->knownuser['pass']]]);
        $content = $response->getBody()->getContents();
        $resultArray = json_decode($content, true);

        $group = $resultArray[$this->gid];

        switch($type){
            case 'two':
                // get second member to settle with
                $memberList = explode(',', $group['user_id_list']);
                $expenseOwner = array_shift(array_values($memberList));
                $memberList =  array($expenseOwner, array_shift(array_slice($memberList, 1, 1, true)));
                break;
            case 'settle':
                // get second member to settle with
                $memberList = explode(',', $group['user_id_list']);
                $expenseOwner = array_shift(array_values($memberList));
                $memberList =  array(array_shift(array_slice($memberList, 1, 1, true)));
                break;
            case 'all':
                $memberList = explode(',', $group['user_id_list']);
                $expenseOwner = array_shift(array_values($memberList));
                break;
        }

        $newExpense['uid'] = $expenseOwner;
        $newExpense['uids'] = implode(',', $memberList);
        $oldBalanceArray = $group['members'];

        $amountPP = $newExpense['amount'] / count($memberList);
        $calcBalanceArray = $oldBalanceArray;
        foreach ($calcBalanceArray as $uid => $val){
            if (in_array($uid, $memberList))
                $calcBalanceArray[$uid]['balance'] -= $amountPP;
        }
        $calcBalanceArray[$expenseOwner]['balance'] += $newExpense['amount'] ;

        // Test response of expense add
        $response = $this->client->request('POST', "/group/{$this->gid}/expenses", ['auth' => [$this->knownuser['name'], $this->knownuser['pass']], 'json' => $newExpense]);
        $content = $response->getBody()->getContents();
        $resultArray = json_decode($content, true);
        foreach ($newExpense as $key => $val){
            $this->assertArrayHasKey($key, $resultArray, "AddExpense type={$type}: Key '{$key}' not found in added expense");
            $this->assertEquals($val, $resultArray[$key], "AddExpense type={$type}: '{$key}' not equal to value of added expense (expected {$val}, got $resultArray[$key])");
        }
        $newEid = $resultArray['eid'];

        // Test member balance as result of expense add
        $response = $this->client->get('/groups', ['auth' => [$this->knownuser['name'], $this->knownuser['pass']]]);
        $content = $response->getBody()->getContents();
        $resultArray = json_decode($content, true);

        $newBalanceArray = $resultArray[$this->gid]['members'];
        foreach ($calcBalanceArray as $uid => $val){
            $calc = $calcBalanceArray[$uid]['balance'];
            $new = $newBalanceArray[$uid]['balance'];
            $this->assertEquals($new, $calc, "AddExpense type={$type}: Calculated balance ({$calc}) for member '{$uid}' not equal to value of returned balance ({$new}) in group {$this->gid}");
        }

        $response = $this->client->request('DELETE', "/group/{$this->gid}/expenses/{$newEid}", ['auth' => [$this->knownuser['name'], $this->knownuser['pass']]]);

    }

}