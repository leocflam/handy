<?php

namespace App\Bankings\Policies;

use App\Bankings\BankAccount;
use App\Bankings\Transaction;

class OpenBankAccount
{
    private $user;
    private $account;

    public function __construct($user)
    {
        $this->user = $user;
    }

    public function handle($input)
    {
        $account          = new BankAccount();
        $account->user()->associate($this->user);
        $account->balance = $input['deposit'] ?? 0;
        $account->uuid    = str_random(60);
        $account->save();

        $this->account = $account;

        $this->storeFirstTransaction($account, $input);

        return $this;
    }

    public function getAccount()
    {
        return $this->account;
    }

    protected function storeFirstTransaction($account, $input)
    {
        if (!isset($input['deposit']) || !$input['deposit']) {
            return false;
        }
        $transaction = new Transaction([
            'type'            => 'deposit',
            'amount'          => $input['deposit'],
            'to_account_id'   => $account->uuid,
            'to_bank_name'    => 'Handy Test Bank Ltd'
        ]);
        $transaction->bankAccount()->associate($account);
        $transaction->save();

        // to make things simple, assume the transaction log is created.
    }
}
