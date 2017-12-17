<?php

use Laravel\Lumen\Testing\DatabaseTransactions;
use App\Bankings\BankAccount;

class CloseAccountTest extends TestCase
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
    public function a_user_close_his_bank_account()
    {
        $this->actingAs($this->bankAccount->user);
        $this->delete("v1/bank-accounts/{$this->bankAccount->uuid}")->seeJson([
            'message' => 'BANK_ACCOUNT_CLOSED'
        ]);
        $this->assertTrue($this->bankAccount->fresh()->is_active === 0);
    }
}
