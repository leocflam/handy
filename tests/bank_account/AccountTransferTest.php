<?php

use App\Bankings\BankAccount;
use App\Bankings\Transaction;
use Laravel\Lumen\Testing\DatabaseTransactions;
use App\Bankings\Policies\BankAccountTransfer;

class AccountTransferTest extends TestCase
{
    use DatabaseTransactions;

    public function setUp()
    {
        parent::setUp();
        $this->bankAccount       = Factory(BankAccount::class)->create(['balance' => 5000]);
        $this->targetBankAccount = Factory(BankAccount::class)->create(['user_id' => $this->bankAccount->user->id]);
        $this->init_user_the_5_thousands_transfer_transaction_first();
        $this->actingAs($this->bankAccount->user);
    }

    /**
     * @test
     */
    public function a_user_transfer_to_his_bank()
    {
        $input = [
          'amount' => rand(500, 1000)
        ];
        $sourceBalance                   = $this->bankAccount->balance;
        $targetBalance                   = $this->targetBankAccount->balance;
        $manager                         = (new BankAccountTransfer($this->bankAccount, $this->targetBankAccount))->handle($input);
        $targetAccount                   = $manager->getTargetAccount();
        $targetTransaction               = $manager->getTargetTransaction();
        $sourceTransaction               = $manager->getSourceTransaction();
        $sourceAccount                   = $manager->getSourceAccount();

        $this->assertEquals($targetBalance + $input['amount'], $targetAccount->balance);
        $this->assertEquals($sourceBalance - $input['amount'], $sourceAccount->balance);

        $this->assertEquals($input['amount'], $targetTransaction->amount);
        $this->assertEquals($input['amount'], $sourceTransaction->amount);

        $this->assertTrue('transfer' === $targetTransaction->type);
        $this->assertTrue('transfer' === $sourceTransaction->type);

        $this->assertTrue('debit' === $targetTransaction->flag);
        $this->assertTrue('credit' === $sourceTransaction->flag);
    }

    /**
     * @test
     */
    public function a_user_transfer_to_his_bank_via_api()
    {
        $input = [
          'amount'      => rand(1000, 1500),
          'target_uuid' => $this->targetBankAccount->uuid,
        ];
        // the api return update status of source account
        $this->post("v1/bank-accounts/{$this->bankAccount->uuid}/transactions/transfer", $input)
             ->seeJson([
               'message' => 'TRANSFER_ACCEPTED',
               'balance' => $this->bankAccount->balance - $input['amount'],
               'amount'  => $input['amount'],
             ]);
    }

    /**
     * @test
     */
    public function transaction_fails_if_source_account_inactive()
    {
        $input = [
          'amount'      => rand(1000, 1500),
          'target_uuid' => $this->targetBankAccount->uuid,
        ];
        $this->bankAccount->is_active = false;
        $this->bankAccount->save();
        $this->post("v1/bank-accounts/{$this->bankAccount->uuid}/transactions/transfer", $input)
           ->seeJson(['message' => 'SOURCE_ACCOUNT_INACTIVE']);
    }

    /**
     * @test
     */
    public function transaction_fails_if_target_account_inactive()
    {
        $input = [
          'amount'      => rand(1000, 1500),
          'target_uuid' => $this->targetBankAccount->uuid,
        ];
        $this->targetBankAccount->is_active = false;
        $this->targetBankAccount->save();
        $this->post("v1/bank-accounts/{$this->bankAccount->uuid}/transactions/transfer", $input)
           ->seeJson(['message' => 'TARGET_ACCOUNT_INACTIVE']);
    }

    /**
     * @test
     */
    public function transaction_fails_if_insufficient_balance_in_source_bank_account()
    {
        $insufficientAmount = $this->bankAccount->balance * 1.2;
        $input              = [
          'amount' => $insufficientAmount
        ];
        $this->expectExceptionMessage('INSUFFICIENT_ACCOUNT_BALANCE');
        $manager = (new BankAccountTransfer($this->bankAccount, $this->targetBankAccount))->handle($input);
    }

    /**
     * @test
     */
    public function transaction_fails_if_reaches_10k_limit_per_day()
    {
        $history = Factory(Transaction::class)->create([
          'bank_account_id' => $this->bankAccount->id,
          'amount'          => 10000,
          'type'            => 'transfer',
          'flag'            => 'credit'
        ]);

        $input              = [
          'amount' => rand(1, 1000)
        ];
        $this->expectExceptionMessage('MAXIMUM_TRANSFER_LIMIT_REACHED');
        $manager = (new BankAccountTransfer($this->bankAccount, $this->targetBankAccount))->handle($input);

        // I can submit next day
    }

    /**
     * @test
     */
    public function transaction_fails_if_sum_of_target_amount_plus_today_cummulative_amount_reaches_10k_limit_per_day()
    {
        $history = Factory(Transaction::class)->create([
          'bank_account_id' => $this->bankAccount->id,
          'amount'          => 8000,
          'type'            => 'transfer',
          'flag'            => 'credit'
        ]);

        $history = Factory(Transaction::class)->create([
          'bank_account_id' => $this->bankAccount->id,
          'amount'          => 1000,
          'type'            => 'transfer',
          'flag'            => 'credit'
        ]);

        $input              = [
          'amount' => 3000
        ];
        $this->expectExceptionMessage('MAXIMUM_TRANSFER_LIMIT_REACHED');
        $manager = (new BankAccountTransfer($this->bankAccount, $this->targetBankAccount))->handle($input);
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
