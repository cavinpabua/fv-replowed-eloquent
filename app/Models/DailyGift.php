<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DailyGift extends Model
{
    protected $table = 'daily_gifts';

    protected $fillable = [
        'uid',
        'cash_amount',
        'gold_amount',
        'claimed_at'
    ];

    protected $casts = [
        'claimed_at' => 'date'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'uid', 'uid');
    }
}
