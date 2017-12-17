<?php

namespace App\Http\Controllers\Bankings;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Bankings\Policies\OpenBankAccount;

class BankAccountsController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth', ['only' => ['store']]);
        $this->middleware('self_bank_account', ['only' => ['show', 'destroy']]);
    }

    public function show(Request $request)
    {
        return new JsonResponse([
            'data'    => $request->bankAccount
        ]);
    }

    public function store(Request $request)
    {
        $this->validate($request, [
          'deposit' => 'numeric|required|min:0|max:99999999'
        ]);
        $handler = (new OpenBankAccount($request->user()))->handle($request->all());
        return new JsonResponse([
          'message' => 'BANK_ACCOUNT_OPENED',
          'data'    => $handler->getAccount()
        ]);
    }

    public function destroy(Request $request, $uuid)
    {
        $bankAccount            = $request->bankAccount;
        $bankAccount->is_active = false;
        $bankAccount->save();
        return new JsonResponse([
          'message' => 'BANK_ACCOUNT_CLOSED',
          'data'    => $bankAccount
        ]);
    }

    //
}
