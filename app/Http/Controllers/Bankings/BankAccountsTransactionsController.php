<?php

namespace App\Http\Controllers\Bankings;

use Illuminate\Http\Request;
use App\Bankings\BankAccount;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Bankings\Policies\BankAccountDeposit;
use App\Bankings\Policies\BankAccountTransfer;
use App\Bankings\Policies\BankAccountWithdrawal;
use App\Exceptions\Bankings\BankAccountException;

class BankAccountsTransactionsController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        // $this->middleware('auth', ['only' => ['store']]);
        $this->middleware('self_bank_account', ['only' => ['withdraw', 'deposit', 'transfer']]);
    }

    public function withdraw(Request $request)
    {
        $this->validate($request, [
          'amount' => 'numeric|required|min:1|max:99999999'
        ]);
        $handler =  DB::transaction(function () use ($request) {
            return (new BankAccountWithdrawal($request->bankAccount))->handle($request->all());
        });
        return new JsonResponse([
          'message' => 'WITHDRAWAL_ACCEPTED',
          'data'    => [
            'account'     => $handler->getAccount(),
            'transaction' => $handler->getTransaction()
          ]
        ]);
    }

    public function deposit(Request $request)
    {
        $this->validate($request, [
          'amount' => 'numeric|required|min:1|max:99999999'
        ]);
        $handler =  DB::transaction(function () use ($request) {
            return (new BankAccountDeposit($request->bankAccount))->handle($request->all());
        });
        return new JsonResponse([
          'message' => 'DEPOSIT_ACCEPTED',
          'data'    => [
            'account'     => $handler->getAccount(),
            'transaction' => $handler->getTransaction()
          ]
        ]);
    }

    public function transfer(Request $request, BankAccount $targetAccount)
    {
        $this->validate($request, [
          'amount'      => 'numeric|required|min:1|max:99999999',
          'target_uuid' => 'required'
        ]);
        $targetAccount = $targetAccount->byUuid($request->input('target_uuid'));
        try {
            $handler =  DB::transaction(function () use ($request, $targetAccount) {
                return (new BankAccountTransfer($request->bankAccount, $targetAccount))->handle($request->all());
            });
        } catch (BankAccountException $e) {
            return new JsonResponse(['message' => $e->getMessage(), 'success' => 0], 422);
        }
        return new JsonResponse([
          'message' => 'TRANSFER_ACCEPTED',
          'data'    => [
            'account'     => $handler->getSourceAccount(),
            'transaction' => $handler->getSourceTransaction()
          ]
        ]);
    }
}
