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

    public function testExpenseAddDelete()
    {
        $this->AddExpense('all');
        $this->AddExpense('two');
        $this->AddExpense('settle');
    }

    public function testUpdateExpense()
    {
        $response = $this->client->get('/groups', ['auth' => [$this->knownuser['name'], $this->knownuser['pass']]]);
        $content = $response->getBody()->getContents();
        $resultArray = json_decode($content, true);
        $group = $resultArray[$this->gid];
        //$groupMembers = explode(',', $group['user_id_list']);
        $oldBalanceArray = $group['members'];

        $newEid = $this->AddExpense('all', false);
        $response = $this->client->get("/group/{$this->gid}/expenses/{$newEid}", ['auth' => [$this->knownuser['name'], $this->knownuser['pass']]]);
        $content = $response->getBody()->getContents();
        $resultArray = json_decode($content, true);

        $memberList = explode(',', $resultArray['uids']);

        $newOwner = array_shift(array_slice($memberList, 1, 1, true));
        $removeMember = array_pop($memberList);
        $memberListStr = implode(',', $memberList);

        $newExpense = array(
            'eid' => $newEid,
            'etitle' => $resultArray['etitle'] . utf8_encode(' - update'),
            'amount' => 200,
            'timezoneoffset' => $resultArray['timezoneoffset'],
            'event_id' => $resultArray['event_id'],
            'deposit_count' => $resultArray['deposit_count'],
            'depid' => $resultArray['depid'],
            'gid' => $resultArray['gid'],
            'cid' => $resultArray['cid'],
            'type' => $resultArray['type'],
            'uid' => $newOwner,
            'uids' => $memberListStr,
            'ecreated' => time(),
            'eupdated' => time()
        );

        $calcBalanceArray = $oldBalanceArray;
        $amountPP = $newExpense['amount'] / count($memberList);
        $calcBalanceArray[$newOwner]['paid'] += $newExpense['amount'];
        foreach ($calcBalanceArray as $uid => $val){
            if (in_array($uid, $memberList)){
                $calcBalanceArray[$uid]['expense'] += $amountPP;
                $calcBalanceArray[$uid]['balance'] = $calcBalanceArray[$uid]['paid'] - $calcBalanceArray[$uid]['expense'];
            }
        }


        // Test response of expense update
        $response = $this->client->request('PUT', "/group/{$this->gid}/expenses", ['auth' => [$this->knownuser['name'], $this->knownuser['pass']], 'json' => $newExpense]);
        $content = $response->getBody()->getContents();
        $resultArray = json_decode($content, true);
        foreach ($newExpense as $key => $val){
            $this->assertArrayHasKey($key, $resultArray, "UpdateExpense: Key '{$key}' not found in added expense");
            if ($key == 'uids'){
                // http://stackoverflow.com/questions/3838288/phpunit-assert-two-arrays-are-equal-but-order-of-elements-not-important
                $actual = explode(',', $resultArray['uids']);
                $expected = explode(',', $val);
                $this->assertSame(array_diff($expected, $actual), array_diff($actual, $expected));
            }
            else {
                $this->assertEquals($val, $resultArray[$key], "UpdateExpense: '{$key}' not equal to value of added expense (expected {$val}, got $resultArray[$key])");
            }
        }

        // Test member balance as result of expense add
        $response = $this->client->get('/groups', ['auth' => [$this->knownuser['name'], $this->knownuser['pass']]]);
        $content = $response->getBody()->getContents();
        $resultArray = json_decode($content, true);

        $newBalanceArray = $resultArray[$this->gid]['members'];
        foreach ($calcBalanceArray as $uid => $val){
            $calc = round($calcBalanceArray[$uid]['balance'],2);
            $new = round($newBalanceArray[$uid]['balance'],2);
            $this->assertEquals($new, $calc, "Update expense: Calculated balance ({$calc}) for member '{$uid}' not equal to value of returned balance ({$new}) in group {$this->gid}");
        }
    }

    public function testExpenseAddNonGroupMember()
    {
        $response = $this->client->get('/groups', ['auth' => [$this->knownuser['name'], $this->knownuser['pass']]]);
        $content = $response->getBody()->getContents();
        $resultArray = json_decode($content, true);
        $group = $resultArray[$this->gid]['members'];

        $response = $this->client->get('/users', ['auth' => [$this->knownuser['name'], $this->knownuser['pass']]]);
        $content = $response->getBody()->getContents();
        $resultArray = json_decode($content, true);
        $users = $resultArray;

        $nonMembers = array_diff_key($users, $group);
        reset($nonMembers);
        $nonMemberUid = key ($nonMembers);
        reset($group);
        $member = key ($group);

        $newExpense = array(
            'etitle' => utf8_encode('Non member test'),
            'amount' => 100,
            'timezoneoffset' => 120,
            'event_id' => 0,
            'deposit_count' => null,
            'depid' => null,
            'gid' => $this->gid,
            'cid' => 1,
            'type' => 1,
            'uid' => $nonMemberUid,
            'uids' => implode(',', array_keys($group)),
            'ecreated' => time(),
            'eupdated' => time()
        );

        $response = $this->client->request('POST', "/group/{$this->gid}/expenses", ['auth' => [$this->knownuser['name'], $this->knownuser['pass']], 'json' => $newExpense]);
        $content = $response->getBody()->getContents();
        $expected = "Error: invalid uids";
        $this->assertEquals($expected, $content, "Adding expense with invalid owner uid did not generate expected result");

        $newExpense = array(
            'etitle' => utf8_encode('Non member test'),
            'amount' => 100,
            'timezoneoffset' => 120,
            'event_id' => 0,
            'deposit_count' => null,
            'depid' => null,
            'gid' => $this->gid,
            'cid' => 1,
            'type' => 1,
            'uid' => $member,
            'uids' => implode(',', array_keys($group)) .  ',' . $nonMemberUid,
            'ecreated' => time(),
            'eupdated' => time()
        );

        $response = $this->client->request('POST', "/group/{$this->gid}/expenses", ['auth' => [$this->knownuser['name'], $this->knownuser['pass']], 'json' => $newExpense]);
        $content = $response->getBody()->getContents();
        $expected = "Error: invalid uids";
        $this->assertEquals($expected, $content, "Adding expense with invalid participant uid did not generate expected result");


    }


        /*
         * $type can be 'all', 'two' or 'settle'
         */
    public function AddExpense($type, $delete = true)
    {
        $newExpense = array(
            'etitle' => 'Test Expense 123 ëäïöó!',
            'amount' => 100,
            'timezoneoffset' => 120,
            'event_id' => 0,
            'deposit_count' => null,
            'depid' => null,
            'gid' => $this->gid,
            'cid' => 1,
            'type' => 1,
            'ecreated' => time(),
            'eupdated' => time()
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
        $expense = json_decode($content, true);
        foreach ($newExpense as $key => $val){
            $this->assertArrayHasKey($key, $expense, "AddExpense type={$type}: Key '{$key}' not found in added expense");
            if ($key == 'uids'){
                // http://stackoverflow.com/questions/3838288/phpunit-assert-two-arrays-are-equal-but-order-of-elements-not-important
                $actual = explode(',', $expense['uids']);
                $expected = explode(',', $val);
                $this->assertSame(array_diff($expected, $actual), array_diff($actual, $expected));
            }
            else {
                $this->assertEquals($val, $expense[$key], "AddExpense type={$type}: '{$key}' not equal to value of added expense (expected {$val}, got " . $expense[$key] .  ")");
            }

        }
        $newEid = $expense['eid'];

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

        if (!$delete)
            return $newEid;

        $response = $this->client->request('DELETE', "/group/{$this->gid}/expenses/{$newEid}", ['auth' => [$this->knownuser['name'], $this->knownuser['pass']]]);
        $content = $response->getBody()->getContents();
        $this->assertEquals($content, $newEid, "AddExpense type={$type}: Unexpected response for delete request of expense {$newEid}) in group {$this->gid}");

        // Test member balance as result of expense delete
        $response = $this->client->get('/groups', ['auth' => [$this->knownuser['name'], $this->knownuser['pass']]]);
        $content = $response->getBody()->getContents();
        $resultArray = json_decode($content, true);

        $newBalanceArray = $resultArray[$this->gid]['members'];
        foreach ($oldBalanceArray as $uid => $val){
            $old = $oldBalanceArray[$uid]['balance'];
            $new = $newBalanceArray[$uid]['balance'];
            $this->assertEquals($new, $old, "AddExpense type={$type}: Balance before expense delete ({$old}) for member '{$uid}' not equal to value of returned balance ({$new}) after delete in group {$this->gid}");
        }

        // test if expense has been stored in delete table
        $response = $this->client->get("/group/{$this->gid}/expensesdel/{$newEid}", ['auth' => [$this->knownuser['name'], $this->knownuser['pass']]]);
        $content = $response->getBody()->getContents();
        $resultArray = json_decode($content, true);
        foreach ($this->expenseKeysToCheck as $key) {
            if ($key == 'deposit_count')
                continue;
            $this->assertEquals($resultArray[$key], $expense[$key], "'{$key}' not equal to expected in deleted expense #{$newEid} ");
        }

        return $newEid;
    }

}