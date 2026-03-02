<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatReadStatus extends Model
{
    protected $table = 'chat_read_status';

    const CREATED_AT = null;

    protected $fillable = [
        'uid',
        'last_read_message_id',
    ];

    protected $casts = [
        'updated_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'uid', 'uid');
    }
}
