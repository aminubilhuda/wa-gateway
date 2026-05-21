<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MenuSession extends Model
{
    protected $guarded = [];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    public function autoReply()
    {
        return $this->belongsTo(AutoReply::class);
    }

    public static function getActiveSession($phoneNumber)
    {
        return static::where('phone_number', $phoneNumber)
            ->where('expires_at', '>', now())
            ->latest()
            ->first();
    }

    public static function createOrUpdateSession($phoneNumber, $autoReplyId, $ttlMinutes = 10)
    {
        return static::updateOrCreate(
            ['phone_number' => $phoneNumber],
            [
                'auto_reply_id' => $autoReplyId,
                'expires_at' => now()->addMinutes($ttlMinutes),
            ]
        );
    }
}
