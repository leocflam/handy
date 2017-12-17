<?php

use App\Bankings\BankAccount;
use App\Bankings\Transaction;
use Laravel\Lumen\Testing\DatabaseTransactions;
use App\Bankings\Policies\BankAccountWithdrawal;

class AccountWithdrawTest extends TestCase
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
    public function a_user_withdraw_from_his_bank_account_balance()
    {
        $input = [
          'amount' => rand(500, 1000)
        ];
        $beforeBalance = $this->bankAccount->balance;
        $manager       = (new BankAccountWithdrawal($this->bankAccount))->handle($input);
        $transaction   = $manager->getTransaction();
        $account       = $manager->getAccount();
        $this->assertEquals($beforeBalance - $input['amount'], $account->balance);
        $this->assertEquals($input['amount'], $transaction->amount);
        $this->assertEquals('withdraw', $transaction->type);
    }

    /**
     * @test
     */
    public function a_user_cannot_withdraw_an_amount_excess_balance()
    {
        $tooMuchAmount = $this->bankAccount->balance * 1.2;
        $input         = [
            'amount' => $tooMuchAmount
        ];
        $this->expectExceptionMessage('INSUFFICIENT_BANK_ACCOUNT_BALANCE');
        $manager       = (new BankAccountWithdrawal($this->bankAccount))->handle($input);
    }

    /**
     * @test
     */
    public function a_user_cannot_withdraw_if_account_inactive()
    {
        $this->bankAccount->is_active = false;
        $this->bankAccount->save();
        $input         = [
            'amount' => rand(1000, 2000)
        ];
        $this->expectExceptionMessage('BANK_ACCOUNT_INACTIVE');
        $manager       = (new BankAccountWithdrawal($this->bankAccount))->handle($input);
    }

    /**
     * @test
     */
    public function a_user_withdraw_from_his_bank_account_balance_via_api()
    {
        // $this->actingAs($this->bankAccount->user);
        $input = [
            'amount' => rand(1000, 2000)
        ];
        $expectedNewBalance = $this->bankAccount->balance - $input['amount'];
        $this->post("v1/bank-accounts/{$this->bankAccount->uuid}/transactions/withdraw", $input)->seeJson([
          'balance' => $expectedNewBalance,
          'amount'  => $input['amount'],
          'flag'    => 'credit',
          'message' => 'WITHDRAWAL_ACCEPTED'
        ]);
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
