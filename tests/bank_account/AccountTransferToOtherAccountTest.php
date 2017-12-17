<?php

use App\Bankings\BankAccount;
use App\Bankings\Transaction;
use App\Bankings\Policies\BankAccountTransfer;
use Laravel\Lumen\Testing\DatabaseTransactions;

class AccountTransferToOtherAccountTest extends TestCase
{
    use DatabaseTransactions;

    public function setUp()
    {
        parent::setUp();
        $this->bankAccount       = Factory(BankAccount::class)->create(['balance' => 5000]);
        $this->targetBankAccount = Factory(BankAccount::class)->create();
        $this->init_user_the_5_thousands_transfer_transaction_first();
        $this->actingAs($this->bankAccount->user);
    }

    /**
     * @test
     */
    public function a_user_transfer_to_other_bank_account_and_pay_service_fee()
    {
        $input = [
            'amount' => rand(500, 1000)
        ];
        $sourceBalance = $this->bankAccount->balance;
        $manager       = (new BankAccountTransfer($this->bankAccount, $this->targetBankAccount))->handle($input);
        $serviceFee    = $manager->getServiceFee();
        $transaction   = $serviceFee->getTransaction();
        $this->assertEquals(100, $serviceFee->getAmount());
        $this->assertEquals(100, $transaction->amount);
        $this->assertEquals('credit', $transaction->flag);
        $this->assertEquals($this->bankAccount->id, $transaction->bank_account_id);

        $account = $manager->getSourceAccount();
        $this->assertEquals($sourceBalance - 100 - $input['amount'], $account->balance);
    }

    /**
     * @test
     */
    public function transfer_fails_if_service_fee_plus_target_amount_excess_balance()
    {
        $input = [
            'amount' => 4999
        ];
        $this->expectExceptionMessage('INSUFFICIENT_ACCOUNT_BALANCE_FOR_SERVICE_FEE');
        $manager       = (new BankAccountTransfer($this->bankAccount, $this->targetBankAccount))->handle($input);
    }

    protected function init_user_the_5_thousands_transfer_transaction_first()
    {
        // this is just to complete the bank's creation for this testing, because the model factory does not generate the transaction object upon bank account creation
        Factory(Transaction::class)->create([
          'amount'          => 5000,
          'to_account_id'   => $this->bankAccount->id,
          'bank_account_id' => $this->bankAccount->id,
        ]);
    }
}
