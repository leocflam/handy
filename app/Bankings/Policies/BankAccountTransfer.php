<?php

namespace App\Bankings\Policies;

use App\Bankings\Apis\HandyAPI;
use App\Bankings\Transaction;
use App\Bankings\Fees\TransferServiceFee;
use App\Exceptions\Bankings\BankAccountException;

class BankAccountTransfer
{
    private $sourceAccount;
    private $targetAccount;
    private $sourceTransaction;
    private $targetTransaction;
    private $serviceFee;
    private $dailyLimit       = 10000;
    private $serviceFeeAmount = 100;

    public function __construct($sourceAccount, $targetAccount)
    {
        $this->sourceAccount = $sourceAccount;
        $this->targetAccount = $targetAccount;
    }

    public function handle($input)
    {
        $this->sourceAccountMustBeActive();
        $this->targetAccountMustBeActive();
        $this->sourceAccountMustHaveSufficientBalance($input);
        $this->sourceAccountMustBeReachDailyLimit($input);

        if ($this->sourceAccount->user_id !== $this->targetAccount->user_id) {
            if (!(new HandyAPI)->getTransferApprove()) {
                throw new BankAccountException('TRANSFER_REJECTED_BY_HANDY');
            }
            $fee = new TransferServiceFee($this->sourceAccount);
            $this->sourceAccountMustHaveBalanceForFeePlusTargetAmount($fee, $input);
            $this->serviceFee = $this->chargeServiceFee($fee);
        }
        $fee = $this->serviceFee ? $this->serviceFee->getAmount() : 0;
        $this->sourceAccount->decrement('balance', $input['amount'] + $fee);
        $this->targetAccount->increment('balance', $input['amount']);

        // $this->account = $account;

        $this->storeTransactions($this->sourceAccount, $this->targetAccount, $input);
        // store servicefee

        return $this;
    }

    public function getSourceAccount()
    {
        return $this->sourceAccount;
    }

    public function getTargetAccount()
    {
        return $this->targetAccount;
    }

    public function getSourceTransaction()
    {
        return $this->sourceTransaction;
    }

    public function getTargetTransaction()
    {
        return $this->targetTransaction;
    }

    public function getServiceFee()
    {
        return $this->serviceFee;
    }

    protected function sourceAccountMustBeActive()
    {
        if (!$this->sourceAccount->is_active) {
            throw new BankAccountException('SOURCE_ACCOUNT_INACTIVE');
        }
    }

    protected function targetAccountMustBeActive()
    {
        if (!$this->targetAccount->is_active) {
            throw new BankAccountException('TARGET_ACCOUNT_INACTIVE');
        }
    }

    protected function sourceAccountMustHaveSufficientBalance($input)
    {
        if ($this->sourceAccount->balance < $input['amount']) {
            throw new BankAccountException('INSUFFICIENT_ACCOUNT_BALANCE');
        }
    }

    protected function sourceAccountMustHaveBalanceForFeePlusTargetAmount(TransferServiceFee $fee, $input)
    {
        if (($fee->getAmount() + $input['amount']) > $this->sourceAccount->balance) {
            throw new BankAccountException('INSUFFICIENT_ACCOUNT_BALANCE_FOR_SERVICE_FEE');
        }
    }

    protected function sourceAccountMustBeReachDailyLimit($input)
    {
        $todayVolume = $this->sourceAccount->transactions()->todayTransfer()->sum('amount');
        if (($todayVolume + $input['amount']) > $this->dailyLimit) {
            throw new BankAccountException('MAXIMUM_TRANSFER_LIMIT_REACHED');
        }
    }

    protected function storeTransactions($sourceAccount, $targetAccount, $input)
    {
        if (!isset($input['amount']) || !$input['amount']) {
            return false;
        }
        $sourceTransaction = new Transaction([
            'type'              => 'transfer',
            'amount'            => $input['amount'],
            'from_account_id'   => $sourceAccount->uuid,
            'from_bank_name'    => 'Handy Test Bank Ltd',
            'to_account_id'     => $targetAccount->uuid,
            'to_bank_name'      => 'Handy Test Bank Ltd',
            'bank_account_id'   => $sourceAccount->id,
            'flag'              => 'credit'
            // credit
        ]);
        $sourceTransaction->bankAccount()->associate($sourceAccount);
        $sourceTransaction->save();

        $this->sourceTransaction = $sourceTransaction;

        $targetTransaction = new Transaction([
            'type'              => 'transfer',
            'amount'            => $input['amount'],
            'from_account_id'   => $targetAccount->uuid,
            'from_bank_name'    => 'Handy Test Bank Ltd',
            'to_account_id'     => $sourceAccount->uuid,
            'to_bank_name'      => 'Handy Test Bank Ltd',
            'bank_account_id'   => $targetAccount->id,
            'flag'              => 'debit'
        ]);
        $targetTransaction->bankAccount()->associate($targetAccount);
        $targetTransaction->save();

        $this->targetTransaction = $targetTransaction;

        // to make things simple, assume the transaction log is created.
    }

    protected function chargeServiceFee(TransferServiceFee $fee)
    {
        return $fee->apply(new Transaction());
    }
}
