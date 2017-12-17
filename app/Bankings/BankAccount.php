<?php

namespace App\Bankings;

use Illuminate\Database\Eloquent\Model;

class BankAccount extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'balance' => 'float'
    ];

    public function user()
    {
        return $this->belongsTo('App\User');
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function byUuid($uuid)
    {
        return self::where('uuid', $uuid)->lockForUpdate()->firstOrFail();
    }
}
