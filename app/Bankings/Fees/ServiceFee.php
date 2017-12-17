<?php

namespace App\Bankings\Fees;

use App\Bankings\Transaction;

class ServiceFee
{
    protected $amount      = 5;
    protected $transaction = null;
    protected $bankAccount = null;

    public function __construct($bankAccount)
    {
        $this->bankAccount = $bankAccount;
    }

    public function getAmount()
    {
        return $this->amount;
    }

    public function getTransaction()
    {
        return $this->transaction;
    }

    public function apply(Transaction $transaction)
    {
        $transaction->amount = $this->getAmount();
        $transaction->type   = 'service_fee';
        $transaction->flag   = 'credit';
        $transaction->bankAccount()->associate($this->bankAccount);
        $transaction->save();
        $this->transaction = $transaction;
        return $this;
    }
}
