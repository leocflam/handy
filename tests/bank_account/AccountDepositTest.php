<?php

use App\Bankings\BankAccount;
use App\Bankings\Transaction;
use Laravel\Lumen\Testing\DatabaseTransactions;
use App\Bankings\Policies\BankAccountDeposit;

class AccountDepositTest extends TestCase
{
    use DatabaseTransactions;

    public function setUp()
    {
        parent::setUp();
        $this->bankAccount = Factory(BankAccount::class)->create(['balance' => 20000]);
        $this->init_user_the_20_thousands_deposit_transaction_first();
        $this->actingAs($this->bankAccount->user);
    }

    /**
     * @test
     */
    public function a_user_deposit_to_his_bank()
    {
        $input = [
          'amount' => rand(500, 1000)
        ];
        $beforeBalance = $this->bankAccount->balance;
        $manager       = (new BankAccountDeposit($this->bankAccount))->handle($input);
        $transaction   = $manager->getTransaction();
        $account       = $manager->getAccount();
        $this->assertEquals($beforeBalance + $input['amount'], $account->balance);
        $this->assertEquals($input['amount'], $transaction->amount);
        $this->assertEquals('deposit', $transaction->type);
    }

    /**
     * @test
     */
    public function a_user_deposit_to_his_bank_via_api()
    {
        // $this->actingAs($this->bankAccount->user);
        $input = [
            'amount' => rand(1000, 2000)
        ];
        $expectedNewBalance = $this->bankAccount->balance + $input['amount'];
        $this->post("v1/bank-accounts/{$this->bankAccount->uuid}/transactions/deposit", $input)->seeJson([
          'balance' => $expectedNewBalance,
          'amount'  => $input['amount'],
          'message' => 'DEPOSIT_ACCEPTED',
          'flag'    => 'debit'
        ]);
    }

    /**
     * @test
     */
    public function a_user_cannot_deposit_if_account_inactive()
    {
        $this->bankAccount->is_active = false;
        $this->bankAccount->save();
        $input         = [
            'amount' => rand(1000, 2000)
        ];
        $this->expectExceptionMessage('BANK_ACCOUNT_INACTIVE');
        $manager       = (new BankAccountDeposit($this->bankAccount))->handle($input);
    }

    protected function init_user_the_20_thousands_deposit_transaction_first()
    {
        // this is just to complete the bank's creation for this testing, because the model factory does not generate the transaction object upon bank account creation
        Factory(Transaction::class)->create([
          'amount'          => 20000,
          'to_account_id'   => $this->bankAccount->id,
          'bank_account_id' => $this->bankAccount->id,
        ]);
    }
}
