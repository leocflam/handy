<?php

namespace App\Bankings;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
      'type', 'amount', 'to_account_id', 'to_bank_name', 'from_account_id', 'from_bank_name', 'flag'
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [
        'password',
    ];

    public function bankAccount()
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function scopeTodayTransfer($query)
    {
        $start = Carbon::today()->toDateTimeString();
        $end   = Carbon::tomorrow()->toDateTimeString();
        $query->where('type', 'transfer')->where('flag', 'credit')->whereBetween('created_at', [$start, $end]);
    }
}
