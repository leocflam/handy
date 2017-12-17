<?php

use App\Bankings\Policies\OpenBankAccount;
use Laravel\Lumen\Testing\DatabaseTransactions;

class OpenAccountTest extends TestCase
{
    use DatabaseTransactions;

    public function setUp()
    {
        parent::setUp();
        $this->user = Factory('App\User')->create();
        $this->actingAs($this->user);
    }

    /**
     * @test
     */
    public function a_user_opens_an_bank_account()
    {
        $input = [
            'deposit'    => 500.00
        ];

        $account = (new OpenBankAccount($this->user))->handle($input)->getAccount();

        $this->assertEquals($input['deposit'], $account->balance);
        $firstTransaction = $account->transactions()->first();
        $this->assertEquals($input['deposit'], $firstTransaction->amount);
        $this->assertEquals('deposit', $firstTransaction->type);
        $this->assertEquals($this->user->id, $account->user_id);
    }

    /**
     * @test
     */
    public function a_user_opens_an_bank_account_via_api()
    {
        $input = [
            'deposit' => 5123.50
        ];
        $this->post('v1/bank-accounts', $input)->seeJson([
            'balance' => $input['deposit']
        ]);
    }
}
