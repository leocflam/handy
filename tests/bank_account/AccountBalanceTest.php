<?php

use App\Bankings\BankAccount;
use Laravel\Lumen\Testing\DatabaseTransactions;

class AccountBalanceTest extends TestCase
{
    use DatabaseTransactions;

    public function setUp()
    {
        parent::setUp();
        $this->bankAccount = Factory(BankAccount::class)->create();
    }

    /**
     * @test
     */
    public function a_user_get_his_bank_account_balance()
    {
        $this->actingAs($this->bankAccount->user);
        $this->get("v1/bank-accounts/{$this->bankAccount->uuid}")->seeJson([
          'balance' => $this->bankAccount->balance
        ]);
    }
}
