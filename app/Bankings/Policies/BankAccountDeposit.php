<?php

namespace App\Bankings\Policies;

use App\Bankings\BankAccount;
use App\Bankings\Transaction;
use App\Exceptions\Bankings\BankAccountException;

class BankAccountDeposit
{
    private $bankAccount;
    private $transaction;

    public function __construct($bankAccount)
    {
        $this->bankAccount = $bankAccount;
    }

    public function handle($input)
    {
        $this->bankAccountMustBeActive();
        $balance = $this->bankAccount->balance;
        $this->bankAccount->increment('balance', $input['amount']);
        $this->storeTransaction($this->bankAccount, $input);
        // $account          = new BankAccount();
        // $account->user()->associate($this->user);
        // $account->balance = $input['deposit'] ?? 0;
        // $account->uuid    = str_random(60);
        // $account->save();

        // $this->account = $account;

        return $this;
    }

    public function bankAccountMustBeActive()
    {
        if (!$this->bankAccount->is_active) {
            throw new BankAccountException('BANK_ACCOUNT_INACTIVE');
        }
    }

    public function getAccount()
    {
        return $this->bankAccount;
    }

    public function getTransaction()
    {
        return $this->transaction;
    }

    protected function storeTransaction($account, $input)
    {
        $transaction = new Transaction([
            'type'            => 'deposit',
            'amount'          => $input['amount'],
            'to_account_id'   => $account->uuid,
            'to_bank_name'    => 'Handy Test Bank Ltd',
            'flag'            => 'debit'
        ]);
        $transaction->bankAccount()->associate($account);
        $transaction->save();
        $this->transaction = $transaction;

        // to make things simple, assume the transaction log is created.
    }
}
